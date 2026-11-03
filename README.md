# ZYVORA AI

A WordPress administrator AI workspace with a chat interface and configurable OpenAI-compatible Chat Completions provider.

## Features

- WordPress admin dashboard.
- Provider endpoint, model, and API key settings.
- Server-side API requests; the API key is never sent to browser JavaScript.
- Capability checks and WordPress nonce verification for chat requests.
- Responsive dark ZYVORA workspace.
- Uses the WordPress HTTP API; no external PHP dependencies.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- An API key for an OpenAI-compatible Chat Completions provider.

## Install

1. Download this repository as a ZIP.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate **ZYVORA AI**.
4. Open **ZYVORA AI** in the WordPress admin menu.
5. Enter your HTTPS Chat Completions endpoint, model name, and API key, then save.
6. Send a test prompt in the assistant panel.

Default endpoint: `https://api.openai.com/v1/chat/completions`  
Default model: `gpt-4o-mini`

## Security notes

- Only users with `manage_options` can access the admin page and chat action.
- Requests are protected by a WordPress nonce.
- API keys are stored in the WordPress options table. Use a trusted host, HTTPS, and restricted administrator accounts.
- A blank API-key field preserves the currently saved key.
- Prompts are sent to the provider endpoint configured by the site administrator. Review that provider's privacy and retention policies.
- This initial release is an admin-only assistant. It does not publish content automatically, expose a public chat endpoint, or modify posts.

## Compatibility

The provider must accept an OpenAI-compatible `/chat/completions` request and return `choices[0].message.content`.

## Development

No build step is required. The plugin uses plain PHP, CSS, and JavaScript.

## License

GPL-2.0-or-later.
