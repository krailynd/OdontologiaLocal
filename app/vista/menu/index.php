<?php
/*
 * VISTA: menu/index.php — MAPA DEL SISTEMA.
 * Lista TODAS las rutas: las GET (solo muestran) y las POST (las
 * únicas que cambian la base de datos), con su controlador, qué
 * hacen y a dónde redirigen. Es la explicación viva de cómo actúa
 * GET y cómo actúa POST en este proyecto.
 */
require __DIR__ . '/../parciales/cabecera.php';
$gets = [
    ['auth / login', 'AuthControlador', 'Muestra el formulario de ingreso (correo + contraseña) y el botón de Google', '— (es pantalla)', 'AuthControlador.php · login() · línea 36', 'Nada: solo lee la sesión'],
    ['auth / mfa', 'AuthControlador', 'Muestra el segundo factor: código por correo o Google Authenticator', '—', 'AuthControlador.php · mfa() · línea 120', 'Sesión temporal mfa_usuario_id'],
    ['auth / google', 'AuthControlador', 'Redirige a la pantalla oficial de Google (OAuth) con la redirect URI exacta', 'accounts.google.com', 'AuthControlador.php · google() · línea 186', 'Google.php (arma la URL OAuth)'],
    ['auth / googleCallback', 'AuthControlador', 'Retorno de Google: canjea el código, busca la cuenta (o la crea como PACIENTE) y pasa al MFA. ÚNICO GET que crea sesión, porque así funciona OAuth y Google ya verificó la identidad', 'auth / mfa', 'AuthControlador.php · googleCallback() · línea 209', 'Google.php (token/userinfo) + UsuarioModelo + tabla usuarios'],
    ['auth / recuperar', 'AuthControlador', 'Formulario "olvidé mi contraseña"', '—', 'AuthControlador.php · recuperar() · línea 273', 'Nada: solo muestra'],
    ['auth / restablecer?token=', 'AuthControlador', 'Valida el token (sin usar, sin vencer) y muestra el formulario de contraseña nueva', '—', 'AuthControlador.php · restablecer() · línea 308', 'SeguridadModelo + tabla tokens_recuperacion'],
    ['auth / cambiarPrimera', 'AuthControlador', 'Crear/cambiar la contraseña en el primer ingreso (obligatorio)', '—', 'AuthControlador.php · cambiarPrimera() · línea 347', 'Sesión (usuario con debe_cambiar = 1)'],
    ['panel / index', 'PanelControlador', 'Inicio según el rol (resumen, mis citas, mis facturas) + ventana de bienvenida 2FA solo a usuarios nuevos de Google', '—', 'PanelControlador.php · index() · línea 14', 'CitaModelo, FacturaModelo, PacienteModelo'],
    ['usuarios / index · crear · editar', 'UsuarioControlador', 'Lista y formularios de usuarios (solo admin)', '—', 'UsuarioControlador.php · líneas 25/33/41', 'UsuarioModelo + tabla usuarios'],
    ['pacientes / index · crear · editar · ver', 'PacienteControlador', 'Lista con buscador, formularios y ficha con historial', '—', 'PacienteControlador.php · líneas 20/29/38/100', 'PacienteModelo + HistoriaModelo'],
    ['citas / index · crear', 'CitaControlador', 'Lista de citas por rol y formulario de agendar', '—', 'CitaControlador.php · líneas 31/40', 'CitaModelo + tablas citas, pacientes, usuarios'],
    ['historias / ver', 'HistoriaControlador', 'Historia única del paciente con sus registros de atención', '—', 'HistoriaControlador.php · ver() · línea 25', 'HistoriaModelo + historias_clinicas, registros_atencion'],
    ['tratamientos / index · crear · editar', 'TratamientoControlador', 'Catálogo de precios y formularios', '—', 'TratamientoControlador.php · líneas 17/25/33', 'TratamientoModelo + tabla tratamientos'],
    ['facturas / index · emitir · ver', 'FacturaControlador', 'Lista, formulario de emisión y detalle del comprobante', '—', 'FacturaControlador.php · líneas 25/42/90', 'FacturaModelo + facturas, detalle_factura'],
    ['seguridad / index', 'SeguridadControlador', 'Mi seguridad: 2FA con QR (Authenticator), sesiones, contraseña, SMTP explicado', '—', 'SeguridadControlador.php · index() · línea 27', 'UsuarioModelo, SeguridadModelo, Totp.php'],
    ['seguridad / respaldo', 'SeguridadControlador', 'Descarga el respaldo .sql (solo admin). Lee la BD, no la modifica', 'descarga de archivo', 'SeguridadControlador.php · respaldo() · línea 118', 'Las 12 tablas (solo lectura)'],
    ['menu / index', 'MenuControlador', 'Este mapa del sistema', '—', 'MenuControlador.php · index() · línea 12', 'Nada: es documentación viva'],
];
$posts = [
    ['auth / entrar', 'AuthControlador', 'Verifica correo+contraseña, cuenta intentos (al 3.º bloquea 15 min) y genera el MFA', 'auth / mfa', 'AuthControlador.php · entrar() · línea 56', 'UsuarioModelo, SeguridadModelo, Correo/Smtp (código)'],
    ['auth / verificarMfa', 'AuthControlador', 'Verifica el código (correo: lo marca usado; TOTP: ventana ±30 s), crea la sesión y la REGISTRA (IP/dispositivo)', 'panel / index o auth / cambiarPrimera', 'AuthControlador.php · verificarMfa() · línea 136', 'SeguridadModelo + codigos_mfa, sesiones'],
    ['auth / enviarRecuperacion', 'AuthControlador', 'Crea el token (guarda su hash) y envía el enlace de un solo uso (30 min)', 'auth / recuperar (pantalla de aviso)', 'AuthControlador.php · enviarRecuperacion() · línea 284', 'SeguridadModelo + tokens_recuperacion + Correo/Smtp'],
    ['auth / guardarRestablecimiento', 'AuthControlador', 'Guarda la contraseña nueva y MATA el token (usado = 1)', 'auth / login', 'AuthControlador.php · guardarRestablecimiento() · línea 324', 'SeguridadModelo + usuarios'],
    ['auth / guardarPrimera', 'AuthControlador', 'Guarda la contraseña del primer ingreso y levanta la obligación', 'panel / index', 'AuthControlador.php · guardarPrimera() · línea 356', 'UsuarioModelo + usuarios'],
    ['auth / salir', 'AuthControlador', 'Destruye la sesión', 'auth / login', 'AuthControlador.php · salir() · línea 377', 'Sesión PHP'],
    ['usuarios / guardar', 'UsuarioControlador', 'Crea o edita usuario (hash de contraseña; si es paciente, crea su ficha e historia)', 'usuarios / index', 'UsuarioControlador.php · guardar() · línea 57', 'UsuarioModelo + PacienteModelo'],
    ['usuarios / estado', 'UsuarioControlador', 'Activa/desactiva sin eliminar', 'usuarios / index', 'UsuarioControlador.php · estado() · línea 107', 'UsuarioModelo'],
    ['pacientes / guardar', 'PacienteControlador', 'Crea o edita la ficha (y la cuenta, si se pidió)', 'pacientes / index', 'PacienteControlador.php · guardar() · línea 51', 'PacienteModelo + HistoriaModelo'],
    ['citas / guardar', 'CitaControlador', 'Agenda validando reglas: horario, doble reserva, no pasado; notifica por correo', 'citas / index', 'CitaControlador.php · guardar() · línea 59', 'CitaModelo + Correo/Smtp + notificaciones'],
    ['citas / confirmar', 'CitaControlador', 'pendiente → confirmada + notificación', 'citas / index', 'CitaControlador.php · confirmar() · línea 98', 'CitaModelo + notificaciones'],
    ['citas / atender', 'CitaControlador', 'confirmada → atendida', 'citas / index', 'CitaControlador.php · atender() · línea 110', 'CitaModelo'],
    ['citas / cancelar', 'CitaControlador', 'Cancela SOLO si faltan 2 h o más; guarda el motivo y notifica', 'citas / index', 'CitaControlador.php · cancelar() · línea 124', 'CitaModelo + Correo/Smtp'],
    ['historias / registrar', 'HistoriaControlador', 'Agrega un registro de atención a la historia única', 'historias / ver', 'HistoriaControlador.php · registrar() · línea 57', 'HistoriaModelo + registros_atencion'],
    ['tratamientos / guardar', 'TratamientoControlador', 'Crea o edita un tratamiento del catálogo', 'tratamientos / index', 'TratamientoControlador.php · guardar() · línea 45', 'TratamientoModelo'],
    ['tratamientos / estado', 'TratamientoControlador', 'Activa/desactiva un tratamiento', 'tratamientos / index', 'TratamientoControlador.php · estado() · línea 68', 'TratamientoModelo'],
    ['facturas / guardar', 'FacturaControlador', 'Emite la factura en transacción (número correlativo, precios del catálogo)', 'facturas / ver', 'FacturaControlador.php · guardar() · línea 58', 'FacturaModelo (transacción) + tratamientos'],
    ['facturas / pagar', 'FacturaControlador', 'Marca pagada con el método de pago', 'facturas / index', 'FacturaControlador.php · pagar() · línea 111', 'FacturaModelo'],
    ['facturas / anular', 'FacturaControlador', 'Anula sin borrar el registro', 'facturas / index', 'FacturaControlador.php · anular() · línea 122', 'FacturaModelo'],
    ['seguridad / iniciarTotp', 'SeguridadControlador', 'Genera el secreto TOTP pendiente de confirmación y muestra el QR', 'seguridad / index', 'SeguridadControlador.php · iniciarTotp() · línea 45', 'Totp.php + usuarios (totp_secret)'],
    ['seguridad / confirmarTotp', 'SeguridadControlador', 'Si el código de la app coincide, ACTIVA el 2FA (two_factor_enabled = 1)', 'seguridad / index', 'SeguridadControlador.php · confirmarTotp() · línea 60', 'Totp.php + usuarios'],
    ['seguridad / quitarTotp', 'SeguridadControlador', 'Quita el secreto y desactiva: MFA vuelve a ser por correo', 'seguridad / index', 'SeguridadControlador.php · quitarTotp() · línea 80', 'usuarios'],
    ['seguridad / cambiarContrasena', 'SeguridadControlador', 'Cambia la contraseña pidiendo la actual', 'seguridad / index', 'SeguridadControlador.php · cambiarContrasena() · línea 91', 'UsuarioModelo'],
];
?>
<h1>Mapa del sistema — todas las rutas</h1>
<p class="ayuda"><strong>Cómo actúa GET:</strong> solo LEE y muestra pantallas. Ningún GET crea, edita ni borra datos (el Front Controller bloquea las acciones de cambio si llegan por GET).<br>
<strong>Cómo actúa POST:</strong> es el único método que CAMBIA la base de datos. Todo POST lleva token CSRF y termina en una redirección (patrón Post/Redirect/Get): así recargar la página nunca duplica un guardado.</p>

