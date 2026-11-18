# Resum mensual: what SoftcatalaBot sends over the REST API

The theme renders monthly summaries (`resum_mensual`) in two places:

- **Archive** (`/resums-mensuals/`): one row per summary with the covered month, the
  figures as badges and a response-rate bar.
- **Single** (`/resums-mensuals/<slug>/`): a figures strip, the summary body with
  styled sections, previous/next navigation and a sidebar with a table of contents.

Two kinds of data drive it:

1. **Post meta**: the month and the figures. Used for the badges, the strip and the
   month block.
2. **`content`**: the full summary as **block markup** (spec v2, below), so it can be
   opened and fixed in the block editor like any page. It stays the complete, readable
   text: it is what the editor shows, what the feed and REST output carry, and what the
   page falls back to without meta.

Everything here is optional for the theme. A summary without meta, or without the
classes, still renders: the archive falls back to the publication date and the first
paragraph, and the single page shows the content as plain HTML.

## Endpoint

| | |
|---|---|
| Create | `POST /wp-json/wp/v2/resums-mensuals` |
| Update | `POST /wp-json/wp/v2/resums-mensuals/<id>` |
| Auth | Application password of a user who can publish posts (editor or above) |

Send `meta` in the same request as `title` and `content`. A request is all or
nothing: if a meta value fails validation, WordPress answers `400` and nothing is
saved.

## Fields

### Top level

