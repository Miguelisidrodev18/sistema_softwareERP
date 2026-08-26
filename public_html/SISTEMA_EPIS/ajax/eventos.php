<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = DB::fetchAll(
            "SELECT e.*, u.nombres as creador_nombre
             FROM eventos e
             JOIN usuarios u ON u.id = e.creado_por
             ORDER BY e.fecha DESC, e.hora_inicio DESC"
        );
        jsonResponse(true, '', $rows);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $ev = DB::fetchOne("SELECT * FROM eventos WHERE id=?", [$id]);
        if (!$ev) jsonResponse(false, 'Evento no encontrado.');
        jsonResponse(true, '', $ev);
        break;

    case 'save':
        csrfCheck();
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
        $fecha       = trim($_POST['fecha'] ?? '');
        $horaInicio  = trim($_POST['hora_inicio'] ?? '');
        $horaCierre  = trim($_POST['hora_cierre'] ?? '');
        $minToleranc = max(0, (int)($_POST['minutos_tolerancia'] ?? 15));
        $minTardanza = max(0, (int)($_POST['minutos_tardanza'] ?? 30));
        $pAsistio    = max(0, (int)($_POST['puntaje_asistio'] ?? 3));
        $pTardanza   = max(0, (int)($_POST['puntaje_tardanza'] ?? 1));
        $pFalta      = max(0, (int)($_POST['puntaje_falta'] ?? 0));
        $edicionId   = (int)($_POST['edicion_id'] ?? 0) ?: null;
        $estado      = in_array($_POST['estado']??'',['programado','activo','finalizado','cancelado'])
                        ? $_POST['estado'] : 'programado';

        if (!$nombre) jsonResponse(false, 'El nombre es requerido.');
        if (!$fecha)  jsonResponse(false, 'La fecha es requerida.');
        if (!$horaInicio) jsonResponse(false, 'La hora de inicio es requerida.');
        if (!$horaCierre) jsonResponse(false, 'La hora de cierre es requerida.');

        $data = [
            'nombre'             => $nombre,
            'descripcion'        => $descripcion,
            'fecha'              => $fecha,
            'hora_inicio'        => $horaInicio,
            'hora_cierre'        => $horaCierre,
            'minutos_tolerancia' => $minToleranc,
            'minutos_tardanza'   => $minTardanza,
            'puntaje_asistio'    => $pAsistio,
            'puntaje_tardanza'   => $pTardanza,
            'puntaje_falta'      => $pFalta,
            'edicion_id'         => $edicionId,
            'estado'             => $estado,
        ];

        try {
            if ($id) {
                DB::update('eventos', $data, 'id = ?', [$id]);
                audit('evento_editado', 'eventos', $id, "Evento '$nombre' actualizado");
                jsonResponse(true, 'Evento actualizado correctamente.');
            } else {
                $data['creado_por'] = Auth::id();
                $newId = DB::insert('eventos', $data);
                audit('evento_creado', 'eventos', $newId, "Evento '$nombre' creado");
                jsonResponse(true, 'Evento creado correctamente.');
            }
        } catch (PDOException $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage());
        }
        break;

    case 'delete':
        csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID invalido.');
        $count = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE evento_id=?", [$id]);
        if ($count > 0) jsonResponse(false, "No se puede eliminar: tiene $count asistencia(s) registrada(s).");
        DB::delete('eventos', 'id = ?', [$id]);
        audit('evento_eliminado', 'eventos', $id);
        jsonResponse(true, 'Evento eliminado.');
        break;

    case 'get_asignaciones':
        $eventoId = (int)($_GET['evento_id'] ?? 0);
        if (!$eventoId) jsonResponse(false, 'ID de evento invalido.');
        $rows = DB::fetchAll(
            "SELECT c.id as ciclo_id, c.nombre as ciclo_nombre,
                    ecd.delegado_id,
                    CONCAT(u.apellidos, ', ', u.nombres) as delegado_nombre
             FROM ciclos c
             LEFT JOIN evento_ciclo_delegado ecd ON ecd.ciclo_id = c.id AND ecd.evento_id = ?
             LEFT JOIN usuarios u ON u.id = ecd.delegado_id
             WHERE c.activo = 1
             ORDER BY c.orden",
            [$eventoId]
        );
        jsonResponse(true, '', $rows);
        break;

    case 'save_asignaciones':
        csrfCheck();
        $eventoId     = (int)($_POST['evento_id'] ?? 0);
        $asignaciones = json_decode($_POST['asignaciones'] ?? '[]', true);
        if (!$eventoId) jsonResponse(false, 'ID de evento invalido.');
        if (!DB::fetchOne("SELECT id FROM eventos WHERE id=?", [$eventoId]))
            jsonResponse(false, 'Evento no encontrado.');
        DB::query("DELETE FROM evento_ciclo_delegado WHERE evento_id = ?", [$eventoId]);
        $count = 0;
        foreach ((array)$asignaciones as $asig) {
            $cicloId    = (int)($asig['ciclo_id'] ?? 0);
            $delegadoId = (int)($asig['delegado_id'] ?? 0);
            if ($cicloId && $delegadoId) {
                DB::insert('evento_ciclo_delegado', [
                    'evento_id'   => $eventoId,
                    'ciclo_id'    => $cicloId,
                    'delegado_id' => $delegadoId,
                ]);
                $count++;
            }
        }
        audit('asignaciones_ciclos', 'evento_ciclo_delegado', $eventoId,
              "$count ciclos asignados para evento $eventoId");
        jsonResponse(true, "$count ciclo(s) asignado(s) correctamente.");
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
