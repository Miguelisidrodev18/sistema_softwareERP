<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
Auth::requireAdmin();
$pageTitle = 'Dashboard';

// Estadisticas generales
$totalEstudiantes = (int)DB::fetchColumn("SELECT COUNT(*) FROM estudiantes WHERE activo=1");
$totalDelegados   = (int)DB::fetchColumn("SELECT COUNT(*) FROM usuarios WHERE activo=1");
$totalEventos     = (int)DB::fetchColumn("SELECT COUNT(*) FROM eventos");
$totalAsistencias = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='asistio'");
$totalTardanzas   = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='tardanza'");
$totalFaltas      = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='falta'");
$totalRegistros   = $totalAsistencias + $totalTardanzas + $totalFaltas;

// Proximos eventos
$proximosEventos = DB::fetchAll(
    "SELECT e.*, u.nombres as creador FROM eventos e
     JOIN usuarios u ON u.id = e.creado_por
     WHERE e.estado IN ('programado','activo')
     ORDER BY e.fecha, e.hora_inicio LIMIT 5"
);

// Ranking de ciclos (top 5)
$rankingCiclos = DB::fetchAll(
    "SELECT c.nombre, COALESCE(SUM(a.puntos),0) as total_puntos,
            COUNT(DISTINCT a.estudiante_id) as participantes
     FROM ciclos c
     LEFT JOIN estudiantes est ON est.ciclo_id = c.id
     LEFT JOIN asistencias a ON a.estudiante_id = est.id
     WHERE c.activo = 1
     GROUP BY c.id, c.nombre
     ORDER BY total_puntos DESC LIMIT 10"
);

// Participacion por evento (ultimos 8)
$participacionEventos = DB::fetchAll(
    "SELECT ev.nombre, COUNT(a.id) as total
     FROM eventos ev
     LEFT JOIN asistencias a ON a.evento_id = ev.id
     GROUP BY ev.id, ev.nombre
     ORDER BY ev.fecha DESC LIMIT 8"
);

include VIEWS_PATH . '/layout/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item active">Inicio</li>
      </ol>
    </nav>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge bg-secondary"><?= e(DB::config('nombre_sistema','EPIS')) ?></span>
    <span class="badge" style="background:var(--primary)"><?= date('d/m/Y') ?></span>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card blue">
      <div class="stat-value"><?= number_format($totalEstudiantes) ?></div>
      <div class="stat-label">Estudiantes</div>
      <i class="fas fa-user-graduate stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card yellow">
      <div class="stat-value"><?= number_format($totalDelegados) ?></div>
      <div class="stat-label">Delegados</div>
      <i class="fas fa-users-cog stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card purple">
      <div class="stat-value"><?= number_format($totalEventos) ?></div>
      <div class="stat-label">Eventos</div>
      <i class="fas fa-calendar-star stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card green">
      <div class="stat-value"><?= number_format($totalAsistencias) ?></div>
      <div class="stat-label">Asistencias</div>
      <i class="fas fa-check-circle stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card yellow">
      <div class="stat-value"><?= number_format($totalTardanzas) ?></div>
      <div class="stat-label">Tardanzas</div>
      <i class="fas fa-clock stat-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card red">
      <div class="stat-value"><?= number_format($totalFaltas) ?></div>
      <div class="stat-label">Faltas</div>
      <i class="fas fa-times-circle stat-icon"></i>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Grafico: Asistencias vs Tardanzas vs Faltas -->
  <div class="col-md-5">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-chart-pie"></i> Distribucion de Asistencia</h5>
      </div>
      <div class="card-body">
        <div class="chart-container">
          <canvas id="chartDistribucion"></canvas>
        </div>
        <?php if ($totalRegistros > 0): ?>
        <div class="d-flex justify-content-around mt-3 text-center">
          <div>
            <div class="fw-bold text-success"><?= round($totalAsistencias/$totalRegistros*100) ?>%</div>
            <small class="text-muted">Asistencias</small>
          </div>
          <div>
            <div class="fw-bold text-warning"><?= round($totalTardanzas/$totalRegistros*100) ?>%</div>
            <small class="text-muted">Tardanzas</small>
          </div>
          <div>
            <div class="fw-bold text-danger"><?= round($totalFaltas/$totalRegistros*100) ?>%</div>
            <small class="text-muted">Faltas</small>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Grafico: Ranking de ciclos -->
  <div class="col-md-7">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-trophy"></i> Ranking de Ciclos (Puntaje)</h5>
      </div>
      <div class="card-body">
        <div class="chart-container">
          <canvas id="chartCiclos"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Proximos eventos -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header justify-content-between">
        <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Proximos Eventos</h5>
        <a href="<?= baseUrl('index.php?p=admin/eventos') ?>" class="btn btn-sm btn-primary">Ver todos</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($proximosEventos)): ?>
          <div class="no-results"><i class="fas fa-calendar-times"></i>Sin eventos programados</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0" style="font-size:0.85rem">
            <tbody>
              <?php foreach ($proximosEventos as $ev): ?>
              <tr>
                <td class="ps-3">
                  <div class="fw-600"><?= e($ev['nombre']) ?></div>
                  <small class="text-muted"><?= fechaES($ev['fecha']) ?> — <?= horaFormatted($ev['hora_inicio']) ?></small>
                </td>
                <td class="text-end pe-3"><?= badgeEstado($ev['estado']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Participacion por evento -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-chart-bar"></i> Participacion por Evento</h5>
      </div>
      <div class="card-body">
        <div class="chart-container" style="height:220px">
          <canvas id="chartEventos"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$jsCiclosLabels = json_encode(array_column($rankingCiclos, 'nombre'));
$jsCiclosPuntos = json_encode(array_map('intval', array_column($rankingCiclos, 'total_puntos')));
$jsEvLabels     = json_encode(array_map(fn($e) => mb_strimwidth((string)($e['nombre'] ?? ''), 0, 23, '...'), $participacionEventos));
$jsEvData       = json_encode(array_map(fn($e) => (int)$e['total'], $participacionEventos));

$extraJs = "<script>
Charts.doughnutChart('chartDistribucion',
  ['Asistencias','Tardanzas','Faltas'],
  [{$totalAsistencias},{$totalTardanzas},{$totalFaltas}]
);

const ciclosLabels = {$jsCiclosLabels};
const ciclosPuntos = {$jsCiclosPuntos};
Charts.horizontalBar('chartCiclos', ciclosLabels, ciclosPuntos);

const evLabels = {$jsEvLabels};
const evData   = {$jsEvData};
Charts.barChart('chartEventos', evLabels, evData, 'Participantes', '#003087');
</script>";

include VIEWS_PATH . '/layout/footer.php';