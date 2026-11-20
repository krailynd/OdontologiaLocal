<?php
/*
 * ARCHIVO: app/ayudantes/Correo.php
 * QUÉ HACE: es el ÚNICO lugar que envía correos del sistema:
 * código MFA, enlace de recuperación y avisos de citas.
 *
 * TRES CAMINOS, EN ESTE ORDEN (config/config.php manda):
 *  1. SMTP configurado (SMTP_HOST con datos): el correo SALE DE
 *     VERDAD al buzón del destinatario, sea el correo que sea,
 *     usando el cliente Smtp.php (PHP puro). El código/enlace ya
 *     NO se muestra en pantalla: mostrarlo sería regalarlo.
 *  2. MODO_DEV sin SMTP (práctica local): el correo se escribe en
 *     runtime/correos.log y la pantalla muestra el código/enlace
 *     para poder probar sin servidor de correo.
 *  3. Sin SMTP y sin MODO_DEV: se intenta mail() de PHP (solo
 *     funciona si el servidor tiene sendmail/SMTP en php.ini).
 * Además cada aviso de CITA queda registrado en la tabla
 * notificaciones, así el flujo queda demostrado aunque no haya SMTP.
 */
class Correo
{
    /*
     * Envía un correo por el camino que corresponda.
     * Devuelve true SOLO si salió por un canal real (SMTP o mail()).
     */
    public static function enviar($para, $asunto, $mensaje)
    {
        // Camino 1: SMTP real
        if (CORREO_REAL) {
            $ok = Smtp::enviar(
                SMTP_HOST,
                SMTP_PUERTO,
                SMTP_SEGURIDAD,
                SMTP_USUARIO,
                SMTP_CLAVE,
                SMTP_USUARIO,
                SMTP_DESDE_NOMBRE,
                $para,
                $asunto,
                $mensaje
            );
            // Registro SIN el cuerpo: en modo real el log no debe
            // guardar códigos ni enlaces (sería una copia insegura).
            $linea = "==== " . date('Y-m-d H:i:s') . " ====\n"
                . "PARA: $para\nASUNTO: $asunto\n[Vía SMTP: " . ($ok ? 'enviado' : 'ERROR: ' . Smtp::$ultimoError) . "]\n\n";
            @file_put_contents(__DIR__ . '/../../runtime/correos.log', $linea, FILE_APPEND);
            return $ok;
        }
        // Camino 3: mail() del servidor (sin SMTP configurado)
        $aceptado = false;
        if (!MODO_DEV) {
            $cabeceras = "Content-Type: text/plain; charset=UTF-8\r\n";
            $aceptado = @mail($para, $asunto, $mensaje, $cabeceras);
        }
        // Camino 2 (dev) y registro local completo para practicar
        $linea = "==== " . date('Y-m-d H:i:s') . " ====\n"
            . "PARA: $para\nASUNTO: $asunto\n$mensaje\n\n";
        @file_put_contents(__DIR__ . '/../../runtime/correos.log', $linea, FILE_APPEND);
        return $aceptado;
    }
}
