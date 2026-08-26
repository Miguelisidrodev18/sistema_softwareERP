<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireRolApi('docente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método no permitido.'], 405);
}

// ── Validar archivo subido ───────────────────────────────────────────────────
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $codigos = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma parcial.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
    ];
    $err = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonResponse(['error' => $codigos[$err] ?? 'Error desconocido al subir el archivo.'], 400);
}

$file = $_FILES['archivo'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'csv'], true)) {
    jsonResponse(['error' => 'Formato no válido. Solo se aceptan archivos .xlsx o .csv'], 400);
}

if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['error' => 'El archivo no debe superar 5 MB.'], 400);
}

// ── Parsear según extensión ──────────────────────────────────────────────────
$filas = $ext === 'xlsx' ? parsearXLSX($file['tmp_name']) : parsearCSV($file['tmp_name']);

if (empty($filas)) {
    jsonResponse(['error' => 'No se pudieron leer datos del archivo. Verifique el formato.'], 422);
}

// ── Procesar filas e insertar ────────────────────────────────────────────────
$pdo        = getDB();
$insertados  = 0;
$actualizados = 0;
$sin_cambio  = 0;
$errores     = [];
$preview     = [];

$stmt = $pdo->prepare(
    "INSERT INTO estudiantes (codigo_matricula, nombre, apellidos)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), apellidos=VALUES(apellidos)"
);

