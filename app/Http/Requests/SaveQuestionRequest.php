<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('quiz')) ?? false;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:1', 'max:10000'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:5', 'max:300'],
            'options' => ['required', 'array:A,B,C,D'],
            'options.A' => ['required', 'string', 'max:255'],
            'options.B' => ['required', 'string', 'max:255'],
            'options.C' => ['required', 'string', 'max:255'],
            'options.D' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'El enunciado de la pregunta es obligatorio.',
            'prompt.max' => 'El enunciado no puede superar los 2000 caracteres.',
            'points.required' => 'Debes indicar el puntaje de la pregunta.',
            'points.integer' => 'El puntaje debe ser un número entero.',
            'points.min' => 'El puntaje mínimo es 1.',
            'points.max' => 'El puntaje máximo es 10000.',
            'time_limit_seconds.integer' => 'El tiempo límite debe ser un número entero.',
            'time_limit_seconds.min' => 'El tiempo límite mínimo es de 5 segundos.',
            'time_limit_seconds.max' => 'El tiempo límite máximo es de 300 segundos.',
            'options.required' => 'Debes completar las cuatro opciones.',
            'options.array' => 'La pregunta debe tener exactamente las opciones A, B, C y D.',
            'options.A.required' => 'La opción A es obligatoria.',
            'options.B.required' => 'La opción B es obligatoria.',
            'options.C.required' => 'La opción C es obligatoria.',
            'options.D.required' => 'La opción D es obligatoria.',
            'correct_option.required' => 'Debes seleccionar la respuesta correcta.',
            'correct_option.in' => 'La respuesta correcta debe ser A, B, C o D.',
        ];
    }
}
