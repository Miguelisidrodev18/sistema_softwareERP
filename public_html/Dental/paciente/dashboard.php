<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('paciente');

$usuario = usuarioActual();

$stmt = $pdo->prepare('SELECT nombre, apellido, email, telefono, fecha_registro FROM usuarios WHERE id = ?');
$stmt->execute([$usuario['id']]);
$datos = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT fecha, hora, motivo, estado
     FROM citas
     WHERE paciente_id = ? AND estado IN ('pendiente', 'confirmada') AND fecha >= CURDATE()
     ORDER BY fecha ASC, hora ASC
     LIMIT 5"
);
$stmt->execute([$usuario['id']]);
$proximasCitas = $stmt->fetchAll();

$tituloPagina = 'Mi panel';
require __DIR__ . '/../includes/header.php';
?>

<h1>Hola, <?= htmlspecialchars($datos['nombre']) ?></h1>
<p class="page-subtitle">Este es tu panel personal del consultorio.</p>

<div class="cards">
    <div class="card">
        <span class="card-icon"><?= icono('id') ?></span>
        <h3>Mis datos</h3>
        <p class="data-row"><strong>Nombre:</strong> <?= htmlspecialchars($datos['nombre'] . ' ' . $datos['apellido']) ?></p>
        <p class="data-row"><strong>Correo:</strong> <?= htmlspecialchars($datos['email']) ?></p>
        <p class="data-row"><strong>Teléfono:</strong> <?= htmlspecialchars($datos['telefono'] ?? 'No registrado') ?></p>
        <p class="data-row"><strong>Paciente desde:</strong> <?= htmlspecialchars($datos['fecha_registro']) ?></p>
    </div>
    <div class="card">
        <span class="card-icon"><?= icono('citas') ?></span>
        <h3>Mis citas</h3>
        <p>Agenda o revisa tus próximas citas.</p>
        <a href="<?= BASE_URL ?>/paciente/citas.php" class="btn btn-secondary">Ver mis citas</a>
    </div>
</div>

<h2 class="section-title">Próximas citas</h2>

<?php if (empty($proximasCitas)): ?>
    <p class="empty-state">No tienes citas próximas. <a href="<?= BASE_URL ?>/paciente/citas.php">Solicita una aquí</a>.</p>
<?php else: ?>
    <div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Motivo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proximasCitas as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['fecha']) ?></td>
                    <td><?= htmlspecialchars(substr($c['hora'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($c['motivo']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($c['estado']) ?>"><?= ucfirst($c['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
