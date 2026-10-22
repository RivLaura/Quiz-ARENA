<?php

namespace App\Models;

use Database\Factories\PlayerAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['player_id', 'game_room_question_id', 'question_id', 'question_option_id', 'selected_option_label', 'selected_option_text', 'is_correct', 'response_time_ms', 'points_earned', 'answered_at'])]
class PlayerAnswer extends Model
{
    /** @use HasFactory<PlayerAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'player_id' => 'integer',
            'game_room_question_id' => 'integer',
            'question_id' => 'integer',
            'question_option_id' => 'integer',
            'is_correct' => 'boolean',
            'response_time_ms' => 'integer',
            'points_earned' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function gameRoomQuestion(): BelongsTo
    {
        return $this->belongsTo(GameRoomQuestion::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
