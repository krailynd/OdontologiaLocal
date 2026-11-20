<?php
/*
 * VISTA: historias/ver.php — la historia clínica ÚNICA del paciente
 * con TODOS sus registros de atención, y (para odontólogo/admin) el
 * formulario para agregar una atención nueva.
 * Variables: $paciente, $historia, $registros, $tratamientos,
 * $citasAtendidas.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();

?>
<?php $infoQueHace = 'Muestra LA historia clínica única del paciente y todos sus registros de atención en orden.'; $infoPeticion = 'GET ?c=historias&a=ver&id= (solo lectura). Agregar una atención es el POST historias/registrar de abajo.'; $infoNoHace = 'No crea una historia nueva por atención: hay UNA por paciente (paciente_id UNIQUE) y aquí solo se le agregan registros.'; $infoArchivo = 'vista/historias/ver.php + HistoriaControlador::ver() + HistoriaModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>
<h1>Historia clínica — <?= e($paciente['nombre_completo'] ?? ('DNI ' . $paciente['dni'])) ?></h1>
<p class="ayuda">Historia N.º <?= (int) $historia['id'] ?> · abierta el <?= e($historia['fecha_apertura']) ?> · DNI <?= e($paciente['dni']) ?><br>
<strong>Antecedentes:</strong> <?= e($paciente['antecedentes'] ?? 'Sin registros.') ?></p>

<h2>Registros de atención (<?= count($registros) ?>)</h2>
<?php if (!$registros): ?><p class="ayuda">Aún no hay atenciones registradas en esta historia.</p><?php endif; ?>
<?php foreach ($registros as $r): ?>
<article class="registro">
    <header><strong><?= e($r['fecha_atencion']) ?></strong> · <?= e($r['odontologo_nombre']) ?> · <strong>Costo: S/ <?= e(number_format((float) $r['costo'], 2)) ?></strong></header>
    <p><strong>Diagnóstico:</strong> <?= e($r['diagnostico']) ?></p>
    <p><strong>Tratamiento:</strong> <?= e($r['tratamiento']) ?><?= $r['tratamiento_catalogo'] ? ' <span class="chip">catálogo: ' . e($r['tratamiento_catalogo']) . '</span>' : '' ?></p>
    <?php if ($r['medicamentos']): ?><p><strong>Medicamentos:</strong> <?= e($r['medicamentos']) ?></p><?php endif; ?>
    <?php if ($r['observaciones']): ?><p><strong>Observaciones:</strong> <?= e($r['observaciones']) ?></p><?php endif; ?>
</article>
<?php endforeach; ?>

<?php if (in_array($u['rol'], ['admin', 'odontologo'], true)): ?>
<h2>Registrar una atención nueva</h2>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=historias&a=registrar" class="formulario ancho">
    <?= campoCsrf() ?>
    <input type="hidden" name="paciente_id" value="<?= (int) $paciente['id'] ?>">
    <label>Cita atendida (opcional, para enlazarla)
        <select name="cita_id">
            <option value="0">— Sin cita enlazada —</option>
            <?php foreach ($citasAtendidas as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= e($c['fecha']) ?> <?= e(substr($c['hora'], 0, 5)) ?> — <?= e($c['motivo'] ?? '') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($u['rol'] === 'admin'): ?>
    <label>Odontólogo que atendió (ID de usuario)
        <input type="number" name="odontologo_id" required min="1" placeholder="Ej. 2">
    </label>
    <?php endif; ?>
    <label>Diagnóstico *
        <textarea name="diagnostico" rows="2" required placeholder="Ej. Caries profunda en pieza 36"></textarea>
    </label>
    <label>Tratamiento del catálogo (opcional — sugiere el costo)
        <select name="tratamiento_id" id="tratamiento_id">
            <option value="0">— Ninguno —</option>
            <?php foreach ($tratamientos as $t): if ((int) $t['estado'] !== 1) continue; ?>
            <option value="<?= (int) $t['id'] ?>" data-precio="<?= e(number_format((float) $t['precio'] * (1 - (float) $t['descuento_pct'] / 100), 2, '.', '')) ?>" data-nombre="<?= e($t['nombre']) ?>"><?= e($t['nombre']) ?> — S/ <?= e(number_format((float) $t['precio'], 2)) ?><?= (float) $t['descuento_pct'] > 0 ? ' (−' . e($t['descuento_pct']) . '%)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Tratamiento indicado *
        <input type="text" name="tratamiento" id="tratamiento_texto" required placeholder="Ej. Curación con resina en pieza 36">
    </label>
    <label>Medicamentos
        <input type="text" name="medicamentos" placeholder="Ej. Amoxicilina 500 mg por 7 días">
    </label>
    <label>Observaciones
        <textarea name="observaciones" rows="2"></textarea>
    </label>
    <label>Costo de la atención (S/)
        <input type="number" name="costo" id="costo" step="0.01" min="0" value="0">
    </label>
    <button type="submit" class="boton grande">Registrar atención (POST) → queda en esta historia</button>
</form>
<script>
// Al elegir un tratamiento del catálogo, sugiere su nombre y su
// precio YA con el descuento aplicado (el odontólogo puede ajustar).
document.getElementById('tratamiento_id').addEventListener('change', function () {
    var op = this.options[this.selectedIndex];
    if (op.dataset.precio) {
        document.getElementById('costo').value = op.dataset.precio;
        document.getElementById('tratamiento_texto').value = op.dataset.nombre;
    }
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
