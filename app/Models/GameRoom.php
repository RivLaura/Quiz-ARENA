<?php

namespace App\Models;

use Database\Factories\GameRoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['quiz_id', 'host_id', 'code', 'status', 'started_at', 'finished_at', 'current_question_position', 'current_question_started_at', 'channel_token'])]
#[Hidden(['channel_token'])]
class GameRoom extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_PLAYING = 'playing';

    public const STATUS_FINISHED = 'finished';

    /** @use HasFactory<GameRoomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'host_id' => 'integer',
            'current_question_position' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'current_question_started_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function questionSnapshots(): HasMany
    {
        return $this->hasMany(GameRoomQuestion::class)->orderBy('position');
    }

    public function ensureChannelToken(): string
    {
        if ($this->channel_token === null) {
            $this->channel_token = Str::random(64);
            $this->save();
        }

        return $this->channel_token;
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
