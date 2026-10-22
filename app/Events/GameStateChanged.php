<?php

namespace App\Events;

use App\Models\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(public GameRoom $gameRoom, public array $state) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('game-play.'.$this->gameRoom->channel_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'GameStateChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->state;
    }
}
