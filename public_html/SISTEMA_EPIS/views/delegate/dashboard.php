<?php
Auth::requireDelegate();
$pageTitle = 'Panel del Delegado';

$delegadoId    = Auth::id();
$cicloPropioId = (int)($_SESSION['ciclo_propio_id'] ?? 0);

// Ciclos que puede evaluar
$ciclosAsignados = Auth::getAssignedCycles();

// Eventos activos y programados
$eventosDisp = DB::fetchAll(
    "SELECT e.*, (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id=e.id) as total_registros
     FROM eventos e
     WHERE e.estado IN ('activo','programado')
     ORDER BY e.fecha, e.hora_inicio"
);

// Mis registros recientes
$misRegistros = DB::fetchAll(
    "SELECT a.*, e.nombre as evento_nombre, est.nombres, est.apellidos, c.nombre as ciclo
     FROM asistencias a
     JOIN eventos e ON e.id = a.evento_id
     JOIN estudiantes est ON est.id = a.estudiante_id
     JOIN ciclos c ON c.id = est.ciclo_id
     WHERE a.registrado_por = ?
     ORDER BY a.fecha_registro DESC LIMIT 20",
    [$delegadoId]
);

$totalMisRegistros = (int)DB::fetchColumn(
    "SELECT COUNT(*) FROM asistencias WHERE registrado_por = ?", [$delegadoId]
);

include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-tachometer-alt"></i> Panel del Delegado</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Inicio</li></ol></nav>
  </div>
</div>

<!-- Info del delegado -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card blue">
      <div class="stat-value"><?= count($ciclosAsignados) ?></div>
      <div class="stat-label">Ciclos Asignados</div>
      <i class="fas fa-layer-group stat-icon"></i>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card green">
      <div class="stat-value"><?= count($eventosDisp) ?></div>
      <div class="stat-label">Eventos Disponibles</div>
      <i class="fas fa-calendar-star stat-icon"></i>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card yellow">
      <div class="stat-value"><?= number_format($totalMisRegistros) ?></div>
      <div class="stat-label">Registros Realizados</div>
      <i class="fas fa-clipboard-check stat-icon"></i>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Ciclos asignados -->
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-layer-group"></i> Mis Ciclos Asignados</h5>
      </div>
      <div class="card-body">
        <?php if (empty($ciclosAsignados)): ?>
          <div class="no-results"><i class="fas fa-ban"></i>Sin ciclos asignados</div>
        <?php else: ?>
          <?php foreach ($ciclosAsignados as $c): ?>
          <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
            <i class="fas fa-check-circle text-success"></i>
            <span><?= e($c['nombre']) ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ($cicloPropioId): ?>
          <?php $cicloPropioNombre = DB::fetchColumn("SELECT nombre FROM ciclos WHERE id=?",[$cicloPropioId]); ?>
          <div class="mt-3 alert alert-warning small py-2">
            <i class="fas fa-ban me-1"></i>
            No puedes evaluar: <strong><?= e($cicloPropioNombre ?: 'Tu ciclo') ?></strong>
          </div>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?= baseUrl('index.php?p=delegate/asistencia') ?>" class="btn btn-primary w-100 mt-3">
          <i class="fas fa-clipboard-check me-1"></i> Registrar Asistencia
        </a>
      </div>
    </div>
  </div>

  <!-- Eventos disponibles -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Eventos Proximos</h5>
      </div>
      <div class="card-body p-0">
        <?php if (empty($eventosDisp)): ?>
          <div class="no-results"><i class="fas fa-calendar-times"></i>Sin eventos disponibles</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:0.87rem">
            <thead class="table-light">
              <tr><th>Evento</th><th>Fecha</th><th>Horario</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($eventosDisp as $ev): ?>
              <tr>
                <td class="fw-600"><?= e($ev['nombre']) ?></td>
                <td><?= fechaES($ev['fecha']) ?></td>
                <td><?= horaFormatted($ev['hora_inicio']) ?></td>
                <td><?= badgeEstado($ev['estado']) ?></td>
                <td>
                  <a href="<?= baseUrl("index.php?p=delegate/asistencia&evento={$ev['id']}") ?>"
                     class="btn btn-sm btn-primary">
                    <i class="fas fa-clipboard-check"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Registros recientes -->
<?php if (!empty($misRegistros)): ?>
<div class="card mt-3">
  <div class="card-header">
    <h5 class="card-title"><i class="fas fa-history"></i> Mis Ultimos Registros</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:0.85rem">
        <thead class="table-light">
          <tr><th>Estudiante</th><th>Ciclo</th><th>Evento</th><th>Estado</th><th>Puntos</th><th>Fecha</th></tr>
        </thead>
        <tbody>
          <?php foreach ($misRegistros as $r): ?>
          <tr>
            <td><?= e($r['apellidos'].', '.$r['nombres']) ?></td>
            <td><?= e($r['ciclo']) ?></td>
            <td><?= e($r['evento_nombre']) ?></td>
            <td><?= badgeEstado($r['estado']) ?></td>
            <td><strong><?= $r['puntos'] ?></strong></td>
            <td><small><?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include VIEWS_PATH . '/layout/footer.php'; ?>
