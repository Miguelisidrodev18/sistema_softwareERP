<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requerir_rol('dentista');

$usuario = usuario_actual();

// Estadísticas rápidas
$totalPacientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente'")->fetchColumn();
$citasHoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()");
$citasHoy->execute();
$citasHoy = $citasHoy->fetchColumn();
$citasPendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'pendiente'")->fetchColumn();

// --- Datos para gráficos ---

// Citas por día (últimos 7 días, incluye hoy)
$citasPorDia = [];
$stmt = $pdo->prepare("SELECT fecha, COUNT(*) AS total FROM citas WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE() GROUP BY fecha");
$stmt->execute();
$citasPorDiaRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$labelsDias = [];
$dataDias = [];
for ($i = 6; $i >= 0; $i--) {
    $f = date('Y-m-d', strtotime("-$i days"));
    $labelsDias[] = date('d/m', strtotime($f));
    $dataDias[] = (int)($citasPorDiaRaw[$f] ?? 0);
}

// Distribución de citas por estado
$stmt = $pdo->query("SELECT estado, COUNT(*) AS total FROM citas GROUP BY estado");
$estadosRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$estadosLabels = ['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];
$dataEstados = [];
foreach ($estadosLabels as $key => $label) {
    $dataEstados[] = (int)($estadosRaw[$key] ?? 0);
}

// Tratamientos más frecuentes (top 5)
$stmt = $pdo->query("SELECT descripcion, COUNT(*) AS total FROM tratamientos GROUP BY descripcion ORDER BY total DESC LIMIT 5");
$topTratamientos = $stmt->fetchAll();
$labelsTrat = array_column($topTratamientos, 'descripcion');
$dataTrat = array_map('intval', array_column($topTratamientos, 'total'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel del dentista — Clínica Dental</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<style>
/* ===========================================================
   SISTEMA DENTAL — Design tokens y estilos BASE compartidos
   (estilos específicos de cada página van dentro de cada
   archivo .php, en su propio <style>)
   Paleta: clínica, confiable, precisa (navy + teal + mint)
   Tipografía: Space Grotesk (display) / Inter (texto) / JetBrains Mono (datos)
   =========================================================== */

:root {
    --color-bg: #F4FAF9;
    --color-surface: #FFFFFF;
    --color-primary: #0F2B3D;
    --color-primary-light: #1C4A63;
    --accent-teal: #2FA39B;
    --accent-teal-dark: #22766F;
    --accent-mint: #BFEDE2;
    --accent-coral: #FF6B5E;
    --text-main: #10242E;
    --text-muted: #5B7480;
    --border-soft: #DCEBE8;
    --radius: 14px;
    --shadow-soft: 0 10px 30px rgba(15, 43, 61, 0.08);
    --shadow-card: 0 25px 60px rgba(15, 43, 61, 0.18);
    --font-display: 'Space Grotesk', sans-serif;
    --font-body: 'Inter', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
}

* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: var(--font-body);
    background: var(--color-bg);
    color: var(--text-main);
    -webkit-font-smoothing: antialiased;
}

h1, h2, h3, .display {
    font-family: var(--font-display);
    letter-spacing: -0.01em;
    margin: 0;
}

a { color: inherit; }

.eyebrow {
    font-family: var(--font-mono);
    font-size: 12px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--accent-teal-dark);
}

/* ---------------- Formularios (compartido) ---------------- */

.field { margin-bottom: 18px; }

.field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 6px;
    letter-spacing: 0.02em;
}

.field input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 9px;
    border: 1.5px solid var(--border-soft);
    font-size: 14px;
    font-family: var(--font-body);
    background: #FBFEFD;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.field input:focus {
    outline: none;
    border-color: var(--accent-teal);
    box-shadow: 0 0 0 3px rgba(47,163,155,0.15);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px 18px;
    border-radius: 9px;
    border: none;
    background: var(--color-primary);
    color: #fff;
    font-family: var(--font-body);
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.15s ease;
}

.btn:hover { background: var(--accent-teal-dark); transform: translateY(-1px); }

.form-foot {
    margin-top: 18px;
    text-align: center;
    font-size: 13px;
    color: var(--text-muted);
}

.form-foot a { color: var(--accent-teal-dark); font-weight: 600; text-decoration: none; }

.alert {
    padding: 11px 14px;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 18px;
}

.alert-error { background: #FFECEA; color: #B7332A; border: 1px solid #FFD2CD; }
.alert-ok { background: #E7F8F1; color: #1F7A56; border: 1px solid #C6EEDD; }

/* ===========================================================
   DASHBOARD SHELL (compartido por dentista/ y paciente/)
   =========================================================== */

.app-shell { min-height: 100vh; display: flex; }

.sidebar {
    width: 240px;
    background: var(--color-primary);
    color: #EAF6F4;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.sidebar .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 40px;
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 16px;
}
.sidebar .brand svg { width: 26px; height: 26px; }

.sidebar nav { display: flex; flex-direction: column; gap: 4px; }

.sidebar nav a {
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 14px;
    text-decoration: none;
    color: rgba(234,246,244,0.75);
    transition: all 0.15s ease;
}
.sidebar nav a {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar nav a svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    opacity: 0.85;
}

.sidebar nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }
.sidebar nav a.active { background: var(--accent-teal); color: #fff; font-weight: 600; }

.sidebar .logout {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.sidebar .logout a { color: rgba(255,107,94,0.85); font-size: 13px; text-decoration: none; }

.main {
    flex: 1;
    padding: 40px 48px;
    max-width: 1100px;
}

.main header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
}

.main header h1 { font-size: 26px; color: var(--color-primary); }
.main header p { margin: 6px 0 0; color: var(--text-muted); font-size: 14px; }

.badge {
    font-family: var(--font-mono);
    font-size: 12px;
    background: var(--accent-mint);
    color: var(--accent-teal-dark);
    padding: 6px 12px;
    border-radius: 20px;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--color-surface);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius);
    padding: 22px;
    box-shadow: var(--shadow-soft);
}

