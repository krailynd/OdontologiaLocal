<?php
/*
 * ARCHIVO: acceso-google-2fa/salir.php
 * QUÉ HACE: cierra la sesión del módulo (solo por POST, con CSRF):
 * vacía $_SESSION, destruye la sesión y vuelve al login.
 */
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $_SESSION = [];
    session_destroy();
}
header('Location: login.php');
exit;
