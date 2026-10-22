<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomQuestion;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_owned_finished_rooms_in_the_results_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['title' => 'Mi concurso']);
        $otherQuiz = Quiz::factory()->for($otherAdmin, 'admin')->create(['title' => 'Concurso ajeno']);
        $finishedRoom = GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);
        GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'code' => 'ACTIVE',
            'status' => GameRoom::STATUS_PLAYING,
        ]);
        GameRoom::factory()->for($otherQuiz)->for($otherAdmin, 'host')->create([
            'code' => 'FOREIG',
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resultados'))
            ->assertOk()
            ->assertSee('Mi concurso')
            ->assertSee($finishedRoom->code)
            ->assertSee('Ver resultados')
            ->assertDontSee('Concurso ajeno')
            ->assertDontSee('ACTIVE')
            ->assertDontSee('FOREIG');
    }

    public function test_finished_room_results_show_rankings_and_answer_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create(['title' => 'Ciencias']);
        $firstQuestion = $this->createQuestion($quiz, 1, '¿Cuál es el planeta rojo?', 'Marte', 'Venus', 10);
        $secondQuestion = $this->createQuestion($quiz, 2, '¿Qué estrella ilumina la Tierra?', 'Sol', 'Luna', 20);
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);
        $firstSnapshot = $this->snapshotQuestion($room, $firstQuestion);
        $secondSnapshot = $this->snapshotQuestion($room, $secondQuestion);
        $winner = $room->players()->create(['nickname' => 'Sol', 'score' => 20]);
        $runnerUp = $room->players()->create(['nickname' => 'Luna', 'score' => 10]);

        $this->recordAnswer($winner, $secondSnapshot, $secondQuestion->options()->where('is_correct', true)->firstOrFail(), true, 20, 1250);
        $this->recordAnswer($runnerUp, $firstSnapshot, $firstQuestion->options()->where('is_correct', true)->firstOrFail(), true, 10, 2400);
        $this->recordAnswer($runnerUp, $secondSnapshot, $secondQuestion->options()->where('is_correct', false)->firstOrFail(), false, 0, 3700);

        $this->actingAs($admin)
            ->get(route('admin.resultados.show', $room))
            ->assertOk()
            ->assertSee('Clasificación final')
            ->assertSee('1. Sol')
            ->assertSee('2. Luna')
            ->assertSee('20 puntos')
            ->assertSee('¿Cuál es el planeta rojo?')
            ->assertSee('Respuesta correcta: A. Marte')
            ->assertSee('B. Luna')
            ->assertSee('Correcta')
            ->assertSee('Incorrecta')
            ->assertSee('Sin respuesta')
            ->assertSee('3.70 s');
    }

    public function test_results_for_another_admins_room_return_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($otherAdmin, 'admin')->create();
        $room = GameRoom::factory()->for($quiz)->for($otherAdmin, 'host')->create([
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resultados.show', $room))
            ->assertNotFound();
    }

    public function test_results_for_a_room_that_has_not_finished_return_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'status' => GameRoom::STATUS_PLAYING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resultados.show', $room))
            ->assertNotFound();
    }

    public function test_results_escape_player_nicknames_in_rendered_html(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $room = GameRoom::factory()->for($quiz)->for($admin, 'host')->create([
            'status' => GameRoom::STATUS_FINISHED,
            'finished_at' => now(),
        ]);
        $room->players()->create(['nickname' => '<script>alert(1)</script>']);

        $this->actingAs($admin)
            ->get(route('admin.resultados.show', $room))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function createQuestion(
        Quiz $quiz,
        int $position,
        string $prompt,
        string $correctAnswer,
        string $incorrectAnswer,
        int $points,
    ): Question {
        $question = $quiz->questions()->create([
            'prompt' => $prompt,
            'position' => $position,
            'points' => $points,
        ]);

        foreach ([
            ['label' => 'A', 'text' => $correctAnswer, 'correct' => true],
            ['label' => 'B', 'text' => $incorrectAnswer, 'correct' => false],
            ['label' => 'C', 'text' => 'Opción C', 'correct' => false],
            ['label' => 'D', 'text' => 'Opción D', 'correct' => false],
        ] as $index => $option) {
            $question->options()->create([
                'label' => $option['label'],
                'text' => $option['text'],
                'position' => $index + 1,
                'is_correct' => $option['correct'],
            ]);
        }

        return $question;
    }

    private function recordAnswer(
        Player $player,
        GameRoomQuestion $questionSnapshot,
        QuestionOption $option,
        bool $isCorrect,
        int $points,
        int $responseTime,
    ): PlayerAnswer {
        return $player->answers()->create([
            'game_room_question_id' => $questionSnapshot->id,
            'question_id' => $questionSnapshot->question_id,
            'question_option_id' => $option->id,
            'selected_option_label' => $option->label,
            'selected_option_text' => $option->text,
            'is_correct' => $isCorrect,
            'response_time_ms' => $responseTime,
            'points_earned' => $points,
            'answered_at' => now(),
        ]);
    }

    private function snapshotQuestion(GameRoom $room, Question $question): GameRoomQuestion
    {
        return $room->questionSnapshots()->create([
            'question_id' => $question->id,
            'position' => $question->position,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'time_limit_seconds' => $question->time_limit_seconds,
            'options' => $question->options()
                ->orderBy('position')
                ->get()
                ->map(fn (QuestionOption $option): array => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'text' => $option->text,
                    'is_correct' => $option->is_correct,
                ])
                ->all(),
        ]);
    }
}
