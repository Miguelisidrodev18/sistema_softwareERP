<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= csrfToken() ?>">
<title><?= e($pageTitle ?? 'Sistema EPIS') ?> — <?= e(DB::config('nombre_sistema','EPIS UNH')) ?></title>
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Sistema EPIS CSS -->
<link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay"></div>

<!-- ======== SIDEBAR ======== -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-2">
      <div class="brand-logo">IS</div>
      <div>
        <div class="brand-text">EPIS - UNH</div>
        <div class="brand-sub">Semana Universitaria</div>
      </div>
    </div>
  </div>

  <?php if (Auth::isAdmin()): ?>
  <!-- MENU DELEGADO PLENO -->
  <ul class="nav flex-column px-0 mt-2" style="list-style:none">
    <li class="nav-section">Principal</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/dashboard')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/dashboard') ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
    </li>

    <li class="nav-section">Gestion</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/ciclos')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/ciclos') ?>">
        <i class="fas fa-layer-group"></i> Ciclos
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/estudiantes')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/estudiantes') ?>">
        <i class="fas fa-user-graduate"></i> Estudiantes
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/delegados')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/delegados') ?>">
        <i class="fas fa-users-cog"></i> Delegados
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/eventos')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/eventos') ?>">
        <i class="fas fa-calendar-star"></i> Eventos
      </a>
    </li>

    <li class="nav-section">Asistencia</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/asistencias')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/asistencias') ?>">
        <i class="fas fa-clipboard-check"></i> Registro
      </a>
    </li>

    <li class="nav-section">Estadisticas</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/ranking')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/ranking') ?>">
        <i class="fas fa-trophy"></i> Ranking
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/reportes')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/reportes') ?>">
        <i class="fas fa-chart-bar"></i> Reportes
      </a>
    </li>

    <li class="nav-section">Sistema</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'admin/configuracion')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=admin/configuracion') ?>">
        <i class="fas fa-cog"></i> Configuracion
      </a>
    </li>
  </ul>

  <?php elseif (Auth::isDelegate()): ?>
  <!-- MENU DELEGADO DE CICLO -->
  <ul class="nav flex-column px-0 mt-2" style="list-style:none">
    <li class="nav-section">Principal</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'delegate/dashboard')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=delegate/dashboard') ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'delegate/asistencia')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=delegate/asistencia') ?>">
        <i class="fas fa-clipboard-check"></i> Registrar Asistencia
      </a>
    </li>
  </ul>

  <?php else: ?>
  <!-- MENU ESTUDIANTE -->
  <ul class="nav flex-column px-0 mt-2" style="list-style:none">
    <li class="nav-section">Mi Cuenta</li>
    <li class="nav-item">
      <a class="nav-link <?= (strpos($page,'student/dashboard')!==false)?'active':'' ?>"
         href="<?= baseUrl('index.php?p=student/dashboard') ?>">
        <i class="fas fa-user"></i> Mi Perfil
      </a>
    </li>
  </ul>
  <?php endif; ?>

  <div class="sidebar-footer">
    <small><?= e(DB::config('nombre_universidad','UNH')) ?></small>
  </div>
</nav>

<!-- ======== TOPBAR ======== -->
<div id="topbar">
  <button id="sidebar-toggle" title="Ocultar menu"><i class="fas fa-bars"></i></button>
  <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
  <div class="topbar-user">
    <small class="d-none d-md-block text-muted"><?= e(Auth::nombre()) ?></small>
    <div class="user-avatar" title="<?= e(Auth::nombre()) ?>">
      <?= strtoupper(substr(Auth::nombre(), 0, 1)) ?>
    </div>
    <div class="dropdown">
      <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="fas fa-chevron-down fa-xs"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><span class="dropdown-item-text small fw-bold"><?= e(Auth::nombre()) ?></span></li>
        <?php if(Auth::isDelegate()): ?>
        <li><span class="dropdown-item-text small text-muted"><?= Auth::isAdmin() ? 'Delegado Pleno' : 'Delegado de Ciclo' ?></span></li>
        <?php endif; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= baseUrl('index.php?p=logout') ?>">
          <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesion
        </a></li>
      </ul>
    </div>
  </div>
</div>

<!-- ======== MAIN CONTENT ======== -->
<div id="main-content">
  <div class="content-wrapper">
