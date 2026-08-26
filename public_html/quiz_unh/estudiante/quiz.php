<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/config.php';
requireRol('estudiante');

$nombre   = htmlspecialchars($_SESSION['nombre']           ?? '', ENT_QUOTES);
$apellidos= htmlspecialchars($_SESSION['apellidos']        ?? '', ENT_QUOTES);
$codigo   = htmlspecialchars($_SESSION['codigo_matricula'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz — Sistema UNH</title>
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
.site-header .inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:1rem;height:65px}
.site-header img.logo{width:44px;height:44px;border-radius:50%;border:2px solid var(--unh-dorado);object-fit:cover;flex-shrink:0}
.logo-fallback{width:44px;height:44px;border-radius:50%;border:2px solid var(--unh-dorado);background:var(--unh-azul-mid);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--unh-dorado);flex-shrink:0}
.header-text{flex:1}
.header-text h1{font-size:.92rem;font-weight:700}
.header-text p{font-size:.75rem;opacity:.8;margin-top:1px}
.header-right{display:flex;align-items:center;gap:.8rem;margin-left:auto}
.badge-codigo{background:var(--unh-dorado);color:var(--unh-azul);padding:.25rem .65rem;border-radius:20px;font-size:.78rem;font-weight:700}
.btn-logout{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.4);color:#fff;padding:.35rem .8rem;border-radius:6px;font-size:.78rem;cursor:pointer;text-decoration:none;transition:background .2s}
.btn-logout:hover{background:rgba(255,255,255,.25)}

/* ── Contenido ── */
main{flex:1;max-width:900px;margin:0 auto;padding:1.5rem 1rem 3rem;width:100%}

