<?php
session_start();
require_once 'config.php';
requireLogin();

$conn    = conectarDB();
$uid     = (int)$_SESSION['usuario_id'];
$metodo  = limpiar($_POST['metodo'] ?? '');
$metodos = ['tarjeta','paypal','bitcoin','ethereum','solana','cartera'];

if (!in_array($metodo, $metodos)) {
    header('Location: checkout.php'); exit;
}

// Obtener items del carrito
$items = $conn->query("
    SELECT c.cantidad, p.id as pid, p.nombre, p.precio
    FROM carrito c JOIN productos p ON p.id=c.producto_id
    WHERE c.usuario_id=$uid
")->fetch_all(MYSQLI_ASSOC);

if (empty($items)) { header('Location: carrito.php'); exit; }

$total = array_sum(array_map(fn($i)=>$i['precio']*$i['cantidad'], $items));
$ref   = strtoupper(bin2hex(random_bytes(6)));

// Validar saldo cartera
if ($metodo === 'cartera') {
    $saldo = (float)$conn->query("SELECT saldo_cartera FROM usuarios WHERE id=$uid")->fetch_assoc()['saldo_cartera'];
    if ($saldo < $total) {
        header('Location: checkout.php'); exit;
    }
    // Descontar saldo
    $conn->query("UPDATE usuarios SET saldo_cartera=saldo_cartera-$total WHERE id=$uid");
    // Registrar movimiento
    $desc = "Compra pedido #$ref";
    $st   = $conn->prepare("INSERT INTO cartera_movimientos (usuario_id, tipo, monto, descripcion) VALUES (?,?,?,?)");
    $tipo = 'compra';
    $st->bind_param('isds', $uid, $tipo, $total, $desc);
    $st->execute();
}

// Crear pedido
$st = $conn->prepare("INSERT INTO pedidos (usuario_id, total, total_final, metodo_pago, estado, referencia_pago) VALUES (?,?,?,?,?,?)");
$estado = ($metodo === 'cartera') ? 'pagado' : 'pendiente';
$st->bind_param('iddsss', $uid, $total, $total, $metodo, $estado, $ref);
$st->execute();
$pedido_id = $conn->insert_id;

// Insertar items del pedido
foreach ($items as $item) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $st2 = $conn->prepare("INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
    $st2->bind_param('iiidd', $pedido_id, $item['pid'], $item['cantidad'], $item['precio'], $subtotal);
    $st2->execute();
}

// Limpiar carrito
$conn->query("DELETE FROM carrito WHERE usuario_id=$uid");

$_SESSION['ultimo_pedido'] = $pedido_id;
$_SESSION['ultimo_ref']    = $ref;

$conn->close();
header('Location: pedido_ok.php');
exit;
