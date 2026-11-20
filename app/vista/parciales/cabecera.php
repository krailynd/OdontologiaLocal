<?php
/*
 * VISTA PARCIAL: cabecera.php — se incluye al inicio de cada pantalla
 * con sesión. Trae el menú principal según el ROL: cada botón dice a
 * dónde redirige, y el de la pantalla actual queda marcado.
 * Una VISTA solo muestra datos: aquí no hay SQL ni reglas de negocio.
 */
$u = usuarioActual();
$cActual = $_GET['c'] ?? 'panel';
function activo($c, $cActual) { return $c === $cActual ? ' class="activo"' : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Clínica Odontológica</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/estilos.css">
</head>
<body>
<header class="barra">
    <div class="marca">🦷 Odontologia Local</div>
    <nav class="menu">
        <a href="<?= e(BASE_URL) ?>index.php?c=panel&a=index"<?= activo('panel', $cActual) ?>>Panel<small>Redirige a tu inicio según tu rol</small></a>
        <?php if (in_array($u['rol'], ['admin'], true)): ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=usuarios&a=index"<?= activo('usuarios', $cActual) ?>>Usuarios<small>Redirige al CRUD de usuarios y roles</small></a>
        <?php endif; ?>
        <?php if (in_array($u['rol'], ['admin', 'recepcionista', 'odontologo'], true)): ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=index"<?= activo('pacientes', $cActual) ?>>Pacientes<small>Redirige a la lista y búsqueda de pacientes</small></a>
        <?php endif; ?>
        <?php if ($u['rol'] === 'paciente'): ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=pacientes&a=ver"<?= activo('pacientes', $cActual) ?>>Mi ficha<small>Redirige a tus datos e historial de citas</small></a>
        <a href="<?= e(BASE_URL) ?>index.php?c=historias&a=ver"<?= activo('historias', $cActual) ?>>Mi historia<small>Redirige a tu historia clínica y atenciones</small></a>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=citas&a=index"<?= activo('citas', $cActual) ?>>Citas<small>Redirige a las citas según tu rol</small></a>
        <?php if (in_array($u['rol'], ['admin', 'recepcionista', 'odontologo'], true)): ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=tratamientos&a=index"<?= activo('tratamientos', $cActual) ?>>Tratamientos<small>Redirige al catálogo de precios</small></a>
        <?php endif; ?>
        <?php if (in_array($u['rol'], ['admin', 'recepcionista', 'paciente'], true)): ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=facturas&a=index"<?= activo('facturas', $cActual) ?>>Facturas<small>Redirige a los comprobantes</small></a>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>index.php?c=seguridad&a=index"<?= activo('seguridad', $cActual) ?>>Mi seguridad<small>Redirige a tu MFA, sesiones y contraseña</small></a>
        <a href="<?= e(BASE_URL) ?>index.php?c=menu&a=index"<?= activo('menu', $cActual) ?>>Mapa<small>Redirige al mapa de rutas GET y POST</small></a>
    </nav>
    <div class="sesion">
        <?php if (!empty($u['avatar'])): ?>
        <img src="<?= e($u['avatar']) ?>" alt="Foto de perfil de Google" class="avatar">
        <?php endif; ?>
        <span><?= e($u['nombre']) ?><small><?= e($u['rol']) ?></small></span>
        <form method="post" action="<?= e(BASE_URL) ?>index.php?c=auth&a=salir">
            <?= campoCsrf() ?>
            <button type="submit" class="boton salir">Salir</button>
        </form>
    </div>
</header>
<main class="contenedor">
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="flash <?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
<?php endif; ?>
