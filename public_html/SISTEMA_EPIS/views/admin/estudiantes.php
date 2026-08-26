<?php
Auth::requireAdmin();
$pageTitle = 'Gestion de Estudiantes';
$ciclos = DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo=1 ORDER BY orden");
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-user-graduate"></i> Estudiantes</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Estudiantes</li></ol></nav>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="ajax/export.php?tipo=plantilla_estudiantes" class="btn btn-outline-success">
      <i class="fas fa-file-excel me-1"></i> Descargar Plantilla
    </a>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImport">
      <i class="fas fa-upload me-1"></i> Importar Excel
    </button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEstudiante"
            onclick="resetModal('modalEstudiante'); document.getElementById('estId').value=''">
      <i class="fas fa-plus me-1"></i> Nuevo Estudiante
    </button>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaEstudiantes">
        <thead>
          <tr>
            <th>#</th><th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th>
            <th>Seccion</th><th>Puntos</th><th>Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyEstudiantes">
          <!-- Cargado via AJAX -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Estudiante -->
<div class="modal fade" id="modalEstudiante" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEstTitle"><i class="fas fa-user-graduate me-2"></i>Nuevo Estudiante</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEstudiante" novalidate>
          <input type="hidden" id="estId" name="id">
          <div class="row g-2">
            <div class="col-12">
              <label class="form-label">Codigo Universitario <span class="text-danger">*</span></label>
              <input type="text" id="estCodigo" name="codigo" class="form-control"
                     placeholder="Ej: 20230001" required maxlength="20">
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellidos <span class="text-danger">*</span></label>
              <input type="text" id="estApellidos" name="apellidos" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombres <span class="text-danger">*</span></label>
              <input type="text" id="estNombres" name="nombres" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ciclo <span class="text-danger">*</span></label>
              <select id="estCiclo" name="ciclo_id" class="form-select" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($ciclos as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Seccion <span class="text-danger">*</span></label>
              <input type="text" id="estSeccion" name="seccion" class="form-control"
                     placeholder="A" maxlength="10" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select id="estActivo" name="activo" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveEstudiante()">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImport" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-file-excel me-2"></i>Importar Estudiantes desde Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info small">
          <i class="fas fa-info-circle me-1"></i>
          El archivo debe tener las columnas: <strong>Codigo, Apellidos y Nombres, Ciclo, Seccion</strong><br>
          <strong>Apellidos y Nombres</strong> van juntos separados por coma: <em>Perez Gomez, Juan Carlos</em><br>
          La primera fila se considera cabecera y se omite.<br>
          El nombre del ciclo debe coincidir exactamente con un ciclo registrado.
        </div>
        <form id="formImport" novalidate enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Archivo Excel (.xlsx) <span class="text-danger">*</span></label>
            <input type="file" id="archivoExcel" name="archivo" class="form-control" accept=".xlsx" required>
          </div>
          <div id="importResult" class="d-none"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="importarEstudiantes()">
          <i class="fas fa-upload me-1"></i> Importar
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
let dtEstudiantes = null;

$(document).ready(() => {
  dtEstudiantes = initDataTable('tablaEstudiantes', {
    ajax: { url: 'ajax/estudiantes.php?action=list', dataSrc: 'data' },
    columns: [
      { data: null, render: (d,t,r,m) => m.row + 1 },
      { data: 'codigo' },
      { data: 'apellidos_nombres' },
      { data: 'ciclo_nombre' },
      { data: 'seccion' },
      { data: 'total_puntos', render: v => `<span class="badge bg-primary">${v||0} pts</span>` },
      { data: 'activo', render: v => v=='1'
          ? '<span class="badge bg-success">Activo</span>'
          : '<span class="badge bg-secondary">Inactivo</span>' },
      { data: null, orderable: false, render: (d,t,row) =>
        `<div class="d-flex gap-1 justify-content-center">
          <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editEstudiante(${JSON.stringify(row).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteEstudiante(${row.id},'${row.codigo}')"><i class="fas fa-trash"></i></button>
        </div>`
      }
    ],
    order: [[2,'asc']]
  });
});

function editEstudiante(e) {
  document.getElementById('estId').value       = e.id;
  document.getElementById('estCodigo').value   = e.codigo;
  document.getElementById('estApellidos').value= e.apellidos;
  document.getElementById('estNombres').value  = e.nombres;
  document.getElementById('estCiclo').value    = e.ciclo_id;
  document.getElementById('estSeccion').value  = e.seccion;
  document.getElementById('estActivo').value   = e.activo;
  document.getElementById('modalEstTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Estudiante';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstudiante')).show();
}

async function saveEstudiante() {
  if (!validateForm('formEstudiante')) return;
  Loading.show();
  try {
    const data = {
      action: 'save',
      id:         document.getElementById('estId').value,
      codigo:     document.getElementById('estCodigo').value,
      apellidos:  document.getElementById('estApellidos').value,
      nombres:    document.getElementById('estNombres').value,
      ciclo_id:   document.getElementById('estCiclo').value,
      seccion:    document.getElementById('estSeccion').value,
      activo:     document.getElementById('estActivo').value,
    };
    const res = await Api.post('ajax/estudiantes.php', data);
    if (res.success) {
      Toast.success(res.message);
      bootstrap.Modal.getInstance(document.getElementById('modalEstudiante')).hide();
      dtEstudiantes.ajax.reload();
    } else Toast.error(res.message);
  } catch(e) { Toast.error('Error: ' + e.message); }
  finally { Loading.hide(); }
}

function deleteEstudiante(id, codigo) {
  confirmDelete(`¿Eliminar al estudiante con código ${codigo}?`, async () => {
    Loading.show();
    const res = await Api.post('ajax/estudiantes.php', { action: 'delete', id });
    Loading.hide();
    if (res.success) { Toast.success(res.message); dtEstudiantes.ajax.reload(); }
    else Toast.error(res.message);
  });
}

async function importarEstudiantes() {
  const file = document.getElementById('archivoExcel').files[0];
  if (!file) { Toast.warning('Selecciona un archivo Excel.'); return; }
  Loading.show();
  const fd = new FormData();
  fd.append('action', 'import');
  fd.append('archivo', file);
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  try {
    const res = await fetch('ajax/estudiantes.php', { method:'POST', body: fd });
    const data = await res.json();
    const resultDiv = document.getElementById('importResult');
    resultDiv.classList.remove('d-none');
    if (data.success) {
      resultDiv.className = 'alert alert-success';
      resultDiv.innerHTML = `<strong>Importacion completada:</strong><br>
        Insertados: ${data.data.insertados}<br>
        Actualizados: ${data.data.actualizados}<br>
        Errores: ${data.data.errores}
        ${data.data.mensajes_error ? '<br><small>'+data.data.mensajes_error.join('<br>')+'</small>' : ''}`;
      dtEstudiantes.ajax.reload();
    } else {
      resultDiv.className = 'alert alert-danger';
      resultDiv.textContent = data.message;
    }
  } catch(e) { Toast.error('Error al importar: ' + e.message); }
  finally { Loading.hide(); }
}
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
