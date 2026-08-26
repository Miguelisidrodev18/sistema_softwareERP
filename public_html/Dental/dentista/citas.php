<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('dentista');

$estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'completada'];
$filtro = $_GET['estado'] ?? '';
if (!in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

$sql = "SELECT c.id, c.fecha, c.hora, c.motivo, c.estado, c.notas_dentista,
               u.nombre, u.apellido, u.telefono
        FROM citas c
        JOIN usuarios u ON u.id = c.paciente_id";
$params = [];

if ($filtro !== '') {
    $sql .= ' WHERE c.estado = ?';
    $params[] = $filtro;
}

$sql .= ' ORDER BY c.fecha ASC, c.hora ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

$etiquetasEstado = [
    'pendiente'  => 'Pendiente',
    'confirmada' => 'Confirmada',
    'cancelada'  => 'Cancelada',
    'completada' => 'Completada',
];

$tituloPagina = 'Citas';
require __DIR__ . '/../includes/header.php';
?>

<h1>Citas</h1>
<p class="page-subtitle">Confirma, completa o cancela las citas solicitadas por tus pacientes.</p>

<div class="filtros">
    <a href="<?= BASE_URL ?>/dentista/citas.php" class="btn btn-secondary btn-sm <?= $filtro === '' ? 'active' : '' ?>">Todas</a>
    <?php foreach ($etiquetasEstado as $valor => $etiqueta): ?>
        <a href="<?= BASE_URL ?>/dentista/citas.php?estado=<?= $valor ?>" class="btn btn-secondary btn-sm <?= $filtro === $valor ? 'active' : '' ?>"><?= $etiqueta ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($citas)): ?>
    <p class="empty-state">No hay citas <?= $filtro !== '' ? 'con estado "' . htmlspecialchars($etiquetasEstado[$filtro]) . '"' : 'registradas' ?>.</p>
<?php else: ?>
    <div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Paciente</th>
                <th>Teléfono</th>
                <th>Motivo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($citas as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['fecha']) ?></td>
                    <td><?= htmlspecialchars(substr($c['hora'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                    <td><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['motivo']) ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($c['estado']) ?>"><?= $etiquetasEstado[$c['estado']] ?></span></td>
                    <td class="acciones-citas">
                        <?php if ($c['estado'] === 'pendiente'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/dentista/cita_actualizar.php">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="accion" value="confirmar">
                                <button type="submit" class="btn btn-primary btn-sm">Confirmar</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($c['estado'], ['pendiente', 'confirmada'], true)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/dentista/cita_actualizar.php">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="accion" value="completar">
                                <button type="submit" class="btn btn-secondary btn-sm">Completar</button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/dentista/cita_actualizar.php" onsubmit="return confirm('¿Cancelar esta cita?');">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="accion" value="cancelar">
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
