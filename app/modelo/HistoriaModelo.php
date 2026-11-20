<?php
/*
 * ARCHIVO: app/modelo/HistoriaModelo.php  (MODELO)
 * QUÉ HACE: el SQL de la historia clínica ÚNICA por paciente y de
 * sus MÚLTIPLES registros de atención (dos tablas, una relación
 * 1 a N: historias_clinicas 1 -> N registros_atencion).
 */
class HistoriaModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /* La historia única de un paciente (o null si aún no tiene). */
    public function porPaciente($pacienteId)
    {
        $st = $this->db->prepare('SELECT * FROM historias_clinicas WHERE paciente_id = ?');
        $st->execute([$pacienteId]);
        return $st->fetch() ?: null;
    }

    /*
     * Devuelve la historia del paciente y, si no existe, la crea.
     * Como paciente_id es UNIQUE, jamás habrá dos historias suyas.
     */
    public function obtenerOCrear($pacienteId)
    {
        $historia = $this->porPaciente($pacienteId);
        if ($historia === null) {
            $st = $this->db->prepare(
                'INSERT INTO historias_clinicas (paciente_id, fecha_apertura) VALUES (?, CURDATE())'
            );
            $st->execute([$pacienteId]);
            $historia = $this->porPaciente($pacienteId);
        }
        return $historia;
    }

    /* Todos los registros de atención de una historia, del más nuevo al más antiguo. */
    public function registros($historiaId)
    {
        $st = $this->db->prepare(
            'SELECT r.*, u.nombre_completo AS odontologo_nombre, t.nombre AS tratamiento_catalogo
             FROM registros_atencion r
             JOIN usuarios u ON u.id = r.odontologo_id
             LEFT JOIN tratamientos t ON t.id = r.tratamiento_id
             WHERE r.historia_id = ?
             ORDER BY r.fecha_atencion DESC'
        );
        $st->execute([$historiaId]);
        return $st->fetchAll();
    }

    /*
     * Agrega UN registro de atención a la historia:
     * diagnóstico, tratamiento, medicamentos, observaciones y costo
     * (exactamente los campos que pide el caso de estudio).
     */
    public function agregarRegistro($historiaId, $citaId, $odontologoId, $diagnostico, $tratamientoId, $tratamientoTexto, $medicamentos, $observaciones, $costo)
    {
        $st = $this->db->prepare(
            'INSERT INTO registros_atencion
                (historia_id, cita_id, odontologo_id, diagnostico, tratamiento_id, tratamiento, medicamentos, observaciones, costo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $historiaId,
            $citaId ?: null,
            $odontologoId,
            $diagnostico,
            $tratamientoId ?: null,
            $tratamientoTexto,
            $medicamentos,
            $observaciones,
            $costo,
        ]);
        return $this->db->lastInsertId();
    }
}
