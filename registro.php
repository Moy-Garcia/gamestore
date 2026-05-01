<?php
session_start();
require_once 'config.php';

if (estaLogueado()) {
    header('Location: ' . BASE_URL . '/tienda.php');
    exit;
}

$error  = '';
$exito  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar campos — strip_tags pero SÍ permitir acentos y ñ
    $nombre_s  = trim(strip_tags($_POST['nombre_s']   ?? ''));
    $ap_pat    = trim(strip_tags($_POST['ap_paterno'] ?? ''));
    $ap_mat    = trim(strip_tags($_POST['ap_materno'] ?? ''));
    $num_cuenta= trim(strip_tags($_POST['num_cuenta'] ?? ''));
    $email     = trim(strip_tags($_POST['email']      ?? ''));
    $pass      = $_POST['password']  ?? '';
    $pass2     = $_POST['password2'] ?? '';

    // Nombre completo para guardar en BD
    $nombre_completo = trim("$nombre_s $ap_pat $ap_mat");

    // ---- Validaciones ----
    if (empty($nombre_s)) {
        $error = 'El campo Nombre(s) es obligatorio.';
    } elseif (empty($ap_pat)) {
        $error = 'El Apellido Paterno es obligatorio.';
    } elseif (empty($email)) {
        $error = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo "' . htmlspecialchars($email) . '" no tiene un formato válido. Ejemplo correcto: usuario@gmail.com';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden. Verifica que sean idénticas.';
    } elseif (!empty($num_cuenta) && !preg_match('/^\d{6,20}$/', $num_cuenta)) {
        $error = 'El número de cuenta solo debe contener dígitos (6 a 20 números).';
    } else {
        // Intentar registrar en BD
        try {
            $conn = conectarDB();

            // Verificar si email ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'Este correo ya está registrado. ¿Quieres <a href="index.php" style="color:var(--cyan)">iniciar sesión</a>?';
            } else {
                $stmt->close();

                $hash   = password_hash($pass, PASSWORD_DEFAULT);
                $wallet = '0x' . bin2hex(random_bytes(20));

                $stmt2 = $conn->prepare(
                    "INSERT INTO usuarios (nombre, email, password, wallet_address) VALUES (?, ?, ?, ?)"
                );
                $stmt2->bind_param('ssss', $nombre_completo, $email, $hash, $wallet);

                if ($stmt2->execute()) {
                    $nuevo_id = $conn->insert_id;

                    // Guardar número de cuenta en log si existe
                    if (!empty($num_cuenta)) {
                        $desc = "Registro. Núm. cuenta: $num_cuenta";
                        $conn->query("INSERT INTO actividad_log (usuario_id, accion, descripcion)
                                      VALUES ($nuevo_id, 'REGISTRO', " . $conn->real_escape_string($desc) . ")");
                    }

                    // Iniciar sesión automáticamente
                    $_SESSION['usuario_id'] = $nuevo_id;
                    $_SESSION['nombre']     = $nombre_completo;
                    $_SESSION['email']      = $email;
                    $_SESSION['rol']        = 'cliente';
                    if (!empty($num_cuenta)) {
                        $_SESSION['num_cuenta'] = $num_cuenta;
                    }

                    $conn->close();
                    header('Location: ' . BASE_URL . '/tienda.php');
                    exit;
                } else {
                    $error = 'Error al guardar el registro: ' . $conn->error;
                }
            }
            $conn->close();
        } catch (Exception $e) {
            $error = 'Error del servidor: ' . $e->getMessage();
        }
    }
}

