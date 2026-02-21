---
title: 'Frontman Now Supports WordPress'
pubDate: 2026-02-21T05:00:00Z
description: 'Frontman brings AI-powered editing to WordPress. Describe changes in plain English — update themes, edit content, manage settings — and see results live on your site.'
author: 'Itay A'
image: '/blog/post-01-cover.png'
tags: ['announcement', 'wordpress']
---

We started Frontman with a clear idea: put an AI agent inside the app, not the editor. That worked great for JavaScript frameworks like Next.js, Astro, and Vite. But one question kept coming up — what about WordPress?

WordPress powers over 40% of the web. Millions of sites, run by people who range from full-time developers to business owners who just want their site to look right. So we built a WordPress integration.

### How It Works

Install the Frontman plugin, navigate to `/frontman` on your WordPress site, and start talking. The AI agent has full context about your site — your theme, your content, your settings — and can make changes on your behalf.

> Describe what you want. The agent makes it happen.

No code editor required. No terminal. Just a chat interface alongside a live view of your site.

### What the Agent Can Do

Frontman for WordPress comes with a full set of tools purpose-built for the platform:

- **Content Management**: Create, edit, and organize posts and pages. Update blocks, reorder content, change copy.
- **Theme Editing**: Read and modify your theme files directly — templates, partials, `theme.json`, `style.css`, `functions.php`. The agent understands WordPress's theme structure.
- **Menu Management**: List, inspect, and update navigation menus and menu items.
- **Site Settings**: Read and update WordPress options. Change the site title, toggle settings, configure plugins.
- **Template Inspection**: Browse block templates and template parts from your active theme.
- **Widget Areas**: List widget areas and update widget configurations.
- **File Operations**: Full filesystem access scoped to your WordPress installation — search, read, and write files.

All of this through natural language. Say "change the site title to Star Wars Cantina" or "update the homepage hero text" and the agent handles the rest.

### Architecture

The integration has two parts:

1. **A WordPress plugin** (`frontman-wordpress`) that handles authentication, serves the chat UI, and exposes WordPress-specific tools via MCP.
2. **A lightweight local server** (`frontman-standalone`) that provides filesystem tools — reading and writing theme files, searching code, navigating the directory structure.

The plugin uses cookie-based WordPress admin authentication, so only logged-in administrators can access Frontman. Tool calls are handled server-side in PHP, and file operations are proxied to the standalone server which enforces path boundaries to your WordPress root.

### Experimental — and We Need Your Help

This is an early release. The WordPress integration works, but it hasn't been battle-tested across the full range of WordPress setups — different themes, page builders, hosting environments, PHP versions.

We're looking for WordPress users and developers who want to try it out and help us improve it. If you run into issues, have ideas for new tools, or want to see better support for specific WordPress patterns, we want to hear from you.

- **Report issues** on [GitHub](https://github.com/frontman-ai/frontman/issues)
- **Join the conversation** and share feedback
- **Contribute** — the entire codebase is open source under Apache 2.0

### A Note on Production Use

Unlike our JavaScript framework integrations (which are development-only), the WordPress plugin can technically run in production environments. WordPress sites are often edited live, and the plugin respects that workflow.

That said, this is experimental software. If you choose to use it in production, do so with care. We recommend starting in a staging environment, reviewing changes carefully, and keeping backups. The agent writes real code and makes real changes — treat it accordingly.

### Getting Started

Getting started is straightforward:

1. Copy the `frontman-wordpress` plugin to `wp-content/plugins/`
2. Activate it from the WordPress admin
3. Start the standalone server pointing at your WordPress root
4. Navigate to `/frontman` on your site
5. Start chatting

We're excited to bring Frontman to the WordPress ecosystem. This is just the beginning — and with your help, it'll get a lot better.
