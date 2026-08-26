<?php
Auth::requireDelegate();
$pageTitle = 'Registrar Asistencia';

$eventoId    = (int)($_GET['evento'] ?? 0);
$eventoActivo = null;
$ciclosEvento = [];

$eventos = DB::fetchAll(
    "SELECT id, nombre, fecha, hora_inicio, hora_cierre, estado,
            minutos_tolerancia, minutos_tardanza,
            puntaje_asistio, puntaje_tardanza, puntaje_falta
     FROM eventos
     WHERE estado IN ('activo','programado')
     ORDER BY fecha DESC, hora_inicio DESC"
);

if ($eventoId) {
    $eventoActivo = DB::fetchOne("SELECT * FROM eventos WHERE id = ?", [$eventoId]);
    if ($eventoActivo) {
        if (Auth::isAdmin()) {
            $ciclosEvento = DB::fetchAll(
                "SELECT id, nombre FROM ciclos WHERE activo=1 ORDER BY orden"
            );
        } else {
            $ciclosEvento = DB::fetchAll(
                "SELECT c.id, c.nombre FROM ciclos c
                 JOIN evento_ciclo_delegado ecd ON ecd.ciclo_id = c.id
                 WHERE ecd.evento_id = ? AND ecd.delegado_id = ? AND c.activo = 1
                 ORDER BY c.orden",
                [$eventoActivo['id'], Auth::id()]
            );
        }
    }
}

include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-clipboard-check"></i> Registrar Asistencia</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php?p=delegate/dashboard">Inicio</a></li>
      <li class="breadcrumb-item active">Asistencia</li>
    </ol></nav>
  </div>
</div>

