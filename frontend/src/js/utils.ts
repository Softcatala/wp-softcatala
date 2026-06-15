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
