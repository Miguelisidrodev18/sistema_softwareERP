<?php
Auth::requireAdmin();
$pageTitle    = 'Gestion de Eventos';
$ediciones    = DB::fetchAll("SELECT id, nombre FROM ediciones ORDER BY anio DESC");
$delegados    = DB::fetchAll(
    "SELECT id, CONCAT(apellidos, ', ', nombres) as nombre_completo
     FROM usuarios WHERE activo=1 AND rol='delegado_ciclo' ORDER BY apellidos"
);
$delegadosJson = json_encode($delegados);
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-calendar-star"></i> Eventos</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Eventos</li></ol></nav>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEvento"
          onclick="resetModal('modalEvento'); document.getElementById('evId').value=''">
    <i class="fas fa-plus me-1"></i> Nuevo Evento
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaEventos">
        <thead>
          <tr>
            <th>#</th><th>Evento</th><th>Fecha</th><th>Horario</th>
            <th>Tolerancia</th><th>Puntajes</th><th>Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyEventos"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Evento -->
<div class="modal fade" id="modalEvento" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEvTitle"><i class="fas fa-calendar-plus me-2"></i>Nuevo Evento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEvento" novalidate>
          <input type="hidden" id="evId" name="id">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nombre del Evento <span class="text-danger">*</span></label>
              <input type="text" id="evNombre" name="nombre" class="form-control"
                     placeholder="Ej: Concurso de Danza, Campeonato Deportivo..." required maxlength="200">
            </div>
            <div class="col-12">
              <label class="form-label">Descripcion</label>
              <textarea id="evDesc" name="descripcion" class="form-control" rows="2"
                        placeholder="Descripcion del evento..."></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha <span class="text-danger">*</span></label>
              <input type="date" id="evFecha" name="fecha" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Hora de Inicio <span class="text-danger">*</span></label>
              <input type="time" id="evHoraInicio" name="hora_inicio" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Hora de Cierre <span class="text-danger">*</span></label>
              <input type="time" id="evHoraCierre" name="hora_cierre" class="form-control" required>
            </div>

            <div class="col-12"><div class="divider"></div></div>
            <div class="col-12"><h6 class="text-primary fw-bold"><i class="fas fa-clock me-1"></i>Control de Asistencia</h6></div>

            <div class="col-md-6">
              <label class="form-label">Tolerancia para Asistencia (minutos)</label>
              <input type="number" id="evToleranc" name="minutos_tolerancia" class="form-control"
                     value="15" min="0" max="120">
              <div class="form-text">Hasta X min despues de hora_inicio → Asistio</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Limite para Tardanza (minutos)</label>
              <input type="number" id="evTardanza" name="minutos_tardanza" class="form-control"
                     value="30" min="0" max="180">
              <div class="form-text">De X+1 a Y min → Tardanza. Despues → Falta</div>
            </div>

            <div class="col-12"><div class="divider"></div></div>
            <div class="col-12"><h6 class="text-primary fw-bold"><i class="fas fa-star me-1"></i>Puntajes</h6></div>

            <div class="col-md-4">
              <label class="form-label">Puntos por Asistencia</label>
              <input type="number" id="evPAsistio" name="puntaje_asistio" class="form-control"
                     value="3" min="0" max="20">
            </div>
            <div class="col-md-4">
              <label class="form-label">Puntos por Tardanza</label>
              <input type="number" id="evPTardanza" name="puntaje_tardanza" class="form-control"
                     value="1" min="0" max="20">
            </div>
            <div class="col-md-4">
              <label class="form-label">Puntos por Falta</label>
              <input type="number" id="evPFalta" name="puntaje_falta" class="form-control"
                     value="0" min="0" max="20">
            </div>

            <div class="col-md-6">
              <label class="form-label">Edicion</label>
              <select id="evEdicion" name="edicion_id" class="form-select">
                <option value="">— Sin edicion —</option>
                <?php foreach ($ediciones as $ed): ?>
                <option value="<?= $ed['id'] ?>"><?= e($ed['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select id="evEstado" name="estado" class="form-select">
                <option value="programado">Programado</option>
                <option value="activo">Activo</option>
                <option value="finalizado">Finalizado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveEvento()">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Asignar Ciclos por Evento -->
<div class="modal fade" id="modalAsignarCiclos" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-users-cog me-2"></i>Asignar Ciclos por Delegado</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" id="asigEvNombre"></p>
        <input type="hidden" id="asigEventoId">
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-2">
            <thead class="table-light">
              <tr><th>Ciclo</th><th>Delegado Responsable</th></tr>
            </thead>
            <tbody id="tbodyAsignaciones">
              <tr><td colspan="2" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary"></div>
              </td></tr>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info py-2 small mb-0">
          <i class="fas fa-info-circle me-1"></i>
          Selecciona el delegado responsable de cada ciclo para esta actividad.
          Los ciclos sin delegado asignado no apareceran en el registro de asistencia.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveAsignaciones()">
          <i class="fas fa-save me-1"></i> Guardar Asignaciones
        </button>
      </div>
    </div>
  </div>
</div>
<script>const DELEGADOS_LIST = <?= $delegadosJson ?>;</script>

<?php
$extraJs = <<<'JS'
<script>
let dtEventos = null;

$(document).ready(() => {
  dtEventos = initDataTable('tablaEventos', {
    ajax: { url: 'ajax/eventos.php?action=list', dataSrc: 'data' },
    columns: [
      { data: null, render:(d,t,r,m) => m.row+1 },
      { data: 'nombre' },
      { data: 'fecha', render: v => formatDate(v) },
      { data: null, render:(d,t,r) => `${formatTime(r.hora_inicio)} – ${formatTime(r.hora_cierre)}` },
      { data: null, render:(d,t,r) =>
        `<small>✓ ${r.minutos_tolerancia}min<br>⏱ ${r.minutos_tardanza}min</small>` },
      { data: null, render:(d,t,r) =>
        `<small class="text-success">A: ${r.puntaje_asistio}pts</small><br>
         <small class="text-warning">T: ${r.puntaje_tardanza}pts</small>` },
      { data: 'estado', render: v => ({
          programado:'<span class="badge bg-secondary">Programado</span>',
          activo:    '<span class="badge bg-success">Activo</span>',
          finalizado:'<span class="badge bg-dark">Finalizado</span>',
          cancelado: '<span class="badge bg-danger">Cancelado</span>',
        }[v] || v) },
      { data: null, orderable:false, render:(d,t,row) =>
        `<div class="d-flex gap-1 justify-content-center">
          <button class="btn btn-sm btn-outline-primary btn-icon" title="Editar" onclick="editEvento(${row.id})"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-outline-success btn-icon" title="Asignar Ciclos" onclick="abrirAsignacion(${row.id},'${escapeHtml(row.nombre)}')"><i class="fas fa-users-cog"></i></button>
          <button class="btn btn-sm btn-outline-danger btn-icon" title="Eliminar" onclick="deleteEvento(${row.id},'${escapeHtml(row.nombre)}')"><i class="fas fa-trash"></i></button>
        </div>`
      }
    ],
    order: [[2,'desc']]
  });
});

async function editEvento(id) {
  const res = await Api.get('ajax/eventos.php', { action:'get', id });
  if (!res.success) { Toast.error(res.message); return; }
  const ev = res.data;
  document.getElementById('evId').value          = ev.id;
  document.getElementById('evNombre').value      = ev.nombre;
  document.getElementById('evDesc').value        = ev.descripcion||'';
  document.getElementById('evFecha').value       = ev.fecha;
  document.getElementById('evHoraInicio').value  = ev.hora_inicio.slice(0,5);
  document.getElementById('evHoraCierre').value  = ev.hora_cierre.slice(0,5);
  document.getElementById('evToleranc').value    = ev.minutos_tolerancia;
  document.getElementById('evTardanza').value    = ev.minutos_tardanza;
  document.getElementById('evPAsistio').value    = ev.puntaje_asistio;
  document.getElementById('evPTardanza').value   = ev.puntaje_tardanza;
  document.getElementById('evPFalta').value      = ev.puntaje_falta;
  document.getElementById('evEdicion').value     = ev.edicion_id||'';
  document.getElementById('evEstado').value      = ev.estado;
  document.getElementById('modalEvTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Evento';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).show();
}

async function saveEvento() {
  if (!validateForm('formEvento')) return;
  Loading.show();
  try {
    const f = id => document.getElementById(id).value;
    const res = await Api.post('ajax/eventos.php', {
      action: 'save',
      id: f('evId'), nombre: f('evNombre'), descripcion: f('evDesc'),
      fecha: f('evFecha'), hora_inicio: f('evHoraInicio'), hora_cierre: f('evHoraCierre'),
      minutos_tolerancia: f('evToleranc'), minutos_tardanza: f('evTardanza'),
      puntaje_asistio: f('evPAsistio'), puntaje_tardanza: f('evPTardanza'), puntaje_falta: f('evPFalta'),
      edicion_id: f('evEdicion'), estado: f('evEstado'),
    });
    if (res.success) {
      Toast.success(res.message);
      bootstrap.Modal.getInstance(document.getElementById('modalEvento')).hide();
      dtEventos.ajax.reload();
    } else Toast.error(res.message);
  } catch(e) { Toast.error('Error: '+e.message); }
  finally { Loading.hide(); }
}

function deleteEvento(id, nombre) {
  confirmDelete(`¿Eliminar el evento "${nombre}"?`, async () => {
    Loading.show();
    const res = await Api.post('ajax/eventos.php', { action:'delete', id });
    Loading.hide();
    if (res.success) { Toast.success(res.message); dtEventos.ajax.reload(); }
    else Toast.error(res.message);
  });
}

async function abrirAsignacion(eventoId, eventoNombre) {
  document.getElementById('asigEventoId').value = eventoId;
  document.getElementById('asigEvNombre').innerHTML =
    '<strong>Evento:</strong> ' + escapeHtml(eventoNombre);
  document.getElementById('tbodyAsignaciones').innerHTML =
    '<tr><td colspan="2" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAsignarCiclos')).show();
  const res = await Api.get('ajax/eventos.php', { action: 'get_asignaciones', evento_id: eventoId });
  if (!res.success) {
    document.getElementById('tbodyAsignaciones').innerHTML =
      '<tr><td colspan="2" class="text-danger text-center py-2">' + escapeHtml(res.message) + '</td></tr>';
    return;
  }
  document.getElementById('tbodyAsignaciones').innerHTML = res.data.map(row =>
    `<tr>
      <td class="fw-bold">${escapeHtml(row.ciclo_nombre)}</td>
      <td>
        <select class="form-select form-select-sm" data-ciclo="${row.ciclo_id}">
          <option value="">— Sin asignar —</option>
          ${DELEGADOS_LIST.map(d =>
            `<option value="${d.id}" ${d.id == row.delegado_id ? 'selected' : ''}>${escapeHtml(d.nombre_completo)}</option>`
          ).join('')}
        </select>
      </td>
    </tr>`
  ).join('');
}

async function saveAsignaciones() {
  const eventoId = document.getElementById('asigEventoId').value;
  const asignaciones = [];
  document.querySelectorAll('#tbodyAsignaciones select[data-ciclo]').forEach(sel => {
    if (sel.value) asignaciones.push({ ciclo_id: sel.dataset.ciclo, delegado_id: sel.value });
  });
  Loading.show();
  try {
    const res = await Api.post('ajax/eventos.php', {
      action: 'save_asignaciones',
      evento_id: eventoId,
      asignaciones: JSON.stringify(asignaciones),
    });
    if (res.success) {
      Toast.success(res.message);
      bootstrap.Modal.getInstance(document.getElementById('modalAsignarCiclos')).hide();
    } else Toast.error(res.message);
  } catch(e) { Toast.error('Error: ' + e.message); }
  finally { Loading.hide(); }
}
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
