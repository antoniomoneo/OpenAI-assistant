# OpenAI Assistant

This plugin allows embedding OpenAI Assistants via a shortcode.

## File structure

- `openai-assistant.php` – main plugin file.
- `css/assistant.css` – plugin styles.
- `js/assistant.js` – admin scripts.
- `js/assistant-frontend.js` – frontend scripts.

## AMP/mobile support

The plugin no longer embeds the chat in an `amp-iframe`. If you need to use the
shortcode on a page that is served as AMP, disable the AMP version of that page
so the normal responsive layout loads. This ensures the chat works correctly on
mobile devices.
