<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('dentista');

$usuario = usuarioActual();

$totalPacientes = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'paciente'")->fetch()['total'];
$citasPendientes = $pdo->query("SELECT COUNT(*) AS total FROM citas WHERE estado = 'pendiente'")->fetch()['total'];

$proximasCitas = $pdo->query(
    "SELECT c.fecha, c.hora, c.motivo, c.estado, u.nombre, u.apellido
     FROM citas c
     JOIN usuarios u ON u.id = c.paciente_id
     WHERE c.estado IN ('pendiente', 'confirmada') AND c.fecha >= CURDATE()
     ORDER BY c.fecha ASC, c.hora ASC
     LIMIT 5"
)->fetchAll();

$tituloPagina = 'Panel del dentista';
require __DIR__ . '/../includes/header.php';
?>

<h1>Bienvenido, Dr(a). <?= htmlspecialchars(explode(' ', $usuario['nombre'])[0]) ?></h1>
<p class="page-subtitle">Este es el resumen de tu consultorio hoy.</p>

<div class="cards">
    <div class="card">
        <span class="card-icon"><?= icono('pacientes') ?></span>
        <h2><?= (int)$totalPacientes ?></h2>
        <p>Pacientes registrados</p>
        <a href="<?= BASE_URL ?>/dentista/pacientes.php" class="btn btn-secondary">Ver pacientes</a>
    </div>
    <div class="card">
        <span class="card-icon"><?= icono('citas') ?></span>
        <h2><?= (int)$citasPendientes ?></h2>
        <p>Citas pendientes por confirmar</p>
        <a href="<?= BASE_URL ?>/dentista/citas.php?estado=pendiente" class="btn btn-secondary">Ver citas</a>
    </div>
</div>

<h2 class="section-title">Próximas citas</h2>

<?php if (empty($proximasCitas)): ?>
    <p class="empty-state">No hay citas próximas.</p>
<?php else: ?>
    <div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Paciente</th>
                <th>Motivo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proximasCitas as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['fecha']) ?></td>
                    <td><?= htmlspecialchars(substr($c['hora'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                    <td><?= htmlspecialchars($c['motivo']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($c['estado']) ?>"><?= ucfirst($c['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
