/**
 * Migration of pre-localStorage votes.
 *
 * @vitest-environment jsdom
 * @vitest-environment-options { "url": "https://www.softcatala.org/programes/gimp/" }
 *
 * The document URL matters: legacy vote cookies were written without an
 * explicit path, so they default to the directory of the page that cast the
 * vote. Served from anywhere else they are invisible, which is the whole
 * reason the migration can only ever run on the program's own page.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import {
  MESSAGES_MODAL,
  clearCookies,
  loadModule,
  mockFetch,
  ratingWidget,
  stubScajax,
} from './helpers'


const KEY = 'sc_programa_gimp_42'

let http: ReturnType<typeof mockFetch>

beforeEach(() => {
  stubScajax()
  localStorage.clear()
  clearCookies()
  document.cookie = `${KEY}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/programes/gimp`
  document.body.innerHTML = ratingWidget({ votes: 11 }) + MESSAGES_MODAL
  http = mockFetch()
})

afterEach(() => {
  vi.unstubAllGlobals()
})

/** Write the cookie exactly as the pre-localStorage code did: no explicit path. */
function setLegacyCookie(): void {
  document.cookie = `${KEY}=1`
}

describe('cookie path scoping', () => {
  it('is enforced, so the fixture reflects real behaviour', () => {
    // Guards the premise of every test below: a cookie scoped to a different
    // program must not be visible from this page.
    document.cookie = 'sc_programa_vlc_99=1; path=/programes/vlc'
    expect(document.cookie).not.toContain('sc_programa_vlc_99')

    setLegacyCookie()
    expect(document.cookie).toContain(KEY)
  })
})

describe('legacy vote migration', () => {
  it('moves the cookie into localStorage on load', async () => {
    setLegacyCookie()
    await loadModule()

    expect(localStorage.getItem(KEY)).toMatch(/^\d{10}$/)
  })

  it('deletes the cookie so it stops riding on every request', async () => {
    setLegacyCookie()
    await loadModule()

    expect(document.cookie).not.toContain(KEY)
  })

  it('runs on load, without waiting for a vote attempt', async () => {
    // Someone who already voted never clicks again — which is exactly who
    // holds a cookie — so a migration deferred to the vote check would never
    // fire for them.
    setLegacyCookie()
    await loadModule()

    expect(http.requests).toHaveLength(0)
    expect(localStorage.getItem(KEY)).not.toBeNull()
  })

  it('still blocks a re-vote after migrating', async () => {
    setLegacyCookie()
    await loadModule()

    const radio = document.querySelector<HTMLInputElement>('.rating-input input[value="5"]')!
    radio.checked = true
    radio.dispatchEvent(new Event('change', { bubbles: true }))
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(http.requests).toHaveLength(0)
    expect(document.querySelector('#message_text')!.textContent).toContain('ja havies votat')
  })

  it('does nothing when there is no legacy cookie', async () => {
    await loadModule()

    expect(localStorage.getItem(KEY)).toBeNull()
    expect(document.cookie).not.toContain(KEY)
  })

  it('leaves an existing localStorage record alone', async () => {
    localStorage.setItem(KEY, '1234567890')
    await loadModule()

    expect(localStorage.getItem(KEY)).toBe('1234567890')
  })
})

describe('without localStorage', () => {
  /** Simulate storage being unavailable, as in some private browsing modes. */
  function breakStorage(): void {
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('storage disabled')
    })
  }

  it('falls back to the cookie rather than losing the vote', async () => {
    breakStorage()
    await loadModule()

    http.respondWith({ status: 1, text: 'Gràcies!', vots: 12, valoracio: '3,55' })
    const radio = document.querySelector<HTMLInputElement>('.rating-input input[value="4"]')!
    radio.checked = true
    radio.dispatchEvent(new Event('change', { bubbles: true }))
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(http.requests).toHaveLength(1)
    expect(document.cookie).toContain(KEY)
  })

  it('keeps reading the legacy cookie, so a past vote still counts', async () => {
    setLegacyCookie()
    breakStorage()
    await loadModule()

    const radio = document.querySelector<HTMLInputElement>('.rating-input input[value="5"]')!
    radio.checked = true
    radio.dispatchEvent(new Event('change', { bubbles: true }))
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(http.requests).toHaveLength(0)
    expect(document.querySelector('#message_text')!.textContent).toContain('ja havies votat')
  })
})
