---
title: "refactor: Replace legacy Bootstrap utility classes with semantic SCSS components"
type: refactor
status: active
date: 2026-05-27
---

# refactor: Replace legacy Bootstrap utility classes with semantic SCSS components

## Summary

Several Twig templates reference Bootstrap 3 utility classes (`alert-warning`, `bg-success`, `bg-danger`, `bg-warning`, `text-success`, `text-danger`, `text-muted`, `alert-info`) that were never compiled into the theme's SCSS and are therefore doing nothing. The goal is to introduce a small set of semantic, reusable SCSS components (`.alert--success`, `.alert--error`, `.alert--warning`, `.alert--info`, `.text-muted`) and replace all occurrences of the dead Bootstrap classes across templates, so that alert-style elements look correct and future templates can use the classes without writing new CSS.

---

## Requirements

- R1. Define semantic alert classes in a new SCSS component so they are available everywhere without page-specific CSS.
- R2. Each alert variant (success, error, warning, info) must have a visually distinct, accessible appearance (background, text colour, border).
- R3. A `text-muted` utility class must exist for muted text.
- R4. All Twig templates must use the new classes instead of the Bootstrap ones.
- R5. Pages that already use `#info`/`#error` ID-based styles (traductor, transcribe, dubbing, transcribe-results) must remain visually unchanged — the ID rules are the source of truth for those elements and the redundant Bootstrap classes on them should simply be removed.
- R6. No new page-specific CSS should be needed to render a success, error, warning, or info alert in a future template.

---

## Scope Boundaries

- No changes to JavaScript or TypeScript — show/hide logic is already handled separately.
- No changes to panel layout or spacing — only colour/typography semantics.
- Bootstrap classes not related to alerts (`col-*`, `row`, `panel-*`, etc.) are out of scope.

### Deferred to Follow-Up Work

- Full audit and removal of all remaining Bootstrap class references beyond the ones identified here.

---

## Context & Research

### Relevant Code and Patterns

- `frontend/src/scss/components/_panels.scss` — existing panel component; alerts will live alongside or nearby as `_alerts.scss`.
- `frontend/src/scss/main.scss` — SCSS entry point, components are imported under section 5.
- `frontend/src/scss/_utilities.scss` — already defines `.hidden`; `.text-muted` fits here.
- `frontend/src/scss/_tokens.scss` — design tokens (CSS custom properties + SCSS variables); colour values for alerts should be added here.
- `frontend/src/scss/layouts/_eines.scss` — currently holds `#info`/`#error` ID rules with hardcoded hex colours; those colours should be moved to tokens and the ID rules kept for the visibility/transition behaviour only.

### Affected templates

| Template | Classes to remove | Notes |
|---|---|---|
| `templates/traductor.twig` | `alert-warning`, `bg-success`, `text-success`, `bg-danger`, `text-danger` | `#info`/`#error` styled by ID; `#message_info` styled by ID too |
| `templates/transcribe.twig` | `alert-warning`, `bg-success`, `text-success`, `bg-danger`, `text-danger` | `#info`/`#error` styled by ID |
| `templates/dubbing.twig` | `alert-warning`, `bg-success`, `text-success`, `bg-danger`, `text-danger` | `#info`/`#error` styled by ID |
| `templates/transcribe-results.twig` | `alert-warning`, `bg-success`, `text-success`, `bg-danger`, `text-danger`, `hidden` | `#info`/`#error` styled by ID; still has `hidden` class (needs same treatment as the others) |
| `templates/nombres-lletres.twig` | `bg-success`, `bg-warning` | Result display paragraphs — need `.alert--success` / `.alert--warning` |
| `templates/cercador-paraules.twig` | `bg-success`, `bg-warning` | Same as above |
| `templates/sep-sillabes.twig` | `bg-success`, `bg-warning` | Same as above |
| `templates/archive-tasca.twig` | `alert-info`, `text-muted` | Kanban empty state + comment date |

---

