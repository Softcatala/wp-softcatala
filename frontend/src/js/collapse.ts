/**
 * collapse.ts — Generic collapse/expand toggle
 *
 * Replaces: Bootstrap 3 Collapse plugin (data-toggle="collapse")
 *
 * Handles all `[data-toggle="collapse"]` triggers that are NOT part of the
 * mobile nav collapse-group (those are handled by nav-mobile.ts).
 *
 * Markup patterns:
 *   <a href="#versions" data-toggle="collapse">Altres versions</a>
 *   <div id="versions" class="collapse">...</div>
 *
 *   <button data-toggle="collapse" href="#mescomentaris_123">mes</button>
 *   <div id="mescomentaris_123" class="collapse">...</div>
 *
 * Accordion pattern (data-parent closes siblings):
 *   <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">...</a>
 *   <div id="collapseOne" class="panel-collapse collapse">...</div>
 *
 * Classes:
 *   .collapse  — hidden state
 *   .in        — visible state (added when shown, removed when hidden)
 *
 * Events emitted (for compatibility with old jQuery handlers):
 *   'show.collapse' — before showing
 *   'hide.collapse' — before hiding
 */

import { $, $$ } from './utils'

/**
 * Resolve the collapse target selector from a trigger element.
 * Looks at data-target first, then href.
 */
function getTargetSelector(trigger: HTMLElement): string | null {
  return trigger.dataset.target ?? trigger.getAttribute('href')
}

/**
 * Close a collapse target and update its trigger's aria-expanded.
 */
function closeCollapse(target: HTMLElement): void {
  target.dispatchEvent(new Event('hide.collapse'))
  target.classList.remove('in')
  target.classList.add('collapse')

  // Update aria-expanded on the trigger that points to this target
  const id = target.id
  if (id) {
    for (const t of $$(`[data-toggle="collapse"][href="#${id}"], [data-toggle="collapse"][data-target="#${id}"]`)) {
      t.setAttribute('aria-expanded', 'false')
    }
  }
}

export function initCollapse(): void {
  const triggers = $$('[data-toggle="collapse"]:not([data-collapse-group])')

  for (const trigger of triggers) {
    trigger.addEventListener('click', (e: Event) => {
      e.preventDefault()
      const targetSel = getTargetSelector(trigger)
      if (!targetSel) return

      const target = $(targetSel)
      if (!target) return

      const isOpen = target.classList.contains('in')

      // Accordion: close siblings within the same data-parent container
      const parentSel = trigger.dataset.parent
      if (parentSel && !isOpen) {
        const parent = $<HTMLElement>(parentSel)
        if (parent) {
          for (const sibling of $$<HTMLElement>('.in', parent)) {
            if (sibling !== target) {
              closeCollapse(sibling)
            }
          }
        }
      }

      if (isOpen) {
        closeCollapse(target)
        trigger.setAttribute('aria-expanded', 'false')
      } else {
        target.dispatchEvent(new Event('show.collapse'))
        target.classList.remove('collapse')
        target.classList.add('in')
        trigger.setAttribute('aria-expanded', 'true')
      }
    })
  }
}
