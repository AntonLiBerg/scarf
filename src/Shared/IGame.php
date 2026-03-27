<?php

namespace Scarf\Shared;

use PDO;

interface IGame
{
    public function InitGame(): array;
    public function UpdateGame(array $actions): array;
}
