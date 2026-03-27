<?php

namespace Scarf\Shared;

use PDO;

interface IRepo
{
   public function addGame(array $map):array;
   public function InitDB(string $dbPath): bool;

   public function getDB(): ?PDO;

   public function updateGame(array $actions): array;
}
