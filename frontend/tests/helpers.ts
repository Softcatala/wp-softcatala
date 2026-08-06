/**
 * Shared fixtures for the programes tests.
 *
 * The markup mirrors the Twig templates the module actually runs against —
 * capcalera_programa.twig, archive-programa.twig, messages.twig,
 * programa-afegeix.twig and baixades_form.twig. Keep them in step.
 */

import { vi } from 'vitest'

declare global {
  var scajax: { ajax_url: string }
}

/** The global wp_localize_script injects on every page that loads the module. */
export function stubScajax(): void {
  globalThis.scajax = { ajax_url: '/wp-admin/admin-ajax.php' }
}

export interface CapturedRequest {
  url: string
  fields: Record<string, string>
  headers: Record<string, string>
}

/**
 * Install a fetch mock. Queue a response object, or the literal
 * 'network-error' to make the call reject.
 */
export function mockFetch(): {
  requests: CapturedRequest[]
  respondWith: (response: unknown | 'network-error') => void
} {
  const requests: CapturedRequest[] = []
  let next: unknown = {}

  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, opts: { body?: FormData; headers?: Record<string, string> }) => {
      const fields: Record<string, string> = {}
      opts?.body?.forEach((value, key) => {
        fields[key] = value instanceof File ? `FILE:${value.name}` : String(value)
      })
      requests.push({ url: String(url), fields, headers: opts?.headers ?? {} })

      if (next === 'network-error') throw new Error('network down')
      return {
        ok: true,
        status: 200,
        json: async () => next,
      } as Response
    })
  )

  return { requests, respondWith: (response) => { next = response } }
}

/**
 * Load the module fresh (resetting its cached storage handle) and initialise
 * it against the current DOM.
 *
 * Calls the exported entry point rather than dispatching DOMContentLoaded:
 * every re-import registers another listener on the document, which persists
 * across tests in a file, so dispatching would re-run every earlier test's
 * handlers as well.
 */
export async function loadModule(): Promise<void> {
  vi.resetModules()
  const module = await import('../src/js/programes')
  module.initProgrames()
}

