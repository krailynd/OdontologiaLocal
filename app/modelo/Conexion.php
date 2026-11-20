<?php
/*
 * ARCHIVO: app/modelo/Conexion.php  (MODELO - infraestructura)
 * QUÉ HACE: crea UNA sola conexión PDO a MySQL y la reutiliza.
 * Todos los modelos piden la conexión aquí; ninguno abre la suya.
 * PDO con sentencias preparadas = protección contra inyección SQL.
 */
class Conexion
{
    private static $instancia = null;

    /* Devuelve la conexión PDO única (patrón Singleton). */
    public static function obtener()
    {
        if (self::$instancia === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4';
            self::$instancia = new PDO($dsn, DB_USUARIO, DB_CLAVE, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instancia;
    }
}
