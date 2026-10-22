<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:255'],
            'time_per_question' => ['required', 'integer', 'min:5', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 5000 caracteres.',
            'category.required' => 'La categoría es obligatoria.',
            'category.max' => 'La categoría no puede superar los 255 caracteres.',
            'time_per_question.required' => 'Debes indicar el tiempo por pregunta.',
            'time_per_question.integer' => 'El tiempo por pregunta debe ser un número entero.',
            'time_per_question.min' => 'El tiempo mínimo por pregunta es de 5 segundos.',
            'time_per_question.max' => 'El tiempo máximo por pregunta es de 300 segundos.',
        ];
    }
}
