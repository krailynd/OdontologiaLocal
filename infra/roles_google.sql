-- =====================================================================
--  Odontologia Local — ROLES DE LAS CUENTAS DE GOOGLE (desde la base)
-- =====================================================================
--  REGLA DEL SISTEMA (pedida por Elvis):
--  - Entrar con Google SOLO prueba la identidad y, si el correo es
--    nuevo, crea la cuenta como PACIENTE. Google NUNCA da roles.
--  - Los roles superiores (admin, odontólogo, recepcionista) se dan
--    DESDE LA BASE DE DATOS (este archivo) o creando antes la cuenta
--    en el panel de Usuarios con ese correo y ese rol: al entrar con
--    Google, el sistema encuentra el correo y CONSERVA su rol.
--  - El cambio de rol se aplica en el SIGUIENTE inicio de sesión.
--
--  CÓMO SE USA: en phpMyAdmin -> pestaña SQL (o por terminal),
--  descomenta y ajusta el UPDATE del rol que quieras dar, con el
--  correo EXACTO de la cuenta de Google de la persona.
-- =====================================================================
USE odontologia_local;

-- Ver primero quién es quién (correo, rol actual y si tiene Google):
SELECT id, nombre_completo, correo, rol, (google_id IS NOT NULL) AS entra_con_google
FROM usuarios ORDER BY id;

-- ---------------------------------------------------------------------
-- EJEMPLOS (descomenta el que necesites y cambia el correo):
-- ---------------------------------------------------------------------

-- Dar rol ADMIN a una cuenta de Google:
-- UPDATE usuarios SET rol = 'admin' WHERE correo = 'tu-admin@ejemplo.com';

-- Dar rol ODONTÓLOGO a una cuenta de Google:
-- UPDATE usuarios SET rol = 'odontologo' WHERE correo = 'tu-doctor@ejemplo.com';

-- Dar rol RECEPCIONISTA a una cuenta de Google:
-- UPDATE usuarios SET rol = 'recepcionista' WHERE correo = 'tu-recepcion@ejemplo.com';

-- Volver a alguien a PACIENTE:
-- UPDATE usuarios SET rol = 'paciente' WHERE correo = 'tu-paciente@ejemplo.com';

-- ---------------------------------------------------------------------
-- Comprobación después de cualquier cambio:
SELECT correo, rol, google_id FROM usuarios ORDER BY rol, correo;
