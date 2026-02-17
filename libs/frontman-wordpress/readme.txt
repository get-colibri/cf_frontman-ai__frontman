=== Frontman ===
Contributors: frontmanai
Tags: ai, editing, blocks, gutenberg, content
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered frontend editing for WordPress. An AI agent that sees your site and edits posts, blocks, menus, templates, and options through a conversational UI.

== Description ==

Frontman adds an AI-powered editing assistant to your WordPress admin. The agent can:

* **Posts & Pages** — List, read, create, update, and delete posts and pages
* **Gutenberg Blocks** — List, read, update, and insert blocks within posts
* **Navigation Menus** — List menus, read items, and update menu entries
* **Site Options** — Read and modify safe site settings (title, tagline, permalink structure, etc.)
* **Templates** — List block templates and template parts
* **Widgets** — List widget areas and update widget settings
* **File Tools** — Read, write, search, and grep project files (via standalone server)

The plugin works with a companion standalone server (Bun binary) that handles file operations locally. The browser talks only to WordPress — the plugin proxies file tool calls to the standalone server.

== Installation ==

1. Upload the `frontman` folder to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu
3. Download and run the Frontman standalone server:
   `frontman-standalone --project-root /path/to/your/wordpress`
4. Navigate to **Frontman** in the admin menu

== Frequently Asked Questions ==

= Do I need the standalone server? =

The standalone server provides file-level tools (read, write, search, grep). WordPress tools (posts, blocks, menus, options) work without it, but the full experience requires both.

= Is this safe to use? =

The plugin requires `manage_options` capability (admin only). All inputs are sanitized. Options are restricted to a safe allowlist. The standalone server only runs on localhost.

== Changelog ==

= 0.1.0 =
* Initial release
* 19 WordPress tools across 6 categories
* Proxy relay architecture
* Admin UI with production client
* Settings page for standalone server configuration
