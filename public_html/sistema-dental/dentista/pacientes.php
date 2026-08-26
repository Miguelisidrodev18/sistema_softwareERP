<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requerir_rol('dentista');

$usuario = usuario_actual();
$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE rol = 'paciente' AND (nombre LIKE ? OR email LIKE ?) ORDER BY nombre ASC");
    $like = '%' . $busqueda . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM usuarios WHERE rol = 'paciente' ORDER BY nombre ASC");
}
$pacientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pacientes — Clínica Dental</title>
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
                <h1>Pacientes</h1>
                <p>Busca un paciente para ver o editar su historia clínica.</p>
            </div>
            <a href="agregar_paciente.php" class="btn" style="max-width:200px; display:inline-flex;">+ Agregar paciente</a>
        </header>

        <div class="panel">
            <form method="GET" action="pacientes.php" class="inline-form">
                <div class="field" style="margin-bottom:0;">
                    <input type="text" name="q" placeholder="Buscar por nombre o correo..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
            </form>
        </div>

        <div class="panel">
            <?php if (count($pacientes) === 0): ?>
                <div class="empty-state">No se encontraron pacientes.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= htmlspecialchars($p['telefono'] ?: '—') ?></td>
                        <td><a href="historia_clinica.php?id=<?= $p['id'] ?>" class="btn" style="display:inline-flex; padding:8px 16px; font-size:13px;">Ver historia clínica</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

</div>
</body>
</html>
