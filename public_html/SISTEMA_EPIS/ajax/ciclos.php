<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'save':
        csrfCheck();
        $id          = (int)($_POST['id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $orden       = max(1, (int)($_POST['orden'] ?? 1));
        $activo      = (int)($_POST['activo'] ?? 1);

        if (!$nombre) jsonResponse(false, 'El nombre del ciclo es requerido.');

        try {
            if ($id) {
                DB::update('ciclos', compact('nombre','descripcion','orden','activo'), 'id = ?', [$id]);
                audit('ciclo_editado', 'ciclos', $id, "Ciclo '$nombre' actualizado");
                jsonResponse(true, 'Ciclo actualizado correctamente.');
            } else {
                // Verificar duplicado
                $existe = DB::fetchColumn("SELECT id FROM ciclos WHERE nombre = ?", [$nombre]);
                if ($existe) jsonResponse(false, "Ya existe un ciclo con el nombre '$nombre'.");
                $newId = DB::insert('ciclos', compact('nombre','descripcion','orden','activo'));
                audit('ciclo_creado', 'ciclos', $newId, "Ciclo '$nombre' creado");
                jsonResponse(true, 'Ciclo creado correctamente.');
            }
        } catch (PDOException $e) {
            jsonResponse(false, 'Error de base de datos: ' . $e->getMessage());
        }
        break;

    case 'toggle':
        csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID invalido.');
        $ciclo = DB::fetchOne("SELECT activo, nombre FROM ciclos WHERE id=?", [$id]);
        if (!$ciclo) jsonResponse(false, 'Ciclo no encontrado.');
        $nuevoEstado = $ciclo['activo'] ? 0 : 1;
        DB::update('ciclos', ['activo' => $nuevoEstado], 'id = ?', [$id]);
        $msg = $nuevoEstado ? 'Ciclo activado.' : 'Ciclo desactivado.';
        audit('ciclo_toggle', 'ciclos', $id, $msg);
        jsonResponse(true, $msg);
        break;

    case 'delete':
        csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID invalido.');
        // Verificar que no tenga estudiantes
        $count = (int)DB::fetchColumn("SELECT COUNT(*) FROM estudiantes WHERE ciclo_id=? AND activo=1", [$id]);
        if ($count > 0) jsonResponse(false, "No se puede eliminar: tiene $count estudiante(s) activo(s).");
        DB::delete('ciclos', 'id = ?', [$id]);
        audit('ciclo_eliminado', 'ciclos', $id, "Ciclo ID $id eliminado");
        jsonResponse(true, 'Ciclo eliminado.');
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
