<?php
// Espera que $tituloPagina esté definido antes de incluir este archivo.
$usuario = usuarioActual();
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

function navActiva(string $ruta, string $rutaActual): string
{
    return substr($rutaActual, -strlen($ruta)) === $ruta ? ' class="is-active"' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestión de citas del consultorio dental.">
    <title><?= htmlspecialchars($tituloPagina ?? 'Consultorio Dental') ?> · Consultorio Dental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><text y=%2220%22 font-size=%2220%22>%F0%9F%A6%B7</text></svg>">
    <style>
/* ---------------------------------------------------------
   Consultorio Dental — sistema de diseño
--------------------------------------------------------- */

:root {
    --primary: #0d9488;
    --primary-dark: #0f766e;
    --primary-darker: #115e59;
    --primary-light: #ccfbf1;
    --primary-soft: #f0fdfa;

    --ink: #0f172a;
    --text: #334155;
    --muted: #64748b;
    --border: #e2e8f0;
    --border-soft: #edf2f7;
    --bg: #f4f7f9;
    --surface: #ffffff;

    --danger: #dc2626;
    --danger-bg: #fef2f2;
    --danger-border: #fecaca;

    --success: #16a34a;
    --success-bg: #f0fdf4;
    --success-border: #bbf7d0;

    --warning: #b45309;
    --warning-bg: #fffbeb;
    --warning-border: #fde68a;

    --info: #2563eb;
    --info-bg: #eff6ff;
    --info-border: #bfdbfe;

    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 18px;
    --radius-pill: 999px;

    --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
    --shadow-md: 0 4px 14px rgba(15, 23, 42, 0.08);
    --shadow-lg: 0 12px 32px rgba(15, 23, 42, 0.10);

    --font: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif;
}

* {
    box-sizing: border-box;
}

html {
    -webkit-text-size-adjust: 100%;
}

body {
    margin: 0;
    font-family: var(--font);
    background: var(--bg);
    background-image:
        radial-gradient(circle at 8% -10%, var(--primary-soft) 0%, transparent 45%),
        radial-gradient(circle at 100% 0%, #eef2ff 0%, transparent 40%);
    background-attachment: fixed;
    color: var(--text);
    line-height: 1.55;
    min-height: 100vh;
}

h1, h2, h3 {
    color: var(--ink);
    font-weight: 700;
    letter-spacing: -0.01em;
    margin: 0 0 4px;
}

h1 {
    font-size: 1.65rem;
}

a {
    color: var(--primary-dark);
}

/* ---------------- Sidebar layout ---------------- */

.app-shell {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 264px;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: saturate(180%) blur(10px);
    border-right: 1px solid var(--border-soft);
    display: flex;
    flex-direction: column;
    padding: 22px 16px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 60;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--ink);
    padding: 4px 8px 20px;
    border-bottom: 1px solid var(--border-soft);
    margin-bottom: 16px;
}

.icono {
    width: 20px;
    height: 20px;
    display: block;
}

.brand-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    background: linear-gradient(145deg, var(--primary), var(--primary-darker));
    color: #fff;
    box-shadow: var(--shadow-sm);
    flex-shrink: 0;
}

.brand-mark .icono {
    width: 21px;
    height: 21px;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.brand-title {
    font-weight: 800;
    font-size: 1rem;
    color: var(--ink);
}

.brand-subtitle {
    font-size: 0.72rem;
    color: var(--muted);
    font-weight: 500;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--text);
    font-size: 0.92rem;
    font-weight: 600;
    padding: 11px 14px;
    border-radius: var(--radius-sm);
    transition: background 0.15s ease, color 0.15s ease;
}

.sidebar-nav a:hover {
    background: var(--primary-soft);
    color: var(--primary-dark);
}

.sidebar-nav a.is-active {
    background: var(--primary-light);
    color: var(--primary-darker);
}

.nav-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nav-icon .icono {
    width: 18px;
    height: 18px;
}

