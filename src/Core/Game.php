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

      $db = $this->repo->getDB();
      $db->exec('CREATE TABLE IF NOT EXISTS Game (id INTEGER, state TEXT, map TEXT, actions TEXT)');

      $storedMap = str_replace(' ', '+', implode("\n", $map));
      $id = (int) $db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM Game')->fetchColumn();

      $statement = $db->prepare('INSERT INTO Game (id, state, map, actions) VALUES (:id, :state, :map, :actions)');
      $statement->execute([
         ':id' => $id,
         ':state' => 'WaitForStart',
         ':map' => $storedMap,
         ':actions' => json_encode([]),
      ]);

      return $map;
   }
   public function updateGame(array $actions): array
   {

   }
}
