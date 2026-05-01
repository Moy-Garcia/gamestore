-- ============================================================
-- GameStore MX - Schema completo y corregido
-- Compatible con MySQL 8.x y MariaDB 10.6+
-- ============================================================

DROP DATABASE IF EXISTS gamestore;
CREATE DATABASE gamestore
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gamestore;

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE usuarios (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    password        VARCHAR(255)    NOT NULL,
    avatar          VARCHAR(255)    DEFAULT 'default.png',
    saldo_cartera   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    saldo_crypto    DECIMAL(18,8)   NOT NULL DEFAULT 0.00000000,
    wallet_address  VARCHAR(255)    DEFAULT NULL,
    rol             ENUM('cliente','admin','moderador') NOT NULL DEFAULT 'cliente',
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    ultimo_login    DATETIME        DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios          PRIMARY KEY (id),
    CONSTRAINT uq_usuarios_email    UNIQUE      (email),
    CONSTRAINT ck_saldo_cartera     CHECK       (saldo_cartera >= 0),
    CONSTRAINT ck_saldo_crypto      CHECK       (saldo_crypto  >= 0)
);

CREATE TABLE categorias (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100)    NOT NULL,
    slug            VARCHAR(100)    NOT NULL,
    descripcion     TEXT            DEFAULT NULL,
    imagen          VARCHAR(255)    DEFAULT NULL,
    activa          TINYINT(1)      NOT NULL DEFAULT 1,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_categorias        PRIMARY KEY (id),
    CONSTRAINT uq_categorias_slug   UNIQUE      (slug)
);

CREATE TABLE productos (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    categoria_id    INT UNSIGNED    NOT NULL,
    nombre          VARCHAR(200)    NOT NULL,
    descripcion     TEXT            DEFAULT NULL,
    precio          DECIMAL(12,2)   NOT NULL,
    precio_crypto   DECIMAL(18,8)   NOT NULL DEFAULT 0.00000000,
    stock           INT             NOT NULL DEFAULT 0,
    stock_minimo    INT             NOT NULL DEFAULT 5,
    imagen          VARCHAR(255)    DEFAULT 'no-image.png',
    sku             VARCHAR(50)     DEFAULT NULL,
    destacado       TINYINT(1)      NOT NULL DEFAULT 0,
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    vistas          INT UNSIGNED    NOT NULL DEFAULT 0,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_productos         PRIMARY KEY (id),
    CONSTRAINT uq_productos_sku     UNIQUE      (sku),
    CONSTRAINT fk_productos_cat     FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    CONSTRAINT ck_precio_pos        CHECK       (precio > 0),
    CONSTRAINT ck_stock_pos         CHECK       (stock >= 0)
);

CREATE TABLE pedidos (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    total           DECIMAL(12,2)   NOT NULL,
    descuento       DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total_final     DECIMAL(12,2)   NOT NULL,
    metodo_pago     ENUM('tarjeta','paypal','bitcoin','ethereum','solana','trx','cartera') NOT NULL,
    estado          ENUM('pendiente','pagado','procesando','enviado','entregado','cancelado','reembolsado') NOT NULL DEFAULT 'pendiente',
    referencia_pago VARCHAR(255)    DEFAULT NULL,
    notas           TEXT            DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_pedidos           PRIMARY KEY (id),
    CONSTRAINT fk_pedidos_usuario   FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT ck_total_pos         CHECK       (total >= 0),
    CONSTRAINT ck_total_final_pos   CHECK       (total_final >= 0)
);

CREATE TABLE pedido_items (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    pedido_id       INT UNSIGNED    NOT NULL,
    producto_id     INT UNSIGNED    NOT NULL,
    cantidad        INT             NOT NULL,
    precio_unitario DECIMAL(12,2)   NOT NULL,
    subtotal        DECIMAL(12,2)   NOT NULL,
    CONSTRAINT pk_pedido_items      PRIMARY KEY (id),
    CONSTRAINT fk_pi_pedido         FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pi_producto       FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    CONSTRAINT ck_cantidad_pos      CHECK       (cantidad > 0)
);

CREATE TABLE carrito (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    producto_id     INT UNSIGNED    NOT NULL,
    cantidad        INT             NOT NULL DEFAULT 1,
    agregado_en     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_carrito           PRIMARY KEY (id),
    CONSTRAINT uq_carrito_user_prod UNIQUE      (usuario_id, producto_id),
    CONSTRAINT fk_carrito_usuario   FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    CONSTRAINT fk_carrito_producto  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    CONSTRAINT ck_cant_carrito      CHECK       (cantidad > 0)
);

