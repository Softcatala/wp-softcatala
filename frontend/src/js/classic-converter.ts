/**
 * Converts a classic page's raw content to block markup.
 *
 * The bulk of it is WordPress's own converter, rawHandler() from
 * @wordpress/blocks: the same code as the editor's "Convert to blocks". This
 * adds what that converter doesn't know about the site:
 *
 * - sc-footnotes notes, [1. text], become core footnotes: the format in the
 *   text, a Footnotes block at the end, and the notes for the post's
 *   `footnotes` meta.
 * - The theme's shortcodes become its blocks or styles: [ia-data] →
 *   sc/ia-data, [ia-local-checker] and [navega-en-catala] → their blocks,
 *   [llista-icones] → sc/llista-icones (icons and colour kept),
 *   [llista-links] → a list with the Amb icones style.
 *   Other shortcodes ([cards], [insert]…) are kept in Shortcode blocks.
 * - Legacy classes on top-level elements become block styles: callouts
 *   (fons-gris, messagebox, alerta, req-banner) → Avís, taula-2col → Dues
 *   columnes, prettytable → Guia d'estil, petit → the "Text petit" format,
 *   center → centred text, the
 *   ia-local req-flex row → Requadres columns.
 *
 * Everything else is left to rawHandler, which drops inline styles and
 * unknown classes. The report says what was mapped and what needs a look.
 *
 * WordPress's functions come in as `api`, so the same code runs in two places:
 * the editor's "Convert to blocks" (editor-convert.ts, with the editor's `wp`
 * globals) and `npm run convert` (tools/convert-classic.ts, with the npm
 * packages). Either way the blocks must be registered first, and it needs a
 * DOM. Tested in tests/convert-classic.test.ts.
 */

import { iconListData } from './icon-list-data'

/** A block as @wordpress/blocks hands it out. */
export interface Block {
  clientId: string
  name: string
  attributes: Record<string, unknown>
  innerBlocks: Block[]
  /** Set by parse(): whether the saved HTML is what the block would save. */
  isValid?: boolean
}

/** The WordPress functions the converter uses. */
export interface WordPressApi {
  autop: (text: string) => string
  createBlock: (name: string, attributes?: Record<string, unknown>, innerBlocks?: Block[]) => Block
  parse: (content: string) => Block[]
  rawHandler: (args: { HTML: string }) => Block[]
  serialize: (blocks: Block | Block[]) => string
  shortcodeAttrs: (text: string) => { named: Record<string, string | undefined> }
}

let api: WordPressApi

export interface Footnote {
  id: string
  content: string
}

export interface Conversion {
  /** Serialized blocks. */
  markup: string
  /** For the post's `footnotes` meta (as JSON). Empty without notes. */
  footnotes: Footnote[]
  /** What was mapped, and what needs a person to look at it. */
  report: string[]
}

export interface Options {
  /** Footnote ids; random UUIDs by default (tests pass a counter). */
  newId?: () => string
}

const AVIS_CLASSES: Record<string, string> = {
  'fons-gris': 'avis-neutre',
  messagebox: 'avis-info',
  alerta: 'avis-atencio',
  'req-banner': 'avis-neutre',
}

/** Legacy table classes and the style they become (null: the default look). */
const TABLE_CLASSES: Array<[RegExp, string | null]> = [
  [/^taula-2col$/, 'dues-columnes'],
  [/^prettytable$/, 'guia-estil'],
  // No CSS of their own in the theme (comparacio had one for eleven days in
  // 2015): they look like the default table today.
  [/^(prettytable\d+|wikitable|comparacio\d*|tres_columnes\d*)$/, null],
]

/** Unwrapped like a <div> (pasted press clippings have them). */
const HTML5_WRAPPERS = ['section', 'article', 'header', 'footer', 'aside', 'main']

/** Unwrapped when they hold whole paragraphs. */
const INLINE_WRAPPERS = ['em', 'i', 'strong', 'b', 'u', 'font', 'span']

/** Shortcodes kept as they are, in a Shortcode block. */
const KEPT_SHORTCODES = ['cards', 'insert']

interface Context {
  /** Blocks built from shortcodes, found again by their token. */
  stored: Block[]
  report: string[]
}

