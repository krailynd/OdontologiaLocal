<?php
/*
 * VISTA: auth/recuperar_enviado.php — confirmación tras pedir el enlace.
 * El mensaje es SIEMPRE el mismo exista o no el correo (para no
 * revelar qué correos están registrados). En modo desarrollo se
 * muestra el enlace para poder practicar sin servidor de correo.
 * Variable: $enlaceDev (solo dev).
 */
require __DIR__ . '/../parciales/cabecera_simple.php';
?>
<h1>Revisa tu correo</h1>
<p class="ayuda">Si ese correo tiene una cuenta en Odontologia Local, ya enviamos el enlace de recuperación. Recuerda: vence en 30 minutos y solo funciona una vez.</p>
<?php if (MODO_DEV && $enlaceDev): ?>
    <div class="flash aviso">MODO DESARROLLO: este es el enlace que habría llegado por correo:<br>
    <a href="<?= e($enlaceDev) ?>"><?= e($enlaceDev) ?></a></div>
<?php endif; ?>
<p class="enlaces"><a href="<?= e(BASE_URL) ?>index.php?c=auth&a=login">← Volver al inicio de sesión</a></p>
<?php require __DIR__ . '/../parciales/pie_simple.php'; ?>
