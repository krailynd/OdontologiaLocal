<?php /* VISTA PARCIAL: cabecera_simple.php — para pantallas SIN sesión (login, MFA, recuperación). */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Acceso</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/estilos.css">
</head>
<body class="fondo-acceso">
<main class="tarjeta-acceso">
    <div class="marca-centro">🦷 Odontologia Local</div>
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="flash <?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
<?php endif; ?>
