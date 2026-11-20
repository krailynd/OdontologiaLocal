<?php
/*
 * ARCHIVO: app/controlador/FacturaControlador.php  (CONTROLADOR)
 * QUÉ HACE: facturación / comprobantes.
 *  - Recepción y admin EMITEN facturas desde el catálogo (el precio
 *    y el descuento salen del tratamiento elegido, no se escriben
 *    a mano: así el descuento aplicado es el del catálogo).
 *  - El paciente ve SOLO sus facturas (y sus pendientes en el panel).
 *  - Pagar y anular son POST; el detalle es GET de solo lectura.
 */
class FacturaControlador
{
    private $facturas;
    private $pacientes;
    private $tratamientos;

    public function __construct()
    {
        $this->facturas = new FacturaModelo();
        $this->pacientes = new PacienteModelo();
        $this->tratamientos = new TratamientoModelo();
    }

    /* GET ?c=facturas&a=index — lista según rol (paciente: solo suyas). */
    public function index()
    {
        requiereLogin();
        $u = usuarioActual();
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            $lista = $p ? $this->facturas->listar($p['id']) : [];
        } elseif ($u['rol'] === 'odontologo') {
            flash('error', 'El odontólogo no gestiona facturación.');
            redirigir('panel', 'index');
        } else {
            $lista = $this->facturas->listar();
        }
        require __DIR__ . '/../vista/facturas/index.php';
    }

    /* GET ?c=facturas&a=emitir — formulario: paciente + tratamientos. */
    public function emitir()
    {
        requiereRol('admin', 'recepcionista');
        $pacientes = $this->pacientes->listar();
        $tratamientos = array_filter($this->tratamientos->listar(), function ($t) {
            return (int) $t['estado'] === 1;
        });
        require __DIR__ . '/../vista/facturas/formulario.php';
    }

    /*
     * POST ?c=facturas&a=guardar — emite la factura.
     * El formulario manda hasta 3 líneas (tratamiento + cantidad);
     * las líneas vacías se ignoran. Precio y descuento se leen del
     * catálogo en el servidor (nunca se confía en el navegador).
     */
    public function guardar()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $pacienteId = (int) ($_POST['paciente_id'] ?? 0);
        $lineas = [];
        foreach ((array) ($_POST['tratamiento_id'] ?? []) as $i => $tratamientoId) {
            $tratamientoId = (int) $tratamientoId;
            $cantidad = (int) (($_POST['cantidad'][$i] ?? 0));
            if ($tratamientoId > 0 && $cantidad > 0) {
                $t = $this->tratamientos->porId($tratamientoId);
                if ($t) {
                    $lineas[] = [
                        'tratamiento_id' => $t['id'],
                        'descripcion' => $t['nombre'],
                        'cantidad' => $cantidad,
                        'precio' => (float) $t['precio'],
                        'descuento_pct' => (float) $t['descuento_pct'],
                    ];
                }
            }
        }
        if ($pacienteId === 0 || !$lineas) {
            flash('error', 'Elige el paciente y al menos un tratamiento con cantidad.');
            redirigir('facturas', 'emitir');
        }
        $id = $this->facturas->emitir($pacienteId, usuarioActual()['id'], $lineas);
        flash('ok', 'Factura emitida correctamente.');
        redirigir('facturas', 'ver', '&id=' . $id);
    }

    /* GET ?c=facturas&a=ver&id=N — detalle (el paciente solo la suya). */
    public function ver()
    {
        requiereLogin();
        $factura = $this->facturas->porId((int) ($_GET['id'] ?? 0));
        if ($factura === null) {
            flash('error', 'Esa factura no existe.');
            redirigir('facturas', 'index');
        }
        $u = usuarioActual();
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            if (!$p || (int) $factura['paciente_id'] !== (int) $p['id']) {
                flash('error', 'Solo puedes ver tus propias facturas.');
                redirigir('facturas', 'index');
            }
        }
        $detalle = $this->facturas->detalle($factura['id']);
        require __DIR__ . '/../vista/facturas/detalle.php';
    }

    /* POST ?c=facturas&a=pagar — registra el cobro (recepción/admin). */
    public function pagar()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $metodo = trim($_POST['metodo_pago'] ?? 'efectivo');
        $this->facturas->marcarPagada((int) ($_POST['id'] ?? 0), $metodo);
        flash('ok', 'Factura registrada como pagada.');
        redirigir('facturas', 'index');
    }

    /* POST ?c=facturas&a=anular — anula sin borrar el registro. */
    public function anular()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $this->facturas->anular((int) ($_POST['id'] ?? 0));
        flash('ok', 'Factura anulada.');
        redirigir('facturas', 'index');
    }
}
