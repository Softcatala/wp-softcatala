/**
 * tabs.ts — Accessible tablist with arrow-key navigation
 *
 * Markup:
 *   <ul class="nav nav-tabs" role="tablist">
 *     <li class="active">
 *       <a href="#recursos" aria-controls="recursos" role="tab" data-toggle="tab">...</a>
 *     </li>
 *   </ul>
 *   <div class="tab-content">
 *     <div role="tabpanel" class="tab-pane active" id="recursos">...</div>
 *   </div>
 *
 * Only `.active` controls visibility (CSS: .tab-pane hides, .tab-pane.active shows).
 * ARIA: aria-selected, tabindex roving, arrow-key navigation.
 */

import { $$ } from './utils'

/**
 * Activate a tab and its corresponding panel.
 */
export function activateTab(tab: HTMLElement, focus = false): void {
  const tablist = tab.closest<HTMLElement>('[role="tablist"], .nav-tabs')
  if (!tablist) return

  const tabs = $$<HTMLAnchorElement>('a[role="tab"], a[data-toggle="tab"]', tablist)
  const panelId = tab.getAttribute('aria-controls') ?? tab.getAttribute('href')?.replace('#', '')
  if (!panelId) return

  const panel = document.getElementById(panelId)
  if (!panel) return
  const tabContent = panel.parentElement

  // Deactivate all
  for (const t of tabs) {
    t.classList.remove('active')
    t.setAttribute('aria-selected', 'false')
    t.setAttribute('tabindex', '-1')
    t.parentElement?.classList.remove('active')
  }

  if (tabContent) {
    $$('.tab-pane', tabContent).forEach(p => p.classList.remove('active'))
  }

  // Activate selected
  tab.classList.add('active')
  tab.setAttribute('aria-selected', 'true')
  tab.setAttribute('tabindex', '0')
  tab.parentElement?.classList.add('active')

  panel.classList.add('active')

  if (focus) tab.focus()
}

/**
 * Initialize all tablists on the page.
 */
export function initTabs(): void {
  const tablists = $$('.nav-tabs, [role="tablist"]')

  for (const tablist of tablists) {
    const tabs = $$<HTMLAnchorElement>('a[role="tab"], a[data-toggle="tab"]', tablist)
    if (!tabs.length) continue

    // Set initial ARIA state
    for (const tab of tabs) {
      const isActive =
        tab.parentElement?.classList.contains('active') ??
        tab.classList.contains('active')
      tab.setAttribute('role', 'tab')
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false')
      tab.setAttribute('tabindex', isActive ? '0' : '-1')

      const panelId = tab.getAttribute('aria-controls') ?? tab.getAttribute('href')?.replace('#', '')
      const panel = panelId ? document.getElementById(panelId) : null
      if (panel) {
        panel.setAttribute('role', 'tabpanel')
        panel.setAttribute('aria-labelledby', tab.id)
      }
    }

    // Click handler
    tablist.addEventListener('click', (e: MouseEvent) => {
      const target = e.target as HTMLElement | null
      const tab = target?.closest<HTMLAnchorElement>('a[role="tab"], a[data-toggle="tab"]')
      if (!tab) return
      e.preventDefault()
      activateTab(tab, true)
    })

    // Arrow-key navigation
    tablist.addEventListener('keydown', (e: KeyboardEvent) => {
      const arrowKeys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'] as const
      if (!(arrowKeys as readonly string[]).includes(e.key)) return

      const focused = document.activeElement as HTMLElement | null
      if (!focused) return
      const idx = tabs.indexOf(focused as HTMLAnchorElement)
      if (idx === -1) return

      e.preventDefault()
      let next: HTMLAnchorElement | undefined

      switch (e.key) {
        case 'ArrowRight':
          next = tabs[(idx + 1) % tabs.length]
          break
        case 'ArrowLeft':
          next = tabs[(idx - 1 + tabs.length) % tabs.length]
          break
        case 'Home':
          next = tabs[0]
          break
        case 'End':
          next = tabs[tabs.length - 1]
          break
      }

      if (next) activateTab(next, true)
    })
  }
}
