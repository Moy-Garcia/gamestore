<?php
// ============================================================
// config.php - Configuración global de GameStore
// ============================================================

// --- Base de datos ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gamestore');

// --- URL base (cambiar a tu IP si usas celular) ---
define('BASE_URL', 'http://192.168.100.13/gamestore');

// --- Tokens QR ---
define('TOKEN_DIR', __DIR__ . '/tokens/');
define('TOKEN_EXPIRY', 300); // 5 minutos

// --- Stripe (modo prueba - usa tus keys reales para producción) ---
define('STRIPE_PUBLIC_KEY', 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXXXX');
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXXXX');

// --- PayPal Sandbox ---
define('PAYPAL_CLIENT_ID', 'PAYPAL_SANDBOX_CLIENT_ID_AQUI');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' o 'live'

// --- Wallets de crypto para recibir pagos ---
define('WALLET_BTC',     '1A1zP1eP5QGefi2DMPTfTL5SLmv7Divf3'); // Demo
define('WALLET_ETH',     '0x742d35Cc6634C0532925a3b8D4C9B2A0e1a2B3C4'); // Demo
define('WALLET_SOL',     '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM'); // Demo
define('WALLET_TRX',     'TJCnKsPa7y5okkXvQAidZZADvu4iyGRsza'); // Demo

// --- Nombre de la tienda ---
define('STORE_NAME', 'GameStore MX');

// ============================================================
// Conexión a base de datos
// ============================================================
function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div style="font-family:monospace;background:#1a0000;color:#ff4444;padding:20px;border:1px solid #ff0000;">
            ⚠️ Error de conexión BD: ' . $conn->connect_error . '<br>
            Verifica que MySQL esté corriendo en XAMPP y que la BD "gamestore" exista.<br>
            Importa el archivo <strong>db.sql</strong> en phpMyAdmin primero.
        </div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ============================================================
// Funciones de sesión y autenticación
// ============================================================
function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// ============================================================
// Funciones de carrito
// ============================================================
function contarCarrito($usuario_id) {
    $conn = conectarDB();
    $id   = (int)$usuario_id;
    $r    = $conn->query("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id=$id");
    $row  = $r->fetch_assoc();
    $conn->close();
    return $row['total'] ?? 0;
}

// ============================================================
// Funciones de tokens QR
// ============================================================
function generarToken() {
    return bin2hex(random_bytes(16));
}

function guardarToken($token) {
    $data = ['status' => 'pendiente', 'created' => time()];
    file_put_contents(TOKEN_DIR . $token . '.json', json_encode($data));
}

function leerToken($token) {
    $file = TOKEN_DIR . $token . '.json';
    if (!file_exists($file)) return null;
    return json_decode(file_get_contents($file), true);
}

function actualizarToken($token, $data) {
    file_put_contents(TOKEN_DIR . $token . '.json', json_encode($data));
}

function borrarToken($token) {
    @unlink(TOKEN_DIR . $token . '.json');
}

// ============================================================
// Utilidades
// ============================================================
function formatoPrecio($precio) {
    return '$' . number_format($precio, 2, '.', ',') . ' MXN';
}

function formatoCrypto($monto, $simbolo = 'BTC') {
    return number_format($monto, 8) . ' ' . $simbolo;
}

function limpiar($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Generar URL de imagen QR (servicio externo gratuito)
function generarQR($texto, $size = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($texto) . '&size=' . $size . 'x' . $size . '&margin=5&ecc=M';
}
