<?php

namespace App\Services;

use App\Events\GameStateChanged;
use App\Models\GameRoom;
use App\Models\GameRoomQuestion;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameEngine
{
    public function advanceExpiredQuestions(): int
    {
        $advancedCount = 0;

        GameRoom::query()
            ->where('status', GameRoom::STATUS_PLAYING)
            ->whereNotNull('current_question_started_at')
            ->orderBy('id')
            ->chunkById(100, function ($rooms) use (&$advancedCount): void {
                foreach ($rooms as $room) {
                    $position = $room->current_question_position;
                    $updatedRoom = $this->advance($room, $position, true);

                    if (
                        $updatedRoom->status !== GameRoom::STATUS_PLAYING
                        || $updatedRoom->current_question_position !== $position
                    ) {
                        $advancedCount++;
                    }
                }
            });

        return $advancedCount;
    }

    public function start(GameRoom $gameRoom): GameRoom
    {
        $gameRoom = DB::transaction(function () use ($gameRoom): GameRoom {
            $gameRoom = GameRoom::query()->lockForUpdate()->findOrFail($gameRoom->id);

            if ($gameRoom->status !== GameRoom::STATUS_WAITING) {
                throw ValidationException::withMessages([
                    'game' => 'Solo puedes iniciar una sala que esté en espera.',
                ]);
            }

            if (! $gameRoom->players()->exists()) {
                throw ValidationException::withMessages([
                    'game' => 'Debe unirse al menos un jugador antes de iniciar la partida.',
                ]);
            }

            $questions = $gameRoom->quiz->questions()
                ->with(['options' => fn ($query) => $query->orderBy('position')])
                ->orderBy('position')
                ->get()
                ->shuffle()
                ->values();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages([
                    'game' => 'El concurso no tiene preguntas para iniciar la partida.',
                ]);
            }

            foreach ($questions as $index => $question) {
                $gameRoom->questionSnapshots()->create([
                    'question_id' => $question->id,
                    'position' => $index + 1,
                    'prompt' => $question->prompt,
                    'points' => $question->points,
                    'time_limit_seconds' => $question->time_limit_seconds,
                    'options' => $question->options
                        ->map(fn (QuestionOption $option): array => [
                            'id' => $option->id,
                            'label' => $option->label,
                            'text' => $option->text,
                            'is_correct' => $option->is_correct,
                        ])
                        ->all(),
                ]);
            }

            $startedAt = now();
            $gameRoom->ensureChannelToken();
            $gameRoom->update([
                'status' => GameRoom::STATUS_PLAYING,
                'started_at' => $startedAt,
                'current_question_position' => 1,
                'current_question_started_at' => $startedAt,
            ]);

            return $gameRoom->fresh(['quiz']);
        });

        $this->broadcastState($gameRoom);

        return $gameRoom;
    }

    public function advance(
        GameRoom $gameRoom,
        ?int $expectedPosition = null,
        bool $automatic = false,
    ): GameRoom {
        [$gameRoom, $advanced] = DB::transaction(function () use ($gameRoom, $expectedPosition, $automatic): array {
            $gameRoom = GameRoom::query()->lockForUpdate()->findOrFail($gameRoom->id);

            if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
                if ($expectedPosition !== null) {
                    return [$gameRoom, false];
                }

                throw ValidationException::withMessages([
                    'game' => 'Solo puedes avanzar una partida que esté en curso.',
                ]);
            }

            if ($expectedPosition !== null && $gameRoom->current_question_position !== $expectedPosition) {
                return [$gameRoom, false];
            }

            if ($automatic) {
                $questionSnapshot = $gameRoom->questionSnapshots()
                    ->where('position', $gameRoom->current_question_position)
                    ->first();

                if ($questionSnapshot === null || $gameRoom->current_question_started_at === null) {
                    throw ValidationException::withMessages([
                        'game' => 'No se pudo validar el tiempo de la pregunta actual.',
                    ]);
                }

                $timeLimit = $questionSnapshot->time_limit_seconds ?? $gameRoom->quiz->time_per_question;
                $deadline = $gameRoom->current_question_started_at->copy()->addSeconds($timeLimit);

                if (now()->lessThan($deadline)) {
                    return [$gameRoom, false];
                }
            }

            $nextQuestion = $gameRoom->questionSnapshots()
                ->where('position', '>', $gameRoom->current_question_position)
                ->orderBy('position')
                ->first();

            if ($nextQuestion === null) {
                $gameRoom->update([
                    'status' => GameRoom::STATUS_FINISHED,
                    'finished_at' => now(),
                    'current_question_started_at' => null,
                ]);
            } else {
                $gameRoom->update([
                    'current_question_position' => $nextQuestion->position,
                    'current_question_started_at' => now(),
                ]);
            }

            return [$gameRoom->fresh(['quiz']), true];
        });

        if ($advanced) {
            $this->broadcastState($gameRoom);
        }

        return $gameRoom;
    }

    public function submitAnswer(Player $player, int $optionId): PlayerAnswer
    {
        return DB::transaction(function () use ($player, $optionId): PlayerAnswer {
            $gameRoom = GameRoom::query()
                ->lockForUpdate()
                ->findOrFail($player->game_room_id);

            if ($gameRoom->status !== GameRoom::STATUS_PLAYING) {
                throw ValidationException::withMessages([
                    'answer' => 'La partida no está aceptando respuestas.',
                ]);
            }

            $question = $gameRoom->questionSnapshots()
                ->where('position', $gameRoom->current_question_position)
                ->first();

            if ($question === null || $gameRoom->current_question_started_at === null) {
                throw ValidationException::withMessages([
                    'answer' => 'No hay una pregunta activa en este momento.',
                ]);
            }

            $answeredAt = now();
            $timeLimit = $question->time_limit_seconds ?? $gameRoom->quiz->time_per_question;

            $deadline = $gameRoom->current_question_started_at->copy()->addSeconds($timeLimit);

            if ($answeredAt->greaterThanOrEqualTo($deadline)) {
                throw ValidationException::withMessages([
                    'answer' => 'Se terminó el tiempo para responder esta pregunta.',
                ]);
            }

            if ($player->answers()->where('game_room_question_id', $question->id)->exists()) {
                throw ValidationException::withMessages([
                    'answer' => 'Ya respondiste esta pregunta.',
                ]);
            }

            $option = collect($question->options)
                ->first(fn (array $option): bool => (int) $option['id'] === $optionId);

            if ($option === null) {
                throw ValidationException::withMessages([
                    'option_id' => 'Selecciona una opción válida para esta pregunta.',
                ]);
            }

            $isCorrect = $option['is_correct'];
            $responseTime = max(
                0,
                (int) $gameRoom->current_question_started_at->diffInMilliseconds($answeredAt),
            );

            $answer = $player->answers()->create([
                'game_room_question_id' => $question->id,
                'question_id' => $question->question_id,
                'question_option_id' => $option['id'],
                'selected_option_label' => $option['label'],
                'selected_option_text' => $option['text'],
                'is_correct' => $isCorrect,
                'response_time_ms' => $responseTime,
                'points_earned' => $isCorrect ? $question->points : 0,
                'answered_at' => $answeredAt,
            ]);

            if ($isCorrect) {
                $player->increment('score', $question->points);
            }

            return $answer;
        });
    }

    /**
     * @return array{
     *     status: string,
     *     code: string,
     *     question: array{id: int, snapshot_id: int, prompt: string, position: int, total: int, points: int, time_limit_seconds: int, started_at: string, options: array<int, array{id: int, label: string, text: string}>}|null,
     *     leaderboard: array<int, array{nickname: string, score: int}>
     * }
     */
    public function state(GameRoom $gameRoom): array
    {
        $gameRoom->loadMissing('quiz');

        $questionState = null;

        if ($gameRoom->status === GameRoom::STATUS_PLAYING) {
            $question = $this->currentQuestion($gameRoom);

            if ($question !== null && $gameRoom->current_question_started_at !== null) {
                $questionState = [
                    'id' => $question->question_id ?? $question->id,
                    'snapshot_id' => $question->id,
                    'prompt' => $question->prompt,
                    'position' => $question->position,
                    'total' => $gameRoom->questionSnapshots()->count(),
                    'points' => $question->points,
                    'time_limit_seconds' => $question->time_limit_seconds ?? $gameRoom->quiz->time_per_question,
                    'started_at' => $gameRoom->current_question_started_at->toIso8601String(),
                    'options' => collect($question->options)
                        ->map(fn (array $option): array => [
                            'id' => (int) $option['id'],
                            'label' => $option['label'],
                            'text' => $option['text'],
                        ])
                        ->all(),
                ];
            }
        }

        $leaderboard = $gameRoom->status === GameRoom::STATUS_FINISHED
            ? $gameRoom->players()
                ->orderByDesc('score')
                ->orderBy('nickname')
                ->get(['nickname', 'score'])
                ->map(fn (Player $player): array => [
                    'nickname' => $player->nickname,
                    'score' => $player->score,
                ])
                ->all()
            : [];

        return [
            'status' => $gameRoom->status,
            'code' => $gameRoom->code,
            'question' => $questionState,
            'leaderboard' => $leaderboard,
        ];
    }

    private function currentQuestion(GameRoom $gameRoom): ?GameRoomQuestion
    {
        return $gameRoom->questionSnapshots()
            ->where('position', $gameRoom->current_question_position)
            ->first();
    }

    private function broadcastState(GameRoom $gameRoom): void
    {
        GameStateChanged::dispatch($gameRoom, $this->state($gameRoom));
    }
}
