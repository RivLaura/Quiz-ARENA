<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use App\Http\Requests\StorePlayerAnswerRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Models\GameRoom;
use App\Models\Player;
use App\Services\GameEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JoinController extends Controller
{
    public function create(): View
    {
        return view('join');
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $player = DB::transaction(function () use ($request): Player {
            $room = GameRoom::query()
                ->where('code', $request->validated('code'))
                ->where('status', GameRoom::STATUS_WAITING)
                ->lockForUpdate()
                ->first();

            if ($room === null) {
                throw ValidationException::withMessages([
                    'code' => 'No encontramos una sala abierta con ese código.',
                ]);
            }

            $room->ensureChannelToken();
            $nickname = $request->validated('nickname');
            $nicknameExists = $room->players()
                ->whereRaw('LOWER(nickname) = ?', [mb_strtolower($nickname)])
                ->exists();

            if ($nicknameExists) {
                throw ValidationException::withMessages([
                    'nickname' => 'Ese nombre ya está siendo usado en esta sala.',
                ]);
            }

            return $room->players()->create(['nickname' => $nickname]);
        });

        $joinedPlayers = $request->session()->get('joined_players', []);
        $joinedPlayers[$player->id] = true;
        $request->session()->put('joined_players', $joinedPlayers);

        PlayerJoined::dispatch($player->load('gameRoom'));

        return redirect()->route('join.lobby', $player);
    }

    public function answer(StorePlayerAnswerRequest $request, Player $player, GameEngine $gameEngine): RedirectResponse
    {
        $gameEngine->submitAnswer($player, $request->integer('option_id'));

        return redirect()->route('join.lobby', $player)->with('ok', 'Respuesta registrada.');
    }

    public function state(Request $request, Player $player, GameEngine $gameEngine): JsonResponse
    {
        abort_unless($request->session()->has('joined_players.'.$player->id), 404);

        $player->load('gameRoom');

        return response()->json($gameEngine->state($player->gameRoom));
    }

    public function advance(Request $request, Player $player, GameEngine $gameEngine): JsonResponse
    {
        abort_unless($request->session()->has('joined_players.'.$player->id), 404);

        $validated = $request->validate([
            'question_position' => ['required', 'integer', 'min:1'],
        ]);
        $player->load('gameRoom');
        $wasPlaying = $player->gameRoom->status === GameRoom::STATUS_PLAYING;
        $previousPosition = $player->gameRoom->current_question_position;
        $updatedRoom = $gameEngine->advance(
            $player->gameRoom,
            (int) $validated['question_position'],
            true,
        );
        $advanced = $wasPlaying
            && ($updatedRoom->status !== GameRoom::STATUS_PLAYING
                || $updatedRoom->current_question_position !== $previousPosition);

        return response()->json([
            'advanced' => $advanced,
            'state' => $gameEngine->state($updatedRoom),
        ]);
    }

    public function show(Request $request, Player $player, GameEngine $gameEngine): View
    {
        abort_unless($request->session()->has('joined_players.'.$player->id), 404);

        $player->load(['gameRoom.quiz', 'gameRoom.players']);
        $player->gameRoom->ensureChannelToken();
        $gameState = $gameEngine->state($player->gameRoom);
        $currentAnswer = isset($gameState['question'])
            ? $player->answers()->where('game_room_question_id', $gameState['question']['snapshot_id'])->first()
            : null;

        return view('join.lobby', compact('player', 'gameState', 'currentAnswer'));
    }
}
