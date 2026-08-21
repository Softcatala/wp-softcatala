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

/* ── Platform detection ──────────────────────────────────── */

export type DetectedOS = 'windows' | 'ios' | 'osx' | 'android' | 'linux'

/**
 * Best-effort OS detection from the user agent, or null when nothing matches.
 * Replaces the abandoned jquery.browser plugin.
 *
 * Order matters: iOS user agents contain "Mac OS X" and Android ones contain
 * "Linux", so the more specific platforms are tested first.
 *
 * Note: iPadOS 13+ in its default desktop mode is indistinguishable from macOS
 * by user agent alone, and is reported as 'osx'.
 */
export function detectOS(): DetectedOS | null {
  const ua = navigator.userAgent
  if (ua.includes('Win')) return 'windows'
  if (ua.includes('iPad') || ua.includes('iPhone') || ua.includes('iPod')) return 'ios'
  if (ua.includes('Mac')) return 'osx'
  if (ua.includes('Android')) return 'android'
  if (ua.includes('Linux')) return 'linux'
  return null
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

/* ── Dynamic SEO metadata ──────────────────────────────── */

export interface SeoMetadata {
  canonical: string
  title: string
  description: string
  indexable?: boolean
}

function setMetaContent(selector: string, content: string): void {
  let meta = document.querySelector<HTMLMetaElement>(selector)
  if (!meta) {
    meta = document.createElement('meta')
    const propertyMatch = selector.match(/^meta\[property="([^"]+)"\]$/)
    const nameMatch = selector.match(/^meta\[name="([^"]+)"\]$/)
    if (propertyMatch) meta.setAttribute('property', propertyMatch[1])
    if (nameMatch) meta.setAttribute('name', nameMatch[1])
    document.head.appendChild(meta)
  }
  meta.content = content
}

/** Keep the visible head coherent after an AJAX tool navigation. */
export function updateSeoMetadata(metadata: SeoMetadata): void {
  document.title = metadata.title

  let canonical = document.querySelector<HTMLLinkElement>('link[rel="canonical"]')
  if (!canonical) {
    canonical = document.createElement('link')
    canonical.rel = 'canonical'
    document.head.appendChild(canonical)
  }
  canonical.href = metadata.canonical

  setMetaContent('meta[name="description"]', metadata.description)
  setMetaContent('meta[property="og:title"]', metadata.title)
  setMetaContent('meta[property="og:description"]', metadata.description)
  setMetaContent('meta[property="og:url"]', metadata.canonical)
  setMetaContent('meta[name="twitter:title"]', metadata.title)
  setMetaContent('meta[name="twitter:description"]', metadata.description)
  setMetaContent('meta[name="robots"]', metadata.indexable === false ? 'noindex, follow' : 'index, follow')
}

/** A history entry contains server-rendered tool results; reload it on back/forward. */
export function reloadToolOnHistoryNavigation(): void {
  window.addEventListener('popstate', () => window.location.reload())
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
    // WordPress core is not at the site root, so the endpoint URL comes from
    // a meta tag printed by inc/api-token.php rather than a hardcoded path.
    const refreshUrl =
      document.querySelector<HTMLMetaElement>('meta[name="sc-token-refresh-url"]')?.content ??
      '/wp/wp-admin/admin-ajax.php?action=sc_get_token'
    const res = await fetch(refreshUrl)
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
