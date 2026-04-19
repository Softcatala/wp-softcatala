/**
 * nav-mobile.ts — Slide-in mobile navigation (3-level lazy build)
 *
 * JS builds each level on demand from the desktop DOM:
 *   - Open hamburger → buildLevel1() — 3 main tabs + gray items
 *   - Click level-1 item → buildLevel2(pane) — go-back + that section's items
 *   - Click level-2 sub-menu → buildLevel3(dropdown) — go-back + submenu content
 *   - Go-back → rebuilds previous level
 *
 * The panel <ul> is cleared and repopulated each time. No hide/show juggling.
 *
 * Classes toggled:
 *   .nav-is-visible — on trigger, primary-nav
 *   .is-visible     — on .cd-overlay
 *   .overflow-hidden — on <body> to prevent scroll
 *   .nav-level-2    — on <ul> when showing level-2 content
 *   .nav-level-3    — on <ul> when showing level-3 content
 */

import { $, $$ } from './utils'

// ── Helpers ──────────────────────────────────────────────────

function htmlWithoutIcons(link: HTMLAnchorElement): string {
  const clone = link.cloneNode(true) as HTMLAnchorElement
  for (const icon of clone.querySelectorAll('i')) icon.remove()
  return clone.innerHTML.trim()
}

function createGoBackLi(label: string, onClick: () => void): HTMLLIElement {
  const li = document.createElement('li')
  li.className = 'go-back'
  const a = document.createElement('a')
  a.href = '#0'
  a.innerHTML = `<i class="fas fa-chevron-left"></i>${label}`
  a.addEventListener('click', (e: MouseEvent) => {
    e.preventDefault()
    onClick()
  })
  li.appendChild(a)
  return li
}

function clearNav(nav: HTMLUListElement): void {
  nav.innerHTML = ''
  nav.classList.remove('nav-level-2', 'nav-level-3')
}

/** Fade out → rebuild → fade in. Skips animation on first open (empty nav). */
function transitionLevel(nav: HTMLUListElement, build: () => void): void {
  // First open: no content yet, just build immediately
  if (!nav.children.length) {
    build()
    return
  }

  // Fade out
  nav.classList.remove('nav-fading-in')
  nav.classList.add('nav-fading')

  const afterFadeOut = (): void => {
    nav.removeEventListener('transitionend', afterFadeOut)
    nav.scrollTop = 0
    build()

    // Force reflow so the browser sees opacity:0 before we flip to 1
    void nav.offsetHeight
    nav.classList.remove('nav-fading')
    nav.classList.add('nav-fading-in')
  }

  nav.addEventListener('transitionend', afterFadeOut, { once: true })

  // Safety timeout in case transitionend doesn't fire (e.g. reduced motion)
  setTimeout(() => {
    if (nav.classList.contains('nav-fading')) {
      afterFadeOut()
    }
  }, 200)
}

// ── Level builders ───────────────────────────────────────────

function buildLevel1(nav: HTMLUListElement): void {
  clearNav(nav)

  const desktopTabs = $$<HTMLAnchorElement>('.navbar-desktop-main .nav-tabs > li > a')
  for (const tabLink of desktopTabs) {
    const paneId = tabLink.getAttribute('href')
    if (!paneId || !paneId.startsWith('#')) continue
    const pane = $<HTMLElement>(paneId)
    if (!pane) continue

    const li = document.createElement('li')
    li.className = 'has-chevron'
    const a = document.createElement('a')
    a.href = '#'
    a.innerHTML = `${htmlWithoutIcons(tabLink)}<i class="fas fa-chevron-right"></i>`
    a.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      transitionLevel(nav, () => buildLevel2(nav, pane, htmlWithoutIcons(tabLink)))
    })
    li.appendChild(a)
    nav.appendChild(li)
  }

  // "Espai de col·laboradors" link
  const collaborators = $<HTMLAnchorElement>('.navbar-espai a')
  if (collaborators) {
    const li = document.createElement('li')
    li.className = 'xs-noticies-collabora'
    const a = document.createElement('a')
    a.href = collaborators.href
    a.textContent = collaborators.textContent?.trim() ?? ''
    li.appendChild(a)
    nav.appendChild(li)
  }

  // Gray navbar links (Notícies, Esdeveniments, etc.)
  for (const newsLink of $$<HTMLAnchorElement>('.navbar-noticies .nav > li > a')) {
    const li = document.createElement('li')
    li.className = 'xs-noticies-collabora'
    const a = document.createElement('a')
    a.href = newsLink.href
    a.textContent = newsLink.textContent?.trim() ?? ''
    li.appendChild(a)
    nav.appendChild(li)
  }
}

