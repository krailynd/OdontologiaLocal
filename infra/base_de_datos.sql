-- =====================================================================
--  Odontologia Local — Sistema Integral de Gestión para Clínica Odontológica
--  Base de datos COMPLETA y NORMALIZADA en SQL MySQL estándar.
--  Nota: XAMPP, al pulsar «MySQL» en su panel, arranca MariaDB,
--  que es 100% compatible con este SQL (y este SQL también corre
--  en un MySQL 5.7/8 puro). No hay que instalar nada extra.
-- =====================================================================
--  CÓMO IMPORTAR (phpMyAdmin de XAMPP):
--    1. Enciende Apache y MySQL en XAMPP.
--    2. Entra a http://localhost/phpmyadmin
--    3. Pestaña "Importar" -> elige este archivo -> "Continuar".
--       (El archivo crea la base de datos solo; no necesitas crearla.)
--
--  ORIGEN: este esquema parte del ANEXO del caso de estudio (PC_3):
--  usuarios, codigos_mfa, pacientes, citas e historias_clinicas vienen
--  del PDF TAL CUAL. Sobre esa base se AGREGARON solo las tablas y
--  columnas que los propios requerimientos del PDF exigen y que el
--  anexo no traía:
--    * Bloqueo tras 3 intentos      -> usuarios.intentos_fallidos
--                                      y usuarios.bloqueado_hasta
--    * Cambio obligatorio 1ra vez   -> usuarios.debe_cambiar_contrasena
--    * Google Authenticator (TOTP)  -> usuarios.totp_secret
--    * Login con Google (OAuth)     -> usuarios.google_id
--    * Registro de sesiones         -> tabla sesiones
--    * Enlace de UN SOLO USO        -> tabla tokens_recuperacion
--    * Historia ÚNICA por paciente
--      con MÚLTIPLES atenciones     -> historias_clinicas (única)
--                                      + registros_atencion (muchos)
--      (En el anexo, "historias_clinicas" mezclaba la historia con
--       cada atención en una sola tabla; aquí se separa para cumplir
--       la regla "una única historia con múltiples registros". Las
--       columnas del anexo viven ahora en registros_atencion.)
--    * Catálogo de tratamientos     -> tabla tratamientos
--    * Facturación / comprobantes   -> tablas facturas y detalle_factura
--    * Notificaciones por correo    -> tabla notificaciones (registro)
--
--  Normalización: sin datos repetidos (el paciente se guarda una vez,
--  el precio vive en el catálogo, cada atención referencia su historia
--  y su tratamiento), claves foráneas con integridad referencial y
--  valores atómicos en cada columna. Motor InnoDB, utf8mb4.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS odontologia_local
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE odontologia_local;

-- ---------------------------------------------------------------------
-- 1) usuarios — viene del anexo del PDF + columnas de seguridad
--    Roles: admin, odontologo, recepcionista, paciente.
--    estado = 1 activo / 0 desactivado (NUNCA se borra el registro).
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_completo VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,              -- SIEMPRE hash (password_hash), nunca texto plano
    rol ENUM('admin','odontologo','recepcionista','paciente') NOT NULL,
    telefono VARCHAR(20),
    estado TINYINT(1) DEFAULT 1,
    mfa_activo TINYINT(1) DEFAULT 1,
    -- Columnas agregadas para cumplir los requerimientos del PDF:
    totp_secret VARCHAR(64) NULL,                  -- secreto TOTP (Base32) del 2FA con Authenticator (NULL = aún no generado)
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0, -- 1 = el usuario YA confirmó su 2FA con un código válido
    google_id VARCHAR(100) NULL UNIQUE,            -- ID de la cuenta Google vinculada (login con Google / OAuth)
    avatar VARCHAR(255) NULL,                      -- URL de la foto de perfil que devuelve Google (picture)
    intentos_fallidos INT NOT NULL DEFAULT 0,      -- contador para el bloqueo tras 3 intentos
    bloqueado_hasta DATETIME NULL,                 -- si NOW() < bloqueado_hasta, la cuenta está bloqueada
    debe_cambiar_contrasena TINYINT(1) NOT NULL DEFAULT 1, -- 1 = cambio obligatorio al primer ingreso
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2) codigos_mfa — TAL CUAL el anexo del PDF.
--    Código de 6 dígitos enviado por correo como segundo factor.
--    La app guarda el HASH SHA-256 del código, no el código en claro.
--    usado = 1 -> ese código ya no sirve otra vez (un solo uso).
-- ---------------------------------------------------------------------
CREATE TABLE codigos_mfa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    codigo VARCHAR(255) NOT NULL,                  -- hash del código (el anexo decía VARCHAR(10); se amplía para el hash)
    expiracion DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3) tokens_recuperacion — enlace de restablecimiento de UN SOLO USO.
