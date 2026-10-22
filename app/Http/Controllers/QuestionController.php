<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveQuestionRequest;
use App\Http\Requests\SaveQuestionRequest;
use App\Models\GameRoom;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Quiz $quiz): View
    {
        Gate::authorize('update', $quiz);

        return view('admin.preguntas.index', [
            'quiz' => $quiz,
            'questions' => $quiz->questions()->with('options')->orderBy('position')->get(),
            'canManageQuestions' => $this->canManageQuestions($quiz),
        ]);
    }

    public function create(Quiz $quiz): View|RedirectResponse
    {
        Gate::authorize('update', $quiz);

        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        return view('admin.preguntas.create', compact('quiz'));
    }

    public function store(SaveQuestionRequest $request, Quiz $quiz): RedirectResponse
    {
        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        DB::transaction(function () use ($request, $quiz): void {
            $question = $quiz->questions()->create([
                'prompt' => $request->validated('prompt'),
                'points' => $request->validated('points'),
                'time_limit_seconds' => $request->validated('time_limit_seconds'),
                'position' => ((int) $quiz->questions()->max('position')) + 1,
            ]);

            $this->saveOptions($question, $request->validated('options'), $request->validated('correct_option'));
        });

        return redirect()
            ->route('admin.concursos.preguntas.index', $quiz)
            ->with('ok', 'Pregunta creada correctamente.');
    }

    public function edit(Quiz $quiz, Question $question): View|RedirectResponse
    {
        Gate::authorize('update', $quiz);

        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        $question->load('options');

        return view('admin.preguntas.edit', compact('quiz', 'question'));
    }

    public function update(SaveQuestionRequest $request, Quiz $quiz, Question $question): RedirectResponse
    {
        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'prompt' => $request->validated('prompt'),
                'points' => $request->validated('points'),
                'time_limit_seconds' => $request->validated('time_limit_seconds'),
            ]);

            $this->saveOptions($question, $request->validated('options'), $request->validated('correct_option'));
        });

        return redirect()
            ->route('admin.concursos.preguntas.index', $quiz)
            ->with('ok', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Quiz $quiz, Question $question): RedirectResponse
    {
        Gate::authorize('update', $quiz);

        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        $question->delete();

        return redirect()
            ->route('admin.concursos.preguntas.index', $quiz)
            ->with('ok', 'Pregunta eliminada correctamente.');
    }

    public function move(MoveQuestionRequest $request, Quiz $quiz, Question $question): RedirectResponse
    {
        if ($response = $this->questionManagementBlock($quiz)) {
            return $response;
        }

        DB::transaction(function () use ($request, $quiz, $question): void {
            $questions = $quiz->questions()->orderBy('position')->get();
            $currentIndex = $questions->search(fn (Question $item): bool => $item->is($question));

            if ($currentIndex === false) {
                abort(404);
            }

            $neighborIndex = $request->validated('direction') === 'up'
                ? $currentIndex - 1
                : $currentIndex + 1;

            if (! $questions->has($neighborIndex)) {
                return;
            }

            $currentQuestion = $questions->get($currentIndex);
            $neighborQuestion = $questions->get($neighborIndex);
            $currentPosition = $currentQuestion->position;
            $neighborPosition = $neighborQuestion->position;
            $temporaryPosition = ((int) $quiz->questions()->max('position')) + 1;

            $currentQuestion->update(['position' => $temporaryPosition]);
            $neighborQuestion->update(['position' => $currentPosition]);
            $currentQuestion->update(['position' => $neighborPosition]);
        });

        return redirect()
            ->route('admin.concursos.preguntas.index', $quiz)
            ->with('ok', 'Orden de preguntas actualizado.');
    }

    /**
     * @param  array<string, string>  $options
     */
    private function saveOptions(Question $question, array $options, string $correctOption): void
    {
        $question->options()->delete();

        foreach ($options as $label => $text) {
            $question->options()->create([
                'label' => $label,
                'text' => $text,
                'position' => ord($label) - ord('A') + 1,
                'is_correct' => $label === $correctOption,
            ]);
        }
    }

    private function canManageQuestions(Quiz $quiz): bool
    {
        return ! $quiz->gameRooms()
            ->whereIn('status', [GameRoom::STATUS_WAITING, GameRoom::STATUS_PLAYING])
            ->exists();
    }

    private function questionManagementBlock(Quiz $quiz): ?RedirectResponse
    {
        if ($quiz->gameRooms()
            ->whereIn('status', [GameRoom::STATUS_WAITING, GameRoom::STATUS_PLAYING])
            ->exists()) {
            return redirect()
                ->route('admin.concursos.preguntas.index', $quiz)
                ->with('error', 'Finaliza o elimina las salas abiertas antes de modificar las preguntas.');
        }

        return null;
    }
}
