<?php
// verificar_qr.php - Página que abre el celular al escanear QR
session_start();
require_once 'config.php';

$token = $_GET['token'] ?? '';
$tipo  = 'error';
$msg   = '';

if (!$token || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    $tipo = 'error'; $msg = 'Token inválido.';
} else {
    $datos = leerToken($token);
    if (!$datos) {
        $tipo = 'error'; $msg = 'QR no encontrado o ya usado.';
    } elseif (time() - $datos['created'] > TOKEN_EXPIRY) {
        $datos['status'] = 'expirado';
        actualizarToken($token, $datos);
        $tipo = 'expirado'; $msg = 'El QR expiró. Genera uno nuevo.';
    } elseif ($datos['status'] === 'aprobado') {
        $tipo = 'ya_usado'; $msg = 'Ya confirmaste el acceso. Continúa en tu computadora.';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
            $datos['status'] = 'aprobado';
            $datos['ts']     = time();
            actualizarToken($token, $datos);
            $tipo = 'exito'; $msg = '¡Acceso confirmado! Ya puedes continuar en tu navegador.';
        } else {
            $tipo = 'pendiente';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmar acceso | <?= STORE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Rajdhani',sans-serif;background:linear-gradient(135deg,#050508,#0a0a20);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#0f0f1a;border:1px solid rgba(0,245,255,.15);border-radius:24px;padding:40px 32px;max-width:360px;width:100%;text-align:center}
.icon{font-size:4rem;margin-bottom:20px}
h2{font-family:'Orbitron',monospace;font-size:1.2rem;color:#00f5ff;margin-bottom:12px}
p{color:#7070a0;font-size:.95rem;line-height:1.6;margin-bottom:24px}
.btn{display:block;width:100%;padding:16px;border:none;border-radius:12px;background:linear-gradient(135deg,#00f5ff,#bf5af2);color:#000;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:700;cursor:pointer;transition:.2s}
.btn:hover{opacity:.9;transform:translateY(-2px)}
.ok{background:rgba(57,255,20,.1);border:1px solid rgba(57,255,20,.3);color:#39ff14;padding:14px;border-radius:10px}
.err{background:rgba(255,45,85,.1);border:1px solid rgba(255,45,85,.3);color:#ff6b6b;padding:14px;border-radius:10px}
.logo{font-family:'Orbitron',monospace;font-size:1rem;color:#00f5ff;margin-bottom:32px;opacity:.6}
.nota{font-size:.78rem;color:#444;margin-top:20px}
</style>
</head>
<body>
<div class="card">
<div class="logo">⚡ <?= STORE_NAME ?></div>

<?php if($tipo==='pendiente'): ?>
<div class="icon">🔐</div>
<h2>CONFIRMAR ACCESO</h2>
<p>Se está intentando iniciar sesión en <strong style="color:#e0e0f0"><?= STORE_NAME ?></strong>.<br>Si fuiste tú, confirma el acceso.</p>
<form method="POST" action="verificar_qr.php?token=<?= htmlspecialchars($token) ?>">
    <button type="submit" name="confirmar" class="btn">✅ SÍ, CONFIRMAR ACCESO</button>
</form>
<p class="nota">🔒 Si no intentaste entrar, ignora esta página.</p>

<?php elseif($tipo==='exito'): ?>
<div class="icon">🎉</div>
<h2>¡LISTO!</h2>
<div class="ok"><?= $msg ?></div>
<p class="nota" style="margin-top:16px">Puedes cerrar esta ventana.</p>

<?php elseif($tipo==='expirado'): ?>
<div class="icon">⏳</div>
<h2>QR EXPIRADO</h2>
<div class="err"><?= $msg ?></div>

<?php elseif($tipo==='ya_usado'): ?>
<div class="icon">✔️</div>
<h2>YA CONFIRMADO</h2>
<div class="ok"><?= $msg ?></div>

<?php else: ?>
<div class="icon">❌</div>
<h2>ERROR</h2>
<div class="err"><?= $msg ?></div>
<?php endif; ?>
</div>
</body>
</html>
