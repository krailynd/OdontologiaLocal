<?php
/*
 * VISTA: usuarios/index.php — lista de usuarios (solo admin).
 * Activar/desactivar se hace con formularios POST pequeños por fila:
 * NUNCA con enlaces GET, porque GET no debe cambiar la base de datos.
 * Variable: $lista.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<?php $infoQueHace = 'Lista a todos los usuarios con su rol y estado; desde aquí el admin crea, edita y activa/desactiva cuentas.'; $infoPeticion = 'GET ?c=usuarios&a=index (solo lectura). Los botones llevan a crear/editar (GET) y las acciones guardar/estado son POST.'; $infoNoHace = 'No borra usuarios (solo desactiva) y no puede cambiar roles de cuentas Google por la web si así lo define el admin: los roles superiores se dan desde la base (infra/roles_google.sql).'; $infoArchivo = 'vista/usuarios/index.php + UsuarioControlador::index() + UsuarioModelo'; require __DIR__ . '/../parciales/info_pagina.php'; ?>

<h1>Usuarios del sistema</h1>
<p class="ayuda">CRUD completo de usuarios con asignación de roles. Los usuarios no se eliminan: se <strong>desactivan</strong> y su historial se conserva.</p>
<p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=usuarios&a=crear">+ Nuevo usuario</a></p>

<table class="tabla">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Teléfono</th><th>MFA</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $us): ?>
        <tr>
            <td><?= e($us['nombre_completo']) ?></td>
            <td><?= e($us['correo']) ?></td>
            <td><span class="chip"><?= e($us['rol']) ?></span></td>
            <td><?= e($us['telefono'] ?? '') ?></td>
            <td><?= !empty($us['two_factor_enabled']) ? 'Authenticator (2FA)' : 'Por correo' ?></td>
            <td><?= (int) $us['estado'] === 1 ? '✅ Activo' : '⛔ Desactivado' ?></td>
            <td class="acciones">
                <a class="boton chico secundario" href="<?= e(BASE_URL) ?>index.php?c=usuarios&a=editar&id=<?= (int) $us['id'] ?>">Editar</a>
                <form method="post" action="<?= e(BASE_URL) ?>index.php?c=usuarios&a=estado" class="en-linea">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="id" value="<?= (int) $us['id'] ?>">
                    <input type="hidden" name="estado" value="<?= (int) $us['estado'] === 1 ? 0 : 1 ?>">
                    <button type="submit" class="boton chico <?= (int) $us['estado'] === 1 ? 'peligro' : '' ?>"><?= (int) $us['estado'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="nota"><strong>Botones de esta pantalla:</strong> «Nuevo usuario» abre el formulario de alta (GET) · «Editar» abre el formulario con los datos (GET) · «Activar/Desactivar» cambia el estado enviando POST y vuelve a esta lista.</div>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
