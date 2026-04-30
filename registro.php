<?php
session_start();
require_once 'config.php';

if (estaLogueado()) { header('Location: ' . BASE_URL . '/tienda.php'); exit; }

$error = $exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $email  = limpiar($_POST['email']  ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (!$nombre || !$email || !$pass) {
        $error = 'Todos los campos son requeridos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $conn = conectarDB();
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Este email ya está registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            // Generar wallet address única para el usuario
            $wallet = '0x' . bin2hex(random_bytes(20));
            $stmt2 = $conn->prepare("INSERT INTO usuarios (nombre, email, password, wallet_address) VALUES (?,?,?,?)");
            $stmt2->bind_param('ssss', $nombre, $email, $hash, $wallet);
            if ($stmt2->execute()) {
                $id = $conn->insert_id;
                $_SESSION['usuario_id']     = $id;
                $_SESSION['nombre']         = $nombre;
                $_SESSION['email']          = $email;
                $_SESSION['rol']            = 'cliente';
                header('Location: ' . BASE_URL . '/tienda.php');
                exit;
            } else {
                $error = 'Error al registrar. Intenta de nuevo.';
            }
        }
        $conn->close();
    }
}

$page_title = 'Registro';
include 'header.php';
?>
<style>
.registro-wrap {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px 0;
}
.registro-wrap h1 {
    font-family: 'Orbitron', monospace;
    font-size: 1.6rem;
    color: var(--cyan);
    margin-bottom: 8px;
}
.registro-wrap .sub { color: var(--text-dim); margin-bottom: 32px; }
</style>

<div class="registro-wrap fade-up">
    <h1>⚡ CREAR CUENTA</h1>
    <p class="sub">Únete a la mejor tienda gaming de México</p>

    <?php if($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Tu nombre" required value="<?= limpiar($_POST['nombre']??'') ?>">
            </div>
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="tu@email.com" required value="<?= limpiar($_POST['email']??'') ?>">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
            </div>
            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" name="password2" class="form-control" placeholder="Repite tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                CREAR MI CUENTA →
            </button>
        </form>
    </div>
    <p style="text-align:center;margin-top:20px;color:var(--text-dim)">
        ¿Ya tienes cuenta? <a href="index.php" style="color:var(--cyan)">Iniciar sesión</a>
    </p>
</div>

<?php include 'footer.php'; ?>
