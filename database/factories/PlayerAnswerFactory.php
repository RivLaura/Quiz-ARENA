<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerAnswer>
 */
class PlayerAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'question_id' => Question::factory(),
            'question_option_id' => function (array $attributes): int {
                return QuestionOption::factory()
                    ->create(['question_id' => $attributes['question_id']])
                    ->getKey();
            },
            'is_correct' => false,
            'response_time_ms' => fake()->numberBetween(1, 20000),
            'points_earned' => 0,
            'answered_at' => now(),
        ];
    }
}
