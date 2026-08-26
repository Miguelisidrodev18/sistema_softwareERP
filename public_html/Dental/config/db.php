<?php
// Configuración de conexión a la base de datos.
// En producción, define estos valores como variables de entorno en el
// servidor (o en un .env cargado antes de este archivo) en vez de
// escribir credenciales reales aquí.
$host    = getenv('DB_HOST') ?: 'localhost';
$dbname  = getenv('DB_NAME') ?: 'u188616411_dental';
$dbuser  = getenv('DB_USER') ?: 'u188616411_dental';
$dbpass  = getenv('DB_PASS') ?: 'Donsultorio1234';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    // En producción no exponer el mensaje real del driver al usuario.
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}
