<?php

namespace App\Http\Requests;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;

class StorePlayerAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $player = $this->route('player');

        return $player instanceof Player
            && $this->session()->has('joined_players.'.$player->id);
    }

    public function rules(): array
    {
        return [
            'option_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'option_id.required' => 'Selecciona una opción para responder.',
            'option_id.integer' => 'La opción seleccionada no es válida.',
        ];
    }
}
