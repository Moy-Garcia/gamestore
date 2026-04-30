<?php
// recibo_publico.php - Recibo público sin necesidad de login
// Accesible desde el celular después del pago simulado
require_once 'config.php';

$pedido_id = limpiar($_GET['pedido'] ?? 'DEMO');
$ref       = limpiar($_GET['ref']    ?? bin2hex(random_bytes(8)));
$fecha     = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago | <?= STORE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Rajdhani',sans-serif;background:#050508;color:#e0e0f0;min-height:100vh;padding:20px}
        .wrap{max-width:420px;margin:0 auto}
        .logo{font-family:'Orbitron',monospace;font-size:1.2rem;color:#00f5ff;text-align:center;padding:20px 0;margin-bottom:16px}
        .card{background:#0f0f1a;border:1px solid rgba(0,245,255,.15);border-radius:20px;padding:24px;margin-bottom:16px}
        .check{font-size:4rem;text-align:center;margin-bottom:12px;animation:pop .5s cubic-bezier(.175,.885,.32,1.275)}
        @keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
        h2{font-family:'Orbitron',monospace;font-size:1.1rem;color:#39ff14;text-align:center;margin-bottom:20px}
        .row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.88rem}
        .row:last-child{border-bottom:none}
        .lbl{color:#7070a0}
        .val{font-weight:600;text-align:right;max-width:60%;word-break:break-all;font-family:monospace;font-size:.78rem}
        .tx{background:rgba(57,255,20,.05);border:1px solid rgba(57,255,20,.2);border-radius:10px;padding:12px;margin:16px 0;font-family:monospace;font-size:.68rem;color:#39ff14;word-break:break-all}
        .demo-nota{background:rgba(255,107,0,.1);border:1px solid rgba(255,107,0,.3);border-radius:10px;padding:12px;font-size:.8rem;color:#ff6b00;text-align:center;margin-bottom:16px}
        .btn-volver{display:block;width:100%;padding:16px;border:none;border-radius:12px;background:linear-gradient(135deg,#00f5ff,#bf5af2);color:#000;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;text-align:center;text-decoration:none;margin-bottom:8px}
        .btn-imprimir{display:block;width:100%;padding:14px;border:1px solid rgba(0,245,255,.3);border-radius:12px;background:transparent;color:#00f5ff;font-family:'Rajdhani',sans-serif;font-size:.95rem;font-weight:600;cursor:pointer;text-align:center;text-decoration:none}
        .entrega{background:rgba(0,245,255,.05);border:1px solid rgba(0,245,255,.15);border-radius:10px;padding:14px;margin:16px 0;text-align:center}
        .entrega-dias{font-family:'Orbitron',monospace;font-size:1.5rem;color:#00f5ff;margin:4px 0}
        .entrega-lbl{font-size:.75rem;color:#7070a0;text-transform:uppercase;letter-spacing:1px}
        @media print{.btn-volver,.btn-imprimir{display:none}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="logo">⚡ <?= STORE_NAME ?></div>

    <div class="card">
        <div class="check">🎉</div>
        <h2>¡PAGO CONFIRMADO!</h2>

        <div class="row">
            <span class="lbl">Pedido #</span>
            <span class="val"><?= $pedido_id ?></span>
        </div>
        <div class="row">
            <span class="lbl">Fecha</span>
            <span class="val"><?= $fecha ?></span>
        </div>
        <div class="row">
            <span class="lbl">Estado</span>
            <span class="val" style="color:#39ff14">✓ CONFIRMADO</span>
        </div>
        <div class="row">
            <span class="lbl">Método</span>
            <span class="val">Crypto (Demo)</span>
        </div>

        <div class="tx">
            <div style="color:#7070a0;margin-bottom:4px;font-size:.7rem">TX HASH (SIMULADO):</div>
            <?= $ref ?>
        </div>

        <div class="entrega">
            <div class="entrega-lbl">Entrega estimada</div>
            <div class="entrega-dias">3-5 días</div>
            <div class="entrega-lbl">hábiles</div>
        </div>
    </div>

    <div class="demo-nota">
        🎓 <strong>Proyecto Escolar Demo</strong><br>
        Pago simulado — No se realizó ninguna transacción real
    </div>

    <a href="<?= BASE_URL ?>/tienda.php" class="btn-volver">
        🎮 VOLVER A LA TIENDA
    </a>
    <a href="javascript:window.print()" class="btn-imprimir">
        🖨 IMPRIMIR RECIBO
    </a>
</div>
</body>
</html>
