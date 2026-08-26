<?php
Auth::requireAdmin();
$pageTitle   = 'Reportes';
$eventos     = DB::fetchAll("SELECT id, nombre, fecha FROM eventos ORDER BY fecha DESC");
$ciclos      = DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo=1 ORDER BY orden");
include VIEWS_PATH . '/layout/header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="fas fa-chart-bar"></i> Reportes</h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item active">Reportes</li></ol></nav>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Reporte por evento -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-calendar-check"></i> Reporte por Evento</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Seleccionar Evento</label>
          <select id="reporteEvento" class="form-select">
            <option value="">— Todos los eventos —</option>
            <?php foreach ($eventos as $ev): ?>
            <option value="<?= $ev['id'] ?>"><?= e($ev['nombre']) ?> (<?= fechaES($ev['fecha']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a id="btnCsvEvento" href="ajax/export.php?tipo=asistencias&evento_id=0" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i> CSV
          </a>
          <button class="btn btn-outline-primary" onclick="verReporteEvento()">
            <i class="fas fa-eye me-1"></i> Ver Reporte
          </button>
          <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Imprimir
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reporte por ciclo -->
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title"><i class="fas fa-layer-group"></i> Reporte por Ciclo</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Seleccionar Ciclo</label>
          <select id="reporteCiclo" class="form-select">
            <option value="">— Todos los ciclos —</option>
            <?php foreach ($ciclos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a id="btnCsvCiclo" href="ajax/export.php?tipo=reporte_ciclo&ciclo_id=0" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i> CSV
          </a>
          <button class="btn btn-outline-primary" onclick="verReporteCiclo()">
            <i class="fas fa-eye me-1"></i> Ver Reporte
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Resultados -->
<div id="reporteResultado" class="card d-none">
  <div class="card-header justify-content-between">
    <h5 class="card-title" id="reporteTitulo"></h5>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
      <i class="fas fa-print me-1"></i> Imprimir
    </button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-epis mb-0" id="tablaReporte">
        <thead id="theadReporte"></thead>
        <tbody id="tbodyReporte"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Estadisticas generales -->
<div class="card mt-4" id="statsGenerales">
  <div class="card-header">
    <h5 class="card-title"><i class="fas fa-chart-pie"></i> Estadisticas Generales</h5>
  </div>
  <div class="card-body">
    <div id="statsContent"><div class="text-center py-3"><div class="spinner-border text-primary"></div></div></div>
  </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
let dtReporte = null;

document.getElementById('reporteEvento').addEventListener('change', function() {
  document.getElementById('btnCsvEvento').href = 'ajax/export.php?tipo=asistencias&evento_id=' + (this.value||0);
});
document.getElementById('reporteCiclo').addEventListener('change', function() {
  document.getElementById('btnCsvCiclo').href = 'ajax/export.php?tipo=reporte_ciclo&ciclo_id=' + (this.value||0);
});

async function verReporteEvento() {
  const eventoId = document.getElementById('reporteEvento').value;
  Loading.show();
  const res = await Api.get('ajax/reports.php', { action:'por_evento', evento_id: eventoId||'' });
  Loading.hide();
  if (!res.success) { Toast.error(res.message); return; }
  const data = res.data;
  const titulo = eventoId ? `Asistencias: ${data.evento_nombre}` : 'Todas las Asistencias';
  document.getElementById('reporteTitulo').textContent = titulo;
  document.getElementById('theadReporte').innerHTML = `<tr>
    <th>#</th><th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th>
    <th>Evento</th><th>Fecha</th><th>Estado</th><th>Puntos</th>
  </tr>`;
  document.getElementById('tbodyReporte').innerHTML = data.rows.map((r,i) => `<tr>
    <td>${i+1}</td>
    <td>${escapeHtml(r.codigo)}</td>
    <td>${escapeHtml(r.apellidos_nombres)}</td>
    <td>${escapeHtml(r.ciclo_nombre)}</td>
    <td>${escapeHtml(r.evento_nombre)}</td>
    <td>${escapeHtml(r.fecha_registro)}</td>
    <td>${{asistio:'<span class="badge bg-success">Asistio</span>',tardanza:'<span class="badge bg-warning text-dark">Tardanza</span>',falta:'<span class="badge bg-danger">Falta</span>'}[r.estado]||r.estado}</td>
    <td><strong>${r.puntos}</strong></td>
  </tr>`).join('') || '<tr><td colspan="8" class="text-center text-muted">Sin registros</td></tr>';
  document.getElementById('reporteResultado').classList.remove('d-none');
  if (dtReporte) dtReporte.destroy();
  dtReporte = initDataTable('tablaReporte');
}

async function verReporteCiclo() {
  const cicloId = document.getElementById('reporteCiclo').value;
  Loading.show();
  const res = await Api.get('ajax/reports.php', { action:'por_ciclo', ciclo_id: cicloId||'' });
  Loading.hide();
  if (!res.success) { Toast.error(res.message); return; }
  const data = res.data;
  document.getElementById('reporteTitulo').textContent = cicloId ? `Reporte: ${data.ciclo_nombre}` : 'Reporte General por Ciclo';
  document.getElementById('theadReporte').innerHTML = `<tr>
    <th>#</th><th>Codigo</th><th>Apellidos y Nombres</th><th>Ciclo</th>
    <th>Asistencias</th><th>Tardanzas</th><th>Faltas</th><th>Total Puntos</th>
  </tr>`;
  document.getElementById('tbodyReporte').innerHTML = data.rows.map((r,i) => `<tr>
    <td>${i+1}</td>
    <td>${escapeHtml(r.codigo)}</td>
    <td>${escapeHtml(r.apellidos_nombres)}</td>
    <td>${escapeHtml(r.ciclo_nombre)}</td>
    <td><span class="badge bg-success">${r.asistencias}</span></td>
    <td><span class="badge bg-warning text-dark">${r.tardanzas}</span></td>
    <td><span class="badge bg-danger">${r.faltas}</span></td>
    <td><strong class="text-primary">${r.total_puntos} pts</strong></td>
  </tr>`).join('') || '<tr><td colspan="8" class="text-center text-muted">Sin registros</td></tr>';
  document.getElementById('reporteResultado').classList.remove('d-none');
  if (dtReporte) dtReporte.destroy();
  dtReporte = initDataTable('tablaReporte', { order:[[7,'desc']] });
}

// Cargar estadisticas generales al inicio
async function cargarStats() {
  const res = await Api.get('ajax/reports.php', { action:'stats_generales' });
  if (!res.success) return;
  const d = res.data;
  document.getElementById('statsContent').innerHTML = `
    <div class="row g-3 text-center">
      <div class="col-6 col-md-3">
        <div class="p-3 bg-light rounded"><div class="fw-800 fs-4 text-primary">${d.total_registros}</div><small>Total Registros</small></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-light rounded"><div class="fw-800 fs-4 text-success">${d.pct_asistio}%</div><small>% Asistencia</small></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-light rounded"><div class="fw-800 fs-4 text-warning">${d.pct_tardanza}%</div><small>% Tardanza</small></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="p-3 bg-light rounded"><div class="fw-800 fs-4 text-danger">${d.pct_falta}%</div><small>% Falta</small></div>
      </div>
    </div>`;
}
cargarStats();
</script>
JS;
include VIEWS_PATH . '/layout/footer.php';
