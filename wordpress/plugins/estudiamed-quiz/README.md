# EstudiaMed Quiz General

## Quiz General 1.2

El shortcode y las rutas de creación y entrega de intentos siguen en sus ubicaciones originales. La portada de EstudiaMed usa la sesión WordPress. El módulo mantiene los favoritos y el historial en la misma clave local por cuenta. Los nuevos resultados agregan tema, modo, idioma, duración y marcas de revisión. El historial reside en el navegador, por lo que sus datos dependen del dispositivo y del perfil de navegador.

El formulario ofrece solo combinaciones con preguntas de banco. El orden prioriza ítems que el estudiante todavía no ha visto en su historial local. Si se solicita más contenido del disponible, se ofrece iniciar con la cantidad real. Las preguntas de muestra se identifican en el ejercicio. La generación de IA existente queda disponible como código y configuración del proveedor, pero Quiz General no la usa para completar intentos.

En modo Examen el servidor guarda respuestas parciales y aplica el plazo aunque el navegador se retrase. Al vencer, entrega las respuestas aceptadas antes del plazo. La calificación y sus explicaciones se revelan al finalizar. El intento de examen se asocia a la cuenta y el token temporal. Un intento activo no se puede recuperar al recargar la página.

### Edición

En **Ajustes → EstudiaMed Quiz**, el catálogo adicional permite introducir categorías y materias por idioma. El editor de **Preguntas EHEP** y su importador CSV/JSON se reutilizan con el módulo **Quiz General**. Los temas se derivan de las preguntas publicadas de la materia. Las importaciones quedan en borrador. Revisa las referencias, la clave y los distractores antes de publicar. Puedes archivar una pregunta para excluirla de los futuros intentos. Esto conserva las copias en resultados anteriores.

Quiz General acepta cuatro o cinco opciones. En CSV, deja `option_e` y `reason_e` en blanco para las preguntas de cuatro opciones. En JSON, envía cuatro o cinco elementos en `options` y `reasons`. La clave puede ser A–D o A–E según corresponda.

Tipos disponibles para edición: `mcq`, `case`, `reasoning`, `basic` y `application`. Los campos opcionales de una importación general incluyen `lang` (`es` o `pt`), `difficulty` (`beginner`, `intermediate`, `advanced`, `exam`) y `url` (referencia). El campo `source` recoge la cita bibliográfica. Para publicar una traducción, importa o crea un registro específico en ese idioma.

### Referencia curricular

Se consultaron los pénsum de Medicina de UTESA con vigencia enero de 2013 (Of. SG. No. 129/12) y enero de 2024 (Of. SG. No. 101/22). Ambos registran Anatomía I y II (MED-172, MED-212), Fisiología I y II (MED-232, MED-292), Bioestadística (MED-160) y Epidemiología (MED-662). El catálogo incorpora las materias faltantes de esa comparación sin cambiar las etiquetas de las preguntas existentes. «Salud Pública» es una agrupación editorial de EstudiaMed. Los pénsum orientan la clasificación curricular. Cada pregunta conserva su fuente médica por separado.

### Verificación local

Activa EstudiaMed Campus, EHEP y Quiz General junto al tema EstudiaMed en una instalación de pruebas. Crea dos cuentas de estudiante locales, abre `/#quiz-general` y completa una práctica y un simulacro. Verifica que el simulacro solo revele la corrección al entregar y que el token no sea accesible desde otra cuenta. Revisa el historial al recargar y cambia a portugués para comprobar el caso traducido.