const TOKEN = (kind: string, n: number) => `⟦${kind}:${n}⟧`
const TOKEN_PATTERN = /⟦(fn|sc):(\d+)⟧/

export function convertClassic(raw: string, wordpress: WordPressApi, options: Options = {}): Conversion {
  api = wordpress
  const newId = options.newId ?? (() => crypto.randomUUID())
  const context: Context = { stored: [], report: [] }
  const { report } = context
  const footnotes: Footnote[] = []

  let html = raw.replace(/\r\n?/g, '\n')

  // Kept, but blocks can lose them: a <script> or <iframe> inside a paragraph
  // goes when the paragraph is edited, or saved by someone without the
  // unfiltered_html capability.
  for (const [tag, what] of [['script', 'scripts'], ['iframe', 'iframes'], ['form', 'formularis']]) {
    const n = (html.match(new RegExp(`<${tag}[\\s>]`, 'gi')) ?? []).length
    if (n > 0) report.push(`${n} ${what} (<${tag}>): comproveu que encara funcionen després de desar; si no, deixeu aquell tros en un bloc «Clàssic»`)
  }

  const styles = (html.match(/\sstyle\s*=\s*["']/gi) ?? []).length
  if (styles > 0) report.push(`${styles} atributs style descartats (colors, mides o alineacions fets a mà)`)

  // sc-footnotes' own pattern: "[1. text]".
  html = html.replace(/\[(\d+)\.\s*(.*?)\]/gs, (_match, _n: string, content: string) => {
    footnotes.push({ id: newId(), content: content.trim() })
    return TOKEN('fn', footnotes.length - 1)
  })
  if (footnotes.length > 0) report.push(`${footnotes.length} notes al peu passades a notes al peu de l'editor`)

  html = convertShortcodes(html, context)

  const blocks = convertHtml(api.autop(html), context)
  if (footnotes.length > 0) blocks.push(api.createBlock('core/footnotes'))

  let markup = api.serialize(blocks)
  markup = markup.replace(new RegExp(TOKEN_PATTERN.source, 'g'), (_m, kind: string, n: string) => {
    if (kind !== 'fn') {
      // A shortcode inside another element (a list item, a table cell):
      // it can't become a block there.
      report.push(`Un codi curt dins d'un altre element no s'ha pogut convertir: ${api.serialize([context.stored[Number(n)]])}`)
      return ''
    }
    // Numbered in reading order, as the editor saves them (WordPress's
    // excerpt code expects the number).
    const id = footnotes[Number(n)].id
    return `<sup data-fn="${id}" class="fn"><a href="#${id}" id="${id}-link">${Number(n) + 1}</a></sup>`
  })

  markup = markup.replace(/\u27E6petit\u27E7/g, '<span class="sc-petit">').replace(/\u27E6\/petit\u27E7/g, '</span>')

  // A round trip through WordPress's own parser and serializer gives the
  // exact saved form (entities such as &nbsp; and &gt;).
  const parsed = keepInvalidAsClassic(withoutEmptyParagraphs(api.parse(markup), report).map(automaticTableLayout), report)
  const htmlBlocks = countBlocks(parsed, 'core/html')
  if (htmlBlocks > 0) report.push(`${htmlBlocks} blocs «HTML personalitzat» (HTML que no té bloc; només els administradors els poden editar): cal revisar-los`)
  markup = api.serialize(parsed)

  return { markup, footnotes, report: summarise(report) }
}

/**
 * Paragraphs with nothing in them: the old <p>&nbsp;</p> spacers, what is left
 * around a shortcode the Classic block had wrapped in a paragraph, and what
 * WordPress's conversion leaves next to an image.
 */
function withoutEmptyParagraphs(blocks: Block[], report: string[]): Block[] {
  return blocks
    .filter((b) => {
      const empty = b.name === 'core/paragraph' && !/<img/i.test(String(b.attributes.content ?? '')) && !String(b.attributes.content ?? '').replace(/<br\s*\/?>|&nbsp;|\u00A0|\s/gi, '')
      if (empty) report.push('Paràgrafs buits eliminats')
      return !empty
    })
    .map((b) => (b.innerBlocks.length > 0 ? { ...b, innerBlocks: withoutEmptyParagraphs(b.innerBlocks, report) } : b))
}

/**
 * HTML that WordPress's conversion turns into a block that doesn't validate (a
 * link inside a link, a table inside a table cell, a pasted wiki gallery)
 * would open with "This block contains unexpected content". It goes into a
 * Classic block instead, which shows and edits it as before.
 */
function keepInvalidAsClassic(blocks: Block[], report: string[]): Block[] {
  const invalid = (b: Block): boolean => b.isValid === false || b.innerBlocks.some(invalid)
  const kept = blocks.filter(invalid).length
  if (kept > 0) report.push(`${kept} blocs que no serien vàlids (HTML que cap bloc no pot desar tal com és) deixats en un bloc «Clàssic»: cal revisar-los`)

  return blocks.map((b) => (invalid(b) ? api.createBlock('core/freeform', { content: api.serialize([b]).replace(/<!-- \/?wp:[\s\S]*?-->\n?/g, '').trim() }) : b))
}

function countBlocks(blocks: Block[], name: string): number {
  return blocks.reduce((n, b) => n + (b.name === name ? 1 : 0) + countBlocks(b.innerBlocks, name), 0)
}

/** Repeated lines once, with a count. */
function summarise(lines: string[]): string[] {
  const counts = new Map<string, number>()
  for (const line of lines) counts.set(line, (counts.get(line) ?? 0) + 1)
  return [...counts].map(([line, n]) => (n > 1 ? `${line} (×${n})` : line))
}

/** Turns the theme's shortcodes into tokens for blocks built here. */
function convertShortcodes(html: string, context: Context): string {
  const { report } = context
  const store = (block: Block): string => {
    context.stored.push(block)
    return `\n\n<p>${TOKEN('sc', context.stored.length - 1)}</p>\n\n`
  }
  const count = (tag: string) => report.push(`[${tag}] convertit en bloc`)
  // One item per line. The editor's Classic block (TinyMCE) ends each line
  // with <br />; the saved content has plain line breaks.
  const lines = (body: string) => body.replace(/<br\s*\/?>/gi, '\n').split('\n').map((l) => l.trim()).filter(Boolean)

  html = html.replace(/\[llista-icones(\s[^\]]*)?\]([\s\S]*?)\[\/llista-icones\]/g, (_m, attrText = '', body: string) => {
    const color = (api.shortcodeAttrs(attrText).named as Record<string, string>).color ?? 'blanc'
    const rows = lines(body).map((line) => line.split('|'))
    const valid = rows.filter((parts) => parts.length === 3)
    if (valid.length < rows.length) report.push(`[llista-icones]: ${rows.length - valid.length} línies sense les 3 parts (icona|títol|text) descartades`)
    report.push('[llista-icones] convertit en bloc «Llista amb icones»')
    const items = valid.map(([icon, title, text]) => ({ icon: icon.trim(), title: title.trim(), text: text.trim() }))
    return store(api.createBlock('sc/llista-icones', { name: 'sc/llista-icones', data: iconListData(items, color), mode: 'preview' }))
  })

  html = html.replace(/\[(llista-links)(\s[^\]]*)?\]([\s\S]*?)\[\/\1\]/g, (_m, tag: string, _a, body: string) => {
    const items = lines(body).map((line) => {
      const parts = line.split('|').map((p) => p.trim())
      return parts.length === 2 ? `<a href="${parts[1]}">${parts[0]}</a>` : line
    })
    report.push(`[${tag}] convertit en llista «Amb icones»`)
    return store(
      api.createBlock(
        'core/list',
        { className: 'is-style-amb-icones' },
        items.map((content) => api.createBlock('core/list-item', { content }))
      )
    )
  })

  html = html.replace(/\[ia-data(\s[^\]]*)?\]/g, (_m, attrText = '') => {
    const named = api.shortcodeAttrs(attrText).named as Record<string, string>
    const attributes: Record<string, unknown> = { ...named }
    if ('cache' in named) attributes.cache = Number(named.cache) || 0
    count('ia-data')
    return store(api.createBlock('sc/ia-data', attributes))
  })

  for (const tag of ['ia-local-checker', 'navega-en-catala']) {
    html = html.replace(new RegExp(`\\[${tag}\\s*\\/?\\]`, 'g'), () => {
      count(tag)
      return store(api.createBlock(`sc/${tag}`))
    })
  }

  for (const tag of KEPT_SHORTCODES) {
    const enclosing = new RegExp(`\\[${tag}(\\s[^\\]]*)?\\][\\s\\S]*?\\[\\/${tag}\\]`, 'g')
    const single = new RegExp(`\\[${tag}(\\s[^\\]]*)?\\/?\\]`, 'g')
    const keep = (text: string) => {
      report.push(`[${tag}] mantingut en un bloc «Codi curt»: cal revisar-lo`)
      return store(api.createBlock('core/shortcode', { text }))
    }
    html = html.replace(enclosing, keep).replace(single, keep)
  }

  return html
}

