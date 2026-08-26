<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requerir_rol('dentista');

$pacienteId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'paciente'");
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$stmt = $pdo->prepare("SELECT nombre_clinica, direccion, telefono, email FROM clinica_info ORDER BY id ASC LIMIT 1");
$stmt->execute();
$clinica = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("
    SELECT t.*, u.nombre AS dentista_nombre
    FROM tratamientos t
    LEFT JOIN usuarios u ON u.id = t.dentista_id
    WHERE t.paciente_id = ?
    ORDER BY t.fecha ASC, t.id ASC
");
$stmt->execute([$pacienteId]);
$evoluciones = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM anamnesis WHERE paciente_id = ?");
$stmt->execute([$pacienteId]);
$anamnesis = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT datos FROM odontogramas WHERE paciente_id = ?");
$stmt->execute([$pacienteId]);
$odonto = $stmt->fetch();
$odontoData = [];
if ($odonto && $odonto['datos']) {
    $decoded = json_decode($odonto['datos'], true);
    if (is_array($decoded)) $odontoData = $decoded;
}

$stmt = $pdo->prepare("
    SELECT c.*, u.nombre AS dentista_nombre
    FROM consentimientos c
    LEFT JOIN usuarios u ON u.id = c.dentista_id
    WHERE c.paciente_id = ?
    ORDER BY c.creado_en DESC
");
$stmt->execute([$pacienteId]);
$consentimientos = $stmt->fetchAll();

// Catálogo de condiciones (mismo que historia_clinica.php) para traducir el JSON del odontograma a texto legible
$condiciones = [
    'caries'          => 'Caries', 'corona' => 'Corona', 'corona_temp' => 'Corona (Temp.)',
    'ausente'         => 'Ausente', 'fractura' => 'Fractura', 'diastema' => 'Diastema',
    'obturacion'      => 'Obturación', 'protesis_remov' => 'Prótesis Remov.', 'desplazamiento' => 'Desplazamiento',
    'rotacion'        => 'Rotación', 'fusion' => 'Fusión', 'remanente_rad' => 'Remanente Rad.',
    'erupcion'        => 'Erupción', 'transposicion' => 'Transposición', 'supernumerario' => 'Supernumerario',
    'pulpa'           => 'Pulpa', 'protesis' => 'Prótesis', 'perno' => 'Perno',
    'ortod_fija'      => 'Ortodoncia Fija', 'protesis_fija' => 'Prótesis Fija', 'implante' => 'Implante',
    'macrodoncia'     => 'Macrodoncia', 'microdoncia' => 'Microdoncia', 'discromia' => 'Discromía',
    'desgaste'        => 'Desgaste', 'impactado_p' => 'Impactado P.', 'intrusion' => 'Intrusión',
    'edentulismo'     => 'Edentulismo', 'ectopico' => 'Ectópico', 'impactado' => 'Impactado',
    'ortod_remov'     => 'Ortod. Remov.', 'extrusion' => 'Extrusión', 'poste' => 'Poste', 'extraer' => 'Extraer',
];

$zonaLabel = ['whole' => 'Pieza completa', 'top' => 'Superficie superior', 'bottom' => 'Superficie inferior', 'left' => 'Mesial', 'right' => 'Distal', 'center' => 'Oclusal/Central'];

// Construir lista legible de hallazgos del odontograma, ordenada por número de diente
$hallazgos = [];
$numerosOrdenados = array_keys($odontoData);
sort($numerosOrdenados, SORT_NUMERIC);
foreach ($numerosOrdenados as $num) {
    $zonas = $odontoData[$num];
    if (!is_array($zonas)) continue;
    foreach ($zonas as $zona => $cond) {
        if ($cond && isset($condiciones[$cond])) {
            $hallazgos[] = "Pieza {$num} — " . ($zonaLabel[$zona] ?? $zona) . ": " . $condiciones[$cond];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historia clínica — <?= htmlspecialchars($paciente['nombre']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --color-primary: #0F2B3D;
    --accent-teal: #2FA39B;
    --accent-teal-dark: #22766F;
    --text-main: #10242E;
    --text-muted: #5B7480;
    --border-soft: #DCEBE8;
    --font-display: 'Space Grotesk', sans-serif;
    --font-body: 'Inter', sans-serif;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: var(--font-body);
    color: var(--text-main);
    background: #F4FAF9;
    -webkit-font-smoothing: antialiased;
}
.page {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 36px 60px;
    background: #fff;
}
.toolbar {
    max-width: 800px;
    margin: 0 auto;
    padding: 16px 36px 0;
    display: flex;
    justify-content: flex-end;
}
.btn-print {
    font-family: var(--font-body);
    font-weight: 600;
    font-size: 14px;
    background: var(--color-primary);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 9px;
    cursor: pointer;
}
.btn-print:hover { background: var(--accent-teal-dark); }

header.doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid var(--color-primary);
    padding-bottom: 16px;
    margin-bottom: 24px;
}
header.doc-header h1 { font-family: var(--font-display); font-size: 20px; color: var(--color-primary); margin: 0 0 4px; }
header.doc-header p { margin: 0; font-size: 12.5px; color: var(--text-muted); }
.clinic-name { font-family: var(--font-display); font-weight: 700; font-size: 16px; color: var(--accent-teal-dark); text-align:right; }

h2.section-title {
    font-family: var(--font-display);
    font-size: 15px;
    color: var(--color-primary);
    border-left: 4px solid var(--accent-teal);
    padding-left: 10px;
    margin: 28px 0 12px;
}

table { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 6px; }
th { text-align: left; color: var(--text-muted); font-weight: 600; padding: 6px 8px; border-bottom: 1px solid var(--border-soft); font-size: 11px; text-transform: uppercase; }
td { padding: 8px 8px; border-bottom: 1px solid var(--border-soft); vertical-align: top; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; font-size: 13px; margin-bottom: 6px; }
.info-grid div span.k { color: var(--text-muted); font-size: 11px; display: block; }

ul.findings { margin: 0; padding-left: 18px; font-size: 12.5px; }
ul.findings li { margin-bottom: 4px; }

.empty { color: var(--text-muted); font-size: 12.5px; font-style: italic; }

.signature-block { margin-top: 10px; font-size: 12.5px; border: 1px solid var(--border-soft); border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; }
.signature-block .meta { color: var(--text-muted); font-size: 11px; margin-top: 4px; }

footer.doc-footer { margin-top: 40px; padding-top: 14px; border-top: 1px solid var(--border-soft); font-size: 11px; color: var(--text-muted); text-align: center; }

@media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .page { padding: 0; max-width: 100%; }
}
</style>
</head>
<body>

<div class="toolbar">
    <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
</div>

<div class="page">
    <header class="doc-header">
        <div>
            <h1>Historia clínica odontológica</h1>
            <p>Generado el <?= date('d/m/Y H:i') ?></p>
        </div>
        <div class="clinic-name">
            <?= htmlspecialchars($clinica['nombre_clinica'] ?? 'Clínica Dental') ?><br>
            <span style="font-family:var(--font-body); font-weight:400; font-size:11px; color:var(--text-muted);">
                <?= htmlspecialchars($clinica['direccion'] ?? '') ?><?= !empty($clinica['telefono']) ? ' · ' . htmlspecialchars($clinica['telefono']) : '' ?>
            </span>
        </div>
    </header>

    <h2 class="section-title">Datos del paciente</h2>
    <div class="info-grid">
        <div><span class="k">Nombre</span><?= htmlspecialchars(trim($paciente['nombre'] . ' ' . ($paciente['apellido'] ?? ''))) ?></div>
        <div><span class="k">Correo</span><?= htmlspecialchars($paciente['email']) ?></div>
        <div><span class="k">Teléfono</span><?= htmlspecialchars($paciente['telefono'] ?? '—') ?></div>
        <div><span class="k">Documento</span><?= htmlspecialchars(($paciente['tipo_documento'] ?? '') . ' ' . ($paciente['numero_documento'] ?? '—')) ?></div>
        <div><span class="k">Fecha de nacimiento</span><?= !empty($paciente['fecha_nacimiento']) ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) : '—' ?></div>
        <div><span class="k">Dirección</span><?= htmlspecialchars($paciente['direccion'] ?? '—') ?></div>
    </div>

    <h2 class="section-title">Anamnesis</h2>
    <?php if (empty($anamnesis)): ?>
        <p class="empty">No se registró ficha de anamnesis para este paciente.</p>
    <?php else: ?>
        <div class="info-grid">
            <div><span class="k">Alergias</span><?= htmlspecialchars($anamnesis['alergias'] ?: '—') ?></div>
            <div><span class="k">Enfermedades / condiciones</span><?= htmlspecialchars($anamnesis['enfermedades'] ?: '—') ?></div>
            <div><span class="k">Medicamentos actuales</span><?= htmlspecialchars($anamnesis['medicamentos'] ?: '—') ?></div>
            <div><span class="k">Antecedentes familiares</span><?= htmlspecialchars($anamnesis['antecedentes_familiares'] ?: '—') ?></div>
            <div><span class="k">Hábitos</span><?= htmlspecialchars($anamnesis['habitos'] ?: '—') ?></div>
            <div><span class="k">Observaciones</span><?= htmlspecialchars($anamnesis['observaciones'] ?: '—') ?></div>
        </div>
    <?php endif; ?>

    <h2 class="section-title">Odontograma — hallazgos registrados</h2>
    <?php if (empty($hallazgos)): ?>
        <p class="empty">No hay condiciones registradas en el odontograma.</p>
    <?php else: ?>
        <ul class="findings">
            <?php foreach ($hallazgos as $h): ?>
                <li><?= htmlspecialchars($h) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2 class="section-title">Evolución / tratamientos realizados</h2>
    <?php if (count($evoluciones) === 0): ?>
        <p class="empty">Sin registros de evolución.</p>
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

    <h2 class="section-title">Consentimientos informados</h2>
    <?php if (count($consentimientos) === 0): ?>
        <p class="empty">No hay consentimientos firmados registrados.</p>
    <?php else: ?>
        <?php foreach ($consentimientos as $c): ?>
            <div class="signature-block">
                <?= nl2br(htmlspecialchars($c['texto'])) ?>
                <div class="meta">Firmado por <strong><?= htmlspecialchars($c['firmado_por']) ?></strong> el <?= date('d/m/Y', strtotime($c['fecha'])) ?> · Registrado por <?= htmlspecialchars($c['dentista_nombre'] ?: '—') ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <footer class="doc-footer">
        Documento generado automáticamente por el sistema de la clínica. Válido como resumen de historia clínica interna.
    </footer>
</div>

</body>
</html>
