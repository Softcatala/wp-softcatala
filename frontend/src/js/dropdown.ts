/**
 * dropdown.ts — Desktop hover-open dropdowns for red sub-nav bar
 *
 * Replaces: jQuery .hover() + fadeIn/fadeOut on `ul.nav li.dropdown`
 *
 * Behavior:
 * - Desktop (hover: hover): open on mouseenter with 100ms delay, close on mouseleave
 * - All devices: open on click, close on outside click or Escape
 * - Adds/removes: .open, .seleccionat, .active on .dropdown-toggle
 * - Fade effect via CSS opacity transition (replaces jQuery fadeIn/fadeOut)
 *
 * Operates on existing BS3 markup — no data-* attributes needed.
 */

import { $$ } from './utils'

const DELAY = 100

let openTimer: ReturnType<typeof setTimeout> | undefined
let closeTimer: ReturnType<typeof setTimeout> | undefined

function toggle(li: HTMLElement, show: boolean): void {
  const menu = li.querySelector<HTMLElement>('.dropdown-hover, .dropdown-menu')
  const link = li.querySelector<HTMLElement>('.dropdown-toggle')
  if (!menu || !link) return

  if (show) {
    // Close any other open dropdown first
    $$('.dropdown.open').forEach(other => {
      if (other !== li) toggle(other, false)
    })
    li.classList.add('open')
    link.classList.add('seleccionat', 'active')
    link.setAttribute('aria-expanded', 'true')
    menu.style.display = 'block'
    // Trigger reflow then fade in
    void menu.offsetHeight
    menu.style.opacity = '1'
  } else {
    li.classList.remove('open')
    link.classList.remove('seleccionat', 'active')
    link.setAttribute('aria-expanded', 'false')
    menu.style.opacity = '0'
    // Wait for fade transition, then hide
    setTimeout(() => {
      if (!li.classList.contains('open')) {
        menu.style.display = ''
      }
    }, 200)
  }
}

export function initDropdowns(): void {
  const dropdowns = $$<HTMLLIElement>('ul.nav li.dropdown')
  if (!dropdowns.length) return

  const canHover = window.matchMedia('(hover: hover)').matches

  for (const li of dropdowns) {
    const link = li.querySelector<HTMLElement>('.dropdown-toggle')

    /* ── Hover behavior (desktop only) ─────────────────── */
    if (canHover) {
      li.addEventListener('mouseenter', () => {
        clearTimeout(closeTimer)
        openTimer = setTimeout(() => toggle(li, true), DELAY)
      })

      li.addEventListener('mouseleave', () => {
        clearTimeout(openTimer)
        closeTimer = setTimeout(() => toggle(li, false), DELAY)
      })
    }

    /* ── Click behavior (all devices) ──────────────────── */
    if (link) {
      link.addEventListener('click', (e: MouseEvent) => {
        e.preventDefault()
        const isOpen = li.classList.contains('open')
        toggle(li, !isOpen)
      })
    }

    /* ── Keyboard: Escape to close ─────────────────────── */
    li.addEventListener('keydown', (e: KeyboardEvent) => {
      if (e.key === 'Escape' && li.classList.contains('open')) {
        toggle(li, false)
        link?.focus()
      }
    })
  }

  /* ── Close on outside click ──────────────────────────── */
  document.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as Node
    for (const li of dropdowns) {
      if (li.classList.contains('open') && !li.contains(target)) {
        toggle(li, false)
      }
    }
  })
}
