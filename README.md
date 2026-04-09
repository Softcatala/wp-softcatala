# WP-Softcatala

WordPress theme powering [softcatala.org](https://www.softcatala.org), the website of Softcatala — a non-profit organization promoting the Catalan language in technology.

**License:** GPLv3 or later

## Architecture

This is a **WordPress theme** built on [Timber](https://timber.github.io/docs/) (v2), which provides [Twig](https://twig.symfony.com/) as a templating engine. The theme separates PHP logic from presentation:

- **PHP templates** (root `*.php` files) — WordPress template hierarchy entry points. They set up context and render Twig templates.
- **Twig templates** (`templates/`) — presentation layer. All HTML output lives here.
- **Classes** (`classes/`) — business logic, custom post types, widgets, services.
- **REST API** (`rest/`) — custom WP REST endpoints (downloads updater, projects CSV).
- **WP-CLI commands** (`wp-cli/`) — data migration and management scripts.
- **Includes** (`inc/`) — legacy procedural code: widgets, post type helpers, rewrites, AJAX handlers.
- **Static assets** (`static/`) — CSS, JS, fonts, images.

### Custom Post Types

Registered in `classes/type-registers/`: `programa`, `projecte`, `esdeveniment`, `dades-obertes`, `slider`, `aparell`.

### Autoloading

Two autoloading conventions coexist:

1. **`SC_` prefix** — classes like `SC_Foo_Bar` resolve to `classes/foo-bar.php`
2. **`Softcatala\` namespace** — classes like `Softcatala\Content\JsonToTable` resolve to `classes/content/json-to-table.php` (decamelized)

Both are registered in `functions.php` via `spl_autoload_register`.

### REST API

- **Downloads updater** (`rest/downloads-api.php`) — update program download info from external APIs. See [REST-API-DOWNLOADS.md](REST-API-DOWNLOADS.md) for full documentation.
- **Projects CSV** (`rest/projectes-csv-api.php`) — projects endpoint.

## Requirements

- PHP >= 7.4
- WordPress (recent versions)
- [Timber](https://wordpress.org/plugins/timber-library/) plugin (v2) installed and active
- Composer

## Setup

```bash
# Install dependencies
composer install

# The theme must be placed in your WordPress wp-content/themes/ directory
```

## Development

### Code Quality

```bash
# Run all checks (PHPCS + PHPMD)
composer code

# PHP CodeSniffer (WordPress coding standards)
composer phpcs

# Auto-fix coding standard violations
composer phpcbf

# PHP Mess Detector
composer phpmd
```

PHPCS is configured in `phpcs.xml` (WordPress ruleset, targets `classes/`). PHPMD is configured in `phpmd.xml`.

### Tests

```bash
# Requires WordPress test suite — see bin/install-wp-tests.sh
phpunit
```

Tests live in `tests/` with the `test-` prefix convention. Bootstrap at `tests/bootstrap.php`.

### WP-CLI Commands

The `wp-cli/` directory contains data converters and management commands (events, programs, sliders, downloads). These are autoloaded when `WP_CLI` is defined.

## Contributors

- Xavi Ivars ([@xavivars](https://github.com/xavivars))
- Pau Iranzo ([@paugnu](https://github.com/paugnu))
- Miquel Piulats
- Jordi Mas ([@jordimas](https://github.com/jordimas))
- Jaume Ortolà ([@jaumeortola](https://github.com/jaumeortola))
