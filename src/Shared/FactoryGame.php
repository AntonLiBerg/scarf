<?php

namespace Scarf\Shared;

use Scarf\Core\Game;
use Scarf\Repo\Repo;

final class FactoryGame
{
   public static function MakeGame(): IGame
   {
      $repo = new Repo();
      $repo->InitDB(__DIR__ . '/../../var/app.db');

      return new Game($repo);
   }
}
