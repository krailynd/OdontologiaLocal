-- =====================================================================
--  Odontologia Local — Consultas de ejemplo COMENTADAS (para practicar y
--  para mostrar en la exposición cómo se consulta la base de datos)
--  Úsalas en phpMyAdmin -> pestaña SQL, con la base odontologia_local elegida.
-- =====================================================================
USE odontologia_local;

-- 1) Pacientes con su correo (une pacientes con su cuenta de usuario)
SELECT p.id, p.dni, u.nombre_completo, u.correo, u.telefono
FROM pacientes p
JOIN usuarios u ON u.id = p.usuario_id;

-- 2) Buscar un paciente por DNI (la búsqueda del sistema usa LIKE
--    también para nombre y correo)
SELECT * FROM pacientes WHERE dni = '45678912';

-- 3) Citas de un odontólogo en una fecha, ordenadas por hora
--    (esta consulta es la base de la regla "no dos citas a la misma hora")
SELECT * FROM citas
WHERE odontologo_id = 2 AND fecha = CURDATE()
  AND estado <> 'cancelada'
ORDER BY hora;

-- 4) Historia clínica COMPLETA de un paciente: su historia única y
--    TODOS sus registros de atención con el odontólogo que atendió
SELECT h.id AS historia, r.fecha_atencion, r.diagnostico,
       r.tratamiento, r.medicamentos, r.costo, u.nombre_completo AS odontologo
FROM historias_clinicas h
JOIN registros_atencion r ON r.historia_id = h.id
JOIN usuarios u ON u.id = r.odontologo_id
WHERE h.paciente_id = 1
ORDER BY r.fecha_atencion;

-- 5) Facturas PENDIENTES de un paciente (lo que ve el rol Paciente)
SELECT f.numero, f.fecha_emision, f.total, f.estado
FROM facturas f
WHERE f.paciente_id = 1 AND f.estado = 'pendiente';

-- 6) Total facturado por mes (reporte del Administrador)
SELECT DATE_FORMAT(fecha_emision, '%Y-%m') AS mes,
       COUNT(*) AS comprobantes, SUM(total) AS total_mes
FROM facturas
WHERE estado = 'pagada'
GROUP BY mes;

-- 7) Sesiones registradas de un usuario (auditoría de accesos)
SELECT fecha_hora, ip, dispositivo, metodo
FROM sesiones WHERE usuario_id = 1 ORDER BY fecha_hora DESC;

-- 8) Usuarios bloqueados ahora mismo (bloqueo tras 3 intentos)
SELECT id, nombre_completo, correo, intentos_fallidos, bloqueado_hasta
FROM usuarios WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW();