.sidebar-footer {
    border-top: 1px solid var(--border-soft);
    padding-top: 14px;
    margin-top: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px;
    border-radius: var(--radius-sm);
    background: var(--bg);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(145deg, var(--primary), var(--primary-darker));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
    overflow: hidden;
}

.user-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.72rem;
    color: var(--muted);
    font-weight: 500;
}

.nav-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--danger);
    padding: 9px 14px;
    border-radius: var(--radius-sm);
    transition: background 0.15s ease;
    background: var(--danger-bg);
}

.nav-logout .icono {
    width: 16px;
    height: 16px;
}

.nav-logout:hover {
    background: #fde2e2;
}

/* ---------------- App main / mobile topbar ---------------- */

.app-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.mobile-topbar {
    display: none;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: saturate(180%) blur(10px);
    border-bottom: 1px solid var(--border-soft);
    position: sticky;
    top: 0;
    z-index: 40;
}

.mobile-topbar-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
    font-size: 0.95rem;
    color: var(--ink);
}

.mobile-topbar-title .icono {
    width: 20px;
    height: 20px;
    color: var(--primary-dark);
}

.nav-toggle {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    flex-shrink: 0;
}

.nav-toggle span {
    width: 18px;
    height: 2px;
    background: var(--ink);
    border-radius: 2px;
}

.sidebar-backdrop {
    display: none;
}

/* ---------------- Layout ---------------- */

.container {
    max-width: 1040px;
    width: 100%;
    margin: 0 auto;
    padding: 36px 24px 64px;
}

.app-main .container {
    flex: 1;
}

body.no-nav {
    background: linear-gradient(160deg, #f4fbfa 0%, #eef2ff 55%, #fdf4ff 100%);
    overflow-x: hidden;
    position: relative;
}

body.no-nav .container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
    position: relative;
    z-index: 1;
    perspective: 1200px;
}

/* Blobs de degradado animados detrás de la tarjeta de login */
.auth-blobs {
    position: fixed;
    inset: 0;
    overflow: hidden;
    z-index: 0;
    pointer-events: none;
}

.auth-blobs span {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.55;
    animation: blobFloat 14s ease-in-out infinite;
}

.auth-blobs span:nth-child(1) {
    width: 420px;
    height: 420px;
    top: -140px;
    left: -120px;
    background: radial-gradient(circle at 30% 30%, var(--primary-light), transparent 70%);
    animation-delay: 0s;
}

.auth-blobs span:nth-child(2) {
    width: 380px;
    height: 380px;
    bottom: -160px;
    right: -100px;
    background: radial-gradient(circle at 60% 40%, #ddd6fe, transparent 70%);
    animation-delay: -5s;
}

.auth-blobs span:nth-child(3) {
    width: 300px;
    height: 300px;
    top: 40%;
    left: 60%;
    background: radial-gradient(circle at 50% 50%, #bae6fd, transparent 70%);
    animation-delay: -9s;
}

@keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.08); }
    66% { transform: translate(-24px, 20px) scale(0.95); }
}

@media (prefers-reduced-motion: reduce) {
    .auth-blobs span { animation: none; }
}

.footer {
    text-align: center;
    padding: 20px;
    color: var(--muted);
    font-size: 0.82rem;
}

.section-title {
    margin-top: 36px;
    margin-bottom: 4px;
    font-size: 1.05rem;
    color: var(--ink);
}

.page-subtitle {
    color: var(--muted);
    font-size: 0.92rem;
    margin: 0 0 6px;
}

.page-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 18px 0;
}

.page-actions .btn {
    margin-top: 0;
}

/* ---------------- Auth (login/registro/setup) ---------------- */

.auth-box {
    width: 100%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: saturate(180%) blur(18px);
    -webkit-backdrop-filter: saturate(180%) blur(18px);
    padding: 44px 38px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow:
        0 1px 1px rgba(255, 255, 255, 0.7) inset,
        0 30px 60px -20px rgba(13, 148, 136, 0.28),
        0 10px 24px -8px rgba(15, 23, 42, 0.15);
    transform-style: preserve-3d;
    transition: transform 0.15s ease-out;
    will-change: transform;
}