## Key Technical Decisions

- **New component file `_alerts.scss`**: keeps alert styles in one place, easy to discover and extend. Follows the existing pattern of one file per component under `frontend/src/scss/components/`.
- **Semantic class names `.alert--success`, `.alert--error`, `.alert--warning`, `.alert--info`**: BEM modifier pattern, consistent with the rest of the codebase. Avoids collision with any future Bootstrap re-introduction.
- **Base `.alert` class**: shared padding, border-radius, and border structure — variants only override colours. Makes adding a new variant trivial.
- **`#info`/`#error` use `@extend` instead of duplication**: the ID rules in `_eines.scss` `@extend .alert--success` and `@extend .alert--error` respectively, rather than repeating colour declarations. This keeps colour semantics entirely in `_alerts.scss` and the ID rules only own what is unique to them: visibility and transition behaviour. No tokens file needed — colours live in the component.
- **No class changes needed in templates for `#info`/`#error` elements**: the `@extend` approach means the HTML for those elements does not need alert classes added — SCSS handles it. Bootstrap classes are simply removed.
- **`.text-muted` in `_utilities.scss`**: single-property utility, fits alongside `.hidden`.

---

## Open Questions

### Resolved During Planning

- **Should `alert--error` or `alert--danger` be used?** `alert--error` — the codebase uses `#error` as the element ID and `displayError` as the function name; "error" is the established vocabulary here.
- **Where do `bg-success`/`bg-warning` result paragraphs go?** They should use `.alert--success` / `.alert--warning` — they are inline result displays, semantically equivalent to alerts.
- **Should colours live in `_tokens.scss` or directly in `_alerts.scss`?** Directly in `_alerts.scss`. The `@extend` approach means `_eines.scss` no longer needs to reference colours at all, so there is no cross-file duplication to resolve with tokens.

### Deferred to Implementation

- Exact padding/border-radius values for `.alert` — follow whatever `_panels.scss` uses for consistency; adjust if visually off.

---

## Implementation Units

### U1. Create `frontend/src/scss/components/_alerts.scss`

**Goal:** Implement the reusable `.alert`, `.alert--success`, `.alert--error`, `.alert--warning`, `.alert--info` classes.

**Requirements:** R1, R2, R6

**Dependencies:** None

**Files:**
- Create: `frontend/src/scss/components/_alerts.scss`
- Modify: `frontend/src/scss/main.scss` (add `@use 'components/alerts'` under section 5)

**Approach:**
- `.alert` base: padding, border (1px solid), border-radius, `display: block`.
- Each modifier (`--success`, `--error`, `--warning`, `--info`) sets `background-color`, `color`, `border-color` directly (no tokens file needed).
- Colour values: success `#dff0d8` / `#3c763d` / `#d6e9c6` (matching existing `#info` styles); error `#f2dede` / `#a94442` / `#ebccd1`; warning `#fcf8e3` / `#8a6d3b` / `#faebcc`; info `#d9edf7` / `#31708f` / `#bce8f1`.
- No `display: none` or visibility logic — that stays on the ID rules in `_eines.scss`.

**Patterns to follow:**
- `frontend/src/scss/components/_panels.scss` for SCSS structure.

**Test scenarios:**
- Test expectation: none — visual-only, no behaviour. Verify in browser after U2/U3 template changes.

**Verification:**
- Build succeeds. The four variant classes exist in the compiled CSS.

---

### U2. Update `_eines.scss` and `_utilities.scss`

**Goal:** Replace hardcoded colour declarations on `#info`/`#error` with `@extend`; add `.text-muted` utility.

**Requirements:** R3, R5

**Dependencies:** U1

**Files:**
- Modify: `frontend/src/scss/layouts/_eines.scss`
- Modify: `frontend/src/scss/_utilities.scss`

