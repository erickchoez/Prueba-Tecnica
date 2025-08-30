<?php
namespace App\Core;

class Controller {
  protected function json(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
  }

  protected function body(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  protected function query(string $key, ?string $default = null): ?string {
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
  }
}
