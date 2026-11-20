# Odontologia Local — Instalación

## En LINUX (tu caso: sin XAMPP)

1. **Instala PHP y MariaDB** si no los tienes (Arch/Omarchy: `sudo pacman -S php mariadb`; Debian/Ubuntu: `sudo apt install php php-mysql php-curl mariadb-server`). En `php.ini` deben estar activas las extensiones `pdo_mysql`, `curl`, `openssl` y `mbstring`.
2. **Enciende la base**: `sudo systemctl start mariadb` y crea el acceso una vez si tu distro lo pide (`sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql` solo la primera vez en Arch).
3. **Importa la base**: desde la carpeta del proyecto, `mysql -u root -p < infra/base_de_datos.sql` (si tu root no tiene contraseña, sin `-p`; en muchas distros `sudo mysql < infra/base_de_datos.sql` también sirve).
4. **Arranca el sistema**: `./iniciar.sh` y abre `http://localhost:8000/` — entra con las cuentas de prueba de abajo. El módulo independiente: `./acceso-google-2fa/iniciar-modulo.sh` → `http://localhost:3000/login.php`.

> La conexión va por TCP `127.0.0.1:3306` y la zona horaria está fijada a `America/Lima` en la configuración: son los dos ajustes que hacen que en Linux funcione igual que en XAMPP.
>
> **Si MySQL te rechaza con el error 1698** (la cuenta root de tu distro solo acepta conexión por socket): crea un usuario para el sistema y pon sus datos en `config/config.php` (y en `acceso-google-2fa/config.php`):
> `CREATE USER 'odontologia'@'127.0.0.1' IDENTIFIED BY 'tu-clave'; GRANT ALL ON odontologia_local.* TO 'odontologia'@'127.0.0.1'; FLUSH PRIVILEGES;`

## En XAMPP / Windows (4 pasos)

1. **Enciende XAMPP**: Apache y MySQL en verde.
2. **Importa la base de datos**: entra a `http://localhost/phpmyadmin` → pestaña **Importar** → elige `infra/base_de_datos.sql` → Continuar. (Crea la base `odontologia_local`, sus 12 tablas y los datos de prueba.)
3. **Copia la carpeta** `OdontologiaLocal` dentro de `C:\xampp\htdocs\`.
4. **Abre** `http://localhost/OdontologiaLocal/` e inicia sesión con una cuenta de prueba:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@odontologialocal.pe | Admin123* |
| Odontólogo | dr.perez@odontologialocal.pe | Odonto123* |
| Recepcionista | recepcion@odontologialocal.pe | Recep123* |
| Paciente | paciente@odontologialocal.pe | Paciente123* |

**¿MariaDB? No necesitas nada extra.** El SQL del proyecto es MySQL estándar. Lo que pasa es que XAMPP, cuando pulsas «MySQL» en su panel, arranca por dentro MariaDB (así viene XAMPP desde hace años): es el mismo botón, el mismo phpMyAdmin y este SQL corre igual ahí y en un MySQL 8 puro. Mis pruebas se hicieron en ese mismo motor de XAMPP, por eso a veces lo nombro.

Tras la contraseña, el sistema pide el **código MFA**: en modo desarrollo (XAMPP local) el código se muestra en pantalla y queda en `runtime/correos.log`, porque XAMPP no envía correo real sin configurar SMTP.

## Login con Google (credenciales NO incluidas: se configuran en local)
El repositorio no contiene credenciales de Google: por seguridad, cada instalador crea su propio cliente OAuth en Google Cloud Console y coloca sus claves exclusivamente en `config/config.local.php` (y en `acceso-google-2fa/config.local.php` para el módulo), archivo que está ignorado por Git y nunca se sube al repositorio.

Pasos para configurarlo:

1. Entra a `https://console.cloud.google.com` → crea un proyecto → **Pantalla de consentimiento de OAuth** (tipo Externa) → llena nombre y correos de contacto.
2. Ve a **Credenciales** → **+ Crear credenciales** → **ID de cliente de OAuth** (Aplicación web).
3. En **URI de redirección autorizados**, registra EXACTAMENTE las que correspondan a tu entorno:
   - `http://localhost:8000/index.php?c=auth&a=googleCallback` (Linux con `iniciar.sh`)
   - `http://localhost/OdontologiaLocal/index.php?c=auth&a=googleCallback` (sistema principal en XAMPP)
   - `http://localhost:3000/google-callback.php` (módulo independiente)
   
   ¿Dudas de cuál es la tuya? La pantalla de login, bajo el botón de Google, **muestra la URI exacta** que tu servidor está enviando: cópiala de ahí.
