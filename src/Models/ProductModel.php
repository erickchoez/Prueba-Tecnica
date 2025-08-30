<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class ProductModel {
  public function list(?string $search, ?string $sort, ?string $dir, int $page=1, int $pageSize=10): array {
    $stmt = Database::pdo()->prepare("CALL sp_productos_listar(?,?,?,?,?)");
    $stmt->execute([$search, $sort, $dir, $page, $pageSize]);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
  }

  public function create(string $sku, string $nombre, float $precio, int $stock): int {
    $stmt = Database::pdo()->prepare("CALL sp_producto_crear(?,?,?,?)");
    $stmt->execute([$sku, $nombre, $precio, $stock]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return (int)$row['id'];
  }

  public function update(int $id, string $sku, string $nombre, float $precio, int $stock): int {
    $stmt = Database::pdo()->prepare("CALL sp_producto_actualizar(?,?,?,?,?)");
    $stmt->execute([$id, $sku, $nombre, $precio, $stock]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return (int)$row['affected'];
  }

  public function delete(int $id): int {
    $stmt = Database::pdo()->prepare("CALL sp_producto_eliminar(?)");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $stmt->closeCursor();
    return (int)$row['affected'];
  }
}