.auth-box .auth-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    background: linear-gradient(145deg, var(--primary), var(--primary-darker));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.4) inset,
        0 14px 24px -8px rgba(13, 148, 136, 0.55);
    margin-bottom: 18px;
    transform: translateZ(30px);
    animation: iconFloat 4s ease-in-out infinite;
}

.auth-box .auth-icon .icono {
    width: 28px;
    height: 28px;
}

@keyframes iconFloat {
    0%, 100% { transform: translateZ(30px) translateY(0) rotate(0deg); }
    50% { transform: translateZ(30px) translateY(-6px) rotate(-3deg); }
}

@media (prefers-reduced-motion: reduce) {
    .auth-box .auth-icon { animation: none; }
}

.auth-box h1 {
    font-size: 1.35rem;
    margin-bottom: 4px;
}

.auth-box .auth-subtitle {
    color: var(--muted);
    font-size: 0.88rem;
    margin: 0 0 22px;
}

/* ---------------- Forms ---------------- */

.form {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form label {
    font-size: 0.82rem;
    font-weight: 700;
    margin-top: 14px;
    color: var(--text);
}

.form input {
    padding: 11px 14px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.95rem;
    font-family: var(--font);
    background: var(--surface);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    color: var(--ink);
}

.form input::placeholder {
    color: #a0aec0;
}

.form input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
}

/* ---------------- Buttons ---------------- */

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px 20px;
    border-radius: var(--radius-sm);
    border: 1.5px solid transparent;
    cursor: pointer;
    font-family: var(--font);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    text-align: center;
    transition: transform 0.05s ease, background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.btn:active {
    transform: translateY(1px);
}

.btn-primary {
    background: linear-gradient(145deg, var(--primary), var(--primary-dark));
    color: #fff;
    margin-top: 22px;
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    background: linear-gradient(145deg, var(--primary-dark), var(--primary-darker));
}

.btn-secondary {
    background: var(--surface);
    color: var(--primary-darker);
    border-color: var(--border);
    margin-top: 12px;
}

.btn-secondary:hover {
    border-color: var(--primary);
    background: var(--primary-soft);
}

.btn-danger {
    background: var(--danger-bg);
    color: var(--danger);
    border-color: var(--danger-border);
}

.btn-danger:hover {
    background: #fde2e2;
}

.btn-sm {
    padding: 7px 14px;
    font-size: 0.8rem;
    margin-top: 0;
}

.auth-link {
    margin-top: 22px;
    font-size: 0.88rem;
    text-align: center;
    color: var(--muted);
}

.auth-link a {
    font-weight: 700;
    text-decoration: none;
}

.auth-link a:hover {
    text-decoration: underline;
}

/* ---------------- Alerts ---------------- */

.alert {
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    font-size: 0.87rem;
    font-weight: 500;
    border: 1px solid transparent;
    margin: 0 0 4px;
}

.alert-error {
    background: var(--danger-bg);
    color: var(--danger);
    border-color: var(--danger-border);
}

.alert-success {
    background: var(--success-bg);
    color: var(--success);
    border-color: var(--success-border);
}

/* ---------------- Cards / stats ---------------- */

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
    margin-top: 20px;
}

.card {
    background: var(--surface);
    padding: 24px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-soft);
}

.card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: var(--radius-md);
    background: var(--primary-soft);
    color: var(--primary-dark);
    margin-bottom: 12px;
}

.card-icon .icono {
    width: 22px;
    height: 22px;
}

.card-icon-inline {
    display: inline-flex;
    align-items: center;
    vertical-align: -3px;
    margin-right: 4px;
    color: var(--primary-dark);
}

.card-icon-inline .icono {
    width: 17px;
    height: 17px;
}