--    Se guarda solo el HASH del token: el enlace que viaja por correo
--    lleva el token original; al usarlo, usado pasa a 1 y el mismo
--    enlace queda muerto para siempre. Expira a los 30 minutos.
-- ---------------------------------------------------------------------
CREATE TABLE tokens_recuperacion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expiracion DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4) sesiones — registro seguro de sesiones (requerimiento del PDF):
--    fecha, hora, IP y dispositivo de cada inicio de sesión.
-- ---------------------------------------------------------------------
CREATE TABLE sesiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) NOT NULL,
    dispositivo VARCHAR(255) NOT NULL,             -- User-Agent del navegador/dispositivo
    metodo ENUM('password','google') NOT NULL DEFAULT 'password', -- cómo entró
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5) pacientes — TAL CUAL el anexo del PDF.
--    usuario_id es UNIQUE: un paciente tiene UNA cuenta de usuario.
--    dni es UNIQUE: no pueden existir dos pacientes con el mismo DNI.
-- ---------------------------------------------------------------------
CREATE TABLE pacientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE,
    dni VARCHAR(15) UNIQUE NOT NULL,
    fecha_nacimiento DATE,
    direccion TEXT,
    antecedentes TEXT,                             -- antecedentes médicos
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6) tratamientos — catálogo de precios (requerimiento del PDF).
--    El descuento es un porcentaje de 0 a 100 aplicable al precio.
-- ---------------------------------------------------------------------
CREATE TABLE tratamientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0, -- ej. 10.00 = 10% de descuento
    estado TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7) citas — TAL CUAL el anexo del PDF.
--    Las reglas de horario, doble reserva y cancelación anticipada se
--    validan en el MODELO (CitaModelo), no solo en la pantalla.
-- ---------------------------------------------------------------------
CREATE TABLE citas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paciente_id INT NOT NULL,
    odontologo_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo TEXT,
    estado ENUM('pendiente','confirmada','atendida','cancelada') DEFAULT 'pendiente',
    observaciones_cancelacion TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (odontologo_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8) historias_clinicas — UNA SOLA historia por paciente.
--    paciente_id es UNIQUE: es imposible crearle dos historias.
-- ---------------------------------------------------------------------
CREATE TABLE historias_clinicas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paciente_id INT NOT NULL UNIQUE,
    fecha_apertura DATE NOT NULL,
    observaciones_generales TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9) registros_atencion — los MÚLTIPLES registros de la historia.
--    Trae las columnas del anexo del PDF (diagnóstico, tratamiento,
--    medicamentos, observaciones) + costo y cita asociada.
-- ---------------------------------------------------------------------
CREATE TABLE registros_atencion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    historia_id INT NOT NULL,
    cita_id INT NULL,                              -- atención ligada a una cita (opcional)
    odontologo_id INT NOT NULL,
    fecha_atencion DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    tratamiento_id INT NULL,                       -- tratamiento del catálogo (opcional)
    tratamiento TEXT NOT NULL,                     -- nombre/descripción del tratamiento aplicado
    medicamentos TEXT,
    observaciones TEXT,
    costo DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (historia_id) REFERENCES historias_clinicas(id),
    FOREIGN KEY (cita_id) REFERENCES citas(id),
    FOREIGN KEY (odontologo_id) REFERENCES usuarios(id),
    FOREIGN KEY (tratamiento_id) REFERENCES tratamientos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10) facturas — comprobantes emitidos por recepción/admin.
--     estado: pendiente (por pagar), pagada, anulada.
-- ---------------------------------------------------------------------
CREATE TABLE facturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(20) NOT NULL UNIQUE,            -- ej. F001-000001
    paciente_id INT NOT NULL,
    emitido_por INT NOT NULL,                      -- usuario recepcionista/admin que la emitió
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente','pagada','anulada') NOT NULL DEFAULT 'pendiente',
    metodo_pago VARCHAR(30) NULL,                  -- efectivo, tarjeta, yape, etc. (al pagar)
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (emitido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11) detalle_factura — líneas de la factura (1 factura, N líneas).
--     Cada línea referencia un tratamiento del catálogo.
-- ---------------------------------------------------------------------
CREATE TABLE detalle_factura (
    id INT PRIMARY KEY AUTO_INCREMENT,
    factura_id INT NOT NULL,
    tratamiento_id INT NULL,
    descripcion VARCHAR(150) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
    importe DECIMAL(10,2) NOT NULL DEFAULT 0,      -- cantidad * precio * (1 - descuento)
    FOREIGN KEY (factura_id) REFERENCES facturas(id),
    FOREIGN KEY (tratamiento_id) REFERENCES tratamientos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12) notificaciones — registro de correos del sistema (citas, MFA no:
--     el MFA va por codigos_mfa). En modo desarrollo los correos se
--     guardan aquí y en runtime/correos.log porque XAMPP local no
--     envía correo real sin configurar sendmail/SMTP.
-- ---------------------------------------------------------------------
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    destinatario_correo VARCHAR(100) NOT NULL,
    asunto VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'cita',      -- cita, recuperacion, sistema
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviado TINYINT(1) NOT NULL DEFAULT 0          -- 1 si mail() lo aceptó, 0 si quedó solo registrado
) ENGINE=InnoDB;

