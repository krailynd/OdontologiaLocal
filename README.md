# 🦷 OdontologiaLocal — Sistema de Gestión Clínica Odontológica

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![OAuth 2.0](https://img.shields.io/badge/Google%20OAuth-2.0-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://developers.google.com/identity/protocols/oauth2)
[![2FA TOTP](https://img.shields.io/badge/2FA%20TOTP-RFC%206238-009688?style=for-the-badge&logo=google-authenticator&logoColor=white)](https://tools.ietf.org/html/rfc6238)
[![Architecture](https://img.shields.io/badge/Architecture-Clean%20MVC-success?style=for-the-badge)](#arquitectura-del-sistema)
[![Zero Dependencies](https://img.shields.io/badge/Dependencies-Zero%20(Pure%20PHP)-orange?style=for-the-badge)](#filosofía-de-desarrollo)

Sistema web integral para la gestión administrativa, médica y financiera de clínicas y consultorios odontológicos. Desarrollado en **PHP Puro** bajo el patrón arquitectónico **MVC (Modelo-Vista-Controlador)** sin dependencias de frameworks ni librerías externas de terceros (Zero-Dependency Architecture), integrando seguridad de nivel corporativo con **Google OAuth 2.0**, **Doble Factor de Autenticación (2FA TOTP)**, control de acceso basado en roles (**RBAC**) y protección contra vulnerabilidades del **OWASP Top 10**.

---

## 📋 Tabla de Contenidos

1. [Características Principales](#-características-principales)
2. [Arquitectura y Seguridad](#-arquitectura-y-seguridad)
3. [Guía Paso a Paso: Configuración de Google Cloud OAuth 2.0](#-guía-paso-a-paso-configuración-de-google-cloud-oauth-20)
4. [Instalación y Despliegue](#-instalación-y-despliegue)
   - [En Linux (Arch / Debian / Ubuntu)](#en-linux-arch--debian--ubuntu)
   - [En Windows (XAMPP)](#en-windows-xampp)
5. [Cuentas de Prueba y Roles](#-cuentas-de-prueba-y-roles)
6. [Módulo Independiente de Autenticación](#-módulo-independiente-de-autenticación)
7. [Estructura del Proyecto](#-estructura-del-proyecto)
8. [Buenas Prácticas de Seguridad Implementadas](#-buenas-prácticas-de-seguridad-implementadas)

---

## 🌟 Características Principales

### 🏥 Gestión Clínica Odontológica
- **Ficha Integral del Paciente:** Datos demográficos, antecedentes médicos, alergias y contacto de emergencia.
- **Historias Clínicas Centralizadas:** Registro de antecedentes, diagnósticos, evolución médica continua y atenciones detalladas.
- **Agenda Inteligente de Citas:** Programación de citas por profesional con detección de solapamiento horario y control de estados (Pendiente, Confirmada, Atendida, Cancelada).
- **Catálogo de Procedimientos Odontológicos:** Administración de precios, costos, categorías y porcentajes de descuento.
- **Módulo de Facturación y Caja:** Emisión de comprobantes (Boletas/Facturas), gestión de pagos parciales/totales y balance diario de ingresos.

### 🔐 Seguridad y Autenticación Avanzada
- **Login Federado con Google Cloud (OAuth 2.0 + OpenID Connect):** Inicio de sesión con un clic sin necesidad de recordar contraseñas locales.
- **Doble Factor de Autenticación (2FA / TOTP RFC 6238):** Compatible con Google Authenticator, Microsoft Authenticator o Authy. Incluye generación de código QR nativo y clave de respaldo en bloques.
- **Control de Acceso Basado en Roles (RBAC):** Cuatro perfiles diferenciados con privilegios estrictos:
  - `Administrador`: Acceso absoluto a configuración, usuarios, reportes y finanzas.
  - `Odontólogo`: Agenda clínica, atención médica e historias clínicas de pacientes.
  - `Recepcionista`: Registro de pacientes, agendamiento de citas y facturación en caja.
  - `Paciente`: Consulta de sus citas agendadas, tratamientos e historial de atenciones.
- **Protección Anti-Fuerza Bruta & Rate Limiting:** Bloqueo temporal progresivo tras múltiples intentos fallidos de autenticación.
- **Recuperación Segura de Contraseña:** Tokens criptográficos de un solo uso con expiración temporal estricta vía correo electrónico.
- **Defensas Activas:** Tokens CSRF en todos los formularios `POST`, cookies de sesión endurecidas (`HttpOnly`, `SameSite=Lax`, `Secure`), cabeceras HTTP de seguridad (`X-Frame-Options`, `X-Content-Type-Options`) y consultas parametrizadas con PDO (100% inmune a SQL Injection).

---

## 🏛 Arquitectura del Sistema

El proyecto implementa una arquitectura limpia y modular que no depende de Composer ni frameworks pesados (como Laravel o Symfony), lo que facilita su auditoría, portabilidad y bajo consumo de recursos:

```
                      [ Cliente / Navegador Web ]
                                   │
                                   ▼
                       [ Front Controller (index.php) ]
                                   │
              ┌────────────────────┴────────────────────┐
              ▼                                         ▼
    [ Controladores (app/controlador/) ]     [ Ayudantes (app/ayudantes/) ]
    - AuthControlador                        - Google (OAuth2 Client)
    - PacienteControlador                    - Totp (RFC 6238 Engine)
    - CitaControlador                        - Smtp / Correo
    - FacturaControlador                     - Funciones (CSRF, Sanitizer)
    - SeguridadControlador                              │
              │                                         │
              ▼                                         │
    [ Modelos (app/modelo/) ]                           │
    - UsuarioModelo, PacienteModelo, ...                │
              │                                         │
              ▼                                         ▼
    [ Capa de Datos (PDO / MySQL) ]            [ Vistas (app/vista/) ]
    - Sentencias Preparadas (Prepared)         - Interfaz HTML5 / CSS3 nativa
```

---

## 🔑 Guía Paso a Paso: Configuración de Google Cloud OAuth 2.0

Para habilitar el botón **"Entrar con Google"** en tu propia instalación, sigue estos pasos en la consola oficial de Google Cloud:

### 1. Crear el Proyecto en Google Cloud
1. Ingresa a [Google Cloud Console](https://console.cloud.google.com/).
2. Inicia sesión con tu cuenta de Google.
3. En la barra superior, haz clic en el selector de proyectos y pulsa **"Proyecto Nuevo"**.
4. Asigna un nombre al proyecto (ej. `Odontologia-Clinica`) y haz clic en **"Crear"**.

### 2. Configurar la Pantalla de Consentimiento OAuth
1. En el menú lateral izquierdo, ve a **APIs y servicios** → **Pantalla de consentimiento de OAuth**.
2. Selecciona **Tipo de usuario:** **Externo** y pulsa **Crear**.
3. Completa los datos obligatorios:
   - **Nombre de la aplicación:** `Odontologia Local`
   - **Correo de asistencia al usuario:** Tu dirección de correo.
   - **Datos de contacto del desarrollador:** Tu dirección de correo.
4. En la sección **Permisos (Scopes)**, haz clic en **Agregar o quitar permisos** y selecciona:
   - `.../auth/userinfo.email` (Ver tu dirección de correo electrónico principal).
   - `.../auth/userinfo.profile` (Ver tu información personal pública).
   - `openid` (Asociar tu identidad con tu cuenta de Google).
5. En **Usuarios de prueba** (si el estado es *En prueba*), agrega los correos de Gmail con los que realizarás pruebas.
6. Pulsa **Guardar y continuar**.

### 3. Crear las Credenciales OAuth 2.0
1. En el menú lateral, dirígete a **Credenciales**.
2. Haz clic en **+ Crear credenciales** → **ID de cliente de OAuth**.
3. Selecciona **Tipo de aplicación:** **Aplicación web**.
4. Asigna un nombre (ej. `Cliente Web Odontologia`).
5. En **URI de redirección autorizados**, añade **EXACTAMENTE** las URLs correspondientes a tu entorno de ejecución:

| Entorno | URI de Redirección a Registrar |
|---|---|
| **Linux (servidor embebido)** | `http://localhost:8000/index.php?c=auth&a=googleCallback` |
| **Windows / XAMPP** | `http://localhost/OdontologiaLocal/index.php?c=auth&a=googleCallback` |
| **Módulo Independiente** | `http://localhost:3000/google-callback.php` |

> 💡 **Nota sobre `redirect_uri_mismatch`:** Si al probar el login recibes ese error, copia la URL exacta que muestra la pantalla de inicio de sesión debajo del botón de Google y regístrala en esta lista.

6. Haz clic en **Crear**. Google te mostrará tu **ID de cliente** y tu **Secreto de cliente**.

### 4. Configurar las Credenciales en el Proyecto
El proyecto cuenta con un sistema de desacople seguro. Para configurar tus claves:

Crea un archivo llamado `config/config.local.php` (este archivo está ignorado por Git y nunca se subirá a repositorios públicos):

```php
<?php
// config/config.local.php — Credenciales Privadas Locales
define('GOOGLE_CLIENT_ID', 'TU_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'TU_CLIENT_SECRET');
```

*(Opcional: Si utilizas el módulo independiente de pruebas en `acceso-google-2fa/`, crea de igual forma `acceso-google-2fa/config.local.php` con las mismas constantes).*

---

## 🚀 Instalación y Despliegue

### Requisitos Previos
- **PHP 8.1 o superior** con las extensiones: `pdo_mysql`, `curl`, `openssl`, `mbstring`.
- **MySQL 8.0+** o **MariaDB 10.5+**.
- Servidor web Apache o servidor embebido de PHP.

---

### En Linux (Arch / Debian / Ubuntu)

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/TU_USUARIO/OdontologiaLocal.git
   cd OdontologiaLocal
   ```

2. **Iniciar el motor de base de datos:**
   ```bash
   # En Arch Linux:
   sudo systemctl start mariadb

   # En Ubuntu / Debian:
   sudo systemctl start mysql
   ```

3. **Importar la base de datos y datos iniciales:**
   ```bash
   mysql -u root -p < infra/base_de_datos.sql
   ```
   *(Si el usuario root no tiene contraseña localmente, omite `-p`)*.

4. **Configurar credenciales locales (Google Cloud):**
   Crea `config/config.local.php` siguiendo la guía de la sección anterior.

5. **Iniciar el servidor de desarrollo:**
   ```bash
   chmod +x iniciar.sh
   ./iniciar.sh
   ```
   Abre tu navegador en: **`http://localhost:8000/`**

---

### En Windows (XAMPP)

1. **Instalar y encender XAMPP:**
   Inicia los módulos de **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. **Copiar el proyecto:**
   Copia o clona la carpeta del proyecto dentro del directorio raíz de Apache:
   `C:\xampp\htdocs\OdontologiaLocal`
3. **Importar la base de datos:**
   - Abre tu navegador en `http://localhost/phpmyadmin/`.
   - Haz clic en la pestaña **Importar**.
   - Selecciona el archivo `infra/base_de_datos.sql` ubicado en la carpeta del proyecto y pulsa **Continuar**.
4. **Configurar credenciales locales:**
   Crea el archivo `config\config.local.php` con tus credenciales de Google.
5. **Acceder a la aplicación:**
   Ingresa a: **`http://localhost/OdontologiaLocal/`**

---

## 👥 Cuentas de Prueba y Roles

El archivo `infra/base_de_datos.sql` incluye una semilla de datos de prueba preconfigurada:

| Rol | Correo Electrónico | Contraseña por Defecto | Nivel de Acceso |
|---|---|---|---|
| **Administrador** | `admin@odontologialocal.pe` | `Admin123*` | Configuración total, roles y finanzas |
| **Odontólogo** | `dr.perez@odontologialocal.pe` | `Odonto123*` | Citas, pacientes e historias clínicas |
| **Recepcionista** | `recepcion@odontologialocal.pe` | `Recep123*` | Registro, agendamiento y facturación |
| **Paciente** | `paciente@odontologialocal.pe` | `Paciente123*` | Consulta de citas y tratamientos |

> 📌 **Simulación en Modo Desarrollo (`MODO_DEV = true`):**  
> En modo local, al iniciar sesión con contraseña se solicita el **código MFA**. Dado que un entorno de desarrollo local habitualmente no dispone de servidor de correo saliente configurado, el código de 6 dígitos se muestra directamente en la pantalla de verificación para facilitar las pruebas.

---

## 🧪 Módulo Independiente de Autenticación

El repositorio incluye la carpeta `acceso-google-2fa/`, un micro-módulo de autenticación independiente desarrollado en archivos directos (sin Front Controller ni arquitectura MVC), ideal para comprender de forma didáctica el protocolo OAuth 2.0 y la matemática de RFC 6238 (TOTP):

```bash
cd acceso-google-2fa
chmod +x iniciar-modulo.sh
./iniciar-modulo.sh
```
Accede desde tu navegador a: **`http://localhost:3000/login.php`**

---

## 📁 Estructura del Proyecto

```
OdontologiaLocal/
├── app/
│   ├── ayudantes/             # Motores auxiliares (Google OAuth, TOTP, SMTP, Helpers)
│   ├── controlador/           # Controladores de la aplicación (MVC)
│   ├── modelo/                # Modelos de dominio y acceso a datos PDO
│   └── vista/                 # Plantillas de interfaz de usuario organizadas por módulo
├── acceso-google-2fa/         # Micro-módulo pedagógico independiente de Google OAuth + 2FA
├── assets/
│   └── css/                   # Estilos visuales del sistema
├── config/
│   ├── config.php             # Configuración centralizada de la aplicación
│   └── config.local.php       # Credenciales locales privadas (ignorado por Git)
├── infra/
│   ├── base_de_datos.sql      # Esquema DDL de 12 tablas + datos semilla
│   ├── migracion_google_2fa.sql
│   └── roles_google.sql       # Script de asignación de roles a cuentas federadas
├── runtime/                   # Registros y logs temporales (ignorado por Git)
├── .env.example               # Plantilla de variables de entorno de ejemplo
├── .gitignore                 # Reglas de exclusión para control de versiones
├── index.php                  # Front Controller principal
├── iniciar.sh                 # Script de arranque rápido para Linux
└── README.md                  # Documentación oficial del repositorio
```

---

## 🛡 Buenas Prácticas de Seguridad Implementadas

- **Gestión de Secretos:** Separación estricta entre el código fuente versionado y los secretos del entorno mediante archivos `.local.php` protegidos por `.gitignore`.
- **Inyección SQL (SQLi):** Uso exhaustivo de PDO con consultas preparadas y enlace estricto de parámetros. Cero concatenación directa de entradas de usuario.
- **Cross-Site Scripting (XSS):** Sanitización y escape contextual en todas las salidas hacia el DOM mediante `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Cross-Site Request Forgery (CSRF):** Tokens criptográficos aleatorios generados con `random_bytes(32)` y validados con `hash_equals()` en cada formulario POST sensible.
- **Robustez de Sesiones:** Flags de sesión configurados: `HttpOnly = true` (inmune a robo de sesión por scripts), `SameSite = Lax` y regeneración periódica de identificador de sesión tras la autenticación.
- **Criptografía de Contraseñas:** Algoritmo nativo `BCrypt` mediante `password_hash()` con coste adaptativo.

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Eres libre de utilizarlo, modificarlo y adaptarlo para fines educativos, personales o comerciales.
