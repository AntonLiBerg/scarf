<?php

namespace Scarf\Repo;

final class Repo
{
   public function InitDB(string dbPath):bool
   {
      $db = new PDO("sqlite:" . $dbPath
   }
}

