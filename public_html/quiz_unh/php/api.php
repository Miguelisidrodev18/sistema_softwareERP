<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = sanitize($_GET['action'] ?? '');

// ── Enrutador ────────────────────────────────────────────────────────────────
switch ($action) {

    // ── GET casos ────────────────────────────────────────────────────────────
    case 'casos':
        requireRolApi('estudiante', 'docente');
        try {
            $pdo  = getDB();
            $stmt = $pdo->query(
                "SELECT id, titulo, rubro, LEFT(descripcion,120) AS resumen
                 FROM casos WHERE activo = 1 ORDER BY id"
            );
            jsonResponse(['data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log('api casos: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener casos.'], 500);
        }
        break;

    // ── GET caso individual ──────────────────────────────────────────────────
    case 'caso':
        requireRolApi('estudiante', 'docente');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'ID de caso inválido.'], 400);
        }
        try {
            $pdo = getDB();
            $sc  = $pdo->prepare("SELECT id,titulo,rubro,descripcion FROM casos WHERE id=? AND activo=1");
            $sc->execute([$id]);
            $caso = $sc->fetch();
            if (!$caso) {
                jsonResponse(['error' => 'Caso no encontrado.'], 404);
            }
            // No enviamos tipo_correcto ni explicaciones al frontend
            $ss = $pdo->prepare(
                "SELECT id, orden, enunciado, puntos
                 FROM situaciones WHERE caso_id=? ORDER BY orden"
            );
            $ss->execute([$id]);
            $caso['situaciones'] = $ss->fetchAll();
            jsonResponse(['data' => $caso]);
        } catch (PDOException $e) {
            error_log('api caso: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener caso.'], 500);
        }
        break;

    // ── POST iniciar intento ─────────────────────────────────────────────────
    case 'iniciar':
        requireRolApi('estudiante');
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $caso_id = (int)($body['caso_id'] ?? 0);
        if ($caso_id <= 0) {
            jsonResponse(['error' => 'caso_id inválido.'], 400);
        }
        $estudiante_id = (int)$_SESSION['usuario_id'];
        try {
            $pdo = getDB();
            // Verificar que el caso existe
            $sc = $pdo->prepare("SELECT id FROM casos WHERE id=? AND activo=1");
            $sc->execute([$caso_id]);
            if (!$sc->fetch()) {
                jsonResponse(['error' => 'Caso no válido.'], 404);
            }
            $ins = $pdo->prepare(
                "INSERT INTO intentos (estudiante_id, caso_id) VALUES (?, ?)"
            );
            $ins->execute([$estudiante_id, $caso_id]);
            jsonResponse(['data' => [
                'intento_id'  => (int)$pdo->lastInsertId(),
                'puntaje_max' => 16,
            ]]);
        } catch (PDOException $e) {
            error_log('api iniciar: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al iniciar intento.'], 500);
        }
        break;

    // ── POST registrar respuesta ─────────────────────────────────────────────
    case 'responder':
        requireRolApi('estudiante');
        $body         = json_decode(file_get_contents('php://input'), true) ?? [];
        $intento_id   = (int)($body['intento_id']   ?? 0);
        $situacion_id = (int)($body['situacion_id'] ?? 0);
        $respuesta    = sanitize((string)($body['respuesta'] ?? ''));

        $opciones_validas = ['Certeza', 'Riesgo', 'Incertidumbre'];
        if ($intento_id <= 0 || $situacion_id <= 0 || !in_array($respuesta, $opciones_validas, true)) {
            jsonResponse(['error' => 'Parámetros inválidos.'], 400);
        }

        $estudiante_id = (int)$_SESSION['usuario_id'];

        try {
            $pdo = getDB();

            // Verificar que el intento pertenece al estudiante y está abierto
            $si = $pdo->prepare(
                "SELECT id, caso_id FROM intentos
                 WHERE id=? AND estudiante_id=? AND completado=0"
            );
            $si->execute([$intento_id, $estudiante_id]);
            $intento = $si->fetch();
            if (!$intento) {
                jsonResponse(['error' => 'Intento no válido o ya finalizado.'], 403);
            }

            // Verificar que la situación no fue ya respondida en este intento
            $sr = $pdo->prepare(
                "SELECT id FROM respuestas WHERE intento_id=? AND situacion_id=?"
            );
            $sr->execute([$intento_id, $situacion_id]);
            if ($sr->fetch()) {
                jsonResponse(['error' => 'Esta situación ya fue respondida.'], 409);
            }

            // Obtener la respuesta correcta del servidor
            $ss = $pdo->prepare(
                "SELECT tipo_correcto, puntos, explicacion_ok, explicacion_fail
                 FROM situaciones
                 WHERE id=? AND caso_id=?"
            );
            $ss->execute([$situacion_id, $intento['caso_id']]);
            $sit = $ss->fetch();
            if (!$sit) {
                jsonResponse(['error' => 'Situación no encontrada.'], 404);
            }

            $es_correcta   = ($respuesta === $sit['tipo_correcto']) ? 1 : 0;
            $puntos_ganados = $es_correcta ? (int)$sit['puntos'] : 0;
            $feedback      = $es_correcta ? $sit['explicacion_ok'] : $sit['explicacion_fail'];

            // Guardar respuesta
            $ir = $pdo->prepare(
                "INSERT INTO respuestas (intento_id, situacion_id, respuesta_dada, es_correcta)
                 VALUES (?, ?, ?, ?)"
            );
            $ir->execute([$intento_id, $situacion_id, $respuesta, $es_correcta]);

            // Actualizar puntaje en intentos
            if ($puntos_ganados > 0) {
                $up = $pdo->prepare(
                    "UPDATE intentos SET puntaje = puntaje + ? WHERE id=?"
                );
                $up->execute([$puntos_ganados, $intento_id]);
            }

            // Obtener puntaje actual
            $sp = $pdo->prepare("SELECT puntaje FROM intentos WHERE id=?");
            $sp->execute([$intento_id]);
            $puntaje_actual = (int)$sp->fetchColumn();

            jsonResponse(['data' => [
                'es_correcta'    => (bool)$es_correcta,
                'puntos_ganados' => $puntos_ganados,
                'tipo_correcto'  => $sit['tipo_correcto'],
                'feedback'       => $feedback,
                'puntaje_actual' => $puntaje_actual,
            ]]);
        } catch (PDOException $e) {
            error_log('api responder: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al registrar respuesta.'], 500);
        }
        break;

    // ── POST finalizar intento ───────────────────────────────────────────────
    case 'finalizar':
        requireRolApi('estudiante');
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $intento_id = (int)($body['intento_id'] ?? 0);
        if ($intento_id <= 0) {
            jsonResponse(['error' => 'intento_id inválido.'], 400);
        }
        $estudiante_id = (int)$_SESSION['usuario_id'];
        try {
            $pdo = getDB();
            $si  = $pdo->prepare(
                "SELECT id, puntaje, puntaje_max FROM intentos
                 WHERE id=? AND estudiante_id=? AND completado=0"
            );
            $si->execute([$intento_id, $estudiante_id]);
            $intento = $si->fetch();
            if (!$intento) {
                jsonResponse(['error' => 'Intento no válido o ya finalizado.'], 403);
            }
            $pdo->prepare(
                "UPDATE intentos SET completado=1, finalizado_en=NOW() WHERE id=?"
            )->execute([$intento_id]);

            $puntaje    = (int)$intento['puntaje'];
            $puntaje_max = (int)$intento['puntaje_max'];
            $porcentaje = round(($puntaje / $puntaje_max) * 100, 1);

            if ($puntaje === $puntaje_max) {
                $mensaje = '¡Puntaje perfecto! Dominas los conceptos de decisión gerencial.';
            } elseif ($puntaje >= 12) {
                $mensaje = '¡Muy bien! Tienes un sólido manejo de las situaciones de decisión.';
            } elseif ($puntaje >= 8) {
                $mensaje = 'Buen intento. Repasa los conceptos y vuelve a intentarlo.';
            } else {
                $mensaje = 'Sigue practicando. Revisa la teoría de decisión y vuelve a intentarlo.';
            }

            jsonResponse(['data' => [
                'puntaje'     => $puntaje,
                'puntaje_max' => $puntaje_max,
                'porcentaje'  => $porcentaje,
                'mensaje'     => $mensaje,
            ]]);
        } catch (PDOException $e) {
            error_log('api finalizar: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al finalizar intento.'], 500);
        }
        break;

    // ── GET historial del estudiante ─────────────────────────────────────────
    case 'mis_intentos':
        requireRolApi('estudiante');
        $estudiante_id = (int)$_SESSION['usuario_id'];
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare(
                "SELECT c.titulo AS caso_titulo, i.puntaje, i.puntaje_max,
                        ROUND((i.puntaje/i.puntaje_max)*100,1) AS porcentaje,
                        i.finalizado_en,
                        IF(i.puntaje >= 12, 1, 0) AS aprobado
                 FROM intentos i
                 JOIN casos c ON c.id = i.caso_id
                 WHERE i.estudiante_id=? AND i.completado=1
                 ORDER BY i.finalizado_en DESC"
            );
            $stmt->execute([$estudiante_id]);
            jsonResponse(['data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log('api mis_intentos: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener historial.'], 500);
        }
        break;

    // ── GET ranking ──────────────────────────────────────────────────────────
    case 'ranking':
        requireRolApi('estudiante', 'docente');
        try {
            $pdo  = getDB();
            $stmt = $pdo->query("SELECT * FROM v_ranking LIMIT 10");
            jsonResponse(['data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log('api ranking: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener ranking.'], 500);
        }
        break;

    // ── GET stats por situación ──────────────────────────────────────────────
    case 'stats':
        requireRolApi('docente');
        try {
            $pdo  = getDB();
            $stmt = $pdo->query("SELECT * FROM v_stats_situaciones");
            jsonResponse(['data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log('api stats: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener estadísticas.'], 500);
        }
        break;

    // ── GET notas para el docente ────────────────────────────────────────────
    case 'notas_docente':
        requireRolApi('docente');
        $caso_id = isset($_GET['caso_id']) ? (int)$_GET['caso_id'] : 0;
        try {
            $pdo  = getDB();
            $sql  = "SELECT i.id, e.codigo_matricula,
                            CONCAT(e.nombre,' ',e.apellidos) AS nombre_completo,
                            c.titulo AS caso, c.id AS caso_id,
                            i.puntaje, i.puntaje_max,
                            ROUND((i.puntaje/i.puntaje_max)*100,1) AS porcentaje,
                            i.finalizado_en,
                            IF(i.puntaje >= 12, 1, 0) AS aprobado
                     FROM intentos i
                     JOIN estudiantes e ON e.id = i.estudiante_id
                     JOIN casos       c ON c.id = i.caso_id
                     WHERE i.completado = 1";
            $params = [];
            if ($caso_id > 0) {
                $sql    .= " AND i.caso_id = ?";
                $params[] = $caso_id;
            }
            $sql .= " ORDER BY i.puntaje DESC, i.finalizado_en ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log('api notas_docente: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener notas.'], 500);
        }
        break;

    // ── GET métricas generales ───────────────────────────────────────────────
    case 'metricas_docente':
        requireRolApi('docente');
        try {
            $pdo = getDB();
            $total_est   = (int)$pdo->query("SELECT COUNT(*) FROM estudiantes WHERE activo=1")->fetchColumn();
            $total_int   = (int)$pdo->query("SELECT COUNT(*) FROM intentos WHERE completado=1")->fetchColumn();
            $prom_stmt   = $pdo->query(
                "SELECT ROUND(AVG(puntaje),2) FROM intentos WHERE completado=1"
            );
            $promedio    = (float)($prom_stmt->fetchColumn() ?? 0);
            $aprobados   = (int)$pdo->query(
                "SELECT COUNT(*) FROM intentos WHERE completado=1 AND puntaje >= 12"
            )->fetchColumn();
            $tasa        = $total_int > 0
                ? round(($aprobados / $total_int) * 100, 1)
                : 0.0;

            jsonResponse(['data' => [
                'total_estudiantes' => $total_est,
                'total_intentos'    => $total_int,
                'promedio_puntaje'  => $promedio,
                'tasa_aprobacion'   => $tasa,
            ]]);
        } catch (PDOException $e) {
            error_log('api metricas_docente: ' . $e->getMessage());
            jsonResponse(['error' => 'Error al obtener métricas.'], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Acción no válida.'], 400);
}
