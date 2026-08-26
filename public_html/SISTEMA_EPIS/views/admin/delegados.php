<?php
Auth::requireAdmin();
$pageTitle = 'Gestion de Delegados';
$ciclos    = DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo=1 ORDER BY orden");
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-users-cog"></i> Delegados</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Delegados</li></ol></nav>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDelegado"
          onclick="resetModal('modalDelegado'); document.getElementById('delId').value=''">
    <i class="fas fa-plus me-1"></i> Nuevo Delegado
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaDelegados">
        <thead>
          <tr>
            <th>#</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Ciclo Propio</th>
            <th>Ciclos Asignados</th><th>Estado</th><th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyDelegados"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Delegado -->
<div class="modal fade" id="modalDelegado" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDelTitle"><i class="fas fa-user-shield me-2"></i>Nuevo Delegado</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formDelegado" novalidate>
          <input type="hidden" id="delId" name="id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nombres <span class="text-danger">*</span></label>
              <input type="text" id="delNombres" name="nombres" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Apellidos <span class="text-danger">*</span></label>
              <input type="text" id="delApellidos" name="apellidos" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Usuario (login) <span class="text-danger">*</span></label>
              <input type="text" id="delUsername" name="username" class="form-control" required maxlength="100" autocomplete="off">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" id="delEmail" name="email" class="form-control" maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Contraseña <span id="passHint" class="text-muted small">(requerida para nuevo)</span></label>
              <div class="input-group">
                <input type="password" id="delPassword" name="password" class="form-control" autocomplete="new-password" minlength="6">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('delPassword',this)"><i class="fas fa-eye"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Rol <span class="text-danger">*</span></label>
              <select id="delRol" name="rol" class="form-select" required onchange="toggleCicloPropio()">
                <option value="delegado_ciclo">Delegado de Ciclo</option>
                <option value="delegado_pleno">Delegado Pleno</option>
              </select>
            </div>
            <div class="col-md-6" id="cicloPropioDiv">
              <label class="form-label">Ciclo al que pertenece</label>
              <select id="delCicloPropio" name="ciclo_propio_id" class="form-select">
                <option value="">— Ninguno —</option>
                <?php foreach ($ciclos as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">El delegado NO podra registrar asistencia de este ciclo.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select id="delActivo" name="activo" class="form-select">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <!-- Ciclos que puede evaluar -->
            <div class="col-12" id="ciclosAsignadosDiv">
              <label class="form-label fw-bold">Ciclos que puede evaluar</label>
              <div class="border rounded p-3 bg-light">
                <div class="row g-2">
                  <?php foreach ($ciclos as $c): ?>
                  <div class="col-6 col-md-4">
                    <div class="form-check">
                      <input class="form-check-input ciclo-check" type="checkbox"
                             name="ciclos_asignados[]" value="<?= $c['id'] ?>"
                             id="chk_ciclo_<?= $c['id'] ?>">
                      <label class="form-check-label" for="chk_ciclo_<?= $c['id'] ?>">
                        <?= e($c['nombre']) ?>
                      </label>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <small class="text-muted mt-2 d-block">
                  <i class="fas fa-info-circle me-1"></i>
                  El delegado pleno tiene acceso a todos los ciclos automaticamente.
                </small>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveDelegado()">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
let dtDelegados = null;

$(document).ready(() => {
  dtDelegados = initDataTable('tablaDelegados', {
    ajax: { url: 'ajax/delegados.php?action=list', dataSrc: 'data' },
    columns: [
      { data: null, render:(d,t,r,m) => m.row+1 },
      { data: 'nombres_completos' },
      { data: 'username' },
      { data: 'rol', render: v => v==='delegado_pleno'
          ? '<span class="badge bg-primary"><i class="fas fa-crown me-1"></i>Pleno</span>'
          : '<span class="badge bg-info text-dark">Ciclo</span>' },
      { data: 'ciclo_propio_nombre', render: v => v||'-' },
      { data: 'ciclos_asignados_nombres', render: v => v
          ? v.split('|').map(n=>`<span class="badge bg-secondary me-1">${escapeHtml(n)}</span>`).join('')
          : '<span class="text-muted small">Ninguno</span>' },
      { data: 'activo', render: v => v=='1'
          ? '<span class="badge bg-success">Activo</span>'
          : '<span class="badge bg-secondary">Inactivo</span>' },
      { data: null, orderable:false, render:(d,t,row) =>
        `<div class="d-flex gap-1 justify-content-center">
          <button class="btn btn-sm btn-outline-primary btn-icon" onclick="editDelegado(${row.id})"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-outline-danger btn-icon" onclick="deleteDelegado(${row.id},'${row.username}')"><i class="fas fa-trash"></i></button>
        </div>`
      }
    ],
    order: [[1,'asc']]
  });
});

function toggleCicloPropio() {
  const esPleno = document.getElementById('delRol').value === 'delegado_pleno';
  document.getElementById('cicloPropioDiv').style.opacity = esPleno ? 0.5 : 1;
  document.getElementById('ciclosAsignadosDiv').style.opacity = esPleno ? 0.5 : 1;
}

async function editDelegado(id) {
  const res = await Api.get('ajax/delegados.php', { action:'get', id });
  if (!res.success) { Toast.error(res.message); return; }
  const d = res.data;
  document.getElementById('delId').value        = d.id;
  document.getElementById('delNombres').value   = d.nombres;
  document.getElementById('delApellidos').value = d.apellidos;
  document.getElementById('delUsername').value  = d.username;
  document.getElementById('delEmail').value     = d.email||'';
  document.getElementById('delPassword').value  = '';
  document.getElementById('delRol').value       = d.rol;
  document.getElementById('delCicloPropio').value= d.ciclo_propio_id||'';
  document.getElementById('delActivo').value    = d.activo;
  document.getElementById('passHint').textContent = '(dejar vacio para no cambiar)';
  // Marcar ciclos asignados
  document.querySelectorAll('.ciclo-check').forEach(cb => {
    cb.checked = d.ciclos_asignados.includes(parseInt(cb.value));
  });
  toggleCicloPropio();
  document.getElementById('modalDelTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Delegado';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDelegado')).show();
}

async function saveDelegado() {
  if (!validateForm('formDelegado')) return;
  const isNew = !document.getElementById('delId').value;
  const pass  = document.getElementById('delPassword').value;
  if (isNew && !pass) { Toast.warning('La contraseña es requerida para un nuevo delegado.'); return; }
  Loading.show();
  try {
    const ciclosChecked = [...document.querySelectorAll('.ciclo-check:checked')].map(c => c.value);
    const data = {
      action: 'save',
      id:               document.getElementById('delId').value,
      nombres:          document.getElementById('delNombres').value,
      apellidos:        document.getElementById('delApellidos').value,
      username:         document.getElementById('delUsername').value,
      email:            document.getElementById('delEmail').value,
      password:         pass,
      rol:              document.getElementById('delRol').value,
      ciclo_propio_id:  document.getElementById('delCicloPropio').value,
      activo:           document.getElementById('delActivo').value,
      ciclos_asignados: ciclosChecked,
    };
    const res = await Api.post('ajax/delegados.php', data);
    if (res.success) {
      Toast.success(res.message);
      bootstrap.Modal.getInstance(document.getElementById('modalDelegado')).hide();
      dtDelegados.ajax.reload();
    } else Toast.error(res.message);
  } catch(e) { Toast.error('Error: '+e.message); }
  finally { Loading.hide(); }
}

function deleteDelegado(id, username) {
  confirmDelete(`¿Eliminar al delegado "${username}"?`, async () => {
    Loading.show();
    const res = await Api.post('ajax/delegados.php', { action:'delete', id });
    Loading.hide();
    if (res.success) { Toast.success(res.message); dtDelegados.ajax.reload(); }
    else Toast.error(res.message);
  });
}

function togglePass(id, btn) {
  const el = document.getElementById(id);
  el.type = el.type==='password' ? 'text' : 'password';
  btn.innerHTML = el.type==='password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