/* ── PANTALLA 1: Selección de caso ── */
#screen-casos h2{font-size:1.2rem;color:var(--unh-azul);margin-bottom:1.2rem;font-weight:700}
.casos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.2rem}
.caso-card{background:var(--surface);border:2px solid var(--border);border-radius:12px;padding:1.3rem;cursor:pointer;transition:border-color .2s,box-shadow .2s,transform .15s}
.caso-card:hover{border-color:var(--unh-azul);box-shadow:0 4px 16px rgba(26,58,107,.14);transform:translateY(-2px)}
.caso-card.selected{border-color:var(--unh-dorado);box-shadow:0 0 0 3px rgba(245,197,24,.22)}
.caso-num{font-size:.72rem;font-weight:700;color:var(--unh-azul-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem}
.caso-titulo{font-size:.95rem;font-weight:700;color:#1a2a3a;margin-bottom:.4rem;line-height:1.3}
.caso-rubro{font-size:.78rem;color:#64748b}
.caso-card .rubro-pill{display:inline-block;background:#eef1f7;color:var(--unh-azul);padding:.15rem .55rem;border-radius:20px;font-size:.72rem;font-weight:600;margin-top:.5rem}
.btn-comenzar{margin-top:1.5rem;padding:.8rem 2.2rem;background:var(--unh-azul);color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;transition:background .2s,transform .1s}
.btn-comenzar:hover:not(:disabled){background:var(--unh-azul-mid)}
.btn-comenzar:active:not(:disabled){transform:scale(.98)}
.btn-comenzar:disabled{opacity:.45;cursor:not-allowed}

/* ── PANTALLA 2: Quiz ── */
#screen-quiz{display:none}
.quiz-header-bar{background:var(--unh-azul);color:#fff;border-radius:12px;padding:1.1rem 1.4rem;margin-bottom:1.2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.6rem}
.quiz-header-bar h2{font-size:1rem;font-weight:700;flex:1}
.quiz-header-bar .rubro-tag{font-size:.75rem;opacity:.8}
.score-badge{background:var(--unh-dorado);color:var(--unh-azul);padding:.3rem .9rem;border-radius:20px;font-size:.95rem;font-weight:800;white-space:nowrap}

.progress-wrap{margin-bottom:1.4rem}
.progress-label{display:flex;justify-content:space-between;font-size:.78rem;color:#64748b;margin-bottom:.3rem}
.progress-bar{height:8px;background:rgba(26,58,107,.12);border-radius:4px;overflow:hidden}
.progress-fill{height:100%;background:var(--unh-dorado);border-radius:4px;transition:width .4s ease}

/* descripción del caso */
.caso-desc{background:var(--unh-azul);color:#fff;border-radius:10px;padding:1rem 1.2rem;margin-bottom:1.5rem;font-size:.88rem;line-height:1.6}
.caso-desc strong{color:var(--unh-dorado)}

/* tarjetas situación */
.sit-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.2rem 1.3rem;margin-bottom:1rem;border-left:4px solid var(--unh-azul);transition:border-color .3s,background .3s}
.sit-card.correct{background:#f0fdf7;border-left-color:var(--unh-verde)}
.sit-card.wrong{background:#fef2f2;border-left-color:var(--unh-rojo)}
.sit-num{font-size:.72rem;font-weight:700;color:var(--unh-azul-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.sit-enunciado{font-size:.92rem;color:#1a2a3a;line-height:1.5;margin-bottom:.8rem;font-weight:500}
.sit-pregunta{font-size:.82rem;color:#64748b;margin-bottom:.8rem}
.opciones{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.8rem}
.opt-btn{padding:.4rem 1rem;border:1.5px solid var(--unh-azul);border-radius:20px;background:#fff;color:var(--unh-azul);font-size:.83rem;font-weight:600;cursor:pointer;transition:background .15s,color .15s,transform .1s}
.opt-btn:hover:not(:disabled){background:var(--unh-azul);color:#fff;transform:scale(1.03)}
.opt-btn:disabled{cursor:not-allowed;opacity:.7}
.opt-btn.elegida-ok{background:var(--unh-verde);border-color:var(--unh-verde);color:#fff}
.opt-btn.elegida-fail{background:var(--unh-rojo);border-color:var(--unh-rojo);color:#fff}
.opt-btn.correcta-revelada{background:var(--unh-verde);border-color:var(--unh-verde);color:#fff}
.feedback-box{display:none;padding:.55rem .8rem;border-radius:8px;font-size:.82rem;line-height:1.4}
.feedback-box.ok{display:block;background:#f0fdf7;border:1px solid #a7f3d0;color:#065f46}
.feedback-box.fail{display:block;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
.pts-pill{display:inline-block;margin-left:.5rem;padding:.1rem .5rem;border-radius:12px;font-weight:700;font-size:.78rem}
.pts-pill.ok{background:var(--unh-verde);color:#fff}
.pts-pill.fail{background:var(--unh-rojo);color:#fff}

/* ── PANTALLA 3: Resultado ── */
#screen-resultado{display:none;text-align:center}
.resultado-circulo{width:130px;height:130px;border-radius:50%;background:var(--unh-azul);display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto 1.4rem;box-shadow:0 6px 20px rgba(26,58,107,.25)}
.resultado-pts{font-size:2.4rem;font-weight:900;color:var(--unh-dorado);line-height:1}
.resultado-de{font-size:.78rem;color:rgba(255,255,255,.7);margin-top:.2rem}
.resultado-titulo{font-size:1.4rem;font-weight:800;color:var(--unh-azul);margin-bottom:.6rem}
.resultado-msg{font-size:.92rem;color:#4a5568;margin-bottom:1.8rem;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.5}
.resultado-btns{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}
.btn-sec{padding:.65rem 1.4rem;border:2px solid var(--unh-azul);border-radius:8px;background:#fff;color:var(--unh-azul);font-size:.88rem;font-weight:700;cursor:pointer;transition:background .2s}
.btn-sec:hover{background:#eef1f7}
.btn-hist{padding:.65rem 1.4rem;border:2px solid var(--border);border-radius:8px;background:#fff;color:#4a5568;font-size:.88rem;font-weight:600;cursor:pointer;transition:background .2s}
.btn-hist:hover{background:#eef1f7}

/* ── Historial ── */
#screen-historial{display:none}
#screen-historial h2{font-size:1.1rem;color:var(--unh-azul);font-weight:700;margin-bottom:1rem}
.back-link{display:inline-flex;align-items:center;gap:.3rem;color:var(--unh-azul);font-size:.85rem;font-weight:600;cursor:pointer;margin-bottom:1rem;text-decoration:none}
.back-link:hover{text-decoration:underline}
.table-wrap{overflow-x:auto;border-radius:10px;border:1px solid var(--border)}
table{width:100%;border-collapse:collapse;font-size:.84rem}
thead th{background:var(--unh-azul);color:#fff;padding:.65rem .9rem;text-align:left;font-weight:600;white-space:nowrap}
tbody tr:nth-child(even){background:#f8fafc}
tbody td{padding:.6rem .9rem;border-bottom:1px solid var(--border);color:#374151}
.pill-aprobado{background:#d1fae5;color:#065f46;padding:.15rem .55rem;border-radius:12px;font-size:.75rem;font-weight:700}
.pill-desaprobado{background:#fee2e2;color:#991b1b;padding:.15rem .55rem;border-radius:12px;font-size:.75rem;font-weight:700}

/* ── Loader ── */
.loader-overlay{display:none;position:fixed;inset:0;background:rgba(26,58,107,.18);z-index:999;align-items:center;justify-content:center}
.loader-overlay.show{display:flex}
.spinner{width:44px;height:44px;border:4px solid rgba(255,255,255,.4);border-top-color:var(--unh-dorado);border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:600px){
  .casos-grid{grid-template-columns:1fr}
  .quiz-header-bar{flex-direction:column;align-items:flex-start}
  .resultado-btns{flex-direction:column;align-items:center}
  .header-right .badge-codigo{display:none}
}
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
      <h1>Sistema de Quiz — UNH · Ingeniería de Sistemas</h1>
      <p>Bienvenido, <?= $nombre . ' ' . $apellidos ?></p>
    </div>
    <div class="header-right">
      <span class="badge-codigo"><?= $codigo ?></span>
      <a href="../logout.php" class="btn-logout">Cerrar sesión</a>
    </div>
  </div>
</header>

<main>

  <!-- PANTALLA 1: Selección de caso -->
  <div id="screen-casos">
    <h2>Selecciona un caso para comenzar</h2>
    <div class="casos-grid" id="casos-grid">
      <p style="color:#64748b;font-size:.9rem">Cargando casos…</p>
    </div>
    <button class="btn-comenzar" id="btn-comenzar" disabled onclick="comenzarCaso()">
      Comenzar quiz
    </button>
  </div>

  <!-- PANTALLA 2: Quiz -->
  <div id="screen-quiz">
    <div class="quiz-header-bar">
      <div>
        <h2 id="quiz-titulo"></h2>
        <div class="rubro-tag" id="quiz-rubro"></div>
      </div>
      <div class="score-badge" id="score-badge">0 / 16 pts</div>
    </div>
    <div class="progress-wrap">
      <div class="progress-label">
        <span id="prog-label">Situación 0 de 4</span>
        <span id="prog-pct">0%</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" id="progress-fill" style="width:0%"></div></div>
    </div>
    <div class="caso-desc" id="caso-desc"></div>
    <div id="situaciones-wrap"></div>
  </div>

  <!-- PANTALLA 3: Resultado -->
  <div id="screen-resultado">
    <div class="resultado-circulo">
      <div class="resultado-pts" id="res-pts">0</div>
      <div class="resultado-de" id="res-de">de 16 pts</div>
    </div>
    <div class="resultado-titulo" id="res-titulo"></div>
    <div class="resultado-msg"   id="res-msg"></div>
    <div class="resultado-btns">
      <button class="btn-primary btn-comenzar" style="margin-top:0" onclick="volverCasos()">Elegir otro caso</button>
      <button class="btn-sec" onclick="repetirCaso()">Repetir</button>
      <button class="btn-hist" onclick="verHistorial()">Ver mi historial</button>
    </div>
  </div>

  <!-- PANTALLA 4: Historial -->
  <div id="screen-historial">
    <a class="back-link" onclick="volverCasos()">&#8592; Volver al inicio</a>
    <h2>Mi historial de intentos</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Caso</th><th>Puntaje</th><th>Máx</th><th>%</th><th>Fecha</th><th>Estado</th>
          </tr>
        </thead>
        <tbody id="historial-body"></tbody>
      </table>
    </div>
  </div>

</main>

<script>
const API = '../php/api.php';
let casoSelId   = null;
let intentoId   = null;
let casoActual  = null;
let puntaje     = 0;
let respondidas = 0;

// ── Loader ───────────────────────────────────────────────────────────────────
function showLoader(v){ document.getElementById('loader').classList.toggle('show', v); }

// ── Utilidades de pantalla ───────────────────────────────────────────────────
function showScreen(id) {
  ['screen-casos','screen-quiz','screen-resultado','screen-historial']
    .forEach(s => document.getElementById(s).style.display = 'none');
  document.getElementById(id).style.display = 'block';
}

// ── PANTALLA 1: Cargar casos ─────────────────────────────────────────────────
async function cargarCasos() {
  showScreen('screen-casos');
  showLoader(true);
  try {
    const r  = await fetch(API + '?action=casos');
    const js = await r.json();
    const grid = document.getElementById('casos-grid');
    grid.innerHTML = '';
    (js.data || []).forEach((c, i) => {
      const div = document.createElement('div');
      div.className = 'caso-card';
      div.dataset.id = c.id;
      div.innerHTML = `
        <div class="caso-num">Caso ${i+1}</div>
        <div class="caso-titulo">${esc(c.titulo)}</div>
        <div class="caso-rubro">${esc(c.resumen)}…</div>
        <div><span class="rubro-pill">${esc(c.rubro)}</span></div>`;
      div.addEventListener('click', () => seleccionarCaso(c.id, div));
      grid.appendChild(div);
    });
  } catch(e) {
    console.error(e);
  } finally { showLoader(false); }
}

function seleccionarCaso(id, el) {
  document.querySelectorAll('.caso-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  casoSelId = id;
  document.getElementById('btn-comenzar').disabled = false;
}

async function comenzarCaso() {
  if (!casoSelId) return;
  showLoader(true);
  try {
    // Obtener datos del caso
    const rc  = await fetch(API + '?action=caso&id=' + casoSelId);
    const jc  = await rc.json();
    casoActual = jc.data;

    // Iniciar intento
    const ri  = await fetch(API + '?action=iniciar', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({caso_id: casoSelId})
    });
    const ji  = await ri.json();
    intentoId = ji.data.intento_id;
    puntaje   = 0;
    respondidas = 0;

    // Renderizar pantalla quiz
    document.getElementById('quiz-titulo').textContent = casoActual.titulo;
    document.getElementById('quiz-rubro').textContent  = casoActual.rubro;
    document.getElementById('caso-desc').innerHTML =
      '<strong>Contexto:</strong> ' + esc(casoActual.descripcion);
    document.getElementById('score-badge').textContent = '0 / 16 pts';
    updateProgress();

    const wrap = document.getElementById('situaciones-wrap');
    wrap.innerHTML = '';
    casoActual.situaciones.forEach(s => {
      wrap.appendChild(crearTarjetaSit(s));
    });
    showScreen('screen-quiz');
  } catch(e) { console.error(e); alert('Error al iniciar el quiz.'); }
  finally { showLoader(false); }
}

function crearTarjetaSit(sit) {
  const div = document.createElement('div');
  div.className = 'sit-card';
  div.id = 'sit-' + sit.id;
  div.innerHTML = `
    <div class="sit-num">Situación ${sit.orden}</div>
    <div class="sit-enunciado">${esc(sit.enunciado)}</div>
    <div class="sit-pregunta">¿Qué tipo de situación de decisión representa?</div>
    <div class="opciones">
      ${['Certeza','Riesgo','Incertidumbre'].map(op =>
        `<button class="opt-btn" onclick="responder(${sit.id}, '${op}', this)">${op}</button>`
      ).join('')}
    </div>
    <div class="feedback-box" id="fb-${sit.id}"></div>`;
  return div;
}

async function responder(sitId, opcion, btn) {
  // Deshabilitar todos los botones de esta situación
  const card   = document.getElementById('sit-' + sitId);
  const botones = card.querySelectorAll('.opt-btn');
  botones.forEach(b => b.disabled = true);

  showLoader(true);
  try {
    const r  = await fetch(API + '?action=responder', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({intento_id: intentoId, situacion_id: sitId, respuesta: opcion})
    });
    const js = await r.json();
    const d  = js.data;

    puntaje = d.puntaje_actual;
    respondidas++;
    updateScore(puntaje);
    updateProgress();

    // Colorear botones
    botones.forEach(b => {
      if (b.textContent === d.tipo_correcto) b.classList.add('correcta-revelada');
    });
    if (d.es_correcta) {
      btn.classList.add('elegida-ok');
      card.classList.add('correct');
    } else {
      btn.classList.add('elegida-fail');
      card.classList.add('wrong');
    }

    // Feedback
    const fb = document.getElementById('fb-' + sitId);
    const ptsHtml = d.es_correcta
      ? '<span class="pts-pill ok">+4 pts</span>'
      : '<span class="pts-pill fail">+0 pts</span>';
    fb.className = 'feedback-box ' + (d.es_correcta ? 'ok' : 'fail');
    fb.innerHTML = esc(d.feedback) + ptsHtml;

    // ¿Terminó el quiz?
    if (respondidas >= casoActual.situaciones.length) {
      setTimeout(() => finalizarQuiz(), 900);
    }
  } catch(e) { console.error(e); botones.forEach(b => b.disabled = false); }
  finally { showLoader(false); }
}

async function finalizarQuiz() {
  showLoader(true);
  try {
    const r  = await fetch(API + '?action=finalizar', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({intento_id: intentoId})
    });
    const js = await r.json();
    const d  = js.data;

    document.getElementById('res-pts').textContent = d.puntaje;
    document.getElementById('res-de').textContent  = 'de ' + d.puntaje_max + ' pts (' + d.porcentaje + '%)';

    let titulo = '';
    if (d.puntaje === d.puntaje_max) titulo = '¡Puntaje Perfecto!';
    else if (d.puntaje >= 12)        titulo = '¡Muy bien!';
    else if (d.puntaje >= 8)         titulo = 'Buen intento';
    else                              titulo = 'Sigue practicando';

    document.getElementById('res-titulo').textContent = titulo;
    document.getElementById('res-msg').textContent    = d.mensaje;
    showScreen('screen-resultado');
  } catch(e) { console.error(e); }
  finally { showLoader(false); }
}

function updateScore(pts) {
  document.getElementById('score-badge').textContent = pts + ' / 16 pts';
}
function updateProgress() {
  const pct = Math.round((respondidas / 4) * 100);
  document.getElementById('progress-fill').style.width = pct + '%';
  document.getElementById('prog-label').textContent = 'Situación ' + respondidas + ' de 4';
  document.getElementById('prog-pct').textContent   = pct + '%';
}

function volverCasos() { casoSelId = null; cargarCasos(); }
function repetirCaso() {
  const tmpId = casoSelId;
  casoSelId = tmpId;
  comenzarCaso();
}

async function verHistorial() {
  showLoader(true);
  showScreen('screen-historial');
  try {
    const r  = await fetch(API + '?action=mis_intentos');
    const js = await r.json();
    const tbody = document.getElementById('historial-body');
    tbody.innerHTML = '';
    if (!js.data || js.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:1rem">Sin intentos completados.</td></tr>';
    } else {
      js.data.forEach((row, i) => {
        const tr = document.createElement('tr');
        const fecha = row.finalizado_en ? row.finalizado_en.substring(0,16).replace('T',' ') : '—';
        const estado = row.aprobado == 1
          ? '<span class="pill-aprobado">Aprobado</span>'
          : '<span class="pill-desaprobado">Desaprobado</span>';
        tr.innerHTML = `<td>${i+1}</td><td>${esc(row.caso_titulo)}</td>
          <td>${row.puntaje}</td><td>${row.puntaje_max}</td>
          <td>${row.porcentaje}%</td><td>${fecha}</td><td>${estado}</td>`;
        tbody.appendChild(tr);
      });
    }
  } catch(e) { console.error(e); }
  finally { showLoader(false); }
}

function esc(s){ const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

// Iniciar
cargarCasos();
</script>
</body>
</html>
