# Block editor: developer guide

How the theme sets up the block editor, and how to add to it. The guide for the people
who write pages is `docs/editors/editor-de-blocs.html` (in Catalan, published as a page).
The roadmap is `docs/plans/2026-11-18-007-feat-block-editor-primitives-plan.md`.

## Rules

1. **Never offer free colours or sizes.** If content needs a new look, add a palette entry
   or a block style. The editor's custom colour, gradient, font-size and spacing pickers
   are closed on purpose.
2. **The editor and the site must match.** Every look lives in SCSS that both the front end
   and the editor canvas compile (see [CSS](#css)).
3. **New shortcode functionality gets a block that shares the shortcode's renderer**, so
   both paths produce the same markup (`classes/blocks/cards.php` and
   `SC_Shortcodes_Cards::render()`).
4. **Block markup is validated, not eyeballed.** Patterns, the resum fixture and the editor
   guide run through WordPress's own block packages in the frontend tests. Anything that
   produces block markup (the theme, SoftcatalaBot, agents) should do the same.
5. **Everything is behind the `block_editor` feature flag** (`SC_Settings::SETTINGS_BLOCK_EDITOR`,
   previewed with `?ff=block_editor`), except the front-end CSS, which is always loaded so
   block content renders whatever the flag says.

## Where things live

| File | What |
|---|---|
| `classes/blocks/editor.php` | `Softcatala\Blocks\Editor`: every editor hook. Each callback checks `Editor::is_active()` |
| `config/block-editor.php` | `theme.json` data: palette, gradient, font sizes, closed pickers, layout width. Merged with the `wp_theme_json_data_theme` filter instead of a root `theme.json`, so the flag can switch it off |
| `config/block-editor.json` | Post types, allowed blocks, block styles and the pattern category. Read by PHP (`Editor::config()`) and by the frontend tests |
| `block-patterns/*.php` | Patterns: a header, then plain block markup |
| `classes/blocks/attendance-table.php` | Server-side totals for the table's `assistencia` style |
| `classes/blocks/cards.php` | The `acf/cards` block, sharing the `[cards]` renderer |
| `frontend/src/scss/_blocks.scss` | The look of core blocks and every `.is-style-*` |
| `frontend/src/scss/_prose.scss` | Content typography: headings, paragraphs, links, lists |
| `frontend/src/scss/editor.scss` | The editor canvas stylesheet, plus editor-only fixes |
| `frontend/src/js/editor-formats.ts` | Rich-text formats (`sc/secundari`, `sc/nota`) |
| `frontend/tests/blocks/validate.ts` | `validateMarkup()` |
| `classes/blocks/content-audit.php`, `wp-cli/blocks_command.php` | `wp sc blocks audit` |

## Adding a block style

1. Add it to `styles` in `config/block-editor.json`, under the block, as
   `"slug": "Etiqueta en català"`. `Editor::register_styles()` registers it, and the
   validator starts accepting `is-style-slug` on that block.
2. Style `.is-style-slug` in `_blocks.scss`, inside the `blocks` mixin. Select it with the
   block's own class (`.wp-block-group.is-style-slug`, `ul.wp-block-list.is-style-slug`):
   older `.contingut` and `article` rules are specific enough to beat a bare class.
3. If the block isn't in `blocks` yet, add it. A test checks every styled block is allowed.
4. Document it in the editor guide (see [The editor guide](#the-editor-guide)).
5. Build (`./bin/test.sh build`) and check it in both the editor and a page.

A style that also needs behaviour, like `assistencia`, gets a `render_block_{name}`
filter that looks for the class token in `$block['attrs']['className']` and rewrites the
HTML at render time (`AttendanceTable::filter()`). Keep the saved markup plain, so the
block stays valid in the editor whatever the filter does.

## Adding a colour or a font size

- Palette, gradients and font sizes are in `config/block-editor.php`. Use the brand
  tokens from `_tokens.scss` (the corporate identity colours are there with their Pantone
  reference) and keep the two in step.
- Check the contrast. Orange (Pantone 151) is deliberately not a palette colour: 2.5:1 on
  white fails WCAG, so it can only appear through a style that uses it as an accent.
- `tests/test-block-editor.php` asserts the palette slugs; update it.

## Adding a pattern

1. Build the content in the editor (with the flag on), open the code editor, and copy the
   markup. Don't hand-write it: the saved format changes between WordPress versions, and
   the validator will reject an old one.
2. Create `block-patterns/<slug>.php`:

   ```php
   <?php
   /**
    * Title: Nom del patró
    * Slug: softcatala/<slug>
    * Description: Per a què serveix, en una frase.
    * Categories: softcatala
    * Keywords: trobada, assistència  (optional: inserter search)
    * Post Types: page               (optional: limit where it's offered)
    * Block Types: core/post-content (optional: offer it when a page starts empty)
    * Viewport Width: 1100           (optional: preview width)
    */
   ?>
   <!-- wp:paragraph -->
   …
   ```

   Nothing but markup after `?>`: the file is read, not executed. `Editor::register_patterns()`
   registers it with the flag on.
3. `blocks.test.ts` validates every file in `block-patterns/` automatically.
   `tests/test-block-editor.php` has a list of slugs to unregister between tests: add yours.

## Adding a rich-text format

Formats are inline spans with a class, added from the toolbar. Add an entry to `FORMATS`
in `frontend/src/js/editor-formats.ts` (`name`, `title` in Catalan, `className`, a
Dashicon), style the class in `_blocks.scss`, and add it to the editor guide. The script
is enqueued only in the editor, with the flag on. Formats are the only way such spans
survive editing: rich text drops any class it doesn't know.

## Adding a custom block

Reach for one only when core blocks plus a style can't do it: dynamic data, or a
shortcode that needs a block (rule 3).

- Today the theme has one, `acf/cards`: an ACF block whose `render_callback` calls the
  shortcode's static `render()`. Copy that shape for a block whose content is a form
  (fields, a repeater).
- For a block that needs no fields, prefer a native, server-rendered block: a
  `block.json` with `"render"` pointing at a PHP template that calls the shared renderer,
  registered with `register_block_type()` on `init`. Moving `acf/cards` to `block.json`
  is on the roadmap.

In both cases: add the block name to `blocks` in `config/block-editor.json`; test the
shared renderer in PHPUnit (the shortcode and the block must produce the same HTML);
style it in `_blocks.scss`; document it in the editor guide; and, if it replaces a
shortcode or legacy class, add a row to the guide's "Del format antic al nou" table and,
if it's useful to track, to `ContentAudit`.

## Post types and allowed blocks

- `post_types` in `config/block-editor.json` lists the types that use the block editor.
  Other types are forced to the classic editor. For a listed type, the Classic Editor
  plugin's per-user and per-post choice still wins, so classic posts keep opening
  classic until someone converts them.
- `blocks` is the allowed list for everyone; `html_blocks` (Custom HTML) is added only for
  users with `unfiltered_html`. `core/freeform`, `core/shortcode`, `core/missing` and
  `core/block` stay allowed so old content keeps opening.

## CSS

- `_prose.scss` and `_blocks.scss` are mixins, included twice: by `main.scss` under
  `.contingut` (via `layouts/_block-content.scss` and `components/_content.scss`) and by
  `editor.scss` at the root of the canvas. `add_editor_style()` scopes the editor file to
  `.editor-styles-wrapper`.
- `editor.scss` is compiled by `npm run build:editor` (the sass CLI), not Vite, because
  Vite bundles all CSS into `main.min.css`. `npm run build` runs both. The compiled
  `static/css/editor.min.css` is committed, like `main.min.css`.
- Editor-only fixes go in `editor.scss`, never in the shared mixins. Example: in the editor
  a list item's text is a `div.block-editor-rich-text__editable` inside the `li`, so the
  chevron and the text need `inline-block` there and nowhere else.
- The theme keeps WordPress's structural block CSS (`wp-block-library`, loaded per block)
  and does **not** opt in to `wp-block-styles`: `_blocks.scss` is that layer, in the
  site's style.
- `./bin/test.sh build` also rewrites unrelated JS bundles (toolchain noise). Commit only
  what your change touched.

## Writing block markup

Whatever writes block markup outside the editor (a pattern, SoftcatalaBot, an agent)
must write exactly what the editor would save. The details that bite:

- Blocks are separated by a blank line; inner blocks follow the parent's opening tag
  directly (`<ul class="wp-block-list"><!-- wp:list-item -->`).
- Attributes go in the comment as JSON, and the matching class or attribute in the HTML:
  `<!-- wp:list {"className":"is-style-amb-filet"} -->` →
  `<ul class="wp-block-list is-style-amb-filet">`.
- A heading's anchor is a block attribute and `id` comes before `class`:
  `<!-- wp:heading {"anchor":"ajuda"} -->` → `<h2 id="ajuda" class="wp-block-heading">`.
- Tables default to fixed layout: `<table class="has-fixed-layout">` with no attribute in
  the comment; `{"hasFixedLayout":false}` gives a plain `<table>`.
- Text is escaped as rich text saves it: `&`, `<` and `>` as entities, quotes as they are.
- Line breaks inside a block are `<br>`; block content is not run through `wpautop`.

Validate it:

```ts
import { validateMarkup } from './blocks/validate'
expect(validateMarkup(markup)).toEqual([])   // pass true to allow Custom HTML
```

Each problem names the block and what's wrong: a block outside the allowed list, an
unregistered style, markup the block wouldn't save (the editor's "unexpected content"),
or markup that is valid but not in the current saved format, with the position of the
first difference.

## Auditing content

`wp sc blocks audit` (read-only) reports what classic content still needs: format per
post type, shortcodes, sc-footnotes `[1. …]`, legacy classes, MediaWiki and pasted
debris, inline styles, Classic and Custom HTML blocks, and blocks outside the allowed list.

```bash
wp sc blocks audit                          # the block editor's post types
wp sc blocks audit --post_type=any
wp sc blocks audit --post_type=page --per-post --format=csv > pages.csv
```

The legacy classes it looks for are `ContentAudit::LEGACY_CLASSES`. When a class gets a
block equivalent, the guide's "Del format antic al nou" table says which.

## The editor guide

`docs/editors/editor-de-blocs.html` is the editors' guide, in Catalan, written as block
markup so it can be published as a page and shows the styles it describes. `blocks.test.ts`
validates it. When you add or change a style, pattern, format or block, update it in the
same change: edit the published page in the block editor, copy the result from the code
editor into the file (or edit the file and paste it into the page), and keep both in step.

## Tests

```bash
./bin/test.sh phpunit --filter 'BlockEditor|AttendanceTable|ContentAudit'
./bin/test.sh frontend     # validator, patterns, fixtures, guide, formats
./bin/test.sh build        # compiles main.min.css and editor.min.css
```

`tests/test-block-editor.php` covers the flag (on and off), palette, closed pickers,
allowed blocks, post types, styles and patterns.
