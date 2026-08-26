<?php
$pageTitle = 'Iniciar Sesion';
$error = '';
$errorStudent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    if ($tipo === 'delegate') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $error = 'Ingresa tu usuario y contraseña.';
        } else {
            $user = Auth::loginDelegate($username, $password);
            if ($user) {
                redirect(defaultPage());
            } else {
                $error = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
            }
        }
    } elseif ($tipo === 'student') {
        $codigo = trim($_POST['codigo'] ?? '');
        if (!$codigo) {
            $errorStudent = 'Ingresa tu codigo universitario.';
        } else {
            $student = Auth::loginStudent($codigo);
            if ($student) {
                redirect('student/dashboard');
            } else {
                $errorStudent = 'Codigo universitario no encontrado o estudiante inactivo.';
            }
        }
    }
}
$tab = isset($_POST['tipo']) && $_POST['tipo'] === 'student' ? 'student' : 'delegate';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar Sesion — Sistema EPIS UNH</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <!-- Logo y encabezado -->
    <div class="text-center mb-3">
      <div class="login-logo">
        <span class="logo-text-inner">IS</span>
      </div>
      <h1 class="login-title mt-3">Semana Universitaria</h1>
      <p class="login-subtitle">
        <?= e(DB::config('nombre_facultad','Ingeniería de Sistemas')) ?><br>
        <small class="text-muted"><?= e(DB::config('nombre_universidad','Universidad Nacional de Huancavelica')) ?></small>
      </p>
    </div>

    <div class="divider mb-4"></div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
      <li class="nav-item flex-fill text-center">
        <button class="nav-link w-100 <?= $tab==='delegate'?'active':'' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-delegate" type="button">
          <i class="fas fa-user-shield me-1"></i> Delegado
        </button>
      </li>
      <li class="nav-item flex-fill text-center">
        <button class="nav-link w-100 <?= $tab==='student'?'active':'' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-student" type="button">
          <i class="fas fa-user-graduate me-1"></i> Estudiante
        </button>
      </li>
    </ul>

    <div class="tab-content">
      <!-- Tab Delegado -->
      <div class="tab-pane fade <?= $tab==='delegate'?'show active':'' ?>" id="tab-delegate">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <input type="hidden" name="tipo" value="delegate">
          <div class="mb-3">
            <label class="form-label">Usuario</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="username" class="form-control"
                     placeholder="Ingresa tu usuario"
                     value="<?= e($tab==='delegate' ? ($_POST['username']??'') : '') ?>"
                     autocomplete="username" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="password" id="delegatePass" class="form-control"
                     placeholder="Ingresa tu contraseña" autocomplete="current-password" required>
              <button type="button" class="btn btn-outline-secondary" onclick="togglePass('delegatePass',this)">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            <i class="fas fa-sign-in-alt me-2"></i>Ingresar
          </button>
        </form>
      </div>

      <!-- Tab Estudiante -->
      <div class="tab-pane fade <?= $tab==='student'?'show active':'' ?>" id="tab-student">
        <?php if ($errorStudent): ?>
          <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i><?= e($errorStudent) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <input type="hidden" name="tipo" value="student">
          <div class="mb-4">
            <label class="form-label">Codigo Universitario</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-id-card"></i></span>
              <input type="text" name="codigo" class="form-control"
                     placeholder="Ej: 20230001"
                     value="<?= e($tab==='student' ? ($_POST['codigo']??'') : '') ?>"
                     autocomplete="off" required>
            </div>
            <div class="form-text">Ingresa tu codigo universitario para consultar tus asistencias.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            <i class="fas fa-search me-2"></i>Consultar
          </button>
        </form>
      </div>
    </div>

    <div class="text-center mt-4">
      <small class="text-muted">
        <i class="fas fa-shield-alt me-1"></i>
        Sistema seguro — <?= date('Y') ?>
      </small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(id, btn) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
  } else {
    input.type = 'password';
    btn.innerHTML = '<i class="fas fa-eye"></i>';
  }
}
</script>
</body>
</html>
