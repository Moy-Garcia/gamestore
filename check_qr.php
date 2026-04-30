<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

if (!$token || $token !== ($_SESSION['qr_token'] ?? '')) {
    echo json_encode(['status' => 'error']);
    exit;
}

$datos = leerToken($token);
if (!$datos) { echo json_encode(['status' => 'error']); exit; }

if (time() - $datos['created'] > TOKEN_EXPIRY) {
    echo json_encode(['status' => 'expirado']); exit;
}

if ($datos['status'] === 'aprobado') {
    // Crear sesión de acceso
    $_SESSION['acceso']    = true;
    $_SESSION['login_en']  = date('Y-m-d H:i:s');
    unset($_SESSION['qr_token']);
    borrarToken($token);

    // Por ahora acceso como usuario demo (en producción buscar el usuario real)
    // Para demo: loguear como primer usuario que no sea admin
    $conn = conectarDB();
    $r = $conn->query("SELECT id, nombre, email, rol FROM usuarios WHERE rol='cliente' LIMIT 1");
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
