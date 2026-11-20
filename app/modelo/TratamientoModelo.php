<?php
/*
 * ARCHIVO: app/modelo/TratamientoModelo.php  (MODELO)
 * QUÉ HACE: el SQL del catálogo de tratamientos con sus precios
 * y descuentos aplicables (requerimiento del PDF).
 */
class TratamientoModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /* Lista el catálogo completo. */
    public function listar()
    {
        return $this->db->query('SELECT * FROM tratamientos ORDER BY nombre')->fetchAll();
    }

    /* Un tratamiento por ID. */
    public function porId($id)
    {
        $st = $this->db->prepare('SELECT * FROM tratamientos WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* Crea un tratamiento del catálogo. */
    public function crear($nombre, $descripcion, $precio, $descuentoPct)
    {
        $st = $this->db->prepare(
            'INSERT INTO tratamientos (nombre, descripcion, precio, descuento_pct) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$nombre, $descripcion, $precio, $descuentoPct]);
        return $this->db->lastInsertId();
    }

    /* Edita un tratamiento del catálogo. */
    public function actualizar($id, $nombre, $descripcion, $precio, $descuentoPct)
    {
        $st = $this->db->prepare(
            'UPDATE tratamientos SET nombre = ?, descripcion = ?, precio = ?, descuento_pct = ? WHERE id = ?'
        );
        return $st->execute([$nombre, $descripcion, $precio, $descuentoPct, $id]);
    }

    /* Activa/desactiva un tratamiento sin borrarlo. */
    public function cambiarEstado($id, $estado)
    {
        $st = $this->db->prepare('UPDATE tratamientos SET estado = ? WHERE id = ?');
        return $st->execute([$estado, $id]);
    }

    /* Precio final de una línea: precio * cantidad * (1 - descuento). */
    public static function importe($precio, $cantidad, $descuentoPct)
    {
        return round($precio * $cantidad * (1 - $descuentoPct / 100), 2);
    }
}
