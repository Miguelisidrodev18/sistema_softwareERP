<?php
declare(strict_types=1);

require_once __DIR__ . '/php/config.php';

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure',   (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? '1' : '0');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si ya hay sesión activa
if (!empty($_SESSION['rol'])) {
    $dest = $_SESSION['rol'] === 'docente' ? 'docente/panel.php' : 'estudiante/quiz.php';
    header('Location: ' . $dest);
    exit;
}

$error_est = '';
$error_doc = '';
$tab_activo = 'estudiante';

// ── Procesar formulario ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = sanitize($_POST['tipo'] ?? '');
    $tab_activo = $tipo;

    if ($tipo === 'estudiante') {
        $codigo = sanitize($_POST['codigo_matricula'] ?? '');
        if ($codigo === '') {
            $error_est = 'Ingrese su código de matrícula.';
        } else {
            try {
                $pdo  = getDB();
                $stmt = $pdo->prepare(
                    "SELECT id, nombre, apellidos, codigo_matricula
                     FROM estudiantes WHERE codigo_matricula = ? AND activo = 1"
                );
                $stmt->execute([$codigo]);
                $row = $stmt->fetch();
                if ($row) {
                    session_regenerate_id(true);
                    $_SESSION['rol']              = 'estudiante';
                    $_SESSION['usuario_id']       = $row['id'];
                    $_SESSION['nombre']           = $row['nombre'];
                    $_SESSION['apellidos']        = $row['apellidos'];
                    $_SESSION['codigo_matricula'] = $row['codigo_matricula'];
                    header('Location: estudiante/quiz.php');
                    exit;
                } else {
                    $error_est = 'Código de matrícula no encontrado.';
                }
            } catch (PDOException $e) {
                error_log('login estudiante: ' . $e->getMessage());
                $error_est = 'Error del servidor. Intente más tarde.';
            }
        }

    } elseif ($tipo === 'docente') {
        $codigo = sanitize($_POST['codigo_docente'] ?? '');
        $pass   = $_POST['contrasena'] ?? '';
        if ($codigo === '' || $pass === '') {
            $error_doc = 'Complete todos los campos.';
        } else {
            try {
                $pdo  = getDB();
                $stmt = $pdo->prepare(
                    "SELECT id, nombre, apellidos, contrasena
                     FROM docentes WHERE codigo = ? AND activo = 1"
                );
                $stmt->execute([$codigo]);
                $row = $stmt->fetch();
                if ($row && password_verify($pass, $row['contrasena'])) {
                    session_regenerate_id(true);
                    $_SESSION['rol']        = 'docente';
                    $_SESSION['usuario_id'] = $row['id'];
                    $_SESSION['nombre']     = $row['nombre'];
                    $_SESSION['apellidos']  = $row['apellidos'];
                    header('Location: docente/panel.php');
                    exit;
                } else {
                    $error_doc = 'Código o contraseña incorrectos.';
                }
            } catch (PDOException $e) {
                error_log('login docente: ' . $e->getMessage());
                $error_doc = 'Error del servidor. Intente más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso — Sistema de Quiz UNH</title>
<style>
:root {
  --unh-azul:     #1A3A6B;
  --unh-azul-mid: #2a5298;
  --unh-dorado:   #F5C518;
  --unh-dorado-dk:#c9a20e;
  --unh-verde:    #1D9E75;
  --unh-rojo:     #C0392B;
  --bg:           #f4f6f9;
  --surface:      #ffffff;
  --border:       #dde3ed;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column}

/* ── Header ── */
.site-header{background:var(--unh-azul);color:#fff;padding:0 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.25)}
.site-header .inner{max-width:900px;margin:0 auto;display:flex;align-items:center;gap:1rem;height:70px}
.site-header img.logo{width:50px;height:50px;border-radius:50%;border:2px solid var(--unh-dorado);object-fit:cover}
.logo-fallback{width:50px;height:50px;border-radius:50%;border:2px solid var(--unh-dorado);background:var(--unh-azul-mid);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;color:var(--unh-dorado);flex-shrink:0}
.header-text h1{font-size:1rem;font-weight:700;letter-spacing:.3px}
.header-text p{font-size:.78rem;opacity:.85;margin-top:1px}

/* ── Main ── */
main{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;
      box-shadow:0 4px 20px rgba(26,58,107,.10);width:100%;max-width:420px;overflow:hidden}

/* ── Tabs ── */
.tabs{display:flex}
.tab-btn{flex:1;padding:.85rem 1rem;border:none;cursor:pointer;font-size:.92rem;font-weight:600;transition:background .2s,color .2s}
.tab-btn.active{background:var(--unh-azul);color:#fff}
.tab-btn:not(.active){background:#eef1f7;color:var(--unh-azul)}
.tab-btn:not(.active):hover{background:#dde3ed}

/* ── Form body ── */
.card-body{padding:1.8rem 2rem 2rem}
.logo-center{display:flex;justify-content:center;margin-bottom:1.4rem}
.logo-center img,.logo-center .logo-fallback{width:72px;height:72px;font-size:1.4rem}
h2.form-title{font-size:1.05rem;color:var(--unh-azul);font-weight:700;text-align:center;margin-bottom:1.4rem}

label{display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:.3rem;margin-top:1rem}
label:first-of-type{margin-top:0}
input[type=text],input[type=password]{
  width:100%;padding:.65rem .9rem;border:1.5px solid var(--border);border-radius:8px;
  font-size:.92rem;font-family:inherit;color:#2d3748;transition:border-color .2s,box-shadow .2s;outline:none}
input:focus{border-color:var(--unh-azul);box-shadow:0 0 0 3px rgba(26,58,107,.12)}

.btn-primary{
  margin-top:1.4rem;width:100%;padding:.75rem;background:var(--unh-azul);color:#fff;
  border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;
  transition:background .2s,transform .1s}
.btn-primary:hover{background:var(--unh-azul-mid)}
.btn-primary:active{transform:scale(.98)}

.alert-error{
  margin-top:1rem;padding:.65rem .9rem;background:#fdecea;border:1px solid #f5c6c2;
  border-radius:8px;color:var(--unh-rojo);font-size:.85rem;font-weight:500}

.pane{display:none}
.pane.active{display:block}

/* ── Footer ── */
footer{text-align:center;padding:1rem;font-size:.75rem;color:#8896a7}
</style>
</head>
<body>

<header class="site-header">
  <div class="inner">
    <img class="logo" src="assets/logo_unh.png" alt="UNH"
         onerror="this.style.display='none';document.getElementById('logo-hdr').style.display='flex'">
    <div class="logo-fallback" id="logo-hdr" style="display:none">UNH</div>
    <div class="header-text">
      <h1>Universidad Nacional de Huancavelica</h1>
      <p>Escuela Profesional de Ingeniería de Sistemas — Sistema de Quiz</p>
    </div>
  </div>
</header>

<main>
  <div class="card">
    <div class="tabs">
      <button class="tab-btn <?= $tab_activo==='estudiante'?'active':'' ?>"
              onclick="setTab('estudiante')">Estudiante</button>
      <button class="tab-btn <?= $tab_activo==='docente'?'active':'' ?>"
              onclick="setTab('docente')">Docente</button>
    </div>

    <div class="card-body">
      <div class="logo-center">
        <img src="assets/logo_unh.png" alt="UNH"
             onerror="this.style.display='none';document.getElementById('logo-crd').style.display='flex'">
        <div class="logo-fallback" id="logo-crd" style="display:none">UNH</div>
      </div>

      <!-- Tab Estudiante -->
      <div id="pane-estudiante" class="pane <?= $tab_activo==='estudiante'?'active':'' ?>">
        <h2 class="form-title">Acceso para Estudiantes</h2>
        <form method="POST">
          <input type="hidden" name="tipo" value="estudiante">
          <label for="codigo_matricula">Código de matrícula</label>
          <input type="text" id="codigo_matricula" name="codigo_matricula"
                 placeholder="Ej: 2021001" autocomplete="off"
                 value="<?= htmlspecialchars($_POST['codigo_matricula'] ?? '', ENT_QUOTES) ?>">
          <?php if ($error_est): ?>
          <div class="alert-error"><?= htmlspecialchars($error_est) ?></div>
          <?php endif; ?>
          <button type="submit" class="btn-primary">Ingresar</button>
        </form>
      </div>

      <!-- Tab Docente -->
      <div id="pane-docente" class="pane <?= $tab_activo==='docente'?'active':'' ?>">
        <h2 class="form-title">Acceso para Docentes</h2>
        <form method="POST">
          <input type="hidden" name="tipo" value="docente">
          <label for="codigo_docente">Código docente</label>
          <input type="text" id="codigo_docente" name="codigo_docente"
                 placeholder="Ej: DOC001" autocomplete="off"
                 value="<?= htmlspecialchars($_POST['codigo_docente'] ?? '', ENT_QUOTES) ?>">
          <label for="contrasena">Contraseña</label>
          <input type="password" id="contrasena" name="contrasena" placeholder="••••••••">
          <?php if ($error_doc): ?>
          <div class="alert-error"><?= htmlspecialchars($error_doc) ?></div>
          <?php endif; ?>
          <button type="submit" class="btn-primary">Ingresar</button>
        </form>
      </div>
    </div>
  </div>
</main>

<footer>© <?= date('Y') ?> Universidad Nacional de Huancavelica — Ingeniería de Sistemas</footer>

<script>
function setTab(t) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
  document.querySelector('[onclick="setTab(\''+t+'\')"]').classList.add('active');
  document.getElementById('pane-' + t).classList.add('active');
}
</script>
</body>
</html>
