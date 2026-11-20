<?php
/*
 * ARCHIVO: acceso-google-2fa/configurar-2fa.php
 * QUÉ HACE: activar (o desactivar) el 2FA del usuario logueado.
 * Requiere sesión COMPLETA (no sirve la sesión pendiente de 2FA).
 *
 * FLUJO DE ACTIVACIÓN:
 *  1. Al entrar (GET), si el 2FA no está activo, se genera un
 *     secreto Base32 de 16 caracteres (TOTP::generateSecret) y se
 *     GUARDA en usuarios.totp_secret, pero con
 *     two_factor_enabled TODAVÍA en 0: el secreto solo no protege
 *     nada hasta que el usuario demuestre que lo agregó bien.
 *  2. La pantalla ofrece las DOS opciones de agregado:
 *     A) CÓDIGO QR: la URI otpauth:// (TOTP::getQRCodeUrl) dibujada
 *        con qrcode.js en el navegador (sin extensión GD de PHP).
 *     B) CLAVE MANUAL: el secreto en bloques de 4 letras, con
 *        botón "Copiar código", para escribirlo a mano en la app.
 *  3. CONFIRMACIÓN (POST): el usuario escribe el código de 6
 *     dígitos que le da su app. Si TOTP::verifyCode lo acepta,
 *     two_factor_enabled pasa a 1 y desde el próximo login el
 *     segundo factor será obligatorio. Si no coincide, no se
 *     activa nada.
 *  DESACTIVACIÓN (POST): borra el secreto y pone el flag en 0.
 */
require __DIR__ . '/config.php';
require __DIR__ . '/TOTP.php';
requiereSesion();

$miId = $_SESSION['usuario']['id'];
$st = bd()->prepare('SELECT * FROM usuarios WHERE id = ?');
$st->execute([$miId]);
$u = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (isset($_POST['confirmar'])) {
        // Paso 3: confirmar con el código de la app
        if (!empty($u['totp_secret']) && TOTP::verifyCode($u['totp_secret'], $_POST['codigo'] ?? '')) {
            bd()->prepare('UPDATE usuarios SET two_factor_enabled = 1 WHERE id = ?')->execute([$miId]);
            flash('ok', '¡2FA ACTIVADO! Desde tu próximo ingreso te pediremos el código de la app.');
        } else {
            flash('error', 'Ese código no coincide. Revisa la hora de tu celular e inténtalo con el código actual.');
        }
    } elseif (isset($_POST['desactivar'])) {
        bd()->prepare('UPDATE usuarios SET totp_secret = NULL, two_factor_enabled = 0 WHERE id = ?')->execute([$miId]);
        flash('ok', '2FA desactivado. Tu cuenta vuelve a entrar solo con el primer factor de este módulo.');
    }
    header('Location: configurar-2fa.php');
    exit;
}

// Paso 1: si no está activo y aún no tiene secreto, generar y guardar
if (empty($u['two_factor_enabled']) && empty($u['totp_secret'])) {
    $nuevo = TOTP::generateSecret();
    bd()->prepare('UPDATE usuarios SET totp_secret = ? WHERE id = ?')->execute([$nuevo, $miId]);
    $u['totp_secret'] = $nuevo;
}
$uriQr = !empty($u['totp_secret']) ? TOTP::getQRCodeUrl($u['totp_secret'], $u['correo']) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Configurar 2FA</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
<main class="contenedor" style="max-width:760px">
    <h1>🔐 Configurar mi 2FA (Authenticator)</h1>
    <?php mostrarFlash(); ?>

    <?php if (!empty($u['two_factor_enabled'])): ?>
        <section class="tarjeta-detalle">
            <p>Estado: <strong>2FA ACTIVADO</strong> para <?= e($u['correo']) ?>. Al ingresar (con contraseña o con Google) te pediremos el código de tu app.</p>
            <!-- FORMULARIO · Desactivar 2FA. POST a esta página. -->
            <form method="post" action="configurar-2fa.php" onsubmit="return confirm('¿Desactivar el 2FA? Tu cuenta quedará menos protegida.');">
                <?= campoCsrf() ?>
                <button type="submit" name="desactivar" value="1" class="boton peligro">Desactivar 2FA</button>
            </form>
        </section>
    <?php else: ?>
        <section class="tarjeta-detalle">
            <h2>Opción A — Escanea el código QR</h2>
            <p class="ayuda">Abre tu app autenticadora → «+» → «Escanear código QR»:</p>
            <div class="qr-caja">
                <div id="qr"></div>
                <div>
                    <h2>Opción B — Clave manual</h2>
                    <p class="ayuda">Si no puedes escanear, en la app elige «Ingresar clave de configuración»:<br>
                    Cuenta: <code><?= e($u['correo']) ?></code> · Tipo: <em>Basada en tiempo</em><br>
                    Clave secreta:</p>
                    <p><code class="secreto" id="secreto"><?= e(TOTP::formatearSecreto($u['totp_secret'])) ?></code>
                    <button type="button" class="boton chico secundario" onclick="copiarSecreto()">Copiar código</button></p>
                </div>
            </div>
            <h2>Paso final — Confirma que funciona</h2>
            <!-- FORMULARIO · Confirmación del 2FA.
                 Petición: POST a configurar-2fa.php.
                 Campos: codigo (el de la app) y csrf oculto.
                 Solo si el código es válido, two_factor_enabled = 1. -->
            <form method="post" action="configurar-2fa.php" class="formulario">
                <?= campoCsrf() ?>
                <label>Escribe el código de 6 dígitos que muestra tu app:
                    <input type="text" name="codigo" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" placeholder="123456">
                </label>
                <button type="submit" name="confirmar" value="1" class="boton grande">Confirmar y activar mi 2FA →</button>
            </form>
        </section>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        // El QR se dibuja en TU navegador: el secreto no viaja a
        // ningún servicio externo de códigos QR.
        new QRCode(document.getElementById('qr'), { text: <?= json_encode($uriQr) ?>, width: 180, height: 180 });
        function copiarSecreto() {
            var texto = document.getElementById('secreto').innerText.replace(/\s+/g, '');
            navigator.clipboard.writeText(texto).then(function () { alert('Clave copiada: ' + texto); });
        }
        </script>
    <?php endif; ?>
    <p><a class="boton secundario" href="panel.php">← Volver al panel</a></p>
</main>
</body>
</html>
