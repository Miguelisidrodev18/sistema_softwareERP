<?php
echo "PHP OK - session: " . session_status();
session_start();
echo " | rol: " . ($_SESSION['rol'] ?? 'SIN SESION');
echo " | HTTPS: " . ($_SERVER['HTTPS'] ?? 'no set');
echo " | FORWARDED: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'no set');
