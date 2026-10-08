# ⚖️ LexGestión — Sistema de gestión de casos legales

Software completo para **bufetes de abogados** construido con **PHP · Laravel 12 · Breeze · SQLite**.
Gestiona clientes, expedientes, audiencias, citas, honorarios, gastos, chat interno y un **asistente legal con IA (Gemini / ChatGPT)**. Funciona hoy mismo en **XAMPP** sin servidor de base de datos: todo se guarda en un archivo SQLite.

> Acceso basado en roles: **Superadministrador**, **Abogado Senior** y **Abogado Junior**. Cada abogado ve sus propios asuntos; el administrador ve todo el bufete.

---

## ✨ Funcionalidades

| Módulo | Qué incluye |
|---|---|
| **Inicio de sesión y notificaciones** | Login con marca del bufete, cuentas demo de un clic, usuarios desactivables, campana de notificaciones (caso asignado, audiencia programada, recordatorio 24 h antes, gasto por aprobar / aprobado / rechazado / reembolsado). |
| **Panel de control** | Indicadores de clientes, casos activos/cerrados, audiencias de la semana, citas de hoy, ingresos del mes y del año, cartera por cobrar, gastos; gráficos de ingresos vs. gastos (12 meses), casos por estado y por tipo; próximas audiencias, casos recientes y gastos por aprobar. |
| **Clientes** | Personas y empresas, documento, contacto, abogado a cargo, notas internas. Búsqueda y filtros. Número de documento único. |
| **Vista 360 del cliente** | Casos, pagos, audiencias, documentos, citas e historial de comunicaciones en una sola pantalla, con honorarios pactados, pagado, saldo y próxima audiencia. |
| **Llamar y enviar correo** | Botones *Llamar* (`tel:`), *WhatsApp* y *Correo* (formulario que envía por SMTP y queda registrado), y registro de llamadas/reuniones. |
| **Casos (expedientes)** | Número de radicado, cliente, **tribunal/juzgado, juez, tipo de caso**, estado, prioridad, abogado responsable y asistente, honorarios y forma de pago, descripción/estrategia. |
| **Verificación de número duplicado** | Comprobación **en vivo** mientras se escribe (AJAX) + validación en servidor. |
| **Documentos del caso** | Carga múltiple de **PDF, imágenes, video, audio** y ofimática; visor integrado; almacenamiento **privado** (sólo el equipo del caso puede verlos o descargarlos). |
| **Partes y notas** | Demandantes, demandados, testigos, peritos, apoderados de la contraparte…; notas con autor, edición y notas fijadas. |
| **Informe imprimible del caso** | Hoja lista para imprimir / guardar en PDF con datos, partes, audiencias, resumen financiero, documentos y notas. |
| **Protección de eliminación** | No se puede borrar un caso con audiencias, pagos, gastos, documentos o citas; ni un cliente con casos/pagos; ni datos maestros en uso; ni usuarios con casos asignados (se desactivan). |
| **Calendario judicial** | FullCalendar (mes, semana, día, agenda) con audiencias y citas por colores; clic en un día para programar; filtro por abogado (admin). |
| **Programador de audiencias** | Tipo, fecha/hora, duración, juzgado, sala o enlace virtual, juez, abogado asignado, estado y resultado. |
| **Citas** | Con clientes o **prospectos**, modalidad presencial / videollamada / telefónica, **tarifa de consulta**, control de solapamiento de horario, cambio rápido de estado. |
| **Pagos y recibos** | Recibos con numeración consecutiva automática (`REC-2026-00001`), **valor en letras**, saldo del caso a la fecha, logotipo y firmas. Seguimiento de honorarios por caso. |
| **Gastos con aprobación / reembolso** | Pendiente → aprobado / rechazado (con motivo) → reembolsado; soporte adjunto; el senior responsable aprueba los gastos de su equipo y el superadministrador registra el reembolso. |
| **Datos maestros** | Tipos de caso, estados de caso (color y si es de cierre) y juzgados/tribunales. |
| **Usuarios** | Alta/edición, roles, activar/desactivar, último acceso e **«Iniciar sesión como»** el abogado (con banner para volver). |
| **Chat interno** | Mensajería entre abogados con indicador de no leídos y actualización automática. |
| **Asistente IA** | Chat en lenguaje natural con **Google Gemini** u **OpenAI ChatGPT**, con el contexto de los datos que el usuario puede ver («¿Qué audiencias tengo esta semana?», «¿Quién me debe honorarios?»), historial de conversaciones e **instrucciones personalizadas** por usuario. Sin clave funciona en **modo sin conexión** respondiendo consultas frecuentes con los datos del sistema. |
| **Tema y logotipo** | 9 colores de interfaz, logotipo propio (también en recibos e informes) y **modo oscuro** por usuario. |
| **Claves de IA y copias de seguridad** | Claves guardadas **cifradas**; prueba de conexión; copias ZIP con la base de datos y los documentos (crear, descargar, eliminar). |
| **setup.php** | Instalador y **restablecimiento de los datos de demostración** desde consola o navegador. |

