<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/config.php';
requireRol('docente');

$nombre   = htmlspecialchars($_SESSION['nombre']   ?? '', ENT_QUOTES);
$apellidos= htmlspecialchars($_SESSION['apellidos'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Docente — UNH</title>
<style>
:root{
  --unh-azul:#1A3A6B;--unh-azul-mid:#2a5298;--unh-dorado:#F5C518;
  --unh-dorado-dk:#c9a20e;--unh-verde:#1D9E75;--unh-rojo:#C0392B;
  --bg:#f4f6f9;--surface:#fff;--border:#dde3ed;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column}

/* ── Header ── */
.site-header{background:var(--unh-azul);color:#fff;padding:0 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.25);position:sticky;top:0;z-index:100}
.site-header .inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:1rem;height:65px}
.site-header img.logo{width:44px;height:44px;border-radius:50%;border:2px solid var(--unh-dorado);object-fit:cover;flex-shrink:0}
.logo-fallback{width:44px;height:44px;border-radius:50%;border:2px solid var(--unh-dorado);background:var(--unh-azul-mid);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--unh-dorado);flex-shrink:0}
.header-text{flex:1}
.header-text h1{font-size:.92rem;font-weight:700}
.header-text p{font-size:.75rem;opacity:.8;margin-top:1px}
.btn-logout{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.4);color:#fff;padding:.35rem .8rem;border-radius:6px;font-size:.78rem;cursor:pointer;text-decoration:none;transition:background .2s;margin-left:auto;white-space:nowrap}
.btn-logout:hover{background:rgba(255,255,255,.25)}

/* ── Layout ── */
main{flex:1;max-width:1200px;margin:0 auto;padding:1.5rem 1rem 3rem;width:100%}
section{margin-bottom:2rem}
.section-title{font-size:1.05rem;font-weight:700;color:var(--unh-azul);margin-bottom:1rem;padding-bottom:.4rem;border-bottom:2px solid var(--unh-dorado)}

/* ── Métricas ── */
.metrics-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem}
.metric-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.2rem 1.3rem;text-align:center;box-shadow:0 2px 8px rgba(26,58,107,.06)}
.metric-card .metric-val{font-size:2rem;font-weight:900;color:var(--unh-azul);line-height:1}
.metric-card .metric-label{font-size:.78rem;color:#64748b;margin-top:.4rem;font-weight:500}
.metric-card .metric-accent{color:var(--unh-dorado)}

/* ── Filtro ── */
.filter-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.8rem;flex-wrap:wrap}
.filter-row label{font-size:.83rem;font-weight:600;color:#4a5568}
.filter-row select{padding:.4rem .7rem;border:1.5px solid var(--border);border-radius:7px;font-size:.83rem;color:#2d3748;outline:none;background:var(--surface)}
.filter-row select:focus{border-color:var(--unh-azul)}

/* ── Tabla ── */
.table-wrap{overflow-x:auto;border-radius:10px;border:1px solid var(--border);background:var(--surface);box-shadow:0 2px 8px rgba(26,58,107,.06)}
table{width:100%;border-collapse:collapse;font-size:.84rem}
thead th{background:var(--unh-azul);color:#fff;padding:.65rem .9rem;text-align:left;font-weight:600;white-space:nowrap}
tbody tr{transition:background .15s}
tbody tr:hover{background:#f0f4ff}
tbody tr:nth-child(even){background:#f8fafc}
tbody tr:nth-child(even):hover{background:#e8eefb}
tbody td{padding:.6rem .9rem;border-bottom:1px solid var(--border);color:#374151;white-space:nowrap}
tfoot td{padding:.65rem .9rem;font-weight:600;color:var(--unh-azul);background:#f0f4ff;font-size:.82rem}

.pill-aprobado{background:#d1fae5;color:#065f46;padding:.15rem .55rem;border-radius:12px;font-size:.75rem;font-weight:700}
.pill-desaprobado{background:#fee2e2;color:#991b1b;padding:.15rem .55rem;border-radius:12px;font-size:.75rem;font-weight:700}

/* ── Ranking podio ── */
.podio{display:flex;justify-content:center;align-items:flex-end;gap:1.2rem;margin-bottom:1.5rem}
.podio-item{text-align:center;flex:1;max-width:180px}
.podio-item .medalla{font-size:2.2rem;line-height:1;margin-bottom:.4rem}
.podio-item .podio-nombre{font-size:.88rem;font-weight:700;color:#1a2a3a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.podio-item .podio-pts{font-size:1.3rem;font-weight:900;line-height:1.1}
.podio-item .podio-base{margin-top:.5rem;border-radius:8px 8px 0 0;padding:.6rem .4rem .4rem}
.podio-1st .podio-pts{color:var(--unh-dorado)}
.podio-1st .podio-base{background:var(--unh-azul);min-height:80px}
.podio-2nd .podio-pts{color:#adb5bd}
.podio-2nd .podio-base{background:#2a5298;min-height:60px}
.podio-3rd .podio-pts{color:#cd7f32}
.podio-3rd .podio-base{background:#3a6599;min-height:44px}
.podio-1st{order:2}.podio-2nd{order:1}.podio-3rd{order:3}
.podio-label{color:#fff;font-size:.75rem;opacity:.85;font-weight:600}

/* ── Stats situaciones ── */
.stat-fila-mala{background:#fff5f5 !important}
.bar-wrap{background:#eef1f7;border-radius:4px;height:8px;overflow:hidden;min-width:80px}
.bar-fill{height:100%;border-radius:4px}
.bar-ok{background:var(--unh-verde)}
.bar-fail{background:var(--unh-rojo)}

/* ── Importar estudiantes ── */
.import-zone{border:2px dashed var(--border);border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:var(--surface);position:relative}
.import-zone:hover,.import-zone.drag-over{border-color:var(--unh-azul);background:#f0f4ff}
.import-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.import-zone .iz-icon{font-size:2.5rem;margin-bottom:.5rem;line-height:1}
.import-zone .iz-title{font-size:.95rem;font-weight:700;color:var(--unh-azul);margin-bottom:.25rem}
.import-zone .iz-sub{font-size:.78rem;color:#64748b}
.import-zone .iz-file{font-size:.82rem;color:var(--unh-verde);font-weight:600;margin-top:.4rem}
.import-hint{background:#f0f4ff;border:1px solid #c7d6f0;border-radius:8px;padding:.7rem 1rem;margin-top:.8rem;font-size:.8rem;color:#334155;line-height:1.6}
.import-hint code{background:rgba(26,58,107,.1);padding:.1rem .3rem;border-radius:3px;font-size:.78rem}
.btn-import{margin-top:.9rem;padding:.7rem 2rem;background:var(--unh-azul);color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:700;cursor:pointer;transition:background .2s,transform .1s}
.btn-import:hover:not(:disabled){background:var(--unh-azul-mid)}
.btn-import:active:not(:disabled){transform:scale(.98)}
.btn-import:disabled{opacity:.45;cursor:not-allowed}
.import-result{margin-top:1rem;display:none}
.import-summary{display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:.8rem}
.imp-stat{flex:1;min-width:110px;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.6rem .8rem;text-align:center}
.imp-stat .sv{font-size:1.5rem;font-weight:800;line-height:1}
.imp-stat .sl{font-size:.72rem;color:#64748b;margin-top:.2rem}
.imp-stat.s-ok{border-color:#a7f3d0}.imp-stat.s-ok .sv{color:var(--unh-verde)}
.imp-stat.s-upd{border-color:#bfdbfe}.imp-stat.s-upd .sv{color:var(--unh-azul-mid)}
.imp-stat.s-nc{border-color:var(--border)}.imp-stat.s-nc .sv{color:#64748b}
.imp-stat.s-err{border-color:#fca5a5}.imp-stat.s-err .sv{color:var(--unh-rojo)}
.import-errors{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:.7rem 1rem;font-size:.8rem;color:#991b1b;margin-bottom:.8rem}
.import-errors ul{margin:.3rem 0 0 1.2rem}
.import-preview-wrap{max-height:260px;overflow-y:auto;border-radius:8px;border:1px solid var(--border)}

/* ── Loader ── */
.loader-overlay{display:none;position:fixed;inset:0;background:rgba(26,58,107,.18);z-index:999;align-items:center;justify-content:center}
.loader-overlay.show{display:flex}
.spinner{width:44px;height:44px;border:4px solid rgba(255,255,255,.4);border-top-color:var(--unh-dorado);border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:700px){
  .metrics-grid{grid-template-columns:1fr 1fr}
  .podio{gap:.7rem}
  .podio-item .podio-nombre{font-size:.78rem}
}
@media(max-width:420px){.metrics-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="loader-overlay" id="loader"><div class="spinner"></div></div>

<header class="site-header">
  <div class="inner">
    <img class="logo" src="../assets/logo_unh.png" alt="UNH"
         onerror="this.style.display='none';document.getElementById('logo-hdr').style.display='flex'">
    <div class="logo-fallback" id="logo-hdr" style="display:none">UNH</div>
    <div class="header-text">
      <h1>Panel Docente — <?= $nombre . ' ' . $apellidos ?></h1>
      <p>Universidad Nacional de Huancavelica · Ingeniería de Sistemas</p>
    </div>
    <a href="../logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<main>

  <!-- SECCIÓN 0: Importar estudiantes -->
  <section>
    <div class="section-title">Importar estudiantes desde Excel</div>
    <div class="import-zone" id="import-zone">
      <input type="file" id="import-file" accept=".xlsx,.csv" onchange="archivoSeleccionado(this)">
      <div class="iz-icon">📂</div>
      <div class="iz-title">Arrastra el archivo aquí o haz clic para seleccionar</div>
      <div class="iz-sub">Formatos aceptados: <strong>.xlsx</strong> (Excel) o <strong>.csv</strong> — máx. 5 MB</div>
      <div class="iz-file" id="iz-file-name"></div>
    </div>
    <div class="import-hint">
      <strong>Formato esperado de columnas:</strong><br>
      <code>A: Codigo</code> &nbsp;|&nbsp;
      <code>B: Apellidos y Nombres</code> (formato: <code>APELLIDOS, NOMBRES</code>) &nbsp;|&nbsp;
      <code>C: Ciclo</code> &nbsp;|&nbsp;
      <code>D: Seccion</code><br>
      La primera fila de encabezado se detecta y omite automáticamente.
      Los nombres se convierten a formato Título (Juan García).
      Si el código ya existe, se actualizan los datos.
    </div>
    <button class="btn-import" id="btn-import" disabled onclick="importarEstudiantes()">
      Importar estudiantes
    </button>

    <div class="import-result" id="import-result">
      <div class="import-summary" id="import-summary"></div>
      <div class="import-errors" id="import-errors" style="display:none"></div>
      <div class="import-preview-wrap">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
          <thead>
            <tr>
              <th style="background:var(--unh-azul);color:#fff;padding:.5rem .8rem;text-align:left">Código</th>
              <th style="background:var(--unh-azul);color:#fff;padding:.5rem .8rem;text-align:left">Nombre</th>
              <th style="background:var(--unh-azul);color:#fff;padding:.5rem .8rem;text-align:left">Apellidos</th>
              <th style="background:var(--unh-azul);color:#fff;padding:.5rem .8rem;text-align:left">Resultado</th>
            </tr>
          </thead>
          <tbody id="import-preview-body"></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- SECCIÓN 1: Métricas -->
  <section>
    <div class="section-title">Métricas generales</div>
    <div class="metrics-grid" id="metrics-grid">
      <div class="metric-card"><div class="metric-val metric-accent">…</div><div class="metric-label">Estudiantes registrados</div></div>
      <div class="metric-card"><div class="metric-val metric-accent">…</div><div class="metric-label">Intentos completados</div></div>
      <div class="metric-card"><div class="metric-val metric-accent">…</div><div class="metric-label">Promedio general (pts)</div></div>
      <div class="metric-card"><div class="metric-val metric-accent">…</div><div class="metric-label">Tasa de aprobación</div></div>
    </div>
  </section>

  <!-- SECCIÓN 2: Tabla de notas -->
  <section>
    <div class="section-title">Tabla de notas</div>
    <div class="filter-row">
      <label for="filtro-caso">Filtrar por caso:</label>
      <select id="filtro-caso" onchange="cargarNotas()">
        <option value="">Todos los casos</option>
      </select>
    </div>
    <div class="table-wrap">
      <table id="tabla-notas">
        <thead>
          <tr>
            <th>#</th><th>Código</th><th>Nombre completo</th><th>Caso</th>
            <th>Puntaje</th><th>Máx</th><th>%</th><th>Fecha</th><th>Estado</th>
          </tr>
        </thead>
        <tbody id="notas-body"></tbody>
      </table>
    </div>
  </section>

  <!-- SECCIÓN 3: Ranking -->
  <section>
    <div class="section-title">Ranking — Top 10</div>
    <div class="podio" id="podio"></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Pos.</th><th>Nombre</th><th>Código</th><th>Caso</th><th>Puntos</th><th>%</th><th>Fecha</th></tr>
        </thead>
        <tbody id="ranking-body"></tbody>
      </table>
    </div>
  </section>

  <!-- SECCIÓN 4: Estadísticas por situación -->
  <section>
    <div class="section-title">Estadísticas por situación</div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Caso</th><th>N°</th><th>Situación</th><th>Tipo correcto</th>
              <th>Respuestas</th><th>Aciertos</th><th>% Acierto</th><th>Gráfico</th></tr>
        </thead>
        <tbody id="stats-body"></tbody>
      </table>
    </div>
  </section>

</main>

<script>
const API = '../php/api.php';

function showLoader(v){ document.getElementById('loader').classList.toggle('show', v); }
function esc(s){ const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

// ── Cargar todo al iniciar ───────────────────────────────────────────────────
async function init() {
  showLoader(true);
  await Promise.all([cargarMetricas(), cargarCasosSelect(), cargarRanking(), cargarStats()]);
  await cargarNotas();
  showLoader(false);
}

// ── Métricas ─────────────────────────────────────────────────────────────────
async function cargarMetricas() {
  try {
    const r  = await fetch(API + '?action=metricas_docente');
    const js = await r.json();
    const d  = js.data;
    const grid = document.getElementById('metrics-grid');
    const vals = [
      { v: d.total_estudiantes, l: 'Estudiantes registrados' },
      { v: d.total_intentos,    l: 'Intentos completados' },
      { v: d.promedio_puntaje,  l: 'Promedio general (pts)' },
      { v: d.tasa_aprobacion + '%', l: 'Tasa de aprobación' },
    ];
    grid.innerHTML = vals.map(m =>
      `<div class="metric-card">
         <div class="metric-val metric-accent">${m.v}</div>
         <div class="metric-label">${m.l}</div>
       </div>`
    ).join('');
  } catch(e) { console.error(e); }
}

// ── Select de casos para filtro ──────────────────────────────────────────────
async function cargarCasosSelect() {
  try {
    const r  = await fetch(API + '?action=casos');
    const js = await r.json();
    const sel = document.getElementById('filtro-caso');
    (js.data||[]).forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id; opt.textContent = c.titulo;
      sel.appendChild(opt);
    });
  } catch(e) { console.error(e); }
}

// ── Tabla de notas ───────────────────────────────────────────────────────────
async function cargarNotas() {
  const casoId = document.getElementById('filtro-caso').value;
  const url    = API + '?action=notas_docente' + (casoId ? '&caso_id='+casoId : '');
  try {
    const r  = await fetch(url);
    const js = await r.json();
    const tbody = document.getElementById('notas-body');
    tbody.innerHTML = '';
    if (!js.data || js.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#64748b;padding:1.2rem">Sin datos para mostrar.</td></tr>';
      return;
    }
    js.data.forEach((row, i) => {
      const tr   = document.createElement('tr');
      const fecha = row.finalizado_en ? row.finalizado_en.substring(0,16).replace('T',' ') : '—';
      const estado = row.aprobado == 1
        ? '<span class="pill-aprobado">Aprobado</span>'
        : '<span class="pill-desaprobado">Desaprobado</span>';
      tr.innerHTML = `
        <td>${i+1}</td>
        <td><strong>${esc(row.codigo_matricula)}</strong></td>
        <td>${esc(row.nombre_completo)}</td>
        <td>${esc(row.caso)}</td>
        <td><strong>${row.puntaje}</strong></td>
        <td>${row.puntaje_max}</td>
        <td>${row.porcentaje}%</td>
        <td>${fecha}</td>
        <td>${estado}</td>`;
      tbody.appendChild(tr);
    });
  } catch(e) { console.error(e); }
}

// ── Ranking ───────────────────────────────────────────────────────────────────
async function cargarRanking() {
  try {
    const r  = await fetch(API + '?action=ranking');
    const js = await r.json();
    const rows = js.data || [];

    // Podio top 3
    const podio = document.getElementById('podio');
    const medals = ['🥇','🥈','🥉'];
    const clases = ['podio-1st','podio-2nd','podio-3rd'];
    podio.innerHTML = '';
    rows.slice(0,3).forEach((row, i) => {
      const div = document.createElement('div');
      div.className = 'podio-item ' + clases[i];
      div.innerHTML = `
        <div class="medalla">${medals[i]}</div>
        <div class="podio-nombre">${esc(row.nombre_completo)}</div>
        <div class="podio-base">
          <div class="podio-pts">${row.puntos}</div>
          <div class="podio-label">pts · ${row.porcentaje}%</div>
        </div>`;
      podio.appendChild(div);
    });

    // Tabla 4° al 10°
    const tbody = document.getElementById('ranking-body');
    tbody.innerHTML = '';
    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:1rem">Sin datos en el ranking.</td></tr>';
      return;
    }
    rows.forEach((row, i) => {
      const tr = document.createElement('tr');
      const fecha = row.fecha ? row.fecha.substring(0,10) : '—';
      tr.innerHTML = `
        <td>${i < 3 ? medals[i] : (i+1) + '°'}</td>
        <td>${esc(row.nombre_completo)}</td>
        <td>${esc(row.codigo_matricula)}</td>
        <td>${esc(row.caso)}</td>
        <td><strong>${row.puntos}</strong></td>
        <td>${row.porcentaje}%</td>
        <td>${fecha}</td>`;
      tbody.appendChild(tr);
    });
  } catch(e) { console.error(e); }
}

// ── Estadísticas por situación ───────────────────────────────────────────────
async function cargarStats() {
  try {
    const r  = await fetch(API + '?action=stats');
    const js = await r.json();
    const tbody = document.getElementById('stats-body');
    tbody.innerHTML = '';
    if (!js.data || js.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:1rem">Sin datos estadísticos.</td></tr>';
      return;
    }
    js.data.forEach(row => {
      const pct  = parseFloat(row.porcentaje_acierto) || 0;
      const mala = pct < 50;
      const tr   = document.createElement('tr');
      if (mala) tr.className = 'stat-fila-mala';
      const barClass = mala ? 'bar-fail' : 'bar-ok';
      tr.innerHTML = `
        <td>${esc(row.caso)}</td>
        <td>${row.numero}</td>
        <td title="${esc(row.resumen)}">${esc(row.resumen)}…</td>
        <td><span class="pill-aprobado" style="${mala?'background:#fee2e2;color:#991b1b':''}">${esc(row.tipo_correcto)}</span></td>
        <td>${row.total_respuestas}</td>
        <td>${row.total_aciertos}</td>
        <td>${pct}%</td>
        <td>
          <div class="bar-wrap">
            <div class="bar-fill ${barClass}" style="width:${pct}%"></div>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  } catch(e) { console.error(e); }
}

init();

// ── Importar estudiantes ─────────────────────────────────────────────────────
const IMPORT_API = '../php/importar_estudiantes.php';

function archivoSeleccionado(input) {
  const f = input.files[0];
  if (!f) return;
  document.getElementById('iz-file-name').textContent = '📄 ' + f.name;
  document.getElementById('btn-import').disabled = false;
  document.getElementById('import-result').style.display = 'none';
}

// Drag & drop
const zone = document.getElementById('import-zone');
zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.classList.remove('drag-over');
  const f = e.dataTransfer.files[0];
  if (!f) return;
  const input = document.getElementById('import-file');
  const dt = new DataTransfer();
  dt.items.add(f);
  input.files = dt.files;
  archivoSeleccionado(input);
});

async function importarEstudiantes() {
  const input = document.getElementById('import-file');
  if (!input.files[0]) return;

  document.getElementById('btn-import').disabled = true;
  showLoader(true);

  const fd = new FormData();
  fd.append('archivo', input.files[0]);

  try {
    const r  = await fetch(IMPORT_API, { method: 'POST', body: fd });
    const js = await r.json();

    if (js.error) {
      alert('Error: ' + js.error);
      document.getElementById('btn-import').disabled = false;
      return;
    }

    const d = js.data;

    // Resumen
    document.getElementById('import-summary').innerHTML = `
      <div class="imp-stat s-ok">
        <div class="sv">${d.insertados}</div>
        <div class="sl">Nuevos</div>
      </div>
      <div class="imp-stat s-upd">
        <div class="sv">${d.actualizados}</div>
        <div class="sl">Actualizados</div>
      </div>
      <div class="imp-stat s-nc">
        <div class="sv">${d.sin_cambio}</div>
        <div class="sl">Sin cambio</div>
      </div>
      <div class="imp-stat s-err">
        <div class="sv">${d.errores.length}</div>
        <div class="sl">Errores</div>
      </div>`;

    // Errores
    const errDiv = document.getElementById('import-errors');
    if (d.errores.length > 0) {
      errDiv.style.display = 'block';
      errDiv.innerHTML = '<strong>Advertencias:</strong><ul>'
        + d.errores.map(e => `<li>${esc(e)}</li>`).join('') + '</ul>';
    } else {
      errDiv.style.display = 'none';
    }

    // Preview table
    const tbody = document.getElementById('import-preview-body');
    tbody.innerHTML = '';
    const accionLabel = { nuevo:'Insertado', actualizado:'Actualizado', sin_cambio:'Sin cambio' };
    const accionColor = {
      nuevo:      'background:#d1fae5;color:#065f46',
      actualizado:'background:#dbeafe;color:#1e40af',
      sin_cambio: 'background:#f3f4f6;color:#6b7280',
    };
    (d.preview || []).forEach(row => {
      const tr = document.createElement('tr');
      const lbl = accionLabel[row.accion] || row.accion;
      const col = accionColor[row.accion] || '';
      tr.innerHTML = `
        <td style="padding:.45rem .8rem;border-bottom:1px solid var(--border)">${esc(row.codigo)}</td>
        <td style="padding:.45rem .8rem;border-bottom:1px solid var(--border)">${esc(row.nombre)}</td>
        <td style="padding:.45rem .8rem;border-bottom:1px solid var(--border)">${esc(row.apellidos)}</td>
        <td style="padding:.45rem .8rem;border-bottom:1px solid var(--border)">
          <span style="padding:.15rem .5rem;border-radius:10px;font-size:.73rem;font-weight:700;${col}">${lbl}</span>
        </td>`;
      tbody.appendChild(tr);
    });

    document.getElementById('import-result').style.display = 'block';

    // Refrescar métricas si hubo cambios
    if (d.insertados > 0 || d.actualizados > 0) {
      cargarMetricas();
    }

  } catch(e) {
    console.error(e);
    alert('Error al procesar el archivo.');
  } finally {
    showLoader(false);
    document.getElementById('btn-import').disabled = false;
  }
}
</script>
</body>
</html>
