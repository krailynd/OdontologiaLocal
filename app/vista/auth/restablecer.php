<?php
/*
 * VISTA: auth/restablecer.php — crear la contraseña nueva desde el
 * enlace de un solo uso. El token viaja oculto en el formulario y
 * el controlador lo valida OTRA VEZ al guardar (que el enlace se
 * haya abierto bien no basta: al usarlo, el token muere).
 * Variable: $token.
 */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<h1>Crear contraseña nueva</h1>
<p class="ayuda">Este enlace es de <strong>un solo uso</strong>: al guardar, quedará invalidado para siempre.</p>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=guardarRestablecimiento" class="formulario">
    <?= campoCsrf() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label>Contraseña nueva (mínimo 8 caracteres)
        <input type="password" name="contrasena" required minlength="8" autocomplete="new-password">
    </label>
    <button type="submit" class="boton grande">Guardar y volver al login →</button>
</form>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
