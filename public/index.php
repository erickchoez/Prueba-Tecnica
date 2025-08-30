<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\ProductController;
use App\Controllers\OrderController;

header('Content-Type: application/json; charset=utf-8');

$router = new Router();

// Productos
$router->get('/api/productos', [ProductController::class, 'index']);
$router->post('/api/productos', [ProductController::class, 'store']);
$router->put('/api/productos/{id}', [ProductController::class, 'update']);
$router->delete('/api/productos/{id}', [ProductController::class, 'destroy']);

// Pedidos
$router->post('/api/pedidos', [OrderController::class, 'store']);
$router->get('/api/pedidos', [OrderController::class, 'index']);
$router->get('/api/pedidos/{id}', [OrderController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
