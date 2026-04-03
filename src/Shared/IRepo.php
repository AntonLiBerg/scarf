<?php

namespace Scarf\Shared;

use PDO;

interface IRepo
{
    public function AddGame(array $map): array;
    public function InitDB(string $dbPath): bool;
    public function GetGame(int $id): array;
    public function UpdateGame(int $id, array $actions): array;
}
