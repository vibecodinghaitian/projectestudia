# EstudiaMed para WordPress

## Instalación

1. Activa el tema `themes/estudiamed` y los plugins `plugins/estudiamed-ehep` y `plugins/estudiamed-quiz` en WordPress con PHP 7.4 o posterior. Para instalar desde el panel, usa cada ZIP de la entrega por separado.
2. Conserva la página EHEP existente. En una instalación nueva, crea una página publicada con slug `ehep-ecuador` y el shortcode `[estudiamed_ehep]`. El tema la añade automáticamente al menú.
3. Accede con un usuario de WordPress para practicar y guardar intentos de EHEP. El banco, los intentos y el progreso de una instalación existente se conservan al actualizar el plugin.
4. Quiz General funciona con preguntas de muestra aun sin credenciales de IA. Sus resultados y favoritos se guardan en el navegador. EHEP guarda los datos en la cuenta de WordPress.

## Alcance

La biblioteca reutiliza 51 temas en siete materias del proyecto original. Quiz General conserva la selección dependiente de categoría y materia e incluye un filtro por tema. Sus siete preguntas originales de demostración cubren fisiología y epidemiología. Los demás contenidos requieren una importación o un proveedor configurado. Las claves de los simulacros se proporcionan al entregar. Requiere revisión cualquier contenido generado con IA antes de incorporarlo a un banco.

EHEP conserva 1.200 preguntas de la Convocatoria I de 2023 y seis preguntas originales de práctica, con ocho revisiones clínicas registradas para los ítems de 2023. Las preguntas importadas sin clasificación clínica se agrupan en el banco general por convocatoria. Los autores pueden asignar un tema a las preguntas nuevas en WordPress. El tutor y la preparación de borradores requieren la clave de Gemini en el entorno del servidor.

## Verificación

Se probó la portada en español, el acceso a EHEP desde el menú, la búsqueda de temas, la práctica con explicaciones, los favoritos, la entrega y corrección de simulacros, las omisiones y la recuperación del historial tras recargar. La prueba se ejecutó en una instalación temporal de WordPress con SQLite y Chromium a 375 px. La aplicación original en Netlify devolvió HTTP 403 al iniciar la generación de un simulacro.
