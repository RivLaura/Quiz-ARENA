<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Player $player) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game-room.'.$this->player->game_room_id),
            new Channel('game-play.'.$this->player->gameRoom->channel_token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PlayerJoined';
    }

    /**
     * @return array{player: array{id: int, nickname: string, avatar: ?string}, players_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'player' => [
                'id' => $this->player->id,
                'nickname' => $this->player->nickname,
                'avatar' => $this->player->avatar,
            ],
            'players_count' => $this->player->gameRoom->players()->count(),
        ];
    }
}
