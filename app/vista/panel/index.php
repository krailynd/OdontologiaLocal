<?php
/*
 * VISTA: panel/index.php — inicio según el rol.
 * Variables: $u (usuario), $resumen (solo admin), $misCitas,
 * $misFacturas (solo paciente). Todo viene calculado del controlador.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<?php $infoQueHace = 'Pantalla de inicio según tu rol: resumen y accesos de tu trabajo diario.'; $infoPeticion = 'GET ?c=panel&a=index (solo lectura, no cambia ningún dato).'; $infoNoHace = 'No edita nada: es un tablero. Los cambios se hacen en cada módulo.'; $infoArchivo = 'vista/panel/index.php + PanelControlador::index()'; require __DIR__ . '/../parciales/info_pagina.php'; ?>

<h1>Hola, <?= e($u['nombre']) ?> <span class="chip"><?= e($u['rol']) ?></span></h1>

<?php if (!empty($mostrarBienvenida2fa)): ?>
<!-- VENTANA DE BIENVENIDA (solo usuarios nuevos de Google, una vez):
     recomienda activar la verificación en 2 pasos con QR.
     «Activar ahora» lleva a Mi seguridad; «Ahora no» solo la cierra
     (la marca de sesión ya se borró en el controlador: no vuelve). -->
<div class="modal-fondo" id="modal2fa">
    <div class="modal-tarjeta">
        <div class="modal-icono">🛡️</div>
        <h2>¡Bienvenido/a a Odontologia Local!</h2>
        <p>Tu cuenta ya está creada y verificada. Te recomendamos un paso más: <strong>activa la verificación en 2 pasos (2FA)</strong>. Además de tu contraseña, al ingresar escribirás un código de 6 dígitos que genera tu celular con un <strong>código QR</strong> (Google Authenticator). Toma menos de un minuto y tu cuenta queda mucho más protegida.</p>
        <div class="modal-botones">
            <a class="boton grande" href="<?= e(BASE_URL) ?>index.php?c=seguridad&a=index">Activar ahora con QR →</a>
            <button type="button" class="boton secundario" onclick="document.getElementById('modal2fa').style.display='none'">Ahora no</button>
        </div>
        <p class="ayuda">Puedes activarlo cuando quieras desde <strong>Mi seguridad</strong>.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($u['rol'] === 'admin' && $resumen): ?>
<section class="tarjetas">
    <div class="tarjeta"><strong><?= e($resumen['usuarios_activos']) ?></strong><span>Usuarios activos</span></div>
    <div class="tarjeta"><strong><?= e($resumen['pacientes']) ?></strong><span>Pacientes registrados</span></div>
    <div class="tarjeta"><strong><?= e($resumen['citas_hoy']) ?></strong><span>Citas de hoy</span></div>
    <div class="tarjeta"><strong>S/ <?= e(number_format((float) $resumen['cobrado'], 2)) ?></strong><span>Total cobrado (facturas pagadas)</span></div>
</section>
<p class="ayuda">Como administrador gestionas usuarios y roles, ves reportes completos, descargas el respaldo de la base de datos desde <a href="<?= e(BASE_URL) ?>index.php?c=seguridad&a=index">Mi seguridad</a> y configuras el sistema.</p>
<?php endif; ?>

<?php if (in_array($u['rol'], ['odontologo', 'recepcionista'], true)): ?>
<h2><?= $u['rol'] === 'odontologo' ? 'Tus próximas citas' : 'Citas de hoy en la clínica' ?></h2>
<?php if (!$misCitas): ?><p class="ayuda">No hay citas para mostrar.</p><?php endif; ?>
<ul class="lista-simple">
    <?php foreach ($misCitas as $c): ?>
    <li><strong><?= e($c['fecha']) ?> <?= e(substr($c['hora'], 0, 5)) ?></strong> — <?= e($c['paciente_nombre']) ?> con <?= e($c['odontologo_nombre']) ?> <span class="chip estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></li>
    <?php endforeach; ?>
</ul>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=citas&a=index">Ir a Citas →</a></p>
<?php endif; ?>

<?php if ($u['rol'] === 'paciente'): ?>
<h2>Tus próximas citas</h2>
<?php if (!$misCitas): ?><p class="ayuda">No tienes citas próximas. Puedes reservar una desde Citas.</p><?php endif; ?>
<ul class="lista-simple">
    <?php foreach ($misCitas as $c): ?>
    <li><strong><?= e($c['fecha']) ?> <?= e(substr($c['hora'], 0, 5)) ?></strong> — con <?= e($c['odontologo_nombre']) ?> <span class="chip estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></li>
    <?php endforeach; ?>
</ul>
<h2>Tus facturas pendientes</h2>
<?php if (!$misFacturas): ?><p class="ayuda">No tienes facturas pendientes. 🎉</p><?php endif; ?>
<ul class="lista-simple">
    <?php foreach ($misFacturas as $f): ?>
    <li><strong><?= e($f['numero']) ?></strong> — S/ <?= e(number_format((float) $f['total'], 2)) ?> · <?= e($f['fecha_emision']) ?></li>
    <?php endforeach; ?>
</ul>
<p>
    <a class="boton" href="<?= e(BASE_URL) ?>index.php?c=citas&a=crear">Reservar una cita →</a>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=historias&a=ver">Ver mi historia clínica →</a>
</p>
<?php endif; ?>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
