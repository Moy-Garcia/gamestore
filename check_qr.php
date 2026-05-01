<?php
// check_qr.php - Polling del navegador para saber si el QR fue escaneado
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

// Validar que el token pertenezca a esta sesión
if (empty($token) || $token !== ($_SESSION['qr_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'msg' => 'token_mismatch']);
    exit;
}

// Verificar que la carpeta tokens existe y es escribible
if (!is_dir(TOKEN_DIR)) {
    if (!mkdir(TOKEN_DIR, 0775, true)) {
        echo json_encode(['status' => 'error', 'msg' => 'no_dir']);
        exit;
    }
}

$archivo = TOKEN_DIR . $token . '.json';

// Si el archivo no existe, el token aún es válido (pendiente)
if (!file_exists($archivo)) {
    // Intentar crearlo por si acá estamos en la primera llamada
    guardarToken($token);
    echo json_encode(['status' => 'pendiente']);
    exit;
}

$datos = json_decode(file_get_contents($archivo), true);

if (!$datos) {
    echo json_encode(['status' => 'pendiente']);
    exit;
}

// Verificar expiración
if (time() - $datos['created'] > TOKEN_EXPIRY) {
    $datos['status'] = 'expirado';
    actualizarToken($token, $datos);
    echo json_encode(['status' => 'expirado']);
    exit;
}

// Si fue aprobado: crear sesión
if ($datos['status'] === 'aprobado') {
    $_SESSION['acceso']   = true;
    $_SESSION['login_en'] = date('Y-m-d H:i:s');
    unset($_SESSION['qr_token']);
    borrarToken($token);

    // Loguear como el primer cliente disponible (demo)
    $conn = conectarDB();
    $r    = $conn->query("SELECT id, nombre, email, rol FROM usuarios WHERE rol='cliente' AND activo=1 LIMIT 1");
    if ($row = $r->fetch_assoc()) {
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['nombre']     = $row['nombre'];
        $_SESSION['email']      = $row['email'];
        $_SESSION['rol']        = $row['rol'];
    }
    $conn->close();

    echo json_encode(['status' => 'aprobado']);
    exit;
}

echo json_encode(['status' => 'pendiente']);