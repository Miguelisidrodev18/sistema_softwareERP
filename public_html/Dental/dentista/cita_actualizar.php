<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

requerirRol('dentista');

$transiciones = [
    'confirmar' => ['de' => ['pendiente'],              'a' => 'confirmada'],
    'completar' => ['de' => ['pendiente', 'confirmada'], 'a' => 'completada'],
    'cancelar'  => ['de' => ['pendiente', 'confirmada'], 'a' => 'cancelada'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($id > 0 && isset($transiciones[$accion])) {
        $regla = $transiciones[$accion];
        $placeholders = implode(',', array_fill(0, count($regla['de']), '?'));

        $stmt = $pdo->prepare(
            "UPDATE citas SET estado = ? WHERE id = ? AND estado IN ($placeholders)"
        );
        $stmt->execute([$regla['a'], $id, ...$regla['de']]);
    }
}

header('Location: ' . BASE_URL . '/dentista/citas.php');
exit;
