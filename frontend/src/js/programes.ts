/**
 * Programes — page behaviours for the `programa` post type
 *
 * Vanilla TypeScript port of static/js/programes.js. Covers four independent
 * features, each a no-op when its markup is absent:
 *   1. Download button   — reveal the build matching the visitor's platform
 *   2. Search filters    — auto-submit the archive search on dropdown change
 *   3. Rating            — send a vote and reflect it immediately
 *   4. Add-program form  — the four-step wizard inside the modal
 *
 * Loaded by archive-programa.php, single-programa.php and subpagina-programa.php.
 *
 * Globals injected by wp_localize_script:
 *   scajax.ajax_url — WordPress AJAX endpoint
 */

import { $, $$, detectOS } from './utils'
import { openModal } from './modal'

declare const scajax: { ajax_url: string }

const GENERIC_ERROR = "S'ha produït un error en enviar les dades. Proveu una altra vegada més tard."

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/** Value of a nonce field, or '' when the field is missing. */
function nonce(name: string): string {
  return $<HTMLInputElement>(`input[name=${name}]`)?.value ?? ''
}

/** POST a FormData payload to admin-ajax and parse the JSON response. */
async function postToAjax(data: FormData): Promise<any> {
  const res = await fetch(scajax.ajax_url, { method: 'POST', body: data })
  if (!res.ok) throw new Error(`admin-ajax returned ${res.status}`)
  return res.json()
}

function show(el: HTMLElement | null): void {
  el?.style.removeProperty('display')
}

function hide(el: HTMLElement | null): void {
  if (el) el.style.display = 'none'
}

/** Show the shared messages modal (templates/messages.twig). */
function showMessage(text: string): void {
  const target = $('#message_text')
  if (target) target.textContent = text
  const modal = $('.bs-messages-modal-lg')
  if (modal) openModal(modal)
}

// ---------------------------------------------------------------------------
// Vote bookkeeping
// ---------------------------------------------------------------------------
//
// Which programs this browser has voted for, one entry per program under the
// same key the cookie used. Kept in localStorage because nothing server-side
// ever read the cookie — it rode along on every request to the origin, static
// assets included, for nothing.

const STORAGE_PROBE = '__sc_probe__'

let storageResolved = false
let storageRef: Storage | null = null

/** localStorage, or null where it is unavailable (private modes, disabled storage). */
function storage(): Storage | null {
  if (!storageResolved) {
    storageResolved = true
    try {
      const candidate = window.localStorage
      candidate.setItem(STORAGE_PROBE, '1')
      candidate.removeItem(STORAGE_PROBE)
      storageRef = candidate
    } catch {
      storageRef = null
    }
  }
  return storageRef
}

function hasCookie(name: string): boolean {
  return document.cookie
    .split('; ')
    .some((cookie) => cookie.split('=')[0] === name)
}

/** '/programes/gimp/' -> ['/', '/programes', '/programes/gimp'] */
function ancestorPaths(pathname: string): string[] {
  const paths = ['/']
  let path = ''
  for (const segment of pathname.split('/').filter(Boolean)) {
    path += `/${segment}`
    paths.push(path)
  }
  return paths
}

/**
 * Delete a cookie. `document.cookie` exposes names and values but never the
 * path a cookie was set with, and deleting needs an exact path match — so
 * clear every path this one could hold: the pre-`path=/` default (the
 * directory of the page that cast the vote), its ancestors, and the root.
 */
