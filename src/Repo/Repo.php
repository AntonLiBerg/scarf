<?php

namespace Scarf\Repo;

use PDO;
use PDOException;
use Scarf\Shared\IRepo;
use Scarf\Shared\GameState;
use Scarf\Shared\UAscii;

final class Repo implements IRepo
{
    private const TABLE = 'Game';

    private ?PDO $_db = null;

    public function InitDB(string $dbPath): bool
    {
        try {
            $this->_db = new PDO('sqlite:' . $dbPath);
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->_db->exec('CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (id INTEGER, state TEXT, map TEXT, actions TEXT)');

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

        $storedMap = str_replace(UAscii::SPACE, UAscii::PLUS, implode(UAscii::NEWLINE, $map));
        $id = (int) $this->_db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM ' . self::TABLE)->fetchColumn();
        $state = GameState::WaitingForInput->value;
        $actions = [];

        $statement = $this->_db->prepare('INSERT INTO ' . self::TABLE . ' (id, state, map, actions) VALUES (:id, :state, :map, :actions)');

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

        $statement = $this->_db->prepare('UPDATE ' . self::TABLE . ' SET actions = :actions WHERE id = :id');
        $statement->execute([
            ':id' => $id,
            ':actions' => json_encode($actions),
        ]);

        return [
            'id' => $id,
            'state' => $state,
            'map' => explode(UAscii::NEWLINE, str_replace(UAscii::PLUS, UAscii::SPACE, $map)),
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

        $statement = $this->_db->prepare('SELECT id, state, map FROM ' . self::TABLE . ' WHERE id = :id');
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
