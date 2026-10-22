<?php

namespace App\Providers;

use App\Models\GameRoom;
use App\Models\Quiz;
use App\Policies\GameRoomPolicy;
use App\Policies\QuizPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(GameRoom::class, GameRoomPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
    }
}
