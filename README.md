# ZYVORA AI

A WordPress AI workspace with a secure administrator chat and content generation studio.

## Features (v1.1.0)

- Dark, responsive WordPress admin dashboard.
- AI assistant chat for site administrators.
- Content Studio: blog articles, SEO title/meta ideas, rewrites, translations, social posts, outlines and product descriptions.
- Choose output language: English, Hindi, Telugu, Tamil, Gujarati and more.
- Save generated content as a **WordPress draft** (never auto-published).
- Configurable OpenAI-compatible Chat Completions endpoint and model.
- API key stays server-side; administrator capability checks and WordPress nonce verification.
- No build tools or external PHP dependencies.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- API key for an OpenAI-compatible Chat Completions provider.

## Install

1. Download this repository as ZIP.
2. WordPress Admin → Plugins → Add New Plugin → Upload Plugin.
3. Upload the ZIP and activate **ZYVORA AI**.
4. Open **ZYVORA AI → Settings**.
5. Enter your HTTPS Chat Completions endpoint, model, and API key.
6. Open **Content Studio** or **AI Assistant**.

Default endpoint: `https://api.openai.com/v1/chat/completions`  
Default model: `gpt-4o-mini`

## Security and privacy

- Only users with `manage_options` can use AI actions.
- AJAX requests use WordPress nonce verification.
- The API key is never placed in frontend JavaScript.
- Prompts are sent to the provider configured by the site administrator; check its privacy and retention policies.
- Generated content is saved as a draft only after the administrator clicks **Save as WordPress draft**.
- Review generated output for accuracy, copyright, SEO claims and factual correctness before publishing.
- Use HTTPS and restrict administrator accounts.

## Provider compatibility

The endpoint must accept an OpenAI-compatible `/chat/completions` payload and return `choices[0].message.content`.

## License

GPL-2.0-or-later.
