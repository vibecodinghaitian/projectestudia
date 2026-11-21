# EstudiaMed EHEP Ecuador

Activate this plugin alongside the EstudiaMed theme. In a private test installation, create a WordPress page with slug `ehep-ecuador` and insert `[estudiamed_ehep]`. The included EstudiaMed theme links to it when that page is published. Existing modules and entries retain their original URLs. Standard WordPress accounts own each attempt.

The bank includes six original Spanish-language items and all 1,200 Spanish-language practice questions authorized for publication from `etid-medsin.html`. Imported items retain their source question and page numbers, keyed answers, fixed-order flags and all three embedded images. They are grouped under **Banco general de medicina** because the HTML provides no specialty field. The review shows the source answer and identifies the absence of a source explanation. These items are educational practice.

Editors can add published items under **Preguntas EHEP**, supplying a specialty, provenance, four alternatives, a key and explanations. Draft and incomplete entries are excluded. To regenerate the imported bank from the repository root, run `python3 wordpress/plugins/estudiamed-ehep/tools/build-html-bank.py etid-medsin.html`.

The dashboard links to [CACES](https://www.caces.gob.ec/examen-de-habilitacion-para-el-ejercicio-profesional/) for official updates. Session length and question count are user-selected practice parameters. The module makes no equivalence or passing-score claim.

The plugin stores attempts in its own indexed WordPress table. Each attempt stores a snapshot of its items. The REST API derives ownership from the logged-in user, grades on the server, and returns answer explanations after a practice answer or completed mock. The table and bank entries persist on deactivation.
