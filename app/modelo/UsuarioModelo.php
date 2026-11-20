<?php
/*
 * ARCHIVO: app/modelo/UsuarioModelo.php  (MODELO)
 * QUÉ HACE: TODO el SQL de la tabla usuarios. Ninguna vista ni
 * controlador escribe SQL de usuarios: piden datos aquí.
 * Incluye la lógica de BLOQUEO tras 3 intentos fallidos.
 */
class UsuarioModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /* Busca un usuario por su correo (para login). */
    public function porCorreo($correo)
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE correo = ?');
        $st->execute([$correo]);
        return $st->fetch() ?: null;
    }

    /* Busca un usuario por su ID. */
    public function porId($id)
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* Busca un usuario por su google_id (login con Google). */
    public function porGoogleId($googleId)
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE google_id = ?');
        $st->execute([$googleId]);
        return $st->fetch() ?: null;
    }

    /* Lista todos los usuarios (gestión del Administrador). */
    public function listar()
    {
        return $this->db->query('SELECT * FROM usuarios ORDER BY rol, nombre_completo')->fetchAll();
    }

    /* Lista solo usuarios con un rol (ej. odontólogos para agendar). */
    public function listarPorRol($rol)
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE rol = ? AND estado = 1 ORDER BY nombre_completo');
        $st->execute([$rol]);
        return $st->fetchAll();
    }

    /*
     * Crea un usuario. La contraseña llega EN CLARO desde el
     * controlador y aquí se convierte en HASH: en la base de datos
     * jamás se guarda la contraseña legible.
     * debe_cambiar_contrasena = 1: al primer ingreso el sistema lo
     * obliga a cambiarla (requerimiento del PDF).
     */
    public function crear($nombre, $correo, $claveEnClaro, $rol, $telefono)
    {
        $hash = password_hash($claveEnClaro, PASSWORD_DEFAULT);
        $st = $this->db->prepare(
            'INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, telefono, debe_cambiar_contrasena)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $st->execute([$nombre, $correo, $hash, $rol, $telefono]);
        return $this->db->lastInsertId();
    }

    /* Edita datos y rol de un usuario (sin tocar su contraseña). */
    public function actualizar($id, $nombre, $correo, $rol, $telefono)
    {
        $st = $this->db->prepare(
            'UPDATE usuarios SET nombre_completo = ?, correo = ?, rol = ?, telefono = ? WHERE id = ?'
        );
        return $st->execute([$nombre, $correo, $rol, $telefono, $id]);
    }

    /*
     * Activa o desactiva SIN ELIMINAR (requerimiento del PDF):
     * estado 1 = activo, 0 = desactivado. El registro y su historial
     * se conservan; un usuario desactivado no puede iniciar sesión.
     */
    public function cambiarEstado($id, $estado)
    {
        $st = $this->db->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
        return $st->execute([$estado, $id]);
    }

    /* Guarda una nueva contraseña (ya convertida en hash aquí). */
    public function guardarContrasena($id, $claveEnClaro, $esPrimeraVez = false)
    {
        $hash = password_hash($claveEnClaro, PASSWORD_DEFAULT);
        $sql = 'UPDATE usuarios SET contrasena = ?'
            . ($esPrimeraVez ? ', debe_cambiar_contrasena = 0' : '')
            . ', intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?';
        $st = $this->db->prepare($sql);
        return $st->execute([$hash, $id]);
    }

    /* ¿La cuenta está bloqueada en este momento? */
    public function estaBloqueado($usuario)
    {
        return !empty($usuario['bloqueado_hasta'])
            && strtotime($usuario['bloqueado_hasta']) > time();
    }

    /*
     * Suma un intento fallido. Al llegar a 3, bloquea la cuenta
     * 15 minutos (bloqueado_hasta) y reinicia el contador.
     * Devuelve true si con este intento la cuenta quedó bloqueada.
     */
    public function registrarIntentoFallido($id)
    {
        $st = $this->db->prepare('UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?');
        $st->execute([$id]);
        $u = $this->porId($id);
        if ($u && (int) $u['intentos_fallidos'] >= 3) {
            $st2 = $this->db->prepare(
                "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?"
            );
            $st2->execute([$id]);
            return true;
        }
        return false;
    }

    /* Tras un login correcto, el contador de intentos vuelve a cero. */
    public function reiniciarIntentos($id)
    {
        $st = $this->db->prepare('UPDATE usuarios SET intentos_fallidos = 0 WHERE id = ?');
        return $st->execute([$id]);
    }

    /*
     * Guarda el secreto TOTP y marca two_factor_enabled = 1.
     * Solo se llama DESPUÉS de que el usuario confirmó un código
     * válido de su app: tener secreto sin confirmar no activa el 2FA.
     */
    public function guardarSecretoMfa($id, $secreto)
    {
        $st = $this->db->prepare('UPDATE usuarios SET totp_secret = ?, two_factor_enabled = 1 WHERE id = ?');
        return $st->execute([$secreto, $id]);
    }

    /* Quita el Authenticator del usuario (vuelve a MFA por correo). */
    public function quitarSecretoMfa($id)
    {
        $st = $this->db->prepare('UPDATE usuarios SET totp_secret = NULL, two_factor_enabled = 0 WHERE id = ?');
        return $st->execute([$id]);
    }

    /* Guarda la foto de perfil (avatar) que devuelve Google. */
    public function guardarAvatar($id, $avatar)
    {
        $st = $this->db->prepare('UPDATE usuarios SET avatar = ? WHERE id = ?');
        return $st->execute([$avatar, $id]);
    }

    /*
     * AUTO-REGISTRO desde Google (pedido en la mejora): si el correo
     * verificado por Google no existe, se crea la cuenta como
     * PACIENTE con su google_id y su avatar. La contraseña queda como
     * un hash de un texto aleatorio imposible de adivinar.
     *
     * FLUJO DEL USUARIO NUEVO (pedido de Elvis, solo para nuevos):
     * nace con debe_cambiar_contrasena = 1, así que tras verificar
     * el código que se le envía a su correo (el MFA de siempre), el
     * sistema lo obliga a CREAR su contraseña propia antes de entrar
     * al panel. Nace SIN ficha de paciente: recepción completa su
     * DNI y su historia desde el módulo Pacientes.
     */
    public function crearDesdeGoogle($nombre, $correo, $googleId, $avatar)
    {
        $hashImposible = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $st = $this->db->prepare(
            'INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, google_id, avatar, debe_cambiar_contrasena)
             VALUES (?, ?, ?, \'paciente\', ?, ?, 1)'
        );
        $st->execute([$nombre, $correo, $hashImposible, $googleId, $avatar]);
        return $this->db->lastInsertId();
    }

    /* Vincula la cuenta Google (google_id) a un usuario existente. */
    public function vincularGoogle($id, $googleId)
    {
        $st = $this->db->prepare('UPDATE usuarios SET google_id = ? WHERE id = ?');
        return $st->execute([$googleId, $id]);
    }
}