function expireCookie(name: string): void {
  for (const path of ancestorPaths(window.location.pathname)) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}`
  }
}

function writeVoteCookie(key: string): void {
  const expiry = new Date()
  expiry.setFullYear(expiry.getFullYear() + 10)
  document.cookie = `${key}=1; expires=${expiry.toUTCString()}; path=/`
}

/** Remember a vote. Epoch seconds, so votes can be aged out later if wanted. */
function recordVote(key: string): void {
  const store = storage()
  if (store) {
    try {
      store.setItem(key, String(Math.floor(Date.now() / 1000)))
      return
    } catch {
      // Quota exhausted — fall through to the cookie.
    }
  }
  writeVoteCookie(key)
}

function hasVoted(key: string): boolean {
  const store = storage()
  return store ? store.getItem(key) !== null : hasCookie(key)
}

/**
 * Move a pre-localStorage vote across and drop the cookie.
 *
 * Legacy cookies were scoped to the directory of the page that set them, so
 * the only page that can see one is that program's own page — which is where
 * this runs. There is no vantage point from which the whole set is visible,
 * and a cookie for a program the visitor never returns to simply expires on
 * its own in 2035.
 */
function migrateLegacyVote(key: string): void {
  if (!hasCookie(key)) return
  if (storage()) {
    recordVote(key)
    expireCookie(key)
  }
}

// ---------------------------------------------------------------------------
// 1. Download button — reveal the build matching the visitor's platform
// ---------------------------------------------------------------------------

/**
 * Coarse CPU architecture from the user agent. Only distinguishes the two
 * values used in download button ids; everything not advertising a 64-bit
 * Windows build is treated as x86.
 */
function detectCpuArchitecture(): string {
  const ua = navigator.userAgent
  const is64 = ua.includes('WOW64') || ua.includes('Win64') || ua.includes('x86_64')
  return is64 ? 'x86_64' : 'x86'
}

/**
 * Reveal the most specific download button available for this platform,
 * falling back through architectures and finally to the first button.
 */
function showDownloadVersion(os: string | null, cpuArchitecture: string): void {
  const candidates = os
    ? [
        `baixada_${os}_${cpuArchitecture}`,
        `baixada_${os}_x86`,
        `baixada_${os}_x86_64`,
        `baixada_${os}_generic`,
      ]
    : []

  for (const id of candidates) {
    const button = document.getElementById(id)
    if (button) {
      show(button)
      return
    }
  }

  show($('.baixada_boto'))
}

function initDownloadButtons(): void {
  const buttons = $$('.baixada_boto')
  if (buttons.length === 0) return

  showDownloadVersion(detectOS(), detectCpuArchitecture())

  // With two or fewer builds there is nothing worth expanding.
  if (buttons.length <= 2) hide($('#show_more_versions'))
}

// ---------------------------------------------------------------------------
// 2. Search filters — auto-submit the archive search on dropdown change
// ---------------------------------------------------------------------------

/**
 * Drop empty filters from the query string by disabling their fields, so the
 * resulting URL only carries the filters the visitor actually set.
 */
function disableEmptyFields(form: HTMLFormElement): void {
  for (const field of $$<HTMLInputElement | HTMLSelectElement>('input, select', form)) {
    if (field.value === '' || field.value === '0') field.disabled = true
  }
}

function initSearchFilters(): void {
  const form = $<HTMLFormElement>('#cerca_programes')
  if (!form) return

  form.addEventListener('submit', () => disableEmptyFields(form))

  // Only the filters inside the search form auto-submit. The old code bound
  // every `.selectpicker` on the page — which also matches the dropdowns in the
  // add-program and contact modals — and leaned on a modal-visibility check to
  // undo it.
  for (const select of $$<HTMLSelectElement>('select.selectpicker', form)) {
    select.addEventListener('change', () => form.requestSubmit())
  }
}

// ---------------------------------------------------------------------------
// 3. Rating — send a vote and reflect it immediately
// ---------------------------------------------------------------------------

interface VoteResult {
  status?: number
  text?: string
  cookie_id?: string
  vots?: number
  valoracio?: string
}

/** Percentage width for the star overlay, from a 0–5 rating. */
function ratingToWidth(rating: number): string {
  return `${Math.round((rating / 5) * 100)}%`
}

/** Parse the Catalan-formatted average the server returns ("4,25"). */
function parseCatalanNumber(value: string): number {
  return Number.parseFloat(value.replace(/\./g, '').replace(',', '.'))
}

function initRating(): void {
  const group = $<HTMLElement>('#input_rating')
  // Scope to this widget: program cards elsewhere on the page carry their own
  // read-only star display.
  const rootEl = group?.closest<HTMLElement>('.cont-rating')
  const starsEl = group?.closest('.rating-container')?.querySelector<HTMLElement>('.rating-stars')
  const rawPostId = group?.dataset.postId
  if (!group || !rootEl || !starsEl || !rawPostId) return
  // Re-bind after the guard: the handlers below are hoisted function
  // declarations, so TypeScript cannot carry the narrowing into them.
  const container = rootEl
  const stars = starsEl
  const postId = rawPostId

  // The hidden field's name doubles as the per-program storage key.
  const cookieId = `sc_${container.querySelector<HTMLInputElement>('#input_rating_value')?.name ?? ''}`
  // Convert on load rather than on the next vote attempt — someone who has
  // already voted never clicks again, which is exactly who holds a cookie.
  migrateLegacyVote(cookieId)
  // Width to restore when the pointer leaves without a vote being cast.
  let baseWidth = stars.style.width

  function preview(rate: number): void {
    stars.style.width = ratingToWidth(rate)
  }

  function resetPreview(): void {
    stars.style.width = baseWidth
  }

  function applyResult(result: VoteResult): void {
    if (result.valoracio) {
      baseWidth = ratingToWidth(parseCatalanNumber(result.valoracio))
      resetPreview()
      const numero = container.querySelector('span.numero')
      if (numero) numero.textContent = result.valoracio
    }
    if (result.vots !== undefined) {
      // The counter is only server-rendered once a program has votes, so the
      // first vote has to create it.
      let vots = container.querySelector('em')
      if (!vots) vots = container.appendChild(document.createElement('em'))
      vots.textContent = `(${result.vots} vots)`
    }
  }

  async function sendVote(rate: number): Promise<void> {
    if (hasVoted(cookieId)) {
      resetPreview()
      showMessage('Sembla que ja havies votat abans...')
      return
    }

    const data = new FormData()
    data.append('post_id', postId)
    data.append('rate', String(rate))
    data.append('cookie_id', cookieId)
    data.append('action', 'send_vote')
    data.append('_wpnonce', nonce('_wpnonce_program_vote'))

    try {
      const result: VoteResult = await postToAjax(data)
      if (result.status === 1) {
        applyResult(result)
        // Record under the key we sent, not the one echoed back: the server
        // sanitises `cookie_id`, and a rewritten key would never match on the
        // next visit.
        recordVote(cookieId)
      } else {
        resetPreview()
      }
      showMessage(result.text ?? GENERIC_ERROR)
    } catch {
      resetPreview()
      showMessage(GENERIC_ERROR)
    }
  }

  for (const radio of $$<HTMLInputElement>('input[type=radio]', group)) {
    const rate = Number(radio.value)
    radio.addEventListener('change', () => void sendVote(rate))
    radio.addEventListener('focus', () => preview(rate))
    radio.addEventListener('blur', resetPreview)
    radio.parentElement?.addEventListener('mouseenter', () => preview(rate))
  }

  group.addEventListener('mouseleave', resetPreview)
}

// ---------------------------------------------------------------------------
// 4. Add-program form — the four-step wizard inside the modal
// ---------------------------------------------------------------------------

interface StepResult {
  status?: number
  text?: string
  post_id?: number
  programs?: string
}

function showFormError(formId: string, text?: string): void {
  const form = document.getElementById(formId)
  if (!form) return
  const message = form.querySelector('.form-error-text')
  if (message) message.textContent = text || GENERIC_ERROR
  form.querySelector('.form-error')?.classList.add('visible')
}

function hideFormError(formId: string): void {
  document.getElementById(formId)?.querySelector('.form-error')?.classList.remove('visible')
}

/**
 * Move the wizard from the given step to the next one.
 *
 * `actiu` marks the step the visitor is on. It has no styling attached — the
 * only reader is the reset below, which uses it to tell whether the downloads
 * step has been reached.
 */
function goToStep(from: number): void {
  const current = document.getElementById(`form_${from}`)
  const next = document.getElementById(`form_${from + 1}`)
  hide(current)
  current?.classList.remove('actiu')
  show(next)
  next?.classList.add('actiu')
}

function initStepButtons(): void {
  for (const button of $$('.next_step')) {
    button.addEventListener('click', (e) => {
      e.preventDefault()
      const from = Number(button.id.split('_')[1])
      if (!Number.isFinite(from)) return
      goToStep(from)
      // Stepping forward from the start clears any downloads-step progress.
      if (from === 1) document.getElementById('form_3')?.classList.remove('actiu')
    })
  }

  // Reopening the modal restarts the wizard, unless the visitor is mid-way
  // through the downloads step.
  $('#afegeix_programa_button')?.addEventListener('click', () => {
    if (document.getElementById('form_3')?.classList.contains('actiu')) return
    hide(document.getElementById('form_4'))
    hide(document.getElementById('form_2'))
    show(document.getElementById('form_1'))
  })
}

/** Step 1: check whether the program already exists. */
function initSearchProgramForm(): void {
  const form = $<HTMLFormElement>('#second_step')
  if (!form) return

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    const loading = $('#loading')
    show(loading)

    const data = new FormData()
    data.append('nom_programa', $<HTMLInputElement>('#nom_programa')?.value ?? '')
    data.append('action', 'search_program')
    data.append('_wpnonce', nonce('_wpnonce_program_search'))

    const response = $('#text_response')
    try {
      const result: StepResult = await postToAjax(data)
      hide(loading)
      // The server returns a rendered list of matching programs.
      if (response) response.innerHTML = (result.text ?? '') + (result.programs ?? '')
      show(document.getElementById('pas_1'))
    } catch {
      hide(loading)
      if (response) response.textContent = 'Proveu més tard'
    }
  })
}

/** Step 2: submit the program details, including the optional images. */
function initAddProgramForm(): void {
  const form = $<HTMLFormElement>('#programa_form')
  if (!form) return

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    hideFormError('form_2')
    const loading = $('#loading_program')
    show(loading)

    const value = (selector: string): string =>
      $<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(selector, form)?.value ?? ''

    const data = new FormData()
    data.append('email_usuari', value('input[name=email_usuari]'))
    data.append('comentari_usuari', value('textarea[name=comentari_usuari]'))
    data.append('nom', value('input[name=nom]'))
    data.append('autor_programa', value('input[name=autor]'))
    data.append('lloc_web_programa', value('input[name=lloc_web]'))
    data.append('descripcio', value('textarea[name=descripcio]'))
    data.append('llicencia', value('#llicencia'))
    data.append('categoria_programa', value('input[name=categoria_programa]:checked'))
    data.append('autor_traduccio', value('input[name=autor_traduccio]'))
    data.append('action', 'add_new_program')
    data.append('_wpnonce', nonce('_wpnonce_program'))

    // Logo and screenshot are optional: only send fields that hold a file,
    // otherwise the server receives the string "undefined".
    for (const field of ['logo', 'captura']) {
      const file = $<HTMLInputElement>(`input[name="${field}"]`, form)?.files?.[0]
      if (file) data.append(field, file)
    }

    try {
      const result: StepResult = await postToAjax(data)
      hide(loading)

      // The server answers with status 0 when it could not store the program:
      // stay on this step instead of walking the user through to the thank you
      // screen.
      if (result.status !== 1) {
        showFormError('form_2', result.text)
        return
      }

      hideFormError('form_2')
      goToStep(2)
      const programaId = $<HTMLInputElement>('#programa_id')
      if (programaId && result.post_id !== undefined) programaId.value = String(result.post_id)
    } catch {
      hide(loading)
      showFormError('form_2')
    }
  })
}

/**
 * Step 3: repeatable download blocks.
 *
 * Each block carries its own `[n]` index so that the radio groups within a
 * block are independent. The old code built its RegExp from the string "[1]",
 * which JavaScript reads as a character class matching every "1" in the
 * markup — mangling placeholders and term ids along with the index.
 */
function initBaixadaBlocks(): void {
  const button = $<HTMLButtonElement>('#add_new_baixada')
  const group = $<HTMLElement>('#baixada_group')
  const first = $<HTMLElement>('.baixada-fields')
  if (!button || !group || !first) return

  const blueprint = first.innerHTML
  let blocks = 1

  button.addEventListener('click', (e) => {
    // The button sits inside #baixades_form and would otherwise submit it.
    e.preventDefault()
    blocks += 1
    const block = document.createElement('div')
    block.className = 'baixada-fields'
    block.innerHTML = blueprint.replace(/\[1\]/g, `[${blocks}]`)
    group.append(block)
  })
}

/** Step 3: submit every download block. */
function initAddBaixadesForm(): void {
  const form = $<HTMLFormElement>('#baixades_form')
  if (!form) return

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    hideFormError('form_3')
    const loading = $('#loading_program')
    show(loading)

    // Read each block as a unit so a block with nothing selected cannot shift
    // the following blocks' values onto the wrong download.
    const baixades: Record<number, Record<string, string>> = {}
    $$('.baixada-fields').forEach((block, index) => {
      baixades[index] = {
        url: $<HTMLInputElement>('.url_baixada', block)?.value ?? '',
        versio: $<HTMLInputElement>('.versio', block)?.value ?? '',
        sistema_operatiu: $<HTMLInputElement>('.sistema_operatiu:checked', block)?.value ?? '',
        arquitectura: $<HTMLInputElement>('.arquitectura:checked', block)?.value ?? '',
      }
    })

    const data = new FormData()
    data.append('programa_id', $<HTMLInputElement>('#programa_id')?.value ?? '')
    data.append('nom', $<HTMLInputElement>('input[name=nom]')?.value ?? '')
    data.append('baixades', JSON.stringify(baixades))
    data.append('action', 'add_new_baixada')
    data.append('_wpnonce', nonce('_wpnonce_baixada'))

    try {
      const result: StepResult = await postToAjax(data)
      hide(loading)

      if (result.status !== 1) {
        showFormError('form_3', result.text)
        return
      }

      hideFormError('form_3')
      goToStep(3)
    } catch {
      hide(loading)
      showFormError('form_3')
    }
  })
}

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

/** Exported as a seam for the tests, which drive it without a load event. */
export function initProgrames(): void {
  initDownloadButtons()
  initSearchFilters()
  initRating()
  initStepButtons()
  initSearchProgramForm()
  initAddProgramForm()
  initBaixadaBlocks()
  initAddBaixadesForm()
}

document.addEventListener('DOMContentLoaded', initProgrames)
