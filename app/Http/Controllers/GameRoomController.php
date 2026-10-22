<?php

namespace App\Http\Controllers;

use App\Events\RoomCreated;
use App\Http\Requests\StoreGameRoomRequest;
use App\Models\GameRoom;
use App\Models\PlayerAnswer;
use App\Models\Quiz;
use App\Services\GameEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameRoomController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', GameRoom::class);

        $rooms = request()->user()
            ->hostedRooms()
            ->with('quiz')
            ->withCount('players')
            ->latest()
            ->paginate(10);

        return view('admin.salas', compact('rooms'));
    }

    public function create(): View
    {
        Gate::authorize('create', GameRoom::class);

        $quizzes = request()->user()
            ->quizzes()
            ->where('status', Quiz::STATUS_ACTIVE)
            ->orderBy('title')
            ->get();

        return view('admin.salas.create', compact('quizzes'));
    }

    public function store(StoreGameRoomRequest $request): RedirectResponse
    {
        Gate::authorize('create', GameRoom::class);

        $room = DB::transaction(function () use ($request): GameRoom {
            $quiz = $request->user()
                ->quizzes()
                ->whereKey($request->validated('quiz_id'))
                ->where('status', Quiz::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $quiz->hasValidQuestions()) {
                throw ValidationException::withMessages([
                    'quiz_id' => 'El concurso debe tener preguntas completas antes de crear una sala.',
                ]);
            }

            return $request->user()->hostedRooms()->create([
                'quiz_id' => $quiz->id,
                'code' => GameRoom::generateUniqueCode(),
                'status' => GameRoom::STATUS_WAITING,
            ])->load('quiz');
        });

        $room->ensureChannelToken();
        RoomCreated::dispatch($room);

        return redirect()
            ->route('admin.salas.show', $room)
            ->with('ok', 'Sala creada correctamente.');
    }

    public function show(GameRoom $gameRoom): View
    {
        Gate::authorize('view', $gameRoom);

        $gameRoom->ensureChannelToken();
        $gameRoom->load(['quiz', 'players']);

        return view('admin.salas.show', [
            'room' => $gameRoom,
            'gameState' => app(GameEngine::class)->state($gameRoom),
        ]);
    }

    public function destroy(Request $request, GameRoom $gameRoom): RedirectResponse
    {
        Gate::authorize('delete', $gameRoom);

        $deleted = DB::transaction(function () use ($request, $gameRoom): bool {
            $room = $request->user()
                ->hostedRooms()
                ->lockForUpdate()
                ->findOrFail($gameRoom->id);

            if ($room->status !== GameRoom::STATUS_WAITING) {
                return false;
            }

            $hasAnswers = PlayerAnswer::query()
                ->whereIn('player_id', $room->players()->select('id'))
                ->exists();

            if ($hasAnswers) {
                return false;
            }

            $room->players()->delete();
            $room->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('admin.salas')
                ->with('error', 'Solo se pueden eliminar salas en espera sin respuestas registradas. Las partidas iniciadas conservan su historial.');
        }

        return redirect()
            ->route('admin.salas')
            ->with('ok', 'Sala eliminada correctamente.');
    }

    public function matches(): View
    {
        Gate::authorize('viewAny', GameRoom::class);

        $rooms = request()->user()
            ->hostedRooms()
            ->with('quiz')
            ->withCount('players')
            ->latest()
            ->paginate(10);

        return view('admin.partidas', compact('rooms'));
    }

    public function start(GameRoom $gameRoom, GameEngine $gameEngine): RedirectResponse
    {
        Gate::authorize('view', $gameRoom);
        $gameEngine->start($gameRoom);

        return redirect()
            ->route('admin.salas.show', $gameRoom)
            ->with('ok', 'La partida comenzó.');
    }

    public function advance(Request $request, GameRoom $gameRoom, GameEngine $gameEngine): RedirectResponse|JsonResponse
    {
        Gate::authorize('view', $gameRoom);
        $validated = $request->validate([
            'question_position' => ['required', 'integer', 'min:1'],
            'automatic' => ['sometimes', 'boolean'],
        ]);
        $wasPlaying = $gameRoom->status === GameRoom::STATUS_PLAYING;
        $previousPosition = $gameRoom->current_question_position;
        $updatedRoom = $gameEngine->advance(
            $gameRoom,
            (int) $validated['question_position'],
            (bool) ($validated['automatic'] ?? false),
        );
        $advanced = $wasPlaying
            && ($updatedRoom->status !== GameRoom::STATUS_PLAYING
                || $updatedRoom->current_question_position !== $previousPosition);

        if ($request->expectsJson()) {
            return response()->json(['advanced' => $advanced]);
        }

        return redirect()
            ->route('admin.salas.show', $gameRoom)
            ->with('ok', ! $advanced
                ? 'La partida ya había avanzado.'
                : ($updatedRoom->status === GameRoom::STATUS_FINISHED
                    ? 'La partida terminó.'
                    : 'Se avanzó a la siguiente pregunta.'));
    }
}
