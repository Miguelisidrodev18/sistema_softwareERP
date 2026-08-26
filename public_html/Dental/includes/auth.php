<?php
// Manejo de sesión y funciones de autenticación/roles.
// Este archivo debe incluirse ANTES de imprimir cualquier HTML.

// BASE_URL: ruta base de la aplicación calculada automáticamente, para que
// todos los enlaces funcionen igual si el sitio vive en la raíz del dominio
// (https://tudominio.com/) o en una subcarpeta (https://tudominio.com/Dental/).
if (!defined('BASE_URL')) {
    $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    $base = '';
    if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    }

    define('BASE_URL', rtrim($base, '/'));
}

if (session_status() === PHP_SESSION_NONE) {
    $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $esHttps, // requiere HTTPS en producción
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function estaLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function usuarioActual(): ?array
{
    if (!estaLogueado()) {
        return null;
    }

    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'rol'    => $_SESSION['usuario_rol'],
    ];
}

// Redirige al login si el usuario no ha iniciado sesión.
function requerirLogin(): void
{
    if (!estaLogueado()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Redirige si el usuario no tiene el rol requerido.
function requerirRol(string $rolRequerido): void
{
    requerirLogin();

    if ($_SESSION['usuario_rol'] !== $rolRequerido) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Manda al usuario a su panel según su rol.
function redirigirSegunRol(string $rol): void
{
    if ($rol === 'dentista') {
        header('Location: ' . BASE_URL . '/dentista/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/paciente/dashboard.php');
    }
    exit;
}

// Devuelve un ícono SVG en línea (estilo trazo, sin relleno) para usar en
// vez de emojis. $clase se agrega al <svg> para poder darle tamaño/color por CSS.
function icono(string $nombre, string $clase = 'icono'): string
{
    $trazos = [
        'diente'    => '<path d="M12 3c-2.4 0-3.7 1.5-4.6 1.5-1.1 0-2.1-.6-3.1-.6-1 3.1-.5 6.2.8 9.6.9 2.4 1.7 6 3.2 6 1.2 0 1-3.4 1.6-4.9.3-.8.7-1.2 1.1-1.2s.9.4 1.1 1.2c.6 1.5.4 4.9 1.6 4.9 1.5 0 2.3-3.6 3.2-6 1.3-3.4 1.8-6.5.8-9.6-1 0-2 .6-3.1.6-.9 0-2.2-1.5-4.6-1.5z"/>',
        'panel'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>',
        'pacientes' => '<circle cx="8" cy="9" r="3"/><path d="M2 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="16.5" cy="9.5" r="2.5"/><path d="M14.5 14.2c2.7.3 4.8 2.6 4.8 5.4"/>',
        'citas'     => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
        'ajustes'   => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'id'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M5.5 16c0-1.7 1.3-3 3-3s3 1.3 3 3"/><path d="M14 9h5M14 12h5M14 15h3"/>',
        'mas'       => '<path d="M12 5v14M5 12h14"/>',
        'salir'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'candado'   => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'buscar'    => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'volver'    => '<path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>',
    ];

    $contenido = $trazos[$nombre] ?? '';

    return '<svg class="' . htmlspecialchars($clase) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $contenido . '</svg>';
}
