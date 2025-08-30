# TECH_NOTES

## Conexión y llamadas a Stored Procedures
Se implementó una capa `Database` (PDO) con:
- `PDO::ATTR_ERRMODE = EXCEPTION`
- `ATTR_EMULATE_PREPARES = false` para verdadero *prepared statements*.
- Charset `utf8mb4`.

**Motivo**: PDO es estándar, portable y seguro. Desacopla el acceso y habilita pruebas.  
**Patrón**: los *Models* invocan únicamente `CALL sp_*` y consumen `ResultSets`. En `sp_pedido_por_id` se usa `nextRowset()` para leer múltiples conjuntos (cabecera e items), evitando el N+1.

## Transacciones
El pedido se maneja **dentro del SP** (`sp_pedido_crear`) con `START TRANSACTION/COMMIT/ROLLBACK`, `FOR UPDATE` para bloquear stock y garantizar atomicidad (valida stock, calcula subtotal/desc/iva/total, inserta y descuenta stock).

## Reglas de cálculos
- Descuento 10% si `subtotal > 100` (antes de IVA).
- IVA 12% sobre `base = subtotal - descuento`.
- Redondeos en el SP para exactitud financiera.

## Razonamiento de diseño
- **SP para todo**: requisito del enunciado y beneficios: seguridad, coherencia, performance.
- **JSON en SP** para items: simplifica interfaz y mantiene transaccionalidad dentro de MySQL 8 (iteración con `JSON_EXTRACT`).
- **SPA JS** minimalista con `fetch` y `hash routing` para cumplir consumo de endpoints.
