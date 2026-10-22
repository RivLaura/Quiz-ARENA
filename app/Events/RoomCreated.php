<?php

namespace App\Events;

use App\Models\GameRoom;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameRoom $gameRoom) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game-room.'.$this->gameRoom->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RoomCreated';
    }

    /**
     * @return array{id: int, code: string, status: string, quiz_title: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->gameRoom->id,
            'code' => $this->gameRoom->code,
            'status' => $this->gameRoom->status,
            'quiz_title' => $this->gameRoom->quiz->title,
        ];
    }
}
