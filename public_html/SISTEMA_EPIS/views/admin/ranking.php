<?php
Auth::requireAdmin();
$pageTitle = 'Ranking y Estadisticas';

// Ranking estudiantes (top 50)
$rankEstudiantes = DB::fetchAll(
    "SELECT e.codigo, e.apellidos, e.nombres, c.nombre as ciclo,
            COALESCE(SUM(a.puntos),0) as total_puntos,
            COUNT(CASE WHEN a.estado='asistio'   THEN 1 END) as asistencias,
            COUNT(CASE WHEN a.estado='tardanza'  THEN 1 END) as tardanzas,
            COUNT(CASE WHEN a.estado='falta'     THEN 1 END) as faltas,
            COUNT(a.id) as total_eventos
     FROM estudiantes e
     JOIN ciclos c ON c.id = e.ciclo_id
     LEFT JOIN asistencias a ON a.estudiante_id = e.id
     WHERE e.activo=1
     GROUP BY e.id, e.codigo, e.apellidos, e.nombres, c.nombre
     ORDER BY total_puntos DESC, asistencias DESC LIMIT 50"
);

// Ranking ciclos
$rankCiclos = DB::fetchAll(
    "SELECT c.nombre,
            COALESCE(SUM(a.puntos),0) as total_puntos,
            COUNT(DISTINCT a.estudiante_id) as participantes,
            COUNT(CASE WHEN a.estado='asistio'  THEN 1 END) as asistencias,
            COUNT(CASE WHEN a.estado='tardanza' THEN 1 END) as tardanzas,
            COUNT(CASE WHEN a.estado='falta'    THEN 1 END) as faltas
     FROM ciclos c
     LEFT JOIN estudiantes e ON e.ciclo_id = c.id AND e.activo=1
     LEFT JOIN asistencias a ON a.estudiante_id = e.id
     WHERE c.activo=1
     GROUP BY c.id, c.nombre
     ORDER BY total_puntos DESC"
);

$maxPuntosEst   = (int)($rankEstudiantes[0]['total_puntos'] ?? 1) ?: 1;
$maxPuntosCiclo = (int)($rankCiclos[0]['total_puntos'] ?? 1) ?: 1;

include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-trophy"></i> Ranking</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Ranking</li></ol></nav>
  </div>
  <div class="d-flex gap-2">
    <a href="ajax/export.php?tipo=ranking_estudiantes" class="btn btn-outline-success btn-sm">
      <i class="fas fa-file-excel me-1"></i> Excel Estudiantes
    </a>
    <a href="ajax/export.php?tipo=ranking_ciclos" class="btn btn-outline-success btn-sm">
      <i class="fas fa-file-excel me-1"></i> Excel Ciclos
    </a>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
      <i class="fas fa-print me-1"></i> Imprimir
    </button>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rankEstTab">
      <i class="fas fa-user me-1"></i> Por Estudiante
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#rankCicloTab">
      <i class="fas fa-layer-group me-1"></i> Por Ciclo
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#rankChartTab">
      <i class="fas fa-chart-bar me-1"></i> Graficos
    </button>
  </li>
</ul>

