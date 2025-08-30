<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderModel;

final class OrderController extends Controller {
  private OrderModel $model;
  public function __construct(){ $this->model = new OrderModel(); }

  public function store(): void {
    $b = $this->body();
    $items = $b['items'] ?? [];
    if (!is_array($items) || count($items) === 0) {
      $this->json(['error'=>'Items requeridos'], 422); return;
    }
    // Validaciones básicas
    foreach ($items as $it) {
      if (!isset($it['producto_id'],$it['cantidad'],$it['precio_unitario'])
          || !is_int($it['cantidad'])
          || !is_numeric($it['precio_unitario'])
          || $it['cantidad'] <= 0
          || $it['precio_unitario'] < 0) {
        $this->json(['error'=>'Item inválido'], 422); return;
      }
    }
    try {
      $pedido = $this->model->create($items);
      $this->json($pedido, 201);
    } catch (\Throwable $e) {
      $this->json(['error'=>$e->getMessage()], 400);
    }
  }

  public function index(): void {
    $desde = $this->query('desde');
    $hasta = $this->query('hasta');
    $min   = $this->query('minTotal');
    $data = $this->model->list($desde ?: null, $hasta ?: null, $min !== null ? (float)$min : null);
    $this->json($data);
  }

  public function show(array $params): void {
    $id = (int)$params['id'];
    $data = $this->model->getById($id);
    if (!$data) { $this->json(['error'=>'Pedido no encontrado'], 404); return; }
    $this->json($data);
  }
}
