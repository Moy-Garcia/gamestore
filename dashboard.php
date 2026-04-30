<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();
$uid  = (int)$_SESSION['usuario_id'];

$user = $conn->query("SELECT * FROM usuarios WHERE id=$uid")->fetch_assoc();
$pedidos_count = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE usuario_id=$uid")->fetch_assoc()['c'];
$pedidos_total = $conn->query("SELECT SUM(total) as t FROM pedidos WHERE usuario_id=$uid AND estado='pagado'")->fetch_assoc()['t'] ?? 0;
$ultimos_pedidos = $conn->query("SELECT * FROM pedidos WHERE usuario_id=$uid ORDER BY creado_en DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// QR único del usuario (para login)
$qr_user = generarQR(BASE_URL . '/index.php?user=' . $uid, 180);
$conn->close();

$page_title = 'Mi Cuenta';
include 'header.php';
?>
<style>
.dash-grid{display:grid;grid-template-columns:300px 1fr;gap:32px;align-items:start}
.user-card{
    background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:32px;text-align:center;
    position:sticky;top:80px
}
.user-avatar{
    width:80px;height:80px;background:linear-gradient(135deg,var(--cyan),var(--purple));
    border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:2rem;margin:0 auto 16px
}
.user-nombre{font-family:'Orbitron',monospace;font-size:1.1rem;color:var(--text);margin-bottom:4px}
.user-email{font-size:.82rem;color:var(--text-dim);margin-bottom:20px}
.user-qr{background:white;padding:10px;border-radius:10px;display:inline-block;margin-bottom:16px}
.user-qr img{display:block;border-radius:6px}
.stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}
.stat-box{background:var(--bg-dark);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center}
.stat-val{font-family:'Orbitron',monospace;font-size:1rem;color:var(--cyan)}
.stat-lbl{font-size:.72rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.pedido-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border)}
.pedido-row:last-child{border-bottom:none}
.est-badge{padding:4px 10px;border-radius:6px;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.pagado{background:rgba(57,255,20,.1);color:var(--green)}
.pendiente{background:rgba(255,107,0,.1);color:var(--orange)}
.enviado{background:rgba(0,245,255,.1);color:var(--cyan)}
.wallet-box{
    background:linear-gradient(135deg,rgba(191,90,242,.15),rgba(0,245,255,.05));
    border:1px solid rgba(191,90,242,.3);border-radius:16px;padding:24px;margin-bottom:24px
}
.wallet-saldo{font-family:'Orbitron',monospace;font-size:2rem;color:var(--purple);margin:8px 0}
.wallet-addr-box{background:var(--bg-dark);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.75rem;color:var(--text-dim);word-break:break-all;margin-top:12px}
</style>

<h1 class="section-title">👤 MI CUENTA</h1>

<div class="dash-grid">
    <!-- Panel usuario -->
    <div class="user-card">
        <div class="user-avatar">
            <?= mb_substr($user['nombre'], 0, 1) ?>
        </div>
        <div class="user-nombre"><?= limpiar($user['nombre']) ?></div>
        <div class="user-email"><?= limpiar($user['email']) ?></div>

        <p style="font-size:.78rem;color:var(--text-dim);margin-bottom:8px">Tu QR personal de acceso:</p>
        <div class="user-qr">
            <img src="<?= $qr_user ?>" alt="Mi QR" width="180">
        </div>
        <p style="font-size:.72rem;color:var(--text-dim)">Escanea para acceder desde otro dispositivo</p>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-val"><?= $pedidos_count ?></div>
                <div class="stat-lbl">Pedidos</div>
            </div>
            <div class="stat-box">
                <div class="stat-val"><?= formatoPrecio($pedidos_total) ?></div>
                <div class="stat-lbl">Gastado</div>
            </div>
        </div>

        <div style="margin-top:16px">
            <a href="generar_pdf.php" class="btn btn-purple" style="width:100%;justify-content:center;margin-bottom:8px">
                📄 Mi perfil en PDF
            </a>
            <a href="cartera.php" class="btn btn-outline" style="width:100%;justify-content:center">
                💎 Ver cartera
            </a>
        </div>
    </div>

    <!-- Contenido principal -->
    <div>
        <!-- Cartera resumen -->
        <div class="wallet-box">
            <div style="font-size:.8rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px">💎 Saldo en cartera</div>
            <div class="wallet-saldo"><?= formatoPrecio($user['saldo_cartera']) ?></div>
            <div style="font-size:.82rem;color:var(--text-dim)">
                ≈ <?= number_format($user['saldo_crypto'],8) ?> BTC
            </div>
            <div class="wallet-addr-box">
                Wallet: <?= limpiar($user['wallet_address'] ?? 'No asignada') ?>
            </div>
            <a href="cartera.php" class="btn btn-purple" style="margin-top:16px">Gestionar cartera →</a>
        </div>

        <!-- Últimos pedidos -->
        <div class="card">
            <h3 style="font-family:'Orbitron',monospace;color:var(--cyan);font-size:.95rem;margin-bottom:20px">📦 ÚLTIMOS PEDIDOS</h3>
            <?php if(empty($ultimos_pedidos)): ?>
            <p style="color:var(--text-dim);text-align:center;padding:20px">No tienes pedidos aún. <a href="tienda.php" style="color:var(--cyan)">¡Ir a la tienda!</a></p>
            <?php else: ?>
            <?php foreach($ultimos_pedidos as $p): ?>
            <div class="pedido-row">
                <div>
                    <div style="font-weight:600;font-size:.9rem">#<?= strtoupper(substr($p['referencia_pago'],0,8)) ?></div>
                    <div style="font-size:.78rem;color:var(--text-dim)"><?= date('d/m/Y H:i', strtotime($p['creado_en'])) ?> · <?= ucfirst($p['metodo_pago']) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:12px">
                    <span class="est-badge <?= $p['estado'] ?>"><?= $p['estado'] ?></span>
                    <span style="font-family:'Orbitron',monospace;font-size:.9rem;color:var(--cyan)"><?= formatoPrecio($p['total']) ?></span>
                    <a href="generar_pdf.php?pedido=<?= $p['id'] ?>" style="color:var(--text-dim);font-size:1rem" title="Descargar PDF">📄</a>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="pedidos.php" style="display:block;text-align:center;margin-top:16px;color:var(--cyan);font-size:.88rem">Ver todos mis pedidos →</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
