/**
 * search.ts — Expanding search bar
 *
 * Replaces: UISearch bower component (expanding-search-bar/js/uisearch.js)
 *           + classie library
 *
 * Markup (html-header.twig):
 *   <div id="sb-search" class="sb-search">
 *     <form role="search" ...>
 *       <input class="sb-search-input" ...>
 *       <input class="sb-search-submit" ...>
 *       <span class="sb-icon-search"><i class="fa fa-search"></i></span>
 *     </form>
 *   </div>
 *
 * Behavior:
 * - Click on .sb-search or icon -> add .sb-search-open, focus input
 * - Click outside or blur -> if input empty, remove .sb-search-open
 * - If input has text and is open -> allow form submit
 */

import { $ } from './utils'

export function initSearch(): void {
  const maybeEl = $<HTMLDivElement>('#sb-search')
  if (!maybeEl) return
  const el: HTMLDivElement = maybeEl

  const maybeInput = el.querySelector<HTMLInputElement>('.sb-search-input')
  if (!maybeInput) return
  const input: HTMLInputElement = maybeInput

  function open(): void {
    el.classList.add('sb-search-open')
    input.focus()
  }

  function close(): void {
    input.blur()
    el.classList.remove('sb-search-open')
  }

  function isOpen(): boolean {
    return el.classList.contains('sb-search-open')
  }

  // Click on the search container -> open or submit
  el.addEventListener('click', (e: MouseEvent) => {
    e.stopPropagation()
    const val = input.value.trim()

    if (!isOpen()) {
      e.preventDefault()
      open()
    } else if (!val) {
      e.preventDefault()
      close()
    }
    // If open and has value -> let the form submit naturally
  })

  // Clicks inside the input shouldn't close
  input.addEventListener('click', (e: MouseEvent) => {
    e.stopPropagation()
  })

  // Close on outside click
  document.addEventListener('click', () => {
    if (isOpen()) close()
  })
}
