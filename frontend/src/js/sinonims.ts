/**
 * Diccionari de sinònims
 *
 * Vanilla TypeScript port of static/js/sinonims.js.
 *
 * Globals injected by wp_localize_script in sinonims.php:
 *   scajax.ajax_url — WordPress AJAX endpoint
 */

import { focusSearchInput, sendTracking, updateShareLinks } from './utils'

declare const scajax: { ajax_url: string }

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface SearchResult {
  html: string
}

interface ErrorResult {
  responseJSON: {
    html: string
    status: string
  }
}

// ---------------------------------------------------------------------------
// Results
// ---------------------------------------------------------------------------

function showResults(html: string): void {
  const results = document.getElementById('results')
  if (!results) return
  results.innerHTML = html
  results.style.removeProperty('display')
}

// ---------------------------------------------------------------------------
// AJAX callbacks
// ---------------------------------------------------------------------------

function onSuccess(result: SearchResult): void {
  const loading = document.getElementById('loading')
  if (loading) loading.style.display = 'none'
  showResults(result.html)
  sendTracking(true)
}

function onError(response: ErrorResult): void {
  const status = response.responseJSON.status !== '0' ? response.responseJSON.status : '500'
  sendTracking(false, status)
  showResults(response.responseJSON.html)
  const loading = document.getElementById('loading')
  if (loading) loading.style.display = 'none'
}

// ---------------------------------------------------------------------------
// Search
// ---------------------------------------------------------------------------

function doSearch(): void {
  const input = document.getElementById('sinonims') as HTMLInputElement | null
  const query = (input?.value ?? '').trim().replace("'", '’')

  const loading = document.getElementById('loading')

  if (!query) {
    if (loading) loading.style.display = 'none'
    focusSearchInput('#sinonims')
    return
  }

  if (loading) loading.style.display = ''

  history.pushState(null, '', `/diccionari-de-sinonims/paraula/${query}/`)

  const headerTitle = document.getElementById('content_header_title')
  if (headerTitle) headerTitle.innerHTML = `Diccionari de sinònims: «${query}»`

  updateShareLinks(`Sinònims de la paraula ${query} al diccionari de sinònims de Softcatalà`)

  const nonce = document.querySelector<HTMLInputElement>('input[name=_wpnonce_sinonim]')?.value ?? ''

  const postData = new FormData()
  postData.append('paraula', query)
  postData.append('action', 'find_sinonim')
  postData.append('_wpnonce', nonce)

  fetch(scajax.ajax_url, { method: 'POST', body: postData })
    .then((res) => (res.ok ? res.json() : Promise.reject(res)))
    .then(onSuccess)
    .catch(async (res) => {
      const json = await res.json().catch(() => ({}))
      onError({ responseJSON: json })
    })
}

// ---------------------------------------------------------------------------
// Inline result links (click a synonym word to search it directly)
// ---------------------------------------------------------------------------

function initInlineLinks(): void {
  document.getElementById('results')?.addEventListener('click', (e) => {
    const anchor = (e.target as HTMLElement).closest<HTMLAnchorElement>('a[data-sinonim]')
    if (!anchor) return
    e.preventDefault()
    const sinonim = anchor.dataset.sinonim
    if (!sinonim) return
    const input = document.getElementById('sinonims') as HTMLInputElement | null
    if (input) input.value = sinonim
    doSearch()
  })
}

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('sinonims_form')?.addEventListener('submit', (e) => {
    e.preventDefault()
    doSearch()
  })

  document.getElementById('_action_consulta_sinonims')?.addEventListener('click', (e) => {
    e.preventDefault()
    doSearch()
  })

  initInlineLinks()
  focusSearchInput('#sinonims')
})
