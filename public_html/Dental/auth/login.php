<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (estaLogueado()) {
    redirigirSegunRol($_SESSION['usuario_rol']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = trim($_POST['identificador'] ?? '');
    $password      = $_POST['password'] ?? '';

    if ($identificador === '' || $password === '') {
        $error = 'Por favor completa todos los campos.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nombre, apellido, password, rol FROM usuarios WHERE email = ? OR dni = ?'
        );
        $stmt->execute([$identificador, $identificador]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            redirigirSegunRol($usuario['rol']);
        } else {
            $error = 'Correo/DNI o contraseña incorrectos.';
        }
    }
}

$tituloPagina = 'Iniciar sesión';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-box">
    <div class="auth-icon"><?= icono('diente') ?></div>
    <h1>Iniciar sesión</h1>
    <p class="auth-subtitle">Accede a tu cuenta del consultorio dental.</p>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/auth/login.php" class="form">
        <label for="identificador">Correo electrónico o DNI</label>
        <input type="text" id="identificador" name="identificador" required autofocus>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>

    <p class="auth-link">¿No tienes cuenta? <a href="<?= BASE_URL ?>/auth/register.php">Regístrate como paciente</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
