<?php
/*
 * VISTA: auth/login.php — formulario de ingreso (PRIMER factor).
 * Solo muestra el formulario y manda los datos por POST a
 * ?c=auth&a=entrar. No valida nada aquí: validar es del controlador.
 * Variables que recibe: $googleConfigurado (bool).
 */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<?php $infoQueHace = 'Puerta de entrada: correo+contraseña o botón de Google. Tras la contraseña siempre sigue el segundo factor (MFA).'; $infoPeticion = 'El formulario es POST ?c=auth&a=entrar. El botón de Google es GET ?c=auth&a=google hacia accounts.google.com.'; $infoNoHace = 'No crea la sesión completa: solo deja un paso temporal. La sesión nace al pasar el MFA en AuthControlador::verificarMfa().'; $infoArchivo = 'vista/auth/login.php + AuthControlador::login()/entrar() + Google.php'; require __DIR__ . '/../parciales/info_pagina.php'; ?>

<h1>Iniciar sesión</h1>
<p class="ayuda">Ingresa con tu correo y contraseña. Después el sistema te pedirá el segundo factor (MFA).</p>

<form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=entrar" class="formulario">
    <?= campoCsrf() ?>
    <label>Correo
        <input type="email" name="correo" required autocomplete="username" placeholder="tucorreo@ejemplo.com">
    </label>
    <label>Contraseña
        <input type="password" name="contrasena" required autocomplete="current-password" placeholder="••••••••">
    </label>
    <button type="submit" class="boton grande">Entrar y continuar al MFA →</button>
</form>

<div class="separador"><span>o</span></div>

<?php if ($googleConfigurado): ?>
    <a class="boton google" href="<?= e(BASE_URL) ?>index.php?c=auth&a=google">G&nbsp; Entrar con Google</a>
    <p class="ayuda">Te llevará a la pantalla oficial de Google. Tu contraseña de Google nunca pasa por este sistema.</p>
    <?php if (MODO_DEV): ?>
    <!-- Ayuda anti redirect_uri_mismatch (solo en desarrollo): la
         URI de retorno EXACTA que este servidor está enviando a
         Google. Debe estar registrada tal cual en Google Cloud ->
         cliente OAuth -> URIs de redireccionamiento autorizados.
         Si Google bloquea con Error 400, es porque esta cadena no
         está registrada: cópiala de aquí, no la escribas a mano. -->
    <p class="ayuda">URI de retorno a registrar en Google Cloud:<br><code><?= e(Google::urlRetorno()) ?></code></p>
    <?php endif; ?>
<?php else: ?>
    <a class="boton google desactivado" href="<?= e(BASE_URL) ?>index.php?c=auth&a=google">G&nbsp; Entrar con Google (aún no configurado)</a>
    <p class="ayuda">Falta pegar las credenciales de Google Cloud en <code>config/config.php</code>. La guía paso a paso está en la documentación PDF.</p>
<?php endif; ?>

<p class="enlaces">
    <a href="<?= e(BASE_URL) ?>index.php?c=auth&a=recuperar">¿Olvidaste tu contraseña? Recupérala con un enlace de un solo uso</a>
</p>

<details class="demo">
    <summary>Cuentas de prueba (solo para practicar)</summary>
    <ul>
        <li>Admin: <code>admin@odontologialocal.pe</code> / <code>Admin123*</code></li>
        <li>Odontólogo: <code>dr.perez@odontologialocal.pe</code> / <code>Odonto123*</code></li>
        <li>Recepción: <code>recepcion@odontologialocal.pe</code> / <code>Recep123*</code></li>
        <li>Paciente: <code>paciente@odontologialocal.pe</code> / <code>Paciente123*</code></li>
    </ul>
    <p><strong>Cuentas de Google (entran con el botón de Google):</strong></p>
    <ul>
        <li>Cualquier cuenta de Google autorizada se auto-registra automáticamente como <strong>Paciente</strong> en su primer ingreso (código de verificación por correo &rarr; crear contraseña &rarr; panel).</li>
        <li>Para que una cuenta de Google tenga rol <strong>Admin</strong> u <strong>Odontólogo</strong>, créala previamente desde el panel de Usuarios o asígnala en la base de datos (ver <code>infra/roles_google.sql</code>).</li>
    </ul>
    <p>Los roles superiores se dan SOLO desde la base de datos (<code>infra/roles_google.sql</code>), nunca desde la web ni desde Google.</p>
    <p>Tras la contraseña, el código MFA llega "por correo": en modo desarrollo se muestra en la siguiente pantalla y queda en <code>runtime/correos.log</code>.</p>
</details>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
