---
name: sc-convert-classic
description: Convert a classic-editor page or post on softcatala.org to blocks, leaving the result as an autosave for review. Use when asked to migrate, convert or move a page to the block editor.
---

# Convert a classic page to blocks

The converter is `npm run convert` (`frontend/tools/convert-classic.ts`): WordPress's own
"Convert to blocks" plus footnotes, theme shortcodes, legacy classes and old anchors. What
it maps and how to save the result: "Converting a classic page" in `docs/blocks/README.md`.
Editors get the same conversion from "Convert to blocks" in the editor; this skill is for
converting on someone's behalf and leaving the result for review.

1. **Fetch the raw content**: `GET /wp-json/wp/v2/pages/<id>?context=edit` (or `/posts`),
   field `content.raw`, with the application password the user provides for this run (kept
   out of files). Save it to a file under `frontend/`. If the content already has block
   comments (`<!-- wp:`), stop: it is a block page.
2. **Convert**: `docker compose -f docker-compose.test.yml run --rm --no-deps node npm run convert -- <in> <prefix>`.
   Done when it writes `<prefix>.html` and prints the report without "El resultat no valida".
3. **Work the report**, line by line. Each line is either mapped (nothing to do) or needs a
   decision: a kept Shortcode block (leave it, or rebuild it with a theme block if one
   fits), a Custom HTML block (rebuild with core blocks where you can; otherwise leave it
   and say so), an anchor with no heading (move it onto the right heading's anchor, or
   drop it if nothing links to it). Edit `<prefix>.html`, then validate it again with
   `npm run validate -- <prefix>.html`. Done when every line is accounted for and it
   prints `OK`.
4. **Save as an autosave**, never over the published content:
   `POST /wp-json/wp/v2/pages/<id>/autosaves` with `content` and, if there is a
   `<prefix>.footnotes.json`, `meta: {"footnotes": "<that JSON as a string>"}`.
5. **Hand over**: the edit link (the editor offers to restore the autosave), a summary of
   the report with what you decided for each open line, and what still needs a person.