foreach ($filas as $idx => $fila) {
    $cod = trim($fila[0] ?? '');

    // Saltar filas vacías o de encabezado
    if ($cod === '' || !ctype_digit(preg_replace('/\s/', '', $cod))) {
        continue;
    }

    $nombreCompleto = trim($fila[1] ?? '');
    if ($nombreCompleto === '') {
        $errores[] = "Fila " . ($idx + 1) . " (código $cod): nombre y apellidos vacíos.";
        continue;
    }

    // Formato esperado: "APELLIDOS, NOMBRE"  →  separar por primera coma
    if (strpos($nombreCompleto, ',') !== false) {
        [$ape, $nom] = explode(',', $nombreCompleto, 2);
        $apellidos   = trim($ape);
        $nombre      = trim($nom);
    } else {
        // Sin coma: primera palabra como nombre, el resto como apellidos
        $partes    = preg_split('/\s+/', $nombreCompleto, 2);
        $nombre    = $partes[0]  ?? '';
        $apellidos = $partes[1] ?? '';
    }

    if ($nombre === '' || $apellidos === '') {
        $errores[] = "Fila " . ($idx + 1) . " ($cod): no se pudo separar nombre y apellidos de '$nombreCompleto'.";
        continue;
    }

    // Capitalizar correctamente (JUAN → Juan)
    $nombre    = mb_convert_case(mb_strtolower($nombre,   'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $apellidos = mb_convert_case(mb_strtolower($apellidos,'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    try {
        $stmt->execute([$cod, $nombre, $apellidos]);
        $affected = $stmt->rowCount();
        // MySQL: 1 = INSERT nuevo | 2 = UPDATE | 0 = sin cambio
        if ($affected === 1) {
            $insertados++;
            $accion = 'nuevo';
        } elseif ($affected === 2) {
            $actualizados++;
            $accion = 'actualizado';
        } else {
            $sin_cambio++;
            $accion = 'sin_cambio';
        }

        $preview[] = [
            'codigo'    => $cod,
            'nombre'    => $nombre,
            'apellidos' => $apellidos,
            'accion'    => $accion,
        ];
    } catch (PDOException $e) {
        error_log('importar_estudiantes fila ' . ($idx + 1) . ': ' . $e->getMessage());
        $errores[] = "Fila " . ($idx + 1) . " ($cod): error al guardar en la base de datos.";
    }
}

jsonResponse([
    'data' => [
        'insertados'   => $insertados,
        'actualizados' => $actualizados,
        'sin_cambio'   => $sin_cambio,
        'errores'      => $errores,
        'preview'      => $preview,
    ]
]);

// ════════════════════════════════════════════════════════════════════════════
// PARSER XLSX — puro PHP, sin librerías externas (XLSX = ZIP + XML)
// ════════════════════════════════════════════════════════════════════════════
function parsearXLSX(string $filepath): array
{
    if (!class_exists('ZipArchive')) {
        error_log('importar_xlsx: ZipArchive no disponible.');
        return [];
    }

    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return [];
    }

    // ── Strings compartidos ──────────────────────────────────────────────────
    $sharedStrings = [];
    $sstRaw        = $zip->getFromName('xl/sharedStrings.xml');
    if ($sstRaw !== false) {
        $sst = @simplexml_load_string($sstRaw);
        if ($sst) {
            foreach ($sst->si as $si) {
                // Caso 1: <si><t>texto</t></si>
                // Caso 2: <si><r><t>texto</t></r><r>…</r></si>  (rich text)
                if (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = (string)$si->t;
                }
            }
        }
    }

    // ── Primera hoja ─────────────────────────────────────────────────────────
    // Buscar el nombre real del archivo de la hoja 1 en workbook.xml.rels
    $sheetFile = 'xl/worksheets/sheet1.xml';
    $relsRaw   = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($relsRaw !== false) {
        $rels = @simplexml_load_string($relsRaw);
        if ($rels) {
            foreach ($rels->Relationship as $rel) {
                if (str_contains((string)$rel['Type'], 'worksheet')) {
                    $sheetFile = 'xl/' . ltrim((string)$rel['Target'], '/');
                    break; // primera hoja
                }
            }
        }
    }

    $sheetRaw = $zip->getFromName($sheetFile);
    $zip->close();

    if ($sheetRaw === false) {
        return [];
    }

    $sheet = @simplexml_load_string($sheetRaw);
    if (!$sheet) {
        return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $rowArr  = [];
        $lastIdx = -1;

        foreach ($row->c as $cell) {
            $ref    = (string)$cell['r'];
            $colStr = preg_replace('/[0-9]/', '', $ref);
            $colIdx = colLetraAIndice($colStr);

            // Rellenar columnas vacías intermedias
            while ($lastIdx < $colIdx - 1) {
                $rowArr[] = '';
                $lastIdx++;
            }

            $type = (string)$cell['t'];
            $val  = (string)($cell->v ?? '');

            if ($type === 's') {
                $val = $sharedStrings[(int)$val] ?? '';
            } elseif ($type === 'inlineStr') {
                $val = (string)($cell->is->t ?? '');
            } elseif ($type === 'b') {
                $val = $val === '1' ? 'TRUE' : 'FALSE';
            }

            $rowArr[] = trim($val);
            $lastIdx  = $colIdx;
        }

        $rows[] = $rowArr;
    }

    return $rows;
}

function colLetraAIndice(string $col): int
{
    $col    = strtoupper(trim($col));
    $result = 0;
    for ($i = 0, $len = strlen($col); $i < $len; $i++) {
        $result = $result * 26 + (ord($col[$i]) - 64);
    }
    return $result - 1; // 0-indexed
}

// ════════════════════════════════════════════════════════════════════════════
// PARSER CSV
// ════════════════════════════════════════════════════════════════════════════
function parsearCSV(string $filepath): array
{
    $rows = [];
    $fh   = @fopen($filepath, 'r');
    if ($fh === false) {
        return [];
    }

    // Detectar y saltar BOM UTF-8
    $bom = fread($fh, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        fseek($fh, 0);
    }

    // Detectar delimitador: coma o punto y coma
    $firstLine = fgets($fh);
    fseek($fh, 0);
    if ($bom === "\xEF\xBB\xBF") fseek($fh, 3);
    $delim = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

    while (($line = fgetcsv($fh, 2000, $delim)) !== false) {
        $rows[] = array_map('trim', $line);
    }

    fclose($fh);
    return $rows;
}
