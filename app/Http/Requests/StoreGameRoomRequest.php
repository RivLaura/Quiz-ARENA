<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGameRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'quiz_id' => [
                'required',
                'integer',
                Rule::exists('quizzes', 'id')->where(fn ($query) => $query
                    ->where('admin_id', $this->user()->id)
                    ->where('status', Quiz::STATUS_ACTIVE)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'quiz_id.required' => 'Selecciona un concurso activo.',
            'quiz_id.integer' => 'El concurso seleccionado no es válido.',
            'quiz_id.exists' => 'Selecciona un concurso activo de tu cuenta.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('quiz_id')) {
                    return;
                }

                $quiz = Quiz::query()->find($this->integer('quiz_id'));

                if ($quiz !== null && ! $quiz->hasValidQuestions()) {
                    $validator->errors()->add(
                        'quiz_id',
                        'El concurso debe tener preguntas completas antes de crear una sala.',
                    );
                }
            },
        ];
    }
}
