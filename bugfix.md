# bugfix.md

## 1) SQL Injection
**Vulnerable (ejemplo anti-patrón):**
```php
// NO USAR
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM productos WHERE nombre LIKE '%$search%'";
$pdo->query($sql);
```

**Corregido (este proyecto):**
```php
$stmt = $pdo->prepare("CALL sp_productos_listar(?,?,?,?,?)");
$stmt->execute([$search, $sort, $dir, $page, $pageSize]);
```
- Parámetros enlazados, sin concatenación de SQL.
- Lógica de filtrado dentro del SP con `PREPARE` y `?` para `LIKE`.

## 2) N+1 en listado de pedidos
**Síntoma**: Al listar pedidos, por cada pedido se dispara otra consulta para items → N+1.  
**Detección**: *EXPLAIN/slow log* muestra múltiples queries repetitivas.  
**Solución aplicada**:
- Endpoint `GET /api/pedidos/{id}` retorna cabecera + items en **dos result sets** de un único `CALL sp_pedido_por_id`.  
- Para listados, si se requiere items embebidos, alternativamente se puede:
  - Usar un SP que haga `JOIN pedido_items` y agregue con `JSON_ARRAYAGG` por pedido.
  - Este proyecto evita N+1 en el detalle usando multi-result-set y deja el listado de cabeceras ligero.
