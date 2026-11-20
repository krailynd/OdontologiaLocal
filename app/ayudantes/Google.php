<?php
/*
 * ARCHIVO: app/ayudantes/Google.php
 * QUÉ ES: "Iniciar sesión con Google" (OAuth 2.0 + OpenID Connect)
 * en PHP PURO, sin Composer y sin la librería oficial de Google.
 *
 * ESTO SÍ USA GOOGLE CLOUD (a diferencia de Google Authenticator):
 * necesitas GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET de
 * console.cloud.google.com pegados en config/config.php.
 * La guía paso a paso está en la documentación PDF, capítulo Google.
 *
 * FLUJO (así actúa, en orden):
 *  1. urlLogin() arma el enlace a accounts.google.com con tu
 *     client_id, la URL de retorno (redirect_uri) y un "state"
 *     aleatorio guardado en sesión (anti-falsificación).
 *  2. Google muestra SU pantalla: el usuario elige su cuenta y
 *     acepta. La contraseña NUNCA pasa por Odontologia Local.
 *  3. Google regresa el navegador a googleCallback con ?code=...
 *  4. intercambiarCodigo() canjea ese código (desde el servidor,
 *     con tu client_secret) por tokens en oauth2.googleapis.com.
 *  5. datosUsuario() lee el perfil verificado (sub = google_id,
 *     correo, nombre) desde el endpoint userinfo.
 *  6. El AuthControlador busca ese correo/google_id en la tabla
 *     usuarios: si no existe, se AUTO-REGISTRA como paciente
 *     verificado (con su avatar), como se pidió en la mejora;
 *     si existe, se vincula su google_id y se actualiza su foto.
 *     Después pasa por el mismo MFA que el login normal.
 *
 *  OJO CON EL ERROR redirect_uri_mismatch: la URL de retorno se
 *  arma con la dirección REAL desde la que abres el sistema
 *  (urlRetorno()). Esa cadena exacta — con su puerto y su carpeta —
 *  debe estar registrada en Google Cloud -> tu cliente OAuth ->
 *  «URIs de redireccionamiento autorizados». Google compara
 *  carácter por carácter: si falta o difiere en algo, bloquea
 *  con Error 400 antes de dejar elegir la cuenta.
 */
class Google
{
    /* ¿Ya pegaron las credenciales en config.php? */
    public static function configurado()
    {
        return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
    }

    /* La URL de retorno EXACTA que debe registrarse en Google Cloud. */
    public static function urlRetorno()
    {
        return BASE_URL . 'index.php?c=auth&a=googleCallback';
    }

    /* Paso 1: enlace para mandar al usuario a la pantalla de Google. */
    public static function urlLogin()
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_state'] = $state;
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => self::urlRetorno(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /* Paso 4: canjea el código por tokens (llamada servidor a servidor). */
    public static function intercambiarCodigo($code)
    {
        $respuesta = self::postFormulario('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => self::urlRetorno(),
            'grant_type' => 'authorization_code',
        ]);
        return $respuesta; // trae access_token e id_token, o error
    }

    /* Paso 5: perfil verificado del usuario con su access_token. */
    public static function datosUsuario($accessToken)
    {
        $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 15,
        ]);
        $salida = curl_exec($ch);
        curl_close($ch);
        $datos = json_decode((string) $salida, true);
        return is_array($datos) ? $datos : null; // sub, email, name, email_verified
    }

    /* POST x-www-form-urlencoded con cURL (sin librerías externas). */
    private static function postFormulario($url, $campos)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
            CURLOPT_TIMEOUT => 15,
        ]);
        $salida = curl_exec($ch);
        curl_close($ch);
        $datos = json_decode((string) $salida, true);
        return is_array($datos) ? $datos : null;
    }
}