<h2><span class="chip get">GET</span> Pantallas (<?= count($gets) ?>)</h2>
<table class="tabla">
    <thead><tr><th>Ruta (?c=…&a=…)</th><th>Controlador</th><th>Qué muestra / hace</th><th>Redirige a</th><th>Archivo · método · línea (de esta versión)</th><th>Con qué se conecta</th></tr></thead>
    <tbody>
    <?php foreach ($gets as $g): ?>
        <tr><td><code><?= e($g[0]) ?></code></td><td><?= e($g[1]) ?></td><td><?= e($g[2]) ?></td><td><?= e($g[3]) ?></td><td><code><?= e($g[4]) ?></code></td><td><?= e($g[5]) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2><span class="chip post">POST</span> Acciones que cambian datos (<?= count($posts) ?>)</h2>
<table class="tabla">
    <thead><tr><th>Ruta (?c=…&a=…)</th><th>Controlador</th><th>Qué cambia / valida</th><th>Redirige a</th><th>Archivo · método · línea (de esta versión)</th><th>Con qué se conecta</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $p): ?>
        <tr><td><code><?= e($p[0]) ?></code></td><td><?= e($p[1]) ?></td><td><?= e($p[2]) ?></td><td><?= e($p[3]) ?></td><td><code><?= e($p[4]) ?></code></td><td><?= e($p[5]) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
