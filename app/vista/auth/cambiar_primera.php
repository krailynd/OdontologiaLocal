<?php
/*
 * VISTA: auth/cambiar_primera.php — cambio OBLIGATORIO de contraseña
 * en el primer ingreso (debe_cambiar_contrasena = 1). Mientras no lo
 * haga, Funciones::requiereLogin() no lo deja ver ninguna otra pantalla.
 */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<h1>Crea tu contraseña</h1>
<p class="ayuda">Es tu primer ingreso. Si entraste con Google, tu cuenta aún no tiene contraseña propia: créala aquí. Si tu cuenta la creó el administrador, tu contraseña es temporal y debes cambiarla. En ambos casos: mínimo 8 caracteres, y con ella también podrás entrar sin Google.</p>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=guardarPrimera" class="formulario">
    <?= campoCsrf() ?>
    <label>Contraseña nueva
        <input type="password" name="contrasena" required minlength="8" autocomplete="new-password">
    </label>
    <button type="submit" class="boton grande">Guardar y entrar al sistema →</button>
</form>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
