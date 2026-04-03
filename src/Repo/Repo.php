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

    public function AddGame(array $map): array
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

    public function UpdateGame(int $id, array $actions): array
    {
        if ($this->_db === null) {
            return [];
        }

        $game = $this->GetGame($id);
        if ($game === []) {
            return [];
        }

        $id = (int) $game['id'];
        $state = (string) $game['state'];
        $map = (string) $game['map'];

        $statement = $this->_db->prepare('UPDATE Game SET actions = :actions WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':actions' => json_encode($actions),
        ]);

        return [
            'id' => $id,
            'state' => $state,
            'map' => explode("\n", str_replace('+', ' ', $map)),
            'actions' => $actions,
        ];
    }

    public function GetGame(int $id): array
    {
        return $this->PGetGame($id);
    }

    private function PGetGame(int $id): array
    {
        if ($this->_db === null) {
            return [];
        }

        $statement = $this->_db->prepare('SELECT id, state, map FROM Game WHERE id = :id');
        $statement->execute([
            ':id' => $id,
        ]);
        $game = $statement->fetch(PDO::FETCH_ASSOC);
        if ($game === false) {
            return [];
        }

        return $game;
    }
}
