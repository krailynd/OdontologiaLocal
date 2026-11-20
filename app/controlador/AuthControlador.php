<?php
/*
 * ARCHIVO: app/controlador/AuthControlador.php  (CONTROLADOR)
 * QUÉ HACE: TODO el acceso al sistema:
 *   - login con correo + contraseña            (GET form / POST entra)
 *   - MFA por código de correo                 (tabla codigos_mfa)
 *   - MFA con Google Authenticator (TOTP)      (si el usuario lo activó)
 *   - login con Google (OAuth, Google Cloud)   (GET google / googleCallback)
 *   - bloqueo tras 3 intentos fallidos
 *   - registro de sesiones (IP, dispositivo, fecha/hora)
 *   - recuperación con enlace de UN SOLO USO   (tokens_recuperacion)
 *   - cambio de contraseña obligatorio la primera vez
 *
 * REGLA GET/POST DE ESTE CONTROLADOR:
 *   GET  = solo MUESTRA formularios (login, mfa, recuperar...).
 *   POST = lo único que CAMBIA algo (entrar, verificar, guardar).
 *   La única excepción es googleCallback: es un GET porque así
 *   funciona OAuth (Google devuelve el navegador con ?code=), y lo
 *   que hace es crear la SESIÓN después de que Google ya verificó
 *   la identidad; no modifica datos de negocio.
 */
class AuthControlador
{
    private $usuarios;
    private $seguridad;

    public function __construct()
    {
        $this->usuarios = new UsuarioModelo();
        $this->seguridad = new SeguridadModelo();
    }

    // ---------------- LOGIN (correo + contraseña) ----------------

    /* GET ?c=auth&a=login — muestra el formulario de ingreso. */
    public function login()
    {
        if (usuarioActual() !== null) {
            redirigir('panel', 'index');
        }
        $googleConfigurado = Google::configurado();
        require __DIR__ . '/../vista/auth/login.php';
    }

    /*
     * POST ?c=auth&a=entrar — PRIMER factor: correo + contraseña.
     * Orden de defensas:
     *  1. ¿Existe el correo?        no -> mensaje genérico
     *  2. ¿Está desactivado?        sí -> no entra
     *  3. ¿Está bloqueado?          sí -> avisa hasta cuándo
     *  4. ¿Contraseña correcta?     no -> suma intento (al 3ro bloquea)
     *  5. Correcta -> pasa al SEGUNDO factor (MFA), todavía NO entra.
     * El mensaje de error nunca dice si falló el correo o la clave:
     * así no se ayuda a un atacante a probar correos.
     */
    public function entrar()
    {
        verificarCsrf();
        $correo = trim($_POST['correo'] ?? '');
        $clave = $_POST['contrasena'] ?? '';

        $u = $this->usuarios->porCorreo($correo);
        if ($u === null || !password_verify($clave, $u['contrasena'])) {
            if ($u !== null) {
                $bloqueada = $this->usuarios->registrarIntentoFallido($u['id']);
                if ($bloqueada) {
                    flash('error', 'Cuenta bloqueada por 3 intentos fallidos. Intenta de nuevo en 15 minutos.');
                    redirigir('auth', 'login');
                }
            }
            flash('error', 'Correo o contraseña incorrectos.');
            redirigir('auth', 'login');
        }
        if ((int) $u['estado'] !== 1) {
            flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
            redirigir('auth', 'login');
        }
        if ($this->usuarios->estaBloqueado($u)) {
            flash('error', 'Tu cuenta está bloqueada hasta las ' . e($u['bloqueado_hasta']) . ' por intentos fallidos.');
            redirigir('auth', 'login');
        }

        $this->usuarios->reiniciarIntentos($u['id']);
        $this->iniciarMfa($u, 'password');
    }

    // ---------------- MFA (segundo factor) ----------------

