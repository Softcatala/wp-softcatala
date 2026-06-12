/**
 * kanban.ts — Kanban board: filter bar, URL hash sync, drag-and-drop, task detail modal.
 *
 * Localised data: window.sc_kanban (via wp_localize_script in archive-tasca.php)
 *
 * Dependencies on HTML structure set by archive-tasca.twig:
 *   #kanban-board            — board container
 *   .kanban-column           — one column per estat_tasca term; data-estat attribute
 *   .kanban-column__cards    — drop zone inside each column
 *   .kanban-card             — task card; data-id, data-projecte, data-assignee,
 *                              data-milestone, data-estat, data-tags, data-due
 *   .kanban-card__handle     — drag handle
 *   #kanban-modal            — Bootstrap-style modal
 *   #kanban-modal-label      — modal title element
 *   #kanban-modal-body       — modal body element
 *   #kanban-modals           — hidden container of pre-rendered task detail divs
 *   #task-detail-{id}        — pre-rendered detail div per task
 *   .kanban-filter           — filter <select> elements; data-filter-type attribute
 *   #kanban-reset-filters    — reset button
 *   .kanban-column__count    — badge showing visible card count per column
 *   #kanban-notice           — dismissible notice container (fixed, bottom-right)
 */

import Sortable, { type SortableEvent } from 'sortablejs'
import { openModal } from './modal'

declare global {
  interface Window {
    sc_kanban?: {
      rest_url: string
      nonce: string
      is_logged_in: boolean
    }
  }
}

// ─── Guard: board must be present ─────────────────────────────────────────────
const board = document.getElementById('kanban-board')
if (board) {
  initKanban()
}

