<?php
namespace App\Core;

use PDO;
use PDOException;

final class Database {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo === null) {
      $host = getenv('DB_HOST') ?: '127.0.0.1';
      $db   = getenv('DB_NAME') ?: 'mini_pedidos';
      $user = getenv('DB_USER') ?: 'root';
      $pass = getenv('DB_PASS') ?: '';
      $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
      $opt  = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
      ];
      try {
        self::$pdo = new PDO($dsn, $user, $pass, $opt);
      } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error'=>'DB connection failed']);
        exit;
      }
    }
    return self::$pdo;
  }
}