.card h2 {
    margin: 0 0 2px;
    font-size: 2.1rem;
    color: var(--primary-dark);
    letter-spacing: -0.02em;
}

.card h3 {
    font-size: 1.05rem;
    margin-bottom: 10px;
}

.card .data-row {
    margin: 0 0 6px;
    font-size: 0.88rem;
}

.card .data-row strong {
    color: var(--ink);
    font-weight: 700;
}

.card-form {
    max-width: 560px;
}

.card p {
    color: var(--muted);
    font-size: 0.9rem;
    margin: 0 0 6px;
}

/* ---------------- Table ---------------- */

.table-wrapper {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-soft);
    overflow-x: auto;
    margin-top: 8px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 560px;
}

.table th, .table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border-soft);
    font-size: 0.88rem;
}

.table th {
    background: var(--bg);
    color: var(--muted);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:hover {
    background: var(--primary-soft);
}

/* ---------------- Badges ---------------- */

.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: var(--radius-pill);
    font-size: 0.74rem;
    font-weight: 700;
    border: 1px solid transparent;
}

.badge::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.badge-pendiente {
    background: var(--warning-bg);
    color: var(--warning);
    border-color: var(--warning-border);
}

.badge-confirmada {
    background: var(--info-bg);
    color: var(--info);
    border-color: var(--info-border);
}

.badge-completada {
    background: var(--success-bg);
    color: var(--success);
    border-color: var(--success-border);
}

.badge-cancelada {
    background: var(--danger-bg);
    color: var(--danger);
    border-color: var(--danger-border);
}

/* ---------------- Filtros / acciones ---------------- */

.filtros {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filtros .btn-secondary {
    margin-top: 0;
    border-radius: var(--radius-pill);
}

.filtros .active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}

.acciones-citas {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.acciones-citas form {
    display: inline;
}

/* ---------------- Empty state ---------------- */

.empty-state {
    background: var(--surface);
    border: 1.5px dashed var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    text-align: center;
    color: var(--muted);
    font-size: 0.9rem;
    margin-top: 8px;
}

.empty-state a {
    font-weight: 700;
}

/* ---------------- Efecto vidrio/brillo en cajas de ícono ---------------- */

.brand-mark,
.auth-box .auth-icon,
.card-icon,
.page-icon,
.user-avatar,
.avatar-chip {
    position: relative;
    overflow: hidden;
}

.brand-mark::before,
.auth-box .auth-icon::before,
.card-icon::before,
.page-icon::before,
.user-avatar::before,
.avatar-chip::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, rgba(255, 255, 255, 0.5), transparent 60%);
    pointer-events: none;
}

.brand-mark,
.user-avatar,
.avatar-chip,
.page-icon {
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.4) inset,
        0 10px 18px -6px rgba(13, 148, 136, 0.5);
}

.card-icon {
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.7) inset,
        0 8px 14px -8px rgba(13, 148, 136, 0.35);
}

/* ---------------- Enlace de volver ---------------- */

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--muted);
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    margin-bottom: 16px;
    transition: color 0.15s ease;
}

.back-link:hover {
    color: var(--primary-dark);
}

.back-link .icono {
    width: 16px;
    height: 16px;
}

/* ---------------- Cabecera de página con ícono + acción ---------------- */

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.page-header .btn {
    margin-top: 0;
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.page-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    background: linear-gradient(145deg, var(--primary), var(--primary-darker));
    color: #fff;
    flex-shrink: 0;
}

.page-icon .icono {
    width: 24px;
    height: 24px;
}

.page-header h1 {
    margin-bottom: 2px;
}

.page-header .page-subtitle {
    margin: 0;
}

/* ---------------- Búsqueda ---------------- */

.search-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--surface);
    border: 1px solid var(--border-soft);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: 6px 6px 6px 16px;
    margin-bottom: 18px;
    max-width: 480px;
}

