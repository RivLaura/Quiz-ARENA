@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Preguntas de ' . $quiz->title . ' — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación de preguntas">
            <a href="{{ route('admin.preguntas') }}" class="qa-menu-link">← Todos los concursos</a>
            <a href="{{ route('admin.concursos') }}" class="qa-menu-link">Concursos</a>
        </nav>
    </header>

    <main>
        <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 18px; flex-wrap: wrap;">
            <div>
                <div class="qa-badge">Gestión del concurso</div>
                <h1 class="qa-title" style="font-size: clamp(30px, 5vw, 48px);">{{ $quiz->title }}</h1>
                <p class="qa-desc" style="margin-left: 0;">{{ $questions->count() }} {{ $questions->count() === 1 ? 'pregunta' : 'preguntas' }} · {{ $quiz->time_per_question }} s predeterminados</p>
            </div>
            @if ($canManageQuestions)
                <a href="{{ route('admin.concursos.preguntas.create', $quiz) }}" class="qa-btn qa-btn-primary">Agregar pregunta</a>
            @endif
        </div>

        @unless ($canManageQuestions)
            <div class="qa-alert qa-alert-error" style="margin-top: 24px;">
                Finaliza las partidas o elimina las salas en espera de este concurso antes de administrar sus preguntas. El historial de partidas finalizadas se conserva.
            </div>
        @endunless

        @if ($questions->isEmpty())
            <section class="qa-card" style="margin-top: 28px; text-align: center;">
                <h2 style="font-size: 20px;">Todavía no hay preguntas</h2>
                <p class="qa-desc" style="margin-top: 10px;">Agrega la primera pregunta con sus cuatro opciones y una respuesta correcta.</p>
                @if ($canManageQuestions)
                    <a href="{{ route('admin.concursos.preguntas.create', $quiz) }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Crear primera pregunta</a>
                @endif
            </section>
        @else
            <section aria-label="Preguntas del concurso" style="display: grid; gap: 16px; margin-top: 28px;">
                @foreach ($questions as $index => $question)
                    <article class="qa-card" style="text-align: left;">
                        <div class="qa-question-heading">
                            <div>
                                <span class="qa-dashboard-meta">PREGUNTA {{ $index + 1 }} · {{ $question->points }} puntos · {{ $question->time_limit_seconds ? $question->time_limit_seconds . ' s' : $quiz->time_per_question . ' s predeterminados' }}</span>
                                <h2 style="font-size: 20px; font-weight: 800; margin-top: 8px;">{{ $question->prompt }}</h2>
                            </div>
                            @if ($canManageQuestions)
                                <div class="qa-question-actions">
                                    <form method="POST" action="{{ route('admin.concursos.preguntas.move', [$quiz, $question]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="qa-btn qa-btn-secondary" aria-label="Subir pregunta" @disabled($index === 0)>↑</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.concursos.preguntas.move', [$quiz, $question]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="qa-btn qa-btn-secondary" aria-label="Bajar pregunta" @disabled($index === $questions->count() - 1)>↓</button>
                                    </form>
                                    <a href="{{ route('admin.concursos.preguntas.edit', [$quiz, $question]) }}" class="qa-btn qa-btn-secondary">Editar</a>
                                    <form method="POST" action="{{ route('admin.concursos.preguntas.destroy', [$quiz, $question]) }}" onsubmit="return confirm('¿Eliminar esta pregunta y sus opciones?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="qa-btn qa-btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <ol class="qa-option-list" aria-label="Opciones de respuesta">
                            @foreach ($question->options as $option)
                                <li class="{{ $option->is_correct ? 'qa-option-correct' : '' }}">
                                    <span class="qa-option-label">{{ $option->label }}</span>
                                    <span>{{ $option->text }}</span>
                                    @if ($option->is_correct)
                                        <span class="qa-correct-indicator">Correcta</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
@endsection
