<?php
if (!Auth::isStudent()) { redirect('login'); }
$pageTitle   = 'Mi Perfil';
$studentId   = (int)$_SESSION['student_id'];
$cicloId     = (int)$_SESSION['ciclo_id'];

// Datos del estudiante
$estudiante  = DB::fetchOne(
    "SELECT e.*, c.nombre as ciclo_nombre FROM estudiantes e JOIN ciclos c ON c.id=e.ciclo_id WHERE e.id=?",
    [$studentId]
);
if (!$estudiante) { Auth::logout(); redirect('login'); }

// Mis asistencias
$misAsistencias = DB::fetchAll(
    "SELECT a.*, ev.nombre as evento_nombre, ev.fecha, ev.hora_inicio
     FROM asistencias a
     JOIN eventos ev ON ev.id = a.evento_id
     WHERE a.estudiante_id = ?
     ORDER BY ev.fecha DESC, ev.hora_inicio DESC",
    [$studentId]
);

$totalPuntos    = array_sum(array_column($misAsistencias, 'puntos'));
$totalAsistio   = count(array_filter($misAsistencias, fn($a) => $a['estado']==='asistio'));
$totalTardanza  = count(array_filter($misAsistencias, fn($a) => $a['estado']==='tardanza'));
$totalFaltas    = count(array_filter($misAsistencias, fn($a) => $a['estado']==='falta'));
$totalEventos   = count($misAsistencias);

// Ranking del estudiante (posicion entre todos)
$rankGlobal = (int)DB::fetchColumn(
    "SELECT COUNT(*)+1 FROM (
       SELECT estudiante_id, SUM(puntos) as pts FROM asistencias GROUP BY estudiante_id
     ) t WHERE pts > ?",
    [$totalPuntos]
);

// Ranking en su ciclo
$rankCiclo = (int)DB::fetchColumn(
    "SELECT COUNT(*)+1 FROM (
       SELECT a.estudiante_id, SUM(a.puntos) as pts
       FROM asistencias a
       JOIN estudiantes e ON e.id=a.estudiante_id
       WHERE e.ciclo_id=?
       GROUP BY a.estudiante_id
     ) t WHERE pts > ?",
    [$cicloId, $totalPuntos]
);

// Top 10 ranking general (para mostrar tabla)
$topEstudiantes = DB::fetchAll(
    "SELECT e.codigo, e.apellidos, e.nombres, c.nombre as ciclo,
            COALESCE(SUM(a.puntos),0) as total_puntos
     FROM estudiantes e
     JOIN ciclos c ON c.id=e.ciclo_id
     LEFT JOIN asistencias a ON a.estudiante_id=e.id
     WHERE e.activo=1
     GROUP BY e.id ORDER BY total_puntos DESC LIMIT 10"
);

