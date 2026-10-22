<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('quiz')) ?? false;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:up,down'],
        ];
    }

    public function messages(): array
    {
        return [
            'direction.required' => 'Debes indicar si la pregunta sube o baja.',
            'direction.in' => 'El movimiento solo puede ser hacia arriba o hacia abajo.',
        ];
    }
}
