<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('dentista');

$pacientes = $pdo->query(
    "SELECT id, nombre, apellido, dni, email, telefono, fecha_registro
     FROM usuarios
     WHERE rol = 'paciente'
     ORDER BY fecha_registro DESC"
)->fetchAll();

$tituloPagina = 'Pacientes';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <span class="page-icon"><?= icono('pacientes') ?></span>
        <div>
            <h1>Gestión de pacientes</h1>
            <p class="page-subtitle">Listado de todos los pacientes con cuenta en el sistema.</p>
        </div>
    </div>

    <a href="<?= BASE_URL ?>/dentista/paciente_nuevo.php" class="btn btn-primary">
        <span class="card-icon-inline"><?= icono('mas') ?></span> Nuevo paciente
    </a>
</div>

<?php if (empty($pacientes)): ?>
    <p class="empty-state">Todavía no hay pacientes registrados.</p>
<?php else: ?>
    <div class="search-bar">
        <?= icono('buscar') ?>
        <input type="text" id="buscarPaciente" placeholder="Buscar por nombre, DNI o teléfono...">
    </div>

    <div class="table-wrapper">
    <table class="table" id="tablaPacientes">
        <thead>
            <tr>
                <th>Paciente</th>
                <th>DNI</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pacientes as $p): ?>
                <?php
                    $iniciales = strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1));
                    $nombreCompleto = $p['nombre'] . ' ' . $p['apellido'];
                ?>
                <tr>
                    <td>
                        <div class="table-person">
                            <span class="avatar-chip"><?= htmlspecialchars($iniciales) ?></span>
                            <span><?= htmlspecialchars($nombreCompleto) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($p['dni'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['fecha_registro']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<script>
(function () {
    var input = document.getElementById('buscarPaciente');
    var tabla = document.getElementById('tablaPacientes');
    if (!input || !tabla) return;

    input.addEventListener('input', function () {
        var texto = input.value.trim().toLowerCase();
        var filas = tabla.querySelectorAll('tbody tr');

        filas.forEach(function (fila) {
            var contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(texto) ? '' : 'none';
        });
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