/** Expire every cookie visible at the current path. */
export function clearCookies(): void {
  for (const cookie of document.cookie.split('; ')) {
    const name = cookie.split('=')[0]
    if (name) document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`
  }
}

/** capcalera_programa.twig — download buttons for the given `os_arch` ids. */
export function downloadButtons(ids: string[]): string {
  return (
    ids
      .map(
        (id) =>
          `<a id="baixada_${id}" class="bt-download-invers bt-download-hide baixada_boto" style="display:none;">${id}</a>`
      )
      .join('') +
    '<a class="bt-versions" id="show_more_versions" role="button" href="#versions" data-toggle="collapse">Altres versions</a>'
  )
}

/** capcalera_programa.twig — rating widget. `votes` omitted mirrors a program with no votes yet. */
export function ratingWidget({
  postId = 42,
  key = 'programa_gimp_42',
  average = 3.4,
  width = '68%',
  votes,
}: { postId?: number; key?: string; average?: number; width?: string; votes?: number } = {}): string {
  const stars = [1, 2, 3, 4, 5]
    .map(
      (n) =>
        `<label class="rating-input__star"><input type="radio" name="valoracio_${postId}" value="${n}"><span class="sr-only">${n} estrelles</span></label>`
    )
    .join('')

  return `
    <div class="cont-rating">
      <input type="hidden" name="_wpnonce_program_vote" value="testnonce">
      <input type="hidden" name="${key}" id="input_rating_value" value="${average}"/>
      <div class="star-rating">
        <div class="rating-container rating-fa" data-content="stars">
          <div class="rating-stars" data-content="stars" style="width: ${width};"></div>
          <fieldset class="rating-input" id="input_rating" data-post-id="${postId}">
            <legend class="sr-only">Valoreu GIMP</legend>${stars}
          </fieldset>
        </div>
      </div>
      <span class="numero">${String(average).replace('.', ',')}</span>
      ${votes === undefined ? '' : `<em>(${votes} vots)</em>`}
    </div>`
}

/** messages.twig */
export const MESSAGES_MODAL = `
  <div class="modal fade bs-messages-modal-lg" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg"><div class="modal-content">
      <button type="button" class="close" data-dismiss="modal">x</button>
      <h1 id="message_text"></h1>
    </div></div>
  </div>`

/** archive-programa.twig search form, plus the contact-form dropdown that shares its class. */
export const SEARCH_FORM = `
  <form class="searchform" method="get" id="cerca_programes" role="search" action="/programes">
    <input type="text" value="" id="cerca" name="cerca" />
    <select class="form-control selectpicker" id="sistema_operatiu" name="sistema_operatiu">
      <option value="">Tots els sistemes operatius</option>
      <option value="windows">Windows</option>
    </select>
  </form>
  <form id="contact_form">
    <select class="form-control selectpicker" name="tipus" id="tipus_contacte">
      <option value="">Trieu</option><option value="error">Error</option>
    </select>
  </form>`

/** programa-afegeix.twig + baixades_form.twig — the four-step wizard. */
export const WIZARD = `
  <div class="modal fade bs-afegeixprograma-modal-lg" tabindex="-1" role="dialog">
   <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="formulari" id="form_1">
      <form id="second_step">
        <input type="text" id="nom_programa" value="GIMP">
        <input type="hidden" name="_wpnonce_program_search" value="n2">
        <button id="cerca_programa" type="submit">Cerca <i id="loading" style="display:none;">…</i></button>
      </form>
      <p id="text_response"></p>
      <button id="pas_1" type="submit" class="next_step" style="display: none;">Ves al formulari</button>
    </div>

    <div class="formulari" id="form_2" style="display: none;">
      <form class="form-horizontal" id="programa_form" method="post">
        <input type="email" name="email_usuari" value="a@b.cat">
        <textarea name="comentari_usuari"></textarea>
        <input type="text" name="nom" value="GIMP">
        <input type="url" name="lloc_web" value="https://gimp.org">
        <textarea name="descripcio">Editor</textarea>
        <input type="radio" name="categoria_programa" value="7" checked>
        <select class="form-control selectpicker" name="llicencia" id="llicencia"><option value="11">GPL</option></select>
        <input type="text" name="autor" value="">
        <input type="text" name="autor_traduccio" value="">
        <input type="file" name="logo">
        <input type="file" name="captura">
        <div class="alert form-error"><p class="form-error-text"></p></div>
        <input type="hidden" name="_wpnonce_program" value="n3">
        <button type="submit">Envia</button>
      </form>
    </div>

    <div class="formulari" id="form_3" style="display: none;">
      <form id="baixades_form" class="form-horizontal" method="post">
        <div id="baixada_group">
          <div id="baixada_fields" class="baixada-fields">
            <input type="url" name="url_baixada[1]" class="url_baixada" placeholder="http://x/file.exe" value="https://a/1.exe">
            <input type="text" name="versio[1]" class="versio" placeholder="5.1.1" value="1.0">
            <label><input class="sistema_operatiu" type="radio" name="sistema_operatiu[1] required" value="31"> Windows</label>
            <label><input class="arquitectura" type="radio" name="arquitectura[1]" value="generic"> N/A</label>
            <label><input class="arquitectura" type="radio" name="arquitectura[1]" value="x86_64" checked> 64 bits</label>
          </div>
        </div>
        <button type="button" id="add_new_baixada">Afegeix una altra baixada</button>
        <div class="alert form-error"><p class="form-error-text"></p></div>
        <input type="hidden" id="programa_id" name="programa_id" value="" />
        <input type="hidden" name="_wpnonce_baixada" value="n4">
        <button type="submit">Envia les dades <i id="loading_program" style="display:none;">…</i></button>
      </form>
    </div>

    <div class="formulari" id="form_4" style="display: none;">Gràcies!</div>
   </div></div>
  </div>
  <a href="#" id="afegeix_programa_button" data-toggle="modal" data-target=".bs-afegeixprograma-modal-lg">Afegiu un programa</a>`
