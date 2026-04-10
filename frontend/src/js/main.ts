/**
 * main.ts — Softcatala Frontend Entry Point
 *
 * Imports SCSS (Vite extracts to static/css/main.min.css) and all TS modules.
 * Ports remaining page behaviors from old jQuery main.js.
 */

// ── SCSS ──────────────────────────────────────────────────
import '../scss/main.scss'

// ── Modules ───────────────────────────────────────────────
import { $, $$, matchesBP, initTouchDetection } from './utils'
import { initDropdowns } from './dropdown'
import { initTabs, activateTab } from './tabs'
import { initCarousels } from './carousel'
import { initNavMobile } from './nav-mobile'
import { initSearch } from './search'
import { initCollapse } from './collapse'

document.addEventListener('DOMContentLoaded', () => {
  // ── Core components ───────────────────────────────────
  initTouchDetection()
  initDropdowns()
  initTabs()
  initCarousels()
  initNavMobile()
  initSearch()
  initCollapse()

  // ── Page-specific behaviors ───────────────────────────
  initTranslatorCheckboxMove()
  initLeftMenuAccordion()
  initLeftMenuButton()
  initVersionsCollapse()
  initMoreComments()
  initTranslatorLanguageButtons()
  initReplyToggle()
  initSmoothScroll()
  initTopSearchForms()
  initActiveMenuHighlighting()
  initHomeProgramsButton()
})

/* ─────────────────────────────────────────────────────────
 * Page-specific behaviors ported from old jQuery main.js
 * ───────────────────────────────────────────────────────── */

/**
 * Traductor: move .traductor-checkbox to different container based on breakpoint.
 * XS: after .primer-textarea | >=SM: after .traductor-textarea
 */
function initTranslatorCheckboxMove(): void {
  const maybeCheckbox = $<HTMLElement>('.traductor-checkbox')
  if (!maybeCheckbox) return
  const checkbox: HTMLElement = maybeCheckbox

  function move(): void {
    const target = matchesBP('<sm')
      ? $<HTMLElement>('.primer-textarea')
      : $<HTMLElement>('.traductor-textarea')
    target?.after(checkbox)
  }

  move()
  window.addEventListener('resize', move)
}

/**
 * Left menu accordion (mobile): toggle .active on #accordion li.
 * Only active at <=sm breakpoint.
 */
function initLeftMenuAccordion(): void {
  const accordion = $<HTMLElement>('#accordion')
  if (!accordion) return

  for (const link of $$<HTMLAnchorElement>('li > a', accordion)) {
    link.addEventListener('click', (e: MouseEvent) => {
      if (!matchesBP('<=sm')) return
      e.preventDefault()
      const li = link.parentElement
      if (!li) return

      if (!li.classList.contains('active')) {
        const parentUl = li.parentElement
        if (parentUl) {
          for (const sib of $$<HTMLLIElement>(':scope > li', parentUl)) {
            sib.classList.remove('active')
          }
        }
        li.classList.add('active')
      } else {
        li.classList.remove('active')
      }
    })
  }
}

/**
 * Left menu toggle button: sync .bt-menu-lateral.active with #menu-lateral collapse state.
 */
function initLeftMenuButton(): void {
  const menuLateral = $<HTMLElement>('#menu-lateral')
  if (!menuLateral) return

  menuLateral.addEventListener('show.collapse', () => {
    for (const btn of $$('.bt-menu-lateral')) btn.classList.add('active')
  })
  menuLateral.addEventListener('hide.collapse', () => {
    for (const btn of $$('.bt-menu-lateral')) btn.classList.remove('active')
  })
}

/**
 * Program versions collapse: hide bt-versions button and dim download button when open.
 */
function initVersionsCollapse(): void {
  const versions = $<HTMLElement>('#versions')
  if (!versions) return

  versions.addEventListener('show.collapse', () => {
    for (const el of $$('.bt-versions')) el.classList.add('hidden')
    for (const el of $$('.bt-download-hide')) el.classList.add('desactivat')
  })
  versions.addEventListener('hide.collapse', () => {
    for (const el of $$('.bt-versions')) el.classList.remove('hidden')
    for (const el of $$('.bt-download-hide')) el.classList.remove('desactivat')
  })
}

/**
 * More comments: disable the "more" button after expanding.
 */
function initMoreComments(): void {
  for (const container of $$('[id^="mescomentaris"]')) {
    container.addEventListener('show.collapse', () => {
      const btn = container.nextElementSibling?.querySelector<HTMLButtonElement>('button')
      if (btn) {
        btn.classList.add('bt-mes-disabled')
        btn.disabled = true
      }
    })
  }
}

/**
 * Translator language buttons: toggle .select class within origin and destination groups.
 */