/** Converts autop'd HTML, top-level element by element. */
function convertHtml(html: string, context: Context): Block[] {
  const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html')
  moveNamedAnchors(doc, context.report)
  markSmallText(doc, context.report)
  wrapStrayListItems(doc, context.report)
  const blocks: Block[] = []
  const pending: string[] = []

  const flush = () => {
    if (pending.length > 0) {
      blocks.push(...api.rawHandler({ HTML: pending.join('') }))
      pending.length = 0
    }
  }

  for (const node of Array.from(doc.body.childNodes)) {
    const special = node.nodeType === 1 ? convertElement(node as Element, context) : null
    if (special) {
      flush()
      blocks.push(...special)
    } else if (node.nodeType === 1) {
      pending.push((node as Element).outerHTML)
    } else if (node.textContent?.trim()) {
      pending.push(`<p>${node.textContent}</p>`)
    }
  }
  flush()

  return blocks
}

function classesOf(el: Element): string[] {
  return Array.from(el.classList)
}

/** Blocks for an element with legacy markup, or null to leave it to rawHandler. */
function convertElement(el: Element, context: Context): Block[] | null {
  const { report } = context
  const tag = el.tagName.toLowerCase()
  const classes = classesOf(el)

  const tokenOnly = tag === 'p' && /^\s*⟦sc:(\d+)⟧\s*$/.exec(el.innerHTML)
  if (tokenOnly) return [context.stored[Number(tokenOnly[1])]]

  if (classes.includes('req-flex')) {
    report.push('Fila req-flex convertida en columnes «Requadres»')
    const columns = Array.from(el.children).map((child) => {
      const inner = child.classList.contains('req-banner') ? child.innerHTML : child.outerHTML
      return api.createBlock('core/column', {}, convertHtml(api.autop(inner), context))
    })
    return [api.createBlock('core/columns', { className: 'is-style-requadres' }, columns)]
  }

  const avis = classes.map((c) => AVIS_CLASSES[c]).find(Boolean)
  if (avis) {
    report.push(`.${classes.find((c) => AVIS_CLASSES[c])} convertit en «Avís» (${avis})`)
    if (tag === 'p') {
      return api.rawHandler({ HTML: `<p>${el.innerHTML}</p>` }).map((b) => withClass(b, `is-style-${avis}`))
    }
    return [api.createBlock('core/group', { className: `is-style-${avis}` }, convertHtml(api.autop(el.innerHTML), context))]
  }

  if (tag === 'dl') return convertDefinitionList(el, context)

  // A quote's content goes through the converter too (WordPress would convert
  // it on its own, without the site's mappings); a trailing <cite> is its
  // citation.
  if (tag === 'blockquote') {
    const cite = el.lastElementChild?.tagName === 'CITE' ? el.lastElementChild : null
    cite?.remove()
    return [api.createBlock('core/quote', cite ? { citation: cite.innerHTML.trim() } : {}, convertHtml(api.autop(el.innerHTML), context))]
  }

  // Bold or italics around whole paragraphs (<em><p>…</p></em>): the
  // paragraphs, without it.
  if (INLINE_WRAPPERS.includes(tag) && el.querySelector('p, div, table, ul, ol, h1, h2, h3, h4, h5, h6, blockquote, dl')) {
    report.push(`<${tag}> al voltant de paràgrafs desfet`)
    return convertHtml(api.autop(el.innerHTML), context)
  }

  // <center>, and the old wiki's <div class="center">: the text, centred.
  if (tag === 'center' || (tag === 'div' && classes.includes('center') && !el.id)) {
    report.push(`<${tag === 'center' ? 'center' : 'div class="center"'}> convertit en text centrat`)
    return convertHtml(api.autop(el.innerHTML), context).map(centred)
  }

  if (classes.includes('download_section')) {
    report.push('.download_section desfet: el seu contingut queda com a blocs normals')
    return convertHtml(api.autop(el.innerHTML), context)
  }

  const table = tag === 'table' ? el : el.querySelector(':scope > table')
  if (table) {
    const tableClasses = classesOf(table)
    const match = tableClasses.map((c) => TABLE_CLASSES.find(([re]) => re.test(c))).find(Boolean)
    if (match) {
      const style = match[1]
      report.push(`Taula .${tableClasses.join('.')} → ${style ? `estil «${style}»` : 'estil per defecte'}`)
      // rawHandler keeps the table's class; the legacy one goes.
      return api.rawHandler({ HTML: table.outerHTML }).map((b) => (b.name === 'core/table' ? withClass(b, style ? `is-style-${style}` : undefined) : b))
    }
  }

  if (tag === 'p' && classes.includes('center')) {
    return api.rawHandler({ HTML: `<p>${el.innerHTML}</p>` }).map((b) =>
      b.name === 'core/paragraph' ? { ...b, attributes: { ...b.attributes, style: { typography: { textAlign: 'center' } } } } : b
    )
  }

  // Any other wrapper div. With an id, it may be a link target (or a CSS or
  // script hook): it becomes a group with that anchor. Without one, only its
  // content matters.
  if (tag === 'div' || HTML5_WRAPPERS.includes(tag)) {
    const inner = convertHtml(api.autop(el.innerHTML), context)
    if (el.id) {
      report.push(`<div id="${el.id}"> convertit en grup amb l'ancoratge «${el.id}»`)
      return [api.createBlock('core/group', { anchor: el.id }, inner)]
    }
    const lost = classes.filter((c) => !c.startsWith('wp-'))
    report.push(`Contenidors <${tag}> desfets (el seu contingut queda com a blocs)${lost.length > 0 ? `; classes perdudes: ${lost.join(' ')}` : ''}`)
    return inner
  }

  return null
}

