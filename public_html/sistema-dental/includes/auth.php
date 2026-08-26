<?php
/**
 * Helpers de autenticación. Incluir DESPUÉS de config.php
 */

function usuario_actual() {
    return $_SESSION['usuario'] ?? null;
}

function requerir_login() {
    if (!isset($_SESSION['usuario'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requerir_rol($rol) {
    requerir_login();
    if ($_SESSION['usuario']['rol'] !== $rol) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
