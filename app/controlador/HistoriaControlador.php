<?php
/*
 * ARCHIVO: app/controlador/HistoriaControlador.php  (CONTROLADOR)
 * QUÉ HACE: historia clínica y registros de atención.
 *  - ver: muestra la historia ÚNICA del paciente con TODOS sus
 *    registros. El paciente solo ve la suya; odontólogo y admin
 *    ven las de sus pacientes.
 *  - registrar (POST): el odontólogo agrega una atención
 *    (diagnóstico, tratamiento, medicamentos, observaciones, costo).
 *    Si elige un tratamiento del catálogo, el costo sugerido sale
 *    de su precio con el descuento aplicado (puede ajustarlo).
 */
class HistoriaControlador
{
    private $historias;
    private $pacientes;

    public function __construct()
    {
        $this->historias = new HistoriaModelo();
        $this->pacientes = new PacienteModelo();
    }

    /* GET ?c=historias&a=ver&paciente_id=N — historia + registros. */
    public function ver()
    {
        requiereLogin();
        $u = usuarioActual();
        if ($u['rol'] === 'paciente') {
            $p = $this->pacientes->porUsuarioId($u['id']);
            if ($p === null) {
                flash('error', 'Tu cuenta no tiene ficha de paciente.');
                redirigir('panel', 'index');
            }
            $pacienteId = $p['id'];
        } elseif ($u['rol'] === 'recepcionista') {
            flash('error', 'Recepción no accede a historias clínicas (datos médicos sensibles).');
            redirigir('panel', 'index');
        } else {
            $pacienteId = (int) ($_GET['paciente_id'] ?? 0);
        }
        $paciente = $this->pacientes->porId($pacienteId);
        if ($paciente === null) {
            flash('error', 'Ese paciente no existe.');
            redirigir('pacientes', 'index');
        }
        $historia = $this->historias->obtenerOCrear($pacienteId);
        $registros = $this->historias->registros($historia['id']);
        $tratamientos = (new TratamientoModelo())->listar();
        $citasAtendidas = array_filter((new CitaModelo())->listar($pacienteId), function ($c) {
            return $c['estado'] === 'atendida';
        });
        require __DIR__ . '/../vista/historias/ver.php';
    }

    /* POST ?c=historias&a=registrar — agrega una atención a la historia. */
    public function registrar()
    {
        requiereRol('admin', 'odontologo');
        verificarCsrf();
        $u = usuarioActual();
        $pacienteId = (int) ($_POST['paciente_id'] ?? 0);
        $diagnostico = trim($_POST['diagnostico'] ?? '');
        $tratamientoTexto = trim($_POST['tratamiento'] ?? '');
        if ($pacienteId === 0 || $diagnostico === '' || $tratamientoTexto === '') {
            flash('error', 'Diagnóstico y tratamiento son obligatorios.');
            redirigir('historias', 'ver', '&paciente_id=' . $pacienteId);
        }
        $historia = $this->historias->obtenerOCrear($pacienteId);

        // El odontólogo que atiende: si entró un odontólogo, es él;
        // si entró el admin, elige uno en el formulario.
        $odontologoId = $u['rol'] === 'odontologo' ? $u['id'] : (int) ($_POST['odontologo_id'] ?? 0);
        if ($odontologoId === 0) {
            flash('error', 'Elige el odontólogo que atendió.');
            redirigir('historias', 'ver', '&paciente_id=' . $pacienteId);
        }
        $this->historias->agregarRegistro(
            $historia['id'],
            (int) ($_POST['cita_id'] ?? 0),
            $odontologoId,
            $diagnostico,
            (int) ($_POST['tratamiento_id'] ?? 0),
            $tratamientoTexto,
            trim($_POST['medicamentos'] ?? ''),
            trim($_POST['observaciones'] ?? ''),
            (float) ($_POST['costo'] ?? 0)
        );
        flash('ok', 'Atención registrada en la historia clínica.');
        redirigir('historias', 'ver', '&paciente_id=' . $pacienteId);
    }
}
