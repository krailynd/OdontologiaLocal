<?php
/*
 * VISTA: pacientes/formulario.php — registro/edición de paciente.
 * Al registrar se puede, en el mismo paso:
 *  - vincular una cuenta de usuario paciente ya creada, o
 *  - crear la cuenta ahí mismo (nombre, correo, clave temporal).
 * Al guardar, el modelo crea también su historia clínica única.
 * Variables: $paciente (null al crear), $usuariosSinFicha.
 */
require __DIR__ . '/../parciales/cabecera.php';
$esEdicion = $paciente !== null;
?>
<h1><?= $esEdicion ? 'Editar paciente' : 'Registrar paciente' ?></h1>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=pacientes&a=guardar" class="formulario ancho">
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= $esEdicion ? (int) $paciente['id'] : 0 ?>">
    <label>DNI (único, no se puede repetir)
        <input type="text" name="dni" required maxlength="15" value="<?= $esEdicion ? e($paciente['dni']) : '' ?>">
    </label>
    <label>Fecha de nacimiento
        <input type="date" name="fecha_nacimiento" value="<?= $esEdicion ? e($paciente['fecha_nacimiento'] ?? '') : '' ?>">
    </label>
    <label>Dirección
        <input type="text" name="direccion" value="<?= $esEdicion ? e($paciente['direccion'] ?? '') : '' ?>">
    </label>
    <label>Antecedentes médicos
        <textarea name="antecedentes" rows="3" placeholder="Alergias, enfermedades, cirugías…"><?= $esEdicion ? e($paciente['antecedentes'] ?? '') : '' ?></textarea>
    </label>
    <?php if (!$esEdicion): ?>
    <fieldset>
        <legend>Cuenta de acceso del paciente (portal)</legend>
        <label>Vincular una cuenta ya creada
            <select name="usuario_id">
                <option value="0">— No vincular ahora / crear abajo —</option>
                <?php foreach ($usuariosSinFicha as $us): ?>
                <option value="<?= (int) $us['id'] ?>"><?= e($us['nombre_completo']) ?> (<?= e($us['correo']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="ayuda">…o crea su cuenta ahora (podrá entrar con MFA y ver su historia, citas y facturas):</p>
        <label>Nombre completo <input type="text" name="nombre_completo"></label>
        <label>Correo <input type="email" name="correo"></label>
        <label>Teléfono <input type="text" name="telefono"></label>
        <label>Contraseña temporal (8+) <input type="text" name="contrasena" value="Temporal123"></label>
    </fieldset>
    <?php endif; ?>
    <button type="submit" class="boton grande">Guardar (POST) → vuelve a la lista</button>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=index">Cancelar y volver</a>
</form>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
