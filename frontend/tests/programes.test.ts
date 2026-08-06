/**
 * programes.ts — page behaviours for the `programa` post type.
 *
 * The legacy-cookie migration lives in programes-migration.test.ts, which needs
 * the document served from a program URL for cookie path scoping to apply.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import {
  MESSAGES_MODAL,
  SEARCH_FORM,
  WIZARD,
  clearCookies,
  downloadButtons,
  loadModule,
  mockFetch,
  ratingWidget,
  stubScajax,
} from './helpers'


let http: ReturnType<typeof mockFetch>

beforeEach(() => {
  stubScajax()
  localStorage.clear()
  clearCookies()
  document.body.innerHTML = ''
  http = mockFetch()
})

afterEach(() => {
  vi.unstubAllGlobals()
})

/** Let queued promise callbacks run. */
const flush = () => new Promise((resolve) => setTimeout(resolve, 0))

function setUserAgent(ua: string): void {
  Object.defineProperty(navigator, 'userAgent', { value: ua, configurable: true })
}

const UA = {
  windows: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/131.0.0.0',
  mac: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/131.0.0.0',
  linux: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/131.0.0.0',
  android: 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/131.0.0.0 Mobile',
  iphone: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
}

const visibleDownloads = (): string[] =>
  [...document.querySelectorAll<HTMLElement>('.baixada_boto')]
    .filter((a) => a.style.display !== 'none')
    .map((a) => a.id)

// ---------------------------------------------------------------------------

describe('download button', () => {
  it('reveals the exact os+architecture match when there is one', async () => {
    setUserAgent(UA.windows)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'windows_x86', 'osx_generic'])
    await loadModule()

    expect(visibleDownloads()).toEqual(['baixada_windows_x86_64'])
  })

  it('falls back to another architecture for the same os', async () => {
    setUserAgent(UA.linux)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'linux_x86'])
    await loadModule()

    expect(visibleDownloads()).toEqual(['baixada_linux_x86'])
  })

  it('falls back to the generic build for the same os', async () => {
    setUserAgent(UA.mac)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'osx_generic'])
    await loadModule()

    expect(visibleDownloads()).toEqual(['baixada_osx_generic'])
  })

  it('falls back to the first button when the os has no build at all', async () => {
    setUserAgent(UA.android)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'linux_x86'])
    await loadModule()

    // Android must not be treated as Linux even though its UA says "Linux".
    expect(visibleDownloads()).toEqual(['baixada_windows_x86_64'])
  })

  it('detects iOS despite the UA containing "Mac OS X"', async () => {
    setUserAgent(UA.iphone)
    document.body.innerHTML = downloadButtons(['osx_generic', 'ios_generic'])
    await loadModule()

    expect(visibleDownloads()).toEqual(['baixada_ios_generic'])
  })

  it('hides "altres versions" when there are two builds or fewer', async () => {
    setUserAgent(UA.windows)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'osx_generic'])
    await loadModule()

    expect(document.querySelector<HTMLElement>('#show_more_versions')!.style.display).toBe('none')
  })

  it('keeps "altres versions" when there are more than two builds', async () => {
    setUserAgent(UA.windows)
    document.body.innerHTML = downloadButtons(['windows_x86_64', 'osx_generic', 'linux_x86'])
    await loadModule()

    expect(document.querySelector<HTMLElement>('#show_more_versions')!.style.display).not.toBe('none')
  })
})

// ---------------------------------------------------------------------------

describe('search filters', () => {
  let submits: number

  beforeEach(async () => {
    document.body.innerHTML = SEARCH_FORM + WIZARD
    await loadModule()
    submits = 0
    document.querySelector('#cerca_programes')!.addEventListener('submit', (e) => {
      e.preventDefault()
      submits++
    })
  })

  const change = (selector: string, value?: string) => {
    const select = document.querySelector<HTMLSelectElement>(selector)!
    if (value !== undefined) select.value = value
    select.dispatchEvent(new Event('change', { bubbles: true }))
  }

  it('submits when a filter inside the search form changes', () => {
    change('#sistema_operatiu', 'windows')
    expect(submits).toBe(1)
  })

  it('ignores the contact form dropdown, which shares the .selectpicker class', () => {
    change('#tipus_contacte', 'error')
    expect(submits).toBe(0)
  })

  it('ignores the licence dropdown inside the add-program modal', () => {
    change('#llicencia')
    expect(submits).toBe(0)
  })

  it('disables empty fields on submit so they drop out of the query string', () => {
    change('#sistema_operatiu', 'windows')
    expect(document.querySelector<HTMLInputElement>('#cerca')!.disabled).toBe(true)
    expect(document.querySelector<HTMLSelectElement>('#sistema_operatiu')!.disabled).toBe(false)
  })

  it('disables fields whose value is the "0" placeholder', () => {
    const select = document.querySelector<HTMLSelectElement>('#sistema_operatiu')!
    select.innerHTML = '<option value="0">Cap</option>'
    select.value = '0'
    select.dispatchEvent(new Event('change', { bubbles: true }))
    expect(select.disabled).toBe(true)
  })
})

