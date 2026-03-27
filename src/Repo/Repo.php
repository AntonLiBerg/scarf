<?php

namespace Scarf\Repo;

use PDO;
use PDOException;
use Scarf\Shared\IRepo;

final class Repo implements IRepo
{
    private ?PDO $_db = null;

    public function InitDB(string $dbPath): bool
    {
        try {
            $this->_db = new PDO('sqlite:' . $dbPath);
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->_db->exec('CREATE TABLE IF NOT EXISTS Game (id INTEGER, state TEXT, map TEXT, actions TEXT)');

            return true;
        } catch (PDOException) {
           $this->_db = null;
           return false;
        }
    }

    public function GetDB(): ?PDO
    {
       return $this->_db;
    }

    public function AddGame(array $map):array
    {
       if ($this->_db === null) {
          return [];
       }

       $storedMap = str_replace(' ', '+', implode("\n", $map));
       $id = (int) $this->_db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM Game')->fetchColumn();
       $state = 'WaitForStart';
       $actions = [];

       $statement = $this->_db->prepare('INSERT INTO Game (id, state, map, actions) VALUES (:id, :state, :map, :actions)');

       $statement->execute([
          ':id' => $id,
          ':state' => $state,
          ':map' => $storedMap,
          ':actions' => json_encode($actions),
       ]);

       return [
          'id' => $id,
          'state' => $state,
          'map' => $map,
          'actions' => $actions,
       ];
    }
    public function UpdateGame(array $actions): array
    {
       if ($this->_db === null) {
          return [];
       }

       $statement = $this->_db->query('SELECT id, state, map FROM Game ORDER BY id DESC LIMIT 1');
       if ($statement === false) {
          return [];
       }

       $game =  $statement->fetch(PDO::FETCH_ASSOC);
       if ($game === false) {
          return [];
       }

       $id = (int) $game['id'] + 1;
       $state = (string) $game['state'];
       $map = (string) $game['map'];

       $statement = $this->_db->prepare('INSERT INTO Game (id, state, map, actions) VALUES (:id, :state, :map, :actions)');
       $statement->execute([
          ':id' => $id,
          ':state' => $state,
          ':map' => $map,
          ':actions' => json_encode($actions),
       ]);

       return [
          'id' => $id,
          'state' => $state,
          'map' => explode("\n", str_replace('+', ' ', $map)),
          'actions' => $actions,
       ];
    }
}
