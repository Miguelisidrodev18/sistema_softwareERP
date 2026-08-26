<?php
Auth::requireAdmin();
$pageTitle    = 'Registro de Asistencias';
$eventos      = DB::fetchAll("SELECT id, nombre, fecha, hora_inicio, estado FROM eventos ORDER BY fecha DESC, hora_inicio DESC");
$eventoActivo = null;
$ciclos       = [];
$estudiantes  = [];

$eventoId = (int)($_GET['evento'] ?? 0);
if ($eventoId) {
    $eventoActivo = DB::fetchOne("SELECT * FROM eventos WHERE id = ?", [$eventoId]);
    if ($eventoActivo) {
        $ciclos = DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo=1 ORDER BY orden");
    }
}
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-clipboard-check"></i> Registro de Asistencias</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Asistencias</li></ol></nav>
  </div>
</div>

<!-- Selector de evento -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Seleccionar Evento</h5>
  </div>
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="p" value="admin/asistencias">
      <div class="col-md-8">
        <label class="form-label">Evento</label>
        <select name="evento" class="form-select" required>
          <option value="">— Seleccionar evento —</option>
          <?php foreach ($eventos as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $eventoId==$ev['id']?'selected':'' ?>>
            <?= e($ev['nombre']) ?> — <?= fechaES($ev['fecha']) ?>
            (<?= badgeEstado($ev['estado']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-1"></i> Cargar Estudiantes
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($eventoActivo): ?>
<!-- Info del evento -->
<div class="alert alert-info d-flex align-items-start gap-3 mb-3">
  <i class="fas fa-info-circle fa-lg mt-1"></i>
  <div>
    <strong><?= e($eventoActivo['nombre']) ?></strong> —
    <?= fechaES($eventoActivo['fecha']) ?>,
    <?= horaFormatted($eventoActivo['hora_inicio']) ?> a <?= horaFormatted($eventoActivo['hora_cierre']) ?><br>
    <small>
      Tolerancia: <strong><?= $eventoActivo['minutos_tolerancia'] ?> min</strong> |
      Tardanza hasta: <strong><?= $eventoActivo['minutos_tardanza'] ?> min</strong> |
      Puntajes: Asistio=<strong><?= $eventoActivo['puntaje_asistio'] ?></strong>
               Tardanza=<strong><?= $eventoActivo['puntaje_tardanza'] ?></strong>
               Falta=<strong><?= $eventoActivo['puntaje_falta'] ?></strong>
    </small>
  </div>
</div>

<!-- Filtro por ciclo -->
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <span class="fw-bold me-2">Filtrar por Ciclo:</span>
    <button class="btn btn-sm btn-primary filter-ciclo active" data-ciclo="0">Todos</button>
    <?php foreach ($ciclos as $c): ?>
    <button class="btn btn-sm btn-outline-primary filter-ciclo" data-ciclo="<?= $c['id'] ?>">
      <?= e($c['nombre']) ?>
    </button>
    <?php endforeach; ?>
    <div class="ms-auto">
      <input type="text" id="buscarEstudiante" class="form-control form-control-sm"
             placeholder="Buscar por nombre o codigo..." style="min-width:220px">
    </div>
  </div>
</div>

<!-- Acciones masivas -->
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <span class="fw-bold">Marcar todo como:</span>
    <button class="btn btn-sm btn-success" onclick="marcarTodos('asistio')">
      <i class="fas fa-check me-1"></i> Asistio
    </button>
    <button class="btn btn-sm btn-warning" onclick="marcarTodos('tardanza')">
      <i class="fas fa-clock me-1"></i> Tardanza
    </button>
    <button class="btn btn-sm btn-danger" onclick="marcarTodos('falta')">
      <i class="fas fa-times me-1"></i> Falta
    </button>
    <div class="ms-auto">
      <button class="btn btn-primary" onclick="guardarTodo()" id="btnGuardar">
        <i class="fas fa-save me-1"></i> Guardar Todo
      </button>
    </div>
  </div>
</div>

<!-- Tabla de estudiantes -->
<div class="card">
  <div class="card-header justify-content-between">
    <h5 class="card-title"><i class="fas fa-users"></i> Estudiantes</h5>
    <span id="countVisible" class="badge bg-primary"></span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaAsistencia">
        <thead>
          <tr>
            <th style="width:40px"><input type="checkbox" id="selAll" class="form-check-input"></th>
            <th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th><th>Seccion</th>
            <th>Asistencia</th><th>Hora</th><th>Registrado por</th>
          </tr>
        </thead>
        <tbody id="tbodyAsistencia">
          <tr><td colspan="8" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$eventoIdJs    = $eventoActivo ? $eventoActivo['id'] : 0;
$pAsistio      = $eventoActivo ? $eventoActivo['puntaje_asistio']  : 3;
$pTardanza     = $eventoActivo ? $eventoActivo['puntaje_tardanza'] : 1;
$pFalta        = $eventoActivo ? $eventoActivo['puntaje_falta']    : 0;
$extraJs = <<<JS
<script>
const EVENTO_ID = {$eventoIdJs};
let asistenciaData = {};

if (EVENTO_ID) {
  cargarEstudiantes();
}

async function cargarEstudiantes() {
  const res = await Api.get('ajax/asistencias.php', { action:'get_estudiantes', evento_id: EVENTO_ID });
  if (!res.success) { document.getElementById('tbodyAsistencia').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar estudiantes</td></tr>'; return; }
  const rows = res.data;
  asistenciaData = {};
  rows.forEach(r => { if (r.estado) asistenciaData[r.id] = { estado: r.estado, hora: r.hora_llegada }; });
  renderRows(rows);
  updateCount();
}

function renderRows(rows) {
  const tbody = document.getElementById('tbodyAsistencia');
  if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="no-results"><i class="fas fa-users-slash"></i>Sin estudiantes</td></tr>'; return; }
  tbody.innerHTML = rows.map(r => {
    const est = asistenciaData[r.id] || {};
    const s = est.estado || '';
    return `<tr class="attendance-row" data-id="${r.id}" data-ciclo="${r.ciclo_id}" data-search="${(r.codigo+' '+r.apellidos+' '+r.nombres).toLowerCase()}">
      <td><input type="checkbox" class="form-check-input row-check" value="${r.id}"></td>
      <td class="fw-600">${escapeHtml(r.codigo)}</td>
      <td>${escapeHtml(r.apellidos+', '+r.nombres)}</td>
      <td>${escapeHtml(r.ciclo_nombre)}</td>
      <td>${escapeHtml(r.seccion)}</td>
      <td>
        <div class="btn-group attendance-btn-group" role="group">
          <button type="button" class="btn btn-asistio btn-sm ${s==='asistio'?'selected':''}"
                  onclick="setEstado(${r.id},'asistio',this)"><i class="fas fa-check me-1"></i>Asistio</button>
          <button type="button" class="btn btn-tardanza btn-sm ${s==='tardanza'?'selected':''}"
                  onclick="setEstado(${r.id},'tardanza',this)"><i class="fas fa-clock me-1"></i>Tardanza</button>
          <button type="button" class="btn btn-falta btn-sm ${s==='falta'?'selected':''}"
                  onclick="setEstado(${r.id},'falta',this)"><i class="fas fa-times me-1"></i>Falta</button>
        </div>
      </td>
      <td id="hora_${r.id}">${est.hora||''}</td>
      <td id="reg_${r.id}">${escapeHtml(r.registrado_por||'')}</td>
    </tr>`;
  }).join('');
  updateCount();
}

function setEstado(id, estado, btn) {
  const row = document.querySelector(`tr[data-id="${id}"]`);
  row.querySelectorAll('.attendance-btn-group .btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  if (!asistenciaData[id]) asistenciaData[id] = {};
  asistenciaData[id].estado = estado;
}

function marcarTodos(estado) {
  document.querySelectorAll('#tbodyAsistencia tr[data-id]:not([style*="display:none"])').forEach(row => {
    const id = row.dataset.id;
    if (!asistenciaData[id]) asistenciaData[id] = {};
    asistenciaData[id].estado = estado;
    row.querySelectorAll('.attendance-btn-group .btn').forEach(b => b.classList.remove('selected'));
    row.querySelector('.btn-'+estado)?.classList.add('selected');
  });
}

async function guardarTodo() {
  const registros = Object.entries(asistenciaData)
    .filter(([,v]) => v.estado)
    .map(([id, v]) => ({ id, estado: v.estado }));
  if (!registros.length) { Toast.warning('Sin cambios para guardar.'); return; }
  Loading.show();
  try {
    const res = await Api.post('ajax/asistencias.php', {
      action: 'save_bulk',
      evento_id: EVENTO_ID,
      registros: JSON.stringify(registros),
    });
    if (res.success) Toast.success(res.message+' ('+registros.length+' registros)');
    else Toast.error(res.message);
  } catch(e) { Toast.error('Error: '+e.message); }
  finally { Loading.hide(); }
}

// Filtrar por ciclo
document.querySelectorAll('.filter-ciclo').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-ciclo').forEach(b => b.classList.remove('active','btn-primary'));
    btn.classList.add('active','btn-primary');
    const cicloId = btn.dataset.ciclo;
    document.querySelectorAll('#tbodyAsistencia tr[data-id]').forEach(row => {
      row.style.display = (cicloId=='0' || row.dataset.ciclo==cicloId) ? '' : 'none';
    });
    updateCount();
  });
});

// Busqueda en tiempo real
document.getElementById('buscarEstudiante')?.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('#tbodyAsistencia tr[data-id]').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
  updateCount();
});

// Select all
document.getElementById('selAll')?.addEventListener('change', function() {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});

function updateCount() {
  const vis = document.querySelectorAll('#tbodyAsistencia tr[data-id]:not([style*="display:none"])').length;
  const el = document.getElementById('countVisible');
  if (el) el.textContent = vis + ' estudiantes';
}
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
