import { describe, expect, it } from 'vitest'
import { convertClassic } from '../tools/convert-classic'
import { validateMarkup } from './blocks/validate'

function convert(raw: string) {
  let n = 0
  const result = convertClassic(raw, { newId: () => `nota-${++n}` })
  // Whatever it produces must be what the editor would save.
  expect(validateMarkup(result.markup)).toEqual([])
  return result
}

describe('convertClassic', () => {
  it('converts plain classic content like the editor does', () => {
    const { markup } = convert('Primer paràgraf.\n\nSegon, amb <strong>negreta</strong>.\n\n<h2>Títol</h2>\n<ul>\n<li>u</li>\n<li>dos</li>\n</ul>')

    expect(markup).toContain('<p>Primer paràgraf.</p>')
    expect(markup).toContain('<!-- wp:heading -->\n<h2 class="wp-block-heading">Títol</h2>')
    expect(markup).toContain('<!-- wp:list -->')
  })

  it('turns sc-footnotes into core footnotes', () => {
    const { markup, footnotes, report } = convert('Cal saber-ho[1. Pot variar segons la versió.] abans.\n\nI això també[2. Una altra nota.].')

    expect(footnotes).toEqual([
      { id: 'nota-1', content: 'Pot variar segons la versió.' },
      { id: 'nota-2', content: 'Una altra nota.' },
    ])
    expect(markup).toContain('Cal saber-ho<sup data-fn="nota-1" class="fn"><a href="#nota-1" id="nota-1-link">*</a></sup> abans.')
    expect(markup.trimEnd().endsWith('<!-- wp:footnotes /-->')).toBe(true)
    expect(report.join('\n')).toContain('2 notes al peu')
  })

  it('turns legacy callouts into notices', () => {
    const { markup } = convert('<p class="fons-gris">Una nota al marge.</p>\n<div class="messagebox">\n\nPrimer.\n\nSegon.\n\n</div>')

    expect(markup).toContain('<p class="is-style-avis-neutre">Una nota al marge.</p>')
    expect(markup).toContain('<!-- wp:group {"className":"is-style-avis-info"} -->')
    expect(markup).toContain('<p>Segon.</p>')
  })

  it('maps legacy table classes to table styles', () => {
    const table = (cls: string) => `<table class="${cls}"><tbody><tr><td>Llicència</td><td>GPL</td></tr></tbody></table>`
    expect(convert(table('taula-2col')).markup).toContain('is-style-dues-columnes')

    const plain = convert(table('prettytable')).markup
    expect(plain).toContain('<!-- wp:table')
    expect(plain).not.toContain('prettytable')
  })

  it('turns the theme shortcodes into its blocks', () => {
    const { markup } = convert(
      'Intro.\n\n[ia-data url="https://example.org/d.json" format="graph" cache="60"]\n\n[ia-local-checker]\n\n[navega-en-catala]'
    )

    expect(markup).toContain('<!-- wp:sc/ia-data {"url":"https://example.org/d.json","format":"graph","cache":60} /-->')
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(markup).toContain('<!-- wp:sc/navega-en-catala /-->')
  })

  it('turns the list shortcodes into Amb icones lists', () => {
    const { markup, report } = convert(
      '[llista-links]\nRecomanacions|#recomanacions\nContrasenyes|#contrasenyes\n[/llista-links]\n\n[llista-icones color="blancgris"]\nlanguage|Traductors|Només cal un bon català.\n[/llista-icones]'
    )

    expect(markup).toContain('<ul class="wp-block-list is-style-amb-icones"><!-- wp:list-item -->\n<li><a href="#recomanacions">Recomanacions</a></li>')
    expect(markup).toContain('<li><strong>Traductors</strong>: Només cal un bon català.</li>')
    expect(report.join('\n')).toContain('les icones de cada element es perden')
  })

  it('keeps other shortcodes in a Shortcode block and says so', () => {
    const { markup, report } = convert('[cards]\n[card title="A" link="/a/"]Una[/card]\n[/cards]\n\n[insert page="123"]')

    expect(markup).toContain('<!-- wp:shortcode -->\n[cards]')
    expect(markup).toContain('[insert page="123"]')
    expect(report.filter((r) => r.includes('cal revisar-lo'))).toHaveLength(2)
  })

  it('rebuilds the ia-local requirements row as Requadres columns', () => {
    const { markup } = convert(
      '<div class="req-flex">\n<div class="req-banner">\n<p class="req-icon">Què us cal?</p>\n<ul>\n<li>16 GB</li>\n</ul>\n</div>\n[ia-local-checker]\n<div class="req-banner">\nRecorregut\n</div>\n</div>'
    )

    expect(markup).toContain('<!-- wp:columns {"className":"is-style-requadres"} -->')
    expect(markup.match(/<!-- wp:column -->/g)).toHaveLength(3)
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
  })

  it('maps small and centred paragraphs, and drops inline styles', () => {
    const { markup, report } = convert('<p class="petit">Lletra petita.</p>\n<p class="center">Centrat.</p>\n<p style="color:#ff0000">Vermell a mà.</p>')

    expect(markup).toContain('<p class="has-petit-font-size">Lletra petita.</p>')
    expect(markup).toContain('<p class="has-text-align-center">Centrat.</p>')
    expect(markup).not.toContain('#ff0000')
    expect(report.join('\n')).toContain('1 atributs style descartats')
  })

  it('moves old named anchors onto the heading they mark', () => {
    const { markup } = convert('[llista-links]\nRecomanacions|#recomanacions\n[/llista-links]\n<a name="recomanacions"></a>\n<h2>Recomanacions generals</h2>\nText.')

    expect(markup).toContain('<!-- wp:heading {"anchor":"recomanacions"} -->\n<h2 id="recomanacions" class="wp-block-heading">Recomanacions generals</h2>')
    expect(markup).not.toContain('name=')
  })

  it('unwraps plain wrapper divs, so what they hold is converted too', () => {
    const { markup } = convert('<div id="sct-ia">\n\nIntro.\n\n<div class="req-flex">\n<div class="req-banner">A</div>\n[ia-local-checker]\n</div>\n\n</div>')

    expect(markup).not.toContain('wp:html')
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(markup).toContain('is-style-requadres')
  })

  it('reports repeated findings once, with a count', () => {
    const table = '<table class="prettytable"><tbody><tr><td>a</td></tr></tbody></table>'
    const { report } = convert(`${table}\n\n${table}`)

    expect(report).toContain('Taula .prettytable → estil per defecte (×2)')
  })
})
