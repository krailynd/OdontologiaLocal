<?php
/*
 * ARCHIVO: config/config.php
 * QUÉ ES: la ÚNICA configuración de todo el sistema.
 * Aquí vive: la conexión a MySQL de XAMPP, el modo desarrollo y las
 * credenciales de Google (OAuth). Ningún otro archivo repite estos
 * datos: si cambias de puerto o de contraseña, cambias SOLO aquí.
 *
 * PHP PURO: sin frameworks, sin Composer, sin librerías externas.
 */

// ---------- Zona horaria de la aplicación ----------
// PHP y MySQL deben mirar el MISMO reloj: el bloqueo por intentos,
// la vigencia de códigos y las horas de las citas comparan horas
// de ambos lados. Sin esto, si PHP queda en UTC y la base guarda
// hora de Perú, un bloqueo de 15 minutos puede durar 5 horas o
// saltarse. Por eso se fija aquí, una sola vez, para todo el sistema.
date_default_timezone_set('America/Lima');

// ---------- Base de datos ----------
// DB_HOST va como 127.0.0.1 (TCP) y no como "localhost" (socket):
// el socket de MySQL cambia de ruta según la instalación (en Linux
// suele fallar), mientras que TCP 127.0.0.1:3306 funciona igual en
// XAMPP, en Linux con MariaDB/MySQL de servicio y en Windows.
define('DB_HOST', '127.0.0.1');
define('DB_NOMBRE', 'odontologia_local');
define('DB_USUARIO', 'root');      // usuario por defecto local
define('DB_CLAVE', '');            // en local, root suele ir SIN contraseña

// ---------- Modo desarrollo ----------
// true  = los "correos" (código MFA, enlace de recuperación y avisos)
//         NO salen de tu PC: se guardan en runtime/correos.log y el
//         código/enlace se muestra en pantalla SOLO para practicar.
//         Esto existe porque XAMPP local no envía correo real sin
//         configurar sendmail/SMTP en php.ini.
// false = se intenta enviar con mail() de verdad (servidor real).
define('MODO_DEV', true);

// =====================================================================
//  CORREO REAL POR SMTP (lo que falta para que los códigos y
//  enlaces LLEGUEN de verdad a cualquier correo)
// =====================================================================
//  Sin estos datos, el sistema queda en modo práctica (MODO_DEV):
//  el código/enlace se muestra en pantalla y en runtime/correos.log,
//  pero NO sale ningún correo real, porque tu PC no es un servidor
//  de correo. Para envío real necesitas una cuenta SMTP. Ejemplo
//  con Gmail (gratis y sirve para cualquier destinatario):
//    1. Tu cuenta Google -> Seguridad -> activa la Verificación
//       en 2 pasos.
//    2. En esa misma sección -> «Contraseñas de aplicaciones» ->
//       crea una para «Odontologia Local»: Google te da 16 letras.
//    3. Pega abajo: host smtp.gmail.com, puerto 587, seguridad
//       'tls', usuario = tu Gmail completo, clave = esas 16 letras
//       (NO tu contraseña normal de Google).
//    4. Pon MODO_DEV en false: desde ahí, los códigos MFA y el
//       enlace de recuperación se envían de verdad y ya NO se
//       muestran en pantalla.
//  La clave SMTP es un secreto: no la compartas ni subas el ZIP
//  a GitHub con ella puesta.
define('SMTP_HOST', '');              // ej.: smtp.gmail.com
define('SMTP_PUERTO', 587);
define('SMTP_SEGURIDAD', 'tls');      // 'tls' (587), 'ssl' (465) o '' local
define('SMTP_USUARIO', '');           // ej.: tucuenta@gmail.com
define('SMTP_CLAVE', '');             // contraseña de aplicación (16 letras)
define('SMTP_DESDE_NOMBRE', 'Odontologia Local');
// ¿Hay correo real configurado? Lo usan Correo y las pantallas dev.
define('CORREO_REAL', SMTP_HOST !== '' && SMTP_USUARIO !== '');

// ---------- Nombre de la aplicación (para correos y Authenticator) ----------
define('APP_NOMBRE', 'Odontologia Local');

// =====================================================================
//  LOGIN CON GOOGLE (OAuth 2.0) — CONFIGURACIÓN SEGURA
// =====================================================================
//  Para activar el login con Google:
//    1. Crea un proyecto en https://console.cloud.google.com
//    2. Configura la Pantalla de Consentimiento OAuth y crea credenciales
//       tipo "ID de cliente de OAuth 2.0" (Aplicación web).
//    3. Crea un archivo local "config/config.local.php" con:
//       define('GOOGLE_CLIENT_ID', 'tu-client-id.apps.googleusercontent.com');
//       define('GOOGLE_CLIENT_SECRET', 'tu-client-secret');
//       (o define las variables de entorno GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET).
//
//  ⚠️ REDIRECT URI — OBLIGATORIO REGISTRAR EN GOOGLE CLOUD:
//  En Google Cloud Console -> Credenciales -> tu cliente OAuth ->
//  "URI de redirección autorizados" agrega según corresponda:
//    - http://localhost:8000/index.php?c=auth&a=googleCallback (Linux / iniciar.sh)
//    - http://localhost/OdontologiaLocal/index.php?c=auth&a=googleCallback (XAMPP)
//    - http://localhost:3000/google-callback.php (Módulo independiente)
//
//  Endpoints reales de Google (OAuth 2.0 y OpenID Connect):
//    Autorización: https://accounts.google.com/o/oauth2/v2/auth
//    Token:        https://oauth2.googleapis.com/token
//    Userinfo:     https://openidconnect.googleapis.com/v1/userinfo

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

// ---------- URL base automática ----------
// Se calcula sola según la carpeta donde esté el proyecto dentro de
// htdocs (OdontologiaLocal, clinica, mi-clinica... da igual). Por eso los
// enlaces y el enlace de recuperación nunca se rompen al renombrar.
if (!defined('BASE_URL')) {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $carpeta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    define('BASE_URL', $protocolo . '://' . $host . $carpeta . '/');
}
