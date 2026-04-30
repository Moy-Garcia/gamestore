<?php
// header.php - Cabecera global de GameStore
if (!defined('BASE_URL')) { require_once __DIR__ . '/config.php'; }
$carrito_count = estaLogueado() ? contarCarrito($_SESSION['usuario_id']) : 0;
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? limpiar($page_title) . ' | ' : '' ?><?= STORE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES CYBERPUNK ===== */
        :root {
            --bg-void:    #050508;
            --bg-dark:    #0a0a12;
            --bg-card:    #0f0f1a;
            --bg-hover:   #16162a;
            --cyan:       #00f5ff;
            --purple:     #bf5af2;
            --green:      #39ff14;
            --orange:     #ff6b00;
            --red:        #ff2d55;
            --text:       #e0e0f0;
            --text-dim:   #7070a0;
            --border:     rgba(0,245,255,0.15);
            --glow-cyan:  0 0 20px rgba(0,245,255,0.4);
            --glow-purple:0 0 20px rgba(191,90,242,0.4);
        }

        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-void);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Fondo con grid cyberpunk */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,245,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,245,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        body > * { position: relative; z-index: 1; }

        /* ===== NAVBAR ===== */
        .navbar {
            background: rgba(5,5,8,0.95);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0 24px;
        }

        .nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            height: 64px;
            gap: 32px;
        }

        .nav-logo {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 1.3rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .nav-logo span.g { color: var(--cyan); }
        .nav-logo span.s { color: var(--purple); }
        .nav-logo span.rest { color: var(--text); }

        .nav-links {
            display: flex;
            gap: 4px;
            flex: 1;
        }

        .nav-links a {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .nav-links a:hover,
        .nav-links a.activo {
            color: var(--cyan);
            border-color: var(--border);
            background: rgba(0,245,255,0.05);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .btn-carrito {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
            text-decoration: none;
            background: rgba(0,245,255,0.08);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: 'Rajdhani', sans-serif;
            transition: all 0.2s;
        }

        .btn-carrito:hover { background: rgba(0,245,255,0.15); color: var(--cyan); }

        .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: var(--red);
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
        }

        .btn-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-nav.login {
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            color: #000;
        }

        .btn-nav.logout {
            background: rgba(255,45,85,0.15);
            color: var(--red);
            border: 1px solid rgba(255,45,85,0.3);
        }

        .btn-nav.dashboard {
            background: rgba(191,90,242,0.15);
            color: var(--purple);
            border: 1px solid rgba(191,90,242,0.3);
        }

        .btn-nav:hover { filter: brightness(1.2); transform: translateY(-1px); }

        /* ===== LAYOUT PRINCIPAL ===== */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        /* ===== COMPONENTES GLOBALES ===== */

        /* Botón primario */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.25s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--cyan), #0090ff);
            color: #000;
            box-shadow: 0 4px 15px rgba(0,245,255,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,245,255,0.5);
        }

        .btn-purple {
            background: linear-gradient(135deg, var(--purple), #6e2db8);
            color: #fff;
            box-shadow: 0 4px 15px rgba(191,90,242,0.3);
        }

        .btn-purple:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(191,90,242,0.5);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-outline:hover { border-color: var(--cyan); color: var(--cyan); }

        .btn-green {
            background: linear-gradient(135deg, var(--green), #00aa00);
            color: #000;
            box-shadow: 0 4px 15px rgba(57,255,20,0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--red), #aa0000);
            color: #fff;
        }

        /* Card */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .card:hover { border-color: rgba(0,245,255,0.3); }

        /* Título de sección */
        .section-title {
            font-family: 'Orbitron', monospace;
            font-size: 1.5rem;
            color: var(--cyan);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--border), transparent);
        }

        /* Inputs */
        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(0,245,255,0.1);
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .alert-error   { background: rgba(255,45,85,0.1);  border: 1px solid rgba(255,45,85,0.3);  color: #ff6b6b; }
        .alert-success { background: rgba(57,255,20,0.1);  border: 1px solid rgba(57,255,20,0.3);  color: var(--green); }
        .alert-info    { background: rgba(0,245,255,0.1);  border: 1px solid rgba(0,245,255,0.3);  color: var(--cyan); }
        .alert-warning { background: rgba(255,107,0,0.1);  border: 1px solid rgba(255,107,0,0.3);  color: var(--orange); }

        /* Badge categoría */
        .badge-cat {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Escanlines effect */
        .scanlines::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,0.05) 2px,
                rgba(0,0,0,0.05) 4px
            );
            pointer-events: none;
        }

        /* Neon border animado */
        @keyframes neon-pulse {
            0%, 100% { box-shadow: 0 0 5px var(--cyan), 0 0 10px var(--cyan); }
            50%       { box-shadow: 0 0 20px var(--cyan), 0 0 40px var(--cyan); }
        }

        /* Animación entrada */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-up { animation: fadeUp 0.4s ease forwards; }

        /* Loader */
        .spinner {
            display: inline-block;
            width: 20px; height: 20px;
            border: 2px solid rgba(0,245,255,0.2);
            border-top-color: var(--cyan);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ===== FOOTER ===== */
        footer {
            text-align: center;
            padding: 40px 24px;
            border-top: 1px solid var(--border);
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-top: 80px;
        }

        footer span { color: var(--cyan); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
            <span class="g">G</span><span class="rest">ame</span><span class="s">Store</span>
        </a>

        <div class="nav-links">
            <a href="<?= BASE_URL ?>/tienda.php" class="<?= $pagina_actual==='tienda.php'?'activo':'' ?>">🎮 Tienda</a>
            <?php if(estaLogueado()): ?>
            <a href="<?= BASE_URL ?>/pedidos.php" class="<?= $pagina_actual==='pedidos.php'?'activo':'' ?>">📦 Pedidos</a>
            <a href="<?= BASE_URL ?>/cartera.php" class="<?= $pagina_actual==='cartera.php'?'activo':'' ?>">💎 Cartera</a>
            <?php if(esAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/index.php">⚙️ Admin</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <?php if(estaLogueado()): ?>
                <a href="<?= BASE_URL ?>/carrito.php" class="btn-carrito">
                    🛒 Carrito
                    <?php if($carrito_count > 0): ?>
                    <span class="badge"><?= $carrito_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>/dashboard.php" class="btn-nav dashboard">👤 <?= limpiar($_SESSION['nombre'] ?? 'Mi cuenta') ?></a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn-nav logout">Salir</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/index.php" class="btn-nav login">🔐 Ingresar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="main-content">
