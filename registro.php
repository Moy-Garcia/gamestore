<?php
session_start();
require_once 'config.php';

if (estaLogueado()) { header('Location: ' . BASE_URL . '/tienda.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_s  = limpiar($_POST['nombre_s']     ?? '');
    $ap_pat    = limpiar($_POST['ap_paterno']    ?? '');
    $ap_mat    = limpiar($_POST['ap_materno']    ?? '');
    $num_cuenta= limpiar($_POST['num_cuenta']    ?? '');
    $email     = limpiar($_POST['email']         ?? '');
    $pass      = $_POST['password']              ?? '';
    $pass2     = $_POST['password2']             ?? '';

    // Nombre completo que se guarda en BD
    $nombre_completo = trim("$nombre_s $ap_pat $ap_mat");

    if (!$nombre_s || !$ap_pat || !$email || !$pass) {
        $error = 'Los campos marcados con * son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no tiene un formato válido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($num_cuenta && !preg_match('/^\d{8,20}$/', $num_cuenta)) {
        $error = 'El número de cuenta debe tener entre 8 y 20 dígitos.';
    } else {
        $conn = conectarDB();
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Este correo ya está registrado. ¿Olvidaste tu contraseña?';
        } else {
            $hash   = password_hash($pass, PASSWORD_DEFAULT);
            $wallet = '0x' . bin2hex(random_bytes(20));

            // Guardamos nombre completo + número de cuenta en el campo nombre
            // (num_cuenta como referencia en wallet_address si no hay wallet real)
            $nombre_bd = $nombre_completo;

            $stmt2 = $conn->prepare(
                "INSERT INTO usuarios (nombre, email, password, wallet_address) VALUES (?,?,?,?)"
            );
            $stmt2->bind_param('ssss', $nombre_bd, $email, $hash, $wallet);

            if ($stmt2->execute()) {
                $id = $conn->insert_id;
                $_SESSION['usuario_id'] = $id;
                $_SESSION['nombre']     = $nombre_bd;
                $_SESSION['email']      = $email;
                $_SESSION['rol']        = 'cliente';

                // Guardar número de cuenta en sesión si existe
                if ($num_cuenta) {
                    $_SESSION['num_cuenta'] = $num_cuenta;
                }

                $conn->close();
                header('Location: ' . BASE_URL . '/tienda.php');
                exit;
            } else {
                $error = 'Error al registrar. Por favor intenta de nuevo.';
            }
        }
        $conn->close();
    }
}

