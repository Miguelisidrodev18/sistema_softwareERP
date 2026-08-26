<?php
require_once __DIR__ . '/_init.php';
Auth::requireAdmin();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = DB::fetchAll(
            "SELECT u.*, c.nombre as ciclo_propio_nombre,
                    CONCAT(u.nombres,' ',u.apellidos) as nombres_completos,
                    GROUP_CONCAT(c2.nombre SEPARATOR '|') as ciclos_asignados_nombres,
                    GROUP_CONCAT(dc.ciclo_id) as ciclos_asignados_ids
             FROM usuarios u
             LEFT JOIN ciclos c  ON c.id  = u.ciclo_propio_id
             LEFT JOIN delegado_ciclos dc ON dc.delegado_id = u.id
             LEFT JOIN ciclos c2 ON c2.id = dc.ciclo_id
             GROUP BY u.id
             ORDER BY u.rol, u.apellidos, u.nombres"
        );
        jsonResponse(true, '', $rows);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $user = DB::fetchOne("SELECT * FROM usuarios WHERE id=?", [$id]);
        if (!$user) jsonResponse(false, 'Delegado no encontrado.');
        unset($user['password']);
        // Ciclos asignados
        $ciclosAsignados = DB::fetchAll(
            "SELECT ciclo_id FROM delegado_ciclos WHERE delegado_id=?", [$id]
        );
        $user['ciclos_asignados'] = array_map(fn($r) => (int)$r['ciclo_id'], $ciclosAsignados);
        jsonResponse(true, '', $user);
        break;

    case 'save':
        csrfCheck();
        $id           = (int)($_POST['id'] ?? 0);
        $nombres      = trim($_POST['nombres'] ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $username     = trim($_POST['username'] ?? '');
        $email        = trim($_POST['email'] ?? '') ?: null;
        $password     = $_POST['password'] ?? '';
        $rol          = in_array($_POST['rol']??'',['delegado_pleno','delegado_ciclo']) ? $_POST['rol'] : 'delegado_ciclo';
        $cicloPropioId= (int)($_POST['ciclo_propio_id'] ?? 0) ?: null;
        $activo       = (int)($_POST['activo'] ?? 1);
        $ciclosAsig   = $_POST['ciclos_asignados'] ?? [];

        if (!$nombres)  jsonResponse(false, 'El nombre es requerido.');
        if (!$apellidos) jsonResponse(false, 'El apellido es requerido.');
        if (!$username)  jsonResponse(false, 'El usuario es requerido.');
        if (!$id && !$password) jsonResponse(false, 'La contraseña es requerida.');
        if ($password && strlen($password) < 6) jsonResponse(false, 'La contraseña debe tener al menos 6 caracteres.');

        // Verificar username duplicado
        $whereId = $id ? "AND id != $id" : '';
        $existe  = DB::fetchColumn("SELECT id FROM usuarios WHERE username = ? $whereId", [$username]);
        if ($existe) jsonResponse(false, "El usuario '$username' ya esta registrado.");

        try {
            $data = [
                'nombres'         => $nombres,
                'apellidos'       => $apellidos,
                'username'        => $username,
                'email'           => $email,
                'rol'             => $rol,
                'ciclo_propio_id' => $cicloPropioId,
                'activo'          => $activo,
            ];
            if ($password) $data['password'] = password_hash($password, PASSWORD_DEFAULT);

            if ($id) {
                DB::update('usuarios', $data, 'id = ?', [$id]);
                // Actualizar ciclos asignados
                DB::delete('delegado_ciclos', 'delegado_id = ?', [$id]);
                foreach ($ciclosAsig as $cicloId) {
                    DB::insert('delegado_ciclos', ['delegado_id'=>$id,'ciclo_id'=>(int)$cicloId]);
                }
                audit('delegado_editado', 'usuarios', $id, "Delegado $username actualizado");
                jsonResponse(true, 'Delegado actualizado correctamente.');
            } else {
                $newId = DB::insert('usuarios', $data);
                foreach ($ciclosAsig as $cicloId) {
                    DB::insert('delegado_ciclos', ['delegado_id'=>$newId,'ciclo_id'=>(int)$cicloId]);
                }
                audit('delegado_creado', 'usuarios', $newId, "Delegado $username creado");
                jsonResponse(true, 'Delegado registrado correctamente.');
            }
        } catch (PDOException $e) {
            jsonResponse(false, 'Error: ' . $e->getMessage());
        }
        break;

    case 'delete':
        csrfCheck();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(false, 'ID invalido.');
        if ($id === Auth::id()) jsonResponse(false, 'No puedes eliminar tu propia cuenta.');
        DB::delete('delegado_ciclos', 'delegado_id = ?', [$id]);
        DB::delete('usuarios', 'id = ?', [$id]);
        audit('delegado_eliminado', 'usuarios', $id);
        jsonResponse(true, 'Delegado eliminado.');
        break;

    default:
        jsonResponse(false, 'Accion no reconocida.');
}
