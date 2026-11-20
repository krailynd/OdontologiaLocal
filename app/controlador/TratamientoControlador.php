<?php
/*
 * ARCHIVO: app/controlador/TratamientoControlador.php  (CONTROLADOR)
 * QUÉ HACE: el catálogo de tratamientos con precios y descuentos.
 * Admin y recepción lo administran; el odontólogo lo consulta.
 */
class TratamientoControlador
{
    private $tratamientos;

    public function __construct()
    {
        $this->tratamientos = new TratamientoModelo();
    }

    /* GET ?c=tratamientos&a=index — lista del catálogo. */
    public function index()
    {
        requiereRol('admin', 'recepcionista', 'odontologo');
        $lista = $this->tratamientos->listar();
        require __DIR__ . '/../vista/tratamientos/index.php';
    }

    /* GET ?c=tratamientos&a=crear — formulario nuevo. */
    public function crear()
    {
        requiereRol('admin', 'recepcionista');
        $tratamiento = null;
        require __DIR__ . '/../vista/tratamientos/formulario.php';
    }

    /* GET ?c=tratamientos&a=editar&id=N — formulario con datos. */
    public function editar()
    {
        requiereRol('admin', 'recepcionista');
        $tratamiento = $this->tratamientos->porId((int) ($_GET['id'] ?? 0));
        if ($tratamiento === null) {
            flash('error', 'Ese tratamiento no existe.');
            redirigir('tratamientos', 'index');
        }
        require __DIR__ . '/../vista/tratamientos/formulario.php';
    }

    /* POST ?c=tratamientos&a=guardar — crea o edita (solo POST). */
    public function guardar()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float) ($_POST['precio'] ?? 0);
        $descuento = (float) ($_POST['descuento_pct'] ?? 0);
        if ($nombre === '' || $precio < 0 || $descuento < 0 || $descuento > 100) {
            flash('error', 'Nombre obligatorio, precio desde 0 y descuento entre 0 y 100%.');
            redirigir('tratamientos', $id ? 'editar' : 'crear', $id ? '&id=' . $id : '');
        }
        if ($id === 0) {
            $this->tratamientos->crear($nombre, trim($_POST['descripcion'] ?? ''), $precio, $descuento);
            flash('ok', 'Tratamiento agregado al catálogo.');
        } else {
            $this->tratamientos->actualizar($id, $nombre, trim($_POST['descripcion'] ?? ''), $precio, $descuento);
            flash('ok', 'Tratamiento actualizado.');
        }
        redirigir('tratamientos', 'index');
    }

    /* POST ?c=tratamientos&a=estado — activa/desactiva sin borrar. */
    public function estado()
    {
        requiereRol('admin', 'recepcionista');
        verificarCsrf();
        $this->tratamientos->cambiarEstado((int) ($_POST['id'] ?? 0), (int) ($_POST['estado'] ?? 1));
        flash('ok', 'Estado del tratamiento actualizado.');
        redirigir('tratamientos', 'index');
    }
}
