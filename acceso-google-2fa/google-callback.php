<?php
/*
 * ARCHIVO: acceso-google-2fa/google-callback.php
 * QUÉ HACE: recibe el regreso del navegador desde Google.
 * Es un GET (así funciona OAuth) con ?code=...&state=...
 *
 * PASOS, EN ORDEN:
 *  1. Valida el STATE contra el que se guardó en sesión al armar
 *     el botón: si no coincide, la respuesta no es de Google
 *     (posible falsificación) y se cancela todo.
 *  2. CANJEA el código: petición POST con cURL al Token URI
 *     (https://oauth2.googleapis.com/token) con code, client_id,
 *     client_secret, redirect_uri y grant_type=authorization_code.
 *     Google responde con access_token e id_token. El code es de
 *     UN SOLO USO y vence en minutos: por eso el canje es inmediato
 *     y desde el servidor (el secret nunca sale al navegador).
 *  3. PIDE EL PERFIL: GET al Userinfo con el access_token en la
 *     cabecera Authorization: Bearer. Google devuelve los datos
 *     VERIFICADOS: sub (= google_id), email, name y picture.
 *  4. BUSCA O REGISTRA: por google_id o por correo. Si no existe,
 *     AUTO-REGISTRO como paciente verificado (contraseña imposible:
 *     su llave es Google), guardando google_id y avatar.
 *  5. 2FA: si el usuario tiene two_factor_enabled = 1, queda
 *     pendiente y va a verificar-2fa.php; si no, sesión completa
 *     y al panel.
 */
require __DIR__ . '/config.php';

function fallar($mensaje)
{
    flash('error', $mensaje);
    header('Location: login.php');
    exit;
}

// Paso 0: Google también puede devolver ?error=... (usuario canceló)
if (isset($_GET['error']) || empty($_GET['code'])) {
    fallar('Google no completó el ingreso (se canceló o faltó el código).');
}

// Paso 1: state anti-CSRF
if (!hash_equals($_SESSION['google_state'] ?? '', (string) ($_GET['state'] ?? ''))) {
    fallar('La respuesta de Google no coincide con la solicitud (state inválido).');
}
unset($_SESSION['google_state']);

// Paso 2: canje del código por tokens (POST servidor a servidor)
$ch = curl_init(GOOGLE_TOKEN_URI);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ]),
    CURLOPT_TIMEOUT => 15,
]);
$tokens = json_decode((string) curl_exec($ch), true);
curl_close($ch);
if (!$tokens || empty($tokens['access_token'])) {
    fallar('Google rechazó el canje del código. Revisa el Client Secret y que la Redirect URI sea EXACTAMENTE la registrada.');
}

// Paso 3: perfil verificado con el access_token
$ch = curl_init(GOOGLE_USERINFO_URI);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokens['access_token']],
    CURLOPT_TIMEOUT => 15,
]);
$perfil = json_decode((string) curl_exec($ch), true);
curl_close($ch);
if (!$perfil || empty($perfil['sub']) || empty($perfil['email'])) {
    fallar('No se pudo leer el perfil verificado de Google.');
}

// Paso 4: buscar por google_id o por correo; si no está, registrar
$st = bd()->prepare('SELECT * FROM usuarios WHERE google_id = ? OR correo = ? LIMIT 1');
$st->execute([$perfil['sub'], $perfil['email']]);
$u = $st->fetch();

if ($u === false) {
    $hashImposible = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $st = bd()->prepare(
        "INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, google_id, avatar, debe_cambiar_contrasena)
         VALUES (?, ?, ?, 'paciente', ?, ?, 0)"
    );
    $st->execute([
        $perfil['name'] ?? $perfil['email'],
        $perfil['email'],
        $hashImposible,
        $perfil['sub'],
        $perfil['picture'] ?? null,
    ]);
    $st = bd()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $st->execute([bd()->lastInsertId()]);
    $u = $st->fetch();
    flash('aviso', 'Tu cuenta fue creada automáticamente con Google como PACIENTE verificado.');
} else {
    if (empty($u['google_id'])) {
        bd()->prepare('UPDATE usuarios SET google_id = ? WHERE id = ?')->execute([$perfil['sub'], $u['id']]);
    }
    if (!empty($perfil['picture']) && ($u['avatar'] ?? null) !== $perfil['picture']) {
        bd()->prepare('UPDATE usuarios SET avatar = ? WHERE id = ?')->execute([$perfil['picture'], $u['id']]);
        $u['avatar'] = $perfil['picture'];
    }
}

if ((int) $u['estado'] !== 1) {
    fallar('Tu cuenta está desactivada. Contacta al administrador.');
}

// Paso 5: ¿falta el segundo factor?
if (!empty($u['two_factor_enabled']) && !empty($u['totp_secret'])) {
    $_SESSION['pendiente_2fa'] = $u['id'];
    $_SESSION['metodo_pendiente'] = 'google';
    header('Location: verificar-2fa.php');
    exit;
}
crearSesionCompleta($u, 'google');
header('Location: panel.php');
exit;
