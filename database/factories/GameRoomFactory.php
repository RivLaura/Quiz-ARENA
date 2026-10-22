<?php

namespace Database\Factories;

use App\Models\GameRoom;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameRoom>
 */
class GameRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'host_id' => User::factory()->state(['role' => 'admin']),
            'code' => Str::upper(Str::random(6)),
            'status' => 'waiting',
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
