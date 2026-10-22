<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['game_room_id', 'nickname', 'avatar', 'score'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'game_room_id' => 'integer',
            'score' => 'integer',
        ];
    }

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }
}
