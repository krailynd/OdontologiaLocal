<?php
/*
 * ARCHIVO: app/controlador/PanelControlador.php  (CONTROLADOR)
 * QUÉ HACE: la pantalla de inicio (panel) según el ROL:
 *  - admin: resumen general (usuarios, pacientes, citas de hoy, cobrado)
 *  - odontólogo: sus citas del día
 *  - recepcionista: citas del día de toda la clínica
 *  - paciente: sus próximas citas y sus facturas pendientes
 * Solo lee (GET): el panel nunca modifica la base de datos.
 */
class PanelControlador
{
    /* GET ?c=panel&a=index — arma el panel según el rol. */
    public function index()
    {
        requiereLogin();
        $u = usuarioActual();
        $resumen = null;
        $misCitas = [];
        $misFacturas = [];

        $citas = new CitaModelo();
        if ($u['rol'] === 'admin') {
            $resumen = (new FacturaModelo())->resumen();
        } elseif ($u['rol'] === 'odontologo') {
            $misCitas = array_filter($citas->listar(null, $u['id']), function ($c) {
                return $c['fecha'] >= date('Y-m-d') && $c['estado'] !== 'cancelada';
            });
        } elseif ($u['rol'] === 'recepcionista') {
            $misCitas = array_filter($citas->listar(), function ($c) {
                return $c['fecha'] === date('Y-m-d');
            });
        } elseif ($u['rol'] === 'paciente') {
            $pac = (new PacienteModelo())->porUsuarioId($u['id']);
            if ($pac) {
                $misCitas = array_filter($citas->listar($pac['id']), function ($c) {
                    return $c['fecha'] >= date('Y-m-d') && $c['estado'] !== 'cancelada';
                });
                $misFacturas = array_filter((new FacturaModelo())->listar($pac['id']), function ($f) {
                    return $f['estado'] === 'pendiente';
                });
            }
        }
        // Ventana de bienvenida SOLO para usuarios nuevos de Google:
        // la marca la dejó el callback al auto-registrar la cuenta.
        // Se muestra una sola vez (se borra aquí) y solo si aún no
        // activó su 2FA: recomienda hacerlo con el código QR.
        $mostrarBienvenida2fa = false;
        if (!empty($_SESSION['bienvenida_2fa'])) {
            unset($_SESSION['bienvenida_2fa']);
            $fila = (new UsuarioModelo())->porId($u['id']);
            $mostrarBienvenida2fa = $fila && empty($fila['two_factor_enabled']);
        }
        require __DIR__ . '/../vista/panel/index.php';
    }
}
