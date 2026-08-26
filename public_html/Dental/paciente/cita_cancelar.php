<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('paciente');

$usuario = usuarioActual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    // Solo puede cancelar sus propias citas, y solo si aún no pasaron.
    $stmt = $pdo->prepare(
        "UPDATE citas
         SET estado = 'cancelada'
         WHERE id = ? AND paciente_id = ? AND estado IN ('pendiente', 'confirmada')"
    );
    $stmt->execute([$id, $usuario['id']]);
}

header('Location: ' . BASE_URL . '/paciente/citas.php');
exit;
