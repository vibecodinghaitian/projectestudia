# Campus EstudiaMed

## Instalación y acceso

Instala y activa el tema EstudiaMed y los tres plugins de la entrega. En una instalación existente, actualiza el tema y los plugins sin desinstalarlos. Conserva la página publicada con slug `ehep-ecuador` y el shortcode `[estudiamed_ehep]`. Para una instalación nueva, crea esa página antes de probar el menú. Configura los enlaces permanentes de WordPress para que las rutas públicas lleguen a su controlador.

El primer administrador es la cuenta creada por el instalador de WordPress. Para un sitio existente, usa una cuenta con rol Administrador o asigna ese rol a una cuenta autorizada desde Usuarios. El formulario público nunca concede ese rol. Los estudiantes usan `/registro/`, `/ingresar/` y `/mi-panel/`. La recuperación usa el correo de WordPress. Comprueba la entrega de correo en el entorno de destino.

## Contenido

- Usa Entradas para editar, previsualizar y publicar artículos. La acción Archivar retira la entrada pública y conserva un borrador. `/blog/` muestra entradas publicadas con búsqueda y categorías.
- En Mensajes y anuncios, elige ubicación, escribe el texto y añade la exportación de Canva como imagen destacada. El enlace del diseño editable permanece en administración. Usa Previsualizar aviso para revisar el resultado.
- En Preguntas EHEP → Importar CSV/JSON, descarga la plantilla, elige el módulo y valida antes de guardar. En JSON se aceptan los mismos encabezados u opciones y explicaciones en arrays de cuatro elementos. La clave se expresa con una letra A–D. Las filas válidas se guardan como borradores.
- Una corrección del banco original se publica en un nuevo registro editorial con su ID original, por ejemplo `html-4`. Documenta la clave editorial y la explicación. Los intentos anteriores conservan sus copias y puntuaciones. Las ediciones de clave y explicación de un registro editorial se anotan en su historial.
- Los 51 temas conservan su introducción y conceptos. Se ampliaron Anatomía del Tórax y Obstrucción Intestinal Mecánica con referencias. Los otros 49 quedan pendientes de desarrollo y cotejo editorial. Los nuevos textos de Temas de estudio se vinculan por el ID de la biblioteca y solo aparecen al publicarlos.

## Gemini

Define `GEMINI_API_KEY` como secreto del entorno PHP. Los editores de preguntas y temas mostrarán la acción de generar cuando la clave esté configurada. Las propuestas se guardan por separado para revisión manual y no modifican la clave ni publican contenido. La edición manual funciona sin credenciales. Configura la entrega de correo, el dominio público y un entorno de prueba antes de publicar.

## Bienvenida por correo y WhatsApp

Actualiza el plugin EstudiaMed Campus a 1.1.0 y entra en **Mensajes y anuncios → Bienvenida**. Ambos canales empiezan desactivados. El campus activa las cuentas al completar el registro, guarda el consentimiento de WhatsApp y pone la bienvenida en cola. Los usuarios existentes no reciben envíos retroactivos. Si se instala un flujo de verificación de correo, la integración debe emitir `estudia_campus_account_activated` solo tras verificar la cuenta. La cola usa WP-Cron. Si en producción se desactiva WP-Cron, programa en el hosting la ejecución periódica de los eventos vencidos.

### FluentSMTP

Instala y activa FluentSMTP en el mismo WordPress. En **Ajustes → FluentSMTP**, crea una conexión con un remitente autorizado. Los permisos y las credenciales dependen del transporte elegido: un usuario y contraseña o un token SMTP, una clave de API de envío, o una autorización OAuth según el proveedor. Configura el remitente y verifica su dominio cuando sea necesario. Desactiva el modo de simulación antes de habilitar la bienvenida. Envía primero una prueba en FluentSMTP y después una prueba de EstudiaMed a un destinatario que la haya autorizado. EstudiaMed llama a `wp_mail()` en el servidor. El estado «Aceptado por el proveedor» no confirma recepción en la bandeja de entrada. No se configura un webhook de correo porque FluentSMTP admite varios transportes y no ofrece un estado de entrega común.

[Instalación oficial de FluentSMTP](https://fluentsmtp.com/docs/installing-fluent-smtp/) · [Conexiones y ajustes](https://fluentsmtp.com/docs/fluent-smtp-settings/)

### Kapso

Conecta el número remitente al proyecto de Kapso y obtén su ID de número de Meta. Define `KAPSO_API_KEY` como secreto del entorno PHP. Usa una clave de proyecto que tenga acceso al remitente, lectura de las plantillas de su cuenta de WhatsApp y permiso para enviar con ese número. En Kapso, solicita aprobación para una plantilla de cuerpo en español con dos parámetros nombrados: `nombre` y `enlace_panel`. Texto propuesto:

> Hola, {{nombre}}. Tu cuenta de EstudiaMed ya está activa. Entra a tu panel para estudiar temas de Medicina, practicar con preguntas y acceder al Examen de Ecuador (EHEP). {{enlace_panel}}

En **Bienvenida**, guarda el ID, el nombre exacto de la plantilla y su código de idioma. Pulsa **Verificar remitente y plantilla**. EstudiaMed comprueba que Kapso informe que el remitente está conectado y que la plantilla está aprobada y tiene los parámetros esperados. Después activa el canal. El enlace del panel se construye a partir del dominio de WordPress. Ajusta el dominio de producción antes de enviar. El estudiante puede retirar el consentimiento o quitar el número en **Mi panel → Preferencias de WhatsApp**.

Para confirmaciones, registra un webhook de eventos de Kapso asociado al número y suscríbelo a `whatsapp.message.delivered`, `whatsapp.message.read` y `whatsapp.message.failed`. Usa la URL mostrada en **Bienvenida** y define el secreto compartido como `KAPSO_WEBHOOK_SECRET` en PHP. EstudiaMed valida la firma HMAC y relaciona el evento con el remitente y el ID devuelto por Kapso.

[Plantillas de WhatsApp](https://docs.kapso.ai/docs/whatsapp/templates/simple-text) · [Firma del webhook](https://docs.kapso.ai/docs/platform/webhooks/security) · [Eventos de mensajes](https://docs.kapso.ai/docs/platform/webhooks/message-events)

### Pruebas y reintentos

El historial refleja la cola, aceptación, entrega confirmada y fallos. Para evitar duplicados, un resultado incierto requiere comprobar el registro del proveedor antes de realizar otro envío. Los fallos confirmados admiten reintento. El trabajador vuelve a comprobar el consentimiento de WhatsApp y que el número sea el del envío original.

La prueba local interceptó `wp_mail()` y las peticiones a Kapso. Validó tres registros, la ausencia de mensajes sin consentimiento, el rechazo del proveedor, la repetición del evento, un webhook firmado y el bloqueo de reintentos tras retirar el consentimiento. Los envíos con credenciales reales y la recepción en un buzón o teléfono de producción siguen pendientes.
