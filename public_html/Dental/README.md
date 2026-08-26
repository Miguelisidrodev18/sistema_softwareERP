# Consultorio Dental — Login con roles

Sistema de acceso con dos roles: **dentista** y **paciente**.

## Estructura

```
Dental/
├── index.php              # Redirige según sesión (login o dashboard)
├── config/
│   └── db.php              # Conexión PDO a MySQL (usa variables de entorno)
├── includes/
│   ├── auth.php             # Sesión, helpers de rol, redirecciones
│   ├── header.php           # Cabecera/nav común
│   └── footer.php           # Pie común
├── auth/
│   ├── login.php            # Login (dentista y paciente)
│   ├── register.php         # Registro público (siempre crea "paciente")
│   └── logout.php
├── dentista/
│   ├── dashboard.php        # Panel del dentista (resumen + próximas citas)
│   ├── pacientes.php        # Listado de pacientes
│   ├── citas.php            # Listado de todas las citas, con filtro por estado
│   └── cita_actualizar.php  # Confirmar / completar / cancelar una cita
├── paciente/
│   ├── dashboard.php        # Panel del paciente (sus datos + próximas citas)
│   ├── citas.php            # Solicitar cita y ver historial propio
│   └── cita_cancelar.php    # Cancelar una cita propia (pendiente/confirmada)
├── setup/
│   └── crear_dentista.php   # Crea la ÚNICA vez la cuenta de dentista
├── assets/css/style.css
└── sql/dental_consultorio.sql
```

## Instalación local (para probar)

Necesitas un entorno con PHP + MySQL, por ejemplo **XAMPP** o **Laragon**
(no tienes PHP instalado en esta máquina todavía).

1. Copia la carpeta `Dental` dentro de `htdocs` (XAMPP) o `www` (Laragon).
2. Crea la base de datos importando `sql/dental_consultorio.sql` (phpMyAdmin
   o `mysql -u root -p < sql/dental_consultorio.sql`).
3. Ajusta credenciales en `config/db.php` si no usas `root` sin contraseña.
4. Abre `http://localhost/Dental/setup/crear_dentista.php` y crea la cuenta
   del dentista (una sola vez).
5. **Borra el archivo `setup/crear_dentista.php`** (o toda la carpeta
   `setup/`) después de crear la cuenta.
6. Entra en `http://localhost/Dental/` — te llevará al login.
   - El dentista inicia sesión con la cuenta que acabas de crear.
   - Los pacientes se registran ellos mismos en "Regístrate como paciente".

## Despliegue en hosting compartido (Hostinger u similar)

`config/db.php` ya trae precargadas las credenciales de tu base de datos
`u188616411_dental` (host `localhost`, mismo usuario y contraseña que
creaste en el panel). Pasos:

1. **Sube los archivos**: por el Administrador de archivos de hPanel o por
   FTP, sube todo el contenido de la carpeta `Dental/` dentro de
   `public_html` (o una subcarpeta si el sitio no va en la raíz).
2. **Importa las tablas**: entra a phpMyAdmin desde hPanel, selecciona la
   base `u188616411_dental` en el panel izquierdo, pestaña "Importar" y
   sube `sql/dental_consultorio.sql`.
3. **Verifica el host**: si tu proveedor no usa `localhost` para MySQL
   (revisa el hostname que te dio hPanel al crear la base), cámbialo en
   `config/db.php`.
4. Visita `https://tudominio.com/setup/crear_dentista.php` y crea la
   cuenta del dentista **una sola vez**.
5. **Borra `setup/crear_dentista.php`** del servidor inmediatamente después
   (por FTP o el Administrador de archivos).
6. Entra a `https://tudominio.com/` para iniciar sesión.

⚠️ Como las credenciales reales ya quedaron escritas en `config/db.php`,
asegúrate de que esa carpeta **no** sea públicamente accesible (el
`.htaccess` con `Require all denied` que incluye el proyecto ya lo bloquea
en Apache) y de no subir este proyecto a un repositorio público tal cual.

## Notas para producción

- **Variables de entorno**: `config/db.php` lee `DB_HOST`, `DB_NAME`,
  `DB_USER`, `DB_PASS` del entorno. Configúralas en el panel de tu hosting
  (o en un `.env` cargado antes vía tu `php.ini`/`vhost`) — no dejes
  credenciales reales escritas en el código.
- **HTTPS obligatorio**: las cookies de sesión se marcan `secure` automáticamente
  cuando la petición llega por HTTPS. Sirve el sitio solo por HTTPS en producción.
- **Carpetas protegidas**: `config/`, `includes/` y `sql/` tienen un
  `.htaccess` con `Require all denied` (Apache) para que nadie acceda a
  esos archivos directamente por URL. Si usas Nginx, replica esa regla
  bloqueando esas rutas en el `server {}` block.
- **Cuenta del dentista**: `setup/crear_dentista.php` se autobloquea en
  cuanto existe un dentista en la base de datos, pero aun así **bórralo**
  del servidor de producción una vez usado.
- **Contraseñas**: se guardan con `password_hash()` (bcrypt), nunca en texto plano.
- **Roles**: el registro público (`auth/register.php`) solo puede crear
  pacientes; no hay forma de autoasignarse el rol de dentista desde la web.

## Gestión de citas

- **Paciente**: en "Mis citas" solicita una cita (fecha, hora, motivo). Queda
  en estado `pendiente` hasta que el dentista la confirme. Puede cancelar
  cualquier cita propia que esté `pendiente` o `confirmada`.
- **Dentista**: en "Citas" ve todas las citas de todos los pacientes, puede
  filtrar por estado, y confirmar / completar / cancelar cada una. Las
  transiciones de estado válidas están controladas en el servidor
  (`dentista/cita_actualizar.php`), así que un paciente nunca puede
  confirmar o completar sus propias citas.
- Estados posibles: `pendiente` → `confirmada` → `completada`, o `cancelada`
  en cualquier punto antes de completarse.