<!-- Selector de evento -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title"><i class="fas fa-calendar-alt"></i> Seleccionar Evento</h5>
  </div>
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <input type="hidden" name="p" value="delegate/asistencia">
      <div class="col-md-8">
        <label class="form-label">Evento</label>
        <select name="evento" class="form-select">
          <option value="">— Seleccionar evento —</option>
          <?php foreach ($eventos as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $eventoId==$ev['id']?'selected':'' ?>>
            <?= e($ev['nombre']) ?> — <?= fechaES($ev['fecha']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-1"></i> Cargar
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($eventoActivo): ?>

<!-- Info del evento -->
<div class="alert alert-info mb-3">
  <div class="row align-items-center">
    <div class="col-md-8">
      <strong><i class="fas fa-calendar me-1"></i><?= e($eventoActivo['nombre']) ?></strong><br>
      <small>
        <?= fechaES($eventoActivo['fecha']) ?> —
        <?= horaFormatted($eventoActivo['hora_inicio']) ?> a <?= horaFormatted($eventoActivo['hora_cierre']) ?><br>
        Tolerancia: <strong><?= $eventoActivo['minutos_tolerancia'] ?> min</strong> |
        Max Tardanza: <strong><?= $eventoActivo['minutos_tardanza'] ?> min</strong>
      </small>
    </div>
    <div class="col-md-4 text-md-end mt-2 mt-md-0">
      <div class="d-flex gap-2 justify-content-md-end flex-wrap">
        <span class="badge bg-success fs-7">A=<?= $eventoActivo['puntaje_asistio'] ?>pts</span>
        <span class="badge bg-warning text-dark fs-7">T=<?= $eventoActivo['puntaje_tardanza'] ?>pts</span>
        <span class="badge bg-danger fs-7">F=<?= $eventoActivo['puntaje_falta'] ?>pts</span>
      </div>
    </div>
  </div>
</div>

<?php if (empty($ciclosEvento) && !Auth::isAdmin()): ?>
<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle me-2"></i>
  No tienes ciclos asignados para este evento. Contacta al Delegado Pleno para que te asigne.
</div>
<?php else: ?>

<!-- Filtros por ciclo -->
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <span class="fw-bold me-1">Ciclo:</span>
    <button class="btn btn-sm btn-primary filter-ciclo active" data-ciclo="0">Todos</button>
    <?php foreach ($ciclosEvento as $c): ?>
    <button class="btn btn-sm btn-outline-primary filter-ciclo" data-ciclo="<?= $c['id'] ?>">
      <?= e($c['nombre']) ?>
    </button>
    <?php endforeach; ?>
  </div>
</div>

<!-- Buscador -->
<div class="card mb-3">
  <div class="card-body py-2">
    <div class="input-group">
      <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
      <input type="text" id="buscarEst" class="form-control"
             placeholder="Buscar por codigo, DNI o apellidos y nombres...">
      <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBuscar"
              title="Limpiar" style="display:none">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</div>

<!-- Acciones masivas -->
<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <span id="autoEstadoLabel" class="text-muted small me-2"></span>
    <span class="fw-bold">Marcar:</span>
    <button class="btn btn-sm btn-outline-success" onclick="marcarTodos()">
      <i class="fas fa-user-check me-1"></i> Todos Presentes
    </button>
    <div class="ms-auto">
      <button class="btn btn-primary fw-bold" onclick="guardarTodo()">
        <i class="fas fa-save me-1"></i> Guardar Asistencias
      </button>
    </div>
  </div>
</div>

<!-- Tabla de asistencia -->
<div class="card">
  <div class="card-header justify-content-between">
    <h5 class="card-title"><i class="fas fa-users"></i> Estudiantes</h5>
    <span id="countEst" class="badge bg-primary">0 estudiantes</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0">
        <thead>
          <tr>
            <th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th><th>Secc.</th>
            <th>Asistencia</th><th>Estado Actual</th>
          </tr>
        </thead>
        <tbody id="tbodyAsistencia">
          <tr><td colspan="6" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; // endif: ciclosEvento not empty or admin ?>
<?php endif; // endif: eventoActivo ?>

<?php
$eventoIdJs      = $eventoActivo ? $eventoActivo['id']                      : 0;
$evHoraInicioJs  = $eventoActivo ? $eventoActivo['hora_inicio']             : '00:00:00';
$evTolJsVal      = $eventoActivo ? (int)$eventoActivo['minutos_tolerancia'] : 0;
$evTardJsVal     = $eventoActivo ? (int)$eventoActivo['minutos_tardanza']   : 0;
$extraJs = <<<JS
<script>
const EVENTO_ID      = {$eventoIdJs};
const EV_HORA_INICIO = '{$evHoraInicioJs}'; // "HH:MM:SS"
const EV_MIN_TOL     = {$evTolJsVal};       // tolerancia puntual
const EV_MIN_TARD    = {$evTardJsVal};      // limite tardanza
let asistenciaData   = {};                  // { id: 'asistio'|'tardanza' } — solo marcas de esta sesion

if (EVENTO_ID && document.getElementById('tbodyAsistencia')) {
  cargarEstudiantes();
  setInterval(actualizarIndicador, 15000);
}

// Calcula el estado que corresponde AHORA segun la hora del evento
function getAutoEstado() {
  const now    = new Date();
  const parts  = EV_HORA_INICIO.split(':').map(Number);
  const inicio = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parts[0], parts[1], parts[2]||0);
  const limAsistio  = new Date(inicio.getTime() + EV_MIN_TOL  * 60000);
  const limTardanza = new Date(inicio.getTime() + EV_MIN_TARD * 60000);
  if (now <= limAsistio)  return 'asistio';
  if (now <= limTardanza) return 'tardanza';
  return null; // Fuera de ventana
}

// Refresca el badge "Estado actual" y bloquea botones si ya cerro la ventana
function actualizarIndicador() {
  const autoE = getAutoEstado();
  const el    = document.getElementById('autoEstadoLabel');
  if (el) {
    if (autoE === 'asistio') {
      el.innerHTML = 'Estado actual: <span class="badge bg-success">Asistio</span>';
    } else if (autoE === 'tardanza') {
      el.innerHTML = 'Estado actual: <span class="badge bg-warning text-dark">Tardanza</span>';
    } else {
      el.innerHTML = '<span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Registro cerrado</span>';
    }
  }
  if (!autoE) {
    document.querySelectorAll('#tbodyAsistencia .btn-presente:not([disabled])').forEach(btn => {
      btn.disabled = true;
      btn.className = 'btn btn-sm btn-secondary btn-presente';
      btn.innerHTML = '<i class="fas fa-lock me-1"></i> Cerrado';
    });
  }
}

async function cargarEstudiantes() {
  try {
    const res = await Api.get('ajax/asistencias.php', {
      action: 'get_estudiantes',
      evento_id: EVENTO_ID,
    });
    if (!res.success) {
      document.getElementById('tbodyAsistencia').innerHTML =
        '<tr><td colspan="6" class="text-center text-danger py-3"><i class="fas fa-exclamation-circle me-2"></i>'+res.message+'</td></tr>';
      return;
    }
    asistenciaData = {};
    actualizarIndicador();
    renderRows(res.data.rows);
  } catch (err) {
    document.getElementById('tbodyAsistencia').innerHTML =
      '<tr><td colspan="6" class="text-center text-danger py-3"><i class="fas fa-exclamation-circle me-2"></i>Error al cargar. Recarga la pagina.</td></tr>';
  }
}

function renderRows(rows) {
  const tbody = document.getElementById('tbodyAsistencia');
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="no-results"><i class="fas fa-users-slash"></i>Sin estudiantes asignados para este evento</td></tr>';
    document.getElementById('countEst').textContent = '0 estudiantes';
    return;
  }
  const locked = getAutoEstado() === null;
  tbody.innerHTML = rows.map(r => {
    const estadoBadge = r.estado
      ? '<span class="badge bg-'+({asistio:'success',tardanza:'warning',falta:'danger'}[r.estado]||'secondary')+' text-'+(r.estado==='tardanza'?'dark':'white')+'">'+r.estado+'</span>'
      : '<span class="text-muted small">Sin registro</span>';
    let btnHtml;
    if (r.estado) {
      btnHtml = '<span class="text-muted small"><i class="fas fa-check-circle me-1"></i>Registrado</span>';
    } else if (locked) {
      btnHtml = '<button class="btn btn-sm btn-secondary btn-presente" disabled><i class="fas fa-lock me-1"></i> Cerrado</button>';
    } else {
      btnHtml = `<button class="btn btn-sm btn-outline-success btn-presente" onclick="marcarPresente(\${r.id},this)"><i class="fas fa-user-check me-1"></i> Presente</button>`;
    }
    return `<tr class="attendance-row" data-id="\${r.id}" data-ciclo="\${r.ciclo_id}"
                data-search="\${(r.codigo+' '+r.apellidos+' '+r.nombres).toLowerCase()}"
                data-saved="\${r.estado||''}">
      <td class="fw-600">\${escapeHtml(r.codigo)}</td>
      <td>\${escapeHtml(r.apellidos+', '+r.nombres)}</td>
      <td>\${escapeHtml(r.ciclo_nombre)}</td>
      <td>\${escapeHtml(r.seccion)}</td>
      <td>\${btnHtml}</td>
      <td id="est_\${r.id}">\${estadoBadge}</td>
    </tr>`;
  }).join('');
  document.getElementById('countEst').textContent = rows.length + ' estudiantes';
}

