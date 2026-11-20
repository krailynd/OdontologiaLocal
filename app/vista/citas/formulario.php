<?php
/*
 * VISTA: citas/formulario.php — agendar una cita.
 * Si quien entra es PACIENTE, no elige paciente: la cita es para él
 * (el controlador toma su ficha de la sesión, no del formulario).
 * Las reglas (horario, doble reserva) las valida el MODELO al guardar.
 * Variables: $pacientes, $odontologos, $miPacienteId.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();
?>
<h1>Agendar cita</h1>
<div class="flash aviso">Reglas: Lun–Vie 08:00–19:00 · Sáb 08:00–13:00 · El odontólogo no puede tener dos citas a la misma hora.</div>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=citas&a=guardar" class="formulario ancho">
    <?= campoCsrf() ?>
    <?php if ($u['rol'] !== 'paciente'): ?>
    <label>Paciente
        <select name="paciente_id" required>
            <option value="">— Elige el paciente —</option>
            <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= e($p['nombre_completo'] ?? ('DNI ' . $p['dni'])) ?> · DNI <?= e($p['dni']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php else: ?>
    <p class="ayuda">La cita se agenda para ti (tu ficha sale de tu sesión, no se puede agendar para otra persona).</p>
    <?php endif; ?>
    <label>Odontólogo
        <select name="odontologo_id" required>
            <option value="">— Elige el odontólogo —</option>
            <?php foreach ($odontologos as $o): ?>
            <option value="<?= (int) $o['id'] ?>"><?= e($o['nombre_completo']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Fecha
        <input type="date" name="fecha" required min="<?= e(date('Y-m-d')) ?>">
    </label>
    <label>Hora (inicio de la cita)
        <input type="time" name="hora" required>
    </label>
    <label>Motivo
        <input type="text" name="motivo" placeholder="Ej. Dolor de muela, limpieza, control…">
    </label>
    <button type="submit" class="boton grande">Agendar (POST) → vuelve a la lista de citas</button>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=citas&a=index">Cancelar y volver</a>
</form>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
