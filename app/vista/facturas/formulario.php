<?php
/*
 * VISTA: facturas/formulario.php — emitir una factura.
 * Se eligen hasta 3 tratamientos del catálogo con su cantidad.
 * El PRECIO y el DESCUENTO no se escriben aquí: el controlador los
 * lee del catálogo en el servidor (lo que mande el navegador con
 * precios escritos a mano no se acepta: sería falsificable).
 * Variables: $pacientes, $tratamientos.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<h1>Emitir factura</h1>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=facturas&a=guardar" class="formulario ancho">
    <?= campoCsrf() ?>
    <label>Paciente
        <select name="paciente_id" required>
            <option value="">— Elige el paciente —</option>
            <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= e($p['nombre_completo'] ?? ('DNI ' . $p['dni'])) ?> · DNI <?= e($p['dni']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php for ($i = 0; $i < 3; $i++): ?>
    <label>Tratamiento <?= $i + 1 ?> <?= $i === 0 ? '(obligatorio)' : '(opcional)' ?>
        <span class="fila-linea">
            <select name="tratamiento_id[]">
                <option value="0">— Ninguno —</option>
                <?php foreach ($tratamientos as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= e($t['nombre']) ?> — S/ <?= e(number_format((float) $t['precio'], 2)) ?><?= (float) $t['descuento_pct'] > 0 ? ' (−' . e($t['descuento_pct']) . '%)' : '' ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" value="1" min="0" style="max-width:90px">
        </span>
    </label>
    <?php endfor; ?>
    <button type="submit" class="boton grande">Emitir factura (POST) → abre su detalle</button>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=facturas&a=index">Cancelar y volver</a>
</form>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
