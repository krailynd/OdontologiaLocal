<?php
/*
 * ARCHIVO: acceso-google-2fa/TOTP.php
 * CLASE: TOTP — Autenticación de dos factores (2FA) en PHP PURO.
 *
 * Estándar RFC 6238 (TOTP) sobre HOTP (RFC 4226): códigos de
 * 6 dígitos que cambian cada 30 segundos, compatibles con
 * Google Authenticator, Authy y Microsoft Authenticator.
 * Sin librerías externas: solo hash_hmac() nativo de PHP.
 *
 * CÓMO FUNCIONA (en una frase): el servidor y la app comparten un
 * SECRETO Base32; ambos calculan el mismo código con el secreto y
 * la ventana de tiempo actual (time() / 30). El código nunca viaja
 * de un lado al otro: por eso funciona sin internet.
 *
 * MÉTODOS PEDIDOS:
 *   generateSecret()          -> crea el secreto Base32 (16 chars)
 *   getQRCodeUrl($secret, $email) -> la URI otpauth:// que codifica
 *                                el código QR y que la app entiende
 *   verifyCode($secret, $code)    -> verifica con tolerancia de
 *                                ±1 ventana (±30 s) por desfase de reloj
 */
class TOTP
{
    /* Genera un secreto Base32 aleatorio de 16 caracteres (80 bits). */
    public static function generateSecret()
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secreto = '';
        foreach (str_split(random_bytes(16)) as $b) {
            $secreto .= $alfabeto[ord($b) % 32];
        }
        return $secreto;
    }

    /*
     * La URI que se convierte en código QR:
     * otpauth://totp/Odontologia Local:correo?secret=XXXX&issuer=Odontologia Local
     * El frontend la dibuja como QR con qrcode.js (sin depender de
     * la extensión GD de PHP) y la misma clave sirve para el ingreso
     * manual en la app.
     */
    public static function getQRCodeUrl($secret, $email)
    {
        return 'otpauth://totp/' . rawurlencode(APP_NOMBRE . ':' . $email)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode(APP_NOMBRE);
    }

    /* El secreto partido en bloques de 4 para leerlo y copiarlo fácil. */
    public static function formatearSecreto($secret)
    {
        return implode(' ', str_split($secret, 4));
    }

    /* Decodifica Base32 a binario (para el HMAC). */
    private static function base32Decode($secret)
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(str_replace([' ', '-'], '', $secret));
        $bits = '';
        foreach (str_split($secret) as $c) {
            $pos = strpos($alfabeto, $c);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $binario = '';
        foreach (str_split($bits, 8) as $trozo) {
            if (strlen($trozo) === 8) {
                $binario .= chr(bindec($trozo));
            }
        }
        return $binario;
    }

    /* Calcula el código de 6 dígitos para un instante dado. */
    public static function codigoEn($secret, $instante)
    {
        $contador = intdiv($instante, 30);
        $hash = hash_hmac('sha1', pack('J', $contador), self::base32Decode($secret), true);
        $desplazamiento = ord($hash[19]) & 0x0f;
        $numero = ((ord($hash[$desplazamiento]) & 0x7f) << 24)
            | (ord($hash[$desplazamiento + 1]) << 16)
            | (ord($hash[$desplazamiento + 2]) << 8)
            | ord($hash[$desplazamiento + 3]);
        return str_pad((string) ($numero % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /*
     * Verifica un código aceptando la ventana anterior, la actual y
     * la siguiente (±30 segundos): tolerancia pedida para relojes
     * de celular ligeramente desfasados. Comparación con hash_equals
     * (tiempo constante, no filtra el código por tiempos de respuesta).
     */
    public static function verifyCode($secret, $code)
    {
        $code = preg_replace('/\D/', '', (string) $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $ahora = time();
        foreach ([-30, 0, 30] as $delta) {
            if (hash_equals(self::codigoEn($secret, $ahora + $delta), $code)) {
                return true;
            }
        }
        return false;
    }
}
