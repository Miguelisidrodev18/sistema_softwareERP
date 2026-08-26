<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso Denegado — Sistema EPIS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#002060,#003087);min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff;text-align:center}
</style>
</head>
<body>
<div>
  <i class="fas fa-ban" style="font-size:5rem;color:#dc3545;margin-bottom:1rem"></i>
  <h1 class="fw-800">Acceso Denegado</h1>
  <p>No tienes permiso para acceder a esta seccion.</p>
  <a href="index.php?p=<?= Auth::isAdmin() ? 'admin/dashboard' : (Auth::isDelegate() ? 'delegate/dashboard' : 'student/dashboard') ?>"
     class="btn btn-light fw-bold">
    <i class="fas fa-home me-2"></i>Volver al inicio
  </a>
</div>
</body>
</html>
