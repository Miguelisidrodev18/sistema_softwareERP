<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'por_evento':
        $eventoId = (int)($_GET['evento_id'] ?? 0);

        $whereEvento = $eventoId ? "AND a.evento_id = $eventoId" : '';
        $rows = DB::fetchAll(
            "SELECT e.codigo, CONCAT(e.apellidos,', ',e.nombres) as apellidos_nombres,
                    c.nombre as ciclo_nombre, ev.nombre as evento_nombre,
                    DATE_FORMAT(a.fecha_registro,'%d/%m/%Y %H:%i') as fecha_registro,
                    a.estado, a.puntos
             FROM asistencias a
             JOIN estudiantes e ON e.id = a.estudiante_id
             JOIN ciclos c ON c.id = e.ciclo_id
             JOIN eventos ev ON ev.id = a.evento_id
             WHERE 1=1 $whereEvento
             ORDER BY ev.fecha DESC, c.orden, e.apellidos"
        );

        $eventoNombre = $eventoId
            ? (DB::fetchColumn("SELECT nombre FROM eventos WHERE id=?", [$eventoId]) ?: 'Evento')
            : 'Todos los eventos';

        jsonResponse(true, '', ['rows' => $rows, 'evento_nombre' => $eventoNombre]);
        break;

    case 'por_ciclo':
        $cicloId     = (int)($_GET['ciclo_id'] ?? 0);
        $whereCiclo  = $cicloId ? "AND e.ciclo_id = $cicloId" : '';
        $rows = DB::fetchAll(
            "SELECT e.codigo, CONCAT(e.apellidos,', ',e.nombres) as apellidos_nombres,
                    c.nombre as ciclo_nombre,
                    COUNT(CASE WHEN a.estado='asistio'  THEN 1 END) as asistencias,
                    COUNT(CASE WHEN a.estado='tardanza' THEN 1 END) as tardanzas,
                    COUNT(CASE WHEN a.estado='falta'    THEN 1 END) as faltas,
                    COALESCE(SUM(a.puntos),0) as total_puntos
             FROM estudiantes e
             JOIN ciclos c ON c.id = e.ciclo_id
             LEFT JOIN asistencias a ON a.estudiante_id = e.id
             WHERE e.activo=1 $whereCiclo
             GROUP BY e.id, e.codigo, e.apellidos, e.nombres, c.nombre
             ORDER BY total_puntos DESC, e.apellidos"
        );
        $cicloNombre = $cicloId
            ? (DB::fetchColumn("SELECT nombre FROM ciclos WHERE id=?", [$cicloId]) ?: 'Ciclo')
            : 'Todos los ciclos';

        jsonResponse(true, '', ['rows' => $rows, 'ciclo_nombre' => $cicloNombre]);
        break;

    case 'stats_generales':
        $total     = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias");
        $asistio   = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='asistio'");
        $tardanza  = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='tardanza'");
        $falta     = (int)DB::fetchColumn("SELECT COUNT(*) FROM asistencias WHERE estado='falta'");

        jsonResponse(true, '', [
            'total_registros' => $total,
            'pct_asistio'   => $total ? round($asistio  / $total * 100) : 0,
            'pct_tardanza'  => $total ? round($tardanza  / $total * 100) : 0,
            'pct_falta'     => $total ? round($falta     / $total * 100) : 0,
        ]);
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
