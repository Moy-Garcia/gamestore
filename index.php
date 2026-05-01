<?php
session_start();
require_once 'config.php';

if (estaLogueado()) {
    header('Location: ' . BASE_URL . '/tienda.php');
    exit;
}

$error = '';
$tab   = $_GET['tab'] ?? 'qr';

// Login con formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = limpiar($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $conn = conectarDB();
        $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email=? AND activo=1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        if ($r && password_verify($pass, $r['password'])) {
            $_SESSION['usuario_id'] = $r['id'];
            $_SESSION['nombre']     = $r['nombre'];
            $_SESSION['email']      = $email;
            $_SESSION['rol']        = $r['rol'];
            // Actualizar último login
            $conn->query("UPDATE usuarios SET ultimo_login=NOW() WHERE id={$r['id']}");
            $conn->close();
            header('Location: ' . BASE_URL . '/tienda.php');
            exit;
        } else {
            $error = '❌ Correo o contraseña incorrectos.';
            $tab   = 'form';
        }
        $conn->close();
    }
}

// Generar token QR
$token = generarToken();
guardarToken($token);
$_SESSION['qr_token'] = $token;

$urlQR = BASE_URL . '/verificar_qr.php?token=' . $token;
$imgQR = generarQR($urlQR, 200);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso | <?= STORE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#050508;--card:#0a0a14;--cyan:#00f5ff;--purple:#bf5af2;
    --green:#39ff14;--text:#e0e0f0;--dim:#7070a0;--border:rgba(0,245,255,0.15);
    --red:#ff2d55;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

body{
    font-family:'Rajdhani',sans-serif;
    background:var(--bg);
    min-height:100vh;
    overflow-x:hidden;
}

/* ===== LAYOUT DESKTOP: dos columnas ===== */
.page-grid{
    display:grid;
    grid-template-columns:1fr 460px;
    min-height:100vh;
}

/* ===== HERO (panel izquierdo) ===== */
.hero{
    background:linear-gradient(135deg,#050508 0%,#0a0a20 50%,#08001a 100%);
    display:flex;flex-direction:column;justify-content:center;
    padding:60px 48px;position:relative;overflow:hidden;
}
.hero::before{
    content:'';position:absolute;inset:0;
    background-image:
        linear-gradient(rgba(0,245,255,0.04) 1px,transparent 1px),
        linear-gradient(90deg,rgba(0,245,255,0.04) 1px,transparent 1px);
    background-size:60px 60px;pointer-events:none;
}
.hero-content{position:relative;z-index:1}

.hero-logo{
    font-family:'Orbitron',monospace;font-size:3.2rem;font-weight:900;
    line-height:1;margin-bottom:20px;
}
.hero-logo .g  {color:var(--cyan);text-shadow:0 0 30px var(--cyan)}
.hero-logo .s  {color:var(--purple);text-shadow:0 0 30px var(--purple)}
.hero-logo .rest{color:white}

.hero-tagline{
    font-size:1.1rem;color:var(--dim);margin-bottom:40px;line-height:1.6;
}
.hero-tagline strong{color:var(--cyan)}

.hero-features{display:flex;flex-direction:column;gap:14px}
.feature{display:flex;align-items:center;gap:12px;color:var(--text);font-size:.95rem}
.feature-icon{
    width:40px;height:40px;
    background:rgba(0,245,255,0.1);border:1px solid var(--border);border-radius:10px;
    display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;
}

.orb{position:absolute;border-radius:50%;filter:blur(80px);pointer-events:none}
.orb1{width:400px;height:400px;background:rgba(0,245,255,.06);top:-100px;right:-100px}
.orb2{width:300px;height:300px;background:rgba(191,90,242,.08);bottom:-50px;left:80px}

/* ===== PANEL LOGIN (panel derecho) ===== */
.login-panel{
    background:var(--card);
    border-left:1px solid var(--border);
    display:flex;flex-direction:column;justify-content:center;
    padding:40px 36px;overflow-y:auto;
}

.login-panel h2{
    font-family:'Orbitron',monospace;font-size:1.3rem;color:var(--cyan);margin-bottom:6px;
}
.login-panel .sub{color:var(--dim);font-size:.88rem;margin-bottom:28px}

/* Tabs */
.tabs{
    display:grid;grid-template-columns:1fr 1fr;gap:6px;
    margin-bottom:28px;
    background:rgba(0,0,0,.3);border:1px solid var(--border);
    border-radius:10px;padding:4px;
}
.tab-btn{
    padding:10px;border:none;border-radius:7px;
    background:transparent;color:var(--dim);
    font-family:'Rajdhani',sans-serif;font-size:.88rem;font-weight:600;
    cursor:pointer;transition:all .2s;letter-spacing:.5px;
}
.tab-btn.active{
    background:linear-gradient(135deg,rgba(0,245,255,.2),rgba(191,90,242,.2));
    color:var(--cyan);border:1px solid var(--border);
}

/* QR Tab */
.qr-tab,.form-tab{display:none}
.qr-tab.active,.form-tab.active{display:block}

.qr-box-wrap{text-align:center}
.qr-frame{
    display:inline-block;background:white;padding:12px;
    border-radius:12px;margin-bottom:16px;position:relative;
}
.qr-frame img{display:block;border-radius:6px}
.qr-overlay{
    display:none;position:absolute;inset:0;
    background:rgba(255,255,255,.92);border-radius:12px;
    flex-direction:column;align-items:center;justify-content:center;gap:8px;
}
.qr-overlay.show{display:flex}

.waiting-bar{
    display:flex;align-items:center;justify-content:center;gap:8px;
    color:var(--dim);font-size:.82rem;margin-bottom:14px;
}
.dot-pulse{
    width:8px;height:8px;border-radius:50%;background:var(--green);
    animation:pulse 1.5s infinite;display:inline-block;
}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.3;transform:scale(.7)}}