.search-bar .icono {
    width: 18px;
    height: 18px;
    color: var(--muted);
    flex-shrink: 0;
}

.search-bar input {
    flex: 1;
    border: none;
    padding: 10px 4px;
    font-family: var(--font);
    font-size: 0.9rem;
    background: transparent;
    color: var(--ink);
}

.search-bar input:focus {
    outline: none;
}

/* ---------------- Avatares en tablas ---------------- */

.table-person {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar-chip {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, var(--primary), var(--primary-darker));
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    flex-shrink: 0;
}

/* ---------------- Responsive ---------------- */

@media (max-width: 900px) {
    .mobile-topbar {
        display: flex;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        transform: translateX(-100%);
        transition: transform 0.22s ease;
        box-shadow: var(--shadow-lg);
    }

    .sidebar.is-open {
        transform: translateX(0);
    }

    .sidebar-backdrop {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        z-index: 55;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    .sidebar-backdrop.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .container {
        padding: 24px 16px 48px;
    }

    .auth-box {
        padding: 30px 22px;
    }
}
    </style>
</head>
<body<?= $usuario ? '' : ' class="no-nav"' ?>>
<?php if (!$usuario): ?>
    <div class="auth-blobs" aria-hidden="true"><span></span><span></span><span></span></div>
<?php endif; ?>
<?php if ($usuario): ?>
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar">
        <a href="<?= BASE_URL ?><?= $usuario['rol'] === 'dentista' ? '/dentista/dashboard.php' : '/paciente/dashboard.php' ?>" class="sidebar-brand">
            <span class="brand-mark" aria-hidden="true"><?= icono('diente', 'icono icono-brand') ?></span>
            <span class="brand-text">
                <span class="brand-title">Consultorio Dental</span>
                <span class="brand-subtitle">Sistema de gestión</span>
            </span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($usuario['rol'] === 'dentista'): ?>
                <a href="<?= BASE_URL ?>/dentista/dashboard.php"<?= navActiva('/dentista/dashboard.php', $rutaActual) ?>><span class="nav-icon"><?= icono('panel') ?></span> Panel</a>
                <a href="<?= BASE_URL ?>/dentista/pacientes.php"<?= navActiva('/dentista/pacientes.php', $rutaActual) ?>><span class="nav-icon"><?= icono('pacientes') ?></span> Pacientes</a>
                <a href="<?= BASE_URL ?>/dentista/citas.php"<?= navActiva('/dentista/citas.php', $rutaActual) ?>><span class="nav-icon"><?= icono('citas') ?></span> Citas</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/paciente/dashboard.php"<?= navActiva('/paciente/dashboard.php', $rutaActual) ?>><span class="nav-icon"><?= icono('panel') ?></span> Mi panel</a>
                <a href="<?= BASE_URL ?>/paciente/citas.php"<?= navActiva('/paciente/citas.php', $rutaActual) ?>><span class="nav-icon"><?= icono('citas') ?></span> Mis citas</a>
                <a href="<?= BASE_URL ?>/paciente/perfil.php"<?= navActiva('/paciente/perfil.php', $rutaActual) ?>><span class="nav-icon"><?= icono('candado') ?></span> Mi perfil</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <span class="user-avatar"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></span>
                <span class="user-info">
                    <span class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></span>
                    <span class="user-role"><?= $usuario['rol'] === 'dentista' ? 'Dentista' : 'Paciente' ?></span>
                </span>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-logout"><span class="nav-icon"><?= icono('salir') ?></span> Cerrar sesión</a>
        </div>
    </aside>

    <div class="app-main">
        <header class="mobile-topbar">
            <button class="nav-toggle" type="button" aria-label="Abrir menú" id="sidebarToggle">
                <span></span><span></span><span></span>
            </button>
            <span class="mobile-topbar-title"><?= icono('diente', 'icono icono-topbar') ?> Consultorio Dental</span>
        </header>

        <main class="container">
<?php else: ?>
<main class="container">
<?php endif; ?>
