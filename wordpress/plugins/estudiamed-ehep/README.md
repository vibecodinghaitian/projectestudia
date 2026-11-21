# EstudiaMed EHEP Ecuador

Activate this plugin alongside the EstudiaMed theme. In a private test installation, create a WordPress page with slug `ehep-ecuador` and insert `[estudiamed_ehep]`. The included EstudiaMed theme links to it when that page is published. Existing modules and entries retain their original URLs. Standard WordPress accounts own each attempt.

The bank starts with six original Spanish-language items. Editors can add published items under **Preguntas EHEP**, supplying a subject, provenance, four alternatives, one key and explanations for each alternative. Draft and incomplete entries are excluded. The uploaded legacy HTML matches the original `etid-medsin.html` exactly and includes 1,200 Spanish-language items. Before importing it, confirm its origin, publication rights and clinical review status. The module excludes those items until that review occurs.

The dashboard links to [CACES](https://www.caces.gob.ec/examen-de-habilitacion-para-el-ejercicio-profesional/) for official updates. Session length and question count are user-selected practice parameters. The module makes no equivalence or passing-score claim.

The plugin stores attempts in its own indexed WordPress table. Each attempt stores a snapshot of its items. The REST API derives ownership from the logged-in user, grades on the server, and returns answer explanations after a practice answer or completed mock. The table and bank entries persist on deactivation.
