<?php
// Inicializacion compartida para todos los handlers AJAX
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/functions.php';

// CRITICO: usar el mismo nombre de sesion que index.php
// Sin esto, PHP busca PHPSESSID en vez de epis_session y no encuentra la sesion del usuario
session_name(SESSION_NAME);

// Derivar cookie path desde APP_URL si esta configurado, o calcular desde SCRIPT_NAME
if (APP_URL) {
    $cookiePath = parse_url(APP_URL, PHP_URL_PATH) ?: '/';
    if (substr($cookiePath, -1) !== '/') $cookiePath .= '/';
} else {
    // ajax/ esta un nivel mas abajo que index.php, subir un nivel
    $cookiePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    if ($cookiePath !== '/') $cookiePath .= '/';
}

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) session_start();
