<?php
/*
 * VISTA: facturas/detalle.php — comprobante completo con sus líneas.
 * Variables: $factura, $detalle.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<h1>Factura <?= e($factura['numero']) ?></h1>
<section class="tarjeta-detalle">
    <p><strong>Paciente:</strong> <?= e($factura['paciente_nombre']) ?> · <strong>Fecha:</strong> <?= e($factura['fecha_emision']) ?> · <strong>Estado:</strong> <span class="chip estado-<?= e($factura['estado']) ?>"><?= e($factura['estado']) ?></span></p>
    <table class="tabla">
        <thead><tr><th>Descripción</th><th>Cant.</th><th>Precio</th><th>Desc.</th><th>Importe</th></tr></thead>
        <tbody>
        <?php foreach ($detalle as $d): ?>
            <tr>
                <td><?= e($d['descripcion']) ?></td>
                <td><?= (int) $d['cantidad'] ?></td>
                <td>S/ <?= e(number_format((float) $d['precio_unitario'], 2)) ?></td>
                <td><?= (float) $d['descuento_pct'] > 0 ? e($d['descuento_pct']) . '%' : '—' ?></td>
                <td>S/ <?= e(number_format((float) $d['importe'], 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="totales">
        Subtotal: S/ <?= e(number_format((float) $factura['subtotal'], 2)) ?><br>
        Descuento: − S/ <?= e(number_format((float) $factura['descuento'], 2)) ?><br>
        <strong>Total: S/ <?= e(number_format((float) $factura['total'], 2)) ?></strong>
    </p>
    <p><a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=facturas&a=index">← Volver a facturas</a></p>
</section>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
