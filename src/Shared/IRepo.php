<?php

namespace Scarf\Shared;

use PDO;

interface IRepo
{
   public function AddGame(array $map):array;
   public function InitDB(string $dbPath): bool;

   public function GetDB(): ?PDO;

   public function UpdateGame(array $actions): array;
}
