# WP-Softcatala

WordPress theme powering [softcatala.org](https://www.softcatala.org), the website of Softcatalà — a non-profit organization promoting the Catalan language in technology.

**License:** GPLv3 or later

## Requirements

- PHP >= 8.2 — production runs 8.4. The floor comes from Timber v2.
- WordPress (recent versions)
- Composer — [Timber](https://timber.github.io/docs/) v2 is a Composer dependency of the theme, not the wordpress.org plugin
- [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) — a hard dependency: theme hooks call `get_field()` unconditionally, and field groups are checked in under `acf-json/`

## Setup

```bash
composer install
```

The theme must live in your WordPress `wp-content/themes/` directory.

**To run the tests you need none of the above — only Docker.** See below.

## Tests and checks

Everything runs inside Docker. No PHP, Composer, Node or npm on the host.

```bash
./bin/test.sh                # everything CI runs: frontend, lint, PHP tests
```

Individual checks:

```bash
./bin/test.sh phpunit        # WordPress test suite
./bin/test.sh frontend       # vitest + tsc --noEmit
./bin/test.sh build          # production frontend build into static/
./bin/test.sh lint           # phpcs + phpmd over all of classes/
./bin/test.sh lint-changed   # ...only over files changed vs master
./bin/test.sh phpcbf         # auto-fix coding standard violations
./bin/test.sh shell          # bash inside the PHP container
./bin/test.sh down           # stop containers and drop the cached volumes
```

Arguments after `phpunit`, `phpcs` and `phpcbf` are passed straight through:

```bash
./bin/test.sh phpunit --filter TascaTest
./bin/test.sh phpunit tests/test-tasca.php
./bin/test.sh phpcs classes/providers/tasques.php
```

Environment overrides:

```bash
PHP_VERSION=8.2 ./bin/test.sh phpunit    # defaults to 8.4, as in production
WP_VERSION=6.7 ./bin/test.sh phpunit     # defaults to the latest release
```

The first run builds the PHP image and downloads WordPress, the wordpress-develop
test suite and ACF into named volumes; later runs reuse them and take seconds.
`./bin/test.sh down` clears that cache if it ever goes stale.

Composer dependencies are installed into `vendor/` in the checkout by the
container, and are shared across `PHP_VERSION` values locally — CI resolves them
per version instead. `composer.lock` is resolved against PHP 8.2 (via
`config.platform` in `composer.json`) so that one lock installs on every
supported version, 8.4 included.

### PHP tests

Tests live in `tests/` with the `test-` prefix and extend `SCTests`
(`tests/sc_tests.php`). Two things are worth knowing before writing one:

- `SCTests::set_up()` re-fires `init`, because `WP_UnitTestCase` unregisters post
  types, taxonomies and meta keys between tests — without it everything the theme
  registers would be missing for every test after the first.
- `tests/bootstrap.php` activates the theme *before* WordPress loads theme
  functions, so `functions.php` runs through the normal path and its `init` hooks
  land in time. It also loads ACF.

`bin/install-wp-tests.sh` provisions WordPress, the test suite and ACF. It needs
neither `svn` nor a MySQL client, and `bin/test.sh` calls it for you.

### Code quality

PHPCS is configured in `phpcs.xml` (WordPress ruleset, targets `classes/`) and
PHPMD in `phpmd.xml`.

Both carry a large backlog inherited from before WPCS 3 — around 1250 PHPCS
findings and 80 PHPMD ones across `classes/` — so **CI reports them without
failing the build**. `./bin/test.sh lint-changed` narrows the report to what your
branch touches, which is the useful signal day to day. Roughly 1050 of the PHPCS
findings are auto-fixable with `./bin/test.sh phpcbf`.

### Frontend

Source lives in `frontend/src/` (TypeScript + SCSS, built with Vite); output goes
to `static/`. Never edit `static/js/main.min.js` or `static/css/main.min.css`
directly.

```bash
./bin/test.sh build     # compiles frontend/src → static/
```

The build runs the frontend tests first and refuses to write `static/` if any
fail. Committing a frontend change means committing the rebuilt `static/` output
along with it.

## CI

[`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs on pushes to `master`
and on every pull request:

| Job | What it does | Gates the build |
| --- | --- | --- |
| Frontend | `npm ci`, `tsc --noEmit`, vitest, production build | yes |
| PHPUnit | Test suite on PHP 8.2 and 8.4 against a MariaDB service | yes |
| Lint | PHPCS + PHPMD, changed files and full report | no — see above |

It runs the same scripts `bin/test.sh` does, so a green local run means a green
CI run.

## Architecture

The theme separates PHP logic from presentation:

- **PHP templates** (root `*.php` files) — WordPress template hierarchy entry points. They set up context and render Twig templates.
- **Twig templates** (`templates/`) — presentation layer. All HTML output lives here.
- **Classes** (`classes/`) — business logic, custom post types, widgets, services.
- **REST API** (`rest/`) — custom WP REST endpoints (downloads updater, projects CSV).
- **WP-CLI commands** (`wp-cli/`) — data migration and management scripts, autoloaded when `WP_CLI` is defined.
- **Includes** (`inc/`) — legacy procedural code: widgets, post type helpers, rewrites, AJAX handlers.
- **Static assets** (`static/`) — compiled CSS, JS, fonts, images.

### Custom Post Types

Registered in `classes/type-registers/`: `programa`, `projecte`, `esdeveniment`, `dades-obertes`, `slider`, `aparell`.

### Autoloading

Two autoloading conventions coexist, both registered in `functions.php` via `spl_autoload_register`:

1. **`SC_` prefix** — classes like `SC_Foo_Bar` resolve to `classes/foo-bar.php`
2. **`Softcatala\` namespace** — classes like `Softcatala\Content\JsonToTable` resolve to `classes/content/json-to-table.php` (decamelized)

### REST API

- **Downloads updater** (`rest/downloads-api.php`) — update program download info from external APIs. See [REST-API-DOWNLOADS.md](REST-API-DOWNLOADS.md) for full documentation.
- **Projects CSV** (`rest/projectes-csv-api.php`) — projects endpoint.

## Contributors

- Xavi Ivars ([@xavivars](https://github.com/xavivars))
- Pau Iranzo ([@paugnu](https://github.com/paugnu))
- Miquel Piulats ([@lequim](https://github.com/lequim))
- Jordi Mas ([@jordimas](https://github.com/jordimas))
- Jaume Ortolà ([@jaumeortola](https://github.com/jaumeortola))
