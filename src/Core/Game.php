<?php

namespace Scarf\Core;

use Scarf\Core\GameAction;
use Scarf\Shared\IGame;
use Scarf\Shared\IRepo;

final class Game implements IGame
{
   private IRepo $_repo;

   public function __construct(IRepo $repo)
   {
      $this->_repo = $repo;
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

      return $this->_repo->AddGame($map);
   }
   public function UpdateGame(array $actions): array
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

      return $this->_repo->UpdateGame($validActions);
   }
}
