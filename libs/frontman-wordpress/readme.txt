=== Frontman ===
Contributors: frontmanai
Tags: ai, editing, content, gutenberg, blocks
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered editing for WordPress. Describe changes in plain English — the agent edits your content, theme, and settings live.

== Description ==

Frontman puts an AI agent inside your WordPress site. Navigate to `/frontman`, describe what you want to change, and the agent handles it — posts, pages, blocks, menus, theme files, site settings, and more.

No code editor. No terminal. Just a chat interface alongside a live view of your site.

**What the agent can do:**

* Create, edit, and delete posts and pages
* Insert, update, and rearrange Gutenberg blocks
* Edit theme files — templates, `style.css`, `theme.json`, `functions.php`
* Update navigation menus and menu items
* Read and change site options (title, tagline, permalinks, etc.)
* Browse block templates and template parts
* Search and modify files across your WordPress installation

**Who it's for:**

Developers who want faster iteration. Designers and content editors who want to make changes without opening an IDE. Anyone managing a WordPress site who'd rather describe what they want than dig through admin screens.

**Open source:**

Frontman is fully open source under Apache 2.0. The entire codebase — every prompt, every tool, every piece of context — is on [GitHub](https://github.com/frontman-ai/frontman).

**Early release — help us improve it:**

This is an experimental release. It works, but it hasn't been tested across every theme, page builder, and hosting setup. We're looking for users to try it and share feedback. [Open an issue](https://github.com/frontman-ai/frontman/issues) or join the conversation on GitHub.

== Installation ==

1. Upload the `frontman` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Navigate to `/frontman` on your site (you must be logged in as an admin)
4. Start chatting

For file editing tools (theme files, search, grep), you also need the companion standalone server:

1. Install: `npm i -g @frontman-ai/standalone`
2. Run: `frontman-standalone --project-root /path/to/your/wordpress`
3. The plugin connects to it automatically on `localhost:19478`

== Frequently Asked Questions ==

= Do I need the standalone server? =

WordPress tools (posts, blocks, menus, options) work without it. The standalone server adds file tools — reading and writing theme files, searching code, navigating directories. For the full experience, run both.

= Is it safe? =

Only WordPress administrators (`manage_options` capability) can access Frontman. All inputs are sanitized. Options are restricted to a safe allowlist. The standalone server runs on localhost only.

= Can I use this in production? =

Technically, yes — unlike the JavaScript framework integrations, the WordPress plugin can run on a live site. But this is experimental software. We recommend starting on a staging site, keeping backups, and reviewing changes carefully.

= Which themes work? =

Frontman works with any WordPress theme. Block themes (Full Site Editing) and classic PHP themes are both supported. The agent adapts based on what it finds in your theme directory.

== Third-Party Services ==

This plugin connects to external services provided by Frontman AI:

**Frontman Client (app.frontman.sh)**
The chat interface is loaded from `https://app.frontman.sh`. This serves the JavaScript and CSS that power the in-browser UI.

* Service URL: [https://app.frontman.sh](https://app.frontman.sh)
* Provider: Frontman AI
* Privacy Policy: [https://frontman.sh/terms](https://frontman.sh/terms)

**Frontman API (api.frontman.sh)**
The plugin connects via WebSocket to `https://api.frontman.sh` for AI agent communication — sending tool results and receiving agent responses. Your site content is sent to this service when the agent processes requests.

* Service URL: [https://api.frontman.sh](https://api.frontman.sh)
* Provider: Frontman AI
* Privacy Policy: [https://frontman.sh/terms](https://frontman.sh/terms)

**AI Model Providers**
The Frontman API routes requests to third-party AI model providers (such as Anthropic and OpenAI) to generate responses. Content from your site may be included in prompts sent to these providers.

No data is sent to these services until you actively use the Frontman chat interface and submit a message.

== Screenshots ==

1. The Frontman chat interface alongside your WordPress site

== Changelog ==

= 0.1.0 =
* Initial release
* 19 WordPress tools: posts, blocks, menus, options, templates, widgets
* File tools via companion standalone server
* Admin-only access with cookie-based authentication
* Settings page for server configuration
* Dev mode for local development
