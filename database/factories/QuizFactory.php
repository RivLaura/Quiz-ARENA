<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->word(),
            'time_per_question' => 20,
            'status' => 'draft',
            'admin_id' => User::factory()->state(['role' => 'admin']),
        ];
    }
}
