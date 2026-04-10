/**
 * carousel.ts — Auto-advancing image carousel
 *
 * Replaces: Bootstrap 3 Carousel plugin (data-ride="carousel")
 *
 * Markup expectations (existing BS3):
 *   <div class="carousel slide" data-ride="carousel">
 *     <ol class="carousel-indicators">
 *       <li data-slide-to="0" class="active"></li>
 *       ...
 *     </ol>
 *     <div class="carousel-inner">
 *       <div class="item active">...</div>
 *       <div class="item">...</div>
 *     </div>
 *   </div>
 *
 * Features:
 * - Auto-advance every 5s
 * - Pause on hover
 * - Indicator dot clicks
 * - Respects prefers-reduced-motion (disables auto-play)
 * - CSS transition via .item.active toggling (transition handled in CSS)
 */

import { $$, prefersReducedMotion } from './utils'

const INTERVAL = 5000

export function initCarousels(): void {
  $$('.carousel[data-ride="carousel"]').forEach(initCarousel)
}

function initCarousel(el: HTMLElement): void {
  const items = $$('.carousel-inner > .item', el)
  const indicators = $$<HTMLLIElement>('.carousel-indicators > li', el)
  if (items.length < 2) return

  let current = items.findIndex(i => i.classList.contains('active'))
  if (current === -1) current = 0

  let timer: ReturnType<typeof setInterval> | undefined

  function goTo(index: number): void {
    if (index === current) return
    items[current].classList.remove('active')
    indicators[current]?.classList.remove('active')

    current = ((index % items.length) + items.length) % items.length
    items[current].classList.add('active')
    indicators[current]?.classList.add('active')
  }

  function next(): void {
    goTo(current + 1)
  }

  function startAutoPlay(): void {
    if (prefersReducedMotion()) return
    stopAutoPlay()
    timer = setInterval(next, INTERVAL)
  }

  function stopAutoPlay(): void {
    if (timer !== undefined) {
      clearInterval(timer)
      timer = undefined
    }
  }

  /* ── Indicator clicks ────────────────────────────────── */
  indicators.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      goTo(i)
      startAutoPlay()
    })
  })

  /* ── Pause on hover ──────────────────────────────────── */
  el.addEventListener('mouseenter', stopAutoPlay)
  el.addEventListener('mouseleave', startAutoPlay)

  /* ── Keyboard ────────────────────────────────────────── */
  el.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'ArrowLeft') {
      goTo(current - 1)
      startAutoPlay()
    } else if (e.key === 'ArrowRight') {
      goTo(current + 1)
      startAutoPlay()
    }
  })

  /* ── Start ───────────────────────────────────────────── */
  startAutoPlay()
}
