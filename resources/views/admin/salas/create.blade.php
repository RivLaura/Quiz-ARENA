@extends('layouts.arena')

@section('title', 'Crear sala — QUIZ ARENA')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <a href="{{ route('admin.salas') }}" class="qa-menu-link" style="display: inline-block; margin-bottom: 20px;">← Volver a salas</a>
        <h1 class="qa-title" style="font-size: 32px;">CREAR SALA</h1>
        <p class="qa-desc">Selecciona uno de tus concursos activos para generar una sala y un código único.</p>

        @if ($errors->any())
            <div class="qa-alert qa-alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($quizzes->isEmpty())
            <section class="qa-card" style="margin-top: 28px;">
                <h2 style="font-size: 20px;">No tienes concursos activos</h2>
                <p class="qa-desc" style="margin-top: 10px;">Activa un concurso con al menos una pregunta completa para poder abrir una sala.</p>
                <a href="{{ route('admin.concursos') }}" class="qa-btn qa-btn-primary" style="margin-top: 24px;">Ir a concursos</a>
            </section>
        @else
            <form method="POST" action="{{ route('admin.salas.store') }}" class="qa-form" style="margin-top: 28px;">
                @csrf

                <div class="qa-field" style="text-align: left;">
                    <label for="quiz_id">Concurso activo</label>
                    <select id="quiz_id" name="quiz_id" required class="qa-input" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: #111328; color: #f8fafc; font-size: 15px;">
                        <option value="">Selecciona un concurso</option>
                        @foreach ($quizzes as $quiz)
                            <option value="{{ $quiz->id }}" @selected((string) old('quiz_id') === (string) $quiz->id)>
                                {{ $quiz->title }} · {{ $quiz->category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <p style="color: #94a3b8; text-align: left; margin: 18px 0 20px;">La sala comenzará en estado de espera. La entrada de jugadores se habilitará en el siguiente módulo.</p>
                <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Crear sala</button>
            </form>
        @endif
    </div>
@endsection
