/**
 * utils.ts — DOM helpers, breakpoint detection, touch detection
 *
 * Replaces: jQuery $(), ResponsiveBootstrapToolkit viewport helpers
 */

/* ── DOM helpers ─────────────────────────────────────────── */

/**
 * Query a single element. Returns typed HTMLElement or null.
 * Accepts an optional generic for narrowing (e.g., `$<HTMLInputElement>(...)`).
 */
export function $<T extends HTMLElement = HTMLElement>(
  sel: string,
  ctx: Document | HTMLElement = document
): T | null {
  return ctx.querySelector<T>(sel)
}

/**
 * Query all matching elements. Returns typed HTMLElement array.
 */
export function $$<T extends HTMLElement = HTMLElement>(
  sel: string,
  ctx: Document | HTMLElement = document
): T[] {
  return Array.from(ctx.querySelectorAll<T>(sel))
}

/* ── Breakpoints (must match _tokens.scss) ───────────────── */

const BP = { xxs: 480, sm: 769, md: 1025, lg: 1200 } as const

type BreakpointQuery = '<sm' | '<=sm' | '>=sm' | '>=md' | '>=lg'

/**
 * Check if viewport matches a breakpoint query.
 * Replaces ResponsiveBootstrapToolkit's `viewport.is()`.
 */
export function matchesBP(query: BreakpointQuery): boolean {
  const w = window.innerWidth
  switch (query) {
    case '<sm':  return w < BP.sm
    case '<=sm': return w < BP.md
    case '>=sm': return w >= BP.sm
    case '>=md': return w >= BP.md
    case '>=lg': return w >= BP.lg
  }
}

/* ── Touch detection ─────────────────────────────────────── */

/**
 * One-shot touch detection: adds `touch` class to <body> on first touchstart.
 * Replaces the old jQuery touchstart listener.
 */
export function initTouchDetection(): void {
  function onTouch(): void {
    document.body.classList.add('touch')
    document.removeEventListener('touchstart', onTouch, false)
  }
  document.addEventListener('touchstart', onTouch, false)
}

/* ── Reduced motion ──────────────────────────────────────── */

/* ── Analytics ──────────────────────────────────────────── */

/**
 * Send a Google Analytics pageview tracking event.
 * No-ops when ga is not available.
 */
export function sendTracking(success: boolean, status = '', verb = ''): void {
  if (typeof (window as any).ga !== 'function') return
  const url = (success ? '' : status) + document.location.pathname + (success ? '' : verb)
  ;(window as any).ga('send', 'pageview', url)
}

/* ── Share links ─────────────────────────────────────────── */

/**
 * Update Facebook and Twitter share links.
 * @param twitterText - Full tweet text (caller supplies the page-specific copy)
 */
export function updateShareLinks(twitterText: string): void {
  const url = window.location.href
  document.getElementById('share_facebook')?.setAttribute(
    'href',
    `https://www.facebook.com/sharer/sharer.php?u=${url}`
  )
  document.getElementById('share_twitter')?.setAttribute(
    'href',
    `https://twitter.com/intent/tweet?text=${twitterText} ${url}`
  )
}

/* ── API auth token ──────────────────────────────────────── */

const TOKEN_REFRESH_MARGIN_S = 15 * 60
const TOKEN_RETRY_INTERVAL_MS = 60 * 1000

let cachedToken = ''
let metaTagRead = false
let lastRefreshAttempt = 0

/**
 * Expiry (unix seconds) of a JWT, without verifying it. Returns 0 when the
 * token cannot be decoded, which makes it count as expired.
 */
function tokenExp(token: string): number {
  try {
    const payload = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')
    return JSON.parse(atob(payload)).exp ?? 0
  } catch {
    return 0
  }
}

function tokenIsFresh(token: string): boolean {
  return token !== '' && tokenExp(token) - Date.now() / 1000 > TOKEN_REFRESH_MARGIN_S
}

/**
 * Auth token for api.softcatala.org requests. Seeded from the sc-token meta
 * tag WordPress prints on every page; when it nears expiry (long-lived tabs),
 * a fresh one is fetched from the same-origin admin-ajax endpoint. Failed
 * refreshes fall back to the stale token and are retried at most once a
 * minute, so callers never block for long.
 */
export async function getScToken(): Promise<string> {
  if (!metaTagRead) {
    metaTagRead = true
    cachedToken = document.querySelector<HTMLMetaElement>('meta[name="sc-token"]')?.content ?? ''
  }

  if (tokenIsFresh(cachedToken) || Date.now() - lastRefreshAttempt < TOKEN_RETRY_INTERVAL_MS) {
    return cachedToken
  }

  lastRefreshAttempt = Date.now()
  try {
    const res = await fetch('/wp-admin/admin-ajax.php?action=sc_get_token')
    if (res.ok) {
      const json = await res.json()
      if (json?.token) cachedToken = json.token
    }
  } catch {
    // Network error: keep whatever we have.
  }
  return cachedToken
}

export async function scAuthHeaders(): Promise<Record<string, string>> {
  const token = await getScToken()
  return token ? { 'X-SC-Token': token } : {}
}

/* ── Input focus ─────────────────────────────────────────── */

/**
 * Focus a search input on desktop (selecting existing text for easy replacement).
 * On mobile, clears the field instead to avoid the keyboard popping up.
 */
export function focusSearchInput(selector: string): void {
  const input = $<HTMLInputElement>(selector)
  if (!input) return
  if (!matchesBP('<sm')) {
    input.select()
    input.focus()
  } else {
    input.value = ''
  }
}

export function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}
