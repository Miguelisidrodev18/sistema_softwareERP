<?php
// Script de un solo uso para crear la primera cuenta de dentista.
// Por seguridad, se bloquea automáticamente si ya existe un dentista,
// y DEBES borrar este archivo (o toda la carpeta /setup) después de usarlo.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$yaExisteDentista = (int) $pdo->query(
    "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'dentista'"
)->fetch()['total'] > 0;

$error   = '';
$success = '';

if ($yaExisteDentista) {
    $error = 'Ya existe una cuenta de dentista. Por seguridad, borra este archivo (setup/crear_dentista.php).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
        $error = 'Completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo inválido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password, rol)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $apellido, $email, $hash, 'dentista']);

        $success = 'Cuenta de dentista creada correctamente. Ahora BORRA este archivo (setup/crear_dentista.php) y luego inicia sesión.';
    }
}

$tituloPagina = 'Crear cuenta de dentista';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-box">
    <div class="auth-icon"><?= icono('ajustes') ?></div>
    <h1>Crear cuenta de dentista</h1>
    <p class="auth-subtitle">Configuración inicial — solo se usa una vez.</p>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
        <p><a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary">Ir a iniciar sesión</a></p>
    <?php endif; ?>

    <?php if (!$yaExisteDentista && !$success): ?>
        <form method="POST" action="<?= BASE_URL ?>/setup/crear_dentista.php" class="form">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" required>

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required minlength="8">

            <label for="password2">Confirmar contraseña</label>
            <input type="password" id="password2" name="password2" required minlength="8">

            <button type="submit" class="btn btn-primary">Crear cuenta de dentista</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
