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

## Bancos externos MIR y Revalida

`tools/prepare_exam_banks.py` lee las tres particiones españolas JSONL de MedExpQA y los JSON del directorio `packages/dataset/data/revalida` de MedBench. Usa Python 3.9 o superior. Pin de MedExpQA: `78b57699763e00e38acd550f28de24d8a65f1643`. Pin de MedBench: `8d71e2b0086d83c15115d8f1328c519c9b8aac2b`. Para reproducir la detección de coincidencias, usa `questoes-Revalida+Fuvest.json` de DrBodeBench con SHA-256 `7f1960c28f8ccfd40589cb7cfcfb3c9ee21d0982829a4de447272725761ee289`.

```sh
python tools/prepare_exam_banks.py \
  --mir /ruta/MedExpQA/data/es --mir-revision 78b57699763e00e38acd550f28de24d8a65f1643 \
  --revalida /ruta/medbench-brasil/packages/dataset/data/revalida \
  --revalida-revision 8d71e2b0086d83c15115d8f1328c519c9b8aac2b \
  --drbode /ruta/drbode.json --reviews /ruta/revisiones.json --output /ruta/salida
```

El archivo de revisión se indexa por `source_id`, visible en `validation.json`. Para cada ítem revisado registra la letra `official_key`, la URL específica `official_key_url`, la versión de cuadernillo `official_version` cuando el identificador sea ambiguo, y evidencia separada en `rights` por `stem`, `options`, `key`, `explanation` e `images`. Cada entrada de derechos requiere `license` y la URL `evidence`. Usa `booklet_url` para el enlace del cuadernillo original. La marca `editorial_reviewed` indica que se cotejaron enunciado, alternativas y explicaciones. Para Revalida, un comentario editorial aportado explícitamente requiere `explanation_source`, `explanation` y `reasons`. Una discrepancia de transcripción registrada bloquea la salida del ítem.

`summary.json` contiene los recuentos. `validation.json` contiene trazabilidad por registro, sin enunciados ni alternativas. `wordpress-ready.json` incluye hasta 15 registros listos por examen de forma predeterminada. En un WordPress de desarrollo respaldado, carga ese JSON en **Preguntas EHEP → Importar CSV/JSON** con el módulo **Quiz General**. Previsualiza y guarda en borrador. La reimportación comprueba el identificador de fuente y el enunciado. Publica en desarrollo solo los borradores autorizados que desees probar. Los filtros de examen, especialidad y año se habilitan según el contenido publicado en el idioma activo. Las sesiones reutilizan el flujo actual de corrección, simulacro e historial local.

Fuentes: [MedExpQA](https://huggingface.co/datasets/HiTZ/MedExpQA), [MedBench](https://github.com/Precisa-Saude/medbench-brasil), [DrBodeBench](https://huggingface.co/datasets/recogna-nlp/drbodebench), [provas e gabaritos del Inep](https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/revalida/provas-e-gabaritos).
