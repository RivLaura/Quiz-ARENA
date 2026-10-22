<?php

namespace App\Console\Commands;

use App\Services\GameEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('games:advance-expired')]
#[Description('Avanza las partidas cuya pregunta actual agotó el tiempo')]
class AdvanceExpiredQuestions extends Command
{
    public function handle(GameEngine $gameEngine): int
    {
        $advancedCount = $gameEngine->advanceExpiredQuestions();

        if ($advancedCount > 0) {
            $this->components->info("Se avanzaron {$advancedCount} partidas.");
        }

        return self::SUCCESS;
    }
}
