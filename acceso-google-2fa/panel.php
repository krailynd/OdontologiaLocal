<?php
/*
 * ARCHIVO: acceso-google-2fa/panel.php
 * QUÉ HACE: la pantalla de llegada del módulo independiente cuando
 * la sesión ya está COMPLETA (pasó contraseña o Google, y el 2FA
 * si lo tenía activo). Muestra quién entró, su rol, su avatar de
 * Google si lo tiene y el estado de su 2FA, con el enlace para
 * configurarlo. Desde aquí también se entra al sistema principal.
 */
require __DIR__ . '/config.php';
requiereSesion();
$u = $_SESSION['usuario'];

$st = bd()->prepare('SELECT two_factor_enabled FROM usuarios WHERE id = ?');
$st->execute([$u['id']]);
$dosfa = (int) $st->fetchColumn() === 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Panel del módulo</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
<main class="contenedor" style="max-width:760px">
    <h1>Hola, <?= e($u['nombre']) ?> <span class="chip"><?= e($u['rol']) ?></span></h1>
    <?php mostrarFlash(); ?>
    <section class="tarjeta-detalle">
        <?php if (!empty($u['avatar'])): ?>
        <p><img src="<?= e($u['avatar']) ?>" alt="Foto de Google" class="avatar" style="width:56px;height:56px"></p>
        <?php endif; ?>
        <p><strong>Correo:</strong> <?= e($u['correo']) ?><br>
        <strong>2FA (Authenticator):</strong> <?= $dosfa ? '✅ ACTIVADO' : '⛔ No activado' ?></p>
        <p>
            <a class="boton" href="configurar-2fa.php"><?= $dosfa ? 'Administrar mi 2FA' : 'Activar mi 2FA con QR' ?> →</a>
            <a class="boton secundario" href="../index.php">Ir al sistema principal →</a>
        </p>
        <!-- FORMULARIO · Salir. POST a salir.php: destruye la sesión. -->
        <form method="post" action="salir.php">
            <?= campoCsrf() ?>
            <button type="submit" class="boton salir">Cerrar sesión</button>
        </form>
    </section>
</main>
</body>
</html>
