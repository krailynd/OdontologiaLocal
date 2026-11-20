<?php
/*
 * VISTA: tratamientos/index.php — catálogo de precios.
 * Muestra el precio final YA con el descuento aplicado.
 * Variable: $lista.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();

?>
<?php $infoQueHace = 'Catálogo de tratamientos con precio y descuento: es la fuente de precios de la facturación.'; $infoPeticion = 'GET ?c=tratamientos&a=index (solo lectura). Guardar/estado son POST.'; $infoNoHace = 'No cobra ni factura: solo define precios. La factura lee el precio de aquí en el servidor al emitirse.'; $infoArchivo = 'vista/tratamientos/index.php + TratamientoControlador::index() + TratamientoModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>
<h1>Catálogo de tratamientos</h1>
<?php if (in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=crear">+ Nuevo tratamiento</a></p>
<?php endif; ?>
<table class="tabla">
    <thead><tr><th>Tratamiento</th><th>Descripción</th><th>Precio</th><th>Descuento</th><th>Precio final</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $t): $final = (float) $t['precio'] * (1 - (float) $t['descuento_pct'] / 100); ?>
        <tr>
            <td><?= e($t['nombre']) ?></td>
            <td><?= e($t['descripcion'] ?? '') ?></td>
            <td>S/ <?= e(number_format((float) $t['precio'], 2)) ?></td>
            <td><?= (float) $t['descuento_pct'] > 0 ? e($t['descuento_pct']) . '%' : '—' ?></td>
            <td><strong>S/ <?= e(number_format($final, 2)) ?></strong></td>
            <td><?= (int) $t['estado'] === 1 ? '✅ Activo' : '⛔ Inactivo' ?></td>
            <td class="acciones">
                <?php if (in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
                <a class="boton chico secundario" href="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=editar&id=<?= (int) $t['id'] ?>">Editar</a>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=estado" class="en-linea">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                    <input type="hidden" name="estado" value="<?= (int) $t['estado'] === 1 ? 0 : 1 ?>">
                    <button class="boton chico" type="submit"><?= (int) $t['estado'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
