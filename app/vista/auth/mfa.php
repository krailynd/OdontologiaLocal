<?php
/*
 * VISTA: auth/mfa.php — SEGUNDO factor (MFA).
 * Dos sabores según lo que tenga el usuario:
 *  - tipo 'correo': código de 6 dígitos enviado por correo
 *    (tabla codigos_mfa, vence en 10 min, un solo uso).
 *  - tipo 'totp': código de la app Google Authenticator
 *    (cambia cada 30 segundos, no viaja por ningún correo).
 * Variables: $tipo, $codigoDev (solo en modo desarrollo).
 */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<?php $infoQueHace = 'Segundo factor: escribes el código de 6 dígitos (de tu correo o de tu app Authenticator si activaste el 2FA).'; $infoPeticion = 'El formulario es POST ?c=auth&a=verificarMfa. El código por correo lo generó SeguridadModelo y lo envió Correo/Smtp.'; $infoNoHace = 'No acepta códigos vencidos ni reutilizados: el de correo se marca usado y vence en 10 minutos; el TOTP vale ±30 segundos.'; $infoArchivo = 'vista/auth/mfa.php + AuthControlador::mfa()/verificarMfa() + SeguridadModelo/Totp'; require __DIR__ . '/../parciales/info_pagina.php'; ?>

<h1>Verificación en dos pasos (MFA)</h1>
<?php if ($tipo === 'totp'): ?>
    <p class="ayuda">Abre tu app <strong>Google Authenticator</strong> y escribe el código de 6 dígitos de Odontologia Local. Cambia cada 30 segundos.</p>
<?php else: ?>
    <p class="ayuda">Te enviamos un código de 6 dígitos a tu correo. Vence en <strong>10 minutos</strong> y solo sirve <strong>una vez</strong>.</p>
    <?php if (MODO_DEV && $codigoDev): ?>
        <div class="flash aviso">MODO DESARROLLO (XAMPP local sin servidor de correo): tu código es <strong><?= e($codigoDev) ?></strong>. En producción este aviso no existe: el código solo llega por correo.</div>
    <?php endif; ?>
<?php endif; ?>

<form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=verificarMfa" class="formulario">
    <?= campoCsrf() ?>
    <label>Código de 6 dígitos
        <input type="text" name="codigo" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456" class="codigo-mfa">
    </label>
    <button type="submit" class="boton grande">Verificar y entrar →</button>
</form>
<p class="enlaces"><a href="<?= e(BASE_URL) ?>index.php?c=auth&a=login">← Volver a iniciar sesión (pide un código nuevo)</a></p>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
