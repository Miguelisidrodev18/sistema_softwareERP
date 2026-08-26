<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogueado()) {
    redirigirSegunRol($_SESSION['usuario_rol']);
}

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
