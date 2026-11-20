<?php
/*
 * ARCHIVO: acceso-google-2fa/verificar-2fa.php
 * QUÉ HACE: la pantalla del SEGUNDO factor. Solo se llega aquí si
 * login.php o google-callback.php dejaron un usuario en
 * $_SESSION['pendiente_2fa'] (pasó el primer factor pero tiene
 * two_factor_enabled = 1).
 *
 * FORMULARIO: POST con el código de 6 dígitos de la app.
 * Se valida con TOTP::verifyCode (tolerancia ±30 s). Si coincide:
 * se borra el pendiente, se crea la SESIÓN COMPLETA (con registro
 * en la tabla sesiones) y se entra al panel. Si no coincide, se
 * queda en esta pantalla con el error: el pendiente NO caduca por
 * intentos aquí, pero sin el código no hay forma de saltársela:
 * ninguna otra página del módulo acepta la sesión pendiente.
 */
require __DIR__ . '/config.php';
require __DIR__ . '/TOTP.php';

if (empty($_SESSION['pendiente_2fa'])) {
    header('Location: login.php');
    exit;
}

$st = bd()->prepare('SELECT * FROM usuarios WHERE id = ?');
$st->execute([$_SESSION['pendiente_2fa']]);
$u = $st->fetch();
if ($u === false || empty($u['totp_secret'])) {
    unset($_SESSION['pendiente_2fa']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (TOTP::verifyCode($u['totp_secret'], $_POST['codigo'] ?? '')) {
        $metodo = $_SESSION['metodo_pendiente'] ?? 'password';
        unset($_SESSION['pendiente_2fa'], $_SESSION['metodo_pendiente']);
        crearSesionCompleta($u, $metodo);
        header('Location: panel.php');
        exit;
    }
    flash('error', 'El código no coincide o ya venció. Abre tu app y escribe el código actual.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Verificación 2FA</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="fondo-acceso">
<main class="tarjeta-acceso">
    <div class="marca-centro">🦷 Odontologia Local</div>
    <h1>Verificación en dos pasos (2FA)</h1>
    <?php mostrarFlash(); ?>
    <p class="ayuda">Hola, <?= e($u['nombre_completo']) ?>. Abre tu app autenticadora (Google Authenticator, Authy o Microsoft Authenticator) y escribe el código de 6 dígitos de <strong>Odontologia Local</strong>. Cambia cada 30 segundos.</p>
    <!-- FORMULARIO · Verificación 2FA.
         Petición: POST a verificar-2fa.php.
         Campos: codigo (6 dígitos) y csrf oculto.
         Éxito: crea la sesión completa y redirige a panel.php. -->
    <form method="post" action="verificar-2fa.php" class="formulario">
        <?= campoCsrf() ?>
        <label>Código de 6 dígitos
            <input type="text" name="codigo" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="codigo-mfa" placeholder="123456">
        </label>
        <button type="submit" class="boton grande">Verificar y entrar →</button>
    </form>
    <p class="enlaces"><a href="login.php">← Cancelar y volver al login</a></p>
</main>
</body>
</html>
