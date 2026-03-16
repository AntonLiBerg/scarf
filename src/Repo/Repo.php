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

            return true;
        } catch (PDOException) {
            $this->_db = null;
            return false;
        }
    }

    public function getDB(): ?PDO
    {
        return $this->_db;
    }
}
