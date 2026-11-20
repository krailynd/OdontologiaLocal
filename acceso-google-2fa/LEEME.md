# Módulo independiente: Google OAuth + 2FA (archivos sueltos)

Este módulo es el **mismo sistema de acceso** de Odontologia Local,
pero escrito en archivos directos, sin Front Controller, para que
puedas estudiarlo, mostrarlo o probarlo por separado. Comparte la
**misma base de datos** (`odontologia_local`) y la misma configuración
de Google que el sistema principal (cargadas desde `config.local.php` o variables de entorno).

## Archivos y qué hace cada uno

| Archivo | Qué hace |
|---|---|
| `config.php` | Configuración: sesión endurecida, conexión PDO, credenciales de Google, endpoints reales, Redirect URI, helpers (CSRF, flash, crear sesión). |
| `TOTP.php` | Clase 2FA en PHP puro (RFC 6238): `generateSecret()`, `getQRCodeUrl()`, `verifyCode()` con tolerancia ±30 s. |
| `login.php` | Formulario correo+contraseña (POST) y botón de Google (GET a la URL de autorización con `response_type=code`, `state`, `access_type=online`). |
| `google-callback.php` | Recibe `?code&state`: valida el state, canjea el código por `access_token` con cURL, pide el perfil verificado, busca o **auto-registra** al usuario (paciente) y sigue al 2FA si aplica. |
| `verificar-2fa.php` | Pantalla del segundo factor cuando `two_factor_enabled = 1`. |
| `configurar-2fa.php` | Activación del 2FA: QR (`otpauth://`) + clave manual en bloques de 4 con botón copiar + confirmación con código antes de activar. |
| `panel.php` | Llegada tras login completo: datos, avatar y estado del 2FA. |
| `salir.php` | Cierre de sesión (POST con CSRF). |

## Cómo probarlo en http://localhost:3000

1. Ten XAMPP con MySQL encendido y la base `odontologia_local` ya
   importada (el módulo usa la misma base).
2. Abre una terminal **en esta carpeta** y ejecuta:

   ```
   php -S localhost:3000
   ```

3. Entra a **http://localhost:3000/login.php**

La Redirect URI configurada (`http://localhost:3000/google-callback.php`)
debe estar registrada EXACTAMENTE así en Google Cloud → tu cliente
OAuth → "URI de redirección autorizados". Si falta, Google devuelve
`redirect_uri_mismatch`.

## Si lo corres dentro de XAMPP (puerto 80)

La URL sería `http://localhost/OdontologiaLocal/acceso-google-2fa/login.php`,
pero el retorno de Google ya NO coincide con `:3000`. Para usarlo
en XAMPP: cambia `GOOGLE_REDIRECT_URI` en `config.php` a
`http://localhost/OdontologiaLocal/acceso-google-2fa/google-callback.php`
y registra también esa URI en Google Cloud. Puedes tener varias
registradas a la vez; Google usa la que coincida con la petición.

## Notas de seguridad (blindaje)

- El `client_secret` solo se usa en peticiones servidor→Google
  (cURL); nunca llega al navegador.
- Todos los formularios llevan token CSRF y las cookies de sesión
  son HttpOnly + SameSite=Lax.
- El código de autorización de Google es de un solo uso y el
  `state` anti-CSRF se valida antes de canjearlo.
- Las credenciales (`client_id` y `client_secret`) no se guardan en el repositorio:
  el módulo las lee desde `config.local.php` (bloqueado por `.gitignore`) o de variables de entorno.
  Crea ese archivo solo en tu entorno local siguiendo las instrucciones de la documentación.