    /*
     * Prepara el segundo factor según lo que tenga el usuario:
     *  - Si CONFIRMÓ su 2FA (two_factor_enabled = 1 y tiene secreto)
     *    -> código TOTP de su app autenticadora.
     *  - Si no, código de 6 dígitos por CORREO (codigos_mfa).
     * Guarda en sesión SOLO el id pendiente: la sesión completa
     * recién nace cuando el MFA se verifica.
     */
    private function iniciarMfa($u, $metodo)
    {
        $_SESSION['mfa_usuario_id'] = $u['id'];
        $_SESSION['mfa_metodo_login'] = $metodo;
        if (!empty($u['two_factor_enabled']) && !empty($u['totp_secret'])) {
            $_SESSION['mfa_tipo'] = 'totp';
        } else {
            $_SESSION['mfa_tipo'] = 'correo';
            $codigo = $this->seguridad->crearCodigoMfa($u['id']);
            Correo::enviar(
                $u['correo'],
                'Tu código de acceso a ' . APP_NOMBRE,
                "Hola {$u['nombre_completo']}:\n\nTu código de verificación es: $codigo\n"
                . "Vence en 10 minutos y solo sirve una vez.\nSi no fuiste tú, ignora este correo."
            );
            if (MODO_DEV && !CORREO_REAL) {
                $_SESSION['dev_codigo_mfa'] = $codigo; // solo para practicar en local
            }
        }
        redirigir('auth', 'mfa');
    }

    /* GET ?c=auth&a=mfa — muestra el formulario del segundo factor. */
    public function mfa()
    {
        if (empty($_SESSION['mfa_usuario_id'])) {
            redirigir('auth', 'login');
        }
        $tipo = $_SESSION['mfa_tipo'] ?? 'correo';
        $codigoDev = $_SESSION['dev_codigo_mfa'] ?? null;
        require __DIR__ . '/../vista/auth/mfa.php';
    }

    /*
     * POST ?c=auth&a=verificarMfa — verifica el segundo factor.
     * Si pasa: nace la sesión completa, se REGISTRA la sesión en la
     * tabla sesiones y, si debe cambiar su contraseña (primera vez),
     * se le obliga antes de usar cualquier otra pantalla.
     */
    public function verificarMfa()
    {
        verificarCsrf();
        if (empty($_SESSION['mfa_usuario_id'])) {
            redirigir('auth', 'login');
        }
        $u = $this->usuarios->porId($_SESSION['mfa_usuario_id']);
        $codigo = $_POST['codigo'] ?? '';
        $tipo = $_SESSION['mfa_tipo'] ?? 'correo';

        $ok = false;
        if ($tipo === 'totp') {
            $ok = Totp::verificar($u['totp_secret'], $codigo);
        } else {
            $ok = $this->seguridad->verificarCodigoMfa($u['id'], $codigo);
        }
        if (!$ok) {
            flash('error', 'El código es incorrecto, ya fue usado o venció. Pide uno nuevo volviendo a iniciar sesión.');
            redirigir('auth', 'mfa');
        }

        $metodo = $_SESSION['mfa_metodo_login'] ?? 'password';
        unset($_SESSION['mfa_usuario_id'], $_SESSION['mfa_tipo'], $_SESSION['mfa_metodo_login'], $_SESSION['dev_codigo_mfa']);
        session_regenerate_id(true); // la sesión cambia de id al autenticarse

        $_SESSION['usuario'] = [
            'id' => $u['id'],
            'nombre' => $u['nombre_completo'],
            'correo' => $u['correo'],
            'rol' => $u['rol'],
            'avatar' => $u['avatar'] ?? null,
        ];
        $this->seguridad->registrarSesion($u['id'], $metodo);

        if ((int) $u['debe_cambiar_contrasena'] === 1) {
            $_SESSION['debe_cambiar'] = true;
            flash('aviso', 'Por seguridad debes cambiar tu contraseña: es tu primer ingreso.');
            redirigir('auth', 'cambiarPrimera');
        }
        flash('ok', 'Bienvenido/a, ' . $u['nombre_completo'] . '.');
        redirigir('panel', 'index');
    }

    // ---------------- LOGIN CON GOOGLE (OAuth) ----------------

    /*
     * GET ?c=auth&a=google — manda el navegador a la pantalla de
     * Google. Si faltan las credenciales de Google Cloud en
     * config.php, avisa exactamente qué falta (no falla en silencio).
     */
    public function google()
    {
        if (!Google::configurado()) {
            flash('error', 'El login con Google aún no está configurado: falta pegar GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en config/config.php (guía paso a paso en la documentación PDF).');
            redirigir('auth', 'login');
        }
        header('Location: ' . Google::urlLogin());
        exit;
    }