// ---------------------------------------------------------------------------

describe('rating', () => {
  const stars = () => document.querySelector<HTMLElement>('.rating-stars')!
  const star = (n: number) =>
    document.querySelector<HTMLInputElement>(`.rating-input input[value="${n}"]`)!

  const vote = async (n: number) => {
    const radio = star(n)
    radio.checked = true
    radio.dispatchEvent(new Event('change', { bubbles: true }))
    await flush()
  }

  beforeEach(async () => {
    document.body.innerHTML = ratingWidget({ votes: 11 }) + MESSAGES_MODAL
    await loadModule()
  })

  it('leaves the server-rendered average alone on load', () => {
    expect(stars().style.width).toBe('68%')
  })

  it('previews the hovered rating and restores the average on leave', () => {
    star(4).parentElement!.dispatchEvent(new MouseEvent('mouseenter'))
    expect(stars().style.width).toBe('80%')

    document.querySelector('#input_rating')!.dispatchEvent(new MouseEvent('mouseleave'))
    expect(stars().style.width).toBe('68%')
  })

  it('previews on keyboard focus too', () => {
    star(2).dispatchEvent(new Event('focus'))
    expect(stars().style.width).toBe('40%')

    star(2).dispatchEvent(new Event('blur'))
    expect(stars().style.width).toBe('68%')
  })

  it('posts the vote with the program id, rating and nonce', async () => {
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    await vote(4)

    expect(http.requests).toHaveLength(1)
    expect(http.requests[0].fields).toMatchObject({
      action: 'send_vote',
      post_id: '42',
      rate: '4',
      cookie_id: 'sc_programa_gimp_42',
      _wpnonce: 'testnonce',
    })
  })

  it('reflects the new average immediately', async () => {
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    await vote(4)

    expect(stars().style.width).toBe('71%')
    expect(document.querySelector('.cont-rating span.numero')!.textContent).toBe('3,55')
    expect(document.querySelector('.cont-rating em')!.textContent).toBe('(12 vots)')
  })

  it('shows the server message in the modal', async () => {
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    await vote(4)

    expect(document.querySelector('#message_text')!.textContent).toBe('Gràcies!')
    expect(document.querySelector('.bs-messages-modal-lg')!.classList.contains('in')).toBe(true)
  })

  it('records the vote in localStorage as an epoch timestamp, not a cookie', async () => {
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    await vote(4)

    expect(localStorage.getItem('sc_programa_gimp_42')).toMatch(/^\d{10}$/)
    expect(document.cookie).not.toContain('sc_programa_gimp_42')
  })

  it('records under the key it sent, not the one the server echoes back', async () => {
    // The server sanitises cookie_id; a rewritten key would never match again.
    http.respondWith({
      status: 1,
      text: 'Gràcies!',
      vots: 12,
      valoracio: '3,55',
      cookie_id: 'mangled_by_sanitiser',
    })
    await vote(4)

    expect(localStorage.getItem('sc_programa_gimp_42')).not.toBeNull()
    expect(localStorage.getItem('mangled_by_sanitiser')).toBeNull()
  })

  it('refuses a second vote without hitting the server', async () => {
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    await vote(4)
    await vote(2)

    expect(http.requests).toHaveLength(1)
    expect(document.querySelector('#message_text')!.textContent).toContain('ja havies votat')
    expect(stars().style.width).toBe('71%')
  })

  it('creates the vote counter for a program voted on for the first time', async () => {
    document.body.innerHTML = ratingWidget() + MESSAGES_MODAL // no <em> rendered
    await loadModule()
    http.respondWith({ status: 1, text: 'Gràcies!', vots: 1, valoracio: '4,00' })
    await vote(4)

    expect(document.querySelector('.cont-rating em')!.textContent).toBe('(1 vots)')
  })

  it('restores the average and reports a rejected vote', async () => {
    http.respondWith({ status: 0, text: 'No s\'ha pogut enviar el vot.' })
    await vote(5)

    expect(stars().style.width).toBe('68%')
    expect(document.querySelector('#message_text')!.textContent).toBe("No s'ha pogut enviar el vot.")
    expect(localStorage.getItem('sc_programa_gimp_42')).toBeNull()
  })

  it('restores the average and reports a network failure', async () => {
    http.respondWith('network-error')
    await vote(5)

    expect(stars().style.width).toBe('68%')
    expect(document.querySelector('#message_text')!.textContent).toContain("S'ha produït un error")
    expect(localStorage.getItem('sc_programa_gimp_42')).toBeNull()
  })

  it('does nothing on a page with no rating widget', async () => {
    document.body.innerHTML = MESSAGES_MODAL
    await expect(loadModule()).resolves.toBeUndefined()
  })
})

