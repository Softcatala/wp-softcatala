# FontAwesome 4 → 7 Icon Migration Map

Phase 2 reference: update all `.twig` templates and old HTML mockups to use FA7 class names.

## Prefix changes

FA4 used `fa` as the universal prefix. FA7 uses:
- `fas` — solid (default, most icons)
- `far` — regular (outline/lighter weight)
- `fab` — brands (logos)

All existing `<i class="fa fa-*">` must become `<i class="fas fa-*">`, `<i class="far fa-*">`, or `<i class="fab fa-*">` depending on the icon.

## Icons that need NO changes (34)

These keep the same name; only the prefix `fa` → `fas`:

| FA4 class | FA7 class |
|---|---|
| `fa fa-align-right` | `fas fa-align-right` |
| `fa fa-angle-down` | `fas fa-angle-down` |
| `fa fa-angle-left` | `fas fa-angle-left` |
| `fa fa-angle-right` | `fas fa-angle-right` |
| `fa fa-angle-up` | `fas fa-angle-up` |
| `fa fa-backward` | `fas fa-backward` |
| `fa fa-bars` | `fas fa-bars` |
| `fa fa-bold` | `fas fa-bold` |
| `fa fa-book` | `fas fa-book` |
| `fa fa-bookmark` | `fas fa-bookmark` |
| `fa fa-caret-right` | `fas fa-caret-right` |
| `fa fa-check` | `fas fa-check` |
| `fa fa-chevron-down` | `fas fa-chevron-down` |
| `fa fa-chevron-left` | `fas fa-chevron-left` |
| `fa fa-chevron-right` | `fas fa-chevron-right` |
| `fa fa-circle` | `fas fa-circle` |
| `fa fa-comment` | `fas fa-comment` |
| `fa fa-download` | `fas fa-download` |
| `fa fa-envelope` | `fas fa-envelope` |
| `fa fa-eraser` | `fas fa-eraser` |
| `fa fa-file` | `fas fa-file` |
| `fa fa-forward` | `fas fa-forward` |
| `fa fa-italic` | `fas fa-italic` |
| `fa fa-link` | `fas fa-link` |
| `fa fa-microphone` | `fas fa-microphone` |
| `fa fa-paperclip` | `fas fa-paperclip` |
| `fa fa-pause` | `fas fa-pause` |
| `fa fa-pencil` | `fas fa-pencil` |
| `fa fa-play` | `fas fa-play` |
| `fa fa-rss` | `fas fa-rss` |
| `fa fa-spinner` | `fas fa-spinner` |
| `fa fa-user` | `fas fa-user` |
| `fa fa-user-plus` | `fas fa-user-plus` |
| `fa fa-users` | `fas fa-users` |

## Icons with name changes (20) — prefix `fa` → `fas`

| FA4 class | FA7 class |
|---|---|
| `fa fa-calendar` | `fas fa-calendar-days` |
| `fa fa-clipboard` | `fas fa-paste` |
| `fa fa-cloud-download` | `fas fa-cloud-arrow-down` |
| `fa fa-cog` | `fas fa-gear` |
| `fa fa-cogs` | `fas fa-gears` |
| `fa fa-dashboard` | `fas fa-gauge-high` |
| `fa fa-exchange` | `fas fa-right-left` |
| `fa fa-expand` | `fas fa-up-right-and-down-left-from-center` |
| `fa fa-globe` | `fas fa-earth-americas` |
| `fa fa-history` | `fas fa-clock-rotate-left` |
| `fa fa-info-circle` | `fas fa-circle-info` |
| `fa fa-line-chart` | `fas fa-chart-line` |
| `fa fa-long-arrow-left` | `fas fa-left-long` |
| `fa fa-long-arrow-right` | `fas fa-right-long` |
| `fa fa-plus-circle` | `fas fa-circle-plus` |
| `fa fa-question-circle` | `fas fa-circle-question` |
| `fa fa-search` | `fas fa-magnifying-glass` |
| `fa fa-share-square-o` | `fas fa-share-from-square` |
| `fa fa-sort-alpha-asc` | `fas fa-arrow-down-a-z` |
| `fa fa-times` | `fas fa-xmark` |

## Icons moving to `far` (regular/outline) (6)

| FA4 class | FA7 class |
|---|---|
| `fa fa-clock-o` | `far fa-clock` |
| `fa fa-envelope-o` | `far fa-envelope` |
| `fa fa-keyboard-o` | `far fa-keyboard` |
| `fa fa-pencil-square-o` | `far fa-pen-to-square` |
| `fa fa-picture-o` | `far fa-image` |
| `fa fa-window-maximize` | `far fa-window-maximize` |

## Brand icons — prefix `fa` → `fab` (10)

| FA4 class | FA7 class | Notes |
|---|---|---|
| `fa fa-android` | `fab fa-android` | |
| `fa fa-apple` | `fab fa-apple` | |
| `fa fa-facebook` | `fab fa-facebook-f` | Name changed |
| `fa fa-github` | `fab fa-github` | |
| `fa fa-google-plus` | `fab fa-google-plus-g` | Name changed |
| `fa fa-linkedin` | `fab fa-linkedin-in` | Name changed |
| `fa fa-linux` | `fab fa-linux` | |
| `fa fa-telegram` | `fab fa-telegram` | |
| `fa fa-twitter` | `fab fa-twitter` | Consider `fab fa-x-twitter` for modern X logo |
| `fa fa-windows` | `fab fa-windows` | |

## Summary

- **70 total icons** across templates and mockups
- **34 unchanged** (prefix only: `fa` → `fas`)
- **20 renamed** (solid icons with new FA7 names)
- **6 moved to regular** (`far`, outline style)
- **10 brand icons** (`fab`, 3 with name changes)
