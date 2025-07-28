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

## AMP/mobile support

The plugin no longer embeds the chat in an `amp-iframe`. When the shortcode is
rendered on an AMP page, the assistant output is skipped and a notice is
displayed instead. If you need to use the shortcode, disable the AMP version of
that page so the normal responsive layout loads. This ensures the chat works
correctly on mobile devices.
