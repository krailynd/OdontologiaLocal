<?php
/* VISTA: auth/recuperar.php — pedir el enlace de recuperación (un solo uso). */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<h1>Recuperar contraseña</h1>
<p class="ayuda">Escribe tu correo. Si la cuenta existe, te enviaremos un enlace para crear una contraseña nueva. El enlace vence en 30 minutos y <strong>solo se puede usar una vez</strong>.</p>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=enviarRecuperacion" class="formulario">
    <?= campoCsrf() ?>
    <label>Correo de tu cuenta
        <input type="email" name="correo" required placeholder="tucorreo@ejemplo.com">
    </label>
    <button type="submit" class="boton grande">Enviarme el enlace →</button>
</form>
<p class="enlaces"><a href="<?= e(BASE_URL) ?>index.php?c=auth&a=login">← Volver al inicio de sesión</a></p>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
