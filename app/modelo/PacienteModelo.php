<?php
/*
 * ARCHIVO: app/modelo/PacienteModelo.php  (MODELO)
 * QUÉ HACE: TODO el SQL de la tabla pacientes, unido a su usuario
 * (el nombre, el correo y el teléfono viven en usuarios; el DNI y
 * los datos médicos viven en pacientes: eso es normalización).
 */
class PacienteModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /*
     * Lista pacientes. Si $buscar viene con texto, filtra por
     * nombre, DNI o correo (requerimiento del PDF), con LIKE seguro
     * (parámetro preparado: lo que escriba el usuario no es SQL).
     */
    public function listar($buscar = '')
    {
        $sql = 'SELECT p.*, u.nombre_completo, u.correo, u.telefono, u.estado
                FROM pacientes p
                LEFT JOIN usuarios u ON u.id = p.usuario_id';
        $params = [];
        if (trim($buscar) !== '') {
            $sql .= ' WHERE u.nombre_completo LIKE ? OR p.dni LIKE ? OR u.correo LIKE ?';
            $like = '%' . trim($buscar) . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY u.nombre_completo';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /* Un paciente por su ID, con los datos de su usuario. */
    public function porId($id)
    {
        $st = $this->db->prepare(
            'SELECT p.*, u.nombre_completo, u.correo, u.telefono
             FROM pacientes p LEFT JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = ?'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* El paciente dueño de una cuenta de usuario (rol Paciente). */
    public function porUsuarioId($usuarioId)
    {
        $st = $this->db->prepare('SELECT * FROM pacientes WHERE usuario_id = ?');
        $st->execute([$usuarioId]);
        return $st->fetch() ?: null;
    }

    /* Crea la ficha de paciente (la cuenta de usuario se crea aparte). */
    public function crear($usuarioId, $dni, $fechaNacimiento, $direccion, $antecedentes)
    {
        $st = $this->db->prepare(
            'INSERT INTO pacientes (usuario_id, dni, fecha_nacimiento, direccion, antecedentes)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([
            $usuarioId ?: null,
            $dni,
            $fechaNacimiento ?: null,
            $direccion,
            $antecedentes,
        ]);
        $id = $this->db->lastInsertId();
        // Al nacer el paciente, nace su historia clínica ÚNICA (vacía):
        $st = $this->db->prepare(
            'INSERT INTO historias_clinicas (paciente_id, fecha_apertura) VALUES (?, CURDATE())'
        );
        $st->execute([$id]);
        return $id;
    }

    /* Edita la ficha de paciente. */
    public function actualizar($id, $dni, $fechaNacimiento, $direccion, $antecedentes)
    {
        $st = $this->db->prepare(
            'UPDATE pacientes SET dni = ?, fecha_nacimiento = ?, direccion = ?, antecedentes = ?
             WHERE id = ?'
        );
        return $st->execute([$dni, $fechaNacimiento ?: null, $direccion, $antecedentes, $id]);
    }

    /* ¿Ya existe otro paciente con ese DNI? (DNI es UNIQUE) */
    public function existeDni($dni, $exceptoId = 0)
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM pacientes WHERE dni = ? AND id <> ?');
        $st->execute([$dni, $exceptoId]);
        return (int) $st->fetchColumn() > 0;
    }
}
