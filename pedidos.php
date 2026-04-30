<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();
$uid  = (int)$_SESSION['usuario_id'];

$pedidos = $conn->query("SELECT * FROM pedidos WHERE usuario_id=$uid ORDER BY creado_en DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();

$page_title = 'Mis Pedidos';
include 'header.php';
?>
<style>
.pedido-card{
    background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
    padding:20px 24px;margin-bottom:16px;transition:.2s;display:grid;
    grid-template-columns:1fr auto;align-items:center;gap:20px
}
.pedido-card:hover{border-color:rgba(0,245,255,.3)}
.ped-ref{font-family:'Orbitron',monospace;font-size:1rem;color:var(--text)}
.ped-meta{font-size:.8rem;color:var(--text-dim);margin-top:4px}
.ped-acciones{display:flex;align-items:center;gap:12px}
.est-badge{padding:5px 12px;border-radius:6px;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.pagado{background:rgba(57,255,20,.1);color:var(--green)}
.pendiente{background:rgba(255,107,0,.1);color:var(--orange)}
.enviado{background:rgba(0,245,255,.1);color:var(--cyan)}
.entregado{background:rgba(191,90,242,.1);color:var(--purple)}
.cancelado{background:rgba(255,45,85,.1);color:var(--red)}
.ped-total{font-family:'Orbitron',monospace;font-size:1.1rem;color:var(--cyan)}
.metodo-icon{font-size:1.3rem}
</style>

<h1 class="section-title">📦 MIS PEDIDOS</h1>

<?php
$iconos = ['tarjeta'=>'💳','paypal'=>'🅿️','bitcoin'=>'₿','ethereum'=>'⟠','solana'=>'◎','cartera'=>'💎'];
if(empty($pedidos)): ?>
<div style="text-align:center;padding:80px 20px;color:var(--text-dim)">
    <div style="font-size:5rem;margin-bottom:20px">📦</div>
    <p style="margin-bottom:16px">No tienes pedidos aún.</p>
    <a href="tienda.php" class="btn btn-primary">🎮 Ir a la tienda</a>
</div>
<?php else: ?>
<?php foreach($pedidos as $p): ?>
<div class="pedido-card fade-up">
    <div>
        <div class="ped-ref">
            <?= $iconos[$p['metodo_pago']]??'📦' ?> Pedido #<?= limpiar($p['referencia_pago']) ?>
        </div>
        <div class="ped-meta">
            <?= date('d/m/Y H:i', strtotime($p['creado_en'])) ?> ·
            <?= ucfirst($p['metodo_pago']) ?>
        </div>
    </div>
    <div class="ped-acciones">
        <span class="est-badge <?= $p['estado'] ?>"><?= $p['estado'] ?></span>
        <span class="ped-total"><?= formatoPrecio($p['total']) ?></span>
        <a href="generar_pdf.php?pedido=<?= $p['id'] ?>" class="btn btn-outline" style="padding:8px 14px;font-size:.8rem">📄 PDF</a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
