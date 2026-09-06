---
title: "Kanban: dragging a card saves, but the estat reverts on reload (WP Super Cache)"
date: 2026-09-06
category: runtime-errors
module: kanban
problem_type: stale_data
component: backend_php
severity: medium
symptoms:
  - "Dragging a card to another column worked in the UI, but reloading /tasques/ put it back in its original column"
  - "The PATCH sc/v1/tasca/{id}/estat request returned 200 and the estat_tasca term was correct in the database"
  - "Only logged-in users could see the problem, because the board hides internal tasks from anonymous visitors"
root_cause: config_error
resolution_type: code_fix
related_components:
  - rest_api
tags:
  - wp-super-cache
  - page-cache
  - DONOTCACHEPAGE
  - cache-control
  - vary-cookie
  - kanban
  - estat_tasca
  - stale-html
---

# Kanban: dragging a card saves, but the estat reverts on reload (WP Super Cache)

## Problem

Moving a task between columns on `/tasques/` appeared to work — the card stayed
where it was dropped — but after a page reload it was back in its original
column. The obvious reading is that the save failed silently, which sends you
into the REST endpoint, the permission callback and the front-end fetch.

All of those were fine. The symptom is a read problem wearing a write problem's
clothes.

### Symptoms

- Card moves in the UI, reverts on reload.
- `PATCH sc/v1/tasca/{id}/estat` returns `200 {"id":…,"estat":"en-curs"}`.
- `wp/v2/tasca/{id}?context=edit` shows the new `estat_tasca` term, so the write
  landed.

## What Didn't Work

Time went into ruling out the write path, none of which was at fault:

- The REST endpoint and `wp_set_post_terms()` — verified by reading the term
  back over `wp/v2` straight after the PATCH.
- `Tasques::group_by_estat()` and `get_ordered_estats()` — they key on the term
  slug and were grouping correctly.
- The front-end build — `sc-js-kanban` is in `$module_handles` in
  `functions.php`, and the hashed chunks `kanban.js` imports were all present in
  `static/js/`, so the module was loading.
- A stale `wp_rest` nonce — a plausible theory after the CSRF change in
  9457423, but it would have produced a 401 and a visible notice, not a
  successful save.

## Root Cause

WP Super Cache was storing the board page for logged-in users, and nothing
invalidates that entry when a task's term changes: the drag's PATCH writes a
taxonomy term, which fires no post-save hook the cache plugin listens for. The
reload was served the HTML rendered before the drag.

The giveaway is in the response headers of `/tasques/`:

```
Vary: Accept-Encoding, Cookie
```

`Vary: Cookie` with no `Cache-Control` at all means a full-page cache is keyed
on the session — the page is being stored per logged-in user. The theme itself
caches nothing here: `archive-tasca.php` uses no transients, and the provider
only caches the internal-ID lists.

## Solution

Two layers, because either alone leaves a gap.

Plugin side, one of:

- Enable **"Don't cache pages for known users"** in WP Super Cache's Advanced
  tab. This is the general fix — it also covers every other per-session view.
- Or add `/tasques/` to **Rejected URI strings**, keeping known-user caching on
  elsewhere.

Then clear the existing cache, or the stale entry outlives the fix.

Theme side, in `archive-tasca.php`, so the guard travels with the code:

```php
if ( is_user_logged_in() ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! headers_sent() ) {
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	}
}
```

## Why This Works

`DONOTCACHEPAGE` is the de-facto constant WP Super Cache, W3 Total Cache,
Batcache and WP Rocket all check before writing a page to the cache, so the
board is skipped whatever plugin is in front of it. The `Cache-Control` header
covers proxies and browser back/forward caches that never see the constant.

The guard is limited to logged-in visitors deliberately. The anonymous board is
identical for everyone and read-only — internal tasks are filtered out of it by
`Tasques::get_all_for_board()` — so it stays cacheable.

## Prevention

- Any page that is drag-editable or varies by session needs `DONOTCACHEPAGE`.
  The tell is a template that branches on `is_user_logged_in()`, as this one
  does for both the "Sense columna" bucket and the internal-task filter.
- When a write "does not stick", confirm which half is broken before debugging
  either: read the value straight back from the API. If the API agrees with what
  you wrote and only the rendered page disagrees, it is a cache, not the write.
- `Vary: Cookie` on an HTML response is worth a second look — a page cache
  keyed on the session is a cache that will serve one user's stale render back
  to them indefinitely.

## If the board gets slow, do not reach for the page cache

The reason caching this page is not worth its cost today is that the page is
cheap: the board holds ~11 tasks as of 2026-09-06. That will not hold forever,
and the first instinct when it stops holding will be to turn known-user caching
back on. Do the query work instead — it is cheaper than the alternative and adds
no API surface.

`Tasques::group_by_estat()` calls `wp_get_post_terms()` once per task, and
`get_filter_options()` calls `get_field()` several times per task, so the board
is N+1 in both terms and postmeta. `get_all_for_board()` already has every task
ID in hand from its `WP_Query`, so the fix is to prime both caches in one pass
before those loops run:

```php
$ids = wp_list_pluck( $tasks, 'ID' );
update_object_term_cache( $ids, 'tasca' );
update_postmeta_cache( $ids );
```

`wp_get_post_terms()` and `get_field()` then read from the object cache rather
than issuing a query each. That turns the per-task queries into two, and leaves
the board server-rendered, uncached and correct.

Only if that is not enough is the cached-shell-plus-client-rehydration approach
worth considering, and it is a much bigger change than it looks: it needs a new
`sc/v1` read route that re-applies the internal-task visibility rules
server-side, and it needs the `wp_rest` nonce fetched at runtime rather than
localized into the HTML (see below), because a cached page otherwise serves an
expired or foreign nonce to every visitor.

## Why the nonce blocks caching this page

`archive-tasca.php` mints the nonce with `wp_create_nonce( 'wp_rest' )` and
localizes it; `kanban.ts` reads it once at load and never refreshes it. Nonces
are user-bound and expire within 12-24h, so any cached copy of this page carries
a token that is wrong for whoever receives it. `utils.ts` already has the shape
of the fix, refreshing `sc_get_token` from `admin_url` since eb9cfaa.

## Related Issues

- 9457423 moved CSRF protection to core on the tasques endpoints; a missing
  nonce now returns 401 rather than 403, which is what a genuinely failing drag
  looks like.
