<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class OrderModel {
  public function create(array $items): array {
    $json = json_encode($items, JSON_UNESCAPED_UNICODE);
    $stmt = Database::pdo()->prepare("CALL sp_pedido_crear(?)");
    $stmt->execute([$json]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return $row ?: [];
  }

  public function list(?string $desde, ?string $hasta, ?float $minTotal): array {
    $stmt = Database::pdo()->prepare("CALL sp_pedidos_listar(?,?,?)");
    $stmt->execute([$desde, $hasta, $minTotal]);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
  }

  public function getById(int $id): array {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare("CALL sp_pedido_por_id(?)");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();
    $stmt->nextRowset();
    $items = $stmt->fetchAll();
    $stmt->closeCursor();
    if (!$pedido) return [];
    $pedido['items'] = $items;
    return $pedido;
  }
}
