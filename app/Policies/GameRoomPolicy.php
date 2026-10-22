<?php

namespace App\Policies;

use App\Models\GameRoom;
use App\Models\User;

class GameRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, GameRoom $gameRoom): bool
    {
        return $user->isAdmin() && $gameRoom->host_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, GameRoom $gameRoom): bool
    {
        return $user->isAdmin() && $gameRoom->host_id === $user->id;
    }
}