function initKanban(): void {
  const isLoggedIn = !!window.sc_kanban?.is_logged_in
  const restUrl    = window.sc_kanban?.rest_url ?? ''
  const nonce      = window.sc_kanban?.nonce ?? ''

  let filterState: Record<string, string> = {}
  let isDragging = false

  // ─── Mark overdue due-date badges ───────────────────────────────────────────
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  for (const badge of document.querySelectorAll<HTMLElement>('.kanban-card__badge--due[data-due]')) {
    const raw = badge.dataset.due
    if (!raw) continue
    const parts = raw.split('-')
    if (parts.length !== 3) continue
    const due = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
    if (due < today) badge.classList.add('is-overdue')
  }

  // ─── Column collapse toggle ──────────────────────────────────────────────────
  const COL_WIDTH     = 220 // px — must match .kanban-column width in kanban.css
  const COL_COLLAPSED = 48  // px — must match .kanban-column--collapsed width
  const COL_GAP       = 14  // px — must match .kanban-board gap

  function toggleColumnCollapse(column: HTMLElement): void {
    const collapsed = column.classList.contains('kanban-column--collapsed')
    column.classList.toggle('kanban-column--collapsed', !collapsed)
    column.querySelector('.kanban-column__header')
          ?.setAttribute('aria-expanded', collapsed ? 'true' : 'false')
  }

  function initCollapseState(): void {
    const boardEl    = document.getElementById('kanban-board')!
    const boardWidth = boardEl.parentElement?.clientWidth ?? boardEl.clientWidth
    const allCols    = Array.from(boardEl.querySelectorAll<HTMLElement>('.kanban-column, .kanban-column--terminal'))
    const totalCols  = allCols.length

    const fullWidth = totalCols * COL_WIDTH + Math.max(0, totalCols - 1) * COL_GAP

    // Strip any server-rendered collapsed classes — JS owns this.
    for (const col of allCols) {
      col.classList.remove('kanban-column--collapsed')
      col.querySelector('.kanban-column__header')?.setAttribute('aria-expanded', 'true')
    }

    if (fullWidth <= boardWidth) return

    // Collapse empty collapsible columns from the end backwards.
    const candidates = allCols
      .filter(col => col.dataset.collapsible === '1' && col.querySelectorAll('.kanban-card').length === 0)
      .reverse()

    let currentWidth = fullWidth
    for (const col of candidates) {
      if (currentWidth <= boardWidth) break
      col.classList.add('kanban-column--collapsed')
      col.querySelector('.kanban-column__header')?.setAttribute('aria-expanded', 'false')
      currentWidth -= (COL_WIDTH - COL_COLLAPSED)
    }
  }

  initCollapseState()

  document.addEventListener('click', (e) => {
    const header = (e.target as HTMLElement).closest<HTMLElement>(
      '.kanban-column[data-collapsible="1"] .kanban-column__header'
    )
    if (header) toggleColumnCollapse(header.closest<HTMLElement>('.kanban-column')!)
  })

  document.addEventListener('keydown', (e: KeyboardEvent) => {
    const header = (e.target as HTMLElement).closest<HTMLElement>(
      '.kanban-column[data-collapsible="1"] .kanban-column__header'
    )
    if (header && (e.key === 'Enter' || e.key === ' ')) {
      e.preventDefault()
      toggleColumnCollapse(header.closest<HTMLElement>('.kanban-column')!)
    }
  })

  // ─── Filter helpers ──────────────────────────────────────────────────────────

  /**
   * Parse the URL hash into a filter state object.
   * Only permits [a-z0-9_-] characters in values to prevent injection via hash.
   */
  function parseHash(): Record<string, string> {
    const hash = window.location.hash.replace('#', '')
    const state: Record<string, string> = {}
    if (!hash) return state
    for (const part of hash.split('&')) {
      const [key, rawValue] = part.split('=')
      const value = (rawValue ? decodeURIComponent(rawValue) : '').replace(/[^a-z0-9_-]/gi, '')
      if (key && value) state[key] = value
    }
    return state
  }

  function serialiseHash(state: Record<string, string>): string {
    const parts = Object.entries(state)
      .filter(([, v]) => v)
      .map(([k, v]) => `${k}=${encodeURIComponent(v)}`)
    return parts.length ? '#' + parts.join('&') : ''
  }

  function applyFilters(): void {
    for (const card of document.querySelectorAll<HTMLElement>('.kanban-card')) {
      let visible = true
      const d = card.dataset

      if (filterState.project && d.projecte !== filterState.project) visible = false
      if (filterState.assignee) {
        const assignees = (d.assignee ?? '').split(',')
        if (!assignees.includes(filterState.assignee)) visible = false
      }
      if (filterState.milestone && d.milestone !== filterState.milestone) visible = false
      if (filterState.estat && d.estat !== filterState.estat) visible = false
      if (filterState.tag) {
        const tags = (d.tags ?? '').split(',')
        if (!tags.includes(filterState.tag)) visible = false
      }

      card.style.display = visible ? '' : 'none'
    }

    for (const col of document.querySelectorAll<HTMLElement>('.kanban-column')) {
      const count = col.querySelectorAll('.kanban-card:not([style*="display: none"]):not([style*="display:none"])').length
      const badge = col.querySelector('.kanban-column__count')
      if (badge) badge.textContent = String(count)
    }
  }

  function syncSelects(): void {
    for (const sel of document.querySelectorAll<HTMLSelectElement>('.kanban-filter')) {
      sel.value = filterState[sel.dataset.filterType ?? ''] ?? ''
    }
  }

  function loadFromHash(): void {
    filterState = parseHash()
    syncSelects()
    applyFilters()
  }

  // ─── Filter bar events ───────────────────────────────────────────────────────
  document.addEventListener('change', (e) => {
    const sel = (e.target as HTMLElement).closest<HTMLSelectElement>('.kanban-filter')
    if (!sel) return
    const type = sel.dataset.filterType ?? ''
    if (sel.value) {
      filterState[type] = sel.value
    } else {
      delete filterState[type]
    }
    window.location.hash = serialiseHash(filterState)
    applyFilters()
  })

  document.getElementById('kanban-reset-filters')?.addEventListener('click', () => {
    filterState = {}
    history.replaceState(null, '', window.location.pathname + window.location.search)
    syncSelects()
    applyFilters()
  })

  window.addEventListener('hashchange', loadFromHash)
  loadFromHash()

  // ─── Task detail modal ───────────────────────────────────────────────────────
  document.addEventListener('click', (e) => {
    const card = (e.target as HTMLElement).closest<HTMLElement>('.kanban-card')
    if (!card) return
    if ((e.target as HTMLElement).closest('.kanban-card__handle') || isDragging) return

    const id     = card.dataset.id
    const title  = card.querySelector('.kanban-card__title')?.textContent?.trim() ?? ''
    const detail = document.getElementById(`task-detail-${id}`)
    if (!detail) return

    const modalLabel = document.getElementById('kanban-modal-label')
    const modalBody  = document.getElementById('kanban-modal-body')
    const modal      = document.getElementById('kanban-modal')

    if (modalLabel) modalLabel.textContent = title
    if (modalBody)  modalBody.innerHTML = detail.innerHTML
    if (modal)      openModal(modal)
  })

  document.addEventListener('keydown', (e: KeyboardEvent) => {
    const card = (e.target as HTMLElement).closest<HTMLElement>('.kanban-card')
    if (card && (e.key === 'Enter' || e.key === ' ')) {
      e.preventDefault()
      card.click()
    }
  })

  // ─── Notice helpers ──────────────────────────────────────────────────────────
  function showNotice(message: string, type = 'danger'): void {
    const notice = document.getElementById('kanban-notice')
    if (!notice) return
    notice.className = `alert alert-${type}`
    notice.innerHTML = `${message} <button type="button" class="close" aria-label="Tanca"><span aria-hidden="true">&times;</span></button>`
    notice.style.display = ''
  }

  document.addEventListener('click', (e) => {
    if ((e.target as HTMLElement).closest('#kanban-notice .close')) {
      const notice = document.getElementById('kanban-notice')
      if (notice) notice.style.display = 'none'
    }
  })

  // ─── PATCH helper ────────────────────────────────────────────────────────────
  function revertCard(card: HTMLElement, originalColumn: HTMLElement, nextSibling: Element | null): void {
    if (nextSibling) {
      originalColumn.insertBefore(card, nextSibling)
    } else {
      originalColumn.appendChild(card)
    }
  }

  function patchTaskEstat(
    id: string,
    newEstat: string,
    card: HTMLElement,
    originalColumn: HTMLElement,
    nextSibling: Element | null
  ): void {
    fetch(restUrl + id + '/estat', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify({ estat: newEstat }),
    })
      .then(response => {
        if (response.ok) {
          card.dataset.estat = newEstat
          return
        }
        revertCard(card, originalColumn, nextSibling)
        if (response.status === 401) {
          showNotice(
            `La sessió ha caducat. <a href="${window.location.pathname}">Torneu a iniciar sessió</a>.`,
            'warning'
          )
        } else {
          showNotice("S'ha produït un error en desar el canvi. Torneu-ho a provar.")
        }
      })
      .catch(() => {
        revertCard(card, originalColumn, nextSibling)
        showNotice("S'ha produït un error de xarxa. Torneu-ho a provar.")
      })
  }

  function updateColumnCounts(): void {
    for (const col of document.querySelectorAll<HTMLElement>('.kanban-column')) {
      const count = col.querySelectorAll('.kanban-card:not([style*="display: none"]):not([style*="display:none"])').length
      const badge = col.querySelector('.kanban-column__count')
      if (badge) badge.textContent = String(count)
    }
  }

  // ─── Drag-and-drop (logged-in only) ─────────────────────────────────────────
  if (isLoggedIn) {
    for (const dropZone of document.querySelectorAll<HTMLElement>('.kanban-column__cards')) {
      Sortable.create(dropZone, {
        group: { name: 'kanban', pull: true, put: true },
        animation:  150,
        handle:     '.kanban-card__handle',
        draggable:  '.kanban-card',
        sort:       false, // intra-column reorder disabled (see file header)
        ghostClass: 'kanban-card--ghost',

        onStart: () => { isDragging = true },
        onEnd:   () => { setTimeout(() => { isDragging = false }, 50) },

        onAdd: (evt: SortableEvent) => {
          if (evt.from === evt.to) return

          const card   = evt.item
          const cardId = card.dataset.id

          // For terminal columns the drop zone is a .kanban-column__cards inside
          // a .kanban-column__section which carries the real estat slug.
          const section = evt.to.closest<HTMLElement>('.kanban-column__section[data-estat]')
          const newEstat = section
            ? (section.dataset.estat ?? '')
            : (evt.to.closest<HTMLElement>('.kanban-column')?.dataset.estat ?? '')

          if (!cardId || newEstat === undefined) return

          const oldIndex = evt.oldIndex ?? 0
          const nextSibling = oldIndex < evt.from.children.length
            ? evt.from.children[oldIndex]
            : null

          updateColumnCounts()
          patchTaskEstat(cardId, newEstat, card, evt.from, nextSibling)
        },
      })
    }
  }
}
