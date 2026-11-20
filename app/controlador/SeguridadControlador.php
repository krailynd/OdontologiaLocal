<?php
/*
 * ARCHIVO: app/controlador/SeguridadControlador.php  (CONTROLADOR)
 * QUÉ HACE: la pantalla "Mi seguridad" de CADA usuario:
 *  - Activar Google Authenticator (TOTP): genera el secreto, lo
 *    muestra como QR + texto para agregarlo en la app, y recién lo
 *    guarda cuando el usuario confirma con un código válido.
 *  - Desactivar Authenticator (vuelve el MFA por correo).
 *  - Ver sus últimas sesiones registradas (fecha, IP, dispositivo).
 *  - Cambiar su contraseña (pidiendo la actual).
 *  - Respaldo (solo admin): explica y enlaza la exportación desde
 *    phpMyAdmin (un respaldo real es exportar la BD; el botón de
 *    aquí genera un .sql de las tablas principales con PHP).
 */
class SeguridadControlador
{
    private $usuarios;
    private $seguridad;

    public function __construct()
    {
        $this->usuarios = new UsuarioModelo();
        $this->seguridad = new SeguridadModelo();
    }

    /* GET ?c=seguridad&a=index — panel de seguridad del usuario. */
    public function index()
    {
        requiereLogin();
        // OJO: la fila completa va en $miFila (no en $u) porque la
        // cabecera de la vista usa $u para el usuario de la sesión.
        $miFila = $this->usuarios->porId(usuarioActual()['id']);
        $sesiones = $this->seguridad->sesionesDe($miFila['id']);
        // Secreto TOTP en preparación (aún no confirmado)
        $secretoPendiente = $_SESSION['totp_pendiente'] ?? null;
        $uriPendiente = $secretoPendiente ? Totp::uriQr($secretoPendiente, $miFila['correo']) : null;
        require __DIR__ . '/../vista/seguridad/index.php';
    }

    /*
     * POST ?c=seguridad&a=iniciarTotp — genera un secreto nuevo y lo
     * deja PENDIENTE en sesión: todavía no protege la cuenta hasta
     * que el usuario demuestre que lo agregó a su app (confirmar).
     */
    public function iniciarTotp()
    {
        requiereLogin();
        verificarCsrf();
        $_SESSION['totp_pendiente'] = Totp::generarSecreto();
        flash('aviso', 'Agrega este secreto en Google Authenticator y luego confírmalo con el código de 6 dígitos que te dé la app.');
        redirigir('seguridad', 'index');
    }

    /*
     * POST ?c=seguridad&a=confirmarTotp — si el código que calcula
     * la app del usuario coincide con el del sistema, el secreto se
     * guarda en usuarios.totp_secret y desde el próximo login el
     * segundo factor será Authenticator (ya no correo).
     */
    public function confirmarTotp()
    {
        requiereLogin();
        verificarCsrf();
        $secreto = $_SESSION['totp_pendiente'] ?? null;
        if ($secreto === null) {
            flash('error', 'Primero genera el secreto con el botón de activar.');
            redirigir('seguridad', 'index');
        }
        if (!Totp::verificar($secreto, $_POST['codigo'] ?? '')) {
            flash('error', 'Ese código no coincide. Revisa la hora de tu celular e intenta con el código actual de la app.');
            redirigir('seguridad', 'index');
        }
        $this->usuarios->guardarSecretoMfa(usuarioActual()['id'], $secreto);
        unset($_SESSION['totp_pendiente']);
        flash('ok', 'Google Authenticator activado. En tu próximo ingreso el código saldrá de la app.');
        redirigir('seguridad', 'index');
    }

    /* POST ?c=seguridad&a=quitarTotp — desactiva Authenticator. */
    public function quitarTotp()
    {
        requiereLogin();
        verificarCsrf();
        $this->usuarios->quitarSecretoMfa(usuarioActual()['id']);
        unset($_SESSION['totp_pendiente']);
        flash('ok', 'Google Authenticator desactivado. Tu MFA vuelve a ser el código por correo.');
        redirigir('seguridad', 'index');
    }

    /* POST ?c=seguridad&a=cambiarContrasena — cambio voluntario con la clave actual. */
    public function cambiarContrasena()
    {
        requiereLogin();
        verificarCsrf();
        $u = $this->usuarios->porId(usuarioActual()['id']);
        $actual = $_POST['contrasena_actual'] ?? '';
        $nueva = $_POST['contrasena_nueva'] ?? '';
        if (!password_verify($actual, $u['contrasena'])) {
            flash('error', 'Tu contraseña actual no es correcta.');
            redirigir('seguridad', 'index');
        }
        if (strlen($nueva) < 8) {
            flash('error', 'La contraseña nueva debe tener al menos 8 caracteres.');
            redirigir('seguridad', 'index');
        }
        $this->usuarios->guardarContrasena($u['id'], $nueva, true);
        flash('ok', 'Contraseña cambiada.');
        redirigir('seguridad', 'index');
    }

    /*
     * GET ?c=seguridad&a=respaldo — SOLO admin. Descarga un respaldo
     * .sql generado con PHP (SHOW CREATE + INSERT de cada tabla).
     * Es un GET que LEE la base y entrega un archivo: no modifica
     * nada. En producción el respaldo serio se programa con
     * mysqldump; esto sirve para la práctica y la exposición.
     */
    public function respaldo()
    {
        requiereRol('admin');
        $db = Conexion::obtener();
        $tablas = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $salida = "-- Respaldo Odontologia Local generado el " . date('Y-m-d H:i:s') . "\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tablas as $tabla) {
            $creacion = $db->query("SHOW CREATE TABLE `$tabla`")->fetch();
            $salida .= "DROP TABLE IF EXISTS `$tabla`;\n" . $creacion['Create Table'] . ";\n\n";
            $filas = $db->query("SELECT * FROM `$tabla`")->fetchAll();
            foreach ($filas as $f) {
                $valores = array_map(function ($v) use ($db) {
                    return $v === null ? 'NULL' : $db->quote((string) $v);
                }, array_values($f));
                $salida .= "INSERT INTO `$tabla` VALUES (" . implode(',', $valores) . ");\n";
            }
            $salida .= "\n";
        }
        $salida .= "SET FOREIGN_KEY_CHECKS=1;\n";
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="respaldo_odontologia_local_' . date('Ymd_His') . '.sql"');
        echo $salida;
        exit;
    }
}