.qr-steps{text-align:left;display:flex;flex-direction:column;gap:8px}
.qr-step{display:flex;align-items:center;gap:10px;color:var(--dim);font-size:.85rem}
.qr-step .num{
    width:22px;height:22px;border-radius:50%;
    background:rgba(0,245,255,.1);border:1px solid var(--border);
    font-size:.72rem;font-family:'Orbitron',monospace;
    display:flex;align-items:center;justify-content:center;color:var(--cyan);flex-shrink:0;
}

.btn-renovar{
    display:none;width:100%;margin-top:12px;padding:10px;
    background:rgba(0,245,255,.1);border:1px solid var(--border);
    border-radius:8px;color:var(--cyan);font-family:'Rajdhani',sans-serif;
    font-weight:600;cursor:pointer;text-align:center;font-size:.88rem;
    text-decoration:none;
}

/* Formulario */
.alert-err{
    background:rgba(255,45,85,.1);border:1px solid rgba(255,45,85,.3);
    color:#ff6b6b;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.88rem;
}

label{
    display:block;font-size:.78rem;font-weight:600;letter-spacing:1px;
    text-transform:uppercase;color:var(--dim);margin-bottom:6px;
}
input[type=email],input[type=password],input[type=text]{
    width:100%;padding:12px 14px;
    background:rgba(0,0,0,.4);border:1px solid var(--border);border-radius:8px;
    color:var(--text);font-family:'Rajdhani',sans-serif;font-size:.95rem;
    outline:none;margin-bottom:14px;transition:border-color .2s;
}
input:focus{border-color:var(--cyan)}

.btn-full{
    width:100%;padding:14px;border:none;border-radius:8px;
    font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:700;
    letter-spacing:1px;cursor:pointer;
    background:linear-gradient(135deg,var(--cyan),var(--purple));color:#000;transition:.25s;
}
.btn-full:hover{opacity:.9;transform:translateY(-1px)}

.divider{
    text-align:center;color:var(--dim);font-size:.8rem;margin:16px 0;position:relative;
}
.divider::before,.divider::after{
    content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--border);
}
.divider::before{left:0}.divider::after{right:0}

.link-reg{text-align:center;color:var(--dim);font-size:.85rem}
.link-reg a{color:var(--cyan);text-decoration:none}

#success-overlay{display:none;text-align:center;padding:20px 0}
#success-overlay .check{font-size:4rem}
#success-overlay h3{color:var(--green);font-family:'Orbitron',monospace;margin:12px 0 8px;font-size:1rem}
#success-overlay p{color:var(--dim);font-size:.88rem}

/* ===== RESPONSIVE MOBILE ===== */
@media(max-width:768px){
    /* En mobile: una sola columna, hero arriba compacto */
    .page-grid{
        grid-template-columns:1fr;
        grid-template-rows:auto auto;
    }

    .hero{
        padding:32px 24px 28px;
        justify-content:flex-start;
    }
    .hero-logo{font-size:2.2rem;margin-bottom:12px}
    .hero-tagline{font-size:.95rem;margin-bottom:20px}
    .hero-features{gap:10px}
    .feature{font-size:.88rem}
    .feature-icon{width:34px;height:34px;font-size:1rem}

    .orb1,.orb2{display:none}

    .login-panel{
        border-left:none;border-top:1px solid var(--border);
        padding:28px 20px;
        justify-content:flex-start;
    }

    .login-panel h2{font-size:1.1rem}

    .qr-frame img{width:180px;height:180px}
}

@media(max-width:420px){
    .hero{padding:24px 16px}
    .hero-logo{font-size:1.8rem}
    .login-panel{padding:24px 16px}
    .qr-frame img{width:160px;height:160px}
}
</style>
</head>
<body>

