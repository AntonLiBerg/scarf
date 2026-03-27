<?php

namespace Scarf\Core;

use Scarf\Core\GameAction;
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
      $map = [
         '#######¤#####',
         '###     #####',
         '### #########',
         '##  #########',
         '##          #',
         '##########  #',
         '#           #',
         '####### #####',
         '#           #',
         '# R #########',
      ];


      return this->repo.addGame($map);
   }
   public function updateGame(array $actions): array
   {
      $validActions = [];

      foreach ($actions as $action) {
         if (!is_string($action)) {
            continue;
         }

         $gameAction = GameAction::tryFrom($action);
         if ($gameAction !== null) {
            $validActions[] = $gameAction->value;
         }
      }

      return $this->repo->updateGame($validActions);
   }
}
