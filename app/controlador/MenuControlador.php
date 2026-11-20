<?php
/*
 * ARCHIVO: app/controlador/MenuControlador.php  (CONTROLADOR)
 * QUÉ HACE: el MAPA DEL SISTEMA: una pantalla que lista TODAS las
 * rutas GET (pantallas) y TODAS las rutas POST (acciones que
 * cambian la base de datos) con su controlador, qué hacen y a
 * dónde redirigen. Sirve para estudiar el proyecto y exponerlo.
 */
class MenuControlador
{
    /* GET ?c=menu&a=index — muestra el mapa completo. */
    public function index()
    {
        requiereLogin();
        require __DIR__ . '/../vista/menu/index.php';
    }
}
