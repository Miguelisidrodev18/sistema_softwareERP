<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requerir_rol('dentista');

$usuario = usuario_actual();
$pacienteId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'paciente'");
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$mensaje = '';

// --- Guardar nueva evolución (tratamiento) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'evolucion') {
    $descripcion = trim($_POST['descripcion'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');

    if ($descripcion !== '') {
        $stmt = $pdo->prepare("INSERT INTO tratamientos (paciente_id, dentista_id, descripcion, notas, fecha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pacienteId, $usuario['id'], $descripcion, $notas ?: null, $fecha]);
        $mensaje = 'Registro de evolución agregado.';
    }
}

// --- Guardar anamnesis ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'anamnesis') {
    $campos = [
        'alergias' => trim($_POST['alergias'] ?? ''),
        'enfermedades' => trim($_POST['enfermedades'] ?? ''),
        'medicamentos' => trim($_POST['medicamentos'] ?? ''),
        'antecedentes_familiares' => trim($_POST['antecedentes_familiares'] ?? ''),
        'habitos' => trim($_POST['habitos'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? ''),
    ];

    $stmt = $pdo->prepare("SELECT id FROM anamnesis WHERE paciente_id = ?");
    $stmt->execute([$pacienteId]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE anamnesis SET alergias=?, enfermedades=?, medicamentos=?, antecedentes_familiares=?, habitos=?, observaciones=? WHERE paciente_id=?");
        $stmt->execute([...array_values($campos), $pacienteId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO anamnesis (paciente_id, alergias, enfermedades, medicamentos, antecedentes_familiares, habitos, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pacienteId, ...array_values($campos)]);
    }
    $mensaje = 'Anamnesis guardada.';
}

// --- Guardar odontograma ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'odontograma') {
    $datos = $_POST['odontograma_json'] ?? '{}';
    json_decode($datos); // valida que sea JSON
    if (json_last_error() === JSON_ERROR_NONE) {
        $stmt = $pdo->prepare("SELECT id FROM odontogramas WHERE paciente_id = ?");
        $stmt->execute([$pacienteId]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE odontogramas SET datos = ? WHERE paciente_id = ?");
            $stmt->execute([$datos, $pacienteId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO odontogramas (paciente_id, datos) VALUES (?, ?)");
            $stmt->execute([$pacienteId, $datos]);
        }
        $mensaje = 'Odontograma guardado.';
    }
}

// --- Guardar consentimiento informado ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'consentimiento') {
    $texto = trim($_POST['texto_consentimiento'] ?? '');
    $firmadoPor = trim($_POST['firmado_por'] ?? '');
    $fechaCons = $_POST['fecha_consentimiento'] ?? date('Y-m-d');
    $acepto = isset($_POST['acepto']);

    if ($texto !== '' && $firmadoPor !== '' && $acepto) {
        $stmt = $pdo->prepare("INSERT INTO consentimientos (paciente_id, dentista_id, texto, firmado_por, fecha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pacienteId, $usuario['id'], $texto, $firmadoPor, $fechaCons]);
        $mensaje = 'Consentimiento registrado.';
    } else {
        $mensaje = 'Falta marcar la aceptación o completar el nombre de quien firma.';
    }
}

// --- Cargar datos actuales ---
$stmt = $pdo->prepare("
    SELECT t.*, u.nombre AS dentista_nombre
    FROM tratamientos t
    LEFT JOIN usuarios u ON u.id = t.dentista_id
    WHERE t.paciente_id = ?
    ORDER BY t.fecha DESC, t.id DESC
");
$stmt->execute([$pacienteId]);
$evoluciones = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM anamnesis WHERE paciente_id = ?");
$stmt->execute([$pacienteId]);
$anamnesis = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT datos FROM odontogramas WHERE paciente_id = ?");
$stmt->execute([$pacienteId]);
$odonto = $stmt->fetch();
$odontoJson = $odonto ? $odonto['datos'] : '{}';

$stmt = $pdo->prepare("
    SELECT c.*, u.nombre AS dentista_nombre
    FROM consentimientos c
    LEFT JOIN usuarios u ON u.id = c.dentista_id
    WHERE c.paciente_id = ?
    ORDER BY c.creado_en DESC
");
$stmt->execute([$pacienteId]);
$consentimientos = $stmt->fetchAll();

$textoConsentimientoDefault = "Declaro que he sido informado(a) de forma clara y comprensible sobre el diagnóstico, el tratamiento odontológico propuesto, sus alternativas, riesgos, molestias y beneficios esperados. He podido resolver mis dudas con el profesional tratante y otorgo mi consentimiento libre y voluntario para que se realicen los procedimientos odontológicos que se registren en mi historia clínica.";

$dientesSuperior = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
$dientesInferior = [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];

// Dentición temporal (niño) — notación FDI 51-55/61-65 (superior) y 71-75/81-85 (inferior)
$dientesSuperiorNino = [55,54,53,52,51,61,62,63,64,65];
$dientesInferiorNino = [85,84,83,82,81,71,72,73,74,75];

// Catálogo de condiciones del odontograma (clave => [etiqueta, color])
$condiciones = [
    'sano'            => ['Sano', '#FFFFFF'],
    'caries'          => ['Caries', '#B7332A'],
    'corona'          => ['Corona', '#F1B44C'],
    'corona_temp'     => ['Corona (Temp.)', '#F6C177'],
    'ausente'         => ['Ausente', '#9AA5B1'],
    'fractura'        => ['Fractura', '#7C3AED'],
    'diastema'        => ['Diastema', '#22B8CF'],
    'obturacion'      => ['Obturación', '#4B7BEC'],
    'protesis_remov'  => ['Prótesis Remov.', '#EC4899'],
    'desplazamiento'  => ['Desplazamiento', '#F97316'],
    'rotacion'        => ['Rotación', '#FACC15'],
    'fusion'          => ['Fusión', '#06B6D4'],
    'remanente_rad'   => ['Remanente Rad.', '#92400E'],
    'erupcion'        => ['Erupción', '#22C55E'],
    'transposicion'   => ['Transposición', '#14B8A6'],
    'supernumerario'  => ['Supernumerario', '#EAB308'],
    'pulpa'           => ['Pulpa', '#DC2626'],
    'protesis'        => ['Prótesis', '#BE185D'],
    'perno'           => ['Perno', '#4338CA'],
    'ortod_fija'      => ['Ortodoncia Fija', '#3B82F6'],
    'protesis_fija'   => ['Prótesis Fija', '#DB2777'],
    'implante'        => ['Implante', '#10B981'],
    'macrodoncia'     => ['Macrodoncia', '#84CC16'],
    'microdoncia'     => ['Microdoncia', '#65A30D'],
    'discromia'       => ['Discromía', '#A16207'],
    'desgaste'        => ['Desgaste', '#78716C'],
    'impactado_p'     => ['Impactado P.', '#F59E0B'],
    'intrusion'       => ['Intrusión', '#EF4444'],
    'edentulismo'     => ['Edentulismo', '#57534E'],
    'ectopico'        => ['Ectópico', '#D946EF'],
    'impactado'       => ['Impactado', '#B45309'],
    'ortod_remov'     => ['Ortod. Remov.', '#60A5FA'],
    'extrusion'       => ['Extrusión', '#FB7185'],
    'poste'           => ['Poste', '#6366F1'],
    'extraer'         => ['Extraer', '#991B1B'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historia clínica — <?= htmlspecialchars($paciente['nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

<style>
/* ===== Estilos base compartidos ===== */
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

/* ===== Estilos propios de esta pagina ===== */

/* Estilos específicos de esta página (historia clínica) */

.tabs {
    display: flex;
    gap: 6px;
    border-bottom: 1px solid var(--border-soft);
    margin-bottom: 22px;
}

.tab-btn {
    font-family: var(--font-body);
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-muted);
    background: none;
    border: none;
    padding: 10px 16px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: color 0.15s ease, border-color 0.15s ease;
}

.tab-btn:hover { color: var(--color-primary); }

.tab-btn.active {
    color: var(--color-primary);
    border-bottom-color: var(--accent-teal);
}

.odonto-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.cond-btn {
    --cond-color: #9AA5B1;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: var(--font-body);
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
    background: var(--color-surface);
    border: 1.5px solid var(--border-soft);
    border-radius: 20px;
    padding: 6px 12px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.cond-btn .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--cond-color);
    border: 1px solid rgba(0,0,0,0.15);
    flex-shrink: 0;
}

.cond-btn:hover { border-color: var(--cond-color); }

.cond-btn.active {
    border-color: var(--cond-color);
    background: color-mix(in srgb, var(--cond-color) 15%, white);
    color: var(--text-main);
}

.odonto-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}

.dentition-switch {
    display: inline-flex;
    gap: 4px;
    background: var(--color-bg);
    border: 1px solid var(--border-soft);
    border-radius: 20px;
    padding: 4px;
}

.dentition-switch button {
    font-family: var(--font-body);
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 16px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}

.dentition-switch button.active {
    background: var(--accent-teal);
    color: #fff;
}

.btn-outline-small {
    font-family: var(--font-body);
    font-size: 12.5px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 20px;
    border: 1.5px solid var(--border-soft);
    background: #fff;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-outline-small:hover { border-color: var(--accent-coral); color: var(--accent-coral); }

.odonto-chart {
    overflow-x: auto;
    padding: 10px 4px 18px;
}

.odonto-arch {
    display: flex;
    gap: 4px;
    justify-content: center;
    min-width: 760px;
    margin-bottom: 4px;
}

.odonto-arch.lower { margin-top: 4px; }

.arch-divider {
    width: 100%;
    min-width: 760px;
    border-top: 2px dashed var(--border-soft);
    margin: 6px 0 10px;
}

.tooth-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 44px;
    flex-shrink: 0;
}

.tooth-num {
    font-family: var(--font-mono);
    font-size: 10.5px;
    color: var(--text-muted);
    margin-bottom: 3px;
    font-weight: 600;
}

.tooth-icon {
    width: 30px;
    height: 30px;
    border-radius: 7px 7px 3px 3px;
    border: 1.5px solid var(--border-soft);
    background: #fff;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.1s ease, border-color 0.15s ease;
    margin-bottom: 3px;
}

.tooth-icon:hover { transform: translateY(-2px); border-color: var(--accent-teal); }

.tooth-icon svg { width: 20px; height: 20px; pointer-events: none; }

.tooth-icon.struck::after {
    content: '';
    position: absolute;
    left: 2px; right: 2px; top: 50%;
    border-top: 2px solid var(--accent-coral);
    transform: rotate(-18deg);
}

.tooth-cross {
    display: grid;
    grid-template-columns: repeat(3, 12px);
    grid-template-rows: repeat(3, 12px);
    gap: 1px;
}

.tooth-cross .zone {
    width: 12px;
    height: 12px;
    background: transparent;
    cursor: default;
}

.tooth-cross .zone.clickable {
    background: #fff;
    border: 1px solid var(--border-soft);
    cursor: pointer;
    transition: transform 0.08s ease;
}

.tooth-cross .zone.clickable:hover { transform: scale(1.15); }

.tooth-cross .z-top { grid-column: 2; grid-row: 1; }
.tooth-cross .z-left { grid-column: 1; grid-row: 2; }
.tooth-cross .z-center { grid-column: 2; grid-row: 2; }
.tooth-cross .z-right { grid-column: 3; grid-row: 2; }
.tooth-cross .z-bottom { grid-column: 2; grid-row: 3; }

.odonto-hint {
    color: var(--text-muted);
    font-size: 13px;
    margin-bottom: 16px;
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
            <a href="dashboard.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg><span>Panel</span></a>
            <a href="pacientes.php" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15 14.3c2.6.4 4.5 2.6 4.5 5.2"/></svg><span>Pacientes</span></a>
            <a href="agenda.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/></svg><span>Agenda</span></a>
        </nav>
        <div class="logout"><a href="../logout.php">Cerrar sesión</a></div>
    </aside>

    <main class="main">
        <header>
            <div>
                <a href="pacientes.php" style="font-size:13px; color:var(--text-muted); text-decoration:none;">&larr; Volver a pacientes</a>
                <h1 style="margin-top:6px;">Historia clínica de <?= htmlspecialchars($paciente['nombre']) ?></h1>
                <p><?= htmlspecialchars($paciente['email']) ?><?= $paciente['telefono'] ? ' · ' . htmlspecialchars($paciente['telefono']) : '' ?></p>
            </div>
            <a href="imprimir_historia.php?id=<?= $pacienteId ?>" target="_blank" class="btn" style="width:auto; background:var(--accent-coral);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M12 3v12m0 0-4-4m4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Descargar PDF
            </a>
        </header>

        <?php if ($mensaje): ?>
            <div class="alert alert-ok" style="margin-bottom:20px;"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="evolucion">Evolución</button>
            <button type="button" class="tab-btn" data-tab="anamnesis">Anamnesis</button>
            <button type="button" class="tab-btn" data-tab="odontograma">Odontograma</button>
            <button type="button" class="tab-btn" data-tab="consentimiento">Consentimiento</button>
        </div>

        <!-- ===== EVOLUCIÓN ===== -->
        <section class="tab-panel" id="tab-evolucion">
            <div class="panel">
                <h3>Agregar registro de evolución</h3>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="accion" value="evolucion">
                    <div class="two-col">
                        <div class="field">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="field">
                            <label>Tratamiento / diagnóstico</label>
                            <input type="text" name="descripcion" placeholder="Ej: Limpieza, extracción, resina..." required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Notas</label>
                        <input type="text" name="notas" placeholder="Observaciones de la consulta (opcional)">
                    </div>
                    <button type="submit" class="btn" style="max-width:220px;">Guardar registro</button>
                </form>
            </div>

            <div class="panel">
                <h3>Historial de registros</h3>
                <?php if (count($evoluciones) === 0): ?>
                    <div class="empty-state">Aún no hay registros de evolución para este paciente.</div>
                <?php else: ?>
                <table>
                    <thead><tr><th>Fecha</th><th>Tratamiento</th><th>Notas</th><th>Doctor</th></tr></thead>
                    <tbody>
                    <?php foreach ($evoluciones as $e): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($e['fecha'])) ?></td>
                            <td><?= htmlspecialchars($e['descripcion']) ?></td>
                            <td><?= htmlspecialchars($e['notas'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($e['dentista_nombre'] ?: '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </section>

        <!-- ===== ANAMNESIS ===== -->
        <section class="tab-panel" id="tab-anamnesis" hidden>
            <div class="panel">
                <h3>Ficha de anamnesis</h3>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="accion" value="anamnesis">

                    <div class="field">
                        <label>Alergias</label>
                        <input type="text" name="alergias" value="<?= htmlspecialchars($anamnesis['alergias'] ?? '') ?>" placeholder="Ej: penicilina, látex...">
                    </div>
                    <div class="field">
                        <label>Enfermedades / condiciones médicas</label>
                        <input type="text" name="enfermedades" value="<?= htmlspecialchars($anamnesis['enfermedades'] ?? '') ?>" placeholder="Ej: diabetes, hipertensión...">
                    </div>
                    <div class="field">
                        <label>Medicamentos actuales</label>
                        <input type="text" name="medicamentos" value="<?= htmlspecialchars($anamnesis['medicamentos'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Antecedentes familiares</label>
                        <input type="text" name="antecedentes_familiares" value="<?= htmlspecialchars($anamnesis['antecedentes_familiares'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Hábitos (fuma, bruxismo, etc.)</label>
                        <input type="text" name="habitos" value="<?= htmlspecialchars($anamnesis['habitos'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Observaciones generales</label>
                        <input type="text" name="observaciones" value="<?= htmlspecialchars($anamnesis['observaciones'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn" style="max-width:220px;">Guardar anamnesis</button>
                </form>
            </div>
        </section>

        <!-- ===== ODONTOGRAMA ===== -->
        <section class="tab-panel" id="tab-odontograma" hidden>
            <div class="panel">
                <div class="odonto-toolbar">
                    <h3 style="margin:0;">Odontograma</h3>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <div class="dentition-switch" id="dentitionSwitch">
                            <button type="button" data-dent="adulto" class="active">Adulto</button>
                            <button type="button" data-dent="nino">Niño</button>
                        </div>
                        <button type="button" class="btn-outline-small" id="btnLimpiar">Limpiar</button>
                    </div>
                </div>

                <p class="odonto-hint">1) Elige una condición de la paleta. &nbsp; 2) Clic en el <strong>diente</strong> (ícono) para condiciones de pieza completa (ausente, corona, implante...), o en una de las <strong>5 celdas</strong> de abajo para marcar una cara específica (caries, obturación, fractura...).</p>

                <div class="odonto-legend" id="odontoLegend">
                    <?php foreach ($condiciones as $key => [$label, $color]): ?>
                        <button type="button" class="cond-btn<?= $key === 'sano' ? ' active' : '' ?>" data-cond="<?= $key ?>" style="--cond-color: <?= $color ?>">
                            <i class="dot"></i><?= htmlspecialchars($label) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <form method="POST" id="formOdontograma">
                    <input type="hidden" name="accion" value="odontograma">
                    <input type="hidden" name="odontograma_json" id="odontogramaJson">

                    <div class="odonto-chart">
                        <div class="odonto-arch upper" id="archUpper"></div>
                        <div class="arch-divider"></div>
                        <div class="odonto-arch lower" id="archLower"></div>
                    </div>

                    <button type="submit" class="btn" style="max-width:220px; margin-top:12px;">Guardar odontograma</button>
                </form>
            </div>
        </section>

        <!-- ===== CONSENTIMIENTO ===== -->
        <section class="tab-panel" id="tab-consentimiento" hidden>
            <div class="panel">
                <h3>Registrar consentimiento informado</h3>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="accion" value="consentimiento">

                    <div class="field">
                        <label>Texto del consentimiento</label>
                        <textarea name="texto_consentimiento" rows="6" style="width:100%; padding:12px 14px; border-radius:9px; border:1.5px solid var(--border-soft); font-size:14px; font-family:var(--font-body); background:#FBFEFD; resize:vertical;"><?= htmlspecialchars($textoConsentimientoDefault) ?></textarea>
                    </div>

                    <div class="two-col">
                        <div class="field">
                            <label>Firmado por (nombre completo)</label>
                            <input type="text" name="firmado_por" placeholder="Ej: <?= htmlspecialchars($paciente['nombre']) ?>" value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
                        </div>
                        <div class="field">
                            <label>Fecha</label>
                            <input type="date" name="fecha_consentimiento" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:var(--text-main); margin-bottom:18px; cursor:pointer;">
                        <input type="checkbox" name="acepto" required style="width:16px; height:16px;">
                        El paciente (o su apoderado) declara haber leído y aceptado el texto anterior.
                    </label>

                    <button type="submit" class="btn" style="max-width:260px;">Guardar consentimiento</button>
                </form>
            </div>

            <div class="panel">
                <h3>Historial de consentimientos firmados</h3>
                <?php if (count($consentimientos) === 0): ?>
                    <div class="empty-state">Este paciente todavía no tiene consentimientos registrados.</div>
                <?php else: ?>
                <table>
                    <thead><tr><th>Fecha</th><th>Firmado por</th><th>Registrado por</th><th>Texto</th></tr></thead>
                    <tbody>
                    <?php foreach ($consentimientos as $c): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                            <td><?= htmlspecialchars($c['firmado_por']) ?></td>
                            <td><?= htmlspecialchars($c['dentista_nombre'] ?: '—') ?></td>
                            <td style="max-width:340px; color:var(--text-muted); font-size:12.5px;"><?= htmlspecialchars(mb_strimwidth($c['texto'], 0, 140, '…')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </section>
    </main>

</div>

<script>
// Pestañas
document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.hidden = true);
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).hidden = false;
    });
});

// Odontograma avanzado (pieza completa + 5 zonas por diente)
(function () {
    var condiciones = <?= json_encode($condiciones) ?>;

    var dientesAdulto = {
        superior: <?= json_encode($dientesSuperior) ?>,
        inferior: <?= json_encode($dientesInferior) ?>
    };
    var dientesNino = {
        superior: <?= json_encode($dientesSuperiorNino) ?>,
        inferior: <?= json_encode($dientesInferiorNino) ?>
    };

    // --- Cargar y migrar datos guardados ---
    // Formato nuevo: { "16": { whole:"corona", top:"caries", left:"", center:"", right:"", bottom:"" }, ... }
    // Formato viejo (compatibilidad): { "16": "caries" }
    var datosRaw = {};
    try { datosRaw = JSON.parse(<?= json_encode($odontoJson ?: '{}') ?>) || {}; } catch (e) { datosRaw = {}; }

    var datos = {};
    Object.keys(datosRaw).forEach(function (num) {
        var v = datosRaw[num];
        if (typeof v === 'string') {
            datos[num] = { whole: (v === 'sano' ? '' : v), top: '', left: '', center: '', right: '', bottom: '' };
        } else if (v && typeof v === 'object') {
            datos[num] = {
                whole: v.whole || '',
                top: v.top || '', left: v.left || '', center: v.center || '',
                right: v.right || '', bottom: v.bottom || ''
            };
        }
    });

    function getDiente(num) {
        if (!datos[num]) {
            datos[num] = { whole: '', top: '', left: '', center: '', right: '', bottom: '' };
        }
        return datos[num];
    }

    var herramienta = 'caries';
    var dentActual = 'adulto';

    var TOOTH_SVG = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c-2.5 0-4.5 1.6-5.4 3.6C5.9 7.4 5.5 9.6 5.8 12c.3 2.6 1 5.3 1.9 7.6.3.9 1.6 1 2 .1.5-1.1.9-2.8 1.3-4 .2-.6.6-1 1-1s.8.4 1 1c.4 1.2.8 2.9 1.3 4 .4.9 1.7.8 2-.1.9-2.3 1.6-5 1.9-7.6.3-2.4-.1-4.6-.8-6.4C16.5 3.6 14.5 2 12 2Z"/></svg>';

    function colorFor(key) {
        return key && condiciones[key] ? condiciones[key][1] : null;
    }

    function crearDiente(num) {
        var d = getDiente(num);
        var wrap = document.createElement('div');
        wrap.className = 'tooth-unit';

        var label = document.createElement('div');
        label.className = 'tooth-num';
        label.textContent = num;
        wrap.appendChild(label);

        // Ícono = condición de "pieza completa"
        var icon = document.createElement('div');
        icon.className = 'tooth-icon';
        icon.innerHTML = TOOTH_SVG;
        var wcolor = colorFor(d.whole);
        icon.style.color = wcolor || 'var(--border-soft)';
        icon.style.background = wcolor ? wcolor + '26' : '#fff';
        icon.style.borderColor = wcolor || '';
        if (d.whole === 'ausente' || d.whole === 'extraer') icon.classList.add('struck');
        icon.title = d.whole ? condiciones[d.whole][0] + ' (pieza completa)' : 'Pieza completa: sin condición';
        icon.addEventListener('click', function () {
            d.whole = (d.whole === herramienta) ? '' : herramienta;
            renderTodo();
        });
        wrap.appendChild(icon);

        // Cruz de 5 zonas
        var cross = document.createElement('div');
        cross.className = 'tooth-cross';
        var zonas = [
            ['corner', null], ['z-top clickable', 'top'], ['corner', null],
            ['z-left clickable', 'left'], ['z-center clickable', 'center'], ['z-right clickable', 'right'],
            ['corner', null], ['z-bottom clickable', 'bottom'], ['corner', null]
        ];
        zonas.forEach(function (z) {
            var cell = document.createElement('div');
            cell.className = 'zone ' + z[0];
            if (z[1]) {
                var zc = colorFor(d[z[1]]);
                cell.style.background = zc || '';
                cell.title = d[z[1]] ? condiciones[d[z[1]]][0] + ' (' + z[1] + ')' : z[1];
                cell.addEventListener('click', function () {
                    d[z[1]] = (d[z[1]] === herramienta) ? '' : herramienta;
                    renderTodo();
                });
            }
            cross.appendChild(cell);
        });
        wrap.appendChild(cross);

        return wrap;
    }

    function renderArco(contenedorId, numeros) {
        var cont = document.getElementById(contenedorId);
        cont.innerHTML = '';
        numeros.forEach(function (n) {
            cont.appendChild(crearDiente(n));
        });
    }

    function renderTodo() {
        var set = dentActual === 'adulto' ? dientesAdulto : dientesNino;
        renderArco('archUpper', set.superior);
        renderArco('archLower', set.inferior);
    }

    // Paleta: elegir condición activa
    document.querySelectorAll('.cond-btn').forEach(function (btn) {
        if (btn.dataset.cond === 'sano') { btn.style.display = 'none'; btn.classList.remove('active'); return; } // "sano" se aplica con clic repetido, no como herramienta
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cond-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            herramienta = btn.dataset.cond;
        });
    });
    // Activar la primera condición real por defecto
    var primerCond = document.querySelector('.cond-btn:not([data-cond="sano"])');
    if (primerCond) primerCond.classList.add('active');

    // Switch Adulto / Niño
    document.querySelectorAll('#dentitionSwitch button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#dentitionSwitch button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            dentActual = btn.dataset.dent;
            renderTodo();
        });
    });

    // Limpiar: borra solo la dentición visible actualmente
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        if (!confirm('¿Limpiar el odontograma ' + (dentActual === 'adulto' ? 'de adulto' : 'de niño') + '? Esta acción no se puede deshacer hasta guardar de nuevo.')) return;
        var set = dentActual === 'adulto' ? dientesAdulto : dientesNino;
        set.superior.concat(set.inferior).forEach(function (n) { delete datos[n]; });
        renderTodo();
    });

    document.getElementById('formOdontograma').addEventListener('submit', function () {
        // Limpia entradas totalmente vacías antes de guardar
        var limpio = {};
        Object.keys(datos).forEach(function (num) {
            var d = datos[num];
            if (d.whole || d.top || d.left || d.center || d.right || d.bottom) limpio[num] = d;
        });
        document.getElementById('odontogramaJson').value = JSON.stringify(limpio);
    });

    renderTodo();
})();
</script>
</body>
</html>