| Field | Value |
|---|---|
| `title` | `Resum mensual — octubre de 2026` (unchanged) |
| `status` | `publish` |
| `content` | Block markup, see [Content](#content) |
| `meta` | object, see below |

Do not send `date` on updates. The archive order and the previous/next links follow
the publication date, so it must stay the date the summary first came out.

### `meta`

| Key | Type | Required | Meaning | Example |
|---|---|---|---|---|
| `periode` | string `AAAA-MM` | yes | Month the summary covers. Shown as the block in the archive, used in the breadcrumb, the sidebar and the previous/next links. | `"2026-10"` |
| `total` | integer ≥ 0 | yes | Projects that were asked. | `34` |
| `respostes` | integer ≥ 0 | yes | Projects that replied, late replies included. | `14` |
| `demanen_ajuda` | integer ≥ 0 | no | Projects asking for help. | `3` |
| `sense_novetats` | integer ≥ 0 | no | Projects that replied with nothing new. | `6` |
| `sense_resposta` | integer ≥ 0 | no | Projects that did not reply. | `20` |
| `tardanes` | integer ≥ 0 | no | Replies that arrived after the summary was first published. `0` or omitted on the first publish. | `2` |
| `actualitzat` | string `AAAA-MM-DD` | no | Date of the last update with late replies. Omit on the first publish. | `"2026-11-07"` |

`total` and `respostes` together switch the figures on: if either is missing, the
theme shows no badges, bar or strip, whatever else was sent.

Expected relations (not enforced by the theme, but the page reads oddly without them):

- `respostes + sense_resposta = total`
- `demanen_ajuda ≤ respostes`, `sense_novetats ≤ respostes`
- `tardanes ≤ respostes`

The response rate shown in the archive is `round(100 × respostes / total)`.

Validation: `periode` must match `^[0-9]{4}-(0[1-9]|1[0-2])$`, `actualitzat` must be
`AAAA-MM-DD` or an empty string, the integers must be whole numbers ≥ 0. Anything
else gets a `400`.

To clear a value later, send it as `null`.

## Content

### Spec v2: block markup

The content is serialized WordPress blocks: core blocks with the theme's block styles
and two inline formats. Nothing in it is specific to the resums, so the same look is
available to anyone editing a page.

**The canonical example is [`fixtures/resum-mensual.html`](fixtures/resum-mensual.html).**
The theme's tests validate it with WordPress's own block packages (the editor's
validation, plus a round trip to the format the editor saves today), so it is exactly
what the editor would produce. Generate the same structure; when in doubt, copy from
it rather than from this table. SoftcatalaBot's tests keep **a copy** of that file and
compare their output with it: whoever changes it here copies it there in the same
change.

| Part | Blocks |
|---|---|
| "Projectes que necessiten ajuda" | `core/group` (`"tagName":"section"`, style `avis-crida`) → `core/heading` with anchor `ajuda` → list |
| "Actualitzacions per categoria" | `core/group` (`section`) → `core/heading` anchor `actualitzacions` → per category: `core/heading` level 3, style `sobretitol`, then its list |
| A list of projects with a message | `core/list` style `amb-filet`, one `core/list-item` per project: `<strong><a href="…">Name</a></strong>: message` |
| "(resposta tardana)" | `<span class="sc-secundari">(resposta tardana)</span>` inside the item (the "Text secundari" format) |
| Extra line under a project | `<span class="sc-nota">…</span>` at the end of the item (the "Nota" format) |
| "Sense novetats (6)" / "Sense resposta (20)" | `core/group` (`section`) → `core/heading` anchor `sense-novetats` / `sense-resposta` → list |
| The project names in those two | `core/list` style `etiquetes`, one `core/list-item` per project: `<a href="…">Name</a>` |

What is **not** in the content any more:

- The "Actualitzat el …" and "14 de 34 projectes han respost …" paragraphs. The page
  header and the figures strip show them from `meta`; v1 sent them only to hide them.

Details that are easy to get wrong (all visible in the fixture):

- A heading's anchor is a block attribute and comes first in the tag:
  `<!-- wp:heading {"anchor":"ajuda"} -->` then `<h2 id="ajuda" class="wp-block-heading">`.
- Blocks are separated by a blank line; inner blocks follow the parent's opening tag
  directly, as in the fixture.
- Block content is not run through `wpautop`, so v1's rules about line breaks no
  longer apply.

The table of contents lists every `<h2>` with an `id`, in order, dropping a trailing
count such as ` (20)`. Ids must be unique in the page; the four above are enough.

Project links: keep linking each name to the project's page, as now.

### Spec v1: HTML with `resum-*` classes (being retired)

Summaries published before v2 are plain HTML with `resum-*` class names
(`resum-ajuda`, `resum-projectes`, `resum-tardana`, `resum-nota`, `resum-llista`…) and
the two paragraphs above, which the template hides when `meta` is present. The theme
keeps rendering them until October 2026, the only v1 summary, has been re-sent as v2;
then the v1 CSS and the hiding code are removed. Do not send v1 any more.

## Requests

First publish:

```json
POST /wp-json/wp/v2/resums-mensuals
{
  "title": "Resum mensual — octubre de 2026",
  "status": "publish",
  "content": "<!-- wp:group {\"tagName\":\"section\",\"className\":\"is-style-avis-crida\"} -->…",
  "meta": {
    "periode": "2026-10",
    "total": 34,
    "respostes": 12,
    "demanen_ajuda": 2,
    "sense_novetats": 6,
    "sense_resposta": 22,
    "tardanes": 0
  }
}
```

Update after late replies (same post, new content and figures, no `date`):

```json
POST /wp-json/wp/v2/resums-mensuals/1234
{
  "content": "<!-- wp:group {\"tagName\":\"section\",\"className\":\"is-style-avis-crida\"} -->…",
  "meta": {
    "respostes": 14,
    "demanen_ajuda": 3,
    "sense_resposta": 20,
    "tardanes": 2,
    "actualitzat": "2026-11-07"
  }
}
```

Meta keys left out of an update keep their stored value.

The response echoes the stored values under `meta`, so the bot can check them.

## Re-sending October 2026 as v2

October 2026 was published as v1 HTML. Send an update to it with its content
regenerated as v2 block markup (its `meta` is already stored); nothing else changes.
Once it is live, the theme drops v1 support.
