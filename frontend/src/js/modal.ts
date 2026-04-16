/**
 * modal.ts — Modal dialog open/close
 *
 * Replaces: Bootstrap 3 Modal plugin (data-toggle="modal", data-dismiss="modal")
 *
 * Markup pattern:
 *   <button data-toggle="modal" data-target=".my-modal">Open</button>
 *   <div class="modal fade my-modal" tabindex="-1" role="dialog">
 *     <div class="modal-dialog">
 *       <div class="modal-content">
 *         <button class="close" data-dismiss="modal">&times;</button>
 *         ...
 *       </div>
 *     </div>
 *   </div>
 *
 * Classes:
 *   .modal.fade     — hidden state
 *   .modal.fade.in  — visible state
 *   body.modal-open — scroll lock while modal is open
 */

import { $, $$ } from './utils'

/** Currently open modal (if any) */
let activeModal: HTMLElement | null = null

function openModal(modal: HTMLElement): void {
  activeModal = modal
  document.body.classList.add('modal-open')
  modal.classList.add('in')
  modal.setAttribute('aria-hidden', 'false')
}

function closeModal(modal: HTMLElement): void {
  modal.classList.remove('in')
  modal.setAttribute('aria-hidden', 'true')
  document.body.classList.remove('modal-open')
  activeModal = null
}

export function initModals(): void {
  // Open triggers: [data-toggle="modal"]
  for (const trigger of $$('[data-toggle="modal"]')) {
    trigger.addEventListener('click', (e: Event) => {
      e.preventDefault()
      const targetSel = trigger.dataset.target
      if (!targetSel) return
      const modal = $<HTMLElement>(targetSel)
      if (!modal) return
      openModal(modal)
    })
  }

  // Close triggers: [data-dismiss="modal"]
  for (const closer of $$('[data-dismiss="modal"]')) {
    closer.addEventListener('click', (e: Event) => {
      e.preventDefault()
      const modal = closer.closest<HTMLElement>('.modal')
      if (modal) closeModal(modal)
    })
  }

  // Close on backdrop click (click on .modal itself, not .modal-dialog)
  for (const modal of $$<HTMLElement>('.modal')) {
    modal.addEventListener('click', (e: Event) => {
      if (e.target === modal) {
        closeModal(modal)
      }
    })
  }

  // Close on Escape key
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && activeModal) {
      closeModal(activeModal)
    }
  })
}
