#!/usr/bin/env python3
"""
generar_documentacion.py — Generador de la documentación técnica en PDF (19 páginas)
Proyecto: OdontologiaLocal
Genera Documentacion-Odontologia-Local.pdf usando ReportLab sin datos privados ni credenciales.
"""

import sys
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, Preformatted
)
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_header_footer(num_pages)
            super().showPage()
        super().save()

    def draw_header_footer(self, page_count):
        self.saveState()
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#4a5568"))
        header_text = f"Odontologia Local — Documentación completa del proyecto · Página {self._pageNumber}"
        self.drawString(36, 815, header_text)
        self.setStrokeColor(colors.HexColor("#cbd5e0"))
        self.setLineWidth(0.5)
        self.line(36, 808, 559, 808)
        self.restoreState()

def build_pdf(filename="Documentacion-Odontologia-Local.pdf"):
    doc = SimpleDocTemplate(
        filename,
        pagesize=A4,
        leftMargin=36,
        rightMargin=36,
        topMargin=46,
        bottomMargin=36
    )

    styles = getSampleStyleSheet()
    
    # Estilos tipográficos
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=18,
        leading=22,
        textColor=colors.HexColor("#1a365d"),
        spaceAfter=4
    )
    
    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        textColor=colors.HexColor("#2b6cb0"),
        spaceAfter=4
    )

    subsub_style = ParagraphStyle(
        'DocSubSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=13,
        textColor=colors.HexColor("#4a5568"),
        spaceAfter=8
    )

    h1_style = ParagraphStyle(
        'Heading1_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        textColor=colors.HexColor("#1a365d"),
        spaceBefore=8,
        spaceAfter=4
    )

    h2_style = ParagraphStyle(
        'Heading2_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9.5,
        leading=12.5,
        textColor=colors.HexColor("#2d3748"),
        spaceBefore=6,
        spaceAfter=3
    )

    body_style = ParagraphStyle(
        'Body_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8,
        leading=10.5,
        textColor=colors.HexColor("#2d3748"),
        spaceAfter=4
    )

    bullet_style = ParagraphStyle(
        'Bullet_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8,
        leading=10.5,
        textColor=colors.HexColor("#2d3748"),
        leftIndent=10,
        firstLineIndent=-6,
        spaceAfter=3
    )

    code_style = ParagraphStyle(
        'Code_Custom',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=7,
        leading=8.5,
        textColor=colors.HexColor("#1a202c")
    )

    table_cell = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7,
        leading=9,
        textColor=colors.HexColor("#2d3748")
    )

    table_cell_bold = ParagraphStyle(
        'TableCellBold',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=7,
        leading=9,
        textColor=colors.HexColor("#1a365d")
    )

    table_header = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=7.5,
        leading=9.5,
        textColor=colors.white
    )

    story = []

    # =========================================================================
    # PÁGINA 1: Portada y Ficha Técnica
    # =========================================================================
    story.append(Paragraph("Odontologia Local", title_style))
    story.append(Paragraph("Sistema Integral de Gestión para Clínica Odontológica", subtitle_style))
    story.append(Paragraph("Documentación completa del proyecto", subsub_style))
    story.append(Paragraph(
        "Solución web en PHP puro + MySQL + arquitectura MVC, sin frameworks y sin Composer, "
        "construida desde el caso de estudio PC_3: autenticación con MFA, Google Authenticator, login con "
        "Google (OAuth), roles de usuario, reglas de negocio de citas, historias clínicas, tratamientos y "
        "facturación. Todo pensado para correr en XAMPP (carpeta htdocs).",
        body_style
    ))
    story.append(Spacer(1, 8))

    meta_data = [
        [Paragraph("Dato", table_header), Paragraph("Detalle", table_header)],
        [Paragraph("Caso de estudio", table_cell_bold), Paragraph("PC_3 — «Odontologia Local — Sistema Integral de Gestión para Clínica Odontológica» (UPSJB)", table_cell)],
        [Paragraph("Tecnologías", table_cell_bold), Paragraph("PHP 8 (puro, sin frameworks), MySQL (SQL estándar; XAMPP lo trae integrado), HTML + CSS propio. Sin Composer.", table_cell)],
        [Paragraph("Arquitectura", table_cell_bold), Paragraph("MVC en español: app/modelo, app/controlador, app/vista + Front Controller (index.php)", table_cell)],
        [Paragraph("Dónde corre", table_cell_bold), Paragraph("XAMPP: Apache + MySQL, carpeta OdontologiaLocal dentro de htdocs, base importada en phpMyAdmin", table_cell)],
        [Paragraph("Qué acompaña", table_cell_bold), Paragraph("El ZIP del proyecto (código completo) + infra/base_de_datos.sql con TODO el SQL comentado", table_cell)]
    ]
    t_meta = Table(meta_data, colWidths=[110, 413])
    t_meta.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('LEFTPADDING', (0,0), (-1,-1), 6),
        ('RIGHTPADDING', (0,0), (-1,-1), 6),
    ]))
    story.append(t_meta)
    story.append(Spacer(1, 10))

    story.append(Paragraph(
        "<b>Cómo leer este documento:</b> el capítulo 1 es el plan de trabajo (qué se hace primero y por qué). El 2 "
        "explica la estructura archivo por archivo. El 3, la base de datos completa. El 4, cómo actúan GET y "
        "POST. El 5 es el más importante para Elvis: la autenticación entera, con la guía paso a paso de "
        "Google Cloud y la aclaración de qué es Google Authenticator (que NO usa Google Cloud). El 6 explica "
        "cada módulo. El 9 trae la instalación en XAMPP y el 10, qué se probó de verdad y qué falta probar con "
        "tus credenciales.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 2: 1. El plan y 2. Estructura del proyecto
    # =========================================================================
    story.append(Paragraph("1. El plan: qué se hace primero y en qué orden", h1_style))
    story.append(Paragraph(
        "El proyecto se construye en este orden, y así quedó construido. Cada paso se apoya en el anterior: "
        "sin base de datos no hay modelos; sin modelos no hay controladores; sin autenticación no se protegen los módulos.",
        body_style
    ))
    story.append(Paragraph("El orden de construcción", h2_style))
    pasos = [
        "<b>• Paso 1 — Leer el caso completo.</b> Del PDF se extraen: los 4 roles, los módulos (autenticación con MFA, usuarios, pacientes, citas, historias, tratamientos, facturación) y las reglas de negocio. Nada se inventa fuera del caso: lo que el anexo trae se respeta y lo que el caso exige pero el anexo no trae, se completa y se marca como agregado.",
        "<b>• Paso 2 — Diseñar la base de datos normalizada (infra/base_de_datos.sql):</b> primero el SQL, porque todo el sistema lee y escribe ahí. Se importa en phpMyAdmin y se comprueba que cree las 12 tablas sin errores.",
        "<b>• Paso 3 — La base del código:</b> config/config.php (única configuración), la conexión PDO y el Front Controller (index.php), que es la única puerta de entrada.",
        "<b>• Paso 4 — Los modelos</b> (todo el SQL de la aplicación, un modelo por tabla o por tema).",
        "<b>• Paso 5 — La autenticación completa</b> (AuthControlador + SeguridadModelo + ayudantes Totp, Correo y Google), porque todos los demás módulos dependen de saber quién entró y con qué rol.",
        "<b>• Paso 6 — Los módulos de negocio en este orden:</b> usuarios, pacientes, citas (con sus reglas), historias, tratamientos, facturación, panel por rol y mapa del sistema.",
        "<b>• Paso 7 — Las vistas</b> de cada módulo con el menú principal y los mensajes de error/éxito (flash).",
        "<b>• Paso 8 — Verificación real:</b> sintaxis de cada PHP, importación del SQL en una base real y pruebas de los flujos completos por HTTP (capítulo 13).",
        "<b>• Paso 9 — Instalación en tu XAMPP</b> (capítulo 9) y, cuando quieras, activar el login con Google con TUS credenciales (capítulo 5.4)."
    ]
    for p in pasos:
        story.append(Paragraph(p, bullet_style))
    
    story.append(Spacer(1, 4))
    story.append(Paragraph("Qué necesitas tener instalado", h2_style))
    story.append(Paragraph(
        "Solo XAMPP (Apache + MySQL + PHP 8). Nada más: el proyecto no usa Composer, ni Node, ni ningún framework, "
        "así que copiar la carpeta a htdocs es toda la «instalación» del código.",
        body_style
    ))
    story.append(Spacer(1, 4))
    story.append(Paragraph("2. Estructura del proyecto: qué archivo va en cada lugar", h1_style))
    story.append(Paragraph(
        "La regla MVC que sigue todo el proyecto: el Modelo es el único que habla con la base de datos (SQL). "
        "El Controlador recibe la petición, revisa permisos y reglas, pide datos al modelo y elige la vista. "
        "La Vista solo muestra: no contiene SQL ni decide nada. Si una vista necesitara un dato que no tiene, es el controlador quien debe pasárselo.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 3: Estructura de archivos y Tabla archivo por archivo (Parte 1)
    # =========================================================================
    tree_text = """OdontologiaLocal/
├── index.php <- Front Controller: ÚNICA puerta de entrada
├── INSTALACION.md <- instalación en 4 pasos
├── config/
│   └── config.php <- BD, modo dev y TUS credenciales de Google
├── app/
│   ├── modelo/ <- TODO el SQL (8 archivos)
│   ├── controlador/ <- permisos, reglas y flujo (10 archivos)
│   ├── vista/ <- solo HTML (una carpeta por módulo)
│   └── ayudantes/ <- Funciones, Totp, Correo, Google
├── infra/
│   ├── base_de_datos.sql <- la BD completa comentada (importar en phpMyAdmin)
│   └── consultas_ejemplos.sql <- consultas comentadas para practicar
├── assets/css/estilos.css <- diseño propio, sin frameworks CSS
└── runtime/correos.log <- «correos» en modo desarrollo (se crea solo)"""
    story.append(Preformatted(tree_text, code_style))
    story.append(Spacer(1, 6))

    story.append(Paragraph("Archivo por archivo", h2_style))

    files_p3 = [
        [Paragraph("Archivo", table_header), Paragraph("Capa", table_header), Paragraph("Qué contiene / qué hace", table_header)],
        [Paragraph("index.php", table_cell_bold), Paragraph("Entrada", table_cell), Paragraph("Front Controller: lee ?c=controlador&a=acción, bloquea acciones de cambio si llegan por GET, carga el controlador y ejecuta el método.", table_cell)],
        [Paragraph("config/config.php", table_cell_bold), Paragraph("Config", table_cell), Paragraph("Host/usuario de MySQL, MODO_DEV, nombre de la app y GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET. URL base automática según la carpeta en htdocs.", table_cell)],
        [Paragraph("modelo/Conexion.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("La única conexión PDO (Singleton) con sentencias preparadas reales.", table_cell)],
        [Paragraph("modelo/UsuarioModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("SQL de usuarios + intentos fallidos y bloqueo, hash de contraseñas, secreto TOTP, vínculo Google.", table_cell)],
        [Paragraph("modelo/SeguridadModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("SQL de códigos MFA, tokens de recuperación de un solo uso, registro de sesiones y notificaciones.", table_cell)],
        [Paragraph("modelo/PacienteModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("Modelo SQL de pacientes unido a su usuario; búsqueda por nombre, DNI o correo.", table_cell)],
        [Paragraph("modelo/CitaModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("SQL de citas + LAS REGLAS DE NEGOCIO (horario, doble reserva, cancelación 2 h).", table_cell)],
        [Paragraph("modelo/HistoriaModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("Historia única por paciente y sus registros de atención (relación 1 a N).", table_cell)],
        [Paragraph("modelo/TratamientoModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("Catálogo de tratamientos con precio y descuento; cálculo de importes.", table_cell)],
        [Paragraph("modelo/FacturaModelo.php", table_cell_bold), Paragraph("Modelo", table_cell), Paragraph("Facturas y su detalle en transacción; número correlativo F001-000001…", table_cell)],
        [Paragraph("controlador/AuthControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Login, MFA (correo y Authenticator), Google OAuth, bloqueo, recuperación de un solo uso, cambio obligatorio, salir.", table_cell)],
        [Paragraph("controlador/PanelControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("El inicio distinto para cada rol.", table_cell)]
    ]
    t_f1 = Table(files_p3, colWidths=[120, 55, 348])
    t_f1.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_f1)
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 4: Archivo por archivo (Parte 2)
    # =========================================================================
    files_p4 = [
        [Paragraph("Archivo", table_header), Paragraph("Capa", table_header), Paragraph("Qué contiene / qué hace", table_header)],
        [Paragraph("controlador/UsuarioControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("CRUD de usuarios (solo admin), roles, activar/desactivar sin eliminar.", table_cell)],
        [Paragraph("controlador/PacienteControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Pacientes: registro, edición, búsqueda, ficha e historial. El paciente solo ve lo suyo.", table_cell)],
        [Paragraph("controlador/CitaControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Citas por rol, agendar validando reglas, confirmar/atender/cancelar y notificar por correo.", table_cell)],
        [Paragraph("controlador/HistoriaControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Ver la historia y registrar atenciones (odontólogo/admin).", table_cell)],
        [Paragraph("controlador/TratamientoControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Catálogo: crear, editar, activar/desactivar.", table_cell)],
        [Paragraph("controlador/FacturaControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("Emitir (precios leídos del catálogo en el servidor), ver, cobrar y anular.", table_cell)],
        [Paragraph("controlador/SeguridadControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("«Mi seguridad»: activar/quitar Authenticator, mis sesiones, mi contraseña y el respaldo .sql (admin).", table_cell)],
        [Paragraph("controlador/MenuControlador.php", table_cell_bold), Paragraph("Controlador", table_cell), Paragraph("El mapa del sistema: todas las rutas GET y POST explicadas.", table_cell)],
        [Paragraph("ayudantes/Funciones.php", table_cell_bold), Paragraph("Ayudante", table_cell), Paragraph("e() anti-XSS, redirigir(), mensajes flash, requiereLogin() y requiereRol(), token CSRF, IP y dispositivo.", table_cell)],
        [Paragraph("ayudantes/Totp.php", table_cell_bold), Paragraph("Ayudante", table_cell), Paragraph("Google Authenticator en PHP puro (TOTP RFC 6238): generar secreto, calcular y verificar códigos.", table_cell)],
        [Paragraph("ayudantes/Correo.php", table_cell_bold), Paragraph("Ayudante", table_cell), Paragraph("El único lugar que «envía» correos: mail() en servidor real; en modo dev escribe runtime/correos.log.", table_cell)],
        [Paragraph("ayudantes/Google.php", table_cell_bold), Paragraph("Ayudante", table_cell), Paragraph("Login con Google (OAuth 2.0) con cURL, sin librerías: URL de login, canje del código y perfil verificado.", table_cell)],
        [Paragraph("vista/parciales/", table_cell_bold), Paragraph("Vista", table_cell), Paragraph("cabecera con el menú por rol y pie, en dos versiones: con sesión y sin sesión (login/MFA).", table_cell)],
        [Paragraph("vista/… por módulo", table_cell_bold), Paragraph("Vista", table_cell), Paragraph("Una carpeta por módulo (auth, panel, usuarios, pacientes, citas, historias, tratamientos, facturas, seguridad, menu). Solo muestran datos.", table_cell)]
    ]
    t_f2 = Table(files_p4, colWidths=[125, 55, 343])
    t_f2.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_f2)
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 5: 3. La base de datos (normalizada y completa)
    # =========================================================================
    story.append(Paragraph("3. La base de datos: normalizada y completa", h1_style))
    story.append(Paragraph(
        "El anexo del caso traía 5 tablas. Este proyecto las respeta tal cual y agrega solo lo que los propios "
        "requerimientos del caso exigen y el anexo no traía. Todo el SQL está, comentado línea por línea, en "
        "infra/base_de_datos.sql (es el archivo que se importa en phpMyAdmin).",
        body_style
    ))
    story.append(Paragraph("Las 12 tablas", h2_style))

    tablas = [
        [Paragraph("Tabla", table_header), Paragraph("Origen", table_header), Paragraph("Qué guarda", table_header)],
        [Paragraph("usuarios", table_cell_bold), Paragraph("Anexo + columnas", table_cell), Paragraph("Cuentas de acceso: nombre, correo único, contraseña EN HASH, rol, teléfono, estado (activo/desactivado). Columnas agregadas: totp_secret (Authenticator), google_id (login con Google), intentos_fallidos y bloqueado_hasta (bloqueo), debe_cambiar_contrasena (primer ingreso).", table_cell)],
        [Paragraph("codigos_mfa", table_cell_bold), Paragraph("Anexo", table_cell), Paragraph("Códigos MFA por correo: hash del código, expiración (10 min) y usado (un solo uso).", table_cell)],
        [Paragraph("tokens_recuperacion", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Tokens del enlace de recuperación: solo el HASH del token, expiración 30 min, usado = un solo uso.", table_cell)],
        [Paragraph("sesiones", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Registro seguro de sesiones: fecha/hora, IP, dispositivo y método (password o google).", table_cell)],
        [Paragraph("pacientes", table_cell_bold), Paragraph("Anexo", table_cell), Paragraph("Ficha del paciente: usuario vinculado (único), DNI único, nacimiento, dirección, antecedentes.", table_cell)],
        [Paragraph("citas", table_cell_bold), Paragraph("Anexo", table_cell), Paragraph("Citas: paciente, odontólogo, fecha, hora, motivo, estado (pendiente/confirmada/atendida/cancelada) y motivo de cancelación.", table_cell)],
        [Paragraph("historias_clinicas", table_cell_bold), Paragraph("Anexo, corregida", table_cell), Paragraph("La historia ÚNICA de cada paciente: paciente_id es UNIQUE, es imposible tener dos.", table_cell)],
        [Paragraph("registros_atencion", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Los múltiples registros de la historia: fecha, odontólogo, diagnóstico, tratamiento, medicamentos, observaciones y costo. Aquí viven las columnas que el anexo mezclaba dentro de historias_clinicas.", table_cell)],
        [Paragraph("tratamientos", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Catálogo de precios con descuento aplicable (porcentaje).", table_cell)],
        [Paragraph("facturas", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Comprobantes: número correlativo único, paciente, emisor, subtotal, descuento, total, estado (pendiente/pagada/anulada) y método de pago.", table_cell)],
        [Paragraph("detalle_factura", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Líneas de cada factura: tratamiento, cantidad, precio, descuento e importe.", table_cell)],
        [Paragraph("notificaciones", table_cell_bold), Paragraph("Agregada", table_cell), Paragraph("Registro de los correos del sistema (avisos de citas), para que el flujo quede demostrado aunque el correo local no salga.", table_cell)]
    ]
    t_tab = Table(tablas, colWidths=[90, 80, 353])
    t_tab.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 2.5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2.5),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_tab)
    story.append(Spacer(1, 4))

    story.append(Paragraph("Por qué está normalizada (en corto)", h2_style))
    story.append(Paragraph(
        "Ningún dato se repite sin necesidad: el nombre y el correo del paciente viven una sola vez en usuarios "
        "y su ficha médica en pacientes, unidas por clave foránea. El precio de un tratamiento vive en el "
        "catálogo; la factura guarda sus líneas en detalle_factura en vez de una lista dentro de un texto. "
        "La historia es una (UNIQUE) y las atenciones son muchas (1 a N). Las claves foráneas impiden, por "
        "ejemplo, facturar a un paciente que no existe. La corrección frente al anexo: el anexo mezclaba la "
        "historia con cada atención en una sola tabla; separarla en dos es lo que hace cumplir la regla "
        "«cada paciente tiene una única historia clínica con múltiples registros de atención».",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 6: Datos de prueba y 4. GET vs POST
    # =========================================================================
    story.append(Paragraph("Datos de prueba que trae el SQL", h2_style))
    story.append(Paragraph(
        "Cuatro usuarios (uno por rol) con contraseñas en hash, la paciente María Torres con su ficha e "
        "historia, y 6 tratamientos de catálogo. Las cuentas están en el capítulo 9. Son de práctica: en una "
        "clínica real se crean usuarios propios y estas se desactivan.",
        body_style
    ))
    story.append(Spacer(1, 4))

    story.append(Paragraph("4. Cómo actúa GET y cómo actúa POST (y qué NO debe hacer cada uno)", h1_style))
    story.append(Paragraph(
        "Esta es la regla de oro del proyecto, y el Front Controller la hace cumplir: "
        "<b>GET solo muestra. POST es lo único que cambia la base de datos.</b>",
        body_style
    ))
    story.append(Spacer(1, 4))

    get_post = [
        [Paragraph("", table_header), Paragraph("GET", table_header), Paragraph("POST", table_header)],
        [Paragraph("Para qué sirve", table_cell_bold), Paragraph("Pedir y MOSTRAR información: formularios, listas, detalles, buscadores.", table_cell), Paragraph("ENVIAR datos que crean, editan o cambian estados: guardar, cancelar, pagar, entrar, verificar.", table_cell)],
        [Paragraph("Qué SÍ contiene", table_cell_bold), Paragraph("Parámetros de lectura en la URL: ?c=citas&a=index, &id=5, &buscar=dni, el token de recuperación al ABRIR el enlace, el ?code= que devuelve Google.", table_cell), Paragraph("Los datos del formulario en el cuerpo del envío (no en la URL): nombres, contraseñas, códigos MFA, importes. Y siempre el token CSRF oculto.", table_cell)],
        [Paragraph("Qué NO debe contener / hacer", table_cell_bold), Paragraph("NUNCA debe crear, editar, borrar ni cambiar estados. Nunca debe llevar contraseñas ni servir para «guardar con un enlace». Si una acción de cambio llega por GET, el Front Controller NO la ejecuta y devuelve al usuario con aviso (probado).", table_cell), Paragraph("Nunca debe ser la respuesta final: todo POST termina en una REDIRECCIÓN a un GET (patrón Post/Redirect/Get). Así, recargar la página no duplica el guardado ni reenvía contraseñas.", table_cell)],
        [Paragraph("Ejemplos del sistema", table_cell_bold), Paragraph("Ver citas, buscar pacientes, abrir el formulario de agendar, ver una historia, descargar el respaldo (lee, no modifica).", table_cell), Paragraph("Entrar (login), verificar el MFA, agendar, confirmar/cancelar una cita, registrar una atención, emitir/cobrar una factura, activar el Authenticator, salir.", table_cell)]
    ]
    t_gp = Table(get_post, colWidths=[95, 214, 214])
    t_gp.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_gp)
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "<b>La excepción explicada:</b> googleCallback es un GET que crea la sesión. Es la única excepción y es "
        "inherente a OAuth: Google devuelve el navegador a tu sistema con ?code=… y ese canje es lo que "
        "prueba la identidad. No modifica datos de negocio: después del callback, el usuario igual pasa por el "
        "MFA antes de tener sesión completa. La otra «excepción» aparente, descargar el respaldo, es un "
        "GET que solo LEE la base y entrega un archivo.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 7: 5. Autenticación completa (5.1, 5.2, 5.3)
    # =========================================================================
    story.append(Paragraph("5. Autenticación completa: el módulo explicado paso a paso", h1_style))
    story.append(Paragraph(
        "Entrar a Odontologia Local tiene DOS factores siempre (eso es el MFA): algo que sabes (tu "
        "contraseña) y algo que tienes (un código que cambia). El flujo completo:",
        body_style
    ))
    story.append(Spacer(1, 2))

    story.append(Paragraph("5.1 Primer factor: correo y contraseña", h2_style))
    p51 = [
        "• El usuario escribe correo y contraseña (POST a auth/entrar). La contraseña nunca se guarda legible: en la base vive su hash (password_hash) y se comprueba con password_verify.",
        "• Si el correo no existe o la clave está mal, el mensaje es genérico («correo o contraseña incorrectos»): no se revela cuál de los dos falló.",
        "• Bloqueo tras 3 intentos fallidos: cada fallo suma en usuarios.intentos_fallidos; al tercero, la cuenta queda bloqueada 15 minutos (bloqueado_hasta) y ni siquiera con la clave correcta puede entrar hasta que pase el plazo. Al entrar bien, el contador vuelve a cero.",
        "• Una cuenta desactivada (estado = 0) no entra aunque la clave sea correcta.",
        "• Si la contraseña es correcta, todavía NO hay sesión: el sistema pasa al segundo factor."
    ]
    for p in p51:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("5.2 Segundo factor por correo (el del caso de estudio)", h2_style))
    p52 = [
        "• El sistema genera un código de 6 dígitos, guarda su hash en la tabla codigos_mfa con vencimiento de 10 minutos, invalida los códigos anteriores y lo envía por correo.",
        "• Al verificarlo: debe existir, no estar usado y no haber vencido. Si es correcto, se marca usado = 1 en el acto: el mismo código jamás sirve dos veces.",
        "• Recién ahí nace la sesión completa, se regenera el ID de sesión y se registra la sesión en la tabla sesiones (fecha, hora, IP, dispositivo y método).",
        "• Si el usuario tiene debe_cambiar_contrasena = 1 (primer ingreso), el sistema lo lleva obligado a cambiarla antes de mostrarle cualquier otra pantalla."
    ]
    for p in p52:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("5.3 Google Authenticator (TOTP): la aclaración que evita la confusión", h2_style))
    story.append(Paragraph(
        "<b>Google Authenticator NO es «login con Google» y NO usa Google Cloud.</b> No hay Client ID, no "
        "hay Client Secret, no hay que registrar nada en ninguna consola y no necesita internet. Es un "
        "algoritmo público (TOTP, RFC 6238) que funciona así:",
        body_style
    ))
    p53 = [
        "• 1. El sistema genera un secreto (texto Base32) solo para ese usuario y lo guarda en usuarios.totp_secret.",
        "• 2. El usuario lo agrega a su app Google Authenticator: escaneando el código QR que muestra «Mi seguridad» o escribiendo el secreto a mano (la pantalla muestra ambos).",
        "• 3. Desde ahí, la app y el sistema calculan el mismo código de 6 dígitos cada 30 segundos usando el secreto y la hora. Nadie envía nada: por eso funciona sin internet.",
        "• 4. La activación solo se guarda cuando el usuario demuestra que agregó bien el secreto: escribe un código de la app y el sistema lo verifica (con tolerancia de ±30 segundos por si el reloj del celular se desfasa).",
        "• 5. Desde el siguiente ingreso, su segundo factor es la app (ya no el correo). Puede desactivarlo en «Mi seguridad» y vuelve el código por correo.",
        "• En este proyecto el TOTP está implementado en PHP puro (ayudantes/Totp.php, sin librerías) y se verificó contra los vectores oficiales del RFC 6238 y contra una implementación independiente: los códigos coinciden."
    ]
    for p in p53:
        story.append(Paragraph(p, bullet_style))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 8: 5.4 Login con Google (OAuth 2.0)
    # =========================================================================
    story.append(Paragraph("5.4 Login con Google (OAuth 2.0): este SÍ usa Google Cloud", h1_style))
    story.append(Paragraph(
        "Respuesta directa a tu pregunta: sí, todo el código de la autenticación con Google está hecho y "
        "funciona (ayudantes/Google.php + AuthControlador). Lo único que ningún programador puede hacer "
        "por ti son las credenciales: Google las entrega solo al dueño de la cuenta de Google que crea el "
        "proyecto en su consola. Es como la llave de tu casa: yo te dejo la cerradura instalada y probada; la "
        "llave la cortas tú con tu cuenta, en 5 minutos, siguiendo estos pasos. Y jamás compartas tu Client "
        "Secret con nadie (tampoco conmigo): va solo en tu config/config.local.php (ignorado por Git), en tu PC.",
        body_style
    ))
    story.append(Paragraph("Paso a paso en Google Cloud Console", h2_style))
    pasos_gc = [
        "• Paso 1. Entra a console.cloud.google.com con tu cuenta de Google (la tuya personal sirve).",
        "• Paso 2. Arriba, abre el selector de proyectos → Nuevo proyecto → nombre «Odontologia Local» → Crear. Espera unos segundos y selecciónalo.",
        "• Paso 3. Menú → APIs y servicios → Pantalla de consentimiento de OAuth → tipo Externa → Crear. Llena solo lo obligatorio: nombre de la aplicación «Odontologia Local», tu correo de asistencia y tu correo de contacto → Guardar y continuar hasta terminar (en «Usuarios de prueba» no necesitas agregar a nadie si la app queda en modo de prueba para tu propio uso; para la práctica del curso alcanza).",
        "• Paso 4. Menú → APIs y servicios → Credenciales → + Crear credenciales → ID de cliente de OAuth.",
        "• Paso 5. Tipo de aplicación: Aplicación web. Nombre: «Odontologia Local local».",
        "• Paso 6. En URI de redireccionamiento autorizados → Agregar URI, pega EXACTAMENTE esta (si tu carpeta en htdocs se llama distinto, cambia solo ese nombre):<br/>"
        "<code>http://localhost/OdontologiaLocal/index.php?c=auth&a=googleCallback</code>",
        "• Paso 7. Crear. Google muestra tu ID de cliente (termina en .apps.googleusercontent.com) y tu Secreto del cliente.",
        "• Paso 8. Guarda esas credenciales solo en tu equipo: crea config/config.local.php (ignorado por Git) con las constantes GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET, y lo mismo en acceso-google-2fa/config.local.php si usas el módulo independiente. El archivo client_secret_*.json que Google ofrece descargar contiene el secreto: no lo copies dentro del proyecto ni lo subas a ningún repositorio.",
        "• Paso 9. Prueba: en el login pulsa «Entrar con Google» → Google muestra SU pantalla (tu contraseña de Google nunca pasa por Odontologia Local) → eliges tu cuenta → Google devuelve el navegador al sistema → el sistema busca tu correo en la tabla usuarios."
    ]
    for p in pasos_gc:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("Reglas de este login (importantes para exponer)", h2_style))
    story.append(Paragraph(
        "• Si el correo de Google no existe en la tabla usuarios, la cuenta se crea automáticamente como "
        "<b>PACIENTE verificado</b> (mejora pedida por Elvis): con su google_id, su foto (avatar) y una "
        "contraseña imposible que nadie conoce (su llave es Google; si quiere contraseña, la crea con "
        "«¿Olvidaste tu contraseña?»). Nace sin ficha: recepción registra su DNI en Pacientes.<br/>"
        "• La primera vez, el sistema vincula tu google_id a tu usuario; las siguientes entra directo por ese vínculo.<br/>"
        "• Después de Google, el usuario pasa por el mismo MFA que el login normal (la sesión se registra con método «google»).",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 9: Continuación de reglas, 5.5 Recuperación y 5.6 Correo local
    # =========================================================================
    story.append(Paragraph(
        "• Las credenciales viven solo en config.local.php (fuera de Git); si ese archivo falta o está vacío, el "
        "botón avisa «aún no configurado» con el motivo exacto: el sistema no falla en silencio. Y ojo: cada "
        "forma de correr el sistema necesita SU redirect URI registrada (la de XAMPP y la de "
        "localhost:3000): si falta, Google devuelve redirect_uri_mismatch.<br/>"
        "• El «state» aleatorio que viaja a Google y vuelve se compara con el de la sesión: protege contra respuestas falsificadas.",
        body_style
    ))
    story.append(Spacer(1, 4))

    story.append(Paragraph("5.5 Recuperación de contraseña con enlace de UN SOLO USO", h1_style))
    p55 = [
        "• 1. Pedir: en «¿Olvidaste tu contraseña?» el usuario escribe su correo. La respuesta en pantalla es siempre la misma, exista o no la cuenta (para no revelar qué correos están registrados).",
        "• 2. Crear: si la cuenta existe y está activa, el sistema genera un token de 64 caracteres imposible de adivinar, guarda en tokens_recuperacion solo su hash, con vencimiento de 30 minutos, e invalida los tokens anteriores de ese usuario.",
        "• 3. Enviar: el correo lleva el enlace con el token en claro, una sola vez: index.php?c=auth&a=restablecer&token=…",
        "• 4. Abrir: al abrir el enlace, el sistema busca el hash: si el token no existe, ya fue usado o venció, no muestra el formulario.",
        "• 5. Usar: al guardar la contraseña nueva, el token se marca usado = 1: si alguien vuelve a abrir el mismo enlace (él u otra persona), el sistema lo rechaza. Eso es «un solo uso», y está probado (capítulo 13). De regalo, restablecer también desbloquea la cuenta y levanta el cambio obligatorio."
    ]
    for p in p55:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 4))
    story.append(Paragraph("5.6 El correo en XAMPP local: la verdad completa", h1_style))
    story.append(Paragraph(
        "Un XAMPP recién instalado no envía correo real: PHP mail() necesita un servidor de correo "
        "(sendmail/SMTP) configurado en php.ini, y en local no lo hay. Para que puedas practicar y exponer sin "
        "depender de eso, el sistema trabaja en MODO_DEV (config/config.php): los «correos» se escriben en "
        "runtime/correos.log y el código MFA y el enlace de recuperación se muestran en pantalla marcados "
        "como modo desarrollo. En un servidor real se pone MODO_DEV = false y se configura el SMTP: el "
        "resto del flujo es idéntico. Además, cada aviso de cita queda registrado en la tabla notificaciones.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 10: 6. Los módulos del sistema
    # =========================================================================
    story.append(Paragraph("6. Los módulos del sistema, uno por uno", h1_style))
    
    story.append(Paragraph("6.1 Usuarios y roles (solo Administrador)", h2_style))
    story.append(Paragraph(
        "CRUD completo: crear (con contraseña temporal y cambio obligatorio al primer ingreso), editar datos y "
        "rol, y activar/desactivar sin eliminar: el registro y su historial se conservan, y un usuario desactivado "
        "no puede iniciar sesión. El correo es único. Si el usuario creado es paciente, el sistema crea también "
        "su ficha y su historia clínica vacía. Un administrador no puede desactivarse a sí mismo.",
        body_style
    ))

    story.append(Paragraph("6.2 Pacientes", h2_style))
    story.append(Paragraph(
        "Registro de datos personales, contacto y antecedentes médicos. Búsqueda por nombre, DNI o "
        "correo (un solo buscador con LIKE parametrizado). La ficha muestra sus datos y su historial de citas. "
        "El DNI es único: el sistema rechaza duplicados. Recepción y admin registran/editan; el odontólogo "
        "consulta; el paciente solo ve su propia ficha (su identidad sale de la sesión, nunca de la URL).",
        body_style
    ))

    story.append(Paragraph("6.3 Citas y sus reglas de negocio", h2_style))
    story.append(Paragraph(
        "Las reglas viven en el modelo (CitaModelo::validarReglas), no en la pantalla: aunque alguien "
        "manipule el formulario, el sistema igual las cumple.<br/>"
        "• R1 Horario: lunes a viernes 08:00–19:00, sábados 08:00–13:00, domingos cerrado. Fuera de eso, no se agenda.<br/>"
        "• R2 Sin doble reserva: un odontólogo no puede tener dos citas la misma fecha y hora (las canceladas no cuentan).<br/>"
        "• R3 Cancelación anticipada: solo se puede cancelar si faltan 2 horas o más; el botón desaparece y el servidor lo vuelve a verificar al recibir el POST.<br/>"
        "• Estados: pendiente → confirmada → atendida, o cancelada guardando el motivo. Cada cambio genera notificación por correo (registrada en la tabla notificaciones).<br/>"
        "• Nada de citas en el pasado. El paciente solo agenda y cancela sus citas.",
        body_style
    ))

    story.append(Paragraph("6.4 Historias clínicas y tratamientos", h2_style))
    story.append(Paragraph(
        "Cada paciente tiene una única historia (la base lo garantiza con UNIQUE) con múltiples registros de "
        "atención. Cada atención guarda: diagnóstico, tratamiento indicado, medicamentos, observaciones y "
        "costo, con el odontólogo que atendió y, si se quiere, la cita atendida enlazada y el tratamiento del "
        "catálogo (que sugiere el costo ya con su descuento). Recepción no entra a historias: son datos médicos sensibles.",
        body_style
    ))

    story.append(Paragraph("6.5 Tratamientos (catálogo)", h2_style))
    story.append(Paragraph(
        "Catálogo con precio y descuento aplicable (0–100%). La lista muestra el precio final ya calculado. Activar/desactivar sin borrar, como en usuarios.",
        body_style
    ))

    story.append(Paragraph("6.6 Facturación", h2_style))
    story.append(Paragraph(
        "Recepción/admin emiten comprobantes con número correlativo (F001-000001…). La factura se arma "
        "desde el catálogo: el precio y el descuento los lee el servidor del catálogo, nunca se aceptan "
        "precios escritos en el navegador. Cabecera + detalle se guardan en una transacción: o entra la "
        "factura completa o no entra nada. Cobrar registra el método de pago (efectivo, tarjeta, yape, "
        "transferencia); anular no borra el registro. El paciente ve solo sus facturas y sus pendientes en su panel.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 11: 6.7 Panel, 7. Matriz de roles y 8. Seguridad aplicada
    # =========================================================================
    story.append(Paragraph("6.7 Panel por rol, Mi seguridad y Mapa del sistema", h2_style))
    story.append(Paragraph(
        "El panel muestra a cada rol lo suyo: admin ve totales (usuarios activos, pacientes, citas de hoy, total "
        "cobrado); el odontólogo, sus próximas citas; recepción, las citas de hoy; el paciente, sus próximas "
        "citas y sus facturas pendientes. «Mi seguridad» concentra el MFA Authenticator, el cambio de "
        "contraseña, el historial de sesiones del propio usuario y, solo para el admin, la descarga del respaldo "
        ".sql de la base (respaldo del sistema, que el caso asigna al administrador). El Mapa (?c=menu) lista "
        "todas las rutas GET y POST con qué hacen y a dónde redirigen: es la explicación viva del capítulo 4.",
        body_style
    ))
    story.append(Spacer(1, 4))

    story.append(Paragraph("7. Matriz de roles y permisos", h1_style))
    story.append(Paragraph(
        "Los permisos se verifican en el controlador (requiereRol), en el servidor. Que un botón no se vea es "
        "solo presentación: si alguien escribe la URL a mano, el sistema igual lo frena.",
        body_style
    ))
    story.append(Spacer(1, 2))

    roles_data = [
        [Paragraph("Módulo", table_header), Paragraph("Admin", table_header), Paragraph("Odontólogo", table_header), Paragraph("Recepcionista", table_header), Paragraph("Paciente", table_header)],
        [Paragraph("Usuarios y roles", table_cell_bold), Paragraph("Total", table_cell), Paragraph("—", table_cell), Paragraph("—", table_cell), Paragraph("—", table_cell)],
        [Paragraph("Pacientes", table_cell_bold), Paragraph("Total", table_cell), Paragraph("Consulta", table_cell), Paragraph("Registra / edita", table_cell), Paragraph("Solo su ficha", table_cell)],
        [Paragraph("Citas", table_cell_bold), Paragraph("Todas", table_cell), Paragraph("Las suyas + confirmar/atender", table_cell), Paragraph("Todas + confirmar", table_cell), Paragraph("Las suyas: reservar/cancelar", table_cell)],
        [Paragraph("Historias clínicas", table_cell_bold), Paragraph("Ver y registrar", table_cell), Paragraph("Ver y registrar", table_cell), Paragraph("— (dato sensible)", table_cell), Paragraph("Solo la suya (lectura)", table_cell)],
        [Paragraph("Tratamientos", table_cell_bold), Paragraph("Total", table_cell), Paragraph("Consulta", table_cell), Paragraph("Administra", table_cell), Paragraph("—", table_cell)],
        [Paragraph("Facturación", table_cell_bold), Paragraph("Total", table_cell), Paragraph("—", table_cell), Paragraph("Emitir / cobrar / anular", table_cell), Paragraph("Solo las suyas (lectura)", table_cell)],
        [Paragraph("Respaldo y reportes", table_cell_bold), Paragraph("Sí", table_cell), Paragraph("—", table_cell), Paragraph("—", table_cell), Paragraph("—", table_cell)],
        [Paragraph("Mi seguridad (MFA, sesiones)", table_cell_bold), Paragraph("La suya", table_cell), Paragraph("La suya", table_cell), Paragraph("La suya", table_cell), Paragraph("La suya", table_cell)]
    ]
    t_rol = Table(roles_data, colWidths=[110, 80, 110, 110, 113])
    t_rol.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_rol)
    story.append(Spacer(1, 4))

    story.append(Paragraph("8. Seguridad aplicada (resumen para exponer)", h1_style))
    p8 = [
        "• Contraseñas en hash (password_hash / password_verify), nunca en texto plano, ni siquiera en los datos de prueba.",
        "• MFA obligatorio para todos: código por correo de un solo uso o Google Authenticator (TOTP).",
        "• Bloqueo tras 3 intentos fallidos (15 minutos) y registro de sesiones con IP y dispositivo.",
        "• Enlaces de un solo uso para recuperar la contraseña: token con hash, expira en 30 minutos y muere al usarse.",
        "• Inyección SQL: PDO con sentencias preparadas en todos los modelos; ningún dato del usuario se concatena al SQL.",
        "• XSS: todo lo que se imprime en las vistas pasa por e() (htmlspecialchars).",
        "• CSRF: cada formulario POST lleva un token de sesión que el servidor verifica.",
        "• Sesión: cookie HttpOnly y SameSite=Lax; el ID de sesión se regenera al autenticarse.",
        "• Permisos por rol en el servidor y aislamiento del paciente (su identidad sale de la sesión, no de la URL).",
        "• GET nunca modifica: el Front Controller bloquea las acciones de cambio si llegan por GET."
    ]
    for p in p8:
        story.append(Paragraph(p, bullet_style))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 12: 9. Instalación XAMPP y 10. La mejora pedida
    # =========================================================================
    story.append(Paragraph(
        "• Carpetas protegidas: app/, config/, infra/ y runtime/ llevan .htaccess de denegación: nadie descarga el SQL ni las credenciales por el navegador.<br/>"
        "• Sin borrados destructivos: usuarios y tratamientos se desactivan; las facturas se anulan. El historial se conserva.",
        body_style
    ))
    story.append(Spacer(1, 2))

    story.append(Paragraph("9. Instalación en XAMPP, paso a paso", h1_style))
    p9 = [
        "• 1. Abre XAMPP y enciende Apache y MySQL.",
        "• 2. Entra a http://localhost/phpmyadmin → pestaña Importar → elige infra/base_de_datos.sql → Continuar. La base odontologia_local se crea sola con sus 12 tablas y los datos de prueba.",
        "• 3. Copia la carpeta OdontologiaLocal dentro de C:\\xampp\\htdocs\\.",
        "• 4. Abre http://localhost/OdontologiaLocal/ y entra con una cuenta de prueba. Tras la contraseña, escribe el código MFA que se muestra en pantalla en modo desarrollo (también queda en runtime/correos.log).",
        "• 5. (Cuando quieras) Activa el login con Google con tus credenciales (capítulo 5.4) y prueba el Authenticator desde «Mi seguridad»."
    ]
    for p in p9:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    cuentas = [
        [Paragraph("Rol", table_header), Paragraph("Correo", table_header), Paragraph("Contraseña", table_header)],
        [Paragraph("Administrador", table_cell_bold), Paragraph("admin@odontologialocal.pe", table_cell), Paragraph("Admin123*", table_cell)],
        [Paragraph("Odontólogo", table_cell_bold), Paragraph("dr.perez@odontologialocal.pe", table_cell), Paragraph("Odonto123*", table_cell)],
        [Paragraph("Recepcionista", table_cell_bold), Paragraph("recepcion@odontologialocal.pe", table_cell), Paragraph("Recep123*", table_cell)],
        [Paragraph("Paciente", table_cell_bold), Paragraph("paciente@odontologialocal.pe", table_cell), Paragraph("Paciente123*", table_cell)]
    ]
    t_cue = Table(cuentas, colWidths=[130, 240, 153])
    t_cue.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_cue)
    story.append(Spacer(1, 4))

    story.append(Paragraph("10. La mejora pedida: login con Google (OAuth), 2FA formal y módulo independiente", h1_style))
    story.append(Paragraph(
        "Se pidió integrar el login con Google usando un cliente OAuth propio de Google Cloud, formalizar el "
        "2FA y entregar además los archivos sueltos del flujo. Las credenciales de ese cliente viven solo en el "
        "equipo local, en config.local.php (ignorado por Git): no forman parte del código ni de esta "
        "documentación. Esto es lo que se hizo, incluidas las correcciones: tres datos que venían mal en el "
        "pedido se corrigieron con la documentación oficial de Google.",
        body_style
    ))
    story.append(Spacer(1, 2))

    mejora_data = [
        [Paragraph("Dato", table_header), Paragraph("Venía así", table_header), Paragraph("Correcto (documentación oficial)", table_header)],
        [Paragraph("Auth URI", table_cell_bold), Paragraph("https://google.com", table_cell), Paragraph("https://accounts.google.com/o/oauth2/v2/auth", table_cell)],
        [Paragraph("Token URI", table_cell_bold), Paragraph("https://googleapis.com", table_cell), Paragraph("https://oauth2.googleapis.com/token", table_cell)],
        [Paragraph("Userinfo", table_cell_bold), Paragraph("https://googleapis.com", table_cell), Paragraph("https://openidconnect.googleapis.com/v1/userinfo", table_cell)],
        [Paragraph("Redirect URI", table_cell_bold), Paragraph("solo http://localhost:3000/google-callback.php", table_cell), Paragraph("Esa es la del MÓDULO independiente. El sistema en XAMPP usa http://localhost/OdontologiaLocal/index.php?c=auth&a=googleCallback y también debe estar registrada.", table_cell)]
    ]
    t_mej = Table(mejora_data, colWidths=[80, 160, 283])
    t_mej.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_mej)
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        "<b>Verificación en la consola de Google Cloud:</b> el cliente OAuth está creado y habilitado, y la URI de "
        "localhost:3000 SÍ está registrada y guardada. También deben registrarse las URI de las demás formas "
        "de correr el sistema (XAMPP y puerto 8000): si falta la que se está usando, hay que agregarla con «+ "
        "Agregar URI» y guardar, o el botón de Google devolverá redirect_uri_mismatch.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 13: Cambios en BD, Sistema y Módulo independiente (Parte 1)
    # =========================================================================
    story.append(Paragraph("Cambios en la base de datos (tabla usuarios)", h2_style))
    p_bd = [
        "• google_id (ya existía): el ID único de la cuenta de Google; se vincula la primera vez.",
        "• avatar (nuevo): la URL de la foto de perfil que devuelve Google; se actualiza en cada ingreso y se muestra en la cabecera del sistema.",
        "• totp_secret (renombrado desde mfa_secreto): el secreto Base32 del 2FA. El dato no se perdió: la migración lo RENOMBRA.",
        "• two_factor_enabled (nuevo): 0 mientras el usuario no confirma; pasa a 1 SOLO cuando escribe un código válido de su app. Tener secreto sin confirmar NO activa el 2FA.",
        "• Entregable SQL doble: infra/base_de_datos.sql ya trae todo para instalaciones nuevas, e infra/migracion_google_2fa.sql trae los ALTER TABLE para actualizar una base anterior sin perder datos (probada sobre la base de la versión 1)."
    ]
    for p in p_bd:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("Qué cambió en el sistema (MVC)", h2_style))
    p_mvc = [
        "• Con las credenciales cargadas desde config.local.php, el botón «Entrar con Google» redirige de verdad a accounts.google.com con client_id, redirect_uri, response_type=code, scope «openid email profile», state anti-CSRF y access_type=online.",
        "• Auto-registro: si el correo verificado por Google no existe, se crea la cuenta como PACIENTE con google_id y avatar (sin ficha hasta que recepción registre el DNI).",
        "• El segundo factor ahora se decide con two_factor_enabled: activado → código de la app; no activado → código por correo. «Mi seguridad» y la lista de usuarios muestran el estado con ese flag.",
        "• Las sesiones registran el método (password o google) y la cabecera muestra el avatar cuando existe.",
        "• Ajustes de la auditoría en Linux (v3): la zona horaria quedó fijada a America/Lima en la configuración (PHP y la base miran el mismo reloj: un bloqueo de 15 minutos ya no puede durar 5 horas ni saltarse por diferencia UTC), la conexión a la base va por TCP 127.0.0.1 en vez del socket «localhost» (en Linux la ruta del socket varía y rompía la conexión), y la pantalla de login en modo desarrollo muestra la URI de retorno exacta que hay que registrar en Google Cloud: el error redirect_uri_mismatch se diagnostica copiándola de ahí. El proyecto trae además iniciar.sh (Linux, puerto 8000) e iniciar-modulo.sh (puerto 3000)."
    ]
    for p in p_mvc:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("El módulo independiente acceso-google-2fa/", h2_style))
    story.append(Paragraph(
        "Los mismos accesos en archivos sueltos, sin Front Controller, compartiendo la misma base y "
        "las mismas credenciales. Se prueba en http://localhost:3000 ejecutando php -S localhost:3000 dentro de la carpeta.",
        body_style
    ))
    story.append(Spacer(1, 2))

    mod_data = [
        [Paragraph("Archivo", table_header), Paragraph("Qué hace", table_header)],
        [Paragraph("config.php", table_cell_bold), Paragraph("Configuración del módulo: sesión endurecida, PDO, credenciales, endpoints reales, Redirect URI :3000, helpers CSRF/flash/sesión.", table_cell)],
        [Paragraph("TOTP.php", table_cell_bold), Paragraph("Clase 2FA en PHP puro pedida tal cual: generateSecret() (Base32 de 16), getQRCodeUrl() (la URI otpauth:// del QR) y verifyCode() con tolerancia de ±1 ventana (30 s).", table_cell)]
    ]
    t_mod1 = Table(mod_data, colWidths=[120, 403])
    t_mod1.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_mod1)
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 14: Módulo independiente (Parte 2) y 11. Cada formulario (Acceso)
    # =========================================================================
    mod_data2 = [
        [Paragraph("Archivo", table_header), Paragraph("Qué hace", table_header)],
        [Paragraph("login.php", table_cell_bold), Paragraph("Formulario correo+contraseña (POST) y botón de Google (GET) con todos los parámetros obligatorios.", table_cell)],
        [Paragraph("google-callback.php", table_cell_bold), Paragraph("Recibe ?code&state: valida el state, canjea el código por access_token con cURL, pide el perfil verificado, busca o auto-registra al usuario y deriva al 2FA si aplica.", table_cell)],
        [Paragraph("verificar-2fa.php", table_cell_bold), Paragraph("Pantalla del segundo factor cuando two_factor_enabled = 1.", table_cell)],
        [Paragraph("configurar-2fa.php", table_cell_bold), Paragraph("Activación: QR + clave manual en bloques de 4 con botón «Copiar código» + confirmación con código antes de activar.", table_cell)],
        [Paragraph("panel.php / salir.php", table_cell_bold), Paragraph("Llegada con la sesión completa (datos, avatar, estado del 2FA) y cierre de sesión por POST.", table_cell)]
    ]
    t_mod2 = Table(mod_data2, colWidths=[120, 403])
    t_mod2.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#2b6cb0")),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor("#cbd5e0")),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 5),
        ('RIGHTPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_mod2)
    story.append(Spacer(1, 4))

    story.append(Paragraph("11. Cada formulario y cada petición, uno por uno", h1_style))
    story.append(Paragraph(
        "Lo que pidió Elvis: que quede explicado qué hace CADA formulario y CADA petición. Regla general "
        "del sistema: las pantallas se piden con GET y NO cambian nada; todo cambio viaja con POST, lleva el "
        "token CSRF oculto y termina en una redirección (patrón Post/Redirect/Get). Cada archivo del código "
        "trae además su propio comentario de cabecera con esta misma información.",
        body_style
    ))
    story.append(Paragraph("Acceso y seguridad", h2_style))
    p11_acc = [
        "• Login (formulario) — POST auth/entrar · campos: correo, contraseña, csrf · verifica hash, estado y bloqueo (3 intentos = 15 min) · si pasa, crea el MFA pendiente y va a la pantalla del código.",
        "• Botón «Entrar con Google» — GET auth/google · no es un formulario: redirige a Google con state aleatorio guardado en sesión · vuelve por GET auth/googleCallback con code+state: se valida el state, se canjea el código por cURL (servidor a servidor, con el secret), se lee el perfil y se busca o auto-registra al usuario; después, el mismo MFA de siempre.",
        "• Código MFA por correo — POST auth/verificarMfa · campo: codigo · compara el HASH del código vigente (10 min, un solo uso) · éxito: nace la sesión completa, se registra en la tabla sesiones y va al panel (o al cambio obligatorio de contraseña).",
        "• Código del Authenticator (2FA) — la MISMA pantalla y petición, pero el código se valida con TOTP contra totp_secret, sin tocar la base: por eso funciona sin internet.",
        "• ¿Olvidaste tu contraseña? — POST auth/recuperar · campo: correo · respuesta idéntica exista o no la cuenta · si existe, crea el token (solo se guarda su hash, 30 min) y manda el enlace.",
        "• Restablecer con el enlace — GET auth/restablecer?token=... muestra el formulario solo si el token es válido · POST auth/guardarNuevaClave guarda la clave, marca el token USADO (segundo uso rechazado), desbloquea la cuenta y va al login.",
        "• Cambio obligatorio (primer ingreso) — POST auth/guardarCambioClave · el sistema no deja usar ninguna otra pantalla hasta hacerlo.",
        "• Mi seguridad: activar Authenticator — POST seguridad/iniciarTotp genera el secreto (queda en sesión, aún NO activo) y muestra QR + clave manual · POST seguridad/confirmarTotp valida el código escrito: recién ahí se guarda totp_secret y two_factor_enabled = 1 · POST seguridad/quitarTotp lo borra y vuelve el flag a 0.",
        "• Mi seguridad: mi contraseña — POST seguridad/cambiarContrasena · exige la actual correcta y la nueva dos veces igual, mínimo 8.",
        "• Respaldo (solo admin) — GET seguridad/respaldo · la excepción consciente: es una DESCARGA .sql generada al momento, no cambia nada.",
        "• Cerrar sesión — POST auth/salir · destruye la sesión; por GET no se ejecuta."
    ]
    for p in p11_acc:
        story.append(Paragraph(p, bullet_style))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 15: Administración, Módulo independiente y 12. Blindaje
    # =========================================================================
    story.append(Paragraph("Administración y operación", h2_style))
    p11_adm = [
        "• Usuarios: guardar — POST usuarios/guardar · campos: nombre, correo, rol, teléfono y (solo al crear) contraseña temporal · el correo duplicado se rechaza · al crear, la cuenta nace con cambio obligatorio · vuelve a la lista.",
        "• Usuarios: activar/desactivar — POST usuarios/alternarEstado · cambia el estado sin borrar (el historial se conserva) · un admin no puede desactivarse a sí mismo.",
        "• Pacientes: guardar — POST pacientes/guardar · crea también su historia clínica vacía (UNA por paciente) · el DNI duplicado se rechaza · eliminar es POST pacientes/eliminar y solo procede si no tiene citas ni atenciones.",
        "• Citas: agendar — POST citas/crear · campos: paciente, odontólogo, tratamiento, fecha y hora · el MODELO valida el horario de la clínica, que no sea pasado y que el odontólogo no tenga otra cita a esa hora · se guarda «pendiente» y queda la notificación.",
        "• Citas: cambiar estado — POST citas/cambiarEstado · confirmada/atendida/cancelada con sus reglas (cancelar exige 2 horas de anticipación y cita no atendida).",
        "• Historia: registrar atención — POST historias/guardarAtencion · diagnóstico, tratamiento, medicamentos, observaciones y costo quedan como un registro nuevo dentro de la historia única del paciente.",
        "• Tratamientos: guardar / activar-desactivar — POST tratamientos/guardar y tratamientos/alternarEstado · precio y descuento % del catálogo: son la fuente de la facturación.",
        "• Facturación: emitir — POST facturas/emitir · elige la atención sin facturar: el servidor toma precio y descuento DEL CATÁLOGO (no del navegador), calcula, numera F001-000001… dentro de una transacción y marca la atención facturada.",
        "• Mapa del sistema — GET menu · la pantalla que lista en vivo las 17 pantallas GET y las 23 acciones POST con su explicación: este capítulo, dentro del propio sistema."
    ]
    for p in p11_adm:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("El módulo independiente (localhost:3000)", h2_style))
    p11_mod = [
        "• login.php — POST con correo+contraseña (mismas reglas de bloqueo) o enlace GET de Google con response_type=code, state y access_type=online.",
        "• google-callback.php — GET con code+state: valida, canjea, lee el perfil, busca o auto-registra, y deriva a verificar-2fa.php o a panel.php.",
        "• configurar-2fa.php — GET genera y guarda el secreto (flag aún en 0) y muestra QR + clave en bloques de 4 con copiar · POST confirmar valida el código y pone two_factor_enabled = 1 · POST desactivar lo limpia.",
        "• verificar-2fa.php — POST con el código de la app: si es válido crea la sesión completa y entra a panel.php."
    ]
    for p in p11_mod:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("12. Blindaje de seguridad aplicado", h1_style))
    story.append(Paragraph(
        "Además de lo del capítulo 8, la mejora agregó estas capas (todas verificables en el código):<br/>"
        "• Cabeceras de seguridad en toda respuesta (index.php y config del módulo): X-Frame-Options SAMEORIGIN (anti-clickjacking), X-Content-Type-Options nosniff, Referrer-Policy same-origin (la URL con token de recuperación no se filtra afuera) y Content-Security-Policy que solo permite recursos propios, el CDN del QR y los avatares de Google.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 16: Continuación de Blindaje y 13. Cómo se probó
    # =========================================================================
    p12_cont = [
        "• Cookie de sesión endurecida: HttpOnly + SameSite=Lax siempre, y Secure automática cuando hay HTTPS. El ID de sesión se regenera al completar el login.",
        "• Apache: .htaccess raíz con Options -Indexes (sin listado de directorios) y denegación de archivos .sql, .log, .md y .json por web; las carpetas app/, config/, infra/ y runtime/ ya tenían su propia denegación total.",
        "• CSRF en el 100% de los POST, incluido el módulo independiente (login, 2FA, configurar y salir).",
        "• Comparaciones en tiempo constante (hash_equals) para el state de Google, el token CSRF y los códigos TOTP: no se filtra información por tiempos de respuesta.",
        "• El Client Secret vive solo en el servidor: participa en el canje por cURL y jamás llega al navegador. La carpeta config/ no es descargable. Las credenciales y el archivo client_secret_*.json quedan fuera de Git por el .gitignore; si el secreto se filtra, se rota en Google Cloud y se genera uno nuevo.",
        "• Sin contraseñas adivinables en el auto-registro: la cuenta creada por Google nace con un hash de texto aleatorio de 64 caracteres: nadie puede entrar por contraseña hasta que el dueño la cree por recuperación.",
        "• Lo de siempre, que se mantiene: sentencias preparadas PDO en todo, escape de salida anti-XSS, bloqueo por intentos, códigos y tokens guardados con hash y de un solo uso, acciones de cambio bloqueadas por GET."
    ]
    for p in p12_cont:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 4))
    story.append(Paragraph("13. Cómo se probó (y qué falta probar)", h1_style))
    story.append(Paragraph(
        "El proyecto no se entrega «de memoria»: se ejecutó en un servidor PHP real con la base importada "
        "desde el mismo infra/base_de_datos.sql que recibes.",
        body_style
    ))
    story.append(Paragraph("Probado y pasando", h2_style))
    p13_pro = [
        "• Sintaxis PHP: 58 de 58 archivos sin errores (php -l), incluidos los 8 del módulo independiente.",
        "• SQL: importación limpia, 12 tablas creadas y datos semilla correctos; los 4 hash de las cuentas de prueba verificados contra sus contraseñas.",
        "• 27 pruebas HTTP de punta a punta, 27 OK: login completo con MFA por correo; aviso de Google sin configurar; GET intentando cancelar (no ejecuta nada); admin crea un usuario; cambio obligatorio en el primer ingreso; bloqueo al 3.er intento y rechazo aun con la clave correcta; cita válida agendada; doble reserva rechazada; domingo rechazado; sábado 15:00 rechazado (cierra 13:00); factura F001-000001 por S/ 108.00 (limpieza S/ 120 con 10% de descuento del catálogo); atención registrada en la historia; recuperación con enlace de un solo uso (el segundo uso del mismo enlace es rechazado y la contraseña nueva funciona); aislamiento del paciente por rol; y Google Authenticator completo: generar el secreto, confirmarlo con el código de una implementación independiente, reingresar usando solo la app y verificar que las sesiones quedaron registradas en la base.",
        "• Regla de cancelación (R3) probada directo sobre el modelo: en 1 hora no se puede, en 3 días sí, atendida no.",
        "• TOTP verificado contra los vectores oficiales del RFC 6238 (t=59 → 287082; t=1111111109 → 081804).",
        "• Sin warnings de PHP en el registro del servidor durante todas las pruebas."
    ]
    for p in p13_pro:
        story.append(Paragraph(p, bullet_style))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 17: Repruebas, Lo que NO se probó y 14. Preguntas típicas
    # =========================================================================
    p13_repr = [
        "• Pruebas de la mejora (v2), 29 de 29 OK: el botón de Google del sistema redirige a accounts.google.com con response_type=code, state, access_type=online, el client_id configurado en local y la redirect URI del MVC; el flujo MFA por correo y el TOTP con two_factor_enabled siguen pasando; el GET sobre POST sigue bloqueado; la migración aplicada sobre la base de la versión 1 renombró mfa_secreto a totp_secret y agregó avatar y two_factor_enabled sin perder datos; y en el módulo independiente (localhost:3000): el botón trae los parámetros pedidos y la redirect :3000, el callback sin código vuelve al login con error, el state falso es rechazado, el login con 2FA activo deriva a verificar-2fa.php, la activación completa (QR + clave manual + confirmación) funciona y el próximo login exige el código; la clase TOTP.php del módulo cumple los vectores RFC 6238.",
        "• Reprueba v3 (tras adoptar los arreglos de la auditoría Linux): 29 de 29 PASS otra vez, con la conexión por TCP, la zona horaria fijada y los servidores levantados con los propios iniciar.sh / iniciar-modulo.sh; la pantalla de login muestra correctamente la URI :8000 a registrar."
    ]
    for p in p13_repr:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("Lo que NO se probó (honestidad)", h2_style))
    p13_no = [
        "• El canje real con Google (el paso en que Google devuelve el code y el servidor lo cambia por el perfil): las credenciales se cargan en local y la redirección sale correctamente con todos los parámetros (probado), pero completar el círculo necesita un humano eligiendo su cuenta en la pantalla de Google y la redirect URI de la forma en que se corre el sistema debidamente registrada. Se prueba en la PC de cada usuario en cuanto agregue la URI que falte.",
        "• El correo saliente real: en local no hay SMTP; el flujo se probó en modo desarrollo (correos.log + código en pantalla), que es el comportamiento documentado del capítulo 5.6.",
        "• La ejecución en tu XAMPP de Windows: aquí se probó en PHP 8.3 + el motor que trae XAMPP (su botón «MySQL» arranca MariaDB, totalmente compatible con el SQL MySQL estándar de este proyecto, que también corre en un MySQL 8 puro; no hay que instalar ningún MariaDB aparte porque XAMPP ya lo incluye). Si algo falla en tu máquina, copia el mensaje exacto de la pantalla y se corrige desde ese mensaje, no adivinando."
    ]
    for p in p13_no:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("14. Preguntas típicas si te lo preguntan en la exposición", h1_style))
    p14 = [
        "<b>¿Por qué el código MFA y el token se guardan con hash?</b> Porque si alguien roba la base, no obtiene ni códigos vigentes ni enlaces de recuperación usables: el dato útil solo viaja una vez, por correo.",
        "<b>¿Por qué Google Authenticator no necesita Google Cloud?</b> Porque no habla con Google: es un algoritmo (TOTP) que comparten la app y el servidor con un secreto y la hora. Google Cloud es para el otro servicio: el login con Google (OAuth).",
        "<b>¿Por qué las reglas de las citas están en el modelo y no en el formulario?</b> Porque el formulario se puede manipular; el modelo es el servidor, y lo que valida el servidor no se puede saltar desde el navegador.",
        "<b>¿Por qué no se borran usuarios?</b> Porque una clínica necesita historial y auditoría: desactivar impide el acceso pero conserva citas, atenciones y sesiones asociadas.",
        "<b>¿Por qué la factura no acepta precios del formulario?</b> Porque serían falsificables: el servidor lee precio y descuento del catálogo, y la factura se guarda en una transacción completa o no se guarda."
    ]
    for p in p14:
        story.append(Paragraph(p, bullet_style))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 18: 15. Mejora v4 (15.1, 15.2, 15.3 Parte 1)
    # =========================================================================
    story.append(Paragraph("15. Mejora v4: primer ingreso con Google, correo real y documentación en pantalla", h1_style))
    story.append(Paragraph(
        "Cuatro pedidos de Elvis en esta versión: (1) que el usuario NUEVO de Google pase por código de "
        "correo y cree su contraseña antes de entrar, con una ventana que le recomiende el 2FA; (2) que los "
        "roles superiores se den desde la base de datos y no por la web; (3) saber qué falta para que el correo "
        "llegue de verdad a cualquier buzón; y (4) que cada pantalla explique dentro de sí misma qué hace, "
        "qué petición llama, qué no hace y en qué archivo y línea está.",
        body_style
    ))
    story.append(Spacer(1, 2))

    story.append(Paragraph("15.1 El primer ingreso con Google, paso a paso (solo usuarios nuevos)", h2_style))
    p151 = [
        "• Paso 1 — Google: el usuario pulsa «Entrar con Google», elige su cuenta en la pantalla oficial y Google devuelve el navegador al callback. Google ya verificó que el correo es suyo.",
        "• Paso 2 — Código por correo: como el correo no existe en la tabla, la cuenta se crea como PACIENTE y el sistema le envía el código MFA de 6 dígitos A SU CORREO (en modo práctica se ve en pantalla y en runtime/correos.log; con SMTP configurado llega al buzón). Sin ese código no se avanza: prueba que el buzón también es suyo.",
        "• Paso 3 — Crear su contraseña: la cuenta nace con debe_cambiar_contrasena = 1, así que tras el código el sistema lo lleva obligado a CREAR su contraseña propia. Desde ahí puede entrar también sin Google.",
        "• Paso 4 — Ventana de bienvenida: al llegar al panel aparece UNA sola vez una ventana que recomienda activar la verificación en 2 pasos con el código QR (Google Authenticator), con botones «Activar ahora» y «Ahora no».",
        "• Lo que NO cambia: los usuarios que ya existen (incluidas las 4 cuentas de prueba) y los que ya vincularon Google siguen el flujo de siempre. Y si una cuenta de Google ya se creó en un intento anterior, ya cuenta como existente."
    ]
    for p in p151:
        story.append(Paragraph(p, bullet_style))

    story.append(Spacer(1, 2))
    story.append(Paragraph("15.2 Roles desde la base de datos (no por la web)", h2_style))
    story.append(Paragraph(
        "Entrar con Google prueba identidad, nunca da privilegios: toda cuenta nueva nace PACIENTE. Para "
        "que una cuenta de Google sea admin, odontólogo o recepcionista hay dos caminos, ambos fuera del "
        "alcance de un visitante: (a) el administrador crea antes la cuenta en el panel de Usuarios con ese "
        "correo y ese rol — al entrar con Google el sistema la encuentra y conserva su rol; (b) un UPDATE "
        "directo en la base. El archivo infra/roles_google.sql trae los UPDATE comentados, listos para "
        "descomentar con el correo exacto; el cambio aplica en el siguiente inicio de sesión. Ejemplo: para dar "
        "rol ADMIN a una cuenta concreta, el administrador la crea antes en el panel de Usuarios (o con un "
        "UPDATE en la base) con su correo exacto y ese rol — al entrar con Google el sistema la encuentra, "
        "vincula su google_id y conserva el rol, y como nace con cambio obligatorio, tras el código MFA crea "
        "su contraseña propia. Una cuenta sin registro previo se auto-registra como paciente en su primer "
        "ingreso. Por privacidad, esta documentación no publica correos personales ni el rol de cada uno.",
        body_style
    ))

    story.append(Spacer(1, 2))
    story.append(Paragraph("15.3 Qué faltaba para el correo real: un servidor SMTP", h2_style))
    story.append(Paragraph(
        "El sistema ya generaba los códigos y enlaces; lo que no existía era un canal de salida: un PC local no "
        "es servidor de correo y mail() no entrega nada sin SMTP. La v4 agrega el cliente "
        "app/ayudantes/Smtp.php en PHP puro (sin librerías): habla SMTP con STARTTLS y AUTH LOGIN "
        "contra cualquier servidor. En la configuración local van los 4 datos (host, puerto, usuario y clave), solo en el equipo de cada usuario.",
        body_style
    ))
    story.append(PageBreak())

    # =========================================================================
    # PÁGINA 19: 15.3 Continuación, 15.4 y Verificación
    # =========================================================================
    story.append(Paragraph(
        "Con Gmail: host smtp.gmail.com, puerto 587, y como clave una contraseña de aplicación de Google "
        "(cuenta Google → Seguridad → Verificación en 2 pasos → Contraseñas de aplicaciones; son 16 letras "
        "y no es la contraseña normal). Al configurarlo y poner MODO_DEV en false, los códigos MFA y el enlace "
        "de recuperación llegan de verdad a cualquier correo y dejan de mostrarse en pantalla; el log pasa a "
        "guardar solo el registro del envío, sin códigos. El cliente se verificó contra un servidor SMTP de "
        "prueba local: la conversación completa (saludo, autenticación, remitente, destinatario, cuerpo) se completó y el mensaje llegó íntegro.",
        body_style
    ))
    story.append(Spacer(1, 4))

    story.append(Paragraph("15.4 Documentación dentro de las propias pantallas", h1_style))
    story.append(Paragraph(
        "El Mapa del sistema (?c=menu) ahora incluye por cada ruta: el archivo, el método y la línea de esta "
        "versión, y con qué modelos y tablas se conecta. Además, las pantallas principales traen arriba una "
        "caja plegable «¿Qué hace esta pantalla?» con cuatro datos: qué hace, qué petición llama, qué NO "
        "hace y dónde está en el código (parcial app/vista/parciales/info_pagina.php). El código mantiene la "
        "indentación uniforme de 4 espacios, sin tabulaciones, con el comentario de cabecera en cada archivo.",
        body_style
    ))
    story.append(Spacer(1, 4))

    story.append(Paragraph(
        "<b>Verificación de la v4:</b> sintaxis 60/60 archivos PHP; la batería completa de 29 pruebas HTTP volvió a "
        "pasar 29/29 sobre una base recién importada; el auto-registro de Google se probó con un arnés que "
        "crea la cuenta igual que el callback (rol paciente, cambio de contraseña obligatorio) y la ventana de "
        "bienvenida se comprobó renderizada una sola vez; el cliente SMTP se probó contra un servidor falso "
        "local. Lo único no probado sigue siendo el viaje real por la pantalla de Google con una cuenta "
        "humana, que depende del registro de la URI :8000 y de la propagación de Google.",
        body_style
    ))

    # Construir el documento PDF
    doc.build(story, canvasmaker=NumberedCanvas)
    print("PDF generado con éxito:", filename)

if __name__ == "__main__":
    build_pdf()
