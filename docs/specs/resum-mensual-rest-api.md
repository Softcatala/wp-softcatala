# Resum mensual: what SoftcatalaBot sends over the REST API

The theme renders monthly summaries (`resum_mensual`) in two places:

- **Archive** (`/resums-mensuals/`): one row per summary with the covered month, the
  figures as badges and a response-rate bar.
- **Single** (`/resums-mensuals/<slug>/`): a figures strip, the summary body with
  styled sections, previous/next navigation and a sidebar with a table of contents.

Two kinds of data drive it:

1. **Post meta**: the month and the figures. Used for the badges, the strip and the
   month block.
2. **`content`**: the full summary as HTML, with the class names and ids below. It
   stays the complete, readable text: it is what the WordPress editor shows, what the
   feed and REST output carry, and what the page falls back to without meta.

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
| `content` | HTML, see [Content](#content) |
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

The current HTML, with these hooks added. Class names are what the theme styles;
ids are what the sidebar table of contents links to.

| Element | Hook | What the theme does |
|---|---|---|
| "Actualitzat el …" paragraph | `<p class="resum-actualitzacio">` | Hidden when `actualitzat` is set (the header shows it instead). |
| "14 de 34 projectes han respost …" paragraph | `<p class="resum-xifres">` | Hidden when the figures are set (the strip shows them instead). |
| "Projectes que necessiten ajuda" | `<section class="resum-ajuda">` with `<h2 id="ajuda">` | Highlighted box. |
| "Actualitzacions per categoria" | `<section class="resum-actualitzacions">` with `<h2 id="actualitzacions">` | Section heading. |
| One category inside it | `<section class="resum-categoria">` with an `<h3>` | Small uppercase heading, list indented with a rule. |
| A list of projects with a message | `<ul class="resum-projectes">`, one `<li>` per project | Project name in bold, no bullets. |
| "(resposta tardana)" marker | `<span class="resum-tardana">` | Grey italics. |
| Extra line under a project | `<span class="resum-nota">` inside the `<li>` | Its own line, italics. |
| "Sense novetats (6)" | `<section class="resum-sense-novetats">` with `<h2 id="sense-novetats">` | Smaller heading. |
| "Sense resposta (20)" | `<section class="resum-sense-resposta">` with `<h2 id="sense-resposta">` | Smaller heading. |
| The project names in those two | `<ul class="resum-llista">`, one `<li><a>` per project, no separators | Shown as tags. |

The table of contents lists every `<h2>` with an `id`, in order, dropping a trailing
count such as ` (20)`. Ids must be unique in the page; the four above are enough.

Project links: keep linking each name to the project's page, as now.

### Formatting rules

WordPress runs `wpautop` on the content, which turns blank lines into `<p>` and single
line breaks into `<br>`. To keep the markup as sent:

- Do not put blank lines or line breaks **inside** an element. One line per block
  element is safe.
- Use `<span class="resum-nota">` for the extra line, not a `<p>` or `<br>` inside the
  `<li>`.

`<section>`, `class` and `id` survive WordPress's HTML filtering for any user who can
publish, so no extra capability is needed.

### Example

```html
<p class="resum-actualitzacio"><em>Actualitzat el 7 nov. 2026 amb 2 respostes tardanes.</em></p>
<p class="resum-xifres">14 de 34 projectes han respost; 3 demanen ajuda.</p>
<section class="resum-ajuda">
<h2 id="ajuda">Projectes que necessiten ajuda</h2>
<ul class="resum-projectes">
<li><a href="https://www.softcatala.org/projectes/libreoffice/">LibreOffice</a>: Sempre calen mans</li>
<li><a href="https://www.softcatala.org/projectes/qgis/">QGIS</a> <span class="resum-tardana">(resposta tardana)</span>: Cal que usuaris de QGIS col·laboren en la traducció, per la gran quantitat de termes específics.</li>
<li><a href="https://www.softcatala.org/projectes/securedrop/">SecureDrop</a>: Caldria trobar un moderador</li>
</ul>
</section>
<section class="resum-actualitzacions">
<h2 id="actualitzacions">Actualitzacions per categoria</h2>
<section class="resum-categoria">
<h3>Traducció de programari</h3>
<ul class="resum-projectes">
<li><a href="https://www.softcatala.org/projectes/liberapay/">Liberapay</a>: Traducció al dia gràcies a Minairó</li>
<li><a href="https://www.softcatala.org/projectes/telegram/">Telegram</a> <span class="resum-tardana">(resposta tardana)</span>: Han entrat més de 4.000 cadenes noves; en falten unes 1.000. <span class="resum-nota">Ahir van implementar una IA per a fer les traduccions noves.</span></li>
</ul>
</section>
</section>
<section class="resum-sense-novetats">
<h2 id="sense-novetats">Sense novetats (6)</h2>
<ul class="resum-llista"><li><a href="https://www.softcatala.org/projectes/joplin/">Joplin</a></li><li><a href="https://www.softcatala.org/projectes/softvalencia/">Softvalència</a></li></ul>
</section>
<section class="resum-sense-resposta">
<h2 id="sense-resposta">Sense resposta (20)</h2>
<ul class="resum-llista"><li><a href="https://www.softcatala.org/projectes/apertium/">Apertium</a></li><li><a href="https://www.softcatala.org/projectes/gimp/">Gimp</a></li></ul>
</section>
```

## Requests

First publish:

```json
POST /wp-json/wp/v2/resums-mensuals
{
  "title": "Resum mensual — octubre de 2026",
  "status": "publish",
  "content": "<p class=\"resum-xifres\">12 de 34 projectes han respost; 2 demanen ajuda.</p>…",
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
  "content": "<p class=\"resum-actualitzacio\"><em>Actualitzat el 7 nov. 2026 amb 2 respostes tardanes.</em></p>…",
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

## Backfilling October 2026

The one summary published so far has no meta. Send an update to it with the
`meta` above and the content with the hooks; nothing else changes.
