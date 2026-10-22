<?php

namespace Database\Factories;

use App\Models\GameRoom;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_room_id' => GameRoom::factory(),
            'nickname' => fake()->userName(),
            'avatar' => null,
            'score' => 0,
        ];
    }
}
