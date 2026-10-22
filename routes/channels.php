<?php

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('game-room.{roomId}', function (User $user, int $roomId): bool {
    return GameRoom::query()
        ->whereKey($roomId)
        ->where('host_id', $user->id)
        ->exists();
});