**Approach:**
- In `_eines.scss`: remove the standalone `#info { background-color/color/border }` and `#error { background-color/color/border }` blocks. Add `@extend .alert--success` inside the existing `#info` rule and `@extend .alert--error` inside `#error`. The `display: none`, `opacity`, `transition`, and `.visible` rules stay unchanged.
- In `_utilities.scss`: add `.text-muted { color: #777; }`.

**Test scenarios:**
- Test expectation: none — colour values are unchanged, only the mechanism changes.

**Verification:**
- Compiled output for `#info` and `#error` contains the same colour values as before. Build succeeds.

---

### U3. Update alert elements in tool/service templates

**Goal:** Strip dead Bootstrap classes from `#info`/`#error` elements in traductor, transcribe, dubbing, and transcribe-results templates. Colours are now provided by `@extend` in the SCSS — no new classes needed in HTML.

**Requirements:** R4, R5

**Dependencies:** U1

**Files:**
- Modify: `templates/traductor.twig`
- Modify: `templates/transcribe.twig`
- Modify: `templates/dubbing.twig`
- Modify: `templates/transcribe-results.twig`

**Approach:**
- For `#info` elements: remove `alert-warning`, `bg-success`, `text-success`. No replacement class needed.
- For `#error` elements: remove `alert-warning`, `bg-danger`, `text-danger`. No replacement class needed.
- For `#message_info` in `traductor.twig`: remove Bootstrap classes; add `alert alert--success` (this element is not covered by the ID `@extend` rules).
- For `transcribe-results.twig`: also remove the `hidden` class from `#info` and `#error` (CSS handles initial hidden state via the ID rule, same as the other templates).

**Test scenarios:**
- Test expectation: none — purely a class cleanup with no behaviour change.

**Verification:**
- No Bootstrap utility classes remain on `#info`/`#error`/`#message_info` elements. Visual appearance is unchanged.

---

### U4. Update result display paragraphs and other alert uses

**Goal:** Replace Bootstrap classes on non-ID alert elements in nombres-lletres, cercador-paraules, sep-sillabes, and archive-tasca.

**Requirements:** R4

**Dependencies:** U1

**Files:**
- Modify: `templates/nombres-lletres.twig`
- Modify: `templates/cercador-paraules.twig`
- Modify: `templates/sep-sillabes.twig`
- Modify: `templates/archive-tasca.twig`

**Approach:**
- `bg-success` result paragraphs → add `alert alert--success`, remove `bg-success`.
- `bg-warning` result paragraphs → add `alert alert--warning`, remove `bg-warning`.
- `alert alert-info` kanban empty state → add `alert alert--info`, remove `alert-info`.
- `text-muted` spans → keep class as-is (now backed by U3's utility definition).

**Test scenarios:**
- Test expectation: none — visual correctness verified in browser.

**Verification:**
- No `bg-success`, `bg-warning`, `alert-info` classes remain. Pages render result/empty-state elements with the correct alert colours.

---

## System-Wide Impact

- **Unchanged invariants:** Show/hide behaviour on `#info`/`#error` (the `.visible` JS toggle + CSS transition) is entirely unaffected — this plan only changes colour semantics.
- **No JS changes required.**
- **Build:** All changes are SCSS + Twig only; a single `npm run build` in `frontend/` produces the updated `static/css/main.min.css`.

---

## Risks & Dependencies

| Risk | Mitigation |
|------|------------|
| Result paragraphs in nombres-lletres / cercador-paraules / sep-sillabes may have page-specific layout that conflicts with `.alert` padding | Check visually after U5; adjust base `.alert` padding or override per-page if needed |
| `transcribe-results.twig` still uses `hidden` class — removing it must be coordinated with whatever JS controls that page | Confirm JS uses `.visible` toggle (same as the others) before removing `hidden` in U3 |

---

## Sources & References

- Related commits: `7da31a3`, `ef744ae` (visibility fix and colour restoration that motivated this plan)
- `frontend/src/scss/layouts/_eines.scss` — current `#info`/`#error` rules
- `frontend/src/scss/components/_panels.scss` — pattern to follow for new component
