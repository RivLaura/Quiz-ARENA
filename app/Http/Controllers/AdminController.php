<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\GameRoom;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function create(): View
    {
        $admin = request()->user();

        return view('admin', [
            'activeQuizzes' => $admin->quizzes()
                ->where('status', Quiz::STATUS_ACTIVE)
                ->count(),
            'totalRooms' => $admin->hostedRooms()->count(),
            'activeMatches' => $admin->hostedRooms()
                ->where('status', GameRoom::STATUS_PLAYING)
                ->count(),
        ]);
    }

    public function concursos()
    {
        Gate::authorize('viewAny', Quiz::class);

        $quizzes = auth()->user()
            ->quizzes()
            ->withCount('questions')
            ->latest()
            ->paginate(10);

        return view('admin.concursos', compact('quizzes'));
    }

    public function createConcursos(): View
    {
        Gate::authorize('create', Quiz::class);

        return view('admin.concursos.create');
    }

    public function storeConcursos(StoreQuizRequest $request): RedirectResponse
    {
        Gate::authorize('create', Quiz::class);

        $request->user()->quizzes()->create([
            ...$request->validated(),
            'status' => Quiz::STATUS_DRAFT,
        ]);

        return redirect()->route('admin.concursos')->with('ok', 'Concurso creado correctamente.');
    }

    public function editConcurso(Quiz $quiz): View
    {
        Gate::authorize('update', $quiz);

        return view('admin.concursos.edit', compact('quiz'));
    }

    public function updateConcurso(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz);

        $quiz->update($request->validated());

        return redirect()->route('admin.concursos')->with('ok', 'Concurso actualizado correctamente.');
    }

    public function destroyConcurso(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('delete', $quiz);

        if ($quiz->gameRooms()->exists()) {
            return back()->with('error', 'No se puede eliminar un concurso que ya tiene salas o historial de partidas.');
        }

        $quiz->delete();

        return redirect()->route('admin.concursos')->with('ok', 'Concurso eliminado correctamente.');
    }

    public function toggleConcursoStatus(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz);

        if ($quiz->status === Quiz::STATUS_ACTIVE) {
            $quiz->update(['status' => Quiz::STATUS_DRAFT]);

            return back()->with('ok', 'Concurso desactivado correctamente.');
        }

        if (! $quiz->hasValidQuestions()) {
            return back()->with('error', 'Para activar un concurso necesita al menos una pregunta con exactamente cuatro opciones y una respuesta correcta.');
        }

        $quiz->update(['status' => Quiz::STATUS_ACTIVE]);

        return back()->with('ok', 'Concurso activado correctamente.');
    }

    public function preguntas()
    {
        $quizzes = auth()->user()
            ->quizzes()
            ->withCount('questions')
            ->latest()
            ->get();

        return view('admin.preguntas', compact('quizzes'));
    }

    public function salas()
    {
        return view('admin.salas');
    }
}
