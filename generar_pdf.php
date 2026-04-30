<?php
// generar_pdf.php
// Genera un recibo/perfil en HTML preparado para imprimir como PDF
// El navegador puede guardarlo como PDF con Ctrl+P > "Guardar como PDF"
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

// QR del perfil de usuario
$qrPerfil  = generarQR(BASE_URL . '/dashboard.php', 120);
$qrPedido  = $pedido ? generarQR(BASE_URL . '/pedidos.php?id=' . $pedido_id, 120) : null;
$fecha     = date('d/M/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo GameStore</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Rajdhani:wght@400;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Rajdhani',sans-serif;background:#fff;color:#111;padding:40px;max-width:800px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:3px solid #000}
.logo{font-family:'Orbitron',monospace;font-size:2rem;font-weight:900}
.logo span{color:#007acc}
.header-right{text-align:right;font-size:.85rem;color:#555}
.section{margin-bottom:28px}
.section h2{font-family:'Orbitron',monospace;font-size:.9rem;letter-spacing:2px;text-transform:uppercase;color:#007acc;border-bottom:1px solid #ddd;padding-bottom:6px;margin-bottom:12px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.info-row{display:flex;flex-direction:column}
.info-lbl{font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:#888}
.info-val{font-size:.95rem;font-weight:600;color:#111}
table{width:100%;border-collapse:collapse;margin-top:8px}
th{background:#111;color:#fff;font-family:'Orbitron',monospace;font-size:.72rem;letter-spacing:1px;padding:8px 12px;text-align:left}
td{padding:8px 12px;border-bottom:1px solid #eee;font-size:.9rem}
.total-row{font-family:'Orbitron',monospace;font-size:1rem;color:#007acc;font-weight:700}
.qr-section{display:flex;gap:32px;align-items:flex-start;justify-content:center;margin-top:20px}
.qr-box{text-align:center}
.qr-box img{border:2px solid #eee;border-radius:8px;padding:4px}
.qr-box p{font-size:.72rem;color:#888;margin-top:6px;letter-spacing:.5px;text-transform:uppercase}
.footer-pdf{text-align:center;margin-top:40px;padding-top:20px;border-top:1px solid #ddd;font-size:.75rem;color:#aaa}
.estado-badge{display:inline-block;padding:4px 12px;border-radius:4px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:1px}
.pagado{background:#d4edda;color:#155724}.pendiente{background:#fff3cd;color:#856404}.cancelado{background:#f8d7da;color:#721c24}
.no-print{
    position:fixed;bottom:30px;right:30px;
    background:#007acc;color:#fff;padding:12px 20px;border-radius:10px;
    font-family:'Rajdhani',sans-serif;font-weight:700;cursor:pointer;border:none;font-size:1rem;
    box-shadow:0 4px 15px rgba(0,122,204,.4)
}
@media print{.no-print{display:none}body{padding:20px}}
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="logo">⚡ Game<span>Store</span></div>
        <div style="color:#555;font-size:.85rem;margin-top:4px">La mejor tienda gaming de México</div>
    </div>
    <div class="header-right">
        <div style="font-weight:700"><?= STORE_NAME ?></div>
        <div>Emitido: <?= $fecha ?></div>
        <?php if($pedido): ?>
        <div style="margin-top:8px">
            <span class="estado-badge <?= $pedido['estado'] ?>"><?= strtoupper($pedido['estado']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Datos del usuario -->
<div class="section">
    <h2>Datos del Cliente</h2>
    <div class="info-grid">
        <div class="info-row">
            <span class="info-lbl">Nombre</span>
            <span class="info-val"><?= limpiar($user['nombre']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Email</span>
            <span class="info-val"><?= limpiar($user['email']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-lbl">ID de cliente</span>
            <span class="info-val">#<?= str_pad($user['id'],6,'0',STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Saldo en cartera</span>
            <span class="info-val"><?= formatoPrecio($user['saldo_cartera']) ?></span>
        </div>
    </div>
</div>

<?php if($pedido): ?>
<!-- Detalle del pedido -->
<div class="section">
    <h2>Detalle del Pedido #<?= limpiar($pedido['referencia_pago']) ?></h2>
    <div class="info-grid" style="margin-bottom:16px">
        <div class="info-row">
            <span class="info-lbl">Fecha</span>
            <span class="info-val"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Método de pago</span>
            <span class="info-val"><?= ucfirst($pedido['metodo_pago']) ?></span>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($items as $item): ?>
        <tr>
            <td><?= limpiar($item['nombre']) ?></td>
            <td><?= $item['cantidad'] ?></td>
            <td><?= formatoPrecio($item['precio_unitario']) ?></td>
            <td><?= formatoPrecio($item['precio_unitario'] * $item['cantidad']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" style="text-align:right;font-weight:700">TOTAL:</td>
            <td class="total-row"><?= formatoPrecio($pedido['total']) ?></td>
        </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- QR codes -->
<div class="qr-section">
    <div class="qr-box">
        <img src="<?= $qrPerfil ?>" width="120" height="120">
        <p>Mi perfil</p>
    </div>
    <?php if($qrPedido): ?>
    <div class="qr-box">
        <img src="<?= $qrPedido ?>" width="120" height="120">
        <p>Este pedido</p>
    </div>
    <?php endif; ?>
    <div class="qr-box">
        <img src="<?= generarQR(BASE_URL . '/tienda.php', 120) ?>" width="120" height="120">
        <p>Tienda online</p>
    </div>
</div>

<div class="footer-pdf">
    <p><?= STORE_NAME ?> · Documento generado automáticamente · <?= BASE_URL ?></p>
    <p style="margin-top:4px">Este documento tiene validez como comprobante de compra.</p>
</div>

<button class="no-print" onclick="window.print()">🖨 Guardar como PDF</button>

</body>
</html>
