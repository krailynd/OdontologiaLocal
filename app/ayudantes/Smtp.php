<?php
/*
 * ARCHIVO: app/ayudantes/Smtp.php
 * QUÉ ES: un cliente SMTP mínimo en PHP PURO (sin PHPMailer, sin
 * Composer): habla el protocolo SMTP con sockets nativos para
 * enviar correo REAL a cualquier servidor (Gmail, Outlook, el de
 * un hosting...). Lo usa Correo.php cuando config.php trae los
 * datos SMTP; si no, el sistema sigue en modo práctica.
 *
 * LA CONVERSACIÓN SMTP QUE HABLA (en orden):
 *   conectar -> saludo 220 -> EHLO -> (STARTTLS si puerto 587)
 *   -> AUTH LOGIN (usuario y clave en Base64) -> MAIL FROM
 *   -> RCPT TO -> DATA -> cabeceras + cuerpo -> "." -> QUIT
 * Cada paso verifica el código de respuesta del servidor; si algo
 * no calza, devuelve false y el error queda en self::$ultimoError.
 *
 * QUÉ FALTA PARA QUE EL CORREO SALGA DE VERDAD (lo que pedía Elvis):
 * un servidor SMTP con credenciales. Ejemplo con Gmail:
 *   host smtp.gmail.com, puerto 587, seguridad 'tls',
 *   usuario = tu Gmail, clave = una CONTRASEÑA DE APLICACIÓN de
 *   Google (no tu contraseña normal: se crea en tu cuenta Google ->
 *   Seguridad -> Verificación en 2 pasos -> Contraseñas de
 *   aplicaciones). Esos 4 datos van en config/config.php.
 */
class Smtp
{
    public static $ultimoError = '';

    private static function leer($socket)
    {
        $respuesta = '';
        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            // Las respuestas multilínea traen "-" tras el código;
            // la última línea trae " " y ahí termina.
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $respuesta;
    }

    private static function esperar($socket, $codigos, $paso)
    {
        $r = self::leer($socket);
        $codigo = (int) substr($r, 0, 3);
        if (!in_array($codigo, $codigos, true)) {
            self::$ultimoError = "Paso $paso: el servidor respondió $codigo ($r)";
            return false;
        }
        return true;
    }

    private static function decir($socket, $texto)
    {
        fwrite($socket, $texto . "\r\n");
    }

    /*
     * Envía un correo de texto plano. $seguridad: 'tls' (STARTTLS,
     * puerto típico 587), 'ssl' (TLS desde el inicio, puerto 465)
     * o '' (sin cifrado, solo servidores locales de práctica).
     */
    public static function enviar($host, $puerto, $seguridad, $usuario, $clave, $desde, $desdeNombre, $para, $asunto, $mensaje)
    {
        self::$ultimoError = '';
        $remoto = ($seguridad === 'ssl' ? 'ssl://' : '') . $host;
        $socket = @stream_socket_client($remoto . ':' . $puerto, $errno, $errstr, 15);
        if ($socket === false) {
            self::$ultimoError = "No se pudo conectar a $host:$puerto ($errstr)";
            return false;
        }
        stream_set_timeout($socket, 15);
        $ok = self::esperar($socket, [220], 'saludo');
        if ($ok) {
            self::decir($socket, 'EHLO odontologialocal');
            $ok = self::esperar($socket, [250], 'EHLO');
        }
        if ($ok && $seguridad === 'tls') {
            self::decir($socket, 'STARTTLS');
            $ok = self::esperar($socket, [220], 'STARTTLS');
            if ($ok && !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                self::$ultimoError = 'No se pudo activar el cifrado TLS.';
                $ok = false;
            }
            if ($ok) {
                self::decir($socket, 'EHLO odontologialocal');
                $ok = self::esperar($socket, [250], 'EHLO tras TLS');
            }
        }
        if ($ok && $usuario !== '') {
            self::decir($socket, 'AUTH LOGIN');
            $ok = self::esperar($socket, [334], 'AUTH');
            if ($ok) {
                self::decir($socket, base64_encode($usuario));
                $ok = self::esperar($socket, [334], 'usuario');
            }
            if ($ok) {
                self::decir($socket, base64_encode($clave));
                $ok = self::esperar($socket, [235], 'clave (revisa la contraseña de aplicación)');
            }
        }
        if ($ok) {
            self::decir($socket, 'MAIL FROM:<' . $desde . '>');
            $ok = self::esperar($socket, [250], 'MAIL FROM');
        }
        if ($ok) {
            self::decir($socket, 'RCPT TO:<' . $para . '>');
            $ok = self::esperar($socket, [250, 251], 'RCPT TO');
        }
        if ($ok) {
            self::decir($socket, 'DATA');
            $ok = self::esperar($socket, [354], 'DATA');
        }
        if ($ok) {
            $asuntoSeguro = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
            $desdeSeguro = '=?UTF-8?B?' . base64_encode($desdeNombre) . '?= <' . $desde . '>';
            $cuerpo = str_replace(["\r\n", "\r"], "\n", $mensaje);
            $cuerpo = str_replace("\n", "\r\n", $cuerpo);
            $cuerpo = preg_replace('/^\./m', '..', $cuerpo); // dot-stuffing
            $correo = 'From: ' . $desdeSeguro . "\r\n"
                . 'To: <' . $para . ">\r\n"
                . 'Subject: ' . $asuntoSeguro . "\r\n"
                . 'Date: ' . date('r') . "\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "\r\n" . $cuerpo . "\r\n.";
            self::decir($socket, $correo);
            $ok = self::esperar($socket, [250], 'cuerpo del correo');
        }
        self::decir($socket, 'QUIT');
        fclose($socket);
        return $ok;
    }
}