4. Crea el archivo `config/config.local.php` en tu máquina (ignorado por Git) con tus claves:
   ```php
   <?php
   define('GOOGLE_CLIENT_ID', 'TU_CLIENT_ID.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'TU_CLIENT_SECRET');
   ```
5. Recarga el login: el botón «Entrar con Google» ya funcionará. Si el correo no existe en la base, la cuenta se **crea automáticamente como paciente** y en su primer ingreso el sistema solicita verificar su correo con código, crear una contraseña y recomienda activar el 2FA con QR. Sin ficha: recepción registra su DNI después.

## Google Authenticator / 2FA (no necesita Google Cloud)
Cada usuario lo activa solo: **Mi seguridad → Activar Google Authenticator**, escanea el QR (o escribe la clave manual en bloques de 4) y confirma con el código de 6 dígitos. Solo al confirmar, `two_factor_enabled` pasa a 1 y desde ahí su segundo factor sale de la app y ya no del correo.

## Módulo independiente (archivos sueltos, localhost:3000)
La carpeta `acceso-google-2fa/` trae el mismo acceso en archivos directos (`login.php`, `google-callback.php`, `configurar-2fa.php`, `verificar-2fa.php`, clase `TOTP.php`). Para probarlo: terminal en esa carpeta → `php -S localhost:3000` → `http://localhost:3000/login.php`. Detalle en su `LEEME.md`.

## ¿Ya tenías la base de la versión anterior?
Importa una sola vez `infra/migracion_google_2fa.sql` (phpMyAdmin → Importar): agrega `google_id`, `avatar`, `totp_secret` y `two_factor_enabled` a `usuarios` sin perder datos. Si instalas desde cero con el `base_de_datos.sql` actual, no lo necesitas.

La explicación completa de cada archivo, cada módulo, GET vs POST, las reglas de negocio y la recuperación de un solo uso está en el PDF de documentación que acompaña este proyecto.

## Correo REAL por SMTP (para que los códigos y enlaces lleguen de verdad)
Sin configurar esto, el sistema queda en modo práctica: los códigos y el enlace de recuperación se muestran en pantalla y en `runtime/correos.log`, pero **no sale ningún correo**, porque tu PC no es un servidor de correo. Para envío real a cualquier correo:

1. Usa una cuenta de Gmail como remitente: entra a tu cuenta Google → **Seguridad** → activa la **Verificación en 2 pasos**.
2. En esa misma sección → **Contraseñas de aplicaciones** → crea una para «Odontologia Local»: Google te da **16 letras**.
3. En `config/config.php` llena: `SMTP_HOST` = `smtp.gmail.com`, `SMTP_PUERTO` = 587, `SMTP_SEGURIDAD` = `'tls'`, `SMTP_USUARIO` = tu Gmail, `SMTP_CLAVE` = las 16 letras (NO tu contraseña normal).
4. Pon `MODO_DEV` en **false**. Desde ahí, el código MFA y el enlace de recuperación se envían de verdad y ya NO se muestran en pantalla (el cliente SMTP está en `app/ayudantes/Smtp.php`, PHP puro).

> ⚠️ La `SMTP_CLAVE` es un secreto más: no la guardes en el repositorio público; si vas a usar correo real, agrégala en `config.local.php` o mediante variables de entorno.

## Roles de las cuentas de Google (se dan desde la base, no por la web)
Entrar con Google valida la identidad del usuario y, si el correo es nuevo, lo registra automáticamente con el rol de **Paciente**. Si deseas que una cuenta de Google tenga un rol superior (Administrador, Odontólogo o Recepcionista), puedes crear previamente el usuario desde el panel administrativo de **Usuarios** o ejecutar una consulta en `infra/roles_google.sql` con el correo correspondiente. Al iniciar sesión con Google por primera vez, el sistema detectará el correo y conservará el rol asignado.
