USE mini_pedidos;
DELIMITER $$

-- PRODUCTOS 

DROP PROCEDURE IF EXISTS sp_productos_listar $$
CREATE PROCEDURE sp_productos_listar(
  IN p_search VARCHAR(200),
  IN p_sort VARCHAR(50),
  IN p_dir VARCHAR(4),
  IN p_page INT,
  IN p_page_size INT
)
BEGIN
  SET @sql = 'SELECT id, sku, nombre, precio, stock, created_at, updated_at FROM productos WHERE 1=1';
  IF p_search IS NOT NULL AND p_search <> '' THEN
    SET @sql = CONCAT(@sql, ' AND (sku LIKE ? OR nombre LIKE ?)');
  END IF;

  IF p_sort IS NULL OR p_sort = '' THEN SET p_sort = 'created_at'; END IF;
  IF p_dir IS NULL OR p_dir = '' THEN SET p_dir = 'desc'; END IF;
  SET @sql = CONCAT(@sql, ' ORDER BY ', p_sort, ' ', p_dir);

  IF p_page IS NULL OR p_page < 1 THEN SET p_page = 1; END IF;
  IF p_page_size IS NULL OR p_page_size < 1 THEN SET p_page_size = 10; END IF;
  SET @offset = (p_page - 1) * p_page_size;
  SET @sql = CONCAT(@sql, ' LIMIT ? OFFSET ?');

  PREPARE stmt FROM @sql;
  IF p_search IS NOT NULL AND p_search <> '' THEN
    SET @like := CONCAT('%', p_search, '%');
    EXECUTE stmt USING @like, @like, p_page_size, @offset;
  ELSE
    EXECUTE stmt USING p_page_size, @offset;
  END IF;
  DEALLOCATE PREPARE stmt;
END $$

DROP PROCEDURE IF EXISTS sp_producto_crear $$
CREATE PROCEDURE sp_producto_crear(
  IN p_sku VARCHAR(50),
  IN p_nombre VARCHAR(200),
  IN p_precio DECIMAL(10,2),
  IN p_stock INT
)
BEGIN
  INSERT INTO productos (sku, nombre, precio, stock)
  VALUES (p_sku, p_nombre, p_precio, p_stock);
  SELECT LAST_INSERT_ID() AS id;
END $$

DROP PROCEDURE IF EXISTS sp_producto_actualizar $$
CREATE PROCEDURE sp_producto_actualizar(
  IN p_id INT,
  IN p_sku VARCHAR(50),
  IN p_nombre VARCHAR(200),
  IN p_precio DECIMAL(10,2),
  IN p_stock INT
)
BEGIN
  UPDATE productos
  SET sku = p_sku, nombre = p_nombre, precio = p_precio, stock = p_stock
  WHERE id = p_id;
  SELECT ROW_COUNT() AS affected;
END $$

DROP PROCEDURE IF EXISTS sp_producto_eliminar $$
CREATE PROCEDURE sp_producto_eliminar(IN p_id INT)
BEGIN
  DELETE FROM productos WHERE id = p_id;
  SELECT ROW_COUNT() AS affected;
END $$


-- PEDIDOS

DROP PROCEDURE IF EXISTS sp_pedido_crear $$
CREATE PROCEDURE sp_pedido_crear(IN p_items JSON)
proc:BEGIN
  DECLARE i INT DEFAULT 0;
  DECLARE n INT;
  DECLARE v_producto_id INT;
  DECLARE v_cantidad INT;
  DECLARE v_precio DECIMAL(10,2);
  DECLARE v_stock INT;
  DECLARE v_subtotal DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_descuento DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_base DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_iva DECIMAL(12,2) DEFAULT 0.00;
  DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;

  IF JSON_TYPE(p_items) <> 'ARRAY' OR JSON_LENGTH(p_items) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Items inválidos';
  END IF;

  SET n = JSON_LENGTH(p_items);

  START TRANSACTION;

  -- Validación de stock y cálculo de subtotal
  WHILE i < n DO
    SET v_producto_id = JSON_EXTRACT(p_items, CONCAT('$[', i, '].producto_id'));
    SET v_cantidad    = JSON_EXTRACT(p_items, CONCAT('$[', i, '].cantidad'));
    SET v_precio      = JSON_EXTRACT(p_items, CONCAT('$[', i, '].precio_unitario'));

    IF v_producto_id IS NULL OR v_cantidad IS NULL OR v_precio IS NULL OR v_cantidad <= 0 OR v_precio < 0 THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Item inválido';
    END IF;

    SELECT stock INTO v_stock FROM productos WHERE id = v_producto_id FOR UPDATE;
    IF v_stock IS NULL THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Producto no existe';
    END IF;

    IF v_stock < v_cantidad THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock insuficiente';
    END IF;

    SET v_subtotal = v_subtotal + (v_precio * v_cantidad);
    SET i = i + 1;
  END WHILE;

  IF v_subtotal > 100 THEN
    SET v_descuento = ROUND(v_subtotal * 0.10, 2);
  END IF;

  SET v_base = v_subtotal - v_descuento;
  SET v_iva = ROUND(v_base * 0.12, 2);
  SET v_total = v_base + v_iva;

  INSERT INTO pedidos(subtotal, descuento, iva, total)
  VALUES (v_subtotal, v_descuento, v_iva, v_total);
  SET @pedido_id := LAST_INSERT_ID();

  -- Inserción de items y decremento de stock
  SET i = 0;
  WHILE i < n DO
    SET v_producto_id = JSON_EXTRACT(p_items, CONCAT('$[', i, '].producto_id'));
    SET v_cantidad    = JSON_EXTRACT(p_items, CONCAT('$[', i, '].cantidad'));
    SET v_precio      = JSON_EXTRACT(p_items, CONCAT('$[', i, '].precio_unitario'));

    INSERT INTO pedido_items(pedido_id, producto_id, cantidad, precio_unitario)
    VALUES (@pedido_id, v_producto_id, v_cantidad, v_precio);

    UPDATE productos SET stock = stock - v_cantidad WHERE id = v_producto_id;

    SET i = i + 1;
  END WHILE;

  COMMIT;

  SELECT
    @pedido_id AS id, v_subtotal AS subtotal, v_descuento AS descuento,
    v_iva AS iva, v_total AS total;
END $$

DROP PROCEDURE IF EXISTS sp_pedidos_listar $$
CREATE PROCEDURE sp_pedidos_listar(
  IN p_desde DATETIME,
  IN p_hasta DATETIME,
  IN p_min_total DECIMAL(12,2)
)
BEGIN
  SELECT p.id, p.subtotal, p.descuento, p.iva, p.total, p.created_at
  FROM pedidos p
  WHERE (p_desde IS NULL OR p.created_at >= p_desde)
    AND (p_hasta IS NULL OR p.created_at <= p_hasta)
    AND (p_min_total IS NULL OR p.total >= p_min_total)
  ORDER BY p.created_at DESC;
END $$

DROP PROCEDURE IF EXISTS sp_pedido_por_id $$
CREATE PROCEDURE sp_pedido_por_id(IN p_id INT)
BEGIN
  SELECT id, subtotal, descuento, iva, total, created_at
  FROM pedidos WHERE id = p_id;

  SELECT i.id, i.producto_id, pr.sku, pr.nombre, i.cantidad, i.precio_unitario
  FROM pedido_items i
  JOIN productos pr ON pr.id = i.producto_id
  WHERE i.pedido_id = p_id
  ORDER BY i.id ASC;
END $$

DELIMITER ;