<div class="page-grid">

    <!-- ===== HERO ===== -->
    <div class="hero">
        <div class="orb orb1"></div>
        <div class="orb orb2"></div>
        <div class="hero-content">
            <div class="hero-logo">
                <span class="g">G</span><span class="rest">ame</span><span class="s">Store</span>
            </div>
            <p class="hero-tagline">
                La mejor tienda de gaming en México.<br>
                Paga con <strong>crypto, tarjeta o PayPal</strong>.
            </p>
            <div class="hero-features">
                <div class="feature"><div class="feature-icon">🎮</div><div>+500 productos gaming al mejor precio</div></div>
                <div class="feature"><div class="feature-icon">₿</div><div>Acepta Bitcoin, Ethereum, Solana y TRX</div></div>
                <div class="feature"><div class="feature-icon">🔐</div><div>Login seguro con código QR personalizado</div></div>
                <div class="feature"><div class="feature-icon">💎</div><div>Cartera digital integrada con historial</div></div>
            </div>
        </div>
    </div>

    <!-- ===== PANEL LOGIN ===== -->
    <div class="login-panel">
        <h2>ACCEDER AL SISTEMA</h2>
        <p class="sub">Elige cómo quieres entrar</p>

        <?php if($error): ?>
        <div class="alert-err"><?= $error ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $tab==='qr'?'active':'' ?>" onclick="switchTab('qr')">📱 Código QR</button>
            <button class="tab-btn <?= $tab==='form'?'active':'' ?>" onclick="switchTab('form')">🔑 Usuario</button>
        </div>

        <!-- QR -->
        <div class="qr-tab <?= $tab==='qr'?'active':'' ?>" id="tab-qr">
            <div class="qr-box-wrap" id="qr-main">
                <div class="qr-frame">
                    <img src="<?= $imgQR ?>" alt="QR de acceso" width="200" height="200" id="qr-img">
                    <div class="qr-overlay" id="qr-overlay">
                        <span style="font-size:2rem">⏳</span>
                        <span style="font-size:.82rem;color:#333;font-weight:600" id="overlay-txt">QR expirado</span>
                    </div>
                </div>
                <div class="waiting-bar">
                    <div class="dot-pulse"></div>
                    <span id="estado-txt">Esperando escaneo...</span>
                </div>
                <div class="qr-steps">
                    <div class="qr-step"><div class="num">1</div>Abre la cámara de tu celular</div>
                    <div class="qr-step"><div class="num">2</div>Apunta al código QR</div>
                    <div class="qr-step"><div class="num">3</div>Toca el enlace y confirma el acceso</div>
                </div>
                <a href="index.php" class="btn-renovar" id="btn-renovar">🔄 Generar nuevo QR</a>
            </div>
            <div id="success-overlay">
                <div class="check">✅</div>
                <h3>¡ACCESO CONCEDIDO!</h3>
                <p>Redirigiendo a la tienda...</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="form-tab <?= $tab==='form'?'active':'' ?>" id="tab-form">
            <form method="POST" action="index.php?tab=form">
                <div>
                    <label>Correo electrónico</label>
                    <input type="email" name="email" placeholder="tu@correo.com" required
                           value="<?= limpiar($_POST['email']??'') ?>">
                </div>
                <div>
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-full">ENTRAR AL SISTEMA</button>
            </form>
            <div class="divider">o</div>
            <div class="link-reg">
                ¿No tienes cuenta? <a href="registro.php">Crear cuenta gratis</a>
            </div>
        </div>
    </div>

</div><!-- /page-grid -->

<script>
const TOKEN    = "<?= $token ?>";
const EXPIRY   = <?= TOKEN_EXPIRY * 1000 ?>;
let polling, expirado = false;

function switchTab(t) {
    document.getElementById('tab-qr').classList.toggle('active',   t==='qr');
    document.getElementById('tab-form').classList.toggle('active',  t==='form');
    document.querySelectorAll('.tab-btn').forEach((b,i) =>
        b.classList.toggle('active', (i===0&&t==='qr')||(i===1&&t==='form'))
    );
}

async function checkStatus() {
    if (expirado) return;
    try {
        const r = await fetch('check_qr.php?token=' + TOKEN);
        const d = await r.json();
        if (d.status === 'aprobado') {
            clearInterval(polling);
            document.getElementById('qr-main').style.display = 'none';
            document.getElementById('success-overlay').style.display = 'block';
            setTimeout(() => window.location.href = 'tienda.php', 1500);
        } else if (d.status === 'expirado' || d.status === 'error') {
            clearInterval(polling);
            marcarExpirado();
        }
    } catch(e) {}
}

function marcarExpirado() {
    expirado = true;
    document.getElementById('qr-overlay').classList.add('show');
    document.getElementById('overlay-txt').textContent  = 'QR expirado';
    document.getElementById('estado-txt').textContent   = '⚠️ Expirado';
    document.getElementById('btn-renovar').style.display = 'block';
}

polling = setInterval(checkStatus, 2000);
setTimeout(marcarExpirado, EXPIRY);
checkStatus();
</script>
</body>
</html>
