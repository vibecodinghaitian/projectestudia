# EstudiaMed · Traspaso a Claude Code
**Quiz General · MIR España y Revalida Brasil · 28 de noviembre de 2026**

## Archivos en GitHub

El [informe](bancos-mir-revalida/INFORME.txt), el [inventario de fuentes](bancos-mir-revalida/fuentes-oficiales.tsv), el [resumen](bancos-mir-revalida/summary.json) y el [paquete de revisión](bancos-mir-revalida/EstudiaMed-revision-MIR-Revalida.zip) están junto a este documento. Las rutas `/workspace` y `/tmp` de abajo corresponden al entorno de Codex. En otro equipo, usa la ruta de tu copia del repositorio y del paquete descargado. El ZIP inicial de desarrollo queda referenciado como evidencia de la sesión previa.

## Estado y punto de partida

Esta entrega es de inspección, investigación y documentación. Las operaciones de preparación, pruebas con escritura e importación que aparecen abajo son para la fase siguiente. El objetivo es incorporar un primer lote pequeño de preguntas que cumplan todos los controles, como **borradores en un WordPress de desarrollo confirmado**.

| Elemento | Evidencia y estado |
|---|---|
| Repositorio | `/workspace/estudiamed-audit-source`. El árbol estaba limpio y en `feature/ehep-ecuador`. `git branch -a --contains b696778` mostró la rama local y `origin/feature/ehep-ecuador`. `git ls-remote` confirmó que el remoto apunta a `6463f6639ebaa27585bbbfd57ea2ae1b0682e973`. `b696778` aporta la integración, `90dd147` habilita 4/5 opciones y `6463f66` fija Quiz General 1.3.0. [Rama de GitHub](https://github.com/vibecodinghaitian/projectestudia/tree/feature/ehep-ecuador) |
| Paquete inicial | `/workspace/EstudiaMed-bancos-MIR-Revalida.zip` encontrado y verificado con `unzip -t`. El chat contiene el enlace a ese archivo, la copia operativa está en el workspace. La búsqueda por nombre en `/workspace/attachments` y `/workspace/library-files` no devolvió otra copia. La Library remota quedó sin inventariar. |
| Paquete más reciente | `/workspace/EstudiaMed-revision-MIR-Revalida.zip` encontrado y verificado. Usa su `revision-consolidada.json`, `mir-2022-convocatoria-2021-cotejo.json`, `fuentes-oficiales.tsv` e `INFORME.txt`. El ZIP inicial aporta `README.md`, `importacion.patch`, `prueba-wordpress.php` y `prueba-navegador.cjs`. Ambos contienen informe JSON y `wordpress-ready.json` vacío. El parche inicial ya está incluido en la rama. Los ZIP contienen resultados y metadatos. Las copias de los datasets originales están en `/tmp/estudia-bancos`. |
| Entornos | Respondió HTTP 200 en `http://127.0.0.1:9408/`, el WordPress Playground utilizado para pruebas locales con datos ficticios. En el turno anterior Novamira identificó [el WordPress conectado](https://choice-teal-elephant-14.hostingersite.com/) como `production` y contó 1.206 preguntas EHEP. Eso es una referencia histórica. La disponibilidad de un staging administrado y el estado final del despliegue previo siguen sin confirmar. |
| Primer lote | **0 READY y 0 importadas**. Faltan evidencias de reutilización por componente, cotejo textual o de clave en parte del banco y revisión editorial. El filtrado, la corrección y el historial se probaron previamente con registros ficticios. No hay borradores reales que enlazar. |

### Recuentos de referencia

| Banco | Analizadas | Pendientes | Excluidas | Coincidencias y bloqueos |
|---|---:|---:|---:|---|
| MIR | 622 | 549 | 73 | 71 duplicados internos. El informe posterior cotejó 80 registros de MedExpQA con la versión 0 de la convocatoria 2021, celebrada en 2022. En 66 coincidieron enunciado y opciones normalizados. Entre esos 66, 64 claves coincidieron, una discrepó y una carecía de valor válido. El registro 544 preserva la clave coincidente y requiere revisar una afirmación sobre omalizumab. [Ficha EMA](https://www.ema.europa.eu/en/medicines/human/EPAR/xolair) |
| Revalida | 500 | 425 | 75 | 227 coincidencias normalizadas con DrBodeBench. En 2024/1 hubo 95 claves coincidentes, 5 anuladas y una diferencia textual documentada en la muestra. En 2024/2 hubo 94 claves coincidentes y 6 anuladas. Los recuentos por clave cubren ambas ediciones. El cotejo de enunciados cubre la muestra anotada. Las explicaciones de 2024/1, preguntas 1 y 2, están en el expediente y siguen pendientes. |

Los recuentos se leen de los informes recuperados. Las pruebas de navegador, los cotejos y los recuentos EHEP históricos se citan como resultados de la ejecución anterior. Durante esta entrega comprobé archivos, referencias Git, sintaxis JavaScript y la respuesta HTTP local. No ejecuté el importador.

## Fuentes y reutilización

| Fuente | Archivo o versión | Condiciones y alcance |
|---|---|---|
| MedExpQA | Revisión `78b57699763e00e38acd550f28de24d8a65f1643`. Archivos `data/es/{train,dev,test}.es.casimedicos.rag.jsonl`. [Archivos fijados](https://huggingface.co/datasets/HiTZ/MedExpQA/tree/78b57699763e00e38acd550f28de24d8a65f1643/data/es) | El dataset declara CC BY 4.0. Confirmar procedencia y atribución de las explicaciones de CasiMedicos. Los enunciados y opciones provienen de FSE. Excluir los fragmentos de recuperación `rag` del lote. [Ficha](https://huggingface.co/datasets/HiTZ/MedExpQA) · [Antidote CasiMedicos](https://github.com/ixa-ehu/antidote-casimedicos) |
| MedBench Brasil | Commit `8d71e2b0086d83c15115d8f1328c519c9b8aac2b`, `packages/dataset/data/revalida/{2024-1,2024-2,2025-1,2025-2,2026-1}.json`. [Archivos fijados](https://github.com/Precisa-Saude/medbench-brasil/tree/8d71e2b0086d83c15115d8f1328c519c9b8aac2b/packages/dataset/data/revalida) | El repositorio declara Apache 2.0. La autorización para reutilizar enunciados, opciones e imágenes del Inep queda por acreditar. |
| DrBodeBench | `questoes-Revalida+Fuvest.json`, filtrar `origem = INEP`. Copia local verificada por SHA-256 `7f1960c28f8ccfd40589cb7cfcfb3c9ee21d0982829a4de447272725761ee289`. [Archivo](https://huggingface.co/datasets/recogna-nlp/drbodebench/blob/main/questoes-Revalida%2BFuvest.json) | La ficha declara Apache 2.0. Usarlo para detectar coincidencias. Resolver diferencias con la edición oficial. Registrar el hash si se obtiene una nueva copia. |

Para cada pregunta registrar **enunciado, opciones, clave, explicación e imágenes** en `provenance` y `rights` por separado. Para enunciado y opciones MIR, cotejar el cuadernillo original y las condiciones aplicables al ítem. Sanidad permite la reutilización del material cubierto por su aviso con condiciones y excepciones. Para Revalida, documentar el permiso o la base específica aplicable al material original. La disponibilidad gratuita de los PDFs y las licencias de los repositorios no acreditan por sí solas los derechos sobre todos sus componentes. Para imágenes, marcar ausencia cuando el ítem sea autónomo y citar la evidencia del cuadernillo. Si una imagen es necesaria y falta o sus derechos no se verificaron, mantener el ítem fuera del lote. Las explicaciones originales en portugués llevan autoría, referencias y revisión separadas. [Aviso de Sanidad](https://www.sanidad.gob.es/avisoLegal/) · [Inep](https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/revalida/provas-e-gabaritos)

### Pares oficiales

| Examen y versión | Cuadernillo | Hoja de respuestas |
|---|---|---|
| MIR, convocatoria 2021, examen 2022, Medicina versión 0 | [Cuadernillo](https://fse.sanidad.gob.es/hera/api/datos/convocatoria/getDatosCuadernosExamen?titulacion=M&anyo=2021&version=0&opcionSelect=C) | [Hoja histórica oficial](https://fse.sanidad.gob.es/hera/api/datos/convocatoria/getHojaRespuestas?titulacion=M&anyo=2021&version=0) |
| MIR, convocatoria 2022, examen 2023, Medicina versión 0 | [Cuadernillo](https://fse.sanidad.gob.es/hera/api/datos/convocatoria/getDatosCuadernosExamen?titulacion=M&anyo=2022&version=0&opcionSelect=C) | [Hoja histórica oficial](https://fse.sanidad.gob.es/hera/api/datos/convocatoria/getHojaRespuestas?titulacion=M&anyo=2022&version=0) |
| Revalida 2024/1 regular | [Cuadernillo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2024_1_PV_objetiva_regular.pdf) | [Gabarito definitivo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2024_1_GB_objetiva.pdf) |
| Revalida 2024/2 regular | [Cuadernillo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2024_2_PV_objetiva_regular.pdf) | [Gabarito definitivo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2024_2_GB_objetiva.pdf) |
| Revalida 2025/1 regular | [Cuadernillo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_1_PV_objetiva_regular.pdf) | [Gabarito definitivo](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_1_GB_objetiva_definitivo.pdf) |
| Revalida 2025/2, comprobar correspondencia | [Caderno 1](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_2_caderno_1_preliminar.pdf) · [Caderno 2](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_2_caderno_2_preliminar.pdf) | [Clave 1](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_2_gabarito_caderno_1.pdf) · [Clave 2](https://download.inep.gov.br/revalida/provas_e_gabaritos/2025_2_gabarito_caderno_2.pdf) · [Nota oficial](https://download.inep.gov.br/revalida/provas_e_gabaritos/nota_gabarito_enamed_revalida_2025.pdf) |
| Revalida 2026/1, comprobar correspondencia | [Caderno 1](https://download.inep.gov.br/revalida/provas_e_gabaritos/2026_1_caderno_1_.pdf) · [Caderno 2](https://download.inep.gov.br/revalida/provas_e_gabaritos/2026_1_caderno_2_.pdf) | [Definitiva 1](https://download.inep.gov.br/revalida/provas_e_gabaritos/2026_1_gabarito_definitivo_caderno1.pdf) · [Definitiva 2](https://download.inep.gov.br/revalida/provas_e_gabaritos/2026_1_gabarito_definitivo_caderno2.pdf) |

Si un enlace FSE directo exige el contexto del portal, abre **Datos, Exámenes anteriores → Consulta de los Cuadernos de Exámenes Anteriores**, elige Medicina, convocatoria y versión, y consulta **Cuaderno de texto** y **Hoja de respuestas**. Los pares MIR de la tabla se recuperaron en la revisión anterior. Antes de marcar una clave como definitiva, confirma su estado en FSE y conserva el documento de evidencia. El JSON oficial de una versión divide los 210 números en cinco bloques `V_0…V_4` con claves `RC_0…RC_4`. En ese formato, los sufijos indican bloques de la tabla, no versiones diferentes. Revalida 2025/1, 2025/2 y 2026/1 tienen enlaces oficiales identificados. Las copias de sus claves y la correspondencia por número quedaron pendientes en la revisión anterior. [Portal FSE](https://fse.sanidad.gob.es/fseweb/#/principal/datosAnteriores/cuadernosExamen) · [Índice Inep](https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/revalida/provas-e-gabaritos)

## Plan de ejecución

**Estado de los comandos.** «Verificado» significa ejecutado en lectura durante este traspaso o identificado expresamente como ejecutado en la revisión anterior. «Por ejecutar» indica que Claude debe probarlo en su entorno después de recibir autorización para la fase correspondiente.

### 1. Recuperar importador, esquema y filtros

**Objetivo y entradas.** Retomar `6463f66` en `feature/ehep-ecuador`, con `b696778` como referencia del cambio. Consultar el README y los JSON de ambos paquetes.

**Comandos verificados en lectura:**

```sh
cd /workspace/estudiamed-audit-source
git status -sb
git branch -a --contains b696778
git ls-remote origin refs/heads/feature/ehep-ecuador
git show --stat --oneline b696778
unzip -t /workspace/EstudiaMed-bancos-MIR-Revalida.zip
unzip -p /workspace/EstudiaMed-bancos-MIR-Revalida.zip README.md
unzip -t /workspace/EstudiaMed-revision-MIR-Revalida.zip
python wordpress/plugins/estudiamed-quiz/tools/prepare_exam_banks.py --help
node --check wordpress/plugins/estudiamed-quiz/assets/quiz.js
```

**Salida y aceptación.** Localizar `wordpress/plugins/estudiamed-ehep/includes/editorial.php` para normalización, procedencia, duplicados e inserción en borrador. `includes/bank.php` valida las opciones y expone los publicados del módulo. En `wordpress/plugins/estudiamed-quiz/estudiamed-quiz.php`, `estudia_quiz_topics()`, `estudia_quiz_years()` y `estudia_quiz_prepare()` definen catálogo y filtros REST. `assets/quiz.js` usa `data-years` y filtra por idioma, materia, tema y año. Verificar `stem`, `options`, `key` A–D/A–E, `explanation`, `reasons`, `subject`, `topic`, `category`, `lang` y la procedencia por componente. La previsualización de WordPress guarda un transitorio y cuenta como escritura.

### 2. Recuperar y fijar fuentes

**Objetivo y entradas.** Preservar `source_id`, `dataset_id`, `year`, `edition`, `official_version`, `official_id`, URL original y revisión de la fuente. Usar las copias locales si están presentes.

**Comandos verificados en lectura:**

```sh
ls /tmp/estudia-bancos
sha256sum /tmp/estudia-bancos/drbode.json
git -C /tmp/estudia-bancos/medbench-brasil rev-parse HEAD
unzip -p /workspace/EstudiaMed-revision-MIR-Revalida.zip fuentes-oficiales.tsv
```

**Recuperación alternativa, por ejecutar si faltan copias.** Guardar únicamente en el directorio de trabajo de la fase siguiente.

```sh
mkdir -p /tmp/estudia-bancos
for split in train dev test; do
  curl -fLSs "https://huggingface.co/datasets/HiTZ/MedExpQA/resolve/78b57699763e00e38acd550f28de24d8a65f1643/data/es/${split}.es.casimedicos.rag.jsonl" -o "/tmp/estudia-bancos/${split}.jsonl"
done
git clone https://github.com/Precisa-Saude/medbench-brasil /tmp/estudia-bancos/medbench-brasil
git -C /tmp/estudia-bancos/medbench-brasil checkout --detach 8d71e2b0086d83c15115d8f1328c519c9b8aac2b
curl -fLSs 'https://huggingface.co/datasets/recogna-nlp/drbodebench/resolve/main/questoes-Revalida%2BFuvest.json' -o /tmp/estudia-bancos/drbode.json
```

**Salida y aceptación.** Inventario con huellas y revisiones reproducibles, tres particiones españolas y cinco ediciones de Revalida en portugués. Resuelve el año de ejecución frente al de convocatoria antes de vincular documentos.

### 3. Cotejar, deduplicar y resolver derechos

**Objetivo y entradas.** Abrir `revision-consolidada.json`, `validation.json`, `mir-2022-convocatoria-2021-cotejo.json` y `fuentes-oficiales.tsv`. Priorizar MIR convocatoria 2021 v0 y Revalida 2024/1. Cada candidato exige cotejo de número, texto y opciones en orden, clave oficial, ausencia de imagen imprescindible y derechos documentados.

**Lectura verificada:**

```sh
unzip -p /workspace/EstudiaMed-revision-MIR-Revalida.zip summary.json
unzip -p /workspace/EstudiaMed-revision-MIR-Revalida.zip wordpress-ready.json
```

**Salida y aceptación.** Ledger por `source_id` con fuente del cuadernillo, clave, edición, versión, hallazgos de transcripción y `rights` por componente con `license` y `evidence`. Los duplicados por texto o ID, las anulaciones y las imágenes imprescindibles ausentes se excluyen. Las discrepancias, las coincidencias aproximadas, la revisión clínica incompleta y los permisos pendientes mantienen el ítem fuera de `READY`. Las 227 coincidencias entre fuentes son señales de revisión y no equivalen a 227 exclusiones.

### 4. Completar explicaciones Revalida

**Objetivo y entradas.** Revisar primero los comentarios portugueses existentes para preguntas 1 y 2 de 2024/1. Cada explicación debe justificar la clave y cada distractor según el escenario. Confirmar el contexto y la guía aplicable, y distinguir corrección histórica de recomendación clínica actual.

**Comando verificado de consulta:**

```sh
unzip -p /workspace/EstudiaMed-revision-MIR-Revalida.zip revision-consolidada.json |
  python -c 'import json,sys; d=json.load(sys.stdin); print(json.dumps({k:d[k] for k in ("revalida-2024-1-q01","revalida-2024-1-q02")},ensure_ascii=False,indent=2))'
```

**Salida y aceptación.** `explanation_source`, explicación general y una entrada en `reasons` por opción, con referencias e indicación de revisión pendiente. Solo un revisor real puede cerrar `editorial_reviewed`. La primera pregunta conserva su incidencia de transcripción hasta el cotejo manual. [Protocolo brasileño de salud de las mujeres](https://bvsms.saude.gov.br/bvs/publicacoes/protocolo_atencao_%20basica_saude_mulher.pdf) · [Guía de trauma urológico](https://uroweb.org/guidelines/urological-trauma/chapter/urogenital-trauma-guidelines)

### 5. Dry run y primer lote en desarrollo

**Objetivo.** Generar un lote pequeño y probar la pantalla de importación del módulo **Quiz General**. Usar un staging aprobado con copia restaurable o un WordPress local aislado. Primera acción de Claude: confirmar el destino y abrir las dos primeras fichas pendientes con su evidencia, antes de subir nada.

**Normalización verificada en la revisión anterior.** La extracción de los archivos y la ejecución nueva quedan para Claude. El script escribe los resultados en disco y deja WordPress intacto.

```sh
cd /workspace/estudiamed-audit-source
unzip -p /workspace/EstudiaMed-revision-MIR-Revalida.zip revision-consolidada.json > /tmp/estudia-bancos/revision-consolidada.json
python wordpress/plugins/estudiamed-quiz/tools/prepare_exam_banks.py \
  --mir /tmp/estudia-bancos \
  --mir-revision 78b57699763e00e38acd550f28de24d8a65f1643 \
  --revalida /tmp/estudia-bancos/medbench-brasil/packages/dataset/data/revalida \
  --revalida-revision 8d71e2b0086d83c15115d8f1328c519c9b8aac2b \
  --drbode /tmp/estudia-bancos/drbode.json \
  --reviews /tmp/estudia-bancos/revision-consolidada.json \
  --limit 5 --output /tmp/estudia-bancos/salida
```

**Comprobación del destino, por ejecutar en staging mediante WP-CLI si está disponible, con el directorio WordPress confirmado.** Con Novamira usa en su lugar los mismos valores en una consulta PHP de solo lectura y respeta sus revisiones.

```sh
wp eval 'echo home_url("/"), PHP_EOL, wp_get_environment_type(), PHP_EOL; echo function_exists("estudiamed_ehep_bank") ? count(estudiamed_ehep_bank()) : "EHEP no disponible"; echo PHP_EOL;'
```

**Salida y aceptación.** Confirmar URL, tipo de entorno, plugins de la rama y snapshot de base de datos. Guardar el recuento EHEP inicial del destino, con 1.206 como referencia histórica. Si el JSON está vacío, registrar 0 y detener la importación. Si hay elegibles, cargar solo ese lote en **Preguntas EHEP → Importar CSV/JSON → Quiz General**. Revisar la previsualización y confirmar el guardado como borrador. [Ruta de importador en el Playground usado](http://127.0.0.1:9408/wp-admin/edit.php?post_type=estudia_ehep_item&page=estudia-ehep-import). Conservar los IDs nuevos, su estado y el hash del lote. El enlace de revisión por ítem es `<URL de staging>/wp-admin/post.php?post=<ID>&action=edit`.

### 6. Repetir, probar y comprobar reversión

**Objetivo y entradas.** Repetir la previsualización del mismo lote y confirmar que genera cero nuevos registros. Probar filtros, simulacro, clave y persistencia mediante datos ficticios publicados en una copia desechable. Mantener los registros reales del lote como borradores.

**Pruebas ya ejecutadas en la revisión anterior.** El ZIP aporta `prueba-wordpress.php`, un paso `runPHP` del Blueprint de Playground con plugins activos, y `prueba-navegador.cjs`, que usa localhost:9408, Playwright y Chromium. El servidor anterior empleó `/tmp/estudia-tools/quiz-banks-blueprint.json`. El test PHP valida derechos, estado borrador, duplicado por ID fuente, opciones, año y recuento EHEP. El de navegador crea una cuenta ficticia y realiza simulacros. Ejecutarlos exclusivamente en local, tras confirmar que la URL y las rutas de prueba corresponden a la copia desechable.

```sh
node /tmp/quiz-banks-browser.cjs
```

Ese comando se ejecutó en la revisión anterior. Si falta el archivo temporal, extraer `prueba-navegador.cjs` del ZIP a un directorio local con Playwright instalable y ejecutar `node prueba-navegador.cjs`. Esa variante queda por ejecutar en el entorno de Claude.

**Salida y aceptación.** Verificar filtros por idioma, año y especialidad, 4 y 5 opciones intactas, corrección y explicaciones, historial tras recarga y EHEP antes/después. Emitir listado de IDs, recuentos, evidencia de segunda pasada y resultados de prueba. Para revertir el lote, mover a la papelera exclusivamente los IDs nuevos confirmados como borradores de ese lote en staging. Si el lote atraviesa cambios adicionales, restaurar el snapshot solo en la copia aislada y tras descartar escrituras ajenas. Verificar de nuevo recuentos e IDs. Producción requiere su propia decisión y, cuando aplique, confirmación en Novamira.

## Accesos pendientes

Claude requiere lectura de la rama y de ambos ZIP, acceso a los portales oficiales y un espacio para guardar las copias de revisión. Para la fase de escritura requiere un staging identificado, permiso administrativo de Quiz General, sesión de prueba, restauración disponible y el visto bueno editorial y de derechos de los ítems. Si solo está disponible la conexión Novamira de producción, continuar el trabajo local y solicitar autorización específica para cualquier escritura allí. La aprobación de un despliegue previo no acredita permisos de contenido ni autoriza importar un lote distinto.

## Prompt pou Claude Code

```text
Lee HANDOFF-CLAUDE-ESTUDIAMED.md y los dos ZIP. Entra en
/workspace/estudiamed-audit-source y verifica feature/ehep-ecuador en
6463f66, que contiene b696778. Usa el importador existente.

Empieza por el paso 1. Luego confirma la URL y el tipo de WordPress
de desarrollo, y continúa el cotejo con la revisión consolidada.
Prioriza MIR convocatoria 2021 versión 0 y Revalida 2024/1.
Conserva todas las opciones originales y los metadatos oficiales.
Mantén pendientes las discrepancias, revisiones clínicas y derechos
incompletos. Si el lote sigue en cero, entrega el informe y el JSON vacío.

Cuando exista un lote READY y un staging autorizado, respalda la base,
guarda exclusivamente como borradores en Quiz General, repite para
verificar duplicados y comprueba los recorridos del paso 6 en una copia
de pruebas. Entrega números, enlaces de borradores y el plan de reversión.
Respeta las aprobaciones de Novamira. El despliegue de producción es aparte.
```
