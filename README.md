# OpenAI Assistant

This plugin allows embedding OpenAI Assistants via a shortcode.

The administration page lets you manage multiple assistants. You can add new
rows for assistants or remove existing ones before saving the settings. Two
additional fields—**Model** and **Description**—allow configuring data required
by the OpenAI Assistants API. Legacy fields are still displayed but appear in
light grey to indicate they are deprecated.

Each assistant now has a **Debug** checkbox. When enabled, the chat UI shows a
log of the actions performed during each request, which can be copied to share
with ChatGPT when troubleshooting.

## File structure

- `openai-assistant.php` – main plugin file.
- `css/assistant.css` – plugin styles.
- `js/assistant.js` – admin scripts (add/remove assistants).
- `js/assistant-frontend.js` – frontend scripts.
- `js/assistant-amp.js` – AMP-compatible frontend script.

## AMP/mobile support

When a page is served in AMP mode, the shortcode wraps the chat interface in an
`amp-script` element that loads a lightweight JavaScript file. This allows the
assistant to work directly on AMP pages without relying on iframes.
