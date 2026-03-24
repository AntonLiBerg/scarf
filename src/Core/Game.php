<?php

namespace Scarf\Core;

use Scarf\Shared\IGame;
use Scarf\Shared\IRepo;

final class Game implements IGame
{
   private IRepo $repo;

   public function __construct(IRepo $repo)
   {
      $this->repo = $repo;
   }
   public function InitGame():array
   {
      $this->repo->InitDB(__DIR__ . '/../../var/app.db');
      $map = [];

      return $map;
   }
   public function updateGame(array $actions): array
   {

   }
}
