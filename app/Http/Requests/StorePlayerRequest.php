<?php

namespace App\Http\Requests;

use App\Models\GameRoom;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $nickname = $this->input('nickname');

        $this->merge([
            'code' => is_string($code) ? Str::upper(trim($code)) : $code,
            'nickname' => is_string($nickname) ? trim($nickname) : $nickname,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:6',
                'alpha_num',
                Rule::exists('game_rooms', 'code')
                    ->where('status', GameRoom::STATUS_WAITING),
            ],
            'nickname' => ['required', 'string', 'min:2', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Ingresa el código de la sala.',
            'code.size' => 'El código debe tener exactamente 6 caracteres.',
            'code.alpha_num' => 'El código solo puede contener letras y números.',
            'code.exists' => 'No encontramos una sala abierta con ese código.',
            'nickname.required' => 'Ingresa el nombre que verán los demás jugadores.',
            'nickname.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nickname.max' => 'El nombre no puede superar los 40 caracteres.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['code', 'nickname'])) {
                    return;
                }

                $room = GameRoom::query()
                    ->where('code', $this->string('code')->toString())
                    ->where('status', GameRoom::STATUS_WAITING)
                    ->first();

                if ($room === null) {
                    return;
                }

                $nicknameExists = Player::query()
                    ->whereBelongsTo($room, 'gameRoom')
                    ->whereRaw('LOWER(nickname) = ?', [mb_strtolower($this->string('nickname')->toString())])
                    ->exists();

                if ($nicknameExists) {
                    $validator->errors()->add('nickname', 'Ese nombre ya está siendo usado en esta sala.');
                }
            },
        ];
    }
}
