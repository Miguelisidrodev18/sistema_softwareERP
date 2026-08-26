<?php
Auth::requireAdmin();
$pageTitle = 'Configuracion del Sistema';
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    csrfCheck();
    $configs = [
        'nombre_sistema'     => trim($_POST['nombre_sistema'] ?? ''),
        'nombre_universidad' => trim($_POST['nombre_universidad'] ?? ''),
        'nombre_facultad'    => trim($_POST['nombre_facultad'] ?? ''),
        'puntaje_asistio'    => (int)($_POST['puntaje_asistio'] ?? 3),
        'puntaje_tardanza'   => (int)($_POST['puntaje_tardanza'] ?? 1),
        'puntaje_falta'      => (int)($_POST['puntaje_falta'] ?? 0),
        'minutos_tolerancia' => (int)($_POST['minutos_tolerancia'] ?? 15),
        'minutos_tardanza'   => (int)($_POST['minutos_tardanza'] ?? 30),
    ];
    foreach ($configs as $k => $v) {
        DB::query(
            "INSERT INTO configuracion (clave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)",
            [$k, $v]
        );
    }
    audit('config_actualizada', 'configuracion', 0, 'Configuracion del sistema actualizada');
    $msg = 'Configuracion guardada correctamente.';
}

$cfg = [];
$rows = DB::fetchAll("SELECT clave, valor FROM configuracion");
foreach ($rows as $r) $cfg[$r['clave']] = $r['valor'];

include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-cog"></i> Configuracion</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Configuracion</li></ol></nav>
  </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-success alert-auto-dismiss">
  <i class="fas fa-check-circle me-2"></i><?= e($msg) ?>
</div>
<?php endif; ?>

<form method="post">
  <?= '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">' ?>
  <input type="hidden" name="save_config" value="1">

  <div class="row g-4">
    <!-- Informacion del sistema -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title"><i class="fas fa-university"></i> Informacion Institucional</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Nombre del Sistema</label>
            <input type="text" name="nombre_sistema" class="form-control"
                   value="<?= e($cfg['nombre_sistema'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Universidad</label>
            <input type="text" name="nombre_universidad" class="form-control"
                   value="<?= e($cfg['nombre_universidad'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Facultad / Escuela Profesional</label>
            <input type="text" name="nombre_facultad" class="form-control"
                   value="<?= e($cfg['nombre_facultad'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Puntajes por defecto -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="card-title"><i class="fas fa-star"></i> Puntajes por Defecto</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">
            Estos valores se usan como valor inicial al crear nuevos eventos.
            Cada evento puede tener sus propios puntajes.
          </p>
          <div class="row g-3">
            <div class="col-4">
              <label class="form-label text-success fw-bold">Asistio</label>
              <div class="input-group">
                <input type="number" name="puntaje_asistio" class="form-control"
                       value="<?= (int)($cfg['puntaje_asistio'] ?? 3) ?>" min="0" max="20">
                <span class="input-group-text">pts</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label text-warning fw-bold">Tardanza</label>
              <div class="input-group">
                <input type="number" name="puntaje_tardanza" class="form-control"
                       value="<?= (int)($cfg['puntaje_tardanza'] ?? 1) ?>" min="0" max="20">
                <span class="input-group-text">pts</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label text-danger fw-bold">Falta</label>
              <div class="input-group">
                <input type="number" name="puntaje_falta" class="form-control"
                       value="<?= (int)($cfg['puntaje_falta'] ?? 0) ?>" min="0" max="20">
                <span class="input-group-text">pts</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tiempos de asistencia -->
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title"><i class="fas fa-clock"></i> Tiempos de Asistencia por Defecto</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">
            Ejemplo: Si el evento inicia a las 08:00 y la tolerancia es 15 min y tardanza 30 min:<br>
            <strong>Hasta 08:15</strong> = Asistio |
            <strong>08:16 – 08:30</strong> = Tardanza |
            <strong>Despues de 08:30</strong> = Falta
          </p>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-bold">Tolerancia para Asistencia</label>
              <div class="input-group">
                <input type="number" name="minutos_tolerancia" class="form-control"
                       value="<?= (int)($cfg['minutos_tolerancia'] ?? 15) ?>" min="0" max="120">
                <span class="input-group-text">minutos</span>
              </div>
              <div class="form-text">Minutos despues de hora de inicio para asistencia puntual.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Limite para Tardanza</label>
              <div class="input-group">
                <input type="number" name="minutos_tardanza" class="form-control"
                       value="<?= (int)($cfg['minutos_tardanza'] ?? 30) ?>" min="0" max="180">
                <span class="input-group-text">minutos</span>
              </div>
              <div class="form-text">Minutos maximos para registrar tardanza.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="alert alert-info w-100 small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Estos valores son reemplazados por la configuracion individual de cada evento.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 text-end">
      <button type="submit" class="btn btn-primary px-4 py-2">
        <i class="fas fa-save me-2"></i> Guardar Configuracion
      </button>
    </div>
  </div>
</form>

<?php include VIEWS_PATH . '/layout/footer.php'; ?>
