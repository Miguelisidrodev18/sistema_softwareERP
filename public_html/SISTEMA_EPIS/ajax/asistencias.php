<?php
require_once __DIR__ . '/_init.php';
Auth::required();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_estudiantes':
        $eventoId = (int)($_GET['evento_id'] ?? 0);
        if (!$eventoId) jsonResponse(false, 'ID de evento invalido.');

        $evento = DB::fetchOne("SELECT * FROM eventos WHERE id=?", [$eventoId]);
        if (!$evento) jsonResponse(false, 'Evento no encontrado.');

        // Calcular el estado automatico segun la hora actual
        $autoEstado = calcularEstadoAsistencia($evento);

        // Filtrar por ciclos asignados a este delegado en este evento especifico
        $whereExtra = '';
        $params     = [];

        if (!Auth::isAdmin()) {
            $asignados = DB::fetchAll(
                "SELECT ciclo_id FROM evento_ciclo_delegado WHERE evento_id=? AND delegado_id=?",
                [$eventoId, Auth::id()]
            );
            $ciclosIds = array_map('intval', array_column($asignados, 'ciclo_id'));
            if (empty($ciclosIds)) {
                jsonResponse(true, '', ['rows' => [], 'auto_estado' => $autoEstado]);
            }
            $placeholders = implode(',', array_fill(0, count($ciclosIds), '?'));
            $whereExtra   = "AND e.ciclo_id IN ($placeholders)";
            $params       = $ciclosIds;
        }

        $sql = "SELECT e.id, e.codigo, e.apellidos, e.nombres, e.ciclo_id, e.seccion,
                       c.nombre as ciclo_nombre,
                       a.estado, a.puntos, a.hora_llegada,
                       u.nombres as registrado_por
                FROM estudiantes e
                JOIN ciclos c ON c.id = e.ciclo_id
                LEFT JOIN asistencias a ON a.estudiante_id = e.id AND a.evento_id = ?
                LEFT JOIN usuarios u ON u.id = a.registrado_por
                WHERE e.activo = 1 $whereExtra
                ORDER BY c.orden, e.apellidos, e.nombres";

        $rows = DB::fetchAll($sql, array_merge([$eventoId], $params));
        jsonResponse(true, '', ['rows' => $rows, 'auto_estado' => $autoEstado]);
        break;

    case 'save_bulk':
        csrfCheck();
        Auth::required();

        $eventoId  = (int)($_POST['evento_id'] ?? 0);
        $registros = json_decode($_POST['registros'] ?? '[]', true);

        if (!$eventoId) jsonResponse(false, 'ID de evento invalido.');
        if (empty($registros)) jsonResponse(false, 'Sin registros para guardar.');

        $evento = DB::fetchOne("SELECT * FROM eventos WHERE id=?", [$eventoId]);
        if (!$evento) jsonResponse(false, 'Evento no encontrado.');

        $guardados  = 0;
        $delegadoId = Auth::id();

        // Pre-cargar ciclos permitidos para este delegado en este evento
        $ciclosPermitidos = [];
        if (!Auth::isAdmin()) {
            $asignados = DB::fetchAll(
                "SELECT ciclo_id FROM evento_ciclo_delegado WHERE evento_id=? AND delegado_id=?",
                [$eventoId, $delegadoId]
            );
            $ciclosPermitidos = array_map('intval', array_column($asignados, 'ciclo_id'));
            if (empty($ciclosPermitidos)) {
                jsonResponse(false, 'No tienes ciclos asignados para este evento.');
            }
        }

        DB::conn()->beginTransaction();
        try {
            foreach ($registros as $reg) {
                $estudianteId = (int)($reg['id'] ?? 0);
                $estado       = $reg['estado'] ?? '';

                if (!$estudianteId || !in_array($estado, ['asistio','tardanza','falta'])) continue;

                // Verificar que el delegado puede evaluar al estudiante en este evento
                if (!Auth::isAdmin()) {
                    $est = DB::fetchOne("SELECT ciclo_id FROM estudiantes WHERE id=?", [$estudianteId]);
                    if (!$est || !in_array((int)$est['ciclo_id'], $ciclosPermitidos)) continue;
                }

                $puntos = calcularPuntos($estado, $evento);
                $hora   = date('H:i:s');

                // Upsert: insertar o actualizar
                DB::query(
                    "INSERT INTO asistencias
                        (estudiante_id, evento_id, estado, puntos, hora_llegada, registrado_por, fecha_registro)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE
                        estado=VALUES(estado), puntos=VALUES(puntos),
                        hora_llegada=VALUES(hora_llegada), registrado_por=VALUES(registrado_por),
                        updated_at=NOW()",
                    [$estudianteId, $eventoId, $estado, $puntos, $hora, $delegadoId]
                );
                $guardados++;
            }
            DB::conn()->commit();
            audit('asistencias_guardadas', 'asistencias', $eventoId,
                  "Evento $eventoId: $guardados asistencias guardadas");
            jsonResponse(true, "Asistencias guardadas correctamente.", ['guardados' => $guardados]);
        } catch (PDOException $e) {
            DB::conn()->rollBack();
            jsonResponse(false, 'Error al guardar: ' . $e->getMessage());
        }
        break;

    case 'save_one':
        csrfCheck();
        $eventoId     = (int)($_POST['evento_id'] ?? 0);
        $estudianteId = (int)($_POST['estudiante_id'] ?? 0);
        $estado       = $_POST['estado'] ?? '';

        if (!$eventoId || !$estudianteId) jsonResponse(false, 'Parametros invalidos.');
        if (!in_array($estado, ['asistio','tardanza','falta'])) jsonResponse(false, 'Estado invalido.');

        $evento = DB::fetchOne("SELECT * FROM eventos WHERE id=?", [$eventoId]);
        if (!$evento) jsonResponse(false, 'Evento no encontrado.');

        if (!Auth::isAdmin()) {
            $est = DB::fetchOne("SELECT ciclo_id FROM estudiantes WHERE id=?", [$estudianteId]);
            if (!$est || !Auth::canEvaluateCycle((int)$est['ciclo_id'])) {
                jsonResponse(false, 'No tienes permiso para evaluar a este estudiante.');
            }
        }

        $puntos = calcularPuntos($estado, $evento);
        DB::query(
            "INSERT INTO asistencias (estudiante_id, evento_id, estado, puntos, hora_llegada, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE estado=VALUES(estado), puntos=VALUES(puntos),
             hora_llegada=VALUES(hora_llegada), registrado_por=VALUES(registrado_por), updated_at=NOW()",
            [$estudianteId, $eventoId, $estado, $puntos, date('H:i:s'), Auth::id()]
        );
        jsonResponse(true, 'Asistencia registrada.', ['puntos' => $puntos]);
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
