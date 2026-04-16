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

export function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}
