<?php

namespace Scarf\Shared;

use PDO;

interface IRepo
{
    public function InitDB(string $dbPath): bool;

    public function getDB(): ?PDO;
}
