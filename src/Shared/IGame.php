<?php

namespace Scarf\Shared;

use PDO;

interface IGame
{
    public function InitGame(): array;
    public function TrySolution(int $id, array $actions): bool;
}