-- =====================================================================
--  DATOS SEMILLA (para probar el sistema apenas se importa)
--  Contraseñas (todas con hash, generadas con password_hash):
--    admin@odontologialocal.pe      / Admin123*      (debe cambiarla: NO, ya es suya)
--    dr.perez@odontologialocal.pe   / Odonto123*
--    recepcion@odontologialocal.pe  / Recep123*
--    paciente@odontologialocal.pe / Paciente123*
--  OJO: son cuentas de PRUEBA para tu práctica. En una clínica real
--  se crean usuarios propios y estas se desactivan.
-- =====================================================================
INSERT INTO usuarios (nombre_completo, correo, contrasena, rol, telefono, estado, mfa_activo, debe_cambiar_contrasena) VALUES
('Administradora General', 'admin@odontologialocal.pe',      '$2y$10$4wBVVr.K/iUHHZpydx0Ws.Ye/9TgiTqKZ5hePdBW9QTq5cFrdFzyG', 'admin',         '999111222', 1, 1, 0),
('Dr. Juan Pérez',         'dr.perez@odontologialocal.pe',   '$2y$10$ak3XleV7aCm6elAsJrpvluA1Zjf2HJtRAp8lz6rsMDdplKQPn3JaW', 'odontologo',    '999333444', 1, 1, 0),
('Recepción Clínica',      'recepcion@odontologialocal.pe',  '$2y$10$pIcinXJAYipJoOf.qRaFgezZGOLQyMQEpDKiFyKLgbF7A3x.iY6Qe', 'recepcionista', '999555666', 1, 1, 0),
('María Torres',           'paciente@odontologialocal.pe',   '$2y$10$.JdVxGrQJb3A1B6NCuuT3uGyVYHnpCpKHWvPN58FspEeuANWKcuwO', 'paciente',      '999777888', 1, 1, 0);

-- NOTA SOBRE CUENTAS DE GOOGLE CON ROLES SUPERIORES:
-- Las cuentas nuevas que ingresan con Google nacen como PACIENTE.
-- Si deseas asignar rol de ADMIN u ODONTÓLOGO a un correo de Google,
-- crea el registro en esta tabla con ese correo y rol, o usa el script
-- infra/roles_google.sql. Al ingresar por primera vez con Google,
-- el sistema vinculará su cuenta y preservará el rol asignado.

-- La paciente María Torres con su ficha de paciente:
INSERT INTO pacientes (usuario_id, dni, fecha_nacimiento, direccion, antecedentes) VALUES
(4, '45678912', '1995-04-12', 'Av. Los Álamos 123, Ica', 'Alergia a la penicilina. Sin cirugías previas.');

-- Su historia clínica ÚNICA (se crea vacía; las atenciones van aparte):
INSERT INTO historias_clinicas (paciente_id, fecha_apertura, observaciones_generales) VALUES
(1, CURDATE(), 'Historia abierta al registrar a la paciente.');

-- Catálogo de tratamientos con precios y un descuento de ejemplo:
INSERT INTO tratamientos (nombre, descripcion, precio, descuento_pct, estado) VALUES
('Consulta general',        'Evaluación y diagnóstico inicial',              50.00,  0,  1),
('Limpieza dental',         'Profilaxis completa con ultrasonido',           120.00, 10, 1),
('Curación con resina',     'Restauración de una pieza con resina compuesta',180.00, 0,  1),
('Extracción simple',       'Extracción de una pieza sin cirugía',           150.00, 0,  1),
('Endodoncia unirradicular','Tratamiento de conducto de una raíz',           450.00, 5,  1),
('Blanqueamiento dental',   'Blanqueamiento con luz LED, una sesión',        350.00, 15, 1);

-- =====================================================================
--  COMPROBACIÓN FINAL: lista las tablas creadas (deben ser 12).
-- =====================================================================
SHOW TABLES;
