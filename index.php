<?php
/*
 * ================================================================
 *  ARCHIVO: index.php — FRONT CONTROLLER (la única puerta)
 * ================================================================
 *  TODAS las pantallas del sistema pasan por este archivo.
 *  La dirección siempre es:  index.php?c=CONTROLADOR&a=ACCION
 *  Ejemplos:
 *    index.php?c=auth&a=login        -> formulario de ingreso
 *    index.php?c=citas&a=index       -> lista de citas
 *    index.php?c=citas&a=guardar     -> (POST) guarda una cita
 *
 *  QUÉ HACE, EN ORDEN:
 *   1. Carga la configuración y arranca la sesión segura.
 *   2. Carga ayudantes, modelos y el controlador pedido.
 *   3. Lee ?c= y ?a=, los limpia (solo letras) y busca la clase
 *      "XxxControlador" y su método. Si no existe -> vuelve al panel.
 *   4. REGLA DE ORO GET/POST: las acciones que CAMBIAN la base de
 *      datos (guardar, estado, cancelar, pagar, salir...) SOLO
 *      funcionan por POST. Si alguien las pide por GET, NO se
 *      ejecutan: se le devuelve a la pantalla anterior con aviso.
 *      GET sirve para VER; POST sirve para CAMBIAR.
 * ================================================================
 */
require __DIR__ . '/config/config.php';

// ---------- BLINDAJE: cabeceras de seguridad en TODA respuesta ----------
// X-Frame-Options: la página no puede meterse en un iframe ajeno
// (anti-clickjacking). nosniff: el navegador no "adivina" tipos de
// archivo. Referrer-Policy: no se filtra la URL (que puede llevar el
// token de recuperación) hacia sitios externos.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.googleusercontent.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline'");

// Cookie de sesión endurecida: HttpOnly (JavaScript no la lee),
// SameSite=Lax (no viaja en envíos cruzados: anti-CSRF extra) y
// Secure automática cuando la conexión es HTTPS.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

require __DIR__ . '/app/ayudantes/Funciones.php';
require __DIR__ . '/app/ayudantes/Totp.php';
require __DIR__ . '/app/ayudantes/Smtp.php';
require __DIR__ . '/app/ayudantes/Correo.php';
require __DIR__ . '/app/ayudantes/Google.php';
require __DIR__ . '/app/modelo/Conexion.php';
require __DIR__ . '/app/modelo/UsuarioModelo.php';
require __DIR__ . '/app/modelo/SeguridadModelo.php';
require __DIR__ . '/app/modelo/PacienteModelo.php';
require __DIR__ . '/app/modelo/CitaModelo.php';
require __DIR__ . '/app/modelo/HistoriaModelo.php';
require __DIR__ . '/app/modelo/TratamientoModelo.php';
require __DIR__ . '/app/modelo/FacturaModelo.php';

// Controlador y acción pedidos (por defecto: login o panel)
$haySesion = !empty($_SESSION['usuario']);
$c = preg_replace('/[^a-z]/', '', strtolower($_GET['c'] ?? ($haySesion ? 'panel' : 'auth')));
$a = preg_replace('/[^a-zA-Z]/', '', $_GET['a'] ?? ($haySesion ? 'index' : 'login'));

// Mapa de controladores permitidos: clave en la URL -> archivo
$controladores = [
    'auth' => 'AuthControlador',
    'panel' => 'PanelControlador',
    'usuarios' => 'UsuarioControlador',
    'pacientes' => 'PacienteControlador',
    'citas' => 'CitaControlador',
    'historias' => 'HistoriaControlador',
    'tratamientos' => 'TratamientoControlador',
    'facturas' => 'FacturaControlador',
    'seguridad' => 'SeguridadControlador',
    'menu' => 'MenuControlador',
];

// Acciones que MODIFICAN datos: solo POST (si llegan por GET, no corren)
$soloPost = ['entrar', 'verificarMfa', 'enviarRecuperacion', 'guardarRestablecimiento',
    'guardarPrimera', 'salir', 'guardar', 'estado', 'confirmar', 'atender', 'cancelar',
    'registrar', 'emitirGuardar', 'pagar', 'anular', 'iniciarTotp', 'confirmarTotp',
    'quitarTotp', 'cambiarContrasena'];

if (!isset($controladores[$c])) {
    redirigir($haySesion ? 'panel' : 'auth', $haySesion ? 'index' : 'login');
}

if (in_array($a, $soloPost, true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Intento de ejecutar una acción de cambio por GET: NO se ejecuta.
    flash('error', 'Esa acción solo funciona enviando el formulario (POST). Por seguridad, nada fue modificado.');
    redirigir($haySesion ? 'panel' : 'auth', $haySesion ? 'index' : 'login');
}

require __DIR__ . '/app/controlador/' . $controladores[$c] . '.php';
$objeto = new $controladores[$c]();

if (!method_exists($objeto, $a)) {
    flash('error', 'Esa pantalla no existe.');
    redirigir($haySesion ? 'panel' : 'auth', $haySesion ? 'index' : 'login');
}

$objeto->$a();
