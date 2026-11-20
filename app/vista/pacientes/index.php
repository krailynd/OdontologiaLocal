<?php
/*
 * VISTA: pacientes/index.php — lista y BUSCADOR de pacientes.
 * El buscador es un formulario GET (buscar es LEER, no cambia nada):
 * filtra por nombre, DNI o correo, como pide el caso de estudio.
 * Variables: $lista, $buscar.
 */
require __DIR__ . '/../parciales/cabecera.php';
$u = usuarioActual();

?>
<?php $infoQueHace = 'Lista y busca pacientes (ficha con DNI, contacto) y enlaza a su historia clínica única.'; $infoPeticion = 'GET ?c=pacientes&a=index&q= (solo lectura). Crear/editar son GET de formulario; guardar es POST.'; $infoNoHace = 'No crea cuentas de acceso por sí sola: la cuenta se crea desde el formulario de paciente marcando la opción o desde Usuarios.'; $infoArchivo = 'vista/pacientes/index.php + PacienteControlador::index() + PacienteModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>
<h1>Pacientes</h1>
<form method="get" action="<?= e(BASE_URL) ?>index.php" class="buscador">
    <input type="hidden" name="c" value="pacientes">
    <input type="hidden" name="a" value="index">
    <input type="search" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, DNI o correo…">
    <button type="submit" class="boton">Buscar</button>
    <?php if ($buscar !== ''): ?><a class="boton secundario" href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=index">Limpiar</a><?php endif; ?>
</form>
<?php if (in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=crear">+ Registrar paciente</a></p>
<?php endif; ?>
<table class="tabla">
    <thead><tr><th>Nombre</th><th>DNI</th><th>Correo</th><th>Teléfono</th><th>Nacimiento</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $p): ?>
        <tr>
            <td><?= e($p['nombre_completo'] ?? '(sin cuenta)') ?></td>
            <td><?= e($p['dni']) ?></td>
            <td><?= e($p['correo'] ?? '') ?></td>
            <td><?= e($p['telefono'] ?? '') ?></td>
            <td><?= e($p['fecha_nacimiento'] ?? '') ?></td>
            <td class="acciones">
                <a class="boton chico secundario" href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=ver&id=<?= (int) $p['id'] ?>">Ver ficha</a>
                <a class="boton chico secundario" href="<?= e(BASE_URL) ?>index.php?c=historias&a=ver&paciente_id=<?= (int) $p['id'] ?>">Historia</a>
                <?php if (in_array($u['rol'], ['admin', 'recepcionista'], true)): ?>
                <a class="boton chico" href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=editar&id=<?= (int) $p['id'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="6">Sin resultados para esa búsqueda.</td></tr><?php endif; ?>
    </tbody>
</table>
<div class="nota"><strong>Botones de esta pantalla:</strong> «Buscar» filtra la lista (GET, no modifica nada) · «Ver ficha» abre los datos y el historial de citas del paciente · «Historia» abre su historia clínica única · «Editar» abre el formulario (GET); guardar siempre es POST.</div>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