// ---------------------------------------------------------------------------

describe('add-program wizard', () => {
  const el = (id: string) => document.getElementById(id)!
  const visible = (id: string) => el(id).style.display !== 'none'
  const submit = async (id: string) => {
    el(id).dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flush()
  }

  beforeEach(async () => {
    document.body.innerHTML = WIZARD + MESSAGES_MODAL
    await loadModule()
  })

  it('searches for an existing program and renders the returned list', async () => {
    http.respondWith({ text: 'Ja tenim:', programs: '<ul><li>GIMP</li></ul>' })
    await submit('second_step')

    expect(http.requests[0].fields).toMatchObject({
      action: 'search_program',
      nom_programa: 'GIMP',
      _wpnonce: 'n2',
    })
    expect(el('text_response').innerHTML).toBe('Ja tenim:<ul><li>GIMP</li></ul>')
    expect(visible('pas_1')).toBe(true)
    expect(el('loading').style.display).toBe('none')
  })

  // Without it, sc_check_is_ajax_call() answers 403 and nothing reaches the endpoint.
  it('marks the request as an AJAX call', async () => {
    await submit('second_step')

    expect(http.requests[0].headers['X-Requested-With']).toBe('XMLHttpRequest')
  })

  it('reports a failed search without breaking the step', async () => {
    http.respondWith('network-error')
    await submit('second_step')

    expect(el('text_response').textContent).toBe('Proveu més tard')
    expect(el('loading').style.display).toBe('none')
  })

  it('advances from step 1 to step 2', () => {
    el('pas_1').dispatchEvent(new MouseEvent('click', { bubbles: true }))

    expect(visible('form_1')).toBe(false)
    expect(visible('form_2')).toBe(true)
  })

  it('sends the program details and advances to the downloads step', async () => {
    http.respondWith({ status: 1, post_id: 987 })
    await submit('programa_form')

    expect(http.requests[0].fields).toMatchObject({
      action: 'add_new_program',
      nom: 'GIMP',
      email_usuari: 'a@b.cat',
      lloc_web_programa: 'https://gimp.org',
      llicencia: '11',
      categoria_programa: '7',
      _wpnonce: 'n3',
    })
    expect(visible('form_3')).toBe(true)
    expect(el('form_3').classList.contains('actiu')).toBe(true)
    expect(document.querySelector<HTMLInputElement>('#programa_id')!.value).toBe('987')
  })

  it('omits file fields when no file was chosen', async () => {
    http.respondWith({ status: 1, post_id: 987 })
    await submit('programa_form')

    expect(http.requests[0].fields).not.toHaveProperty('logo')
    expect(http.requests[0].fields).not.toHaveProperty('captura')
  })

  it('sends a chosen file', async () => {
    const input = document.querySelector<HTMLInputElement>('input[name="logo"]')!
    // jsdom has no DataTransfer, so stand in a FileList-alike.
    Object.defineProperty(input, 'files', {
      value: [new File(['x'], 'logo.png', { type: 'image/png' })],
      configurable: true,
    })

    http.respondWith({ status: 1, post_id: 987 })
    await submit('programa_form')

    expect(http.requests[0].fields.logo).toBe('FILE:logo.png')
  })

  it('stays on step 2 and shows the reason when the server rejects the program', async () => {
    el('pas_1').dispatchEvent(new MouseEvent('click', { bubbles: true })) // reach step 2
    http.respondWith({ status: 0, text: 'Ja existeix' })
    await submit('programa_form')

    expect(visible('form_2')).toBe(true)
    expect(visible('form_3')).toBe(false)
    expect(el('form_2').querySelector('.form-error')!.classList.contains('visible')).toBe(true)
    expect(el('form_2').querySelector('.form-error-text')!.textContent).toBe('Ja existeix')
  })

  it('shows a generic error when the program submission fails outright', async () => {
    http.respondWith('network-error')
    await submit('programa_form')

    expect(el('form_2').querySelector('.form-error-text')!.textContent).toContain(
      "S'ha produït un error"
    )
    expect(el('loading_program').style.display).toBe('none')
  })

  it('restarts the wizard when reopened before the downloads step', () => {
    el('pas_1').dispatchEvent(new MouseEvent('click', { bubbles: true }))
    el('afegeix_programa_button').dispatchEvent(new MouseEvent('click', { bubbles: true }))

    expect(visible('form_1')).toBe(true)
    expect(visible('form_2')).toBe(false)
  })

  it('does not restart the wizard once the downloads step is reached', async () => {
    el('pas_1').dispatchEvent(new MouseEvent('click', { bubbles: true })) // reach step 2
    http.respondWith({ status: 1, post_id: 987 })
    await submit('programa_form')
    el('afegeix_programa_button').dispatchEvent(new MouseEvent('click', { bubbles: true }))

    expect(visible('form_3')).toBe(true)
    expect(visible('form_1')).toBe(false)
  })
})

