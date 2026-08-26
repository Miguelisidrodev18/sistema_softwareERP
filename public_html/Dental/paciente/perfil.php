<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('paciente');

$usuario = usuarioActual();
$error   = '';
$success = '';

$stmt = $pdo->prepare('SELECT nombre, apellido, dni, email, telefono, password FROM usuarios WHERE id = ?');
$stmt->execute([$usuario['id']]);
$datos = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual    = $_POST['actual'] ?? '';
    $nueva     = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($actual === '' || $nueva === '' || $confirmar === '') {
        $error = 'Completa todos los campos.';
    } elseif (!password_verify($actual, $datos['password'])) {
        $error = 'Tu contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $usuario['id']]);

        $success = 'Tu contraseña se actualizó correctamente.';
    }
}

$tituloPagina = 'Mi perfil';
require __DIR__ . '/../includes/header.php';
?>

<h1>Mi perfil</h1>
<p class="page-subtitle">Consulta tus datos y cambia tu contraseña cuando quieras.</p>

<div class="cards">
    <div class="card">
        <span class="card-icon"><?= icono('id') ?></span>
        <h3>Mis datos</h3>
        <p class="data-row"><strong>Nombre:</strong> <?= htmlspecialchars($datos['nombre'] . ' ' . $datos['apellido']) ?></p>
        <p class="data-row"><strong>DNI:</strong> <?= htmlspecialchars($datos['dni'] ?? '—') ?></p>
        <p class="data-row"><strong>Correo:</strong> <?= htmlspecialchars($datos['email'] ?? 'No registrado') ?></p>
        <p class="data-row"><strong>Teléfono:</strong> <?= htmlspecialchars($datos['telefono'] ?? 'No registrado') ?></p>
    </div>

    <div class="card card-form">
        <span class="card-icon"><?= icono('candado') ?></span>
        <h3>Cambiar contraseña</h3>

        <?php if ($error): ?>
            <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/paciente/perfil.php" class="form">
            <label for="actual">Contraseña actual</label>
            <input type="password" id="actual" name="actual" required>

            <label for="nueva">Nueva contraseña</label>
            <input type="password" id="nueva" name="nueva" required minlength="6">

            <label for="confirmar">Confirmar nueva contraseña</label>
            <input type="password" id="confirmar" name="confirmar" required minlength="6">

            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
