/**
 * nav-mobile.ts — Slide-in mobile navigation (3-level accordion)
 *
 * Replaces: mega-site-navigation bower component + jQuery mobile menu logic
 *
 * Markup (in footer.twig):
 *   <div class="cd-overlay"></div>
 *   <nav class="navbar-principal-xs cd-nav">
 *     <ul class="cd-primary-nav nav nav-principal-xs">
 *       <li class="has-children">
 *         <a href="#">Recursos...<i class="fa fa-chevron-right"></i></a>
 *         <ul class="cd-secondary-nav is-hidden">
 *           <li class="go-back"><a href="#0">...</a></li>
 *           ...
 *
 * Classes toggled:
 *   .nav-is-visible — on trigger, primary-nav, pag-header (cd-main-header)
 *   .is-visible     — on .cd-overlay
 *   .is-hidden      — on submenu <ul> (removed to show, added to hide)
 *   .moves-out      — on parent <ul> when child submenu opens
 *   .selected       — on <a> when its submenu is open
 *   .overflow-hidden — on <body> to prevent scroll
 */

import { $, $$ } from './utils'

/* ── Collapse-group helpers ──────────────────────────────── */

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

/* ── Main mobile nav ─────────────────────────────────────── */

export function initNavMobile(): void {
  const maybeTrigger = $<HTMLAnchorElement>('.cd-nav-trigger')
  const maybeNav = $<HTMLUListElement>('.cd-primary-nav')
  if (!maybeTrigger || !maybeNav) return

  const trigger: HTMLAnchorElement = maybeTrigger
  const primaryNav: HTMLUListElement = maybeNav
  const overlay = $<HTMLDivElement>('.cd-overlay')
  const header = $<HTMLElement>('.pag-header.cd-main-header, .cd-main-header')

  function openNav(): void {
    trigger.classList.add('nav-is-visible')
    primaryNav.classList.add('nav-is-visible')
    header?.classList.add('nav-is-visible')
    overlay?.classList.add('is-visible')
    document.body.classList.add('overflow-hidden')
    closeAllCollapseGroups(trigger)
  }

  function closeNav(): void {
    trigger.classList.remove('nav-is-visible')
    primaryNav.classList.remove('nav-is-visible')
    header?.classList.remove('nav-is-visible')
    overlay?.classList.remove('is-visible')
    document.body.classList.remove('overflow-hidden')

    for (const ul of $$('.has-children ul', primaryNav)) ul.classList.add('is-hidden')
    for (const a of $$('.has-children a', primaryNav)) a.classList.remove('selected')
    for (const ul of $$('.moves-out', primaryNav)) ul.classList.remove('moves-out')
  }

  /* ── Trigger click ─────────────────────────────────────── */
  trigger.addEventListener('click', (e: MouseEvent) => {
    e.preventDefault()
    if (overlay?.classList.contains('is-visible')) {
      closeNav()
    } else {
      openNav()
    }
  })

  /* ── Close on overlay click ────────────────────────────── */
  overlay?.addEventListener('click', () => {
    closeNav()
    for (const a of $$('.bt-menu a')) a.classList.remove('active')
  })

  /* ── Close when other navbar-toggle buttons are clicked ── */
  for (const btn of $$<HTMLButtonElement>('button.navbar-toggle')) {
    btn.addEventListener('click', () => closeNav())
  }

  /* ── Submenu open: .has-children > a ───────────────────── */
  for (const link of $$<HTMLAnchorElement>('.has-children > a', primaryNav)) {
    link.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      const submenu = link.nextElementSibling
      if (!(submenu instanceof HTMLUListElement)) return

      if (submenu.classList.contains('is-hidden')) {
        link.classList.add('selected')
        submenu.classList.remove('is-hidden')
        link.closest('ul')?.classList.add('moves-out')

        const parentLi = link.parentElement
        const parentUl = parentLi?.parentElement
        if (parentLi && parentUl instanceof HTMLElement) {
          for (const sib of $$<HTMLLIElement>(':scope > .has-children', parentUl)) {
            if (sib !== parentLi) {
              sib.querySelector<HTMLUListElement>(':scope > ul')?.classList.add('is-hidden')
              sib.querySelector<HTMLAnchorElement>(':scope > a')?.classList.remove('selected')
            }
          }
        }
      } else {
        link.classList.remove('selected')
        submenu.classList.add('is-hidden')
        link.closest('ul')?.classList.remove('moves-out')
      }
    })
  }

  /* ── Go back ───────────────────────────────────────────── */
  for (const goBack of $$<HTMLLIElement>('.go-back', primaryNav)) {
    goBack.addEventListener('click', (e: MouseEvent) => {
      e.preventDefault()
      const submenu = goBack.parentElement
      if (!submenu) return
      submenu.classList.add('is-hidden')
      submenu.closest('.has-children')?.closest('ul')?.classList.remove('moves-out')
    })
  }

  /* ── Escape to close ───────────────────────────────────── */
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && primaryNav.classList.contains('nav-is-visible')) {
      closeNav()
      trigger.focus()
    }
  })

  /* ── Collapse groups ───────────────────────────────────── */
  initCollapseGroups()
}
