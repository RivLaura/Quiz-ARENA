<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = $request->user()
            ->hostedRooms()
            ->where('status', GameRoom::STATUS_FINISHED)
            ->with('quiz:id,title')
            ->withCount('players')
            ->latest('finished_at')
            ->paginate(10);

        return view('admin.resultados', compact('rooms'));
    }

    public function show(Request $request, GameRoom $gameRoom): View
    {
        $gameRoom = $request->user()
            ->hostedRooms()
            ->whereKey($gameRoom->getKey())
            ->where('status', GameRoom::STATUS_FINISHED)
            ->with([
                'quiz:id,title',
                'questionSnapshots.answers',
                'players' => fn (Relation $query) => $query
                    ->withCount([
                        'answers as correct_answers_count' => fn (Builder $answers) => $answers->where('is_correct', true),
                    ])
                    ->orderByDesc('score')
                    ->orderBy('nickname'),
            ])
            ->firstOrFail();

        $maximumScore = $gameRoom->questionSnapshots->sum('points');

        return view('admin.resultados.show', compact('gameRoom', 'maximumScore'));
    }
}