// El delegado marca a un estudiante como presente; el estado (asistio/tardanza) lo determina la hora
function marcarPresente(id, btn) {
  const estado = getAutoEstado();
  if (!estado) { Toast.warning('El tiempo de registro ha cerrado.'); return; }
  asistenciaData[id] = estado;
  const esBueno = estado === 'asistio';
  btn.className = 'btn btn-sm '+(esBueno ? 'btn-success' : 'btn-warning')+' btn-presente';
  btn.disabled  = true;
  btn.innerHTML = '<i class="fas fa-check me-1"></i> '+(esBueno ? 'Asistio' : 'Tardanza');
  const cell = document.getElementById('est_'+id);
  if (cell) {
    const bc = esBueno ? 'success' : 'warning';
    const tc = esBueno ? 'white'   : 'dark';
    cell.innerHTML = `<span class="badge bg-\${bc} text-\${tc}">\${estado}</span>`;
  }
}

// Marca como presentes todos los estudiantes visibles aun no registrados
function marcarTodos() {
  const estado = getAutoEstado();
  if (!estado) { Toast.warning('El tiempo de registro ha cerrado.'); return; }
  document.querySelectorAll('#tbodyAsistencia tr[data-id]:not([style*="display:none"])').forEach(row => {
    if (row.dataset.saved || asistenciaData[row.dataset.id]) return;
    const btn = row.querySelector('.btn-presente:not([disabled])');
    if (btn) marcarPresente(parseInt(row.dataset.id), btn);
  });
}

// Guarda:
//  - Dentro de ventana: solo los marcados como presentes
//  - Cerrada la ventana: marcados + falta automatica para todos los no registrados
async function guardarTodo() {
  const isPastWindow = getAutoEstado() === null;
  const registros    = [];
  document.querySelectorAll('#tbodyAsistencia tr[data-id]').forEach(row => {
    if (row.dataset.saved) return; // Ya en BD
    const id    = parseInt(row.dataset.id);
    const estado = asistenciaData[id];
    if (estado) {
      registros.push({ id, estado });
    } else if (isPastWindow) {
      registros.push({ id, estado: 'falta' }); // Auto-falta al cerrar ventana
    }
  });
  if (!registros.length) {
    Toast.warning(isPastWindow ? 'Todos los estudiantes ya tienen registro.' : 'No has marcado ningun estudiante todavia.');
    return;
  }
  Loading.show();
  const res = await Api.post('ajax/asistencias.php', {
    action: 'save_bulk',
    evento_id: EVENTO_ID,
    registros: JSON.stringify(registros),
  });
  Loading.hide();
  if (res.success) {
    Toast.success(res.message);
    setTimeout(() => cargarEstudiantes(), 1000);
  } else Toast.error(res.message);
}

// Filtro por ciclo
document.querySelectorAll('.filter-ciclo').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-ciclo').forEach(b => {
      b.classList.remove('active','btn-primary'); b.classList.add('btn-outline-primary');
    });
    btn.classList.add('active','btn-primary'); btn.classList.remove('btn-outline-primary');
    const cicloId = btn.dataset.ciclo;
    document.querySelectorAll('#tbodyAsistencia tr[data-id]').forEach(row => {
      row.style.display = (cicloId==='0' || row.dataset.ciclo===cicloId) ? '' : 'none';
    });
  });
});

// Busqueda en tiempo real (codigo, dni, apellidos, nombres)
const inputBuscar  = document.getElementById('buscarEst');
const btnLimpiar   = document.getElementById('btnLimpiarBuscar');

function aplicarBusqueda() {
  const q = inputBuscar.value.toLowerCase().trim();
  btnLimpiar.style.display = q ? '' : 'none';
  document.querySelectorAll('#tbodyAsistencia tr[data-id]').forEach(row => {
    row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
  });
}

inputBuscar?.addEventListener('input', aplicarBusqueda);
btnLimpiar?.addEventListener('click', () => {
  inputBuscar.value = '';
  aplicarBusqueda();
  inputBuscar.focus();
});
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';