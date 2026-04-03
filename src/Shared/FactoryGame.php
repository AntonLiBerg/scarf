<?php

namespace Scarf\Shared;

use Scarf\Core\Game;
use Scarf\Repo\Repo;

final class FactoryGame
{
    public static function MakeGame(): IGame
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException('pdo_sqlite extension is not installed');
        }

        $repo = new Repo();
        if (!$repo->InitDB(__DIR__ . '/../../var/app.db')) {
            throw new \RuntimeException('Could not initialize game database');
        }

        return new Game($repo);
    }
}