## 👥 Roles y permisos

| Acción | Superadministrador | Abogado Senior | Abogado Junior |
|---|:-:|:-:|:-:|
| Ver casos | Todos | Donde es responsable o asistente | Donde es responsable o asistente |
| Crear clientes, casos, audiencias, citas, notas, documentos | ✔ | ✔ | ✔ |
| Eliminar casos / clientes (sin registros vinculados) | ✔ | Sólo los suyos | ✘ |
| Registrar pagos | ✔ | ✔ | ✔ |
| Editar / eliminar pagos | ✔ | ✔ (de sus casos) | ✘ |
| Aprobar / rechazar gastos | ✔ | De los casos que lidera (no los propios) | ✘ |
| Marcar gastos como reembolsados | ✔ | ✘ | ✘ |
| Usuarios, datos maestros, configuración, copias, «iniciar sesión como» | ✔ | ✘ | ✘ |

Las conversaciones con la IA son privadas de cada usuario.

---

## 🚀 Instalación

### Requisitos
- PHP **8.2 o superior** con extensiones `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo` (y `zip`, `curl`, `gd` recomendadas).
- [Composer](https://getcomposer.org/).
- Node.js **sólo** si va a modificar estilos/JS (los recursos compilados ya vienen en `public/build`).

### Opción A — XAMPP (Windows / macOS / Linux)

1. Copie o clone el proyecto dentro de `htdocs`, por ejemplo `C:\xampp\htdocs\bufete`.
2. En `C:\xampp\php\php.ini` verifique que estén activas (sin `;` al inicio):
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   extension=fileinfo
   extension=zip
   extension=gd
   upload_max_filesize = 64M
   post_max_size = 64M
   ```
   y reinicie Apache desde el panel de XAMPP.
3. Abra una terminal en la carpeta del proyecto e instale las dependencias:
   ```bash
   composer install
   ```
4. Ejecute el instalador (crea `.env`, la clave `APP_KEY`, la base SQLite y carga los datos de demostración):
   ```bash
   php setup.php
   ```
   *(o abra `http://localhost/bufete/setup.php` en el navegador del mismo equipo).*
5. Edite `APP_URL` en `.env` con la dirección real, p. ej. `APP_URL=http://localhost/bufete`.
6. Abra **http://localhost/bufete** (el archivo `.htaccess` de la raíz redirige a `public/`; también funciona `http://localhost/bufete/public`).

### Opción B — Servidor de desarrollo de PHP

```bash
composer install
php setup.php
php artisan serve
```
Abra **http://127.0.0.1:8000**.

### Cuentas de demostración (contraseña `password`)

| Correo | Rol |
|---|---|
| `admin@bufete.test` | Superadministrador |
| `senior@bufete.test` · `senior2@bufete.test` | Abogado Senior |
| `junior@bufete.test` · `junior2@bufete.test` | Abogado Junior |

> En producción cambie las contraseñas, ponga `DEMO_MODE=false`, `APP_DEBUG=false` y `SETUP_WEB_ENABLED=false` en `.env`.

## 🔄 Restablecer los datos de demostración

Borra **todo** (incluidos documentos subidos) y vuelve a cargar los datos de ejemplo:

- **Consola:** `php setup.php --reset` o `php artisan bufete:demo`
- **Navegador:** `http://localhost/<carpeta>/setup.php` — sólo desde el mismo equipo (`localhost`) y, si ya hay datos, pide el correo y la contraseña de un superadministrador. Se desactiva con `SETUP_WEB_ENABLED=false`.

## 🤖 Asistente de IA (Gemini / ChatGPT)

1. Obtenga una clave:
   - Google Gemini: <https://aistudio.google.com/apikey>
   - OpenAI: <https://platform.openai.com/api-keys>
2. Entre como superadministrador → **Configuración → Inteligencia artificial**, elija el proveedor, pegue la clave (se guarda cifrada con `APP_KEY`) y pulse **Probar conexión**.
   *(También puede definir `GEMINI_API_KEY` / `OPENAI_API_KEY` en `.env` antes de ejecutar `php setup.php --reset`).*
3. Modelos por defecto: `gemini-2.5-flash` y `gpt-4o-mini` (editables). La URL base de OpenAI admite cualquier API compatible con `/chat/completions`.
4. Cada usuario puede definir sus **instrucciones personalizadas** desde el asistente. Puede desactivar el envío del contexto del bufete si no desea compartir datos con el proveedor.

Sin clave configurada, el asistente funciona en **modo sin conexión** y responde preguntas sobre audiencias, citas, casos, pagos, saldos y gastos usando los datos del sistema.

## ✉️ Correo saliente

Por defecto `MAIL_MAILER=log` (los correos se escriben en `storage/logs/laravel.log`). Para enviarlos de verdad configure SMTP en `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=su-correo@gmail.com
MAIL_PASSWORD=contraseña-de-aplicación
MAIL_FROM_ADDRESS=su-correo@gmail.com
```

## ⏰ Recordatorios de audiencias

Se envían automáticamente al abrir el panel de control (no requiere cron). Si desea programarlos:

```bash
php artisan schedule:work            # desarrollo
* * * * * php /ruta/artisan schedule:run   # cron en producción
php artisan bufete:recordatorios     # manual
```

## 💾 Copias de seguridad

**Configuración → Copias de seguridad → Crear copia ahora** genera un ZIP con `database/database.sqlite` y `storage/app/private` (documentos, soportes y logotipo). Para restaurar, copie esos archivos sobre el proyecto usando la misma `APP_KEY`.

## 🛠️ Desarrollo

```bash
npm install
npm run dev        # recarga en caliente
npm run build      # recompila public/build (súbalo al repositorio)
php artisan test   # pruebas automatizadas
vendor/bin/pint    # estilo de código
```

### Estructura principal

```
app/
  Enums/                 Roles y estados (audiencias, citas, gastos, pagos, prioridad…)
  Http/Controllers/      Clientes, casos, documentos, audiencias, calendario, citas, pagos,
                         gastos, chat, IA, notificaciones, admin (usuarios, datos maestros, configuración, respaldos)
  Models/                Client, LegalCase, CaseParty, CaseNote, Document, Hearing, Appointment,
                         Payment, Expense, Communication, Message, AiConversation, Setting…
  Policies/              Reglas de acceso por rol (cada abogado ve sus asuntos)
  Services/Ai/           Gemini, OpenAI, asistente sin conexión y contexto del bufete
  Services/              Notificaciones, copias de seguridad, PDF de ejemplo
config/bufete.php        Catálogos (tipos de documento, partes, categorías…), temas e IA
database/seeders/        Datos de demostración
resources/views/         Vistas Blade (Breeze + Tailwind + Alpine.js)
setup.php                Instalador / restablecer datos de demostración
```

## 🔐 Seguridad

- Autorización con *policies* en cada recurso; los documentos y soportes se sirven sólo a usuarios autorizados desde almacenamiento privado.
- Claves de IA cifradas; conversaciones con la IA privadas.
- Registro público deshabilitado: los usuarios los crea el administrador.
- `setup.php` vía web limitado a `localhost` y protegido con credenciales de superadministrador.

---

Hecho con Laravel, Breeze, Tailwind CSS, Alpine.js, Chart.js y FullCalendar. Las respuestas de la IA son orientativas y no sustituyen el criterio profesional del abogado.
