<?php
/* VISTA: tratamientos/formulario.php — alta/edición del catálogo. Variable: $tratamiento (null al crear). */
require __DIR__ . '/../parciales/cabecera.php';
$esEdicion = $tratamiento !== null;
?>
<h1><?= $esEdicion ? 'Editar tratamiento' : 'Nuevo tratamiento' ?></h1>
<form method="post" action="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=guardar" class="formulario ancho">
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= $esEdicion ? (int) $tratamiento['id'] : 0 ?>">
    <label>Nombre
        <input type="text" name="nombre" required value="<?= $esEdicion ? e($tratamiento['nombre']) : '' ?>">
    </label>
    <label>Descripción
        <textarea name="descripcion" rows="2"><?= $esEdicion ? e($tratamiento['descripcion'] ?? '') : '' ?></textarea>
    </label>
    <label>Precio (S/)
        <input type="number" name="precio" step="0.01" min="0" required value="<?= $esEdicion ? e($tratamiento['precio']) : '' ?>">
    </label>
    <label>Descuento aplicable (% de 0 a 100)
        <input type="number" name="descuento_pct" step="0.01" min="0" max="100" value="<?= $esEdicion ? e($tratamiento['descuento_pct']) : '0' ?>">
    </label>
    <button type="submit" class="boton grande">Guardar (POST) → vuelve al catálogo</button>
    <a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=index">Cancelar y volver</a>
</form>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
