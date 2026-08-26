<?php
// ============================================================
// AUTENTICACION Y CONTROL DE SESION
// ============================================================

class Auth {

    // Inicia sesion con usuario y contrasena (delegados)
    public static function loginDelegate(string $username, string $password): array|false {
        $user = DB::fetchOne(
            "SELECT u.*, c.nombre as ciclo_nombre
             FROM usuarios u
             LEFT JOIN ciclos c ON c.id = u.ciclo_propio_id
             WHERE u.username = ? AND u.activo = 1",
            [$username]
        );
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        self::createSession($user);
        DB::query("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$user['id']]);
        return $user;
    }

    // Login de estudiante por codigo universitario
    public static function loginStudent(string $codigo): array|false {
        $student = DB::fetchOne(
            "SELECT e.*, c.nombre as ciclo_nombre
             FROM estudiantes e
             JOIN ciclos c ON c.id = e.ciclo_id
             WHERE e.codigo = ? AND e.activo = 1",
            [$codigo]
        );
        if (!$student) {
            return false;
        }
        $_SESSION['user_id']       = 'e_' . $student['id'];
        $_SESSION['user_type']     = 'estudiante';
        $_SESSION['user_nombre']   = $student['nombres'] . ' ' . $student['apellidos'];
        $_SESSION['user_codigo']   = $student['codigo'];
        $_SESSION['student_id']    = $student['id'];
        $_SESSION['ciclo_id']      = $student['ciclo_id'];
        $_SESSION['ciclo_nombre']  = $student['ciclo_nombre'];
        return $student;
    }

    // Crea la sesion para un delegado
    private static function createSession(array $user): void {
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_type']     = 'delegado';
        $_SESSION['user_rol']      = $user['rol'];
        $_SESSION['user_nombre']   = $user['nombres'] . ' ' . $user['apellidos'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['ciclo_propio_id'] = $user['ciclo_propio_id'];
    }

    // Cierra la sesion
    public static function logout(): void {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    // Verifica si hay sesion activa
    public static function check(): bool {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '';
    }

    // Verifica si es delegado pleno (admin)
    public static function isAdmin(): bool {
        return self::check()
            && isset($_SESSION['user_rol'])
            && $_SESSION['user_rol'] === 'delegado_pleno';
    }

    // Verifica si es delegado de ciclo
    public static function isDelegate(): bool {
        return self::check()
            && isset($_SESSION['user_type'])
            && $_SESSION['user_type'] === 'delegado';
    }

    // Verifica si es estudiante
    public static function isStudent(): bool {
        return self::check()
            && isset($_SESSION['user_type'])
            && $_SESSION['user_type'] === 'estudiante';
    }

    // Requiere autenticacion o redirige al login
    public static function required(): void {
        if (!self::check()) {
            redirect('login');
        }
    }

    // Requiere ser admin o redirige
    public static function requireAdmin(): void {
        self::required();
        if (!self::isAdmin()) {
            redirect('acceso_denegado');
        }
    }

    // Requiere ser delegado (cualquier tipo) o redirige
    public static function requireDelegate(): void {
        self::required();
        if (!self::isDelegate()) {
            redirect('acceso_denegado');
        }
    }

    // Verifica si el delegado puede evaluar un ciclo dado
    public static function canEvaluateCycle(int $cicloId): bool {
        if (!self::isDelegate()) return false;
        if (self::isAdmin()) return true;

        $delegadoId = (int)$_SESSION['user_id'];
        $cicloPropioId = (int)($_SESSION['ciclo_propio_id'] ?? 0);

        // No puede evaluar su propio ciclo
        if ($cicloPropioId === $cicloId) return false;

        // Verificar que tiene asignado este ciclo
        $row = DB::fetchOne(
            "SELECT id FROM delegado_ciclos WHERE delegado_id = ? AND ciclo_id = ?",
            [$delegadoId, $cicloId]
        );
        return (bool)$row;
    }

    // Retorna los ciclos que puede evaluar el delegado actual
    public static function getAssignedCycles(): array {
        if (!self::isDelegate()) return [];
        if (self::isAdmin()) {
            return DB::fetchAll("SELECT id, nombre FROM ciclos WHERE activo = 1 ORDER BY orden");
        }
        $delegadoId    = (int)$_SESSION['user_id'];
        $cicloPropioId = (int)($_SESSION['ciclo_propio_id'] ?? 0);

        return DB::fetchAll(
            "SELECT c.id, c.nombre
             FROM ciclos c
             JOIN delegado_ciclos dc ON dc.ciclo_id = c.id
             WHERE dc.delegado_id = ? AND c.activo = 1 AND c.id != ?
             ORDER BY c.orden",
            [$delegadoId, $cicloPropioId]
        );
    }

    public static function id(): int {
        return (int)$_SESSION['user_id'];
    }

    public static function nombre(): string {
        return $_SESSION['user_nombre'] ?? '';
    }

    public static function rol(): string {
        return $_SESSION['user_rol'] ?? 'estudiante';
    }
}
