@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Concursos — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Acciones de concursos">
            <a href="{{ route('admin') }}" class="qa-menu-link">Panel</a>
            <a href="{{ route('admin.concursos.create') }}" class="qa-btn qa-btn-primary">Crear concurso</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Administración</div>
        <h1 class="qa-title" style="font-size: clamp(32px, 6vw, 54px);">CONCURSOS</h1>
        <p class="qa-desc">Crea y administra tus concursos. Solo tú puedes modificar los que has creado.</p>

        @if ($quizzes->isEmpty())
            <section class="qa-card" style="margin-top: 32px;">
                <h2 style="font-size: 20px;">Todavía no tienes concursos</h2>
                <p class="qa-desc" style="margin-top: 10px;">Crea tu primer borrador y luego agrega preguntas para poder activarlo.</p>
                <a href="{{ route('admin.concursos.create') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Crear mi primer concurso</a>
            </section>
        @else
            <section aria-label="Tus concursos" style="display: grid; gap: 16px; margin-top: 32px;">
                @foreach ($quizzes as $quiz)
                    <article class="qa-card" style="text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 220px;">
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <h2 style="font-size: 20px; font-weight: 800;">{{ $quiz->title }}</h2>
                                    <span class="qa-status qa-status-{{ $quiz->status }}">{{ $quiz->status === 'active' ? 'Activo' : 'Borrador' }}</span>
                                </div>
                                <p class="qa-dashboard-meta" style="margin-top: 8px;">
                                    {{ $quiz->category }} · {{ $quiz->questions_count }} {{ $quiz->questions_count === 1 ? 'pregunta' : 'preguntas' }} · {{ $quiz->time_per_question }} s por pregunta
                                </p>
                                @if ($quiz->description)
                                    <p style="margin-top: 12px; color: #cbd5e1; line-height: 1.6;">{{ $quiz->description }}</p>
                                @endif
                            </div>

                            <div class="qa-quiz-actions">
                                <a href="{{ route('admin.concursos.preguntas.index', $quiz) }}" class="qa-btn qa-btn-secondary">Preguntas ({{ $quiz->questions_count }})</a>
                                <a href="{{ route('admin.concursos.edit', $quiz) }}" class="qa-btn qa-btn-secondary">Editar</a>
                                <form method="POST" action="{{ route('admin.concursos.status', $quiz) }}">
                                    @csrf
                                    <button type="submit" class="qa-btn {{ $quiz->status === 'active' ? 'qa-btn-secondary' : 'qa-btn-primary' }}">
                                        {{ $quiz->status === 'active' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.concursos.destroy', $quiz) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este concurso?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="qa-btn qa-btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div style="margin-top: 24px;">
                {{ $quizzes->links() }}
            </div>
        @endif
    </main>
@endsection
