<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['game_room_id', 'question_id', 'position', 'prompt', 'points', 'time_limit_seconds', 'options'])]
class GameRoomQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'game_room_id' => 'integer',
            'question_id' => 'integer',
            'position' => 'integer',
            'points' => 'integer',
            'time_limit_seconds' => 'integer',
            'options' => 'array',
        ];
    }

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }
}
