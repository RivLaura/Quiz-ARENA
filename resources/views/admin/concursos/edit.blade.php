@extends('layouts.arena')

@section('title', 'Editar concurso — QUIZ ARENA')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <a href="{{ route('admin.concursos') }}" class="qa-menu-link" style="display: inline-block; margin-bottom: 20px;">← Volver a concursos</a>
        <div class="qa-badge">Editar concurso</div>
        <h1 class="qa-title" style="font-size: 32px;">ACTUALIZAR</h1>
        <p class="qa-desc">Modifica los datos generales de «{{ $quiz->title }}».</p>

        <form method="POST" action="{{ route('admin.concursos.update', $quiz) }}" class="qa-form" style="margin-top: 28px;">
            @csrf
            @method('PUT')

            <div style="text-align: left; margin-bottom: 16px;">
                <label for="title" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Título</label>
                <input id="title" type="text" name="title" value="{{ old('title', $quiz->title) }}" maxlength="255" required class="qa-input" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;" />
            </div>

            <div style="text-align: left; margin-bottom: 16px;">
                <label for="description" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Descripción</label>
                <textarea id="description" name="description" rows="3" maxlength="5000" class="qa-input" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <div style="text-align: left; margin-bottom: 16px;">
                <label for="category" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Categoría</label>
                <input id="category" type="text" name="category" value="{{ old('category', $quiz->category) }}" maxlength="255" required class="qa-input" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;" />
            </div>

            <div style="text-align: left; margin-bottom: 20px;">
                <label for="time_per_question" style="display: block; color: #94a3b8; font-size: 14px; font-weight: 600; margin-bottom: 6px;">Tiempo por pregunta (segundos)</label>
                <input id="time_per_question" type="number" name="time_per_question" value="{{ old('time_per_question', $quiz->time_per_question) }}" required min="5" max="300" step="1" class="qa-input" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(5, 6, 15, 0.7); color: #f8fafc; font-size: 15px;" />
            </div>

            <p style="color: #94a3b8; text-align: left; margin-bottom: 20px;">
                Estado actual: <strong>{{ $quiz->status === 'active' ? 'Activo' : 'Borrador' }}</strong>. Cambia el estado desde la lista de concursos.
            </p>
            <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">Guardar cambios</button>
        </form>
    </div>
@endsection
