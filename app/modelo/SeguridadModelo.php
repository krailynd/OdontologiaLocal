<?php
/*
 * ARCHIVO: app/modelo/SeguridadModelo.php  (MODELO)
 * QUÉ HACE: el SQL de seguridad que no es de una sola tabla de
 * negocio: códigos MFA por correo, tokens de recuperación de
 * UN SOLO USO, registro de sesiones y notificaciones.
 */
class SeguridadModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    // ---------- Códigos MFA por correo (tabla codigos_mfa) ----------

    /*
     * Crea un código MFA de 6 dígitos válido 10 minutos.
     * En la tabla se guarda el HASH del código, y el código en claro
     * se devuelve SOLO para poder enviarlo por correo.
     * Antes de crear uno nuevo, marca como usados los anteriores:
     * solo el último código enviado sirve.
     */
    public function crearCodigoMfa($usuarioId)
    {
        $st = $this->db->prepare('UPDATE codigos_mfa SET usado = 1 WHERE usuario_id = ? AND usado = 0');
        $st->execute([$usuarioId]);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $st = $this->db->prepare(
            'INSERT INTO codigos_mfa (usuario_id, codigo, expiracion)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
        );
        $st->execute([$usuarioId, hash('sha256', $codigo)]);
        return $codigo;
    }

    /*
     * Verifica un código MFA: debe existir, NO estar usado y NO
     * haber expirado. Si es correcto lo marca usado = 1 en el acto:
     * el mismo código jamás sirve dos veces.
     */
    public function verificarCodigoMfa($usuarioId, $codigo)
    {
        $st = $this->db->prepare(
            'SELECT * FROM codigos_mfa
             WHERE usuario_id = ? AND codigo = ? AND usado = 0 AND expiracion > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$usuarioId, hash('sha256', trim((string) $codigo))]);
        $fila = $st->fetch();
        if (!$fila) {
            return false;
        }
        $st = $this->db->prepare('UPDATE codigos_mfa SET usado = 1 WHERE id = ?');
        $st->execute([$fila['id']]);
        return true;
    }

    // ---------- Recuperación de contraseña (tokens_recuperacion) ----------

    /*
     * Crea el token de recuperación. El enlace lleva el token en
     * claro UNA sola vez (por correo); en la base queda solo su hash.
     * Invalida los tokens anteriores del usuario y expira en 30 min.
     */
    public function crearTokenRecuperacion($usuarioId)
    {
        $st = $this->db->prepare('UPDATE tokens_recuperacion SET usado = 1 WHERE usuario_id = ? AND usado = 0');
        $st->execute([$usuarioId]);

        $token = bin2hex(random_bytes(32)); // 64 caracteres imposibles de adivinar
        $st = $this->db->prepare(
            'INSERT INTO tokens_recuperacion (usuario_id, token_hash, expiracion)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $st->execute([$usuarioId, hash('sha256', $token)]);
        return $token;
    }

    /* Busca un token VÁLIDO (sin usar y sin expirar) o devuelve null. */
    public function tokenValido($token)
    {
        $st = $this->db->prepare(
            'SELECT * FROM tokens_recuperacion
             WHERE token_hash = ? AND usado = 0 AND expiracion > NOW() LIMIT 1'
        );
        $st->execute([hash('sha256', (string) $token)]);
        return $st->fetch() ?: null;
    }

    /* Marca el token como usado: el enlace muere (UN SOLO USO). */
    public function marcarTokenUsado($tokenId)
    {
        $st = $this->db->prepare('UPDATE tokens_recuperacion SET usado = 1 WHERE id = ?');
        return $st->execute([$tokenId]);
    }

    // ---------- Registro de sesiones (tabla sesiones) ----------

    /* Registra cada inicio de sesión: fecha/hora, IP, dispositivo, método. */
    public function registrarSesion($usuarioId, $metodo)
    {
        $st = $this->db->prepare(
            'INSERT INTO sesiones (usuario_id, ip, dispositivo, metodo) VALUES (?, ?, ?, ?)'
        );
        return $st->execute([$usuarioId, ipCliente(), dispositivoCliente(), $metodo]);
    }

    /* Últimas sesiones de un usuario (pantalla "Mi seguridad"). */
    public function sesionesDe($usuarioId, $limite = 10)
    {
        $st = $this->db->prepare('SELECT * FROM sesiones WHERE usuario_id = ? ORDER BY fecha_hora DESC LIMIT ?');
        $st->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $st->bindValue(2, $limite, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    // ---------- Notificaciones (tabla notificaciones) ----------

    /* Registra un aviso enviado (citas): queda constancia en la BD. */
    public function registrarNotificacion($correo, $asunto, $mensaje, $tipo, $enviado)
    {
        $st = $this->db->prepare(
            'INSERT INTO notificaciones (destinatario_correo, asunto, mensaje, tipo, enviado)
             VALUES (?, ?, ?, ?, ?)'
        );
        return $st->execute([$correo, $asunto, $mensaje, $tipo, $enviado ? 1 : 0]);
    }
}