// ---------------------------------------------------------------------------

describe('download blocks', () => {
  const el = (id: string) => document.getElementById(id)!
  const blocks = () => document.querySelectorAll<HTMLElement>('.baixada-fields')
  const addBlock = () =>
    el('add_new_baixada').dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }))

  beforeEach(async () => {
    document.body.innerHTML = WIZARD + MESSAGES_MODAL
    await loadModule()
  })

  it('appends a new block', () => {
    addBlock()
    expect(blocks()).toHaveLength(2)
  })

  it('does not submit the form it sits inside', () => {
    let submits = 0
    el('baixades_form').addEventListener('submit', (e) => {
      e.preventDefault()
      submits++
    })
    addBlock()
    expect(submits).toBe(0)
  })

  it('re-indexes the radio groups so blocks are independent', () => {
    addBlock()
    const [first, second] = [...blocks()]

    expect(second.querySelector<HTMLInputElement>('.arquitectura')!.name).toBe('arquitectura[2]')
    expect(first.querySelector<HTMLInputElement>('.arquitectura')!.name).not.toBe(
      second.querySelector<HTMLInputElement>('.arquitectura')!.name
    )
  })

  it('rewrites only the [1] index, not every "1" in the markup', () => {
    // The old code built its RegExp from "[1]", a character class matching
    // every "1" — which mangled placeholders and term ids.
    addBlock()
    const second = [...blocks()][1]

    expect(second.querySelector<HTMLInputElement>('.versio')!.placeholder).toBe('5.1.1')
    expect(second.querySelector<HTMLInputElement>('.sistema_operatiu')!.value).toBe('31')
  })

  it('does not duplicate the template id', () => {
    addBlock()
    addBlock()
    expect(document.querySelectorAll('#baixada_fields')).toHaveLength(1)
    expect(blocks()).toHaveLength(3)
  })

  it('numbers each added block in sequence', () => {
    addBlock()
    addBlock()
    const names = [...blocks()].map((b) => b.querySelector<HTMLInputElement>('.arquitectura')!.name)

    expect(names).toEqual(['arquitectura[1]', 'arquitectura[2]', 'arquitectura[3]'])
  })

  it('keeps each block\'s values together when submitting', async () => {
    addBlock()
    const second = [...blocks()][1]
    second.querySelector<HTMLInputElement>('.url_baixada')!.value = 'https://b/2.dmg'
    second.querySelector<HTMLInputElement>('.versio')!.value = '2.0'
    second.querySelector<HTMLInputElement>('.arquitectura[value="generic"]')!.checked = true

    http.respondWith({ status: 1 })
    el('baixades_form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flush()

    const sent = JSON.parse(http.requests[0].fields.baixades)
    expect(sent['0']).toEqual({
      url: 'https://a/1.exe',
      versio: '1.0',
      sistema_operatiu: '',
      arquitectura: 'x86_64',
    })
    expect(sent['1']).toEqual({
      url: 'https://b/2.dmg',
      versio: '2.0',
      sistema_operatiu: '',
      arquitectura: 'generic',
    })
  })

  it('advances to the thank-you step on success', async () => {
    http.respondWith({ status: 1 })
    el('baixades_form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flush()

    expect(el('form_3').style.display).toBe('none')
    expect(el('form_4').style.display).not.toBe('none')
  })

  it('stays on the downloads step when the server rejects it', async () => {
    http.respondWith({ status: 0, text: 'Falta la URL' })
    el('baixades_form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flush()

    expect(el('form_4').style.display).toBe('none')
    expect(el('form_3').querySelector('.form-error-text')!.textContent).toBe('Falta la URL')
    expect(el('loading_program').style.display).toBe('none')
  })
})
