---
name: sc-block-dev
description: Add or change a block, block style, pattern, rich-text format or palette entry in the wp-softcatala theme. Use for any new editor primitive, including a shortcode that needs a block.
---

# Add an editor primitive

`docs/blocks/README.md` is the source of truth: read its section for the kind of
primitive you are adding ("Adding a block style", "Adding a colour or a font size",
"Adding a pattern", "Adding a rich-text format", "Adding a custom block") and follow it.
This skill is the checklist that makes the change complete.

1. **Pick the smallest primitive** that does the job: a block style before a block, a
   native server-rendered block before an ACF one. A shortcode with no block gets one
   sharing its renderer. Name it in Catalan for editors (`config/block-editor.json`,
   `block.json` titles), in English in code.
2. **Build it** as the README section says, with its CSS in `frontend/src/scss/_blocks.scss`
   so the site and the editor share it. Editor-only fixes go in `editor.scss`.
3. **Test it**: PHPUnit for server behaviour (`tests/test-theme-blocks.php`,
   `tests/test-block-editor.php`), Vitest for markup and editor scripts. A block that
   replaces a shortcode gets a test that both render the same HTML. Run `./bin/test.sh`.
   Done when it is green and `./bin/test.sh lint-changed` reports nothing new.
4. **Look at it**: build (`./bin/test.sh build`, then commit only what your change touched;
   the build also rewrites unrelated bundles) and render it through WordPress, not from
   saved markup, at desktop and phone widths.
5. **Document it in the editors' guide** (`docs/editors/editor-de-blocs.html`): what it is
   for, a live example, and a row in "Del format antic al nou" if it replaces something.
   `npm run validate` must pass on the guide. Remind the user that the published guide
   page needs the same change.