function buildLevel2(nav: HTMLUListElement, pane: HTMLElement, parentLabel: string): void {
  clearNav(nav)
  nav.classList.add('nav-level-2')

  // Go-back to level 1
  nav.appendChild(createGoBackLi('Menu', () => transitionLevel(nav, () => buildLevel1(nav))))

  for (const paneLi of $$<HTMLLIElement>('ul.navbar-nav > li', pane)) {
    if (paneLi.classList.contains('divider-vertical')) continue
    const link = paneLi.querySelector<HTMLAnchorElement>(':scope > a')
    if (!link) continue

    const li = document.createElement('li')
    const a = document.createElement('a')
    a.href = '#'

    if (paneLi.classList.contains('dropdown') || paneLi.classList.contains('dropdown-megamenu')) {
      // Has a level-3 submenu
      li.className = 'has-chevron'
      a.innerHTML = `${htmlWithoutIcons(link)}<i class="fas fa-chevron-right"></i>`
      const dropdown = paneLi.querySelector<HTMLElement>(':scope > ul.dropdown-menu')
      const isMega = paneLi.classList.contains('dropdown-megamenu')
      a.addEventListener('click', (e: MouseEvent) => {
        e.preventDefault()
        if (dropdown) {
          transitionLevel(nav, () => buildLevel3(nav, dropdown, isMega, parentLabel, pane))
        }
      })
    } else {
      // Plain link, no submenu
      a.href = link.href
      a.innerHTML = link.innerHTML.trim()
    }

    li.appendChild(a)
    nav.appendChild(li)
  }
}

function buildLevel3(
  nav: HTMLUListElement,
  sourceDropdown: HTMLElement,
  isMega: boolean,
  parentLabel: string,
  parentPane: HTMLElement
): void {
  clearNav(nav)
  nav.classList.add('nav-level-3')

  // Go-back to level 2
  nav.appendChild(createGoBackLi(parentLabel, () => transitionLevel(nav, () => buildLevel2(nav, parentPane, parentLabel))))

  if (isMega) {
    appendMegaDropdownItems(nav, sourceDropdown)
  } else {
    appendSimpleDropdownItems(nav, sourceDropdown)
  }
}

function appendSimpleDropdownItems(target: HTMLUListElement, sourceDropdown: HTMLElement): void {
  for (const item of $$<HTMLLIElement>('li', sourceDropdown)) {
    const link = item.querySelector<HTMLAnchorElement>('a')
    if (!link) continue
    const li = document.createElement('li')
    const a = document.createElement('a')
    a.href = link.href
    a.innerHTML = link.innerHTML.trim()
    li.appendChild(a)
    target.appendChild(li)
  }
}

function appendMegaDropdownItems(target: HTMLUListElement, sourceDropdown: HTMLElement): void {
  const megamenu = sourceDropdown.querySelector<HTMLElement>('.megamenu')
  if (!megamenu) {
    appendSimpleDropdownItems(target, sourceDropdown)
    return
  }

  const osTitle = megamenu.querySelector<HTMLElement>('.mega-sense-llista p')
  if (osTitle) {
    const li = document.createElement('li')
    li.className = 'titol'
    li.textContent = osTitle.textContent?.trim() ?? ''
    target.appendChild(li)
  }

  const thumbnails = $$<HTMLAnchorElement>('.mega-sense-llista a.thumbnail', megamenu)
  if (thumbnails.length) {
    const thumbsLi = document.createElement('li')
    thumbsLi.className = 'thumbnail-mega'
    for (const thumb of thumbnails) {
      thumbsLi.appendChild(thumb.cloneNode(true))
    }
    target.appendChild(thumbsLi)
  }

  const categoryTitle = megamenu.querySelector<HTMLElement>('.mega-amb-llista p')
  if (categoryTitle) {
    const li = document.createElement('li')
    li.className = 'titol'
    li.textContent = categoryTitle.textContent?.trim() ?? ''
    target.appendChild(li)
  }

  for (const link of $$<HTMLAnchorElement>('.mega-amb-llista ul li a', megamenu)) {
    const li = document.createElement('li')
    li.className = 'llista'
    const a = document.createElement('a')
    a.href = link.href
    a.textContent = link.textContent?.trim() ?? ''
    li.appendChild(a)
    target.appendChild(li)
  }

  const button = megamenu.querySelector<HTMLButtonElement>('.bt-basic')
  if (button) {
    const li = document.createElement('li')
    li.appendChild(button.cloneNode(true) as HTMLButtonElement)
    target.appendChild(li)
  }
}

