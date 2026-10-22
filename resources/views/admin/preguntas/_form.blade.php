@php
    $isEditing = $question !== null;
    $correctOption = $question?->options->firstWhere('is_correct', true)?->label;
@endphp

<form method="POST"
      action="{{ $isEditing ? route('admin.concursos.preguntas.update', [$quiz, $question]) : route('admin.concursos.preguntas.store', $quiz) }}"
      style="display: grid; gap: 18px; margin-top: 28px; text-align: left;">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="qa-field">
        <label for="prompt">Enunciado</label>
        <textarea id="prompt" name="prompt" rows="3" maxlength="2000" required>{{ old('prompt', $question?->prompt) }}</textarea>
    </div>

    <div class="qa-question-settings">
        <div class="qa-field">
            <label for="points">Puntaje</label>
            <input id="points" type="number" name="points" min="1" max="10000" step="1" value="{{ old('points', $question?->points ?? 100) }}" required>
        </div>
        <div class="qa-field">
            <label for="time_limit_seconds">Tiempo límite (segundos, opcional)</label>
            <input id="time_limit_seconds" type="number" name="time_limit_seconds" min="5" max="300" step="1" value="{{ old('time_limit_seconds', $question?->time_limit_seconds) }}" placeholder="Usar tiempo del concurso">
        </div>
    </div>

    <fieldset class="qa-options-fieldset">
        <legend>Opciones de respuesta</legend>
        <p class="qa-dashboard-meta">Completa A, B, C y D. Marca el círculo de la única respuesta correcta.</p>

        <div class="qa-option-editor">
            @foreach (['A', 'B', 'C', 'D'] as $label)
                <div class="qa-option-editor-row">
                    <label for="option-{{ $label }}" class="qa-option-editor-label">{{ $label }}</label>
                    <input id="option-{{ $label }}" type="text" name="options[{{ $label }}]" maxlength="255" value="{{ old('options.' . $label, $question?->options->firstWhere('label', $label)?->text) }}" required>
                    <label class="qa-correct-choice" for="correct-{{ $label }}">
                        <input id="correct-{{ $label }}" type="radio" name="correct_option" value="{{ $label }}" @checked(old('correct_option', $correctOption) === $label) required>
                        Correcta
                    </label>
                </div>
            @endforeach
        </div>
    </fieldset>

    <button type="submit" class="qa-btn qa-btn-primary" style="width: 100%;">
        {{ $isEditing ? 'Guardar cambios' : 'Crear pregunta' }}
    </button>
</form>
