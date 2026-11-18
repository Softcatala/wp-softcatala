---
name: sc-block-page
description: Write a softcatala.org page or post in block markup (a trobada, a guide, any new page) and save it as a draft. Use when asked to create or rewrite site content for the block editor.
---

# Write a page in blocks

The look comes from the theme's blocks, styles and patterns, never from colours, inline
styles or Custom HTML. What exists and what each is for: `docs/editors/editor-de-blocs.html`
(the editors' guide; read it first). How to write the markup exactly: "Writing block
markup" in `docs/blocks/README.md`.

1. **Start from a pattern** when one fits (`block-patterns/*.php`: trobada, sessió,
   xifres, avisos, requisits). Copy its markup after `?>` and fill it in. Otherwise build
   from the blocks and styles in the guide. Text is Catalan, addressing readers as vós.
   Done when every section of the brief has its blocks.
2. **Anchor the sections**: every `h2` the reader may want to jump to gets an anchor
   (`{"anchor":"…"}` and `id`), so the index (template "Plantilla text amb índex", or the
   `sc/index` block) lists it.
3. **Validate**: write the markup to a file in `frontend/` and run
   `docker compose -f docker-compose.test.yml run --rm --no-deps node npm run validate -- <file>`.
   Done when it prints `OK`. Fix every reported problem in the markup; the message gives
   the position of the first difference from what the editor would save.
4. **Save as a draft** through the REST API (`POST /wp-json/wp/v2/pages` or `/posts`, with
   `status: "draft"`, or `"private"` for internal pages; `template: "page-index.php"` for long
   pages). Use the application password the user provides for this run, and keep it out of
   files. Read the page back with `context=edit` and check `content.raw` equals what you
   sent. Publishing is the user's call.
5. **Hand over**: the draft's edit link, the pattern used, and anything the brief asked
   for that no block or style covers (an editor should ask for a style, not work around it).
