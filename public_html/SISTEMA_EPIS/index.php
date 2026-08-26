<?php
// ============================================================
// FRONT CONTROLLER - ROUTER PRINCIPAL
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Iniciar sesion segura
session_name(SESSION_NAME);
// Detectar ruta base para la cookie de sesion (necesario en subdirectorios)
$cookiePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) session_start();

// Generar CSRF token si no existe
csrfToken();

$page = trim($_GET['p'] ?? '');
if ($page === '') $page = 'login';

// --- RUTAS PUBLICAS ---
if ($page === 'login') {
    if (Auth::check()) {
        redirect(defaultPage());
    }
    include VIEWS_PATH . '/login.php';
    exit;
}

if ($page === 'logout') {
    Auth::logout();
    redirect('login');
}

// --- REQUIERE AUTENTICACION ---
Auth::required();

// Redireccion por defecto segun rol
if ($page === 'home' || $page === 'dashboard') {
    redirect(defaultPage());
}

// --- ACCESO DENEGADO ---
if ($page === 'acceso_denegado') {
    include VIEWS_PATH . '/acceso_denegado.php';
    exit;
}

// --- RUTAS ADMIN (delegado_pleno) ---
$adminPages = [
    'admin/dashboard', 'admin/ciclos', 'admin/estudiantes',
    'admin/delegados', 'admin/eventos', 'admin/asistencias',
    'admin/ranking', 'admin/reportes', 'admin/configuracion',
];

if (in_array($page, $adminPages)) {
    Auth::requireAdmin();
    $file = VIEWS_PATH . '/' . $page . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        include VIEWS_PATH . '/404.php';
    }
    exit;
}

// --- RUTAS DELEGADO DE CICLO ---
$delegatePages = ['delegate/dashboard', 'delegate/asistencia'];
if (in_array($page, $delegatePages)) {
    Auth::requireDelegate();
    $file = VIEWS_PATH . '/' . $page . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        include VIEWS_PATH . '/404.php';
    }
    exit;
}

// --- RUTAS ESTUDIANTE ---
$studentPages = ['student/dashboard'];
if (in_array($page, $studentPages)) {
    if (!Auth::isStudent()) redirect('login');
    $file = VIEWS_PATH . '/' . $page . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        include VIEWS_PATH . '/404.php';
    }
    exit;
}

// 404
include VIEWS_PATH . '/404.php';

// Retorna la pagina por defecto segun el rol
function defaultPage(): string {
    if (Auth::isAdmin())    return 'admin/dashboard';
    if (Auth::isDelegate()) return 'delegate/dashboard';
    if (Auth::isStudent())  return 'student/dashboard';
    return 'login';
}
