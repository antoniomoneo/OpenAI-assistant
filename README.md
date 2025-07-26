# OpenAI Assistant

This plugin allows embedding OpenAI Assistants via a shortcode.

## File structure

- `openai-assistant.php` – main plugin file.
- `css/assistant.css` – plugin styles.
- `js/assistant.js` – admin scripts.
- `js/assistant-frontend.js` – frontend scripts.

## AMP/mobile support

When the shortcode is used inside an AMP page, the plugin now embeds the chat in
an `amp-iframe`. The iframe loads a non-AMP version of the chat so the full
JavaScript functionality works on mobile devices. The iframe now uses a
`responsive` layout so it adapts to smaller screens. No configuration is
required.
