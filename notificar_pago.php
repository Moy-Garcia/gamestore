<?php
// notificar_pago.php
// El celular llama aquí cuando confirma el pago
// El checkout.php hace polling a este archivo para detectar la confirmación
require_once 'config.php';
header('Content-Type: application/json');

$token  = $_GET['token']  ?? '';
$pedido = $_GET['pedido'] ?? '';

if (!$token) {
    echo json_encode(['ok' => false]);
    exit;
}

// Guardar notificación en archivo temporal
$archivo = TOKEN_DIR . 'pago_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $token) . '.json';
$data    = ['pagado' => true, 'pedido' => $pedido, 'ts' => time()];
file_put_contents($archivo, json_encode($data));

echo json_encode(['ok' => true]);