# EstudiaMed Quiz General

Activate the plugin alongside the EstudiaMed theme. The theme adds a Quiz General tab when the shortcode is available. The quiz uses WordPress login and the Spanish categories and subjects (and legacy Portuguese support when hosted by a compatible theme) recovered from the original EstudiaMed build. EHEP remains a separate question bank.

## Provider setup

Go to **Settings → EstudiaMed Quiz** as a WordPress administrator. Choose Gemini or OpenAI, supply a model with JSON output support and enter the API key directly in WordPress. The key is stored in a server-side, non-autoloaded option, is absent from the HTML and API responses, and can be removed in settings. Limit the provider key's scope and budget in its own dashboard. AI is off by default. Once configured, the learner can opt in per session, and the server uses it only for the shortfall after checking the selected question bank. The app labels generated questions as unreviewed and does not publish them to the bank.

## Question import

Convert each supplied PDF and answer key to reviewed JSON. For scanned PDFs, use OCR and compare question stems, answer alternatives and referenced illustrations with the original. In the same settings screen, upload an array of up to 500 items. Each item uses `lang` (`es` or `pt`), `type` (`mcq` or `case`), exact `category` and `subject` strings from `catalog.json`, `stem`, four distinct `options`, an optional `topic`, a zero-based integer `key`, an `explanation` and a `source` with the PDF and page. Reimporting the same stem in the same language, type, category and subject updates that record. No PDF has been submitted for this general quiz yet.

General Quiz withholds keys and explanations on the server during exam mode. Completed attempts and favorites are stored in the learner’s browser. The separate EHEP simulator retains server-side scoring and account-level history and favorites.