<div class="tab-content">
  <!-- Ranking Estudiantes -->
  <div class="tab-pane fade show active" id="rankEstTab">
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-epis mb-0" id="tablaRankEst">
            <thead>
              <tr>
                <th style="width:50px">Pos.</th>
                <th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th>
                <th>Puntos</th><th>Asist.</th><th>Tard.</th><th>Faltas</th>
                <th>Progreso</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rankEstudiantes as $i => $est): ?>
              <?php
              $pos  = $i + 1;
              $pct  = round($est['total_puntos'] / $maxPuntosEst * 100);
              $cls  = $pos <= 3 ? "rank-$pos" : '';
              ?>
              <tr class="<?= $cls ?>">
                <td>
                  <span class="rank-num"><?= $pos ?></span>
                  <?php if ($pos===1): ?> <i class="fas fa-crown text-warning ms-1"></i><?php endif; ?>
                </td>
                <td><?= e($est['codigo']) ?></td>
                <td class="fw-600"><?= e($est['apellidos'].', '.$est['nombres']) ?></td>
                <td><?= e($est['ciclo']) ?></td>
                <td><strong class="text-primary"><?= number_format($est['total_puntos']) ?></strong> pts</td>
                <td><span class="badge bg-success"><?= $est['asistencias'] ?></span></td>
                <td><span class="badge bg-warning text-dark"><?= $est['tardanzas'] ?></span></td>
                <td><span class="badge bg-danger"><?= $est['faltas'] ?></span></td>
                <td style="min-width:100px">
                  <div class="score-bar">
                    <div class="score-bar-fill" style="width:<?= $pct ?>%"></div>
                  </div>
                  <small class="text-muted"><?= $pct ?>%</small>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Ranking Ciclos -->
  <div class="tab-pane fade" id="rankCicloTab">
    <div class="row g-3">
      <?php foreach ($rankCiclos as $i => $ciclo): ?>
      <?php
      $pos = $i + 1;
      $pct = round($ciclo['total_puntos'] / $maxPuntosCiclo * 100);
      $badgeColors = ['#FFD700','#C0C0C0','#CD7F32'];
      $bg = $badgeColors[$i] ?? 'var(--primary)';
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div style="width:44px;height:44px;border-radius:50%;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:<?= $i<3?'#1a1a1a':'#fff' ?>">
                <?= $pos ?>
              </div>
              <div>
                <div class="fw-bold"><?= e($ciclo['nombre']) ?></div>
                <small class="text-muted"><?= $ciclo['participantes'] ?> participantes</small>
              </div>
              <div class="ms-auto">
                <span class="badge fs-6" style="background:var(--primary)">
                  <?= number_format($ciclo['total_puntos']) ?> pts
                </span>
              </div>
            </div>
            <div class="score-bar mb-2">
              <div class="score-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="row text-center g-2">
              <div class="col-4">
                <div class="fw-bold text-success"><?= $ciclo['asistencias'] ?></div>
                <small class="text-muted">Asist.</small>
              </div>
              <div class="col-4">
                <div class="fw-bold text-warning"><?= $ciclo['tardanzas'] ?></div>
                <small class="text-muted">Tard.</small>
              </div>
              <div class="col-4">
                <div class="fw-bold text-danger"><?= $ciclo['faltas'] ?></div>
                <small class="text-muted">Faltas</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Graficos -->
  <div class="tab-pane fade" id="rankChartTab">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h5 class="card-title"><i class="fas fa-trophy"></i> Puntaje por Ciclo</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="chartRankCiclos"></canvas></div></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header"><h5 class="card-title"><i class="fas fa-users"></i> Top 10 Estudiantes</h5></div>
          <div class="card-body"><div class="chart-container"><canvas id="chartRankTop10"></canvas></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$ciclosLabels  = json_encode(array_column($rankCiclos, 'nombre'));
$ciclosPuntos  = json_encode(array_map('intval', array_column($rankCiclos, 'total_puntos')));
$top10         = array_slice($rankEstudiantes, 0, 10);
$top10Labels   = json_encode(array_map(fn($e) => $e['apellidos'].', '.mb_substr($e['nombres'],0,8), $top10));
$top10Puntos   = json_encode(array_map(fn($e) => (int)$e['total_puntos'], $top10));
$extraJs = <<<JS
<script>
$(document).ready(() => initDataTable('tablaRankEst', {
  pageLength: 25,
  order: [[4,'desc']],
  columnDefs: [{ orderable: false, targets: 8 }]
}));

document.querySelector('[data-bs-target="#rankChartTab"]').addEventListener('shown.bs.tab', () => {
  Charts.horizontalBar('chartRankCiclos', {$ciclosLabels}, {$ciclosPuntos});
  Charts.horizontalBar('chartRankTop10', {$top10Labels}, {$top10Puntos}, Charts.palette);
});
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
