<?php
session_start();
require_once 'config.php';
requireLogin();

$conn = conectarDB();

// Filtros
$cat    = limpiar($_GET['cat']    ?? '');
$buscar = limpiar($_GET['buscar'] ?? '');
$orden  = limpiar($_GET['orden']  ?? 'destacado');

$where  = "WHERE activo=1";
$params = [];
$types  = '';

if ($cat) { $where .= " AND categoria_id=?"; $params[] = $cat; $types .= 's'; }
if ($buscar) { $where .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
               $params[] = "%$buscar%"; $params[] = "%$buscar%"; $types .= 'ss'; }

$orderMap = [
    'destacado' => 'destacado DESC, nombre ASC',
    'precio_asc'=> 'precio ASC',
    'precio_desc'=> 'precio DESC',
    'nombre'    => 'nombre ASC',
];
$orderSQL = $orderMap[$orden] ?? 'destacado DESC';

$sql  = "SELECT * FROM productos $where ORDER BY $orderSQL";
$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Categorías para filtro
$cats = $conn->query("SELECT id, nombre FROM categorias WHERE activa=1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $pid = (int)$_POST['producto_id'];
    $uid = (int)$_SESSION['usuario_id'];
    $ex  = $conn->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id=? AND producto_id=?");
    $ex->bind_param('ii', $uid, $pid);
    $ex->execute();
    $row = $ex->get_result()->fetch_assoc();
    if ($row) {
        $nc = $row['cantidad'] + 1;
        $conn->prepare("UPDATE carrito SET cantidad=? WHERE id=?")->execute() || true;
        $up = $conn->prepare("UPDATE carrito SET cantidad=? WHERE id=?");
        $up->bind_param('ii', $nc, $row['id']);
        $up->execute();
    } else {
        $in = $conn->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?,?,1)");
        $in->bind_param('ii', $uid, $pid);
        $in->execute();
    }
    header('Location: tienda.php?agregado=1' . ($cat?"&cat=$cat":'') . ($buscar?"&buscar=$buscar":''));
    exit;
}

