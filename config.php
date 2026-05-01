<?php
// ============================================================
// config.php - GameStore MX
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gamestore');

// Auto-detecta si estás en local o en producción con tu dominio
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($_host === 'localhost' || str_starts_with($_host, '127.') || str_starts_with($_host, '192.168.')) {
    define('BASE_URL', 'http://' . $_host . '/gamestore');
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $scheme . '://' . $_host);
}

define('TOKEN_DIR',    __DIR__ . '/tokens/');
define('TOKEN_EXPIRY', 300);

define('STRIPE_PUBLIC_KEY', 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXXXX');
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXXXX');
define('PAYPAL_CLIENT_ID',  'PAYPAL_SANDBOX_CLIENT_ID_AQUI');
define('PAYPAL_MODE',       'sandbox');

define('WALLET_BTC', '1A1zP1eP5QGefi2DMPTfTL5SLmv7Divf3');
define('WALLET_ETH', '0x742d35Cc6634C0532925a3b8D4C9B2A0e1a2B3C4');
define('WALLET_SOL', '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM');
define('WALLET_TRX', 'TJCnKsPa7y5okkXvQAidZZADvu4iyGRsza');

define('STORE_NAME', 'GameStore MX');

// Crear carpeta tokens automáticamente si no existe
if (!is_dir(TOKEN_DIR)) {
    mkdir(TOKEN_DIR, 0775, true);
}

// ============================================================
function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div style="font-family:monospace;background:#1a0000;color:#ff4444;padding:20px">
            ⚠️ Error BD: ' . htmlspecialchars($conn->connect_error) . '</div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function requireLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function estaLogueado() { return isset($_SESSION['usuario_id']); }
function esAdmin()       { return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'; }

function contarCarrito($usuario_id) {
    $conn = conectarDB();
    $id   = (int)$usuario_id;
    $r    = $conn->query("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id=$id");
    $row  = $r->fetch_assoc();
    $conn->close();
    return $row['total'] ?? 0;
}

function generarToken()           { return bin2hex(random_bytes(16)); }

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

function borrarToken($token) { @unlink(TOKEN_DIR . $token . '.json'); }

function formatoPrecio($precio) {
    return '$' . number_format((float)$precio, 2, '.', ',') . ' MXN';
}

function limpiar($str) {
    return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
}

function generarQR($texto, $size = 200) {
    return 'https://api.qrserver.com/v1/create-qr-code/?data='
         . urlencode($texto) . '&size=' . $size . 'x' . $size . '&margin=5&ecc=M';
}
