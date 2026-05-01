<?php
session_start();
require_once 'config.php';
requireLogin();

$conn      = conectarDB();
$uid       = (int)$_SESSION['usuario_id'];
$pedido_id = (int)($_GET['pedido'] ?? 0);

$user = $conn->query("SELECT * FROM usuarios WHERE id=$uid")->fetch_assoc();

$pedido = null;
$items  = [];
if ($pedido_id) {
    $pedido = $conn->query("SELECT * FROM pedidos WHERE id=$pedido_id AND usuario_id=$uid")->fetch_assoc();
    if ($pedido) {
        $items = $conn->query("
            SELECT pi.*, p.nombre FROM pedido_items pi
            JOIN productos p ON p.id=pi.producto_id
            WHERE pi.pedido_id=$pedido_id
        ")->fetch_all(MYSQLI_ASSOC);
    }
}
$conn->close();

// Hora correcta México
date_default_timezone_set('America/Mexico_City');
$fecha = date('d/m/Y H:i:s');

$qrPedido = $pedido ? generarQR(BASE_URL.'/pedidos.php?id='.$pedido_id, 100) : null;
$qrPerfil = generarQR(BASE_URL.'/dashboard.php', 100);
$qrTienda = generarQR(BASE_URL.'/tienda.php', 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket | <?= STORE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}

        body{
            font-family:'Rajdhani',sans-serif;
            background:#e8e8e8;
            display:flex;flex-direction:column;
            align-items:center;
            padding:24px;min-height:100vh;
        }

        /* Ticket ancho fijo tipo térmico */
        .ticket{
            background:#fff;
            width:310px;
            padding:24px 18px 28px;
            border-radius:0;
            box-shadow:0 4px 20px rgba(0,0,0,.2);
            position:relative;
        }

        .logo{text-align:center;font-family:'Orbitron',monospace;font-size:1.4rem;font-weight:900;letter-spacing:2px}
        .logo span{color:#007acc}
        .logo-sub{text-align:center;font-size:.65rem;color:#999;letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;margin-bottom:12px}

        .sep{border:none;border-top:1px dashed #ccc;margin:10px 0}
        .sep-bold{border:none;border-top:2px solid #111;margin:10px 0}

        .meta{text-align:center;font-size:.72rem;color:#666;line-height:1.7}
        .folio{text-align:center;font-family:'Orbitron',monospace;font-size:.9rem;font-weight:700;color:#007acc;margin-top:3px}

        .estado{display:inline-block;padding:3px 10px;border-radius:3px;font-family:'Orbitron',monospace;font-size:.65rem;font-weight:700;letter-spacing:1px;text-transform:uppercase}
        .pagado{background:#d4edda;color:#155724}
        .pendiente{background:#fff3cd;color:#856404}
        .cancelado{background:#f8d7da;color:#721c24}
        .procesando{background:#cce5ff;color:#004085}
        .enviado{background:#d1ecf1;color:#0c5460}
        .entregado{background:#d4edda;color:#155724}

        .sec-lbl{font-family:'Orbitron',monospace;font-size:.58rem;letter-spacing:1.5px;text-transform:uppercase;color:#aaa;margin:8px 0 5px}

        .fila{display:flex;justify-content:space-between;align-items:baseline;font-size:.78rem;margin-bottom:3px}
        .fila .lbl{color:#777}
        .fila .val{font-weight:600;text-align:right;max-width:55%;word-break:break-all;font-size:.75rem}

        /* Productos */
        .prod-th{display:grid;grid-template-columns:1fr 26px 54px 54px;gap:2px;font-size:.6rem;font-weight:700;text-transform:uppercase;color:#aaa;letter-spacing:.5px;margin-bottom:3px}
        .prod-tr{display:grid;grid-template-columns:1fr 26px 54px 54px;gap:2px;font-size:.74rem;margin-bottom:5px;align-items:start;line-height:1.3}
        .tc{text-align:center}
        .tr{text-align:right;font-family:monospace}

        .subtotal-row{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:2px;color:#555}
        .total-row{display:flex;justify-content:space-between;font-family:'Orbitron',monospace;font-size:1rem;font-weight:900;color:#007acc;padding-top:6px;border-top:2px solid #111;margin-top:5px}

        .qr-wrap{display:flex;justify-content:center;gap:14px;margin:10px 0}
        .qr-box{text-align:center}
        .qr-box img{background:#fff;border:1px solid #eee;padding:3px;border-radius:3px}
        .qr-box p{font-size:.58rem;color:#bbb;margin-top:3px;text-transform:uppercase;letter-spacing:.5px}

        .gracias{text-align:center;font-family:'Orbitron',monospace;font-size:.72rem;letter-spacing:1.5px;margin:8px 0;color:#333}
        .pie{text-align:center;font-size:.65rem;color:#bbb;line-height:1.7;margin-top:4px}

        .btn-print{
            margin-top:20px;padding:12px 36px;
            background:#007acc;color:#fff;border:none;
            border-radius:8px;font-family:'Rajdhani',sans-serif;
            font-size:1rem;font-weight:700;cursor:pointer;
            box-shadow:0 4px 12px rgba(0,122,204,.3);transition:.2s;
        }
        .btn-print:hover{background:#005fa3;transform:translateY(-1px)}

        @media print{
            body{background:#fff;padding:0;display:block}
            .btn-print{display:none}
            .ticket{width:100%;box-shadow:none;padding:8px}
            @page{size:80mm auto;margin:3mm}
        }
    </style>
</head>
<body>

<div class="ticket">

    <div class="logo">⚡ Game<span>Store</span></div>
    <div class="logo-sub">La mejor tienda gaming de México</div>

    <hr class="sep-bold">

    <div class="meta">
        <?= $fecha ?><br>
        <?= limpiar($user['nombre']) ?>
    </div>

    <?php if($pedido): ?>
        <div class="folio">FOLIO #<?= limpiar($pedido['referencia_pago']) ?></div>
        <div style="text-align:center;margin-top:5px">
            <span class="estado <?= $pedido['estado'] ?>"><?= strtoupper($pedido['estado']) ?></span>
        </div>
    <?php endif; ?>

    <hr class="sep">

    <!-- CLIENTE -->
    <div class="sec-lbl">Cliente</div>
    <div class="fila"><span class="lbl">ID</span><span class="val">#<?= str_pad($user['id'],6,'0',STR_PAD_LEFT) ?></span></div>
    <div class="fila"><span class="lbl">Email</span><span class="val"><?= limpiar($user['email']) ?></span></div>

    <?php if($pedido): ?>
        <hr class="sep">

        <!-- PAGO -->
        <div class="sec-lbl">Información del pago</div>
        <div class="fila"><span class="lbl">Método</span><span class="val"><?= ucfirst($pedido['metodo_pago']) ?></span></div>
        <div class="fila"><span class="lbl">Fecha pedido</span><span class="val"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></span></div>
        <div class="fila"><span class="lbl">Entrega estimada</span><span class="val">3 – 5 días hábiles</span></div>

        <hr class="sep">

        <!-- ARTÍCULOS -->
        <div class="sec-lbl">Artículos</div>
        <div class="prod-th">
            <span>Producto</span><span class="tc">Cant</span>
            <span class="tr">P.Unit</span><span class="tr">Total</span>
        </div>
        <hr class="sep">
        <?php foreach($items as $item): ?>
            <div class="prod-tr">
                <span><?= limpiar($item['nombre']) ?></span>
                <span class="tc"><?= $item['cantidad'] ?></span>
                <span class="tr">$<?= number_format($item['precio_unitario'],2) ?></span>
                <span class="tr">$<?= number_format($item['precio_unitario']*$item['cantidad'],2) ?></span>
            </div>
        <?php endforeach; ?>
        <hr class="sep">

        <!-- TOTALES -->
        <div class="subtotal-row"><span>Subtotal</span><span><?= formatoPrecio($pedido['total']) ?></span></div>
        <div class="subtotal-row"><span>Descuento</span><span><?= formatoPrecio($pedido['descuento'] ?? 0) ?></span></div>
        <div class="total-row"><span>TOTAL</span><span><?= formatoPrecio($pedido['total_final']) ?></span></div>
    <?php endif; ?>

    <hr class="sep">

    <!-- QR -->
    <div class="sec-lbl" style="text-align:center">Escanea para más opciones</div>
    <div class="qr-wrap">
        <?php if($qrPedido): ?>
            <div class="qr-box"><img src="<?= $qrPedido ?>" width="76" height="76"><p>Mi pedido</p></div>
        <?php endif; ?>
        <div class="qr-box"><img src="<?= $qrPerfil ?>" width="76" height="76"><p>Mi cuenta</p></div>
        <div class="qr-box"><img src="<?= $qrTienda ?>" width="76" height="76"><p>Tienda</p></div>
    </div>

    <hr class="sep">

    <div class="gracias">¡ GRACIAS POR TU COMPRA !</div>
    <div class="pie">
        <?= STORE_NAME ?><br>
        <?= BASE_URL ?><br>
        Conserva este comprobante de pago
    </div>

</div>

<button class="btn-print" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>

</body>
</html>