$page_title = 'Crear cuenta';
include 'header.php';
?>
    <style>
        .registro-wrap{max-width:560px;margin:0 auto;padding:20px 0}
        .registro-wrap h1{font-family:'Orbitron',monospace;font-size:1.5rem;color:var(--cyan);margin-bottom:6px}
        .registro-wrap .sub{color:var(--text-dim);margin-bottom:28px;font-size:.95rem}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-grid .full{grid-column:1/-1}
        .campo-req{color:var(--red);margin-left:2px}
        .hint{font-size:.75rem;color:var(--text-dim);margin-top:4px}
        .divider-reg{text-align:center;color:var(--text-dim);font-size:.82rem;margin:20px 0;position:relative}
        .divider-reg::before,.divider-reg::after{content:'';position:absolute;top:50%;width:44%;height:1px;background:var(--border)}
        .divider-reg::before{left:0}.divider-reg::after{right:0}
        .strength-bar{height:3px;background:rgba(255,255,255,.1);border-radius:2px;margin-top:6px;overflow:hidden}
        .strength-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s}
        .strength-txt{font-size:.72rem;color:var(--text-dim);margin-top:3px}
        @media(max-width:500px){.form-grid{grid-template-columns:1fr}}
    </style>

    <div class="registro-wrap fade-up">
        <h1>⚡ CREAR CUENTA</h1>
        <p class="sub">Únete y accede a los mejores precios gaming de México</p>

        <?php if($error): ?>
            <div class="alert alert-error"><?= limpiar($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" autocomplete="off">

                <!-- DATOS PERSONALES -->
                <h3 style="font-family:'Orbitron',monospace;font-size:.85rem;color:var(--purple);margin-bottom:16px;letter-spacing:1px">
                    👤 DATOS PERSONALES
                </h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre(s) <span class="campo-req">*</span></label>
                        <input type="text" name="nombre_s" class="form-control"
                               placeholder="Ej. Juan Carlos"
                               value="<?= limpiar($_POST['nombre_s']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido paterno <span class="campo-req">*</span></label>
                        <input type="text" name="ap_paterno" class="form-control"
                               placeholder="Ej. García"
                               value="<?= limpiar($_POST['ap_paterno']??'') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido materno</label>
                        <input type="text" name="ap_materno" class="form-control"
                               placeholder="Ej. López"
                               value="<?= limpiar($_POST['ap_materno']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Número de cuenta</label>
                        <input type="text" name="num_cuenta" class="form-control"
                               placeholder="Ej. 20251234"
                               value="<?= limpiar($_POST['num_cuenta']??'') ?>"
                               inputmode="numeric" maxlength="20">
                        <div class="hint">8 a 20 dígitos · opcional</div>
                    </div>
                </div>

                <div style="border-top:1px solid var(--border);margin:20px 0"></div>

                <!-- ACCESO -->
                <h3 style="font-family:'Orbitron',monospace;font-size:.85rem;color:var(--purple);margin-bottom:16px;letter-spacing:1px">
                    🔐 DATOS DE ACCESO
                </h3>

                <div class="form-group full">
                    <label>Correo electrónico <span class="campo-req">*</span></label>
                    <input type="email" name="email" class="form-control"
                           placeholder="tu@correo.com"
                           value="<?= limpiar($_POST['email']??'') ?>" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Contraseña <span class="campo-req">*</span></label>
                        <input type="password" name="password" id="pass-input" class="form-control"
                               placeholder="Mínimo 6 caracteres" required
                               oninput="medirFuerza(this.value)">
                        <div class="strength-bar"><div class="strength-fill" id="s-fill"></div></div>
                        <div class="strength-txt" id="s-txt"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirmar contraseña <span class="campo-req">*</span></label>
                        <input type="password" name="password2" id="pass2-input" class="form-control"
                               placeholder="Repite tu contraseña" required
                               oninput="verificarMatch()">
                        <div class="hint" id="match-txt"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;margin-top:8px">
                    ⚡ CREAR MI CUENTA
                </button>
            </form>
        </div>

        <p style="text-align:center;margin-top:20px;color:var(--text-dim);font-size:.9rem">
            ¿Ya tienes cuenta? <a href="index.php" style="color:var(--cyan)">Iniciar sesión</a>
        </p>
    </div>

    <script>
        function medirFuerza(v) {
            const fill = document.getElementById('s-fill');
            const txt  = document.getElementById('s-txt');
            let score  = 0;
            if (v.length >= 6)  score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const niveles = [
                {w:'0%',  c:'transparent', t:''},
                {w:'25%', c:'#ff2d55',     t:'Muy débil'},
                {w:'50%', c:'#ff6b00',     t:'Débil'},
                {w:'75%', c:'#f7c948',     t:'Aceptable'},
                {w:'90%', c:'#39ff14',     t:'Fuerte'},
                {w:'100%',c:'#00f5ff',     t:'Muy fuerte'},
            ];
            const n = niveles[Math.min(score, 5)];
            fill.style.width      = n.w;
            fill.style.background = n.c;
            txt.textContent       = n.t;
            txt.style.color       = n.c;
        }

        function verificarMatch() {
            const p1  = document.getElementById('pass-input').value;
            const p2  = document.getElementById('pass2-input').value;
            const txt = document.getElementById('match-txt');
            if (!p2) { txt.textContent = ''; return; }
            if (p1 === p2) {
                txt.textContent = '✓ Contraseñas coinciden';
                txt.style.color = 'var(--green)';
            } else {
                txt.textContent = '✗ No coinciden';
                txt.style.color = 'var(--red)';
            }
        }
    </script>

<?php include 'footer.php'; ?>