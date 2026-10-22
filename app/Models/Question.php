<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'prompt', 'position', 'points', 'time_limit_seconds'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quiz_id' => 'integer',
            'position' => 'integer',
            'points' => 'integer',
            'time_limit_seconds' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function playerAnswers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }
}
