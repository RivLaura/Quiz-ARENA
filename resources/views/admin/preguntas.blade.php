@extends('layouts.arena')

@section('layout-class', 'qa-wrap-wide')
@section('title', 'Preguntas — QUIZ ARENA')

@section('content')
    <header class="qa-header">
        <a href="{{ route('admin') }}" class="qa-brand" style="text-decoration: none;">QUIZ ARENA</a>
        <nav class="qa-nav" aria-label="Navegación administrativa">
            <a href="{{ route('admin') }}" class="qa-menu-link">Panel</a>
            <a href="{{ route('admin.concursos') }}" class="qa-menu-link">Concursos</a>
        </nav>
    </header>

    <main>
        <div class="qa-badge">Administración</div>
        <h1 class="qa-title" style="font-size: clamp(32px, 6vw, 54px);">PREGUNTAS</h1>
        <p class="qa-desc">Elige uno de tus concursos para administrar sus preguntas y respuestas.</p>

        @if ($quizzes->isEmpty())
            <section class="qa-card" style="margin-top: 32px; text-align: center;">
                <h2 style="font-size: 20px;">Primero crea un concurso</h2>
                <p class="qa-desc" style="margin-top: 10px;">Las preguntas pertenecen a un concurso.</p>
                <a href="{{ route('admin.concursos.create') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Crear concurso</a>
            </section>
        @else
            <section aria-label="Concursos disponibles" style="display: grid; gap: 16px; margin-top: 32px;">
                @foreach ($quizzes as $quiz)
                    <article class="qa-card" style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; text-align: left;">
                        <div>
                            <h2 style="font-size: 20px; font-weight: 800;">{{ $quiz->title }}</h2>
                            <p class="qa-dashboard-meta" style="margin-top: 8px;">
                                {{ $quiz->category }} · {{ $quiz->questions_count }} {{ $quiz->questions_count === 1 ? 'pregunta' : 'preguntas' }} · {{ $quiz->status === 'active' ? 'Activo' : 'Borrador' }}
                            </p>
                        </div>
                        <a href="{{ route('admin.concursos.preguntas.index', $quiz) }}" class="qa-btn qa-btn-secondary">Administrar preguntas</a>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
@endsection