.stat-card .num { font-family: var(--font-display); font-size: 30px; color: var(--color-primary); }
.stat-card .label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

.panel {
    background: var(--color-surface);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius);
    padding: 26px;
    box-shadow: var(--shadow-soft);
    margin-bottom: 24px;
}

.panel h3 { font-size: 16px; color: var(--color-primary); margin-bottom: 16px; }

table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
th { text-align: left; color: var(--text-muted); font-weight: 600; padding: 10px 8px; border-bottom: 1px solid var(--border-soft); font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
td { padding: 12px 8px; border-bottom: 1px solid var(--border-soft); }

.pill { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.pill-pendiente { background: #FFF3D6; color: #9A6B00; }
.pill-confirmada { background: #E7F8F1; color: #1F7A56; }
.pill-cancelada { background: #FFECEA; color: #B7332A; }
.pill-completada { background: #E5ECF5; color: #2E4C82; }

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-muted);
}
.empty-state .icon { width: 46px; height: 46px; margin: 0 auto 14px; color: var(--accent-teal); }

form.inline-form .field { margin-bottom: 14px; }

.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

@media (max-width: 900px) {
    .app-shell { flex-direction: column; }
    .sidebar { width: 100%; flex-direction: row; align-items: center; padding: 16px 20px; }
    .sidebar nav { flex-direction: row; }
    .sidebar .logout { margin: 0 0 0 auto; padding: 0; border: none; }
    .main { padding: 24px; }
    .two-col { grid-template-columns: 1fr; }
}

.charts-grid {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 18px;
    margin-bottom: 24px;
}

@media (max-width: 800px) {
    .charts-grid { grid-template-columns: 1fr; }
}

</style>
</head>
<body>
<div class="app-shell">

    <aside class="sidebar">
        <div class="brand">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2c-2.5 0-4.5 1.6-5.4 3.6C5.9 7.4 5.5 9.6 5.8 12c.3 2.6 1 5.3 1.9 7.6.3.9 1.6 1 2 .1.5-1.1.9-2.8 1.3-4 .2-.6.6-1 1-1s.8.4 1 1c.4 1.2.8 2.9 1.3 4 .4.9 1.7.8 2-.1.9-2.3 1.6-5 1.9-7.6.3-2.4-.1-4.6-.8-6.4C16.5 3.6 14.5 2 12 2Z" stroke="#BFEDE2" stroke-width="1.4"/>
            </svg>
            Clínica Dental
        </div>
        <nav>
            <a href="dashboard.php" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg><span>Panel</span></a>
            <a href="pacientes.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15 14.3c2.6.4 4.5 2.6 4.5 5.2"/></svg><span>Pacientes</span></a>
            <a href="agenda.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/></svg><span>Agenda</span></a>
        </nav>
        <div class="logout"><a href="../logout.php">Cerrar sesión</a></div>
    </aside>

    <main class="main">
        <header>
            <div>
                <h1>Hola, <?= htmlspecialchars($usuario['nombre']) ?></h1>
                <p>Este es tu panel general. Vamos a completarlo con las funciones que necesites.</p>
            </div>
            <span class="badge">Cuenta dentista</span>
        </header>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="num"><?= (int)$totalPacientes ?></div>
                <div class="label">Pacientes registrados</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= (int)$citasHoy ?></div>
                <div class="label">Citas para hoy</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= (int)$citasPendientes ?></div>
                <div class="label">Citas pendientes de confirmar</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="panel">
                <h3>Citas — últimos 7 días</h3>
                <canvas id="chartCitasDia" height="180"></canvas>
            </div>
            <div class="panel">
                <h3>Estado de citas</h3>
                <canvas id="chartEstados" height="180"></canvas>
            </div>
        </div>

        <div class="panel">
            <h3>Tratamientos más frecuentes</h3>
            <?php if (empty($labelsTrat)): ?>
                <div class="empty-state">Aún no hay tratamientos registrados para mostrar aquí.</div>
            <?php else: ?>
                <canvas id="chartTratamientos" height="110"></canvas>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>Historia clínica</h3>
            <p style="color:var(--text-muted); font-size:14px; line-height:1.6; margin-bottom:16px;">
                Busca un paciente para ver su historial de evolución, su ficha de anamnesis y su odontograma.
            </p>
            <a href="pacientes.php" class="btn" style="max-width:240px; display:inline-flex;">Ir a pacientes</a>
        </div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#5B7480';

new Chart(document.getElementById('chartCitasDia'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsDias) ?>,
        datasets: [{
            label: 'Citas',
            data: <?= json_encode($dataDias) ?>,
            backgroundColor: '#2FA39B',
            borderRadius: 6,
            maxBarThickness: 36,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EAF3F1' } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('chartEstados'), {
    type: 'doughnut',
    data: {
        labels: ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'],
        datasets: [{
            data: <?= json_encode($dataEstados) ?>,
            backgroundColor: ['#D9A441', '#1F7A56', '#2E4C82', '#B7332A'],
            borderWidth: 0,
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } } },
        cutout: '65%'
    }
});

<?php if (!empty($labelsTrat)): ?>
new Chart(document.getElementById('chartTratamientos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labelsTrat) ?>,
        datasets: [{
            label: 'Veces realizado',
            data: <?= json_encode($dataTrat) ?>,
            backgroundColor: '#0F2B3D',
            borderRadius: 6,
            maxBarThickness: 28,
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EAF3F1' } },
            y: { grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>
