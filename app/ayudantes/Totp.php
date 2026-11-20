<?php
/*
 * ARCHIVO: app/ayudantes/Totp.php
 * QUÉ ES: Google Authenticator en PHP PURO (algoritmo TOTP, RFC 6238).
 *
 * ACLARACIÓN CLAVE (lo que más se confunde):
 * Google Authenticator NO usa Google Cloud, NO pide Client ID ni
 * Client Secret y NO necesita internet. Funciona así:
 *   1. El sistema genera un SECRETO (texto Base32) por usuario.
 *   2. El usuario lo agrega a su app Google Authenticator
 *      (escaneando el QR o escribiendo el secreto a mano).
 *   3. La app y el sistema calculan el MISMO código de 6 dígitos
 *      cada 30 segundos, usando el secreto y la hora.
 * Por eso el secreto se guarda en usuarios.totp_secret y jamás se
 * comparte: quien tenga el secreto puede generar los códigos.
 */
class Totp
{
    /* Genera un secreto nuevo en Base32 (16 caracteres). */
    public static function generarSecreto()
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secreto = '';
        $bytes = random_bytes(16); // 16 caracteres Base32 = 80 bits de secreto
        foreach (str_split($bytes) as $b) {
            $secreto .= $alfabeto[ord($b) % 32];
        }
        return $secreto;
    }

    /* Decodifica Base32 a binario (para calcular el HMAC). */
    private static function base32Decodificar($secreto)
    {
        $alfabeto = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secreto = strtoupper(str_replace([' ', '-'], '', $secreto));
        $bits = '';
        foreach (str_split($secreto) as $c) {
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
    public static function codigoEn($secreto, $instante)
    {
        $contador = intdiv($instante, 30); // ventana de 30 segundos
        $mensaje = pack('J', $contador);   // contador en 8 bytes
        $hash = hash_hmac('sha1', $mensaje, self::base32Decodificar($secreto), true);
        $desplazamiento = ord($hash[19]) & 0x0f;
        $numero = ((ord($hash[$desplazamiento]) & 0x7f) << 24)
            | (ord($hash[$desplazamiento + 1]) << 16)
            | (ord($hash[$desplazamiento + 2]) << 8)
            | ord($hash[$desplazamiento + 3]);
        return str_pad((string) ($numero % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /*
     * Verifica un código aceptando la ventana anterior y la siguiente
     * (±30 s) por si el reloj del celular va unos segundos desfasado.
     */
    public static function verificar($secreto, $codigo)
    {
        $codigo = preg_replace('/\D/', '', (string) $codigo);
        if (strlen($codigo) !== 6) {
            return false;
        }
        $ahora = time();
        foreach ([-30, 0, 30] as $delta) {
            if (hash_equals(self::codigoEn($secreto, $ahora + $delta), $codigo)) {
                return true;
            }
        }
        return false;
    }

    /*
     * URI que se convierte en el código QR y que la app entiende:
     * otpauth://totp/Odontologia Local:correo?secret=XXXX&issuer=Odontologia Local
     */
    public static function uriQr($secreto, $correo)
    {
        return 'otpauth://totp/' . rawurlencode(APP_NOMBRE . ':' . $correo)
            . '?secret=' . $secreto
            . '&issuer=' . rawurlencode(APP_NOMBRE);
    }
}
