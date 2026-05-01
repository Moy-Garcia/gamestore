<?php
if (!defined('BASE_URL')) { require_once __DIR__ . '/config.php'; }
$carrito_count = estaLogueado() ? contarCarrito($_SESSION['usuario_id']) : 0;
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? limpiar($page_title).' | ' : '' ?><?= STORE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root{
        --bg-void:#050508;--bg-dark:#0a0a12;--bg-card:#0f0f1a;--bg-hover:#16162a;
        --cyan:#00f5ff;--purple:#bf5af2;--green:#39ff14;--orange:#ff6b00;--red:#ff2d55;
        --text:#e0e0f0;--text-dim:#7070a0;--border:rgba(0,245,255,0.15);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

    body{
        font-family:'Rajdhani',sans-serif;
        background:var(--bg-void);color:var(--text);min-height:100vh;overflow-x:hidden;
    }
    body::before{
        content:'';position:fixed;inset:0;
        background-image:
            linear-gradient(rgba(0,245,255,.03) 1px,transparent 1px),
            linear-gradient(90deg,rgba(0,245,255,.03) 1px,transparent 1px);
        background-size:50px 50px;pointer-events:none;z-index:0;
    }
    body>*{position:relative;z-index:1}

    /* ===== NAVBAR ===== */
    .navbar{
        background:rgba(5,5,8,.96);border-bottom:1px solid var(--border);
        backdrop-filter:blur(20px);position:sticky;top:0;z-index:1000;padding:0 20px;
    }
    .nav-inner{
        max-width:1400px;margin:0 auto;display:flex;align-items:center;
        height:60px;gap:20px;
    }
    .nav-logo{
        font-family:'Orbitron',monospace;font-weight:900;font-size:1.2rem;
        text-decoration:none;display:flex;align-items:center;gap:4px;flex-shrink:0;
    }
    .nav-logo .g{color:var(--cyan)}.nav-logo .s{color:var(--purple)}.nav-logo .rest{color:var(--text)}

    /* Links desktop */
    .nav-links{display:flex;gap:2px;flex:1}
    .nav-links a{
        color:var(--text-dim);text-decoration:none;font-size:.85rem;font-weight:600;
        letter-spacing:.8px;text-transform:uppercase;padding:7px 12px;border-radius:6px;
        transition:all .2s;border:1px solid transparent;white-space:nowrap;
    }
    .nav-links a:hover,.nav-links a.activo{
        color:var(--cyan);border-color:var(--border);background:rgba(0,245,255,.05);
    }

    /* Nav right */
    .nav-right{display:flex;align-items:center;gap:8px;flex-shrink:0}

    .btn-carrito{
        position:relative;display:flex;align-items:center;gap:6px;color:var(--text);
        text-decoration:none;background:rgba(0,245,255,.08);border:1px solid var(--border);
        border-radius:8px;padding:7px 12px;font-size:.82rem;font-weight:600;
        font-family:'Rajdhani',sans-serif;transition:all .2s;white-space:nowrap;
    }
    .btn-carrito:hover{background:rgba(0,245,255,.15);color:var(--cyan)}
    .badge{
        position:absolute;top:-6px;right:-6px;background:var(--red);color:white;
        width:18px;height:18px;border-radius:50%;font-size:.68rem;
        display:flex;align-items:center;justify-content:center;font-family:'Orbitron',monospace;
    }

    .btn-nav{
        display:flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;
        font-family:'Rajdhani',sans-serif;font-size:.82rem;font-weight:600;
        letter-spacing:.4px;text-decoration:none;border:none;cursor:pointer;transition:all .2s;
        white-space:nowrap;
    }
    .btn-nav.login{background:linear-gradient(135deg,var(--cyan),var(--purple));color:#000}
    .btn-nav.logout{background:rgba(255,45,85,.15);color:var(--red);border:1px solid rgba(255,45,85,.3)}
    .btn-nav.dashboard{background:rgba(191,90,242,.15);color:var(--purple);border:1px solid rgba(191,90,242,.3)}
    .btn-nav:hover{filter:brightness(1.15);transform:translateY(-1px)}

    /* ===== HAMBURGER MOBILE ===== */
    .hamburger{
        display:none;flex-direction:column;justify-content:center;
        gap:5px;background:transparent;border:none;cursor:pointer;
        padding:4px;margin-left:auto;
    }
    .hamburger span{
        display:block;width:24px;height:2px;background:var(--text);
        border-radius:2px;transition:all .3s;
    }
    .hamburger.open span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
    .hamburger.open span:nth-child(2){opacity:0;transform:translateX(-10px)}
    .hamburger.open span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}

    /* Menú móvil desplegable */
    .mobile-menu{
        display:none;position:fixed;top:60px;left:0;right:0;
        background:rgba(10,10,18,.98);border-bottom:1px solid var(--border);
        backdrop-filter:blur(20px);padding:16px 20px;z-index:999;
        flex-direction:column;gap:8px;
    }
    .mobile-menu.open{display:flex}
    .mobile-menu a,.mobile-menu button{
        color:var(--text);text-decoration:none;font-size:1rem;font-weight:600;
        padding:12px 16px;border-radius:10px;border:1px solid transparent;
        background:transparent;cursor:pointer;font-family:'Rajdhani',sans-serif;
        text-align:left;transition:all .2s;display:flex;align-items:center;gap:8px;
        width:100%;letter-spacing:.5px;
    }
    .mobile-menu a:hover,.mobile-menu a.activo{
        background:rgba(0,245,255,.08);border-color:var(--border);color:var(--cyan);
    }
    .mobile-menu .m-carrito{
        background:rgba(0,245,255,.08);border-color:var(--border);color:var(--cyan);
    }
    .mobile-menu .m-logout{color:var(--red)}
    .mobile-menu .m-dashboard{color:var(--purple)}

    /* ===== MAIN CONTENT ===== */
    .main-content{max-width:1400px;margin:0 auto;padding:32px 20px}

    /* ===== COMPONENTES GLOBALES ===== */
    .btn{
        display:inline-flex;align-items:center;justify-content:center;gap:8px;
        padding:11px 22px;border-radius:8px;font-family:'Rajdhani',sans-serif;
        font-size:.92rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;
        text-decoration:none;border:none;cursor:pointer;transition:all .25s;
    }
    .btn-primary{
        background:linear-gradient(135deg,var(--cyan),#0090ff);color:#000;
        box-shadow:0 4px 15px rgba(0,245,255,.3);
    }
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,245,255,.5)}
    .btn-purple{
        background:linear-gradient(135deg,var(--purple),#6e2db8);color:#fff;
        box-shadow:0 4px 15px rgba(191,90,242,.3);
    }
    .btn-purple:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(191,90,242,.5)}
    .btn-outline{background:transparent;border:1px solid var(--border);color:var(--text)}
    .btn-outline:hover{border-color:var(--cyan);color:var(--cyan)}
    .btn-green{background:linear-gradient(135deg,var(--green),#00aa00);color:#000;box-shadow:0 4px 15px rgba(57,255,20,.3)}
    .btn-danger{background:linear-gradient(135deg,var(--red),#aa0000);color:#fff}

    .card{
        background:var(--bg-card);border:1px solid var(--border);border-radius:16px;
        padding:24px;transition:border-color .2s;
    }
    .card:hover{border-color:rgba(0,245,255,.3)}

    .section-title{
        font-family:'Orbitron',monospace;font-size:1.4rem;color:var(--cyan);
        margin-bottom:24px;display:flex;align-items:center;gap:12px;
    }
    .section-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent)}

    .form-group{margin-bottom:18px}
    .form-group label{
        display:block;font-size:.82rem;font-weight:600;letter-spacing:1px;
        text-transform:uppercase;color:var(--text-dim);margin-bottom:7px;
    }
    .form-control{
        width:100%;padding:11px 14px;background:var(--bg-dark);border:1px solid var(--border);
        border-radius:8px;color:var(--text);font-family:'Rajdhani',sans-serif;font-size:.95rem;
        transition:border-color .2s,box-shadow .2s;outline:none;
    }
    .form-control:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,245,255,.1)}

    .alert{padding:11px 15px;border-radius:8px;margin-bottom:18px;font-weight:500;font-size:.92rem}
    .alert-error  {background:rgba(255,45,85,.1);border:1px solid rgba(255,45,85,.3);color:#ff6b6b}
    .alert-success{background:rgba(57,255,20,.1);border:1px solid rgba(57,255,20,.3);color:var(--green)}
    .alert-info   {background:rgba(0,245,255,.1);border:1px solid rgba(0,245,255,.3);color:var(--cyan)}
    .alert-warning{background:rgba(255,107,0,.1);border:1px solid rgba(255,107,0,.3);color:var(--orange)}

    @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .fade-up{animation:fadeUp .4s ease forwards}

    footer{
        text-align:center;padding:36px 20px;border-top:1px solid var(--border);
        color:var(--text-dim);font-size:.82rem;margin-top:80px;
    }
    footer span{color:var(--cyan)}

    /* ===== RESPONSIVE general ===== */
    @media(max-width:900px){
        .nav-links{display:none}
        .nav-right{display:none}
        .hamburger{display:flex}
        .nav-inner{gap:12px}
    }
    @media(max-width:480px){
        .main-content{padding:24px 14px}
        .section-title{font-size:1.1rem}
        .card{padding:16px}
        footer{margin-top:48px}
    }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
            <span class="g">G</span><span class="rest">ame</span><span class="s">Store</span>
        </a>

        <!-- Links desktop -->
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/tienda.php"  class="<?= $pagina_actual==='tienda.php'?'activo':'' ?>">🎮 Tienda</a>
            <?php if(estaLogueado()): ?>
            <a href="<?= BASE_URL ?>/pedidos.php" class="<?= $pagina_actual==='pedidos.php'?'activo':'' ?>">📦 Pedidos</a>
            <a href="<?= BASE_URL ?>/cartera.php" class="<?= $pagina_actual==='cartera.php'?'activo':'' ?>">💎 Cartera</a>
            <?php if(esAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/index.php">⚙️ Admin</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Botones desktop -->
        <div class="nav-right">
            <?php if(estaLogueado()): ?>
            <a href="<?= BASE_URL ?>/carrito.php" class="btn-carrito">
                🛒 Carrito
                <?php if($carrito_count>0): ?><span class="badge"><?= $carrito_count ?></span><?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn-nav dashboard">
                👤 <?= limpiar(explode(' ', $_SESSION['nombre'] ?? 'Mi cuenta')[0]) ?>
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn-nav logout">Salir</a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php" class="btn-nav login">🔐 Ingresar</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger mobile -->
        <button class="hamburger" id="hamburger" onclick="toggleMenu()" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Menú móvil -->
<div class="mobile-menu" id="mobile-menu">
    <a href="<?= BASE_URL ?>/tienda.php"  class="<?= $pagina_actual==='tienda.php'?'activo':'' ?>">🎮 Tienda</a>
    <?php if(estaLogueado()): ?>
    <a href="<?= BASE_URL ?>/carrito.php" class="m-carrito">
        🛒 Carrito <?php if($carrito_count>0): ?>(<?= $carrito_count ?>)<?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/pedidos.php">📦 Mis pedidos</a>
    <a href="<?= BASE_URL ?>/cartera.php">💎 Mi cartera</a>
    <a href="<?= BASE_URL ?>/dashboard.php" class="m-dashboard">👤 <?= limpiar($_SESSION['nombre'] ?? 'Mi cuenta') ?></a>
    <?php if(esAdmin()): ?>
    <a href="<?= BASE_URL ?>/admin/index.php">⚙️ Admin</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/logout.php" class="m-logout">🚪 Cerrar sesión</a>
    <?php else: ?>
    <a href="<?= BASE_URL ?>/index.php">🔐 Iniciar sesión</a>
    <a href="<?= BASE_URL ?>/registro.php">⚡ Crear cuenta</a>
    <?php endif; ?>
</div>

<div class="main-content">

<script>
function toggleMenu() {
    const menu = document.getElementById('mobile-menu');
    const ham  = document.getElementById('hamburger');
    menu.classList.toggle('open');
    ham.classList.toggle('open');
}
// Cerrar menú al hacer clic fuera
document.addEventListener('click', e => {
    const menu = document.getElementById('mobile-menu');
    const ham  = document.getElementById('hamburger');
    if (!menu.contains(e.target) && !ham.contains(e.target)) {
        menu.classList.remove('open');
        ham.classList.remove('open');
    }
});
</script>
