<?php
// ============================================================
// LECTOR DE ARCHIVOS XLSX SIN DEPENDENCIAS EXTERNAS
// Usa la extension ZipArchive (disponible en la mayoria de hostings)
// ============================================================

class XlsxReader {
    private string $filePath;

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    // Lee la primera hoja del archivo y retorna un array de filas
    public function readRows(int $skipRows = 1): array {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('La extension ZipArchive de PHP no esta disponible.');
        }
        if (!file_exists($this->filePath)) {
            throw new RuntimeException('Archivo no encontrado: ' . $this->filePath);
        }

        $zip = new ZipArchive;
        if ($zip->open($this->filePath) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        // Leer strings compartidos
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $xml = simplexml_load_string($ssXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            if (isset($r->t)) $text .= (string)$r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Leer la primera hoja
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('No se encontro la hoja de calculo.');
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml) {
            throw new RuntimeException('Error al parsear el archivo XLSX.');
        }

        $rows = [];
        $rowIndex = 0;
        foreach ($xml->sheetData->row as $row) {
            $rowIndex++;
            if ($rowIndex <= $skipRows) continue;

            $rowData = [];
            foreach ($row->c as $cell) {
                $colIndex = $this->columnLetterToIndex((string)$cell['r']);
                $type     = (string)$cell['t'];
                $value    = '';

                if (isset($cell->v)) {
                    $rawVal = (string)$cell->v;
                    if ($type === 's') {
                        $value = $sharedStrings[(int)$rawVal] ?? '';
                    } elseif ($type === 'b') {
                        $value = $rawVal === '1' ? 'TRUE' : 'FALSE';
                    } else {
                        $value = $rawVal;
                    }
                }

                // Asegurar que el array tenga el indice correcto
                while (count($rowData) < $colIndex) $rowData[] = '';
                $rowData[$colIndex] = trim($value);
            }

            // Ignorar filas vacias
            $nonEmpty = array_filter($rowData, fn($v) => $v !== '');
            if (!empty($nonEmpty)) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    // Convierte letra(s) de columna Excel a indice numerico (0-based)
    private function columnLetterToIndex(string $cellRef): int {
        preg_match('/^([A-Z]+)/', strtoupper($cellRef), $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    // Genera una plantilla XLSX simple (en formato XML/OOXML)
    public static function generateTemplate(string $outputPath): void {
        // Usamos la libreria de escritura minima basada en ZIP + XML
        $zip = new ZipArchive;
        $zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Estudiantes" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // Shared strings: headers + example rows
        // Columnas: Codigo | Apellidos y Nombres | Ciclo | Seccion
        // Formato: "Perez Gomez, Juan Carlos" (apellidos COMA nombres)
        $strings = [
            'Codigo',                      // 0
            'Apellidos y Nombres',         // 1
            'Ciclo',                       // 2
            'Seccion',                     // 3
            '20230001',                    // 4
            'Perez Gomez, Juan Carlos',    // 5
            'V Ciclo',                     // 6
            'A',                           // 7
            '20230002',                    // 8
            'Quispe Ramos, Ana Maria',     // 9
            'III Ciclo',                   // 10
            'B',                           // 11
        ];
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        foreach ($strings as $s) {
            $ssXml .= '<si><t>' . htmlspecialchars($s, ENT_XML1) . '</t></si>';
        }
        $ssXml .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);

        // Styles
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
  <borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>');

        // Worksheet: 4 columnas — Codigo | Apellidos y Nombres | Ciclo | Seccion
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row>
    <row r="2"><c r="A2" t="s"><v>4</v></c><c r="B2" t="s"><v>5</v></c><c r="C2" t="s"><v>6</v></c><c r="D2" t="s"><v>7</v></c></row>
    <row r="3"><c r="A3" t="s"><v>8</v></c><c r="B3" t="s"><v>9</v></c><c r="C3" t="s"><v>10</v></c><c r="D3" t="s"><v>11</v></c></row>
  </sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
    }
}
