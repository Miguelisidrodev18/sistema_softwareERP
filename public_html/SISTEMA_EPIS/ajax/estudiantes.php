<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = DB::fetchAll(
            "SELECT e.*, c.nombre as ciclo_nombre,
                    CONCAT(e.apellidos, ', ', e.nombres) as apellidos_nombres,
                    COALESCE((SELECT SUM(a.puntos) FROM asistencias a WHERE a.estudiante_id=e.id),0) as total_puntos
             FROM estudiantes e
             JOIN ciclos c ON c.id = e.ciclo_id
             ORDER BY e.apellidos, e.nombres"
        );
        jsonResponse(true, '', $rows);
        break;

    case 'save':
        csrfCheck();
        $id        = (int)($_POST['id'] ?? 0);
        $codigo    = strtoupper(trim($_POST['codigo'] ?? ''));
        $apellidos = trim($_POST['apellidos'] ?? '');
        $nombres   = trim($_POST['nombres'] ?? '');
        $cicloId   = (int)($_POST['ciclo_id'] ?? 0);
        $seccion   = strtoupper(trim($_POST['seccion'] ?? 'A'));
        $activo    = (int)($_POST['activo'] ?? 1);

        if (!$codigo)  jsonResponse(false, 'El codigo es requerido.');
        if (!$apellidos) jsonResponse(false, 'Los apellidos son requeridos.');
        if (!$nombres)   jsonResponse(false, 'Los nombres son requeridos.');
        if (!$cicloId)   jsonResponse(false, 'El ciclo es requerido.');

        // Verificar duplicado
        $whereId = $id ? "AND id != $id" : '';
        $existe  = DB::fetchColumn("SELECT id FROM estudiantes WHERE codigo = ? $whereId", [$codigo]);
        if ($existe) jsonResponse(false, "El codigo '$codigo' ya esta registrado.");

        $data = compact('codigo','apellidos','nombres','cicloId','seccion','activo');
        $data['ciclo_id'] = $data['cicloId'];
        unset($data['cicloId']);

        try {
            if ($id) {
                DB::update('estudiantes', $data, 'id = ?', [$id]);
                audit('estudiante_editado', 'estudiantes', $id, "Estudiante $codigo actualizado");
                jsonResponse(true, 'Estudiante actualizado correctamente.');
            } else {
                $newId = DB::insert('estudiantes', $data);
                audit('estudiante_creado', 'estudiantes', $newId, "Estudiante $codigo creado");
                jsonResponse(true, 'Estudiante registrado correctamente.');
            }
        } catch (PDOException $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage());
        }
        break;

    case 'delete':
        csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID invalido.');
        DB::delete('estudiantes', 'id = ?', [$id]);
        audit('estudiante_eliminado', 'estudiantes', $id);
        jsonResponse(true, 'Estudiante eliminado.');
        break;

    case 'import':
        csrfCheck();
        require_once dirname(__DIR__) . '/xlsx_reader.php';

        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Error al subir el archivo.');
        }

        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx'])) {
            jsonResponse(false, 'Solo se aceptan archivos .xlsx');
        }

        $tmpPath = $_FILES['archivo']['tmp_name'];

        try {
            $reader = new XlsxReader($tmpPath);
            $rows   = $reader->readRows(1); // Omitir cabecera
        } catch (Exception $e) {
            jsonResponse(false, 'Error al leer el archivo: ' . $e->getMessage());
        }

        if (empty($rows)) jsonResponse(false, 'El archivo no contiene datos.');

        // Mapear ciclos por nombre
        $ciclosMap = [];
        $ciclosDB  = DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo=1");
        foreach ($ciclosDB as $c) {
            $ciclosMap[mb_strtolower(trim($c['nombre']))] = $c['id'];
        }

        $insertados  = 0;
        $actualizados = 0;
        $errores     = 0;
        $mensajesError = [];

        foreach ($rows as $lineNum => $row) {
            $rowNum          = $lineNum + 2; // +2 porque saltamos cabecera (fila 1)
            $codigo          = strtoupper(trim($row[0] ?? ''));
            $apellidosNombres = trim($row[1] ?? '');
            $cicloNom        = trim($row[2] ?? '');
            $seccion         = strtoupper(trim($row[3] ?? 'A')) ?: 'A';

            // Separar apellidos y nombres por la primera coma
            // Formato esperado: "Perez Gomez, Juan Carlos"
            if (strpos($apellidosNombres, ',') !== false) {
                [$apellidos, $nombres] = array_map('trim', explode(',', $apellidosNombres, 2));
            } else {
                $errores++;
                $mensajesError[] = "Fila $rowNum: 'Apellidos y Nombres' debe separarse con coma (Ej: Perez Gomez, Juan)";
                continue;
            }

            if (!$codigo || !$apellidos || !$nombres || !$cicloNom) {
                $errores++;
                $mensajesError[] = "Fila $rowNum: Datos incompletos (codigo/apellidos y nombres/ciclo son requeridos)";
                continue;
            }

            $cicloId = $ciclosMap[mb_strtolower($cicloNom)] ?? null;
            if (!$cicloId) {
                $errores++;
                $mensajesError[] = "Fila $rowNum: Ciclo '$cicloNom' no encontrado";
                continue;
            }

            $existe = DB::fetchOne("SELECT id FROM estudiantes WHERE codigo = ?", [$codigo]);
            try {
                if ($existe) {
                    DB::update('estudiantes',
                        ['apellidos'=>$apellidos,'nombres'=>$nombres,'ciclo_id'=>$cicloId,'seccion'=>$seccion],
                        'codigo = ?', [$codigo]
                    );
                    $actualizados++;
                } else {
                    DB::insert('estudiantes', [
                        'codigo'    => $codigo,
                        'apellidos' => $apellidos,
                        'nombres'   => $nombres,
                        'ciclo_id'  => $cicloId,
                        'seccion'   => $seccion,
                        'activo'    => 1,
                    ]);
                    $insertados++;
                }
            } catch (PDOException $e) {
                $errores++;
                $mensajesError[] = "Fila $rowNum: " . $e->getMessage();
            }
        }

        audit('estudiantes_importados', 'estudiantes', 0,
              "Importacion: $insertados nuevos, $actualizados actualizados, $errores errores");
        jsonResponse(true, 'Importacion completada.', [
            'insertados'   => $insertados,
            'actualizados' => $actualizados,
            'errores'      => $errores,
            'mensajes_error' => array_slice($mensajesError, 0, 10),
        ]);
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
