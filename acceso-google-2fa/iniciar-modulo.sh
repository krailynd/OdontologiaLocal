#!/usr/bin/env bash
# Módulo independiente acceso-google-2fa en LINUX.
# Uso: ./iniciar-modulo.sh  →  http://localhost:3000/login.php
# La URI de retorno de este módulo (http://localhost:3000/google-callback.php)
# ya está registrada en Google Cloud.
cd "$(dirname "$0")"
echo "Módulo Google + 2FA en http://localhost:3000/login.php  (Ctrl+C para detener)"
php -S localhost:3000 -t .
