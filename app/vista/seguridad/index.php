<?php
/*
 * VISTA: seguridad/index.php — "Mi seguridad" del usuario logueado.
 * Bloques:
 *  1. Google Authenticator: estado, activación con QR + secreto
 *     manual y confirmación con código; desactivación.
 *     El QR se dibuja en el NAVEGADOR con una librería JS (qrcodejs
 *     por CDN): el secreto nunca sale a un servicio externo de QR.
 *     Sin internet, el secreto se puede escribir a mano en la app.
 *  2. Cambiar mi contraseña (pide la actual).
 *  3. Mis sesiones registradas (fecha, IP, dispositivo, método).
 *  4. Respaldo de la BD (solo admin).
 * Variables: $miFila (fila completa del usuario), $sesiones,
 * $secretoPendiente, $uriPendiente.
 */
require __DIR__ . '/../parciales/cabecera.php';
?>
<?php $infoQueHace = 'Tu centro de seguridad: activar el 2FA con QR (Google Authenticator), ver tus sesiones, cambiar tu contraseña y (admin) descargar el respaldo.'; $infoPeticion = 'GET ?c=seguridad&a=index. iniciarTotp/confirmarTotp/quitarTotp/cambiarContrasena son POST; respaldo es GET de descarga.'; $infoNoHace = 'No configura el correo SMTP: eso vive en config/config.php. Tampoco da roles: los roles superiores se dan desde la base de datos.'; $infoArchivo = 'vista/seguridad/index.php + SeguridadControlador::index() + Totp.php'; require __DIR__ . '/../parciales/info_pagina.php'; ?>

<h1>Mi seguridad</h1>

<section class="tarjeta-detalle">
    <h2>🔐 Autenticación en dos pasos (MFA)</h2>
    <?php if (!empty($miFila['two_factor_enabled'])): ?>
        <p>Estado: <strong>Google Authenticator ACTIVADO</strong>. Al ingresar, tu segundo factor es el código de la app (cambia cada 30 segundos).</p>
        <form method="post" action="<?= e(BASE_URL) ?>index.php?c=seguridad&a=quitarTotp" onsubmit="return confirm('¿Desactivar Authenticator? Volverás al código por correo.');">
            <?= campoCsrf() ?>
            <button class="boton peligro" type="submit">Desactivar Authenticator</button>
        </form>
    <?php else: ?>
        <p>Estado: <strong>MFA por código de correo</strong> (el código llega a <?= e($miFila['correo']) ?>, vence en 10 minutos y sirve una vez).</p>
        <p class="ayuda">Puedes reforzarlo con la app <strong>Google Authenticator</strong>: no necesita internet ni cuenta de Google Cloud; la app y el sistema calculan el mismo código con un secreto compartido.</p>
        <?php if ($secretoPendiente): ?>
            <div class="qr-caja">
                <div id="qr"></div>
                <div>
                    <p><strong>Paso 1.</strong> Abre Google Authenticator → «+» → «Escanear código QR».<br>
                    <strong>Sin cámara:</strong> elige «Ingresar clave de configuración» y escribe:<br>
                    Nombre de la cuenta: <code><?= e($miFila['correo']) ?></code><br>
                    Clave: <code class="secreto"><?= e($secretoPendiente) ?></code><br>
                    Tipo: <em>Basada en tiempo</em>.</p>
                    <form method="post" action="<?= e(BASE_URL) ?>index.php?c=seguridad&a=confirmarTotp" class="formulario">
                        <?= campoCsrf() ?>
                        <label><strong>Paso 2.</strong> Escribe el código de 6 dígitos que muestra la app:
                            <input type="text" name="codigo" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" placeholder="123456">
                        </label>
                        <button class="boton" type="submit">Confirmar y activar →</button>
                    </form>
                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>new QRCode(document.getElementById('qr'), { text: <?= json_encode($uriPendiente) ?>, width: 180, height: 180 });</script>
        <?php else: ?>
            <form method="post" action="<?= e(BASE_URL) ?>index.php?c=seguridad&a=iniciarTotp">
                <?= campoCsrf() ?>
                <button class="boton" type="submit">Activar Google Authenticator →</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="tarjeta-detalle">
    <h2>🔑 Cambiar mi contraseña</h2>
    <form method="post" action="<?= e(BASE_URL) ?>index.php?c=seguridad&a=cambiarContrasena" class="formulario">
        <?= campoCsrf() ?>
        <label>Contraseña actual <input type="password" name="contrasena_actual" required autocomplete="current-password"></label>
        <label>Contraseña nueva (8+) <input type="password" name="contrasena_nueva" required minlength="8" autocomplete="new-password"></label>
        <button class="boton" type="submit">Cambiar contraseña (POST)</button>
    </form>
    <?php if (!empty($miFila['google_id'])): ?>
    <p class="ayuda">Tu cuenta está vinculada con Google (puedes entrar con el botón «Entrar con Google»).</p>
    <?php endif; ?>
</section>

<section class="tarjeta-detalle">
    <h2>🕒 Mis sesiones registradas</h2>
    <p class="ayuda">Cada inicio de sesión queda guardado con fecha, IP, dispositivo y método (requerimiento de seguridad del sistema).</p>
    <table class="tabla">
        <thead><tr><th>Fecha y hora</th><th>IP</th><th>Dispositivo / navegador</th><th>Método</th></tr></thead>
        <tbody>
        <?php foreach ($sesiones as $s): ?>
            <tr><td><?= e($s['fecha_hora']) ?></td><td><?= e($s['ip']) ?></td><td class="chico-texto"><?= e($s['dispositivo']) ?></td><td><?= e($s['metodo']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$sesiones): ?><tr><td colspan="4">Todavía no hay sesiones registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>

<?php if ($miFila['rol'] === 'admin'): ?>
<section class="tarjeta-detalle">
    <h2>💾 Respaldo del sistema (solo admin)</h2>
    <p class="ayuda">Descarga un respaldo <code>.sql</code> completo de la base de datos generado ahora mismo. Guárdalo fuera de esta PC. Para restaurarlo: phpMyAdmin → Importar.</p>
    <p><a class="boton" href="<?= e(BASE_URL) ?>index.php?c=seguridad&a=respaldo">Descargar respaldo .sql →</a></p>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../parciales/pie.php'; ?>
