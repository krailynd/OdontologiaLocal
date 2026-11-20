<?php
/*
 * ARCHIVO: app/controlador/PacienteControlador.php  (CONTROLADOR)
 * QUÉ HACE: gestión de pacientes.
 *  Roles: admin y recepcionista registran/editan; el odontólogo
 *  consulta; el paciente SOLO ve su propia ficha e historial
 *  (su paciente_id sale de SU sesión, nunca de la URL: así no puede
 *  ver datos de otro paciente cambiando un número).
 */
class PacienteControlador
{
    private $pacientes;

    public function __construct()
    {
        $this->pacientes = new PacienteModelo();
    }

    /* GET ?c=pacientes&a=index&buscar=texto — lista con buscador. */
    public function index()
    {
        requiereRol('admin', 'recepcionista', 'odontologo');
        $buscar = $_GET['buscar'] ?? '';
        $lista = $this->pacientes->listar($buscar);
        require __DIR__ . '/../vista/pacientes/index.php';
    }

    /* GET ?c=pacientes&a=crear — formulario de paciente nuevo. */
    public function crear()
    {
        requiereRol('admin', 'recepcionista');
        $paciente = null;
        $usuariosSinFicha = $this->usuariosPacientesSinFicha();
        require __DIR__ . '/../vista/pacientes/formulario.php';
    }

    /* GET ?c=pacientes&a=editar&id=N — formulario con datos. */
    public function editar()
    {
        requiereRol('admin', 'recepcionista');
        $paciente = $this->pacientes->porId((int) ($_GET['id'] ?? 0));
        if ($paciente === null) {
            flash('error', 'Ese paciente no existe.');
            redirigir('pacientes', 'index');
        }
        $usuariosSinFicha = [];
        require __DIR__ . '/../vista/pacientes/formulario.php';
    }

    /* POST ?c=pacientes&a=guardar — crea o edita la ficha (solo POST cambia la BD). */
    public function guardar()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $dni = trim($_POST['dni'] ?? '');
        if ($dni === '' || $this->pacientes->existeDni($dni, $id)) {
            flash('error', 'El DNI es obligatorio y no puede repetirse.');
            redirigir('pacientes', $id ? 'editar' : 'crear', $id ? '&id=' . $id : '');
        }
        if ($id === 0) {
            // La ficha puede vincularse a una cuenta de usuario paciente
            $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
            // Y puede crear la cuenta en el mismo paso (nombre+correo+clave temporal)
            if ($usuarioId === 0 && trim($_POST['nombre_completo'] ?? '') !== '') {
                $um = new UsuarioModelo();
                $correo = trim($_POST['correo'] ?? '');
                $clave = $_POST['contrasena'] ?? '';
                if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($clave) < 8 || $um->porCorreo($correo)) {
                    flash('error', 'Para crear la cuenta: correo válido no repetido y contraseña temporal de 8+ caracteres.');
                    redirigir('pacientes', 'crear');
                }
                $usuarioId = $um->crear(trim($_POST['nombre_completo']), $correo, $clave, 'paciente', trim($_POST['telefono'] ?? ''));
            }
            $this->pacientes->crear(
                $usuarioId,
                $dni,
                $_POST['fecha_nacimiento'] ?? '',
                trim($_POST['direccion'] ?? ''),
                trim($_POST['antecedentes'] ?? '')
            );
            flash('ok', 'Paciente registrado con su historia clínica creada.');
        } else {
            $this->pacientes->actualizar(
                $id,
                $dni,
                $_POST['fecha_nacimiento'] ?? '',
                trim($_POST['direccion'] ?? ''),
                trim($_POST['antecedentes'] ?? '')
            );
            flash('ok', 'Paciente actualizado.');
        }
        redirigir('pacientes', 'index');
    }

    /*
     * GET ?c=pacientes&a=ver&id=N — ficha + historial de citas.
     * El rol paciente ignora el id de la URL y ve SOLO el suyo.
     */
    public function ver()
    {
        requiereLogin();
        $u = usuarioActual();
        if ($u['rol'] === 'paciente') {
            $paciente = $this->pacientes->porUsuarioId($u['id']);
            if ($paciente === null) {
                flash('error', 'Tu cuenta aún no tiene ficha de paciente. Pide ayuda en recepción.');
                redirigir('panel', 'index');
            }
            $paciente = $this->pacientes->porId($paciente['id']);
        } else {
            $paciente = $this->pacientes->porId((int) ($_GET['id'] ?? 0));
            if ($paciente === null) {
                flash('error', 'Ese paciente no existe.');
                redirigir('pacientes', 'index');
            }
        }
        $citas = (new CitaModelo())->listar($paciente['id']);
        require __DIR__ . '/../vista/pacientes/detalle.php';
    }

    /* Cuentas con rol paciente que todavía no tienen ficha. */
    private function usuariosPacientesSinFicha()
    {
        $db = Conexion::obtener();
        return $db->query(
            "SELECT u.* FROM usuarios u
             LEFT JOIN pacientes p ON p.usuario_id = u.id
             WHERE u.rol = 'paciente' AND p.id IS NULL AND u.estado = 1
             ORDER BY u.nombre_completo"
        )->fetchAll();
    }
}