    /*
     * GET ?c=auth&a=googleCallback — Google devuelve aquí al usuario
     * con un código. Se canjea por el perfil VERIFICADO y se busca
     * la cuenta en la clínica:
     *  - Si el correo no existe en usuarios -> AUTO-REGISTRO como
     *    paciente (el correo ya viene verificado por Google), con
     *    su google_id y su avatar; queda sin ficha hasta que
     *    recepción registre su DNI.
     *  - Si existe, se vincula su google_id la primera vez y se
     *    actualiza su avatar con la foto de Google.
     * Después pasa por el MISMO MFA que el login normal: si tiene
     * 2FA confirmado va a verificar el TOTP; si no, código por correo.
     */
    public function googleCallback()
    {
        if (!Google::configurado()) {
            redirigir('auth', 'google');
        }
        if (isset($_GET['error']) || empty($_GET['code'])) {
            flash('error', 'Google no completó el ingreso (se canceló o faltó el código).');
            redirigir('auth', 'login');
        }
        if (($_GET['state'] ?? '') !== ($_SESSION['google_state'] ?? null)) {
            flash('error', 'La respuesta de Google no coincide con la solicitud (state inválido).');
            redirigir('auth', 'login');
        }
        unset($_SESSION['google_state']);

        $tokens = Google::intercambiarCodigo($_GET['code']);
        if (!$tokens || empty($tokens['access_token'])) {
            flash('error', 'Google rechazó el canje del código. Revisa el Client Secret y la URI de redirección registrada en Google Cloud.');
            redirigir('auth', 'login');
        }
        $perfil = Google::datosUsuario($tokens['access_token']);
        if (!$perfil || empty($perfil['sub']) || empty($perfil['email'])) {
            flash('error', 'No se pudo leer el perfil verificado de Google.');
            redirigir('auth', 'login');
        }

        $u = $this->usuarios->porGoogleId($perfil['sub']) ?: $this->usuarios->porCorreo($perfil['email']);
        if ($u === null) {
            // AUTO-REGISTRO (mejora pedida): el correo ya viene
            // VERIFICADO por Google, así que se crea la cuenta como
            // PACIENTE con su google_id y su foto (avatar). Queda sin
            // ficha de paciente hasta que recepción registre su DNI.
            $nuevoId = $this->usuarios->crearDesdeGoogle(
                $perfil['name'] ?? $perfil['email'],
                $perfil['email'],
                $perfil['sub'],
                $perfil['picture'] ?? null
            );
            $u = $this->usuarios->porId($nuevoId);
            // Marca de sesión: al llegar al panel (tras el código y
            // crear su contraseña) se le mostrará UNA vez la ventana
            // que recomienda activar el 2FA con QR. Solo nuevos.
            $_SESSION['bienvenida_2fa'] = true;
            flash('aviso', 'Tu cuenta fue creada con Google como PACIENTE. Paso siguiente: te enviamos un código a tu correo para verificar que es tuyo; después crearás tu contraseña. En recepción completarán tu ficha con tu DNI.');
        } else {
            if (empty($u['google_id'])) {
                $this->usuarios->vincularGoogle($u['id'], $perfil['sub']);
            }
            // La foto de Google se actualiza en cada ingreso
            if (!empty($perfil['picture']) && ($u['avatar'] ?? null) !== $perfil['picture']) {
                $this->usuarios->guardarAvatar($u['id'], $perfil['picture']);
                $u['avatar'] = $perfil['picture'];
            }
        }
        if ((int) $u['estado'] !== 1) {
            flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
            redirigir('auth', 'login');
        }
        $this->iniciarMfa($u, 'google');
    }

    // ---------------- RECUPERACIÓN (enlace de un solo uso) ----------------

    /* GET ?c=auth&a=recuperar — formulario "olvidé mi contraseña". */
    public function recuperar()
    {
        require __DIR__ . '/../vista/auth/recuperar.php';
    }

