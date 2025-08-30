<?php
namespace App\Core;

final class Router {
  private array $routes = [];

  public function get(string $path, callable|array $handler): void { $this->map('GET', $path, $handler); }
  public function post(string $path, callable|array $handler): void { $this->map('POST', $path, $handler); }
  public function put(string $path, callable|array $handler): void { $this->map('PUT', $path, $handler); }
  public function delete(string $path, callable|array $handler): void { $this->map('DELETE', $path, $handler); }

  private function map(string $method, string $path, callable|array $handler): void {
    $pattern = preg_replace('#\{([\w]+)\}#', '(?P<\1>[\w-]+)', $path);
    $pattern = "#^" . rtrim($pattern, '/') . "/?$#";
    $this->routes[] = compact('method','pattern','handler');
  }

  public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH) ?? '/';
    foreach ($this->routes as $route) {
      if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $handler = $route['handler'];
        if (is_array($handler)) {
          [$class,$action] = $handler;
          (new $class)->{$action}($params);
        } else {
          $handler($params);
        }
        return;
      }
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
  }
}
