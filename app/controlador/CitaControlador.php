<?php
/*
 * ARCHIVO: app/controlador/CitaControlador.php  (CONTROLADOR)
 * QUÉ HACE: gestión de citas con las reglas del caso de estudio.
 * El controlador coordina (permisos, datos, avisos por correo) y el
 * MODELO valida las reglas: horario, doble reserva y cancelación
 * con 2 horas de anticipación.
 * Cada cambio de cita genera NOTIFICACIÓN por correo (queda en la
 * tabla notificaciones y en runtime/correos.log en modo dev).
 */
class CitaControlador
{
    private $citas;
    private $pacientes;
    private $usuarios;
    private $seguridad;

    public function __construct()
    {
        $this->citas = new CitaModelo();
        $this->pacientes = new PacienteModelo();
        $this->usuarios = new UsuarioModelo();
        $this->seguridad = new SeguridadModelo();
    }

    /*
     * GET ?c=citas&a=index — lista según el rol:
     * paciente = solo las suyas, odontólogo = solo las suyas,
     * recepción/admin = todas.
     */
    public function index()
    {
        requiereLogin();
        $u = usuarioActual();
        $lista = $this->listaSegunRol($u);
        require __DIR__ . '/../vista/citas/index.php';
    }

    /* GET ?c=citas&a=crear — formulario para agendar. */
    public function crear()
    {
        requiereLogin();
        $u = usuarioActual();
        $pacientes = $this->pacientes->listar();
        $odontologos = $this->usuarios->listarPorRol('odontologo');
        $miPacienteId = null;
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            $miPacienteId = $p ? $p['id'] : null;
        }
        require __DIR__ . '/../vista/citas/formulario.php';
    }

    /*
     * POST ?c=citas&a=guardar — agenda validando las reglas.
     * El paciente SOLO puede agendar para sí mismo: su paciente_id
     * se toma de la sesión, no del formulario.
     */
    public function guardar()
    {
        requiereLogin();
        verificarCsrf();
        $u = usuarioActual();

        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            if ($p === null) {
                flash('error', 'Tu cuenta no tiene ficha de paciente.');
                redirigir('panel', 'index');
            }
            $pacienteId = $p['id'];
        } else {
            $pacienteId = (int) ($_POST['paciente_id'] ?? 0);
        }
        $odontologoId = (int) ($_POST['odontologo_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');

        if ($pacienteId === 0 || $odontologoId === 0 || $fecha === '' || $hora === '') {
            flash('error', 'Paciente, odontólogo, fecha y hora son obligatorios.');
            redirigir('citas', 'crear');
        }
        // REGLAS DE NEGOCIO (viven en el modelo):
        $error = $this->citas->validarReglas($odontologoId, $fecha, $hora);
        if ($error !== '') {
            flash('error', $error);
            redirigir('citas', 'crear');
        }
        $id = $this->citas->crear($pacienteId, $odontologoId, $fecha, $hora, $motivo);
        $cita = $this->citas->porId($id);
        $this->notificar($cita, 'Cita registrada', 'Tu cita fue registrada en estado PENDIENTE.');
        flash('ok', 'Cita agendada en estado pendiente. Se registró la notificación por correo.');
        redirigir('citas', 'index');
    }

    /* POST ?c=citas&a=confirmar — pendiente -> confirmada (recepción/odontólogo/admin). */
    public function confirmar()
    {
        requiereRol('admin', 'recepcionista', 'odontologo');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $this->citas->cambiarEstado($id, 'confirmada');
        $this->notificar($this->citas->porId($id), 'Cita confirmada', 'Tu cita fue CONFIRMADA.');
        flash('ok', 'Cita confirmada.');
        redirigir('citas', 'index');
    }

    /* POST ?c=citas&a=atender — confirmada -> atendida (odontólogo/admin). */
    public function atender()
    {
        requiereRol('admin', 'odontologo');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $this->citas->cambiarEstado($id, 'atendida');
        flash('ok', 'Cita marcada como atendida. Ya puedes registrar la atención en la historia clínica.');
        redirigir('citas', 'index');
    }

    /*
     * POST ?c=citas&a=cancelar — cancela SOLO si faltan 2+ horas
     * (regla R3 del modelo) y guarda el motivo de cancelación.
     */
    public function cancelar()
    {
        requiereLogin();
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $cita = $this->citas->porId($id);
        if ($cita === null) {
            flash('error', 'Esa cita no existe.');
            redirigir('citas', 'index');
        }
        // El paciente solo cancela SUS citas
        $u = usuarioActual();
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            if (!$p || (int) $cita['paciente_id'] !== (int) $p['id']) {
                flash('error', 'Solo puedes cancelar tus propias citas.');
                redirigir('citas', 'index');
            }
        }
        if (!$this->citas->puedeCancelar($cita)) {
            flash('error', 'No se puede cancelar: faltan menos de 2 horas para la cita (o ya fue atendida/cancelada).');
            redirigir('citas', 'index');
        }
        $motivo = trim($_POST['observaciones_cancelacion'] ?? '');
        $this->citas->cancelar($id, $motivo);
        $this->notificar($cita, 'Cita cancelada', 'Tu cita fue CANCELADA. Motivo: ' . ($motivo ?: 'sin motivo registrado'));
        flash('ok', 'Cita cancelada y notificación registrada.');
        redirigir('citas', 'index');
    }

    /* Lista filtrada por rol (la usa index). */
    private function listaSegunRol($u)
    {
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            return $p ? $this->citas->listar($p['id']) : [];
        }
        if ($u['rol'] === 'odontologo') {
            return $this->citas->listar(null, $u['id']);
        }
        return $this->citas->listar();
    }

    /* Envía el aviso de la cita y lo deja registrado en notificaciones. */
    private function notificar($cita, $asuntoCorto, $texto)
    {
        if (!$cita || empty($cita['paciente_correo'])) {
            return;
        }
        $asunto = $asuntoCorto . ' — ' . APP_NOMBRE;
        $mensaje = "Hola {$cita['paciente_nombre']}:\n\n$texto\n"
            . "Fecha: {$cita['fecha']}  Hora: {$cita['hora']}\n"
            . "Odontólogo: {$cita['odontologo_nombre']}\n\n— " . APP_NOMBRE;
        $enviado = Correo::enviar($cita['paciente_correo'], $asunto, $mensaje);
        $this->seguridad->registrarNotificacion($cita['paciente_correo'], $asunto, $mensaje, 'cita', $enviado);
    }
}
