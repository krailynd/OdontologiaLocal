<?php
/*
 * VISTA: citas/index.php — lista de citas según el rol.
 * Confirmar / Atender / Cancelar son formularios POST por fila.
 * El botón Cancelar solo aparece si la regla de las 2 horas lo
 * permite (el controlador lo vuelve a verificar al recibir el POST:
 * la vista orienta, el servidor manda).
 * Variable: $lista.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();

$modelo = new CitaModelo();
?>
<?php $infoQueHace = 'Muestra las citas según el rol (paciente: las suyas; staff: las de la clínica) con su estado.'; $infoPeticion = 'GET ?c=citas&a=index (solo lectura). Confirmar/atender/cancelar son POST separados, cada uno con su validación.'; $infoNoHace = 'No agenda desde aquí (eso es el formulario crear) y cancelar exige la regla de 2 horas: la valida el modelo, no esta pantalla.'; $infoArchivo = 'vista/citas/index.php + CitaControlador::index() + CitaModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>
<h1>Citas</h1>
<p class="ayuda">Horario de atención: lunes a viernes 08:00–19:00 · sábados 08:00–13:00 · domingos cerrado. Una cita se cancela con al menos 2 horas de anticipación.</p>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=citas&a=crear">+ Agendar cita</a></p>
<table class="tabla">
    <thead><tr><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Odontólogo</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
        <tr>
            <td><?= e($c['fecha']) ?></td>
            <td><?= e(substr($c['hora'], 0, 5)) ?></td>
            <td><?= e($c['paciente_nombre']) ?></td>
            <td><?= e($c['odontologo_nombre']) ?></td>
            <td><?= e($c['motivo'] ?? '') ?></td>
            <td><span class="chip estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></td>
            <td class="acciones">
                <?php if ($c['estado'] === 'pendiente' && in_array($u['rol'], ['admin', 'recepcionista', 'odontologo'], true)): ?>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=citas&a=confirmar" class="en-linea">
                    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button class="boton chico" type="submit">Confirmar</button>
                </form>
                <?php endif; ?>
                <?php if ($c['estado'] === 'confirmada' && in_array($u['rol'], ['admin', 'odontologo'], true)): ?>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=citas&a=atender" class="en-linea">
                    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button class="boton chico" type="submit">Atender</button>
                </form>
                <?php endif; ?>
                <?php if ($modelo->puedeCancelar($c)): ?>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=citas&a=cancelar" class="en-linea" onsubmit="return confirm('¿Cancelar esta cita? Se notificará por correo.');">
                    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <input type="hidden" name="observaciones_cancelacion" value="Cancelada desde el sistema">
                    <button class="boton chico peligro" type="submit">Cancelar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="7">No hay citas para mostrar.</td></tr><?php endif; ?>
    </tbody>
</table>
<div class="nota"><strong>Botones de esta pantalla:</strong> «Confirmar» pasa la cita a confirmada (POST) · «Atender» la pasa a atendida (POST) y habilita registrar la atención en la historia · «Cancelar» solo aparece si faltan 2 horas o más (POST, con notificación por correo registrada).</div>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
