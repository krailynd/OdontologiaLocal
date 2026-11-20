<?php
/*
 * ARCHIVO: app/modelo/FacturaModelo.php  (MODELO)
 * QUÉ HACE: el SQL de facturación: cabecera (facturas) + líneas
 * (detalle_factura) dentro de UNA transacción: o se guarda la
 * factura completa o no se guarda nada.
 * El número es correlativo: F001-000001, F001-000002, ...
 */
class FacturaModelo
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    /* Lista facturas; si se pasa paciente, solo las de ese paciente. */
    public function listar($pacienteId = null)
    {
        $sql = 'SELECT f.*, u.nombre_completo AS paciente_nombre
                FROM facturas f
                JOIN pacientes p ON p.id = f.paciente_id
                LEFT JOIN usuarios u ON u.id = p.usuario_id';
        $params = [];
        if ($pacienteId !== null) {
            $sql .= ' WHERE f.paciente_id = ?';
            $params[] = $pacienteId;
        }
        $sql .= ' ORDER BY f.fecha_emision DESC';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /* Una factura por ID, con nombre del paciente. */
    public function porId($id)
    {
        $st = $this->db->prepare(
            'SELECT f.*, u.nombre_completo AS paciente_nombre, u.correo AS paciente_correo
             FROM facturas f
             JOIN pacientes p ON p.id = f.paciente_id
             LEFT JOIN usuarios u ON u.id = p.usuario_id
             WHERE f.id = ?'
        );
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /* Las líneas (detalle) de una factura. */
    public function detalle($facturaId)
    {
        $st = $this->db->prepare('SELECT * FROM detalle_factura WHERE factura_id = ? ORDER BY id');
        $st->execute([$facturaId]);
        return $st->fetchAll();
    }

    /*
     * Emite una factura completa en una TRANSACCIÓN.
     * $lineas = [ ['tratamiento_id'=>, 'descripcion'=>, 'cantidad'=>,
     *              'precio'=>, 'descuento_pct'=>], ... ]
     * Devuelve el ID de la factura creada.
     */
    public function emitir($pacienteId, $emitidoPor, $lineas)
    {
        $this->db->beginTransaction();
        try {
            // Número correlativo a partir del último emitido
            $ultimo = $this->db->query("SELECT COUNT(*) FROM facturas")->fetchColumn();
            $numero = 'F001-' . str_pad((string) ((int) $ultimo + 1), 6, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $descuentoTotal = 0;
            foreach ($lineas as $l) {
                $bruto = $l['precio'] * $l['cantidad'];
                $subtotal += $bruto;
                $descuentoTotal += $bruto * ($l['descuento_pct'] / 100);
            }
            $total = round($subtotal - $descuentoTotal, 2);

            $st = $this->db->prepare(
                'INSERT INTO facturas (numero, paciente_id, emitido_por, subtotal, descuento, total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $st->execute([$numero, $pacienteId, $emitidoPor, round($subtotal, 2), round($descuentoTotal, 2), $total]);
            $facturaId = $this->db->lastInsertId();

            $st = $this->db->prepare(
                'INSERT INTO detalle_factura
                    (factura_id, tratamiento_id, descripcion, cantidad, precio_unitario, descuento_pct, importe)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $l) {
                $importe = TratamientoModelo::importe($l['precio'], $l['cantidad'], $l['descuento_pct']);
                $st->execute([$facturaId, $l['tratamiento_id'] ?: null, $l['descripcion'], $l['cantidad'], $l['precio'], $l['descuento_pct'], $importe]);
            }
            $this->db->commit();
            return $facturaId;
        } catch (Exception $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    /* Marca una factura como pagada registrando el método de pago. */
    public function marcarPagada($id, $metodoPago)
    {
        $st = $this->db->prepare("UPDATE facturas SET estado = 'pagada', metodo_pago = ? WHERE id = ?");
        return $st->execute([$metodoPago, $id]);
    }

    /* Anula una factura (no se borra: queda el registro). */
    public function anular($id)
    {
        $st = $this->db->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?");
        return $st->execute([$id]);
    }

    /* Totales para el panel del administrador. */
    public function resumen()
    {
        $fila = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM usuarios WHERE estado = 1) AS usuarios_activos,
                (SELECT COUNT(*) FROM pacientes) AS pacientes,
                (SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()) AS citas_hoy,
                (SELECT COALESCE(SUM(total),0) FROM facturas WHERE estado = 'pagada') AS cobrado"
        )->fetch();
        return $fila;
    }
}
