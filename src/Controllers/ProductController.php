<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProductModel;
use App\Helpers\Http;

final class ProductController extends Controller {
  private ProductModel $model;
  public function __construct(){ $this->model = new ProductModel(); }

  public function index(): void {
    $search = $this->query('search', '');
    $sort   = $this->query('sort', 'created_at');
    $dir    = $this->query('dir', 'desc');
    $page   = (int)($this->query('page','1') ?? 1);
    $size   = (int)($this->query('pageSize','10') ?? 10);

    $data = $this->model->list($search, $sort, $dir, max(1,$page), max(1,$size));
    $this->json($data);
  }

  public function store(): void {
    $b = $this->body();
    $sku = $b['sku'] ?? '';
    $nombre = $b['nombre'] ?? '';
    $precio = $b['precio'] ?? null;
    $stock  = $b['stock'] ?? null;

    if ($sku === '' || $nombre === '' || !is_numeric($precio) || !is_int($stock)) {
      $this->json(['error'=>'Datos inválidos'], 422); return;
    }
    try {
      $id = $this->model->create($sku, $nombre, (float)$precio, (int)$stock);
      $this->json(['id'=>$id], 201);
    } catch (\Throwable $e) {
      $this->json(['error'=>'No se pudo crear (SKU duplicado?)'], 400);
    }
  }

  public function update(array $params): void {
    $id = (int)$params['id'];
    $b = $this->body();
    if (!isset($b['sku'],$b['nombre'],$b['precio'],$b['stock'])) {
      $this->json(['error'=>'Datos incompletos'], 422); return;
    }
    try {
      $affected = $this->model->update($id, (string)$b['sku'], (string)$b['nombre'], (float)$b['precio'], (int)$b['stock']);
      $this->json(['updated'=>$affected>0]);
    } catch (\Throwable $e) {
      $this->json(['error'=>'No se pudo actualizar'], 400);
    }
  }

  public function destroy(array $params): void {
    $id = (int)$params['id'];
    try {
      $affected = $this->model->delete($id);
      $this->json(['deleted'=>$affected>0]);
    } catch (\Throwable $e) {
      $this->json(['error'=>'No se pudo eliminar'], 400);
    }
  }
}
