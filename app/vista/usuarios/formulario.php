<?php
/*
 * VISTA: usuarios/formulario.php — alta y edición de usuario.
 * Al crear se pide contraseña TEMPORAL: el usuario deberá cambiarla
 * en su primer ingreso. Si el rol es paciente, aparecen los campos
 * para crear también su ficha (DNI y fecha de nacimiento).
 * Variable: $usuario (null al crear).
 */
require __DIR__ . '/../parciales/cabecera.php';
$esEdicion = $usuario !== null;
?>
<h1><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=usuarios&a=guardar" class="formulario ancho">
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= $esEdicion ? (int) $usuario['id'] : 0 ?>">
    <label>Nombre completo
        <input type="text" name="nombre_completo" required value="<?= $esEdicion ? e($usuario['nombre_completo']) : '' ?>">
    </label>
    <label>Correo (será su usuario de ingreso)
        <input type="email" name="correo" required value="<?= $esEdicion ? e($usuario['correo']) : '' ?>">
    </label>
    <label>Rol
        <select name="rol" id="rol" required>
            <?php foreach (['admin', 'odontologo', 'recepcionista', 'paciente'] as $r): ?>
            <option value="<?= e($r) ?>" <?= $esEdicion && $usuario['rol'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Teléfono
        <input type="text" name="telefono" value="<?= $esEdicion ? e($usuario['telefono'] ?? '') : '' ?>">
    </label>
    <?php if (!$esEdicion): ?>
    <label>Contraseña temporal (mínimo 8 — el usuario la cambiará al primer ingreso)
        <input type="text" name="contrasena" required minlength="8" value="Temporal123">
    </label>
    <div id="campos-paciente">
        <label>DNI del paciente (solo si el rol es paciente)
            <input type="text" name="dni" maxlength="15">
        </label>
        <label>Fecha de nacimiento (solo paciente)
            <input type="date" name="fecha_nacimiento">
        </label>
    </div>
    <?php endif; ?>
    <button type="submit" class="boton grande">Guardar (POST) → vuelve a la lista</button>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=usuarios&a=index">Cancelar y volver</a>
</form>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
