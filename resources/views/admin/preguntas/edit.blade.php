@extends('layouts.arena')

@section('title', 'Editar pregunta — QUIZ ARENA')

@section('content')
    <div style="max-width: 760px; margin: 0 auto;">
        <a href="{{ route('admin.concursos.preguntas.index', $quiz) }}" class="qa-menu-link" style="display: inline-block; margin-bottom: 20px;">← Volver a preguntas</a>
        <div class="qa-badge">{{ $quiz->title }} · Pregunta {{ $question->position }}</div>
        <h1 class="qa-title" style="font-size: 32px;">EDITAR PREGUNTA</h1>
        <p class="qa-desc">Modifica el enunciado, las opciones, el puntaje o el tiempo límite.</p>

        @include('admin.preguntas._form', ['quiz' => $quiz, 'question' => $question])
    </div>
@endsection
