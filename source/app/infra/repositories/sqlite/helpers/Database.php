<?php

namespace App\infra\repositories\sqlite\helpers;

use PDO;
use PDOException;

class Database
{
  public static function connect()
  {
    try {
      return new PDO(
        'sqlite:../../data/db.sqlite',
        '',
        '',
        [
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]
      );
    } catch (PDOException $e) {
      echo 'Erro na conexão: ' . $e->getMessage();
    }
  }
}
