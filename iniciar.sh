#!/usr/bin/env bash
# =====================================================================
#  Odontologia Local — arranque en LINUX (sin XAMPP)
#  Levanta el servidor embebido de PHP en el puerto 8000 con esta
#  carpeta como raíz. Uso:   ./iniciar.sh
#  Luego abre:               http://localhost:8000/
#
#  Antes de usarlo por primera vez:
#   1. MySQL/MariaDB encendido:   sudo systemctl start mariadb
#      (o: sudo systemctl start mysql, según tu distro)
#   2. Base importada una vez:
#      mysql -u root -p < infra/base_de_datos.sql
#      (si tu root de MySQL no tiene contraseña, quita la -p)
#
#  ⚠️ GOOGLE: con este arranque, la URI de retorno es
#     http://localhost:8000/index.php?c=auth&a=googleCallback
#     y debe estar registrada en Google Cloud. La misma pantalla
#     de login la muestra exacta bajo el botón de Google.
# =====================================================================
cd "$(dirname "$0")"
echo "Odontologia Local en http://localhost:8000/  (Ctrl+C para detener)"
php -S localhost:8000 -t .
