/**
 * Diccionari anglès-català
 *
 * Vanilla TypeScript port of static/js/diccionari-engcat/diccionari-engcat.js.
 *
 * Globals injected by wp_localize_script in diccionari-engcat.php:
 *   scajax.ajax_url — WordPress AJAX endpoint
 */

import { focusSearchInput, sendTracking, updateShareLinks } from './utils'

declare const scajax: { ajax_url: string }

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface SearchResult {
  html: string
  canonical: string
  content_title: string
}

interface ErrorResult {
  responseJSON: {
    html: string
    canonical: string
    content_title: string
    status: string
    description: string
  }
}

type Language = 'cat' | 'eng'

// ---------------------------------------------------------------------------
// Input helpers
// ---------------------------------------------------------------------------

function prepareInputSearchQuery(): void {
  focusSearchInput('#cerca_diccionari_engcat')
}

function getQuery(): string {
  const input = document.getElementById('cerca_diccionari_engcat') as HTMLInputElement | null
  return (input?.value ?? '').trim().replace("'", '’')
}

function getLanguage(): string {
  const select = document.getElementById('llengua_diccionari_engcat') as HTMLSelectElement | null
  return select?.value ?? ''
}

// ---------------------------------------------------------------------------
// AJAX callbacks
// ---------------------------------------------------------------------------

function onSuccess(result: SearchResult): void {
  history.pushState(null, '', result.canonical)
  updateShareLinks(`Diccionari anglès-català: ${result.canonical}`)
  sendTracking(true)

  const loading = document.getElementById('loading')
  if (loading) loading.style.display = 'none'

  const headerTitle = document.getElementById('content_header_title')
  if (headerTitle) headerTitle.innerHTML = result.content_title

  document.title = result.content_title

  const results = document.getElementById('results')
  if (results) {
    results.innerHTML = result.html
    results.style.removeProperty('display')
  }

  // Sync language selector to the language returned in the canonical URL
  let lang: Language = 'cat'
  const m = result.canonical.match(/\/(cat|eng)\//)
  if (m?.[1] === 'eng' || m?.[1] === 'cat') {
    lang = m[1] as Language
  } else {
    const t = result.content_title.toLowerCase()
    if (t.includes('anglès-català') || t.includes('angles-català')) lang = 'eng'
  }

  const select = document.getElementById('llengua_diccionari_engcat') as HTMLSelectElement | null
  if (select && select.value !== lang) {
    select.value = lang
    select.dispatchEvent(new Event('change'))
  }
}

function onError(result: ErrorResult): void {
  const r = result.responseJSON
  history.pushState(null, '', r.canonical)
  sendTracking(false, r.status, r.description)

  const loading = document.getElementById('loading')
  if (loading) loading.style.display = 'none'

  const headerTitle = document.getElementById('content_header_title')
  if (headerTitle) headerTitle.innerHTML = 'Diccionari anglès-català'

  document.title = r.content_title

  const results = document.getElementById('results')
  if (results) {
    results.innerHTML = r.html
    results.style.removeProperty('display')
  }
}

// ---------------------------------------------------------------------------
// AJAX search
// ---------------------------------------------------------------------------

function doSearch(): void {
  const query = getQuery()
  const llengua = getLanguage()

  const loading = document.getElementById('loading')
  if (loading) loading.style.display = ''

  if (!query || (llengua !== 'cat' && llengua !== 'eng')) {
    if (loading) loading.style.display = 'none'
    prepareInputSearchQuery()
    return
  }

  const postData = new FormData()
  postData.append('paraula', query)
  postData.append('llengua', llengua)
  postData.append('action', 'diccionari_engcat_search')

  fetch(scajax.ajax_url, { method: 'POST', body: postData })
    .then((res) => (res.ok ? res.json() : Promise.reject(res)))
    .then(onSuccess)
    .catch(async (res) => {
      const json = await res.json().catch(() => ({}))
      onError({ responseJSON: json })
    })
}

// ---------------------------------------------------------------------------
// Corpus toggle
// ---------------------------------------------------------------------------

function initCorpusToggle(): void {
  document.addEventListener('click', (e) => {
    const target = (e.target as HTMLElement).closest('.mostra_corpus')
    if (!target) return
    e.preventDefault()
    const table = target.closest('table')
    if (!table) return
    const corpusRows = table.querySelectorAll('tr.corpus_hidden, tr.corpus_hidden_visible')
    if (!corpusRows.length) return
    const expanding = Array.from(corpusRows).some((row) => row.classList.contains('corpus_hidden'))
    corpusRows.forEach((row) => {
      row.classList.toggle('corpus_hidden', !expanding)
      row.classList.toggle('corpus_hidden_visible', expanding)
    })
    target.textContent = expanding ? 'Mostra menys exemples' : 'Mostra més exemples'
  })
}

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('diccionari_engcat_form')?.addEventListener('submit', (e) => {
    e.preventDefault()
    document.getElementById('_action_consulta_diccionari_engcat')?.dispatchEvent(new MouseEvent('click'))
  })

  document.getElementById('toggle_llengua_btn')?.addEventListener('click', () => {
    const select = document.getElementById('llengua_diccionari_engcat') as HTMLSelectElement | null
    if (!select) return
    select.value = select.value === 'cat' ? 'eng' : 'cat'
    select.dispatchEvent(new Event('change'))
  })

  document.getElementById('llengua_diccionari_engcat')?.addEventListener('change', () => {
    prepareInputSearchQuery()
  })

  document.getElementById('_action_consulta_diccionari_engcat')?.addEventListener('click', (e) => {
    e.preventDefault()
    doSearch()
  })

  initCorpusToggle()
  prepareInputSearchQuery()
})