// Eventos programados
$eventosProximos = DB::fetchAll(
    "SELECT id, nombre, fecha, hora_inicio, estado
     FROM eventos WHERE estado IN ('programado','activo')
     ORDER BY fecha, hora_inicio LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi Perfil — Sistema EPIS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body style="background:var(--gray-100)">

<!-- Topbar simple para estudiante -->
<nav class="navbar navbar-expand-md" style="background:var(--primary);padding:10px 20px">
  <div class="container-fluid">
    <span class="navbar-brand text-white fw-bold">
      <span style="background:var(--secondary);color:var(--primary-dark);padding:4px 10px;border-radius:6px;font-size:0.9rem;font-weight:900;margin-right:8px">IS</span>
      EPIS — Semana Universitaria
    </span>
    <div class="d-flex align-items-center gap-3">
      <span class="text-white small d-none d-md-block">
        <i class="fas fa-user me-1"></i><?= e($estudiante['nombres'].' '.$estudiante['apellidos']) ?>
      </span>
      <a href="<?= baseUrl('index.php?p=logout') ?>" class="btn btn-sm btn-outline-light">
        <i class="fas fa-sign-out-alt"></i> Salir
      </a>
    </div>
  </div>
</nav>

<div class="container-lg py-4">
  <!-- Encabezado -->
  <div class="row g-3 mb-4">
    <div class="col-md-8">
      <div class="card">
        <div class="card-body d-flex align-items-center gap-4">
          <div style="width:70px;height:70px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;font-weight:900;flex-shrink:0">
            <?= strtoupper(substr($estudiante['nombres'],0,1)) ?>
          </div>
          <div>
            <h4 class="fw-800 mb-0" style="color:var(--primary)">
              <?= e($estudiante['apellidos'].', '.$estudiante['nombres']) ?>
            </h4>
            <div class="text-muted">
              <i class="fas fa-id-card me-1"></i><strong><?= e($estudiante['codigo']) ?></strong>
              &nbsp;|&nbsp;
              <i class="fas fa-layer-group me-1"></i><?= e($estudiante['ciclo_nombre']) ?>
              &nbsp;|&nbsp;
              Seccion <?= e($estudiante['seccion']) ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card blue h-100">
        <div class="stat-value"><?= number_format($totalPuntos) ?></div>
        <div class="stat-label">Puntos Acumulados</div>
        <div class="stat-sub">
          Ranking Global: #<?= $rankGlobal ?> |
          Ciclo: #<?= $rankCiclo ?>
        </div>
        <i class="fas fa-trophy stat-icon"></i>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card green">
        <div class="stat-value"><?= $totalAsistio ?></div>
        <div class="stat-label">Asistencias</div>
        <i class="fas fa-check-circle stat-icon"></i>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card yellow">
        <div class="stat-value"><?= $totalTardanza ?></div>
        <div class="stat-label">Tardanzas</div>
        <i class="fas fa-clock stat-icon"></i>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card red">
        <div class="stat-value"><?= $totalFaltas ?></div>
        <div class="stat-label">Faltas</div>
        <i class="fas fa-times-circle stat-icon"></i>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card purple">
        <div class="stat-value"><?= $totalEventos ?></div>
        <div class="stat-label">Eventos</div>
        <i class="fas fa-calendar stat-icon"></i>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAsistencias">
        <i class="fas fa-list me-1"></i> Mis Asistencias
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRanking">
        <i class="fas fa-trophy me-1"></i> Ranking
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEventos">
        <i class="fas fa-calendar me-1"></i> Eventos
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- Mis asistencias -->
    <div class="tab-pane fade show active" id="tabAsistencias">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.88rem">
              <thead style="background:var(--primary);color:#fff">
                <tr><th>Evento</th><th>Fecha</th><th>Estado</th><th>Puntos</th></tr>
              </thead>
              <tbody>
                <?php if (empty($misAsistencias)): ?>
                <tr><td colspan="4" class="no-results"><i class="fas fa-clipboard"></i>Sin registros aun</td></tr>
                <?php else: ?>
                <?php foreach ($misAsistencias as $a): ?>
                <tr>
                  <td class="fw-600"><?= e($a['evento_nombre']) ?></td>
                  <td><?= fechaES($a['fecha']) ?></td>
                  <td><?= badgeEstado($a['estado']) ?></td>
                  <td><strong class="text-primary"><?= $a['puntos'] ?> pts</strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Ranking -->
    <div class="tab-pane fade" id="tabRanking">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title"><i class="fas fa-trophy"></i> Top 10 Estudiantes</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.88rem">
              <thead style="background:var(--primary);color:#fff">
                <tr><th>Pos</th><th>Nombre</th><th>Ciclo</th><th>Puntos</th></tr>
              </thead>
              <tbody>
                <?php foreach ($topEstudiantes as $i => $top): ?>
                <?php $esYo = $top['codigo'] === $estudiante['codigo']; ?>
                <tr <?= $esYo ? 'style="background:#fffbe6;font-weight:bold"' : '' ?>>
                  <td>
                    <span class="rank-num <?= $i<3?"rank-".($i+1):'' ?>">
                      <?= $i+1 ?>
                    </span>
                    <?= $esYo ? ' <i class="fas fa-arrow-left text-warning" title="Tu posicion"></i>' : '' ?>
                  </td>
                  <td><?= e($top['apellidos'].', '.$top['nombres']) ?></td>
                  <td><?= e($top['ciclo']) ?></td>
                  <td><strong><?= number_format($top['total_puntos']) ?></strong> pts</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php if ($rankGlobal > 10): ?>
      <div class="alert alert-info mt-2">
        Tu posicion actual es <strong>#<?= $rankGlobal ?></strong> con <strong><?= $totalPuntos ?> puntos</strong>.
      </div>
      <?php endif; ?>
    </div>

    <!-- Proximos eventos -->
    <div class="tab-pane fade" id="tabEventos">
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.88rem">
              <thead style="background:var(--primary);color:#fff">
                <tr><th>Evento</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <?php if (empty($eventosProximos)): ?>
                <tr><td colspan="4" class="no-results"><i class="fas fa-calendar-times"></i>Sin eventos proximos</td></tr>
                <?php else: ?>
                <?php foreach ($eventosProximos as $ev): ?>
                <tr>
                  <td class="fw-600"><?= e($ev['nombre']) ?></td>
                  <td><?= fechaES($ev['fecha']) ?></td>
                  <td><?= horaFormatted($ev['hora_inicio']) ?></td>
                  <td><?= badgeEstado($ev['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