    /*
     * POST ?c=auth&a=enviarRecuperacion — crea el token y manda el
     * enlace. Responde SIEMPRE el mismo mensaje, exista o no el
     * correo: así nadie puede averiguar qué correos están registrados.
     * El enlace lleva el token en claro UNA vez; la base guarda su hash.
     */
    public function enviarRecuperacion()
    {
        verificarCsrf();
        $correo = trim($_POST['correo'] ?? '');
        $u = $this->usuarios->porCorreo($correo);
        if ($u !== null && (int) $u['estado'] === 1) {
            $token = $this->seguridad->crearTokenRecuperacion($u['id']);
            $enlace = BASE_URL . 'index.php?c=auth&a=restablecer&token=' . $token;
            Correo::enviar(
                $u['correo'],
                'Restablecer tu contraseña de ' . APP_NOMBRE,
                "Hola {$u['nombre_completo']}:\n\nPara crear una contraseña nueva entra a este enlace:\n$enlace\n\n"
                . "El enlace vence en 30 minutos y SOLO SE PUEDE USAR UNA VEZ.\nSi no pediste esto, ignora el correo: tu contraseña no cambia."
            );
            if (MODO_DEV && !CORREO_REAL) {
                $_SESSION['dev_enlace_recuperacion'] = $enlace;
            }
        }
        $enlaceDev = $_SESSION['dev_enlace_recuperacion'] ?? null;
        unset($_SESSION['dev_enlace_recuperacion']);
        require __DIR__ . '/../vista/auth/recuperar_enviado.php';
    }

    /* GET ?c=auth&a=restablecer&token=... — valida el token y muestra el formulario. */
    public function restablecer()
    {
        $token = $_GET['token'] ?? '';
        $fila = $this->seguridad->tokenValido($token);
        if ($fila === null) {
            flash('error', 'Ese enlace ya fue usado, venció o no es válido. Pide uno nuevo.');
            redirigir('auth', 'recuperar');
        }
        require __DIR__ . '/../vista/auth/restablecer.php';
    }

    /*
     * POST ?c=auth&a=guardarRestablecimiento — guarda la contraseña
     * nueva y MATA el token (usado = 1): si alguien vuelve a abrir
     * el mismo enlace, ya no sirve. También desbloquea la cuenta.
     */
    public function guardarRestablecimiento()
    {
        verificarCsrf();
        $token = $_POST['token'] ?? '';
        $fila = $this->seguridad->tokenValido($token);
        if ($fila === null) {
            flash('error', 'Ese enlace ya fue usado, venció o no es válido. Pide uno nuevo.');
            redirigir('auth', 'recuperar');
        }
        $clave = $_POST['contrasena'] ?? '';
        if (strlen($clave) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirigir('auth', 'restablecer', '&token=' . urlencode($token));
        }
        $this->usuarios->guardarContrasena($fila['usuario_id'], $clave, true);
        $this->seguridad->marcarTokenUsado($fila['id']);
        flash('ok', 'Contraseña actualizada. Ya puedes iniciar sesión.');
        redirigir('auth', 'login');
    }

    // ---------------- CAMBIO OBLIGATORIO (primera vez) ----------------

    /* GET ?c=auth&a=cambiarPrimera — formulario forzado al primer ingreso. */
    public function cambiarPrimera()
    {
        if (usuarioActual() === null) {
            redirigir('auth', 'login');
        }
        require __DIR__ . '/../vista/auth/cambiar_primera.php';
    }

    /* POST ?c=auth&a=guardarPrimera — guarda y levanta la obligación. */
    public function guardarPrimera()
    {
        verificarCsrf();
        if (usuarioActual() === null) {
            redirigir('auth', 'login');
        }
        $clave = $_POST['contrasena'] ?? '';
        if (strlen($clave) < 8) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            redirigir('auth', 'cambiarPrimera');
        }
        $u = usuarioActual();
        $this->usuarios->guardarContrasena($u['id'], $clave, true);
        unset($_SESSION['debe_cambiar']);
        flash('ok', 'Contraseña actualizada. Ya puedes usar todo el sistema.');
        redirigir('panel', 'index');
    }

    // ---------------- SALIR ----------------

    /* POST ?c=auth&a=salir — cierra la sesión (solo por POST). */
    public function salir()
    {
        verificarCsrf();
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . 'index.php?c=auth&a=login');
        exit;
    }
}