/**
 * A definition list. With terms (<dt>), a list with each term in bold before
 * its description. Without (only <dd>, how the old wiki indented a line with
 * ":"), its lines in a group with the Sagnat style.
 */
function convertDefinitionList(el: Element, context: Context): Block[] {
  const children = Array.from(el.children)
  const hasTerms = children.some((c) => c.tagName === 'DT' && c.textContent?.trim())

  if (!hasTerms) {
    context.report.push('Llistes de definició sense termes (el sagnat de la wiki antiga) convertides en grups amb l\'estil «Sagnat»')
    const inner = children.filter((c) => c.tagName === 'DD').flatMap((dd) => convertHtml(api.autop(dd.innerHTML), context))
    return [api.createBlock('core/group', { className: 'is-style-sagnat' }, inner)]
  }

  context.report.push('Llistes de definició convertides en llistes, amb el terme en negreta')
  const items: string[] = []
  let term: string | null = null
  const descriptions: string[] = []
  const flush = () => {
    if (term !== null) items.push(descriptions.length > 0 ? `<strong>${term}</strong>: ${descriptions.join(' ')}` : `<strong>${term}</strong>`)
    else items.push(...descriptions)
    term = null
    descriptions.length = 0
  }
  for (const child of children) {
    if (child.tagName === 'DT') {
      flush()
      term = child.innerHTML.trim()
    } else if (child.tagName === 'DD') {
      descriptions.push(child.innerHTML.trim())
    }
  }
  flush()

  return [api.createBlock('core/list', {}, items.map((content) => api.createBlock('core/list-item', { content })))]
}

