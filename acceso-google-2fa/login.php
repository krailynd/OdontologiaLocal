<?php
/*
 * ARCHIVO: acceso-google-2fa/login.php
 * QUÉ HACE: la puerta del módulo independiente. Dos caminos:
 *
 *  A) CORREO + CONTRASEÑA (formulario POST a esta misma página):
 *     verifica el hash, el estado y el bloqueo por intentos; si el
 *     usuario tiene two_factor_enabled = 1, NO crea la sesión
 *     todavía: deja su id en $_SESSION['pendiente_2fa'] y lo manda
 *     a verificar-2fa.php. Si no tiene 2FA, crea la sesión completa.
 *
 *  B) BOTÓN "INICIAR SESIÓN CON GOOGLE" (enlace GET):
 *     arma la URL de autorización de Google con TODOS los
 *     parámetros obligatorios:
 *       client_id     -> tu ID de cliente de Google Cloud
 *       redirect_uri  -> google-callback.php (la registrada)
 *       response_type -> code  (OBLIGATORIO: sin esto, Google
 *                        responde Error 400 invalid_request)
 *       scope         -> openid email profile
 *       state         -> texto aleatorio guardado en sesión
 *                        (anti-CSRF: el callback lo compara)
 *       access_type   -> online (no pedimos refresh token)
 *     La contraseña de Google NUNCA pasa por esta página: el
 *     usuario la escribe en la pantalla oficial de Google.
 */
require __DIR__ . '/config.php';

// ---------- Camino A: envío del formulario (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['contrasena'] ?? '';

    $st = bd()->prepare('SELECT * FROM usuarios WHERE correo = ?');
    $st->execute([$correo]);
    $u = $st->fetch();

    if ($u === null || !password_verify($clave, $u['contrasena'])) {
        // Suma el intento fallido (al 3.º, bloqueo de 15 minutos),
        // con el mismo criterio del sistema principal.
        if ($u !== null) {
            bd()->prepare('UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?')
                ->execute([$u['id']]);
            $st2 = bd()->prepare('SELECT intentos_fallidos FROM usuarios WHERE id = ?');
            $st2->execute([$u['id']]);
            if ((int) $st2->fetchColumn() >= 3) {
                bd()->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?")
                    ->execute([$u['id']]);
                flash('error', 'Cuenta bloqueada por 3 intentos fallidos. Intenta en 15 minutos.');
                header('Location: login.php');
                exit;
            }
        }
        flash('error', 'Correo o contraseña incorrectos.');
        header('Location: login.php');
        exit;
    }
    if ((int) $u['estado'] !== 1) {
        flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
        header('Location: login.php');
        exit;
    }
    if (!empty($u['bloqueado_hasta']) && strtotime($u['bloqueado_hasta']) > time()) {
        flash('error', 'Tu cuenta está bloqueada hasta las ' . $u['bloqueado_hasta'] . '.');
        header('Location: login.php');
        exit;
    }
    bd()->prepare('UPDATE usuarios SET intentos_fallidos = 0 WHERE id = ?')->execute([$u['id']]);

    // ¿Tiene 2FA confirmado? Entonces falta el segundo factor.
    if (!empty($u['two_factor_enabled']) && !empty($u['totp_secret'])) {
        $_SESSION['pendiente_2fa'] = $u['id'];
        $_SESSION['metodo_pendiente'] = 'password';
        header('Location: verificar-2fa.php');
        exit;
    }
    crearSesionCompleta($u, 'password');
    header('Location: panel.php');
    exit;
}

// ---------- Camino B: la URL de Google (se arma al mostrar) ----------
$state = bin2hex(random_bytes(16));
$_SESSION['google_state'] = $state;
$urlGoogle = GOOGLE_AUTH_URI . '?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Odontologia Local — Acceso (módulo Google + 2FA)</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="fondo-acceso">
<main class="tarjeta-acceso">
    <div class="marca-centro">🦷 Odontologia Local</div>
    <h1>Acceso — módulo Google + 2FA</h1>
    <?php mostrarFlash(); ?>
    <!-- FORMULARIO 1 · Login con correo y contraseña.
         Petición: POST a login.php (esta misma página).
         Campos: correo, contrasena y csrf (oculto, anti-CSRF).
         Si el usuario tiene 2FA, la respuesta NO es el panel:
         es la redirección a verificar-2fa.php. -->
    <form method="post" action="login.php" class="formulario">
        <?= campoCsrf() ?>
        <label>Correo
            <input type="email" name="correo" required autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="contrasena" required autocomplete="current-password">
        </label>
        <button type="submit" class="boton grande">Entrar →</button>
    </form>

    <div class="separador"><span>o</span></div>

    <!-- BOTÓN GOOGLE · Es un enlace GET a la URL de autorización
         ya armada arriba con client_id, redirect_uri,
         response_type=code, scope, state y access_type.
         Al pulsarlo sales hacia accounts.google.com. -->
    <a class="boton google" href="<?= e($urlGoogle) ?>">G&nbsp; Iniciar sesión con Google</a>
    <p class="ayuda">Google te pedirá tu cuenta en SU pantalla. Si tu correo no existe en el sistema, se registra automáticamente como paciente verificado.</p>

    <details class="demo">
        <summary>Cuentas de prueba</summary>
        <ul>
            <li>Admin: <code>admin@odontologialocal.pe</code> / <code>Admin123*</code></li>
            <li>Recepción: <code>recepcion@odontologialocal.pe</code> / <code>Recep123*</code></li>
        </ul>
    </details>
</main>
</body>
</html>
