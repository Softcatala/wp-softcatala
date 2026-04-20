/**
 * Conjugador de Softcatalà
 *
 * Vanilla TypeScript port of static/js/conjugador/conjugador.js.
 * jQuery is still used for the typeahead plugin (jQuery-only plugin).
 *
 * Globals injected by wp_localize_script in conjugador.php:
 *   scajax.ajax_url        — WordPress AJAX endpoint
 *   scajax.autocomplete_url — Conjugador autocomplete API base URL
 */

// ---------------------------------------------------------------------------
// Ambient declarations for globals provided by WordPress / jQuery plugins
// ---------------------------------------------------------------------------

import { initTabs } from './tabs'

declare const scajax: { ajax_url: string; autocomplete_url: string }
declare const jQuery: any

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface AjaxSuccess {
  html: string
  canonical: string
  content_title: string
  title: string
}

interface AjaxError {
  responseJSON: {
    html: string
    canonical: string
    content_title: string
    title: string
    status: string
    description: string
  }
}

// ---------------------------------------------------------------------------
// DOM helpers
// ---------------------------------------------------------------------------

function el<T extends HTMLElement>(id: string): T | null {
  return document.getElementById(id) as T | null
}

function show(element: HTMLElement | null): void {
  if (element) element.style.display = ''
}

function hide(element: HTMLElement | null): void {
  if (element) element.style.display = 'none'
}

// ---------------------------------------------------------------------------
// Analytics
// ---------------------------------------------------------------------------

function sendTracking(success: boolean, status = '', verb = ''): void {
  if (typeof (window as any).ga !== 'function') return
  const url = (success ? '' : status) + document.location.pathname + (success ? '' : verb)
  ;(window as any).ga('send', 'pageview', url)
}

// ---------------------------------------------------------------------------
// Share links
// ---------------------------------------------------------------------------

function updateShareLinks(query: string): void {
  const url = window.location.href
  el<HTMLAnchorElement>('share_facebook')?.setAttribute(
    'href',
    `https://www.facebook.com/sharer/sharer.php?u=${url}`
  )
  el<HTMLAnchorElement>('share_twitter')?.setAttribute(
    'href',
    `https://twitter.com/intent/tweet?text=Conjugació del verb ${query} al conjugador de Softcatalà ${url}`
  )
}

// ---------------------------------------------------------------------------
// Result injection
// ---------------------------------------------------------------------------

function injectResults(html: string): void {
  const container = el('resultats-conjugador')
  if (!container) return
  container.innerHTML = html
  show(container)
  container.style.removeProperty('display') // slideDown equivalent
  // Re-initialise tabs for the freshly injected markup
  if (typeof initTabs === 'function') {
    initTabs()
  }
}

// ---------------------------------------------------------------------------
// AJAX callbacks
// ---------------------------------------------------------------------------

function onSuccess(result: AjaxSuccess): void {
  history.pushState(null, '', result.canonical)
  updateShareLinks(result.canonical)
  sendTracking(true)

  const source = el<HTMLInputElement>('source')
  if (source) {
    source.value = ''
    jQuery('#source').typeahead('val', '')
  }
  el('infinitiu')?.focus()
  hide(el('loading'))

  const headerTitle = el('content_header_title')
  if (headerTitle) headerTitle.innerHTML = result.content_title
  document.title = result.title

  injectResults(result.html)
}

function onError(result: AjaxError): void {
  const r = result.responseJSON
  history.pushState(null, '', r.canonical)
  sendTracking(false, r.status, r.description)

  el<HTMLInputElement>('source')?.focus()
  hide(el('loading'))

  const headerTitle = el('content_header_title')
  if (headerTitle) headerTitle.innerHTML = r.content_title
  document.title = r.title

  injectResults(r.html)
}

// ---------------------------------------------------------------------------
// AJAX search
// ---------------------------------------------------------------------------

