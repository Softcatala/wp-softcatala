
## Project Overview

WordPress theme for softcatala.org. Built on Timber v2 (Twig templating). PHP >= 8.2 (production runs 8.4).

## Key Files

- `functions.php` — main entry point. Defines `StarterSite` class, registers hooks, autoloaders, post types, REST endpoints. Version constant: `WP_SOFTCATALA_VERSION`.
- `style.css` — theme metadata header (name, version, license). No actual styles — CSS lives in `static/css/`.
- `composer.json` — dependencies and dev scripts.

## Directory Structure

```
classes/              # Business logic (autoloaded)
  type-registers/     # Custom post type definitions (programa, projecte, esdeveniment, etc.)
  providers/          # Data providers
  routing/            # URL routing
  content/            # Content processing (namespaced: Softcatala\Content\*)
  sitemaps/           # Sitemap generation
  widgets/            # WordPress widgets
rest/                 # REST API endpoint definitions
inc/                  # Legacy includes (widgets, rewrites, AJAX, post type functions)
templates/            # Twig templates (.twig files)
static/               # Compiled/output CSS, JS, fonts, images — do not edit directly
frontend/             # Frontend source (TypeScript + SCSS, built with Vite)
  src/js/             # TypeScript modules (main.ts entry point)
  src/scss/           # SCSS source files
  vite.config.js      # Outputs to static/ (js/main.min.js, css/main.min.css, fonts/)
wp-cli/               # WP-CLI commands and data converters
tests/                # PHPUnit tests
bin/                  # WP test suite installer script
languages/            # i18n translation files
docs/solutions/       # documented solutions to past problems (ui-bugs, runtime-errors, etc.), searchable by YAML frontmatter (module, tags, problem_type)
```

## Frontend (TypeScript + SCSS)

Source lives in `frontend/src/`, output goes to `static/`. **Never edit `static/js/main.min.js` or `static/css/main.min.css` directly.**

```bash
cd frontend && npm run build   # runs the tests, then compiles → static/
```

- Entry point: `frontend/src/js/main.ts` → `static/js/main.min.js`
- Styles: `frontend/src/scss/main.scss` → `static/css/main.min.css`
- The corrector React app lives in `../corrector/` (sibling repo). After building it, run `npm run wordpress` inside that repo to copy its assets into `static/css/corrector/` and `static/js/corrector/`.

### Frontend tests

Vitest + jsdom, in `frontend/tests/`. `prebuild` runs them, so `npm run build` fails
without writing to `static/` if anything is red. `npm run dev` (watch) does not run them.

```bash
cd frontend
npm test         # vitest run
npm run test:watch
npm run typecheck   # not part of the build — esbuild strips types without checking them
```

Fixtures in `tests/helpers.ts` mirror the Twig templates the modules run against; keep
them in step when the templates change. Modules that self-initialise on `DOMContentLoaded`
should also export their init function — tests call it directly, because re-importing a
module per test leaves its old listener attached to the shared document.

### Adding new Vite entry points
Vite entry points that import other modules produce ES module output (with `import` statements for shared chunks). WordPress enqueues classic scripts by default, so every new entry point must:
1. Be added to `rollupOptions.input` in `vite.config.js` with its output path in `entryFileNames`
2. Have its handle added to the `$module_handles` array in the consolidated `script_loader_tag` filter in `functions.php`

Exception: self-contained IIFE files with no module-level imports produce classic script output and do not need `type="module"`. (No current entry point uses this; `traductor.ts` used to, before it started importing from `utils.ts`.)

### Per-page JS and CSS
Page-specific scripts (e.g. `conjugador.ts`) are enqueued in the page template PHP file (e.g. `conjugador.php`). Localized data (`wp_localize_script`) is also set there. Page-specific CSS should be integrated into `frontend/src/scss/` rather than kept as separate static files.

## Autoloading Conventions

Classes are autoloaded via two conventions (both defined in `functions.php`):

1. `SC_` prefix: `SC_Foo_Bar` → `classes/foo-bar.php`
2. `Softcatala\` namespace: `Softcatala\Content\JsonToTable` → `classes/content/json-to-table.php` (decamelized path)

When creating new classes, prefer the `Softcatala\` namespace convention.

## Coding Standards

- WordPress PHP coding standards (configured in `phpcs.xml`, targets `classes/`)
- Run `./bin/test.sh lint` to check, `./bin/test.sh phpcbf` to auto-fix
- `./bin/test.sh lint-changed` narrows both linters to files changed vs master
- The codebase carries ~1250 pre-existing PHPCS findings from before WPCS 3, so
  CI reports the linters without failing on them. Keep *new* code clean.

## Testing

Everything runs in Docker — do not assume PHP, Composer or npm on the host.

```bash
./bin/test.sh              # everything CI runs
./bin/test.sh phpunit      # PHP test suite (extra args pass through)
./bin/test.sh frontend     # vitest + tsc
PHP_VERSION=8.2 ./bin/test.sh phpunit    # the other version CI covers
```

Tests in `tests/` use the `test-` file prefix and extend `SCTests`
(`tests/sc_tests.php`), which re-fires `init` in `set_up()` because
WP_UnitTestCase unregisters post types, taxonomies and meta keys between tests.
`tests/bootstrap.php` activates the theme before WordPress loads theme functions
and loads ACF; `bin/install-wp-tests.sh` provisions WordPress, the test suite
and ACF.

CI is `.github/workflows/ci.yml` (push to master + pull requests), running the
same scripts.

## Language

- Code: PHP/English (variable names, class names, comments in English)
- Content/UI strings: Catalan (template text, user-facing labels)
- Commit messages: English

## Common Patterns

- PHP template files in root set up Timber context and call `Timber::render('template.twig', $context)`
- Custom post types are registered in `classes/type-registers/` extending a base class
- REST endpoints are defined in `rest/` and registered via `rest_api_init` hook in `functions.php`
- WP-CLI commands in `wp-cli/` are autoloaded only when `WP_CLI` is defined
