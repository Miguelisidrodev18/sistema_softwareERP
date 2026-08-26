<?php
Auth::requireAdmin();
$pageTitle = 'Gestion de Ciclos';
$ciclos = DB::fetchAll("SELECT c.*, (SELECT COUNT(*) FROM estudiantes WHERE ciclo_id=c.id AND activo=1) as total_estudiantes FROM ciclos c ORDER BY c.orden, c.id");
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-layer-group"></i> Ciclos</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Ciclos</li></ol></nav>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCiclo" onclick="resetModal('modalCiclo'); document.getElementById('cicloId').value=''">
    <i class="fas fa-plus me-1"></i> Nuevo Ciclo
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaCiclos">
        <thead>
          <tr>
            <th>#</th><th>Nombre</th><th>Descripcion</th><th>Orden</th>
            <th>Estudiantes</th><th>Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ciclos as $i => $c): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td class="fw-600"><?= e($c['nombre']) ?></td>
            <td><?= e($c['descripcion'] ?? '-') ?></td>
            <td><?= $c['orden'] ?></td>
            <td><span class="badge bg-primary"><?= $c['total_estudiantes'] ?></span></td>
            <td><?= $c['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
            <td class="text-center actions">
              <button class="btn btn-sm btn-outline-primary btn-icon" title="Editar"
                onclick="editCiclo(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-<?= $c['activo']?'warning':'success' ?> btn-icon" title="<?= $c['activo']?'Desactivar':'Activar' ?>"
                onclick="toggleCiclo(<?= $c['id'] ?>, <?= $c['activo'] ?>)">
                <i class="fas fa-<?= $c['activo']?'ban':'check' ?>"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger btn-icon" title="Eliminar"
                onclick="deleteCiclo(<?= $c['id'] ?>, '<?= e($c['nombre']) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Ciclo -->
<div class="modal fade" id="modalCiclo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCicloTitle"><i class="fas fa-layer-group me-2"></i>Nuevo Ciclo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCiclo" novalidate>
          <input type="hidden" id="cicloId" name="id">
          <div class="mb-3">
            <label class="form-label">Nombre del Ciclo <span class="text-danger">*</span></label>
            <input type="text" id="cicloNombre" name="nombre" class="form-control"
                   placeholder="Ej: I Ciclo, III Ciclo..." required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripcion</label>
            <input type="text" id="cicloDesc" name="descripcion" class="form-control"
                   placeholder="Descripcion del ciclo (opcional)">
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Orden</label>
              <input type="number" id="cicloOrden" name="orden" class="form-control" value="1" min="1" max="20">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Estado</label>
              <select id="cicloActivo" name="activo" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveCiclo()">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
function editCiclo(c) {
  document.getElementById('cicloId').value        = c.id;
  document.getElementById('cicloNombre').value    = c.nombre;
  document.getElementById('cicloDesc').value      = c.descripcion || '';
  document.getElementById('cicloOrden').value     = c.orden;
  document.getElementById('cicloActivo').value    = c.activo;
  document.getElementById('modalCicloTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Ciclo';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCiclo')).show();
}

async function saveCiclo() {
  if (!validateForm('formCiclo')) return;
  Loading.show();
  try {
    const data = {
      id:          document.getElementById('cicloId').value,
      nombre:      document.getElementById('cicloNombre').value,
      descripcion: document.getElementById('cicloDesc').value,
      orden:       document.getElementById('cicloOrden').value,
      activo:      document.getElementById('cicloActivo').value,
    };
    const res = await Api.post('ajax/ciclos.php', { action: 'save', ...data });
    if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 800); }
    else Toast.error(res.message);
  } catch(e) { Toast.error('Error: ' + e.message); }
  finally { Loading.hide(); }
}

function toggleCiclo(id, activo) {
  const msg = activo ? '¿Desactivar este ciclo?' : '¿Activar este ciclo?';
  confirmDelete(msg, async () => {
    Loading.show();
    const res = await Api.post('ajax/ciclos.php', { action: 'toggle', id });
    Loading.hide();
    if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 700); }
    else Toast.error(res.message);
  });
}

function deleteCiclo(id, nombre) {
  confirmDelete(`¿Eliminar el ciclo "${nombre}"? Esta accion no se puede deshacer.`, async () => {
    Loading.show();
    const res = await Api.post('ajax/ciclos.php', { action: 'delete', id });
    Loading.hide();
    if (res.success) { Toast.success(res.message); setTimeout(() => location.reload(), 700); }
    else Toast.error(res.message);
  });
}

$(document).ready(() => initDataTable('tablaCiclos', { order: [[3,'asc']] }));
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
