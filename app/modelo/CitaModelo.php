<?php
/*
 * ARCHIVO: app/modelo/CitaModelo.php  (MODELO)
 * QUÉ HACE: TODO el SQL de citas Y las REGLAS DE NEGOCIO del PDF.
 * Las reglas viven AQUÍ (en el modelo), no en la pantalla: aunque
 * alguien manipule el formulario, el sistema igual las cumple.
 *
 * REGLAS (del caso de estudio):
 *  R1. Horario de atención: Lun–Vie 08:00–19:00, Sáb 08:00–13:00.
 *      Domingo no se atiende.
 *  R2. Un odontólogo NO puede tener dos citas a la misma hora
 *      (misma fecha y hora, salvo citas canceladas).
 *  R3. Una cita solo se cancela con AL MENOS 2 horas de anticipación.
 *  R4. Estados: pendiente -> confirmada -> atendida / cancelada.
 */
class CitaModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /*
     * Lista citas con nombres legibles. Filtros opcionales por rol:
     * el paciente ve solo las suyas; el odontólogo, solo las suyas;
     * recepción y admin ven todas.
     */
    public function listar($filtroPacienteId = null, $filtroOdontologoId = null)
    {
        $sql = 'SELECT c.*,
                       up.nombre_completo AS paciente_nombre,
                       uo.nombre_completo AS odontologo_nombre
                FROM citas c
                JOIN pacientes p ON p.id = c.paciente_id
                LEFT JOIN usuarios up ON up.id = p.usuario_id
                JOIN usuarios uo ON uo.id = c.odontologo_id';
        $cond = [];
        $params = [];
        if ($filtroPacienteId !== null) {
            $cond[] = 'c.paciente_id = ?';
            $params[] = $filtroPacienteId;
        }
        if ($filtroOdontologoId !== null) {
            $cond[] = 'c.odontologo_id = ?';
            $params[] = $filtroOdontologoId;
        }
        if ($cond) {
            $sql .= ' WHERE ' . implode(' AND ', $cond);
        }
        $sql .= ' ORDER BY c.fecha DESC, c.hora';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /* Una cita por ID, con nombres. */
    public function porId($id)
    {
        $st = $this->db->prepare(
            'SELECT c.*, up.nombre_completo AS paciente_nombre, up.correo AS paciente_correo,
                    uo.nombre_completo AS odontologo_nombre
             FROM citas c
             JOIN pacientes p ON p.id = c.paciente_id
             LEFT JOIN usuarios up ON up.id = p.usuario_id
             JOIN usuarios uo ON uo.id = c.odontologo_id
             WHERE c.id = ?'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* Crea la cita (las reglas se validan ANTES, en validarReglas). */
    public function crear($pacienteId, $odontologoId, $fecha, $hora, $motivo)
    {
        $st = $this->db->prepare(
            'INSERT INTO citas (paciente_id, odontologo_id, fecha, hora, motivo)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$pacienteId, $odontologoId, $fecha, $hora, $motivo]);
        return $this->db->lastInsertId();
    }

    /* Cambia el estado de una cita (confirmar / atender). */
    public function cambiarEstado($id, $estado)
    {
        $st = $this->db->prepare('UPDATE citas SET estado = ? WHERE id = ?');
        return $st->execute([$estado, $id]);
    }

    /* Cancela guardando el motivo de cancelación. */
    public function cancelar($id, $observacion)
    {
        $st = $this->db->prepare(
            "UPDATE citas SET estado = 'cancelada', observaciones_cancelacion = ? WHERE id = ?"
        );
        return $st->execute([$observacion, $id]);
    }

    // ------------------- REGLAS DE NEGOCIO -------------------

    /*
     * Valida R1 y R2 antes de agendar. Devuelve '' si todo está bien
     * o el mensaje de error exacto para mostrar al usuario.
     * $excluirCitaId permite reprogramar sin chocar consigo misma.
     */
    public function validarReglas($odontologoId, $fecha, $hora, $excluirCitaId = 0)
    {
        // --- R1: horario de atención según el día de la semana ---
        $diaSemana = (int) date('N', strtotime($fecha)); // 1=Lun ... 7=Dom
        if ($diaSemana === 7) {
            return 'Los domingos la clínica no atiende. Elige de lunes a sábado.';
        }
        $horaTexto = substr($hora, 0, 5);
        $apertura = '08:00';
        $cierre = ($diaSemana === 6) ? '13:00' : '19:00'; // sábado cierra 13:00
        if ($horaTexto < $apertura || $horaTexto >= $cierre) {
            return "Fuera del horario de atención: ese día se atiende de $apertura a $cierre.";
        }

        // --- La cita no puede ser en el pasado ---
        if (strtotime($fecha . ' ' . $hora) < time()) {
            return 'La fecha y hora de la cita ya pasaron.';
        }

        // --- R2: el odontólogo no puede tener otra cita a esa hora ---
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM citas
             WHERE odontologo_id = ? AND fecha = ? AND hora = ?
               AND estado <> 'cancelada' AND id <> ?"
        );
        $st->execute([$odontologoId, $fecha, $hora, $excluirCitaId]);
        if ((int) $st->fetchColumn() > 0) {
            return 'El odontólogo ya tiene una cita a esa misma fecha y hora. Elige otro horario.';
        }
        return '';
    }

    /*
     * R3: ¿esta cita aún se puede cancelar?
     * Solo si faltan 2 horas o más para su fecha+hora y no está
     * ya atendida ni cancelada.
     */
    public function puedeCancelar($cita)
    {
        if (in_array($cita['estado'], ['atendida', 'cancelada'], true)) {
            return false;
        }
        $instanteCita = strtotime($cita['fecha'] . ' ' . $cita['hora']);
        return ($instanteCita - time()) >= 2 * 3600;
    }
}
