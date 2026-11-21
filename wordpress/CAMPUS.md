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