// ── Collapse-group helpers (for 00-home accordion header) ────

function getGroupTargetSelector(btn: HTMLElement): string | null {
  return btn.dataset.target ?? btn.getAttribute('href')
}

function closeAllCollapseGroups(except: HTMLElement): void {
  for (const btn of $$('[data-collapse-group="menu-xs"]')) {
    if (btn === except) continue
    btn.classList.remove('active')
    btn.setAttribute('aria-expanded', 'false')
    const targetSel = getGroupTargetSelector(btn)
    if (!targetSel) continue
    const target = $(targetSel)
    if (target) {
      target.classList.remove('in')
      target.classList.add('collapse')
    }
  }
}

function initCollapseGroups(): void {
  const buttons = $$('[data-collapse-group="menu-xs"]')
  if (!buttons.length) return

  for (const btn of buttons) {
    btn.addEventListener('click', (e: Event) => {
      e.preventDefault()
      const targetSel = getGroupTargetSelector(btn)
      const target = targetSel ? $(targetSel) : null
      const wasActive = btn.classList.contains('active')

      for (const b of buttons) b.classList.remove('active')

      if (!wasActive) {
        btn.classList.add('active')
        btn.setAttribute('aria-expanded', 'true')
      } else {
        btn.setAttribute('aria-expanded', 'false')
      }

      // Collapse all other targets in the group
      for (const b of buttons) {
        if (b === btn) continue
        const otherSel = getGroupTargetSelector(b)
        if (!otherSel) continue
        const otherTarget = $(otherSel)
        if (otherTarget) {
          otherTarget.classList.remove('in')
          otherTarget.classList.add('collapse')
        }
        b.setAttribute('aria-expanded', 'false')
      }

      // Toggle this target
      if (target) {
        if (target.classList.contains('in')) {
          target.classList.remove('in')
          target.classList.add('collapse')
        } else {
          target.classList.remove('collapse')
          target.classList.add('in')
        }
      }
    })
  }
}

// ── Main mobile nav ─────────────────────────────────────────

export function initNavMobile(): void {
  // Needed on 00-home mockup (accordion mobile header) even when cd-nav is absent
  initCollapseGroups()

  const maybeTrigger = $<HTMLAnchorElement>('.cd-nav-trigger')
  const maybeNav = $<HTMLUListElement>('.cd-primary-nav')
  if (!maybeTrigger || !maybeNav) return

  const trigger: HTMLAnchorElement = maybeTrigger
  const primaryNav: HTMLUListElement = maybeNav
  const overlay = $<HTMLDivElement>('.cd-overlay')

  function openNav(): void {
    buildLevel1(primaryNav)
    trigger.classList.add('nav-is-visible')
    primaryNav.classList.add('nav-is-visible')
    overlay?.classList.add('is-visible')
    document.body.classList.add('overflow-hidden')
    closeAllCollapseGroups(trigger)
  }

  function closeNav(): void {
    trigger.classList.remove('nav-is-visible')
    primaryNav.classList.remove('nav-is-visible')
    overlay?.classList.remove('is-visible')
    document.body.classList.remove('overflow-hidden')
    // Clear content so next open starts fresh
    clearNav(primaryNav)
  }

  /* ── Trigger click ─────────────────────────────────────── */
  trigger.addEventListener('click', (e: MouseEvent) => {
    e.preventDefault()
    if (primaryNav.classList.contains('nav-is-visible')) {
      closeNav()
    } else {
      openNav()
    }
  })

  /* ── Close on overlay click ────────────────────────────── */
  overlay?.addEventListener('click', () => closeNav())

  /* ── Close button on overlay (left of panel) ──────────── */
  if (overlay) {
    const closeBtn = document.createElement('button')
    closeBtn.className = 'cd-overlay-close'
    closeBtn.setAttribute('aria-label', 'Tanca el menú')
    closeBtn.innerHTML = '<i class="fas fa-xmark"></i>'
    overlay.appendChild(closeBtn)
    closeBtn.addEventListener('click', (e: MouseEvent) => {
      e.stopPropagation()
      closeNav()
    })
  }

  /* ── Close when other navbar-toggle buttons are clicked ── */
  for (const btn of $$<HTMLButtonElement>('button.navbar-toggle')) {
    btn.addEventListener('click', () => closeNav())
  }

  /* ── Escape to close ───────────────────────────────────── */
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && primaryNav.classList.contains('nav-is-visible')) {
      closeNav()
      trigger.focus()
    }
  })
}
