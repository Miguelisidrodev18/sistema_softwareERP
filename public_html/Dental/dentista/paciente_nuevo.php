<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('dentista');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $dni      = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($nombre === '' || $apellido === '' || $dni === '') {
        $error = 'Nombre, apellido y DNI son obligatorios.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE dni = ?');
        $stmt->execute([$dni]);

        if ($stmt->fetch()) {
            $error = 'Ya existe un paciente registrado con ese DNI.';
        } elseif ($email !== '') {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Ese correo ya está registrado.';
            }
        }

        if ($error === '') {
            // La contraseña inicial es el mismo DNI; el paciente puede
            // cambiarla luego desde "Mi perfil".
            $hash = password_hash($dni, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, apellido, dni, email, telefono, password, rol)
                 VALUES (?, ?, ?, ?, ?, ?, \'paciente\')'
            );
            $stmt->execute([
                $nombre,
                $apellido,
                $dni,
                $email !== '' ? $email : null,
                $telefono !== '' ? $telefono : null,
                $hash,
            ]);

            $success = 'Paciente creado. Usuario: ' . $dni . ' — Contraseña inicial: ' . $dni;
        }
    }
}

$tituloPagina = 'Nuevo paciente';
require __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/dentista/pacientes.php" class="back-link">
    <?= icono('volver') ?> Volver a pacientes
</a>

<div class="page-header">
    <div class="page-header-left">
        <span class="page-icon"><?= icono('mas') ?></span>
        <div>
            <h1>Nuevo paciente</h1>
            <p class="page-subtitle">El usuario y la contraseña inicial del paciente serán su DNI. Podrá cambiarla luego desde su panel.</p>
        </div>
    </div>
</div>

<div class="card card-form">
    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
        <div class="page-actions">
            <a href="<?= BASE_URL ?>/dentista/pacientes.php" class="btn btn-secondary">Ver pacientes</a>
            <a href="<?= BASE_URL ?>/dentista/paciente_nuevo.php" class="btn btn-secondary">Crear otro paciente</a>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/dentista/paciente_nuevo.php" class="form">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" name="apellido" required>

            <label for="dni">DNI</label>
            <input type="text" id="dni" name="dni" required>

            <label for="telefono">Teléfono (opcional)</label>
            <input type="tel" id="telefono" name="telefono">

            <label for="email">Correo electrónico (opcional)</label>
            <input type="email" id="email" name="email">

            <button type="submit" class="btn btn-primary">Crear paciente</button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