function initTranslatorLanguageButtons(): void {
  // Origin language buttons
  for (const btn of $$('.btns-llengues-origen .bt')) {
    btn.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      for (const b of $$('.btns-llengues-origen .bt')) b.classList.remove('select')
      btn.classList.add('select')
    })
  }

  // Destination language buttons
  for (const btn of $$('.btns-llengues-desti .bt')) {
    btn.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      // If it's a wrapper div containing a disabled button, skip
      if (btn.tagName === 'DIV') {
        const innerBtn = btn.querySelector<HTMLButtonElement>('button')
        if (innerBtn?.disabled) return
      }
      for (const b of $$('.btns-llengues-desti .bt')) b.classList.remove('select')
      btn.classList.add('select')
    })
  }
}

/**
 * Reply button toggle: .respon toggle .active
 */
function initReplyToggle(): void {
  for (const btn of $$('.respon')) {
    btn.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      btn.classList.toggle('active')
    })
  }
}

/**
 * Smooth scroll for anchor links in FAQ nav and bt-up buttons.
 */
function initSmoothScroll(): void {
  for (const link of $$<HTMLAnchorElement>('.nav-anchor ul li a[href^="#"], .bt-up')) {
    link.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      const hash = link.getAttribute('href')
      if (!hash) return
      const target = $<HTMLElement>(hash)
      target?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
  }
}

/**
 * Top search forms: redirect to /cerca/{query}/ on submit.
 */
function initTopSearchForms(): void {
  const formIds = ['searchform_top_1', 'searchform_top_2'] as const

  for (const id of formIds) {
    const form = $<HTMLFormElement>(`#${id}`)
    if (!form) continue

    form.addEventListener('submit', (e: SubmitEvent) => {
      e.preventDefault()
      const input = form.querySelector<HTMLInputElement>('input[type="text"]')
      const query = input?.value.trim()
      if (query) {
        window.location.href = `/cerca/${query}/`
      }
    })
  }
}

/**
 * Active menu highlighting: activate the header tab + menu item matching current URL.
 * On homepage: detect OS and activate the matching programari tab.
 */
function initActiveMenuHighlighting(): void {
  const path = window.location.pathname
  if (path === '/') {
    activateHomeOsTab()
    return
  }

  // Find nav link matching current path
  let matchedLink: HTMLElement | null = null
  try {
    matchedLink = $<HTMLElement>(`nav a[href^="${CSS.escape(path)}"]`)
  } catch {
    return
  }
  if (!matchedLink) return

  // Activate the parent header tab (recursos/coneixeu/collaboreu)
  const topTabs = ['recursos', 'coneixeu', 'collaboreu'] as const
  for (const tabId of topTabs) {
    const tabPanel = $<HTMLElement>(`#${tabId}`)
    if (tabPanel?.contains(matchedLink)) {
      const tabLink = $<HTMLAnchorElement>(`a[aria-controls="${tabId}"]`)
      if (tabLink) activateTab(tabLink)
    }
  }

  // Mark the link or its parent dropdown-toggle as active
  const inDropdown = matchedLink.closest('.dropdown-menu')
  if (inDropdown) {
    const navContainer = matchedLink.closest<HTMLElement>('.nav-tabs, .navbar-nav')
    const dropdownToggle = navContainer?.querySelector<HTMLElement>('.dropdown-toggle')
    if (dropdownToggle) {
      dropdownToggle.classList.add('active')
    }
  } else {
    matchedLink.classList.add('active')
  }

  // Ensure the mobile user button isn't highlighted
  $<HTMLElement>('#navbar-usuari-mobile')?.classList.remove('active')
}

type DetectedOS = 'windows' | 'ios' | 'osx' | 'android' | 'linux'

/**
 * Homepage: detect OS and activate the matching programari tab.
 */
function activateHomeOsTab(): void {
  const ua = navigator.userAgent
  let os: DetectedOS | null = null

  if (ua.includes('Win')) os = 'windows'
  else if (ua.includes('iPad') || ua.includes('iPhone') || ua.includes('iPod')) os = 'ios'
  else if (ua.includes('Mac')) os = 'osx'
  else if (ua.includes('Android')) os = 'android'
  else if (ua.includes('Linux')) os = 'linux'

  if (!os) return

  const osTab = $<HTMLAnchorElement>(`.tab-${os} > a`)
  if (osTab) activateTab(osTab)
}

/**
 * Home programs button: update href to match currently active OS tab.
 */
function initHomeProgramsButton(): void {
  const btn = $<HTMLAnchorElement>('#btn-home-programes')
  if (!btn) return

  btn.addEventListener('click', () => {
    const activePanel = $<HTMLElement>('.programari .tab-content .active')
    if (activePanel) {
      btn.href = `/programes/so/${activePanel.id}/`
    }
  })
}
