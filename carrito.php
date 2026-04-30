<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();
$uid  = (int)$_SESSION['usuario_id'];

// Eliminar item
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $st = $conn->prepare("DELETE FROM carrito WHERE id=? AND usuario_id=?");
    $st->bind_param('ii', $id, $uid);
    $st->execute();
    header('Location: carrito.php'); exit;
}

// Actualizar cantidad
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['actualizar'])) {
    foreach ($_POST['cantidad'] as $cid => $cant) {
        $cid  = (int)$cid; $cant = max(1, (int)$cant);
        $st   = $conn->prepare("UPDATE carrito SET cantidad=? WHERE id=? AND usuario_id=?");
        $st->bind_param('iii', $cant, $cid, $uid);
        $st->execute();
    }
    header('Location: carrito.php'); exit;
}

// Obtener carrito
$items = $conn->query("
    SELECT c.id as cid, c.cantidad, p.id as pid, p.nombre, p.precio, p.precio_crypto, cat.nombre as categoria, p.stock
    FROM carrito c
    JOIN productos p ON p.id=c.producto_id
    JOIN categorias cat ON cat.id=p.categoria_id
    WHERE c.usuario_id=$uid
")->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_map(fn($i)=>$i['precio']*$i['cantidad'], $items));
$total_crypto = array_sum(array_map(fn($i)=>$i['precio_crypto']*$i['cantidad'], $items));

$conn->close();
$page_title = 'Carrito';
include 'header.php';
?>
<style>
.carrito-layout{display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start}
.carrito-tabla{display:flex;flex-direction:column;gap:12px}
.item-row{
    display:grid;grid-template-columns:1fr auto auto auto;
    align-items:center;gap:16px;
    background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px 20px
}
.item-nombre{font-weight:600;font-size:.95rem}
.item-cat{font-size:.75rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px}
.item-precio{font-family:'Orbitron',monospace;color:var(--cyan);font-size:1rem;white-space:nowrap}
.qty-input{
    width:60px;padding:6px 8px;background:var(--bg-dark);border:1px solid var(--border);
    border-radius:6px;color:var(--text);text-align:center;font-family:'Orbitron',monospace;font-size:.9rem
}
.btn-del{background:rgba(255,45,85,.15);border:1px solid rgba(255,45,85,.3);color:#ff2d55;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:1rem;transition:.2s}
.btn-del:hover{background:rgba(255,45,85,.3)}
.resumen-card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;position:sticky;top:80px}
.resumen-card h3{font-family:'Orbitron',monospace;color:var(--cyan);margin-bottom:20px;font-size:1rem}
.resumen-row{display:flex;justify-content:space-between;margin-bottom:12px;font-size:.9rem}
.resumen-row.total{font-size:1.1rem;font-weight:700;color:var(--cyan);font-family:'Orbitron',monospace;border-top:1px solid var(--border);padding-top:12px;margin-top:4px}
.empty-cart{text-align:center;padding:80px 20px}
.empty-cart .icon{font-size:5rem;margin-bottom:20px}
@media(max-width:768px){.carrito-layout{grid-template-columns:1fr}.item-row{grid-template-columns:1fr auto}}
</style>

<h1 class="section-title">🛒 MI CARRITO</h1>

<?php if(empty($items)): ?>
<div class="empty-cart">
    <div class="icon">🛒</div>
    <h2 style="color:var(--text-dim);margin-bottom:12px">Tu carrito está vacío</h2>
    <a href="tienda.php" class="btn btn-primary">Ir a la tienda</a>
</div>
<?php else: ?>

<div class="carrito-layout">
    <div>
        <form method="POST" id="form-carrito">
        <div class="carrito-tabla">
        <?php foreach($items as $item): ?>
        <div class="item-row">
            <div>
                <div class="item-nombre"><?= limpiar($item['nombre']) ?></div>
                <div class="item-cat"><?= limpiar($item['categoria']) ?></div>
                <div style="font-size:.8rem;color:var(--text-dim);margin-top:4px">
                    ≈ <?= number_format($item['precio_crypto']*$item['cantidad'],8) ?> BTC
                </div>
            </div>
            <div class="item-precio"><?= formatoPrecio($item['precio']*$item['cantidad']) ?></div>
            <input type="number" name="cantidad[<?= $item['cid'] ?>]"
                   class="qty-input" value="<?= $item['cantidad'] ?>"
                   min="1" max="<?= $item['stock'] ?>"
                   onchange="document.getElementById('form-carrito').submit()">
            <a href="carrito.php?eliminar=<?= $item['cid'] ?>" class="btn-del" onclick="return confirm('¿Quitar del carrito?')">🗑</a>
        </div>
        <?php endforeach; ?>
        </div>
        <input type="hidden" name="actualizar" value="1">
        </form>
    </div>

    <div class="resumen-card">
        <h3>RESUMEN DEL PEDIDO</h3>
        <?php foreach($items as $item): ?>
        <div class="resumen-row">
            <span><?= limpiar(substr($item['nombre'],0,22)).'...' ?> ×<?= $item['cantidad'] ?></span>
            <span><?= formatoPrecio($item['precio']*$item['cantidad']) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="resumen-row total">
            <span>TOTAL</span>
            <span><?= formatoPrecio($total) ?></span>
        </div>
        <div style="text-align:center;margin:8px 0 16px;font-size:.78rem;color:var(--text-dim)">
            ≈ <?= number_format($total_crypto,8) ?> BTC
        </div>
        <a href="checkout.php" class="btn btn-primary" style="width:100%;justify-content:center">
            PROCEDER AL PAGO →
        </a>
        <a href="tienda.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:8px">
            ← Seguir comprando
        </a>
    </div>
</div>

<?php endif; ?>
<?php include 'footer.php'; ?>
