<?php
/*
 * ARCHIVO: app/ayudantes/Funciones.php
 * QUÉ HACE: funciones cortas que usan TODAS las vistas y controladores:
 * escapar HTML (anti-XSS), redirigir, mensajes flash de una sola vista,
 * control de sesión y control de ROLES.
 */

/* Escapa texto antes de imprimirlo en HTML (protección XSS). */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/* Redirige a una ruta del sistema. Ej: redirigir('citas', 'index'). */
function redirigir($controlador, $accion = 'index', $extra = '')
{
    header('Location: ' . BASE_URL . 'index.php?c=' . $controlador . '&a=' . $accion . $extra);
    exit;
}

/* Guarda un mensaje que se muestra UNA vez en la siguiente pantalla. */
function flash($tipo, $mensaje)
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/* Usuario actualmente logueado (arreglo) o null si no hay sesión. */
function usuarioActual()
{
    return $_SESSION['usuario'] ?? null;
}

/* Exige sesión iniciada. Si no hay, manda al login. */
function requiereLogin()
{
    if (usuarioActual() === null) {
        redirigir('auth', 'login');
    }
    // Cambio de contraseña obligatorio en el primer ingreso:
    // mientras no lo haga, SOLO puede estar en esa pantalla.
    if (!empty($_SESSION['debe_cambiar'])) {
        $c = $_GET['c'] ?? '';
        $a = $_GET['a'] ?? '';
        if (!($c === 'auth' && ($a === 'cambiarPrimera' || $a === 'guardarPrimera' || $a === 'salir'))) {
            redirigir('auth', 'cambiarPrimera');
        }
    }
}

/*
 * Exige que el usuario tenga uno de los roles dados.
 * Esta es la barrera de PERMISOS: se revisa en el CONTROLADOR,
 * nunca se confía en que "el botón no se ve" en la vista.
 */
function requiereRol(...$roles)
{
    requiereLogin();
    $u = usuarioActual();
    if (!in_array($u['rol'], $roles, true)) {
        flash('error', 'No tienes permiso para entrar a esa parte del sistema (tu rol es ' . $u['rol'] . ').');
        redirigir('panel', 'index');
    }
}

/* Token CSRF: cada formulario POST lo incluye; cada POST lo verifica. */
function tokenCsrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verificarCsrf()
{
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $enviado)) {
        flash('error', 'La sesión del formulario venció o el envío no es válido. Intenta de nuevo.');
        redirigir('panel', 'index');
    }
}

/* Campo oculto listo para pegar dentro de cualquier <form method="post">. */
function campoCsrf()
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

/* IP real del visitante (para el registro de sesiones). */
function ipCliente()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
}

/* Dispositivo / navegador (User-Agent) para el registro de sesiones. */
function dispositivoCliente()
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconocido', 0, 250);
}
