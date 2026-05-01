<?php
// recibo_publico.php - Comprobante accesible desde celular sin login
require_once 'config.php';
date_default_timezone_set('America/Mexico_City');

$pedido_id = limpiar($_GET['pedido'] ?? 'N/A');
$ref       = limpiar($_GET['ref']    ?? strtoupper(bin2hex(random_bytes(8))));
$fecha     = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Comprobante | <?= STORE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Rajdhani',sans-serif;
            background:#e8e8e8;
            min-height:100vh;
            display:flex;flex-direction:column;
            align-items:center;padding:20px;
        }
        .ticket{
            background:#fff;width:100%;max-width:340px;
            padding:24px 18px 28px;
            box-shadow:0 4px 20px rgba(0,0,0,.2);
        }
        .logo{text-align:center;font-family:'Orbitron',monospace;font-size:1.3rem;font-weight:900;letter-spacing:2px}
        .logo span{color:#007acc}
        .logo-sub{text-align:center;font-size:.65rem;color:#999;letter-spacing:1px;text-transform:uppercase;margin:3px 0 12px}
        .sep{border:none;border-top:1px dashed #ccc;margin:10px 0}
        .sep-bold{border:none;border-top:2px solid #111;margin:10px 0}
        .check{font-size:3.5rem;text-align:center;margin:10px 0;animation:pop .5s cubic-bezier(.175,.885,.32,1.275)}
        @keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
        .titulo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;color:#155724;text-align:center;letter-spacing:1.5px;margin-bottom:4px}
        .meta{text-align:center;font-size:.72rem;color:#666;line-height:1.7;margin-bottom:6px}
        .folio{text-align:center;font-family:'Orbitron',monospace;font-size:.88rem;font-weight:700;color:#007acc}
        .sec-lbl{font-family:'Orbitron',monospace;font-size:.58rem;letter-spacing:1.5px;text-transform:uppercase;color:#aaa;margin:8px 0 5px}
        .fila{display:flex;justify-content:space-between;align-items:baseline;font-size:.78rem;margin-bottom:3px}
        .lbl{color:#777}
        .val{font-weight:600;text-align:right;word-break:break-all;font-size:.75rem}
        .tx-box{
            background:#f8fff8;border:1px dashed #c3e6cb;border-radius:4px;
            padding:8px;margin:8px 0;font-family:monospace;font-size:.6rem;
            color:#155724;word-break:break-all;text-align:center;line-height:1.5
        }
        .tx-lbl{font-size:.58rem;color:#aaa;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px}
        .estado{
            display:inline-block;padding:4px 12px;border-radius:3px;
            font-family:'Orbitron',monospace;font-size:.65rem;font-weight:700;
            letter-spacing:1px;background:#d4edda;color:#155724;margin:4px 0
        }
        .gracias{text-align:center;font-family:'Orbitron',monospace;font-size:.7rem;letter-spacing:1.5px;margin:8px 0;color:#333}
        .pie{text-align:center;font-size:.65rem;color:#bbb;line-height:1.7;margin-top:4px}

        .btn-volver{
            display:block;width:100%;padding:14px;margin-top:16px;
            background:#007acc;color:#fff;border:none;border-radius:6px;
            font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;
            text-align:center;text-decoration:none;letter-spacing:.5px;
            cursor:pointer;transition:.2s
        }
        .btn-volver:hover{background:#005fa3}
        .btn-print{
            display:block;width:100%;padding:12px;margin-top:8px;
            background:transparent;color:#007acc;border:1px dashed #007acc;
            border-radius:6px;font-family:'Rajdhani',sans-serif;font-size:.9rem;font-weight:600;
            text-align:center;cursor:pointer;transition:.2s
        }
        .btn-print:hover{background:#e8f4ff}

        @media print{
            body{background:#fff;padding:0;display:block}
            .btn-volver,.btn-print{display:none}
            .ticket{box-shadow:none;width:100%;padding:6px}
            @page{size:80mm auto;margin:3mm}
        }
    </style>
</head>
<body>

<div class="ticket">

    <div class="logo">⚡ Game<span>Store</span></div>
    <div class="logo-sub">La mejor tienda gaming de México</div>

    <hr class="sep-bold">

    <div class="check">✅</div>
    <div class="titulo">PAGO CONFIRMADO</div>

    <div style="text-align:center;margin:4px 0">
        <span class="estado">AUTORIZADO</span>
    </div>

    <hr class="sep">

    <div class="meta">
        <?= $fecha ?><br>
        <?= STORE_NAME ?>
    </div>
    <div class="folio">FOLIO #<?= $pedido_id ?></div>

    <hr class="sep">

    <!-- DETALLES -->
    <div class="sec-lbl">Detalles de la transacción</div>
    <div class="fila"><span class="lbl">Estado</span><span class="val" style="color:#155724;font-weight:700">✓ Confirmado</span></div>
    <div class="fila"><span class="lbl">Método</span><span class="val">Pago Crypto</span></div>
    <div class="fila"><span class="lbl">Fecha</span><span class="val"><?= $fecha ?></span></div>
    <div class="fila"><span class="lbl">Entrega estimada</span><span class="val">3 – 5 días hábiles</span></div>

    <hr class="sep">

    <!-- HASH DE TRANSACCIÓN -->
    <div class="sec-lbl">Hash de transacción</div>
    <div class="tx-box">
        <div class="tx-lbl">TX ID</div>
        <?= $ref ?>
    </div>

    <hr class="sep">

    <!-- PRÓXIMOS PASOS -->
    <div class="sec-lbl">¿Qué sigue?</div>
    <div style="font-size:.75rem;color:#555;line-height:1.7;margin-bottom:6px">
        📦 Tu pedido está siendo preparado para envío.<br>
        📱 Recibirás actualizaciones en tu cuenta.<br>
        🔍 Rastrea tu pedido desde <em>Mis Pedidos</em>.
    </div>

    <hr class="sep">

    <div class="gracias">¡ GRACIAS POR TU COMPRA !</div>
    <div class="pie">
        <?= STORE_NAME ?><br>
        <?= BASE_URL ?><br>
        Conserva este comprobante
    </div>

    <a href="<?= BASE_URL ?>/tienda.php" class="btn-volver">🎮 Volver a la tienda</a>
    <button onclick="window.print()" class="btn-print">🖨 Guardar comprobante</button>

</div>

</body>
</html>