$page_title = 'Crear cuenta';
include 'header.php';
?>
    <style>
        .registro-wrap{max-width:580px;margin:0 auto;padding:16px 0 40px}
        .registro-wrap h1{font-family:'Orbitron',monospace;font-size:1.4rem;color:var(--cyan);margin-bottom:6px}
        .registro-wrap .sub{color:var(--text-dim);margin-bottom:24px;font-size:.92rem}

        .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-grid-2 .full{grid-column:1/-1}

        .campo-req{color:var(--red);margin-left:2px;font-size:.9rem}
        .campo-hint{font-size:.72rem;color:var(--text-dim);margin-top:4px;line-height:1.4}

        /* Indicador fuerza contraseña */
        .strength-bar{height:3px;background:rgba(255,255,255,.08);border-radius:2px;margin-top:5px;overflow:hidden}
        .strength-fill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s}
        .strength-lbl{font-size:.72rem;margin-top:3px;color:var(--text-dim)}

        /* Confirmación de coincidencia */
        .match-ok {font-size:.72rem;color:var(--green);margin-top:4px}
        .match-err{font-size:.72rem;color:var(--red);  margin-top:4px}

        .sec-title{
            font-family:'Orbitron',monospace;font-size:.8rem;color:var(--purple);
            letter-spacing:1.5px;margin:4px 0 16px;padding-bottom:8px;
            border-bottom:1px solid var(--border);
        }

        @media(max-width:560px){
            .form-grid-2{grid-template-columns:1fr}
            .form-grid-2 .full{grid-column:1}
        }
    </style>

    <div class="registro-wrap fade-up">
        <h1>⚡ CREAR CUENTA</h1>
        <p class="sub">Únete y accede a los mejores precios gaming de México</p>

        <?php if($error): ?>
            <div class="alert alert-error" style="margin-bottom:20px">
                ⚠️ <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" autocomplete="off" novalidate>

                <!-- DATOS PERSONALES -->
                <div class="sec-title">👤 DATOS PERSONALES</div>

                <div class="form-grid-2">
                    <!-- Nombre(s) -->
                    <div class="form-group full">
                        <label>Nombre(s) <span class="campo-req">*</span></label>
                        <input type="text" name="nombre_s" class="form-control"
                               placeholder="Ej. Juan Carlos"
                               value="<?= htmlspecialchars($_POST['nombre_s'] ?? '', ENT_QUOTES) ?>"
                               required autocomplete="given-name">
                        <div class="campo-hint">Sí se permiten acentos (á, é, í, ó, ú) y ñ</div>
                    </div>

                    <!-- Apellido paterno -->
                    <div class="form-group">
                        <label>Apellido paterno <span class="campo-req">*</span></label>
                        <input type="text" name="ap_paterno" class="form-control"
                               placeholder="Ej. García"
                               value="<?= htmlspecialchars($_POST['ap_paterno'] ?? '', ENT_QUOTES) ?>"
                               required autocomplete="family-name">
                    </div>

                    <!-- Apellido materno -->
                    <div class="form-group">
                        <label>Apellido materno</label>
                        <input type="text" name="ap_materno" class="form-control"
                               placeholder="Ej. López (opcional)"
                               value="<?= htmlspecialchars($_POST['ap_materno'] ?? '', ENT_QUOTES) ?>"
                               autocomplete="additional-name">
                    </div>

                    <!-- Número de cuenta -->
                    <div class="form-group full">
                        <label>Número de cuenta</label>
                        <input type="text" name="num_cuenta" class="form-control"
                               placeholder="Ej. 22230706 (solo dígitos, opcional)"
                               value="<?= htmlspecialchars($_POST['num_cuenta'] ?? '', ENT_QUOTES) ?>"
                               inputmode="numeric" maxlength="20"
                               oninput="this.value=this.value.replace(/\D/g,'')">
                        <div class="campo-hint">Solo números · entre 6 y 20 dígitos · opcional</div>
                    </div>
                </div>

                <!-- DATOS DE ACCESO -->
                <div class="sec-title" style="margin-top:8px">🔐 DATOS DE ACCESO</div>

                <div class="form-grid-2">
                    <!-- Email -->
                    <div class="form-group full">
                        <label>Correo electrónico <span class="campo-req">*</span></label>
                        <input type="email" name="email" class="form-control"
                               placeholder="Ej. usuario@gmail.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>"
                               required autocomplete="email">
                        <div class="campo-hint">Formato válido: usuario@dominio.com — sin espacios ni caracteres especiales</div>
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label>Contraseña <span class="campo-req">*</span></label>
                        <input type="password" name="password" id="pass1" class="form-control"
                               placeholder="Mínimo 6 caracteres"
                               required autocomplete="new-password"
                               oninput="medirFuerza(this.value)">
                        <div class="strength-bar"><div class="strength-fill" id="s-fill"></div></div>
                        <div class="strength-lbl" id="s-lbl"></div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="form-group">
                        <label>Confirmar contraseña <span class="campo-req">*</span></label>
                        <input type="password" name="password2" id="pass2" class="form-control"
                               placeholder="Repite la contraseña"
                               required autocomplete="new-password"
                               oninput="verificarMatch()">
                        <div id="match-msg"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;margin-top:8px;padding:14px">
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
            const fill  = document.getElementById('s-fill');
            const label = document.getElementById('s-lbl');
            let score   = 0;
            if (v.length >= 6)           score++;
            if (v.length >= 10)          score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            const niveles = [
                {w:'0%',  c:'transparent',t:''},
                {w:'20%', c:'#ff2d55',    t:'Muy débil'},
                {w:'40%', c:'#ff6b00',    t:'Débil'},
                {w:'65%', c:'#f7c948',    t:'Aceptable'},
                {w:'85%', c:'#39ff14',    t:'Fuerte'},
                {w:'100%',c:'#00f5ff',    t:'Muy fuerte ✓'},
            ];
            const n = niveles[Math.min(score, 5)];
            fill.style.width      = n.w;
            fill.style.background = n.c;
            label.textContent     = n.t;
            label.style.color     = n.c;
            verificarMatch();
        }

        function verificarMatch() {
            const p1  = document.getElementById('pass1').value;
            const p2  = document.getElementById('pass2').value;
            const msg = document.getElementById('match-msg');
            if (!p2) { msg.textContent = ''; return; }
            if (p1 === p2) {
                msg.className   = 'match-ok';
                msg.textContent = '✓ Las contraseñas coinciden';
            } else {
                msg.className   = 'match-err';
                msg.textContent = '✗ No coinciden aún';
            }
        }
    </script>

<?php include 'footer.php'; ?>