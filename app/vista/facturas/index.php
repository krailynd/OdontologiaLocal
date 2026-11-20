<?php
/*
 * VISTA: facturas/index.php — comprobantes.
 * Recepción/admin: todas + botones Emitir, Pagar, Anular.
 * Paciente: solo las suyas (el controlador ya filtró la lista).
 * Variable: $lista.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();

?>
<?php $infoQueHace = 'Lista los comprobantes emitidos con su estado (pendiente/pagada/anulada) y total.'; $infoPeticion = 'GET ?c=facturas&a=index (solo lectura). Emitir es un formulario GET + POST guardar; pagar/anular son POST.'; $infoNoHace = 'No permite editar una factura emitida ni escribir el precio a mano: sale del catálogo. Anular no borra el registro.'; $infoArchivo = 'vista/facturas/index.php + FacturaControlador::index() + FacturaModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>
<h1>Facturas / Comprobantes</h1>
<?php if (in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=facturas&a=emitir">+ Emitir factura</a></p>
<?php endif; ?>
<table class="tabla">
    <thead><tr><th>Número</th><th>Paciente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $f): ?>
        <tr>
            <td><?= e($f['numero']) ?></td>
            <td><?= e($f['paciente_nombre']) ?></td>
            <td><?= e($f['fecha_emision']) ?></td>
            <td><strong>S/ <?= e(number_format((float) $f['total'], 2)) ?></strong></td>
            <td><span class="chip estado-<?= e($f['estado']) ?>"><?= e($f['estado']) ?></span><?= $f['metodo_pago'] ? ' · ' . e($f['metodo_pago']) : '' ?></td>
            <td class="acciones">
                <a class="boton chico secundario" href="<?= e(BASE_URL) ?>index.php?c=facturas&a=ver&id=<?= (int) $f['id'] ?>">Detalle</a>
                <?php if ($f['estado'] === 'pendiente' && in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=facturas&a=pagar" class="en-linea">
                    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <select name="metodo_pago"><option>efectivo</option><option>tarjeta</option><option>yape</option><option>transferencia</option></select>
                    <button class="boton chico" type="submit">Cobrar</button>
                </form>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=facturas&a=anular" class="en-linea" onsubmit="return confirm('¿Anular esta factura?');">
                    <?= campoCsrf() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                    <button class="boton chico peligro" type="submit">Anular</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="6">No hay facturas para mostrar.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
