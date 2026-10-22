@extends('layouts.arena')

@section('title', 'Crear pregunta — QUIZ ARENA')

@section('content')
    <div style="max-width: 760px; margin: 0 auto;">
        <a href="{{ route('admin.concursos.preguntas.index', $quiz) }}" class="qa-menu-link" style="display: inline-block; margin-bottom: 20px;">← Volver a preguntas</a>
        <div class="qa-badge">{{ $quiz->title }}</div>
        <h1 class="qa-title" style="font-size: 32px;">NUEVA PREGUNTA</h1>
        <p class="qa-desc">Completa el enunciado, las cuatro opciones y marca una única respuesta correcta.</p>

        @include('admin.preguntas._form', ['quiz' => $quiz, 'question' => null])
    </div>
@endsection