function doAjax(): void {
  const verbForm = (el<HTMLInputElement>('source')?.value ?? '').trim()
  const infinitiu = (el<HTMLInputElement>('infinitiu')?.value ?? '').toLowerCase()
  const verburl = (el<HTMLInputElement>('verburl')?.value ?? '').toLowerCase()

  if (!verbForm) {
    const container = el('resultats-conjugador')
    if (container) container.innerHTML = 'Introduïu un verb per conjugar'
    return
  }

  show(el('loading'))

  const postData = new FormData()
  postData.append('verb', verbForm.toLowerCase())
  postData.append('ajaxquery', 'true')
  postData.append('infinitiu', infinitiu)
  postData.append('url', verburl)
  postData.append('action', 'conjugador_search')

  const nonce = document.querySelector<HTMLInputElement>('input[name=_wpnonce_search]')?.value ?? ''
  postData.append('_wpnonce', nonce)

  jQuery.ajax({
    url: scajax.ajax_url,
    type: 'POST',
    data: postData,
    dataType: 'json',
    contentType: false,
    processData: false,
    success: onSuccess,
    error: onError,
  })
}

// ---------------------------------------------------------------------------
// Initialisation
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  // ── Form submit ──────────────────────────────────────────────────────────
  document.getElementById('conjugador_form')?.addEventListener('submit', (e) => {
    e.preventDefault()
    const infinitiu = el<HTMLInputElement>('infinitiu')
    const verburl = el<HTMLInputElement>('verburl')
    if (infinitiu) infinitiu.value = ''
    if (verburl) verburl.value = ''
    doAjax()
  })

  // ── Search link click ────────────────────────────────────────────────────
  document.getElementById('_action_consulta')?.addEventListener('click', (e) => {
    e.preventDefault()
    const infinitiu = el<HTMLInputElement>('infinitiu')
    const verburl = el<HTMLInputElement>('verburl')
    if (infinitiu) infinitiu.value = ''
    if (verburl) verburl.value = ''
    doAjax()
  })

  // ── Typeahead (jQuery plugin — keep jQuery here) ─────────────────────────
  const items: string[] = []
  const infinitives: Record<string, string> = {}
  const formVerb: Record<string, string> = {}
  const urlMap: Record<string, string> = {}

  jQuery('#source').typeahead(
    { minLength: 3, hint: false },
    {
      delay: 3600,
      limit: 200,
      async: true,
      source(query: string, _: unknown, processAsync: (items: string[]) => void) {
        jQuery.ajax({
          url: scajax.autocomplete_url + query,
          dataType: 'json',
          success(data: Array<{ verb_form: string; infinitive: string; url: string }>) {
            items.length = 0
            for (const verb of data) {
              const str = `${verb.verb_form} (${verb.infinitive})`
              infinitives[str] = verb.infinitive
              formVerb[str] = verb.verb_form
              urlMap[str] = verb.url
              items.push(str)
            }
            processAsync(items)
          },
          error(textStatus: unknown, status: unknown, errorThrown: unknown) {
            console.error(textStatus, status, errorThrown)
          },
        })
      },
    }
  ).on('typeahead:selected', (_evt: unknown, item: string) => {
    const regExp = /\(([^)]+)\)/
    const matches = regExp.exec(item)

    if (!infinitives[item] && matches) infinitives[item] = matches[1]
    if (!formVerb[item] && matches) formVerb[item] = matches[1]
    if (!urlMap[item] && matches) urlMap[item] = matches[1]

    const infinitiu = el<HTMLInputElement>('infinitiu')
    const verburl = el<HTMLInputElement>('verburl')
    const source = el<HTMLInputElement>('source')

    if (infinitiu) infinitiu.value = infinitives[item] ?? ''
    if (verburl) verburl.value = urlMap[item] ?? ''
    if (source) {
      source.value = formVerb[item] ?? ''
      jQuery('#source').typeahead('val', formVerb[item] ?? '')
    }

    doAjax()
  })
})
