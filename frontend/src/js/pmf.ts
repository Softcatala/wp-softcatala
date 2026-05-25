/**
 * pmf.ts — FAQ (PMF) table-of-contents builder
 *
 * Reads h2/h3 headings from `.contingut > section`, builds a collapsible
 * nav-anchor TOC into `.contingut > header`, wraps each h3 block in an
 * <article> with an anchor id, and appends "Ves al principi" links.
 *
 * Loaded only on pages with the "Activa els estils per a les PMF" meta field.
 * Replaces: static/js/pmf.js (jQuery-based)
 */

import { $, $$ } from './utils'

interface TocItem {
  text: string
  link: string
}

interface TocGroup {
  text: string
  items: TocItem[]
}

/**
 * Split `parent` children into groups that each start at a `delimiter` match.
 * Returns DocumentFragment[]  — one per matching heading found.
 */
function extractSections(parent: Element, delimiter: string): DocumentFragment[] {
  const headers = Array.from(parent.querySelectorAll<HTMLElement>(delimiter))
  return headers.map((header, i) => {
    const range = document.createRange()
    range.setStartBefore(header)
    const next = headers[i + 1]
    if (next) {
      range.setEndBefore(next)
    } else {
      const last = parent.lastElementChild
      if (last) range.setEndAfter(last)
    }
    return range.extractContents()
  })
}

function buildToc(): void {
  const contingut = $<HTMLElement>('.contingut')
  const innerSection = contingut?.querySelector<HTMLElement>('section')
  const header = contingut?.querySelector<HTMLElement>('header')

  if (!contingut || !innerSection || !header) return

  const h2Fragments = extractSections(innerSection, 'h2')
  if (h2Fragments.length === 0) return

  const backToTop =
    '<div class="row"><div class="col-sm-12">' +
    '<a class="bt-basic bt-basic-petit bt-up" href="#principi">Ves al principi</a>' +
    '</div></div><hr>'

  let anchorId = 0
  const tocGroups: TocGroup[] = []
  const newSections: HTMLElement[] = []

  for (const h2Fragment of h2Fragments) {
    const h3Fragments = extractSections(h2Fragment as unknown as Element, 'h3')

    const section = document.createElement('section')
    section.classList.add('contingut-section')

    const h2 = h2Fragment.querySelector('h2')
    if (h2) section.appendChild(h2)

    const items: TocItem[] = []

    for (const h3Fragment of h3Fragments) {
      anchorId++
      const article = document.createElement('article')
      article.classList.add('contingut-article')
      article.id = `pmf-${anchorId}`
      article.appendChild(h3Fragment)
      section.appendChild(article)

      const h3 = article.querySelector('h3')
      if (h3) {
        items.push({ text: h3.textContent ?? '', link: article.id })
      }
    }

    const groupH2 = section.querySelector('h2')
    tocGroups.push({ text: groupH2?.textContent ?? '', items })
    newSections.push(section)
  }

  // Build TOC HTML
  contingut.classList.add('pmf')

  let html =
    '<a href="#llista-preguntes" class="bt-collapse-pmf" data-toggle="collapse">' +
    '<i class="fas fa-align-right"></i></a>'
  html += '<nav class="nav-anchor collapse in" id="llista-preguntes">'

  for (const group of tocGroups) {
    html += `<h2>${group.text}</h2>`
    html += '<ul class="nav" role="navigation">'
    for (const item of group.items) {
      html += `<li><a href="#${item.link}"><i class="fas fa-caret-right"></i>${item.text}</a></li>`
    }
    html += '</ul>'
  }
  html += '</nav>'

  header.id = 'principi'
  header.insertAdjacentHTML('beforeend', html)

  // Insert rebuilt sections after the (now-empty) original section
  for (const section of newSections.reverse()) {
    innerSection.insertAdjacentElement('afterend', section)
  }

  // Append "Ves al principi" to the last article of each section
  for (const section of $$('.contingut .contingut-section')) {
    const articles = $$<HTMLElement>('.contingut-article', section)
    if (articles.length > 0) {
      articles[articles.length - 1].insertAdjacentHTML('beforeend', backToTop)
    }
  }

  // Smooth scroll for TOC links and bt-up buttons (mirror of main.ts initSmoothScroll)
  for (const link of $$<HTMLAnchorElement>('.nav-anchor ul li a[href^="#"], .bt-up')) {
    link.addEventListener('click', (e) => {
      e.preventDefault()
      const target = $(link.getAttribute('href') ?? '')
      target?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
  }
}

document.addEventListener('DOMContentLoaded', buildToc)
