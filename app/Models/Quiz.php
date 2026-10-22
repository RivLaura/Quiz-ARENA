<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'category', 'time_per_question', 'status', 'admin_id'])]
class Quiz extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    use HasFactory;

    protected function casts(): array
    {
        return [
            'time_per_question' => 'integer',
            'admin_id' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function gameRooms(): HasMany
    {
        return $this->hasMany(GameRoom::class);
    }

    public function hasValidQuestions(): bool
    {
        if (! $this->questions()->exists()) {
            return false;
        }

        return ! $this->questions()
            ->where(function (Builder $query): void {
                $query->has('options', '!=', 4)
                    ->orWhereHas(
                        'options',
                        fn (Builder $options): Builder => $options->whereIn('label', ['A', 'B', 'C', 'D']),
                        '!=',
                        4,
                    )
                    ->orWhereHas(
                        'options',
                        fn (Builder $options): Builder => $options->where('is_correct', true),
                        '!=',
                        1,
                    );
            })
            ->exists();
    }
}
