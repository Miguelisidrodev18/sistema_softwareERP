<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
require_once dirname(__DIR__) . '/xlsx_reader.php';

$tipo = $_GET['tipo'] ?? '';

switch ($tipo) {
    case 'plantilla_estudiantes':
        // Generar plantilla Excel para importacion
        $tmpBase  = tempnam(sys_get_temp_dir(), 'epis_plantilla_');
        $xlsxFile = $tmpBase . '.xlsx';
        XlsxReader::generateTemplate($xlsxFile);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Plantilla_Estudiantes.xlsx"');
        header('Content-Length: ' . filesize($xlsxFile));
        header('Cache-Control: no-cache');
        readfile($xlsxFile);
        unlink($xlsxFile);
        unlink($tmpBase);
        exit;

    case 'asistencias':
        $eventoId = (int)($_GET['evento_id'] ?? 0);
        $whereEv  = $eventoId ? "AND a.evento_id=$eventoId" : '';
        $rows = DB::fetchAll(
            "SELECT e.codigo, e.apellidos, e.nombres, c.nombre as ciclo, e.seccion,
                    ev.nombre as evento, ev.fecha,
                    a.estado, a.puntos,
                    DATE_FORMAT(a.fecha_registro,'%d/%m/%Y %H:%i') as registrado,
                    u.nombres as registrado_por
             FROM asistencias a
             JOIN estudiantes e ON e.id=a.estudiante_id
             JOIN ciclos c ON c.id=e.ciclo_id
             JOIN eventos ev ON ev.id=a.evento_id
             LEFT JOIN usuarios u ON u.id=a.registrado_por
             WHERE 1=1 $whereEv
             ORDER BY ev.fecha DESC, e.apellidos"
        );
        $headers = ['Codigo','Apellidos','Nombres','Ciclo','Seccion','Evento','Fecha Evento','Estado','Puntos','Fecha Registro','Registrado por'];
        exportCSV($headers, array_map(fn($r) => [
            $r['codigo'], $r['apellidos'], $r['nombres'], $r['ciclo'], $r['seccion'],
            $r['evento'], $r['fecha'], $r['estado'], $r['puntos'], $r['registrado'], $r['registrado_por']
        ], $rows), 'asistencias');
        break;

    case 'ranking_estudiantes':
        $rows = DB::fetchAll(
            "SELECT e.codigo, e.apellidos, e.nombres, c.nombre as ciclo, e.seccion,
                    COALESCE(SUM(a.puntos),0) as total_puntos,
                    COUNT(CASE WHEN a.estado='asistio'  THEN 1 END) as asistencias,
                    COUNT(CASE WHEN a.estado='tardanza' THEN 1 END) as tardanzas,
                    COUNT(CASE WHEN a.estado='falta'    THEN 1 END) as faltas
             FROM estudiantes e
             JOIN ciclos c ON c.id=e.ciclo_id
             LEFT JOIN asistencias a ON a.estudiante_id=e.id
             WHERE e.activo=1
             GROUP BY e.id ORDER BY total_puntos DESC"
        );
        $headers = ['Posicion','Codigo','Apellidos','Nombres','Ciclo','Seccion','Total Puntos','Asistencias','Tardanzas','Faltas'];
        exportCSV($headers, array_map(fn($r,$i) => [
            $i+1, $r['codigo'], $r['apellidos'], $r['nombres'], $r['ciclo'], $r['seccion'],
            $r['total_puntos'], $r['asistencias'], $r['tardanzas'], $r['faltas']
        ], $rows, array_keys($rows)), 'ranking_estudiantes');
        break;

    case 'ranking_ciclos':
        $rows = DB::fetchAll(
            "SELECT c.nombre, COALESCE(SUM(a.puntos),0) as total_puntos,
                    COUNT(DISTINCT e.id) as estudiantes,
                    COUNT(CASE WHEN a.estado='asistio'  THEN 1 END) as asistencias,
                    COUNT(CASE WHEN a.estado='tardanza' THEN 1 END) as tardanzas,
                    COUNT(CASE WHEN a.estado='falta'    THEN 1 END) as faltas
             FROM ciclos c
             LEFT JOIN estudiantes e ON e.ciclo_id=c.id AND e.activo=1
             LEFT JOIN asistencias a ON a.estudiante_id=e.id
             WHERE c.activo=1
             GROUP BY c.id ORDER BY total_puntos DESC"
        );
        $headers = ['Posicion','Ciclo','Total Puntos','Estudiantes','Asistencias','Tardanzas','Faltas'];
        exportCSV($headers, array_map(fn($r,$i) => [
            $i+1, $r['nombre'], $r['total_puntos'], $r['estudiantes'],
            $r['asistencias'], $r['tardanzas'], $r['faltas']
        ], $rows, array_keys($rows)), 'ranking_ciclos');
        break;

    case 'reporte_ciclo':
        $cicloId = (int)($_GET['ciclo_id'] ?? 0);
        $where   = $cicloId ? "AND e.ciclo_id=$cicloId" : '';
        $rows = DB::fetchAll(
            "SELECT e.codigo, e.apellidos, e.nombres, c.nombre as ciclo, e.seccion,
                    COUNT(CASE WHEN a.estado='asistio'  THEN 1 END) as asistencias,
                    COUNT(CASE WHEN a.estado='tardanza' THEN 1 END) as tardanzas,
                    COUNT(CASE WHEN a.estado='falta'    THEN 1 END) as faltas,
                    COALESCE(SUM(a.puntos),0) as total_puntos
             FROM estudiantes e
             JOIN ciclos c ON c.id=e.ciclo_id
             LEFT JOIN asistencias a ON a.estudiante_id=e.id
             WHERE e.activo=1 $where
             GROUP BY e.id ORDER BY total_puntos DESC, e.apellidos"
        );
        $headers = ['Codigo','Apellidos','Nombres','Ciclo','Seccion','Asistencias','Tardanzas','Faltas','Total Puntos'];
        exportCSV($headers, array_map(fn($r) => [
            $r['codigo'],$r['apellidos'],$r['nombres'],$r['ciclo'],$r['seccion'],
            $r['asistencias'],$r['tardanzas'],$r['faltas'],$r['total_puntos']
        ], $rows), 'reporte_ciclo');
        break;

    default:
        http_response_code(404);
        echo 'Tipo de exportacion no valido.';
}
