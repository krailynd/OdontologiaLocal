<?php
/*
 * ARCHIVO: app/controlador/UsuarioControlador.php  (CONTROLADOR)
 * QUÉ HACE: el CRUD de usuarios. SOLO el Administrador entra aquí
 * (requiereRol('admin') en cada acción: el permiso se revisa en el
 * servidor, no escondiendo botones).
 *  - GET  index / crear / editar  -> muestran pantallas
 *  - POST guardar / estado        -> ÚNICOS que cambian la BD
 * No hay "eliminar": el PDF pide activar/desactivar SIN eliminar.
 * Si el usuario nuevo es PACIENTE, también se crea su ficha en
 * pacientes (con DNI) y su historia clínica única nace vacía.
 */
class UsuarioControlador
{
    private $usuarios;
    private $pacientes;

    public function __construct()
    {
        $this->usuarios = new UsuarioModelo();
        $this->pacientes = new PacienteModelo();
    }

    /* GET ?c=usuarios&a=index — lista de usuarios con su rol y estado. */
    public function index()
    {
        requiereRol('admin');
        $lista = $this->usuarios->listar();
        require __DIR__ . '/../vista/usuarios/index.php';
    }

    /* GET ?c=usuarios&a=crear — formulario de usuario nuevo. */
    public function crear()
    {
        requiereRol('admin');
        $usuario = null;
        require __DIR__ . '/../vista/usuarios/formulario.php';
    }

    /* GET ?c=usuarios&a=editar&id=N — formulario con los datos cargados. */
    public function editar()
    {
        requiereRol('admin');
        $usuario = $this->usuarios->porId((int) ($_GET['id'] ?? 0));
        if ($usuario === null) {
            flash('error', 'Ese usuario no existe.');
            redirigir('usuarios', 'index');
        }
        require __DIR__ . '/../vista/usuarios/formulario.php';
    }

    /*
     * POST ?c=usuarios&a=guardar — crea o edita según venga "id".
     * Al CREAR se exige contraseña temporal: el usuario deberá
     * cambiarla en su primer ingreso (debe_cambiar_contrasena = 1).
     */
    public function guardar()
    {
        requiereRol('admin');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $rol = $_POST['rol'] ?? '';
        $telefono = trim($_POST['telefono'] ?? '');
        $rolesValidos = ['admin', 'odontologo', 'recepcionista', 'paciente'];

        if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || !in_array($rol, $rolesValidos, true)) {
            flash('error', 'Revisa los datos: nombre, correo válido y rol son obligatorios.');
            redirigir('usuarios', $id ? 'editar' : 'crear', $id ? '&id=' . $id : '');
        }
        // Correo UNIQUE: no puede repetirse en otro usuario
        $existente = $this->usuarios->porCorreo($correo);
        if ($existente && (int) $existente['id'] !== $id) {
            flash('error', 'Ya existe un usuario con ese correo.');
            redirigir('usuarios', $id ? 'editar' : 'crear', $id ? '&id=' . $id : '');
        }

        if ($id === 0) {
            $clave = $_POST['contrasena'] ?? '';
            if (strlen($clave) < 8) {
                flash('error', 'La contraseña temporal debe tener al menos 8 caracteres.');
                redirigir('usuarios', 'crear');
            }
            $nuevoId = $this->usuarios->crear($nombre, $correo, $clave, $rol, $telefono);
            // Si es paciente, nace también su ficha + historia única
            if ($rol === 'paciente') {
                $dni = trim($_POST['dni'] ?? '');
                if ($dni === '' || $this->pacientes->existeDni($dni)) {
                    flash('aviso', 'Usuario paciente creado, pero su ficha NO: falta el DNI o ya existe. Complétala en Pacientes.');
                } else {
                    $this->pacientes->crear($nuevoId, $dni, $_POST['fecha_nacimiento'] ?? '', '', '');
                }
            }
            flash('ok', 'Usuario creado. En su primer ingreso el sistema le pedirá el MFA y cambiar su contraseña.');
        } else {
            $this->usuarios->actualizar($id, $nombre, $correo, $rol, $telefono);
            flash('ok', 'Usuario actualizado.');
        }
        redirigir('usuarios', 'index');
    }

    /*
     * POST ?c=usuarios&a=estado — activa o desactiva (sin eliminar).
     * Un administrador no puede desactivarse a sí mismo.
     */
    public function estado()
    {
        requiereRol('admin');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $nuevo = (int) ($_POST['estado'] ?? 1);
        if ($id === (int) usuarioActual()['id'] && $nuevo === 0) {
            flash('error', 'No puedes desactivar tu propia cuenta de administrador.');
            redirigir('usuarios', 'index');
        }
        $this->usuarios->cambiarEstado($id, $nuevo);
        flash('ok', $nuevo ? 'Usuario activado.' : 'Usuario desactivado (su historial se conserva).');
        redirigir('usuarios', 'index');
    }
}
