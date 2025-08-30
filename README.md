# Mini Pedidos 

## Requisitos
- PHP 8.1+, Composer
- MySQL 8.x
- Servidor web (Apache con mod_rewrite o PHP built-in)

## Instalación
1. Clonar repo y entrar al directorio.
2. `composer install`
3. Crear DB y cargar scripts:
   ```bash
   mysql -u root -p < sql/schema.sql
   mysql -u root -p < sql/seed.sql
   mysql -u root -p < sql/procedures.sql
   ```
4. Variables de entorno para conexión:
   ```
   export DB_HOST=127.0.0.1
   export DB_NAME=mini_pedidos
   export DB_USER=root
   export DB_PASS=tu_password
   ```
5. Servir backend:
   - Apache -> apuntar DocumentRoot a `public/`
   - o PHP built-in: `php -S 127.0.0.1:8000 -t public`
6. Frontend: abrir `frontend/index.html` (si está en el mismo host, asegurarse de que el backend esté en la misma origin).

## Endpoints
- `GET /api/productos?search=&sort=&dir=&page=&pageSize=`
- `POST /api/productos` `{sku,nombre,precio,stock}`
- `PUT /api/productos/{id}`
- `DELETE /api/productos/{id}`

- `POST /api/pedidos` `{ items:[ {producto_id,cantidad,precio_unitario}, ... ] }`
- `GET /api/pedidos?desde=&hasta=&minTotal=`
- `GET /api/pedidos/{id}`

## Pruebas
```
./vendor/bin/phpunit
```

## Seguridad
- **Todas** las operaciones usan *stored procedures*.
- Uso de PDO con consultas preparadas (sin concatenación de SQL).
- Validaciones de entrada y sanitización de salida (JSON).
- Reglas de negocio y transacciones encapsuladas en `sp_pedido_crear`.