CREATE TABLE cartera_movimientos (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    tipo            ENUM('recarga','compra','transferencia_enviada','transferencia_recibida','reembolso') NOT NULL,
    monto           DECIMAL(12,2)   NOT NULL,
    saldo_anterior  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    saldo_posterior DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    descripcion     VARCHAR(255)    DEFAULT NULL,
    referencia      VARCHAR(100)    DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_cartera_movs      PRIMARY KEY (id),
    CONSTRAINT fk_cartera_usuario   FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT ck_monto_cartera     CHECK       (monto > 0)
);

CREATE TABLE tokens_qr (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    token           CHAR(32)        NOT NULL,
    usuario_id      INT UNSIGNED    DEFAULT NULL,
    status          ENUM('pendiente','aprobado','expirado','cancelado') NOT NULL DEFAULT 'pendiente',
    ip_generador    VARCHAR(45)     DEFAULT NULL,
    ip_scanner      VARCHAR(45)     DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en       DATETIME        NOT NULL,
    usado_en        DATETIME        DEFAULT NULL,
    CONSTRAINT pk_tokens_qr         PRIMARY KEY (id),
    CONSTRAINT uq_token             UNIQUE      (token),
    CONSTRAINT fk_token_usuario     FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE actividad_log (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    DEFAULT NULL,
    accion          VARCHAR(100)    NOT NULL,
    tabla_afectada  VARCHAR(100)    DEFAULT NULL,
    registro_id     INT UNSIGNED    DEFAULT NULL,
    descripcion     TEXT            DEFAULT NULL,
    ip              VARCHAR(45)     DEFAULT NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_actividad_log     PRIMARY KEY (id),
    CONSTRAINT fk_log_usuario       FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_productos_nombre      ON productos            (nombre);
CREATE INDEX idx_productos_categoria   ON productos            (categoria_id);
CREATE INDEX idx_productos_activo      ON productos            (activo, destacado);
CREATE INDEX idx_productos_precio      ON productos            (precio);
CREATE INDEX idx_pedidos_usuario       ON pedidos              (usuario_id);
CREATE INDEX idx_pedidos_estado        ON pedidos              (estado);
CREATE INDEX idx_pedidos_fecha         ON pedidos              (creado_en);
CREATE INDEX idx_cartera_usuario       ON cartera_movimientos  (usuario_id);
CREATE INDEX idx_cartera_tipo          ON cartera_movimientos  (tipo);
CREATE INDEX idx_cartera_fecha         ON cartera_movimientos  (creado_en);
CREATE INDEX idx_tokens_status         ON tokens_qr            (status);
CREATE INDEX idx_tokens_expira         ON tokens_qr            (expira_en);
CREATE INDEX idx_log_usuario           ON actividad_log        (usuario_id);
CREATE INDEX idx_log_accion            ON actividad_log        (accion);
CREATE INDEX idx_log_fecha             ON actividad_log        (creado_en);

-- ============================================================
-- VISTAS
-- ============================================================

CREATE VIEW v_productos_completo AS
    SELECT p.id, p.sku, p.nombre AS producto, c.nombre AS categoria,
           p.precio, p.precio_crypto, p.stock, p.stock_minimo,
           (p.stock <= p.stock_minimo) AS stock_bajo,
           p.destacado, p.activo, p.vistas, p.creado_en
    FROM productos p
    INNER JOIN categorias c ON c.id = p.categoria_id;

CREATE VIEW v_pedidos_detalle AS
    SELECT pe.id AS pedido_id, pe.referencia_pago AS referencia,
           u.nombre AS cliente, u.email,
           pe.total, pe.descuento, pe.total_final,
           pe.metodo_pago, pe.estado,
           COUNT(pi.id) AS total_items, SUM(pi.cantidad) AS total_unidades,
           pe.creado_en
    FROM pedidos pe
    INNER JOIN usuarios     u  ON u.id       = pe.usuario_id
    LEFT  JOIN pedido_items pi ON pi.pedido_id = pe.id
    GROUP BY pe.id;

CREATE VIEW v_ventas_por_categoria AS
    SELECT c.nombre AS categoria,
           COUNT(DISTINCT pe.id) AS total_pedidos,
           SUM(pi.cantidad)      AS unidades_vendidas,
           SUM(pi.subtotal)      AS ingresos_totales
    FROM categorias       c
    INNER JOIN productos    p  ON p.categoria_id = c.id
    INNER JOIN pedido_items pi ON pi.producto_id = p.id
    INNER JOIN pedidos      pe ON pe.id          = pi.pedido_id
        AND pe.estado NOT IN ('cancelado','reembolsado')
    GROUP BY c.id
    ORDER BY ingresos_totales DESC;

CREATE VIEW v_stock_bajo AS
    SELECT p.id, p.sku, p.nombre, c.nombre AS categoria, p.stock, p.stock_minimo
    FROM productos p
    INNER JOIN categorias c ON c.id = p.categoria_id
    WHERE p.stock <= p.stock_minimo AND p.activo = 1
    ORDER BY p.stock ASC;

CREATE VIEW v_saldos_usuarios AS
    SELECT u.id, u.nombre, u.email, u.saldo_cartera, u.saldo_crypto,
           COUNT(cm.id) AS total_movimientos,
           SUM(CASE WHEN cm.tipo = 'recarga' THEN cm.monto ELSE 0 END) AS total_recargado,
           SUM(CASE WHEN cm.tipo = 'compra'  THEN cm.monto ELSE 0 END) AS total_gastado
    FROM usuarios u
    LEFT JOIN cartera_movimientos cm ON cm.usuario_id = u.id
    WHERE u.activo = 1
    GROUP BY u.id;

-- ============================================================
-- STORED PROCEDURES  (todos con el mismo DELIMITER)
-- ============================================================

DELIMITER $$

CREATE PROCEDURE sp_registrar_usuario(
    IN  p_nombre   VARCHAR(100),
    IN  p_email    VARCHAR(150),
    IN  p_password VARCHAR(255),
    IN  p_wallet   VARCHAR(255),
    OUT p_nuevo_id INT,
    OUT p_mensaje  VARCHAR(255)
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    SELECT COUNT(*) INTO v_existe FROM usuarios WHERE email = p_email;
    IF v_existe > 0 THEN
        SET p_nuevo_id = 0;
        SET p_mensaje  = 'ERROR: El email ya esta registrado.';
    ELSE
        INSERT INTO usuarios (nombre, email, password, wallet_address)
        VALUES (p_nombre, p_email, p_password, p_wallet);
        SET p_nuevo_id = LAST_INSERT_ID();
        SET p_mensaje  = 'OK: Usuario registrado correctamente.';
        INSERT INTO actividad_log (usuario_id, accion, tabla_afectada, registro_id, descripcion)
        VALUES (p_nuevo_id, 'REGISTRO', 'usuarios', p_nuevo_id, CONCAT('Nuevo usuario: ', p_email));
    END IF;
END$$

CREATE PROCEDURE sp_recargar_cartera(
    IN  p_usuario_id  INT UNSIGNED,
    IN  p_monto       DECIMAL(12,2),
    IN  p_descripcion VARCHAR(255),
    OUT p_nuevo_saldo DECIMAL(12,2),
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE v_saldo_actual DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_existe       INT           DEFAULT 0;
    SELECT COUNT(*), saldo_cartera
    INTO v_existe, v_saldo_actual
    FROM usuarios WHERE id = p_usuario_id AND activo = 1;
    IF v_existe = 0 THEN
        SET p_nuevo_saldo = 0;
        SET p_mensaje     = 'ERROR: Usuario no encontrado.';
    ELSEIF p_monto < 100 THEN
        SET p_nuevo_saldo = v_saldo_actual;
        SET p_mensaje     = 'ERROR: Monto minimo es $100.';
    ELSEIF p_monto > 50000 THEN
        SET p_nuevo_saldo = v_saldo_actual;
        SET p_mensaje     = 'ERROR: Monto maximo es $50,000.';
    ELSE
        UPDATE usuarios SET saldo_cartera = saldo_cartera + p_monto WHERE id = p_usuario_id;
        SET p_nuevo_saldo = v_saldo_actual + p_monto;
        INSERT INTO cartera_movimientos
            (usuario_id, tipo, monto, saldo_anterior, saldo_posterior, descripcion)
        VALUES (p_usuario_id, 'recarga', p_monto, v_saldo_actual, p_nuevo_saldo, p_descripcion);
        INSERT INTO actividad_log (usuario_id, accion, tabla_afectada, descripcion)
        VALUES (p_usuario_id, 'RECARGA_CARTERA', 'cartera_movimientos',
                CONCAT('Recarga: $', p_monto, ' MXN'));
        SET p_mensaje = CONCAT('OK: Saldo actual: $', p_nuevo_saldo);
    END IF;
END$$

CREATE PROCEDURE sp_crear_pedido(
    IN  p_usuario_id  INT UNSIGNED,
    IN  p_metodo_pago VARCHAR(20),
    OUT p_pedido_id   INT,
    OUT p_referencia  VARCHAR(20),
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE v_total       DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_saldo       DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_items_count INT           DEFAULT 0;
    DECLARE v_ref         VARCHAR(20);
    DECLARE v_error       TINYINT       DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_pedido_id = 0; SET p_referencia = ''; SET p_mensaje = 'ERROR: Fallo en la transaccion.';
    END;

    SELECT COUNT(*), COALESCE(SUM(p.precio * c.cantidad), 0)
    INTO v_items_count, v_total
    FROM carrito c INNER JOIN productos p ON p.id = c.producto_id
    WHERE c.usuario_id = p_usuario_id;

    IF v_items_count = 0 THEN
        SET p_pedido_id = 0; SET p_referencia = '';
        SET p_mensaje = 'ERROR: Carrito vacio.'; SET v_error = 1;
    END IF;

    IF v_error = 0 AND p_metodo_pago = 'cartera' THEN
        SELECT saldo_cartera INTO v_saldo FROM usuarios WHERE id = p_usuario_id;
        IF v_saldo < v_total THEN
            SET p_pedido_id = 0; SET p_referencia = '';
            SET p_mensaje = 'ERROR: Saldo insuficiente.'; SET v_error = 1;
        END IF;
    END IF;

    IF v_error = 0 THEN
        START TRANSACTION;
        IF p_metodo_pago = 'cartera' THEN
            UPDATE usuarios SET saldo_cartera = saldo_cartera - v_total WHERE id = p_usuario_id;
        END IF;
        SET v_ref = UPPER(SUBSTRING(MD5(RAND()), 1, 12));
        INSERT INTO pedidos (usuario_id, total, total_final, metodo_pago, estado, referencia_pago)
        VALUES (p_usuario_id, v_total, v_total, p_metodo_pago,
                IF(p_metodo_pago='cartera','pagado','pendiente'), v_ref);
        SET p_pedido_id = LAST_INSERT_ID();
        INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
        SELECT p_pedido_id, c.producto_id, c.cantidad, p.precio, p.precio * c.cantidad
        FROM carrito c INNER JOIN productos p ON p.id = c.producto_id
        WHERE c.usuario_id = p_usuario_id;
        UPDATE productos p
        INNER JOIN carrito c ON c.producto_id = p.id AND c.usuario_id = p_usuario_id
        SET p.stock = p.stock - c.cantidad;
        DELETE FROM carrito WHERE usuario_id = p_usuario_id;
        COMMIT;
        SET p_referencia = v_ref;
        SET p_mensaje = CONCAT('OK: Pedido #', v_ref, ' - $', v_total);
    END IF;
END$$

CREATE PROCEDURE sp_reporte_ventas(
    IN p_fecha_ini DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT DATE(pe.creado_en) AS fecha,
           COUNT(pe.id)       AS total_pedidos,
           SUM(pe.total_final) AS ingresos,
           pe.metodo_pago, pe.estado
    FROM pedidos pe
    WHERE DATE(pe.creado_en) BETWEEN p_fecha_ini AND p_fecha_fin
    GROUP BY DATE(pe.creado_en), pe.metodo_pago, pe.estado
    ORDER BY fecha DESC;
END$$

CREATE PROCEDURE sp_limpiar_tokens()
BEGIN
    DECLARE v_borrados INT DEFAULT 0;
    UPDATE tokens_qr SET status = 'expirado'
    WHERE status = 'pendiente' AND expira_en < NOW();
    SET v_borrados = ROW_COUNT();
    DELETE FROM tokens_qr
    WHERE status IN ('expirado','cancelado')
      AND creado_en < DATE_SUB(NOW(), INTERVAL 7 DAY);
    SELECT CONCAT('Tokens expirados marcados: ', v_borrados) AS resultado;
END$$

-- ============================================================
-- FUNCTIONS
-- ============================================================

CREATE FUNCTION fn_total_compras(p_usuario_id INT UNSIGNED)
RETURNS DECIMAL(12,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total DECIMAL(12,2) DEFAULT 0.00;
    SELECT COALESCE(SUM(total_final), 0) INTO v_total
    FROM pedidos
    WHERE usuario_id = p_usuario_id AND estado NOT IN ('cancelado','reembolsado');
    RETURN v_total;
END$$

CREATE FUNCTION fn_estado_stock(p_stock INT, p_minimo INT)
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    RETURN CASE
        WHEN p_stock = 0              THEN 'AGOTADO'
        WHEN p_stock <= p_minimo      THEN 'STOCK_BAJO'
        WHEN p_stock <= p_minimo * 2  THEN 'STOCK_MEDIO'
        ELSE                               'DISPONIBLE'
    END;
END$$

CREATE FUNCTION fn_generar_referencia()
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    RETURN UPPER(CONCAT(
        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ', FLOOR(1+RAND()*26), 1),
        SUBSTRING('ABCDEFGHIJKLMNOPQRSTUVWXYZ', FLOOR(1+RAND()*26), 1),
        LPAD(FLOOR(RAND() * 999999), 6, '0')
    ));
END$$

CREATE FUNCTION fn_saldo_suficiente(p_usuario_id INT UNSIGNED, p_monto DECIMAL(12,2))
RETURNS TINYINT(1)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_saldo DECIMAL(12,2) DEFAULT 0.00;
    SELECT saldo_cartera INTO v_saldo FROM usuarios WHERE id = p_usuario_id;
    RETURN v_saldo >= p_monto;
END$$

-- ============================================================
-- TRIGGERS
-- ============================================================

CREATE TRIGGER trg_validar_stock_before_insert
BEFORE INSERT ON pedido_items
FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;
    SELECT stock INTO v_stock FROM productos WHERE id = NEW.producto_id;
    IF v_stock < NEW.cantidad THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuficiente para este producto.';
    END IF;
    SET NEW.subtotal = NEW.precio_unitario * NEW.cantidad;
END$$

CREATE TRIGGER trg_log_nuevo_pedido
AFTER INSERT ON pedidos
FOR EACH ROW
BEGIN
    INSERT INTO actividad_log (usuario_id, accion, tabla_afectada, registro_id, descripcion)
    VALUES (NEW.usuario_id, 'NUEVO_PEDIDO', 'pedidos', NEW.id,
            CONCAT('Pedido creado. Total: $', NEW.total_final, ' | Metodo: ', NEW.metodo_pago));
END$$

CREATE TRIGGER trg_log_estado_pedido
AFTER UPDATE ON pedidos
FOR EACH ROW
BEGIN
    IF OLD.estado <> NEW.estado THEN
        INSERT INTO actividad_log (usuario_id, accion, tabla_afectada, registro_id, descripcion)
        VALUES (NEW.usuario_id, 'CAMBIO_ESTADO_PEDIDO', 'pedidos', NEW.id,
                CONCAT('Estado: ', OLD.estado, ' -> ', NEW.estado));
    END IF;
END$$

CREATE TRIGGER trg_cartera_mov_after_insert
AFTER INSERT ON cartera_movimientos
FOR EACH ROW
BEGIN
    INSERT INTO actividad_log (usuario_id, accion, tabla_afectada, registro_id, descripcion)
    VALUES (NEW.usuario_id, CONCAT('CARTERA_', UPPER(NEW.tipo)),
            'cartera_movimientos', NEW.id,
            CONCAT('Monto: $', NEW.monto,
                   ' | Saldo antes: $', NEW.saldo_anterior,
                   ' -> $', NEW.saldo_posterior));
END$$

CREATE TRIGGER trg_protect_producto_delete
BEFORE DELETE ON productos
FOR EACH ROW
BEGIN
    DECLARE v_en_pedido INT DEFAULT 0;
    SELECT COUNT(*) INTO v_en_pedido
    FROM pedido_items pi
    INNER JOIN pedidos pe ON pe.id = pi.pedido_id
    WHERE pi.producto_id = OLD.id
      AND pe.estado NOT IN ('cancelado','reembolsado','entregado');
    IF v_en_pedido > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se puede eliminar: producto en pedidos activos.';
    END IF;
END$$

CREATE TRIGGER trg_alerta_stock_bajo
AFTER UPDATE ON productos
FOR EACH ROW
BEGIN
    IF NEW.stock <= NEW.stock_minimo AND OLD.stock > OLD.stock_minimo THEN
        INSERT INTO actividad_log (accion, tabla_afectada, registro_id, descripcion)
        VALUES ('ALERTA_STOCK_BAJO', 'productos', NEW.id,
                CONCAT('Producto "', NEW.nombre, '" con stock bajo: ', NEW.stock, ' unidades'));
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

INSERT INTO categorias (nombre, slug, descripcion) VALUES
('Consolas',    'consolas',    'Consolas de videojuegos de ultima generacion'),
('Accesorios',  'accesorios',  'Audifonos, controles y perifericos de gaming'),
('Perifericos', 'perifericos', 'Teclados, mouse y dispositivos de entrada'),
('Monitores',   'monitores',   'Pantallas gaming de alta frecuencia'),
('Mobiliario',  'mobiliario',  'Sillas y escritorios gamer'),
('Streaming',   'streaming',   'Equipos para creadores de contenido');

-- Admin  (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol, wallet_address) VALUES
('Admin GameStore', 'admin@gamestore.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', '0xADMIN00000000000000000000000000000000');

-- Demo   (password: password)
INSERT INTO usuarios (nombre, email, password, rol, saldo_cartera, wallet_address) VALUES
('Usuario Demo', 'demo@gamestore.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'cliente', 2500.00, '0xDEMO000000000000000000000000000000000');

INSERT INTO productos (categoria_id, nombre, descripcion, precio, precio_crypto, stock, stock_minimo, destacado, sku) VALUES
(1,'PlayStation 5 Digital','Consola Sony 825GB SSD, 4K, Ray Tracing, DualSense.',8999.00,0.00150000,5,2,1,'PS5-DIG-001'),
(1,'Xbox Series X','Microsoft 1TB SSD, 4K/120fps, Quick Resume.',8499.00,0.00145000,8,2,1,'XBX-SX-001'),
(1,'Nintendo Switch OLED','Pantalla OLED 7", 64GB interno, audio mejorado.',5999.00,0.00100000,12,3,1,'NSW-OLED-001'),
(1,'Steam Deck 512GB','PC portatil gaming. AMD RDNA 2, hasta 8h bateria.',9999.00,0.00166000,4,2,1,'SDECK-512-001'),
(2,'HyperX Cloud Alpha','Sonido 7.1, microfono desmontable, aluminio.',1499.00,0.00025000,20,5,0,'HX-ALPHA-001'),
(2,'Control PS5 DualSense','Gatillos hapticos, vibracion haptica, USB-C.',1299.00,0.00021000,25,5,1,'PS5-DS-001'),
(3,'Razer BlackWidow V3','Switches Green, RGB Chroma, reposamuñecas.',2299.00,0.00038000,15,5,1,'RZR-BW3-001'),
(3,'Logitech G502 HERO','Sensor HERO 25K DPI, 11 botones, pesas ajustables.',999.00,0.00016000,30,8,0,'LGT-G502-001'),
(4,'ASUS ROG 144Hz 27"','Panel IPS, 144Hz, 1ms, G-Sync, Full HD.',4999.00,0.00083000,7,2,1,'ASUS-ROG27-001'),
(5,'DXRacer Formula','Cuero PU, reclinable 135 grados, reposabrazos 3D.',5500.00,0.00092000,10,3,0,'DXR-FORM-001'),
(6,'Logitech C922 Pro','1080p/30fps, 720p/60fps, fondo virtual.',1799.00,0.00030000,18,5,0,'LGT-C922-001'),
(6,'Elgato HD60 S+','Captura 4K60, pass-through 4K HDR.',3299.00,0.00055000,9,3,0,'ELG-HD60-001');

-- ============================================================
-- VERIFICACION
-- ============================================================
SELECT '== TABLAS ==' AS '';    SHOW TABLES;
SELECT '== VISTAS ==' AS '';
SELECT table_name FROM information_schema.views WHERE table_schema='gamestore';
SELECT '== PROCEDURES ==' AS '';
SELECT routine_name FROM information_schema.routines WHERE routine_schema='gamestore' AND routine_type='PROCEDURE';
SELECT '== FUNCTIONS ==' AS '';
SELECT routine_name FROM information_schema.routines WHERE routine_schema='gamestore' AND routine_type='FUNCTION';
SELECT '== TRIGGERS ==' AS '';
SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema='gamestore';
SELECT '✅ Schema GameStore listo.' AS resultado;