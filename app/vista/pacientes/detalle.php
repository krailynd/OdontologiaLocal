<?php
/*
 * VISTA: pacientes/detalle.php — ficha del paciente con sus datos,
 * antecedentes y su historial de citas asociadas.
 * Variables: $paciente, $citas.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<h1>Ficha de paciente</h1>
<section class="tarjeta-detalle">
    <p><strong>Nombre:</strong> <?= e($paciente['nombre_completo'] ?? '(sin cuenta)') ?></p>
    <p><strong>DNI:</strong> <?= e($paciente['dni']) ?> · <strong>Correo:</strong> <?= e($paciente['correo'] ?? '') ?> · <strong>Teléfono:</strong> <?= e($paciente['telefono'] ?? '') ?></p>
    <p><strong>Fecha de nacimiento:</strong> <?= e($paciente['fecha_nacimiento'] ?? '') ?> · <strong>Dirección:</strong> <?= e($paciente['direccion'] ?? '') ?></p>
    <p><strong>Antecedentes médicos:</strong> <?= e($paciente['antecedentes'] ?? 'Sin registros.') ?></p>
    <p>
        <a class="boton" href="<?= e(BASE_URL) ?>index.php?c=historias&a=ver&paciente_id=<?= (int) $paciente['id'] ?>">Ver su historia clínica →</a>
        <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=citas&a=crear">Agendar una cita →</a>
    </p>
</section>
<h2>Historial de citas</h2>
<?php if (!$citas): ?><p class="ayuda">Este paciente aún no tiene citas.</p><?php endif; ?>
<ul class="lista-simple">
    <?php foreach ($citas as $c): ?>
    <li><strong><?= e($c['fecha']) ?> <?= e(substr($c['hora'], 0, 5)) ?></strong> — <?= e($c['odontologo_nombre']) ?> · <?= e($c['motivo'] ?? '') ?> <span class="chip estado-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></li>
    <?php endforeach; ?>
</ul>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
