<?php
session_start();
require_once 'config.php';
requireLogin();

$pedido_id = $_SESSION['ultimo_pedido'] ?? null;
$ref       = $_SESSION['ultimo_ref']    ?? 'N/A';
if (!$pedido_id) { header('Location: tienda.php'); exit; }

$qr_url = BASE_URL . '/pedidos.php?id=' . $pedido_id;
$page_title = 'Pedido Confirmado';
include 'header.php';
?>
<style>
.ok-wrap{max-width:600px;margin:0 auto;text-align:center;padding:40px 0}
.ok-icon{font-size:6rem;animation:aparecer .5s cubic-bezier(.175,.885,.32,1.275)}
@keyframes aparecer{from{transform:scale(0)}to{transform:scale(1)}}
.ok-wrap h1{font-family:'Orbitron',monospace;font-size:2rem;color:var(--green);margin:20px 0 8px}
.ok-wrap .ref{
    font-family:monospace;font-size:1.2rem;
    background:rgba(57,255,20,.1);border:1px solid rgba(57,255,20,.3);
    color:var(--green);padding:10px 20px;border-radius:8px;
    display:inline-block;margin:16px 0
}
.qr-pedido{
    background:white;padding:16px;border-radius:16px;display:inline-block;
    box-shadow:0 8px 32px rgba(0,245,255,.2);margin:20px 0
}
.acciones{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px}
</style>

<div class="ok-wrap fade-up">
    <div class="ok-icon">🎉</div>
    <h1>¡PEDIDO CONFIRMADO!</h1>
    <p style="color:var(--text-dim);margin-bottom:8px">Tu referencia de pedido es:</p>
    <div class="ref">#<?= limpiar($ref) ?></div>
    <p style="color:var(--text-dim);font-size:.9rem;margin-bottom:20px">
        Guarda el QR de tu pedido para rastrearlo fácilmente.
    </p>
    <div class="qr-pedido">
        <img src="<?= generarQR($qr_url, 180) ?>" alt="QR Pedido" width="180">
    </div>
    <br>
    <p style="color:var(--text-dim);font-size:.85rem">
        Escanea este QR para ver el estado de tu pedido en cualquier momento.
    </p>
    <div class="acciones">
        <a href="pedidos.php" class="btn btn-primary">📦 Ver mis pedidos</a>
        <a href="generar_pdf.php?pedido=<?= $pedido_id ?>" class="btn btn-purple">📄 Descargar PDF</a>
        <a href="tienda.php" class="btn btn-outline">🎮 Seguir comprando</a>
    </div>
</div>

<?php include 'footer.php'; ?>
