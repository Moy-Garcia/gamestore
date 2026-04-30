-- ============================================================
-- GameStore - Base de datos completa
-- Importar en phpMyAdmin: localhost/phpmyadmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS gamestore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gamestore;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    saldo_cartera DECIMAL(10,2) DEFAULT 0.00,
    saldo_crypto DECIMAL(18,8) DEFAULT 0.00000000,
    wallet_address VARCHAR(255) DEFAULT NULL,
    rol ENUM('cliente','admin') DEFAULT 'cliente',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    precio_crypto DECIMAL(18,8) DEFAULT 0.00000000,
    stock INT DEFAULT 0,
    categoria VARCHAR(100),
    imagen VARCHAR(255) DEFAULT 'no-image.png',
    destacado TINYINT(1) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('tarjeta','paypal','bitcoin','ethereum','solana','cartera') NOT NULL,
    estado ENUM('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente',
    referencia_pago VARCHAR(255),
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Detalle de pedidos
CREATE TABLE pedido_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tabla de carrito (sesión BD)
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Movimientos de cartera digital
CREATE TABLE cartera_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('recarga','compra','transferencia','recibido') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    descripcion VARCHAR(255),
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================================
-- Datos de ejemplo
-- ============================================================

-- Admin (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Admin GameStore', 'admin@gamestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, precio_crypto, stock, categoria, destacado) VALUES
('PlayStation 5 Digital Edition', 'Consola de última generación de Sony. 825GB SSD, resolución 4K, Ray Tracing.', 8999.00, 0.00150000, 5, 'Consolas', 1),
('Xbox Series X', 'La consola más potente de Microsoft. 1TB SSD, 4K/120fps, Quick Resume.', 8499.00, 0.00145000, 8, 'Consolas', 1),
('Nintendo Switch OLED', 'Pantalla OLED de 7 pulgadas, mayor almacenamiento interno (64GB), audio mejorado.', 5999.00, 0.00100000, 12, 'Consolas', 1),
('Gaming Headset HyperX Cloud Alpha', 'Sonido envolvente 7.1 virtual, micrófono desmontable, diadema de aluminio.', 1499.00, 0.00025000, 20, 'Accesorios', 0),
('Teclado Mecánico Razer BlackWidow V3', 'Switches mecánicos Razer Green, iluminación RGB Chroma, reposamuñecas incluido.', 2299.00, 0.00038000, 15, 'Periféricos', 1),
('Mouse Gaming Logitech G502 HERO', 'Sensor HERO 25K DPI, 11 botones programables, pesas ajustables, RGB.', 999.00, 0.00016000, 30, 'Periféricos', 0),
('Monitor Gaming ASUS ROG 144Hz', 'Panel IPS 27", 144Hz, 1ms, G-Sync compatible, resolución Full HD.', 4999.00, 0.00083000, 7, 'Monitores', 1),
('Silla Gamer DXRacer Formula', 'Tapizado en cuero PU, reclinable 135°, reposabrazos 3D, garantía 2 años.', 5500.00, 0.00092000, 10, 'Mobiliario', 0),
('Control PS5 DualSense', 'Gatillos hápticos, vibración haptica, micrófono integrado, USB-C.', 1299.00, 0.00021000, 25, 'Accesorios', 1),
('Steam Deck 512GB', 'PC portátil para gaming. Pantalla táctil 7", AMD RDNA 2, hasta 8h batería.', 9999.00, 0.00166000, 4, 'Consolas', 1),
('Webcam Logitech C922 Pro', '1080p/30fps, 720p/60fps, fondo virtual, micrófono estéreo con cancelación de ruido.', 1799.00, 0.00030000, 18, 'Streaming', 0),
('Capturadora Elgato HD60 S+', 'Captura 4K60, pass-through 4K HDR, compatible PS5/Xbox Series X.', 3299.00, 0.00055000, 9, 'Streaming', 0);
