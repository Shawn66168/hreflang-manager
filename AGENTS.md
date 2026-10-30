# Hreflang Manager — Agent Rules

## Project

- Public open-source WordPress plugin (`Shawn66168/hreflang-manager`, GPL-2.0-or-later) that outputs hreflang tags and provides a language switcher shortcode for multilingual sites on separate domains.
- Requirements: PHP 7.4+, WordPress 5.0+.
- Read `README.md`, `DEVELOPMENT.md`, and `.skills/ARCHITECTURE.md` (architecture and development conventions) before changing code. Release notes go in `CHANGELOG.md`; WordPress.org metadata lives in `readme.txt`.

## Rules

- Several production sites include this plugin as a Git submodule. Keep changes backward compatible (settings, hooks, filters, shortcode attributes); breaking changes need an explicit migration plan approved by Shawn.
- This repository is public. Never commit site-specific data, customer information, credentials, or internal URLs.
- Local development uses Docker (`docker compose up -d`, see `DEVELOPMENT.md`); the admin credentials there are local-only test values.
- Run `php -l` on every changed PHP file at minimum, and test in the Docker environment before claiming a change works.
