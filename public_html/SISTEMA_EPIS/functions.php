<?php
// ============================================================
// FUNCIONES AUXILIARES DEL SISTEMA
// ============================================================

// Sanitizar salida HTML
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Redireccion
function redirect(string $page): void {
    header("Location: " . baseUrl("index.php?p=$page"));
    exit;
}

// Retorna respuesta JSON y termina ejecucion
function jsonResponse(bool $success, string $message = '', mixed $data = null): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// Registra en la tabla de auditoria
function audit(string $accion, string $tabla = '', int $registroId = 0, string $detalle = ''): void {
    try {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        DB::query(
            "INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalle, ip)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $accion, $tabla ?: null, $registroId ?: null, $detalle ?: null, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (Exception $e) {
        // Fallo de auditoria no debe interrumpir el flujo
    }
}

// Determina el estado de asistencia segun la hora y configuracion del evento
function calcularEstadoAsistencia(array $evento): string {
    $ahora     = new DateTime('now');
    $inicio    = new DateTime(date('Y-m-d') . ' ' . $evento['hora_inicio']);
    $limPuntual = clone $inicio;
    $limPuntual->modify('+' . (int)$evento['minutos_tolerancia'] . ' minutes');
    $limTardanza = clone $inicio;
    $limTardanza->modify('+' . (int)$evento['minutos_tardanza'] . ' minutes');

    if ($ahora <= $limPuntual)  return 'asistio';
    if ($ahora <= $limTardanza) return 'tardanza';
    return 'falta';
}

// Calcula los puntos segun estado y evento
function calcularPuntos(string $estado, array $evento): int {
    return match($estado) {
        'asistio'  => (int)$evento['puntaje_asistio'],
        'tardanza' => (int)$evento['puntaje_tardanza'],
        'falta'    => (int)$evento['puntaje_falta'],
        default    => 0,
    };
}

// Etiqueta badge HTML segun estado
function badgeEstado(string $estado): string {
    return match($estado) {
        'asistio'    => '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Asistio</span>',
        'tardanza'   => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Tardanza</span>',
        'falta'      => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Falta</span>',
        'programado' => '<span class="badge bg-secondary">Programado</span>',
        'activo'     => '<span class="badge bg-success">Activo</span>',
        'finalizado' => '<span class="badge bg-dark">Finalizado</span>',
        'cancelado'  => '<span class="badge bg-danger">Cancelado</span>',
        default      => '<span class="badge bg-secondary">' . e($estado) . '</span>',
    };
}

// Formatea fecha en español
function fechaES(string $date): string {
    if (!$date) return '-';
    $ts = strtotime($date);
    $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
              'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    return date('d', $ts) . ' de ' . $meses[(int)date('m', $ts)] . ' de ' . date('Y', $ts);
}

// Formatea hora (08:00:00 → 08:00 AM)
function horaFormatted(string $time): string {
    if (!$time) return '-';
    return date('h:i A', strtotime($time));
}

// Verifica si un token CSRF es valido
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(false, 'Token de seguridad invalido. Recarga la pagina.');
    }
}

// Obtiene la URL base del sistema
function baseUrl(string $path = ''): string {
    if (APP_URL) return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
    // Detectar HTTPS incluso detrás de proxy/Cloudflare/cPanel
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['HTTP_X_FORWARDED_SSL']   ?? '') === 'on')
            || (($_SERVER['SERVER_PORT']             ?? '') == '443');
    $protocol = $isHttps ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    $base     = $protocol . '://' . $host . rtrim($script, '/') . '/';
    return $base . ltrim($path, '/');
}

// Paginacion simple
function paginate(string $sql, array $params, int $page, int $perPage = 20): array {
    $total   = (int) DB::fetchColumn("SELECT COUNT(*) FROM ($sql) t", $params);
    $pages   = max(1, ceil($total / $perPage));
    $offset  = ($page - 1) * $perPage;
    $rows    = DB::fetchAll("$sql LIMIT $perPage OFFSET $offset", $params);
    return compact('rows', 'total', 'pages', 'page', 'perPage');
}

// Exporta datos a CSV descargable
function exportCSV(array $headers, array $rows, string $filename = 'export'): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-cache');
    // BOM para UTF-8 en Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Genera HTML de paginacion Bootstrap
function paginationHtml(int $page, int $pages, string $urlBase): string {
    if ($pages <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm justify-content-end mb-0">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $urlBase . '&pg=' . ($page - 1) . '">&laquo;</a></li>';
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) {
        $html .= '<li class="page-item' . ($i === $page ? ' active' : '') . '">';
        $html .= '<a class="page-link" href="' . $urlBase . '&pg=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $urlBase . '&pg=' . ($page + 1) . '">&raquo;</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
