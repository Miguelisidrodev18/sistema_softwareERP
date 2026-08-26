<?php
declare(strict_types=1);

// Bloquear acceso directo a este archivo
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// ── Seguridad de sesión antes de cualquier session_start() ──────────────────
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
          || (($_SERVER['SERVER_PORT'] ?? '') === '443');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   $_isHttps ? '1' : '0');
ini_set('session.use_strict_mode', '1');

// ── Headers de seguridad HTTP ───────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// ── Credenciales de base de datos (variables de entorno con fallback vacío) ─
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'u188616411_eladio');
define('DB_USER',    getenv('DB_USER')    ?: 'u188616411_eladio');
define('DB_PASS',    getenv('DB_PASS')    ?: 'Piero2007-');
define('DB_CHARSET', 'utf8mb4');

// ── Singleton PDO ────────────────────────────────────────────────────────────
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';dbname='    . DB_NAME
         . ';charset='   . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('DB connection error: ' . $e->getMessage());
        jsonResponse(['error' => 'Error de conexión a la base de datos.'], 500);
        exit;
    }

    return $pdo;
}

// ── Respuesta JSON normalizada ───────────────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Sanitización básica de entrada ──────────────────────────────────────────
function sanitize(string $val): string
{
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

// ── Guardia de rol ───────────────────────────────────────────────────────────
function requireRol(string $rol): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (($_SESSION['rol'] ?? '') !== $rol) {
        header('Location: ../login.php');
        exit;
    }
}

// ── Guardia de rol para API (responde JSON en lugar de redirigir) ────────────
function requireRolApi(string ...$roles): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        jsonResponse(['error' => 'No autorizado.'], 401);
    }
}