/**
 * Classic tables had the browser's automatic layout: each column as wide as
 * its content needs. WordPress's conversion turns on "Fixed width table
 * cells", which makes the columns equal (a short label column takes half the
 * table), so converted tables go back to automatic.
 */
function automaticTableLayout(block: Block): Block {
  const inner = block.innerBlocks.map(automaticTableLayout)
  return block.name === 'core/table' ? { ...block, attributes: { ...block.attributes, hasFixedLayout: false }, innerBlocks: inner } : { ...block, innerBlocks: inner }
}

function centred(block: Block): Block {
  if (block.name !== 'core/paragraph' && block.name !== 'core/heading') return block
  const style = (block.attributes.style ?? {}) as Record<string, Record<string, unknown>>
  return { ...block, attributes: { ...block.attributes, style: { ...style, typography: { ...style.typography, textAlign: 'center' } } } }
}

/** List items outside any list (pasted fragments): runs of them get a <ul>. */
function wrapStrayListItems(doc: Document, report: string[]): void {
  for (const li of Array.from(doc.querySelectorAll('li'))) {
    const parent = li.parentElement
    if (!li.isConnected || !parent || ['UL', 'OL', 'MENU'].includes(parent.tagName)) continue
    const ul = doc.createElement('ul')
    li.before(ul)
    let node: ChildNode | null = li
    while (node && (node.nodeName === 'LI' || (node.nodeType === 3 && !node.textContent?.trim()))) {
      const next: ChildNode | null = node.nextSibling
      ul.append(node)
      node = next
    }
    report.push('Elements de llista solts posats dins d\'una llista')
  }
}

