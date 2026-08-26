<?php
/**
 * Configuración de conexión a la base de datos.
 * IMPORTANTE: confirma en tu hPanel de Hostinger que el nombre exacto
 * de la base de datos y del usuario sean estos (con el prefijo u188616411_).
 */

// --- DATOS DE CONEXIÓN (Hostinger) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'u188616411_dentistachl');
define('DB_USER', 'u188616411_dentistachl');
define('DB_PASS', 'Dentintas12');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos. Verifica config.php. (' . $e->getMessage() . ')');
}

// Sesión (se usa en todas las páginas protegidas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BASE_URL: ruta base del sistema, calculada automáticamente para que
// los enlaces funcionen igual estando en la raíz o en /dentista o /paciente.
if (!defined('BASE_URL')) {
    $appRoot = str_replace('\\', '/', realpath(__DIR__));
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    $base = '';
    if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    }

    define('BASE_URL', rtrim($base, '/'));
}
