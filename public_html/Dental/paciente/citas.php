<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('paciente');

$usuario = usuarioActual();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha  = trim($_POST['fecha'] ?? '');
    $hora   = trim($_POST['hora'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');

    $hoy = date('Y-m-d');

    if ($fecha === '' || $hora === '' || $motivo === '') {
        $error = 'Completa todos los campos.';
    } elseif ($fecha < $hoy) {
        $error = 'La fecha debe ser hoy o una fecha futura.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO citas (paciente_id, fecha, hora, motivo, estado)
             VALUES (?, ?, ?, ?, \'pendiente\')'
        );
        $stmt->execute([$usuario['id'], $fecha, $hora, $motivo]);

        $success = 'Tu cita fue solicitada. Quedará pendiente de confirmación por el dentista.';
    }
}

$stmt = $pdo->prepare(
    'SELECT id, fecha, hora, motivo, estado, notas_dentista
     FROM citas
     WHERE paciente_id = ?
     ORDER BY fecha DESC, hora DESC'
);
$stmt->execute([$usuario['id']]);
$citas = $stmt->fetchAll();

$etiquetasEstado = [
    'pendiente'  => 'Pendiente',
    'confirmada' => 'Confirmada',
    'cancelada'  => 'Cancelada',
    'completada' => 'Completada',
];

$tituloPagina = 'Mis citas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Mis citas</h1>
<p class="page-subtitle">Solicita una nueva cita o revisa el estado de las anteriores.</p>

<div class="card card-form">
    <h3><span class="card-icon-inline"><?= icono('mas') ?></span> Solicitar nueva cita</h3>

    <?php if ($error): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/paciente/citas.php" class="form">
        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" min="<?= date('Y-m-d') ?>" required>

        <label for="hora">Hora</label>
        <input type="time" id="hora" name="hora" required>

        <label for="motivo">Motivo de la consulta</label>
        <input type="text" id="motivo" name="motivo" maxlength="255" placeholder="Ej: dolor de muela, limpieza, revisión" required>

        <button type="submit" class="btn btn-primary">Solicitar cita</button>
    </form>
</div>

<h2 class="section-title">Historial</h2>

<?php if (empty($citas)): ?>
    <p class="empty-state">Todavía no has solicitado ninguna cita.</p>
<?php else: ?>
    <div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Motivo</th>
                <th>Estado</th>
                <th>Notas del dentista</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['fecha']) ?></td>
                    <td><?= htmlspecialchars(substr($c['hora'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($c['motivo']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($c['estado']) ?>"><?= $etiquetasEstado[$c['estado']] ?></span></td>
                    <td><?= htmlspecialchars($c['notas_dentista'] ?? '—') ?></td>
                    <td>
                        <?php if (in_array($c['estado'], ['pendiente', 'confirmada'], true)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/paciente/cita_cancelar.php" onsubmit="return confirm('¿Cancelar esta cita?');">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
