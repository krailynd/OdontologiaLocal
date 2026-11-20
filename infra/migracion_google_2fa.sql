-- =====================================================================
--  Odontologia Local — MIGRACIÓN: campos de Google y de 2FA en usuarios
--  Sintaxis MySQL estándar (corre igual en MySQL 5.7/8 y en el
--  MariaDB que XAMPP trae como su «MySQL»: es el mismo SQL).
-- =====================================================================
--  ¿CUÁNDO SE USA ESTE ARCHIVO?
--  - Si importas la base desde cero con base_de_datos.sql (versión
--    nueva), NO necesitas esta migración: ya viene aplicada.
--  - Si YA tenías la base creada con la versión anterior del
--    proyecto, importa SOLO este archivo una vez (phpMyAdmin ->
--    Importar) y tu tabla usuarios queda actualizada sin perder datos.
--  - Se corre UNA sola vez: si lo corres dos veces, MySQL avisará
--    que las columnas ya existen (error inofensivo, no rompe nada).
--
--  QUÉ AGREGA / RENOMBRA:
--    google_id          -> ID único de la cuenta de Google (ya existía
--                          en la v1; se deja por si tu base era anterior)
--    avatar             -> URL de la foto de perfil que devuelve Google
--    totp_secret        -> el secreto Base32 del 2FA (antes se llamaba
--                          mfa_secreto: aquí se RENOMBRA, no se pierde)
--    two_factor_enabled -> 1 cuando el usuario YA CONFIRMÓ su 2FA con
--                          un código válido; 0 mientras no lo activa.
-- =====================================================================
USE odontologia_local;

-- 1) google_id (login con Google) YA EXISTE desde la versión 1,
--    por eso aquí NO se agrega (si se agregara otra vez, MySQL
--    detendría la importación con «Duplicate column name»).
--    Solo si tu base fuera más antigua que la v1 y no lo tuviera,
--    ejecuta esta línea suelta en la pestaña SQL de phpMyAdmin:
--    ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(100) NULL UNIQUE;

-- 2) avatar — foto de perfil de Google (picture del userinfo)
ALTER TABLE usuarios
    ADD COLUMN avatar VARCHAR(255) NULL;

-- 3) totp_secret — renombra mfa_secreto conservando los secretos ya
--    guardados. Si tu base NUNCA tuvo mfa_secreto, usa en su lugar:
--    ALTER TABLE usuarios ADD COLUMN totp_secret VARCHAR(64) NULL;
ALTER TABLE usuarios
    CHANGE COLUMN mfa_secreto totp_secret VARCHAR(64) NULL;

-- 4) two_factor_enabled — interruptor del 2FA confirmado
ALTER TABLE usuarios
    ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0;

-- 5) Coherencia inicial: quien ya tenía secreto TOTP confirmado en
--    la v1 queda con el 2FA marcado como activado.
UPDATE usuarios SET two_factor_enabled = 1 WHERE totp_secret IS NOT NULL;

-- Comprobación: deben verse google_id, avatar, totp_secret y
-- two_factor_enabled en la lista de columnas de usuarios.
SHOW COLUMNS FROM usuarios;