$conn->close();
$page_title = 'Tienda';
$catColors  = ['Consolas'=>'#00f5ff','Accesorios'=>'#bf5af2','Periféricos'=>'#39ff14','Monitores'=>'#ff6b00','Mobiliario'=>'#ff2d55','Streaming'=>'#f7c948'];
include 'header.php';
?>
<style>
.tienda-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px}
.tienda-header h1{font-family:'Orbitron',monospace;font-size:1.5rem;color:var(--cyan)}
.filtros{display:flex;gap:8px;flex-wrap:wrap}
.filtros a,.filtros-sort select{
    padding:8px 16px;border-radius:8px;border:1px solid var(--border);
    background:var(--bg-card);color:var(--text-dim);font-family:'Rajdhani',sans-serif;
    font-size:.85rem;font-weight:600;text-decoration:none;cursor:pointer;
    transition:all .2s;letter-spacing:.5px
}
.filtros a.active,.filtros a:hover{border-color:var(--cyan);color:var(--cyan);background:rgba(0,245,255,.08)}
.filtros-sort select{outline:none}
.buscar-bar{display:flex;gap:8px;margin-bottom:24px}
.buscar-bar input{flex:1;padding:10px 16px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'Rajdhani',sans-serif;font-size:.95rem;outline:none}
.buscar-bar input:focus{border-color:var(--cyan)}
.buscar-bar button{padding:10px 20px;background:linear-gradient(135deg,var(--cyan),var(--purple));border:none;border-radius:8px;color:#000;font-weight:700;font-family:'Rajdhani',sans-serif;cursor:pointer}
.grid-productos{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px}
.producto-card{
    background:var(--bg-card);border:1px solid var(--border);border-radius:16px;
    overflow:hidden;transition:all .25s;display:flex;flex-direction:column
}
.producto-card:hover{transform:translateY(-4px);border-color:rgba(0,245,255,.4);box-shadow:0 12px 40px rgba(0,245,255,.1)}
.prod-img{
    height:180px;background:linear-gradient(135deg,#0a0a20,#16162a);
    display:flex;align-items:center;justify-content:center;font-size:4rem;
    border-bottom:1px solid var(--border);position:relative
}
.prod-destacado{
    position:absolute;top:10px;right:10px;
    background:linear-gradient(135deg,var(--orange),#ff2d55);
    color:white;font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:4px;
    font-family:'Orbitron',monospace;letter-spacing:1px
}
.prod-body{padding:16px 20px;flex:1;display:flex;flex-direction:column;gap:8px}
.prod-cat{font-size:.72rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase}
.prod-nombre{font-size:1rem;font-weight:600;color:var(--text);line-height:1.3}
.prod-desc{font-size:.82rem;color:var(--text-dim);line-height:1.5;flex:1}
.prod-precio{
    font-family:'Orbitron',monospace;font-size:1.2rem;
    color:var(--cyan);font-weight:700;margin-top:8px
}
.prod-crypto{font-size:.75rem;color:var(--text-dim);font-family:monospace}
.prod-actions{padding:12px 20px 20px;display:flex;gap:8px}
.btn-add{
    flex:1;padding:10px;border:none;border-radius:8px;
    background:linear-gradient(135deg,var(--cyan),var(--purple));color:#000;
    font-family:'Rajdhani',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;
    transition:all .2s;letter-spacing:.5px
}
.btn-add:hover{opacity:.9;transform:translateY(-1px)}
.stock-badge{
    display:inline-block;padding:3px 8px;border-radius:4px;font-size:.72rem;font-weight:700
}
.in-stock{background:rgba(57,255,20,.1);color:var(--green);border:1px solid rgba(57,255,20,.2)}
.low-stock{background:rgba(255,107,0,.1);color:var(--orange);border:1px solid rgba(255,107,0,.2)}
.toast{
    position:fixed;bottom:30px;right:30px;
    background:linear-gradient(135deg,rgba(57,255,20,.2),rgba(0,245,255,.1));
    border:1px solid rgba(57,255,20,.4);border-radius:12px;
    padding:14px 20px;color:var(--green);font-weight:600;
    animation:slideIn .3s ease;z-index:9999
}
@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}
.no-results{text-align:center;padding:80px 20px;color:var(--text-dim)}
.no-results .icon{font-size:4rem;margin-bottom:16px}
</style>

<?php if(isset($_GET['agregado'])): ?>
<div class="toast" id="toast">✅ Producto agregado al carrito</div>
<script>setTimeout(()=>document.getElementById('toast').remove(),3000)</script>
<?php endif; ?>

<div class="tienda-header">
    <h1>🎮 TIENDA GAMING</h1>
    <div class="filtros">
        <a href="tienda.php" class="<?= !$cat&&!$buscar?'active':'' ?>">Todos</a>
<?php foreach($cats as $c): ?>
        <a href="tienda.php?cat=<?= $c['id'] ?>" class="<?= (int)$cat===$c['id']?'active':'' ?>">
            <?= limpiar($c['nombre']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<form method="GET" class="buscar-bar">
    <?php if($cat): ?><input type="hidden" name="cat" value="<?= limpiar($cat) ?>"><?php endif; ?>
    <input type="text" name="buscar" placeholder="🔍 Buscar productos..." value="<?= $buscar ?>">
    <select name="orden" class="filtros-sort" onchange="this.form.submit()">
        <option value="destacado" <?= $orden==='destacado'?'selected':'' ?>>⭐ Destacados</option>
        <option value="precio_asc" <?= $orden==='precio_asc'?'selected':'' ?>>💰 Menor precio</option>
        <option value="precio_desc" <?= $orden==='precio_desc'?'selected':'' ?>>💎 Mayor precio</option>
        <option value="nombre" <?= $orden==='nombre'?'selected':'' ?>>🔤 A-Z</option>
    </select>
    <button type="submit">Buscar</button>
</form>

<?php
$emojis = ['Consolas'=>'🎮','Accesorios'=>'🎧','Periféricos'=>'⌨️','Monitores'=>'🖥️','Mobiliario'=>'🪑','Streaming'=>'📡'];
?>

<?php if(empty($productos)): ?>
<div class="no-results">
    <div class="icon">😕</div>
    <p>No se encontraron productos. <a href="tienda.php" style="color:var(--cyan)">Ver todos</a></p>
</div>
<?php else: ?>
<div class="grid-productos">
<?php foreach($productos as $p):
    $em    = $emojis[$p['categoria_id']] ?? '📦';
    $color = '#00f5ff';
    $stock = $p['stock'];
?>
<div class="producto-card fade-up">
    <div class="prod-img">
        <?= $em ?>
        <?php if($p['destacado']): ?><span class="prod-destacado">★ TOP</span><?php endif; ?>
    </div>
    <div class="prod-body">
        <span class="prod-cat" style="color:<?= $color ?>"><?= limpiar($p['nombre']) ?></span>
        <div class="prod-nombre"><?= limpiar($p['nombre']) ?></div>
        <div class="prod-desc"><?= limpiar(substr($p['descripcion'], 0, 90)) ?>...</div>
        <div class="prod-precio"><?= formatoPrecio($p['precio']) ?></div>
        <div class="prod-crypto">≈ <?= number_format($p['precio_crypto'],8) ?> BTC</div>
        <?php if($stock > 5): ?>
            <span class="stock-badge in-stock">✓ En stock (<?= $stock ?>)</span>
        <?php elseif($stock > 0): ?>
            <span class="stock-badge low-stock">⚠ Últimas <?= $stock ?> unidades</span>
        <?php endif; ?>
    </div>
    <div class="prod-actions">
        <?php if($stock > 0): ?>
        <form method="POST" style="flex:1">
            <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
            <?php if($cat): ?><input type="hidden" name="cat" value="<?= limpiar($cat) ?>"><?php endif; ?>
            <button type="submit" name="agregar" class="btn-add">🛒 AGREGAR</button>
        </form>
        <?php else: ?>
        <button class="btn-add" style="background:#333;color:#666;cursor:default" disabled>SIN STOCK</button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
