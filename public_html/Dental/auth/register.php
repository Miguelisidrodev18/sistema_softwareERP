<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

if (estaLogueado()) {
    redirigirSegunRol($_SESSION['usuario_rol']);
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
        $error = 'Por favor completa los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Ese correo ya está registrado.';
        } else {
            // El registro público siempre crea pacientes.
            // La cuenta de dentista se crea únicamente vía /setup/crear_dentista.php
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, apellido, email, telefono, password, rol)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nombre, $apellido, $email, $telefono ?: null, $hash, 'paciente']);

            $success = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        }
    }
}

$tituloPagina = 'Crear cuenta';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-box">
    <div class="auth-icon"><?= icono('diente') ?></div>
    <h1>Crear cuenta de paciente</h1>
    <p class="auth-subtitle">Regístrate para solicitar y dar seguimiento a tus citas.</p>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/auth/register.php" class="form">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" required>

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required>

        <label for="telefono">Teléfono (opcional)</label>
        <input type="tel" id="telefono" name="telefono">

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required minlength="6">

        <label for="password2">Confirmar contraseña</label>
        <input type="password" id="password2" name="password2" required minlength="6">

        <button type="submit" class="btn btn-primary">Registrarme</button>
    </form>

    <p class="auth-link">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/auth/login.php">Inicia sesión</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
