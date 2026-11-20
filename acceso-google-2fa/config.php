<?php
/*
 * ARCHIVO: acceso-google-2fa/config.php
 * QUÉ ES: configuración del MÓDULO INDEPENDIENTE de autenticación
 * (Google OAuth + 2FA/TOTP). Es el mismo sistema de acceso de
 * Odontologia Local, pero en archivos sueltos y directos
 * (login.php, google-callback.php, ...) para estudiarlo o probarlo
 * aparte, sin pasar por el Front Controller del MVC.
 *
 * CÓMO SE PRUEBA en http://localhost:3000 (como fue pedido):
 *   Abre una terminal EN ESTA CARPETA y ejecuta:
 *       php -S localhost:3000
 *   Luego entra a http://localhost:3000/login.php
 *   (También funciona en XAMPP, pero ahí la URL de retorno de
 *   Google cambia: lee el LEEME.md de esta carpeta.)
 *
 * Comparte la MISMA base de datos del sistema (odontologia_local):
 * los usuarios, su google_id, su avatar y su 2FA son los mismos.
 */

// ---------- Sesión endurecida + cabeceras (blindaje) ----------
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

// Zona horaria: el mismo reloj que el sistema principal y la base
// (si PHP queda en UTC, los bloqueos y vigencias se descuadran).
date_default_timezone_set('America/Lima');

// ---------- Base de datos (la misma del sistema) ----------
// 127.0.0.1 = conexión TCP: funciona en Linux y en XAMPP, sin
// depender de la ruta del socket de MySQL.
define('DB_HOST', '127.0.0.1');
define('DB_NOMBRE', 'odontologia_local');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');

// ---------- Google OAuth 2.0 (Configuración Segura) ----------
// Cargar credenciales locales si existen (archivo ignorado por Git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}

// Endpoints REALES de Google (del JSON oficial de credenciales).
// (Ojo: no son "https://google.com" ni "https://googleapis.com"
// a secas: cada paso del flujo tiene su dirección completa.)
define('GOOGLE_AUTH_URI', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URI', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URI', 'https://openidconnect.googleapis.com/v1/userinfo');

// La URL de retorno EXACTA registrada en Google Cloud para este
// módulo. Si lo corres en XAMPP, cámbiala por la de XAMPP y
// regístrala también en la consola (LEEME.md lo explica).
define('GOOGLE_REDIRECT_URI', 'http://localhost:3000/google-callback.php');

define('APP_NOMBRE', 'Odontologia Local');

/* Conexión PDO única del módulo (sentencias preparadas siempre). */
function bd()
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4',
            DB_USUARIO,
            DB_CLAVE,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}

/* Escapa texto al imprimir (anti-XSS). */
function e($t)
{
    return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
}

/* Token CSRF del módulo: formularios POST lo llevan y lo verifican. */
function csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verificarCsrf()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        die('Envío inválido (CSRF). Vuelve a intentarlo desde el formulario.');
    }
}

function campoCsrf()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}

/* Mensaje flash de una sola vista. */
function flash($tipo, $mensaje)
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrarFlash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash ' . e($f['tipo']) . '">' . e($f['mensaje']) . '</div>';
    }
}

/*
 * Crea la sesión COMPLETA del usuario (recién cuando ya pasó todos
 * los factores que le correspondan). Regenera el ID de sesión y
 * registra la sesión en la tabla sesiones, igual que el MVC.
 */
function crearSesionCompleta($usuario, $metodo)
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre_completo'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol'],
        'avatar' => $usuario['avatar'] ?? null,
    ];
    $st = bd()->prepare('INSERT INTO sesiones (usuario_id, ip, dispositivo, metodo) VALUES (?, ?, ?, ?)');
    $st->execute([
        $usuario['id'],
        $_SERVER['REMOTE_ADDR'] ?? 'desconocida',
        substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconocido', 0, 250),
        $metodo,
    ]);
}

/* Exige sesión completa; si no hay, al login. */
function requiereSesion()
{
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
}