/**
 * Old-style anchors, <a name="x"></a>, become the id (the block's anchor) of
 * the heading they mark, so links to #x keep working; the <a> goes.
 */
function moveNamedAnchors(doc: Document, report: string[]): void {
  for (const a of Array.from(doc.querySelectorAll('a[name]'))) {
    const name = a.getAttribute('name') ?? ''
    const heading = a.closest('h1, h2, h3, h4, h5, h6') ?? nextHeading(a)

    if (heading && !heading.id && name) {
      heading.id = name
      report.push('Ancoratges <a name> passats al títol que marquen')
    } else if (name) {
      report.push(`Ancoratge «${name}» sense títol al costat: s'ha perdut, cal revisar els enllaços que hi apunten`)
    }

    const parent = a.parentElement
    a.replaceWith(...Array.from(a.childNodes))
    // autop wraps a lone anchor in its own paragraph.
    if (parent && parent.tagName === 'P' && !parent.textContent?.trim() && parent.children.length === 0) parent.remove()
  }
}

/**
 * .petit (small print: file sizes under download links) becomes the "Text
 * petit" format. Its text is marked with tokens, which survive rawHandler and
 * become <span class="sc-petit"> at the end; a .petit div, a line of its
 * own, gets a line break before it.
 */
function markSmallText(doc: Document, report: string[]): void {
  for (const el of Array.from(doc.querySelectorAll('.petit'))) {
    const marked = doc.createTextNode(`\u27E6petit\u27E7${el.textContent ?? ''}\u27E6/petit\u27E7`)
    if (el.tagName === 'P') {
      el.replaceChildren(marked)
      el.classList.remove('petit')
    } else {
      const parts: Node[] = [marked]
      if (el.tagName === 'DIV' && el.previousSibling) parts.unshift(doc.createElement('br'))
      el.replaceWith(...parts)
    }
    report.push('.petit convertit en «Text petit»')
  }
  // autop leaves empty paragraphs where block-level .petit divs were.
  for (const p of Array.from(doc.querySelectorAll('td p, th p'))) {
    if (!p.textContent?.trim() && p.children.length === 0) p.remove()
  }
}

function nextHeading(el: Element): Element | null {
  // The anchor alone (or in its own paragraph) right before the heading.
  const holder = el.parentElement?.tagName === 'P' && !el.parentElement.textContent?.replace(el.textContent ?? '', '').trim() ? el.parentElement : el
  const next = holder.nextElementSibling
  return next && /^H[1-6]$/.test(next.tagName) ? next : null
}

function withClass(block: Block, className: string | undefined): Block {
  return { ...block, attributes: { ...block.attributes, className } }
}
