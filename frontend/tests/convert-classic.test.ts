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
    expect(markup).toContain('Cal saber-ho<sup data-fn="nota-1" class="fn"><a href="#nota-1" id="nota-1-link">1</a></sup> abans.')
    expect(markup).toContain('<a href="#nota-2" id="nota-2-link">2</a></sup>')
    expect(markup.trimEnd().endsWith('<!-- wp:footnotes /-->')).toBe(true)
    expect(report.join('\n')).toContain('2 notes al peu')
  })

  it('turns legacy callouts into notices', () => {
    const { markup } = convert('<p class="fons-gris">Una nota al marge.</p>\n<div class="messagebox">\n\nPrimer.\n\nSegon.\n\n</div>')

    expect(markup).toContain('<p class="is-style-avis-neutre">Una nota al marge.</p>')
    expect(markup).toContain('<!-- wp:group {"className":"is-style-avis-info"} -->')
    expect(markup).toContain('<p>Segon.</p>')
  })

  it('keeps tables on automatic layout, as they were (columns sized by their content)', () => {
    const { markup } = convert('<table class="prettytable"><tbody><tr><td><tt>Text original</tt></td><td><tt>A long sentence that should get the room.</tt></td></tr></tbody></table>\n\n<blockquote><table><tbody><tr><td>a</td></tr></tbody></table></blockquote>')

    expect(markup).not.toContain('has-fixed-layout')
    expect(markup.match(/"hasFixedLayout":false/g)).toHaveLength(2)
  })

  it('maps legacy table classes to table styles', () => {
    const table = (cls: string) => `<table class="${cls}"><tbody><tr><td>Llicència</td><td>GPL</td></tr></tbody></table>`
    expect(convert(table('taula-2col')).markup).toContain('is-style-dues-columnes')

    expect(convert(table('prettytable')).markup).toContain('is-style-guia-estil')

    const plain = convert(table('wikitable')).markup
    expect(plain).toContain('<!-- wp:table')
    expect(plain).not.toContain('wikitable')
    expect(plain).not.toContain('is-style')
  })

  it('turns the theme shortcodes into its blocks', () => {
    const { markup } = convert(
      'Intro.\n\n[ia-data url="https://example.org/d.json" format="graph" cache="60"]\n\n[ia-local-checker]\n\n[navega-en-catala]'
    )

    expect(markup).toContain('<!-- wp:sc/ia-data {"url":"https://example.org/d.json","format":"graph","cache":60} /-->')
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(markup).toContain('<!-- wp:sc/navega-en-catala /-->')
  })

  it('turns [llista-links] into an Amb icones list', () => {
    const { markup } = convert('[llista-links]\nRecomanacions|#recomanacions\nContrasenyes|#contrasenyes\n[/llista-links]')

    expect(markup).toContain('<ul class="wp-block-list is-style-amb-icones"><!-- wp:list-item -->\n<li><a href="#recomanacions">Recomanacions</a></li>')
  })

  it('turns [llista-icones] into the icon list block, icons and colour kept', () => {
    const { markup, report } = convert('[llista-icones color="blancgris"]\nlanguage||La satisfacció.\nsort-alpha-asc|Traductors|Només cal un bon català.\nlínia trencada\n[/llista-icones]')

    const data = JSON.parse(/<!-- wp:sc\/llista-icones (\{.*\}) \/-->/.exec(markup)![1]).data
    expect(data).toMatchObject({
      color: 'blancgris',
      items: 2,
      items_0_icon: 'language',
      items_0_title: '',
      items_1_icon: 'sort-alpha-asc',
      items_1_title: 'Traductors',
      items_1_text: 'Només cal un bon català.',
      _items: 'field_sc_llista_icones_items',
    })
    expect(report.join('\n')).toContain('1 línies sense les 3 parts')
  })

  it("reads shortcode lines ended with the Classic block's <br />", () => {
    const { markup, report } = convert('[llista-icones]<br />\nlanguage|Llengua|Text.<br />\ngroup|Grup|Més.<br />\n[/llista-icones]\n\n[llista-links]<br />\nA|#a<br />\n[/llista-links]')

    const data = JSON.parse(/<!-- wp:sc\/llista-icones (\{.*\}) \/-->/.exec(markup)![1]).data
    expect(data).toMatchObject({ items: 2, items_0_text: 'Text.', items_1_text: 'Més.' })
    expect(markup).toContain('<li><a href="#a">A</a></li>')
    expect(report.join('\n')).not.toContain('sense les 3 parts')
  })

  it('leaves no empty paragraphs around shortcodes the Classic block wrapped in one', () => {
    const { markup, report } = convert(
      '<h2>Per què?</h2>\n<p>[llista-icones color="blancgris"]<br />\nlanguage|Llengua|Text.<br />\n[/llista-icones]</p>\n<h2>Qui?</h2>\n<p>Intro.</p>\n<p>[ia-local-checker]</p>\n<p>&nbsp;</p>\n<p><br /></p>'
    )

    expect(markup).not.toMatch(/<p>(\s|&nbsp;|<br>)*<\/p>/)
    expect(markup.match(/<!-- wp:paragraph/g)).toHaveLength(1)
    expect(markup).toContain('wp:sc/llista-icones')
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(report.join('\n')).toContain('Paràgrafs buits eliminats')
  })

  it('leaves no empty paragraph where an image was wrapped in divs', () => {
    const { markup } = convert('Captura:\n<div class="center">\n<div class="floatnone"><a href="/a.png"><img class="aligncenter size-medium wp-image-1" src="/a-300x193.png" alt="" width="300" height="193" /></a></div>\n</div>\nUna altra captura:')

    expect(markup).toContain('wp:image')
    expect(markup).not.toMatch(/<p>(\s|&nbsp;|<br>)*<\/p>/)
  })

  it('keeps what WordPress converts into an invalid block in a Classic block, and says so', () => {
    const nestedTable = '<table><tbody><tr><td>Ponència</td><td>\n<table border="0"><tbody><tr><td>PDF</td></tr></tbody></table>\n</td></tr></tbody></table>'
    const nestedLink = '<ul>\n<li><a href="http://a.org"><a href="http://b.org">B</a></a></li>\n</ul>'
    const { markup, report } = convert(`Abans.\n\n${nestedTable}\n\n${nestedLink}\n\nDesprés.`)

    // A Classic block is saved as plain HTML between the other blocks.
    expect(markup).not.toContain('wp:table')
    expect(markup).not.toContain('wp:list')
    expect(markup).not.toContain('wp:html')
    expect(markup).toContain('<td>PDF</td>')
    expect(markup).toContain('<a href="http://b.org">B</a>')
    expect(markup).toContain('<p>Després.</p>')
    expect(report.join('\n')).toContain('2 blocs que no serien vàlids')
  })

  it('warns about scripts and iframes, which blocks can lose', () => {
    const { report } = convert('Text.\n\n<script src="/a.js"></script>\n\n<iframe src="https://example.org/"></iframe>')

    expect(report.join('\n')).toContain('1 scripts (<script>)')
    expect(report.join('\n')).toContain('1 iframes (<iframe>)')
  })

  it("turns the old wiki's indentation (<dl> with only <dd>) into a Sagnat group", () => {
    const { markup } = convert('Ordre:\n<dl>\n \t<dd>Subjecte + Verb + Complements</dd>\n \t<dd><tt>Correcte</tt>: <b>Només</b> es pot inserir.\n<dl><dd>Més endins.</dd></dl></dd>\n</dl>\nDesprés.')

    expect(markup).toContain('<!-- wp:group {"className":"is-style-sagnat"} -->')
    expect(markup).toContain('<p>Subjecte + Verb + Complements</p>')
    expect(markup.match(/is-style-sagnat/g)!.length).toBeGreaterThanOrEqual(4)
    expect(markup).not.toContain('<dl')
    expect(markup).not.toContain('wp:html')
  })

  it('turns a definition list with terms into a list with the term in bold', () => {
    const { markup } = convert('<dl>\n<dt><a href="/po">mailman.po</a></dt>\n<dd>Els missatges.</dd>\n<dt>templates/ca</dt>\n<dd>Les plantilles.</dd>\n</dl>\n<dl><dt></dt><dd><em>Sense terme.</em></dd></dl>')

    expect(markup).toContain('<li><strong><a href="/po">mailman.po</a></strong>: Els missatges.</li>')
    expect(markup).toContain('<li><strong>templates/ca</strong>: Les plantilles.</li>')
    // An empty <dt> is the wiki's indentation too.
    expect(markup).toContain('is-style-sagnat')
  })

  it('centres <center> and div.center, and puts stray list items in a list', () => {
    const { markup, report } = convert('<center>\n\nCentrat.\n\n</center>\n<div class="center">També.</div>\n<p>Abans.</p>\n<li>U</li>\n<li>Dos</li>\n<p>Després.</p>')

    expect(markup.match(/has-text-align-center/g)).toHaveLength(2)
    expect(markup).not.toContain('wp:html')
    expect(markup).toContain('<!-- wp:list -->\n<ul class="wp-block-list"><!-- wp:list-item -->\n<li>U</li>')
    expect(report.join('\n')).not.toContain('classes perdudes: center')
  })

  it('converts what is inside quotes, unwraps HTML5 wrappers and bold around paragraphs', () => {
    const { markup } = convert(
      '<blockquote><p>Demanem:</p>\n<dl><dd><em>Que l\'administració no imposi programes.</em></dd></dl>\n<cite>Manifest</cite></blockquote>\n<footer class="article-footer">Publicat per Ara.cat</footer>\n<em>\n<p>Primer.</p>\n<p>Segon.</p>\n</em>'
    )

    expect(markup).toContain('<!-- wp:quote')
    expect(markup).toContain('is-style-sagnat')
    expect(markup).toContain('<cite>Manifest</cite>')
    expect(markup).toContain('<p>Publicat per Ara.cat</p>')
    expect(markup).toContain('<p>Segon.</p>')
    expect(markup).not.toContain('wp:html')
  })

  it('flattens block content inside list items (an indented command) without empty lines', () => {
    const { markup } = convert(
      "<ol>\n<li>Amb el navegador.</li>\n<li>Amb el Bazaar, escrivint l'ordre següent en un terminal:\n<dl>\n<dd>\n<pre>bzr branch lp:~mailman-l10n-ca/mailman/stable-sc</pre>\n</dd>\n</dl>\n</li>\n<li><p>Primer.</p>\n<p>Segon.</p></li>\n</ol>"
    )

    expect(markup).toContain("<li>Amb el Bazaar, escrivint l'ordre següent en un terminal:<br><code>bzr branch lp:~mailman-l10n-ca/mailman/stable-sc</code></li>")
    expect(markup).toContain('<li>Primer.<br>Segon.</li>')
    expect(markup).not.toMatch(/<br>\s*<br>|<br><\/li>|<li><br>/)
    expect(markup).not.toContain('<dl')
  })

  it('leaves no line break at the start or end of a list item', () => {
    const { markup } = convert('<blockquote>\n<ul>\n<li>\nEl programari lliure\nés gratuït.\n</li>\n<li>Últim.<br />\n</li>\n<li>Amb negreta buida.\n<b></b></li>\n</ul>\n</blockquote>')

    // A line break inside the text was a <br> on the old page too (autop).
    expect(markup).toContain('<li>El programari lliure<br>és gratuït.</li>')
    expect(markup).toContain('<li>Últim.</li>')
    expect(markup).toContain('<li>Amb negreta buida.</li>')
  })

  it("keeps an author's own line breaks in a list item that also has a block", () => {
    const { markup } = convert('<ul>\n<li>Horari:<br />matí<br />tarda\n<pre>9-13 h</pre></li>\n</ul>')

    expect(markup).toContain('<li>Horari:<br>matí<br>tarda<br><code>9-13 h</code></li>')
  })

  it('keeps forms in Custom HTML and flags them', () => {
    // Custom HTML is allowed only for users with unfiltered_html, so the
    // allowed-list check doesn't apply here.
    const { markup, report } = convertClassic('<form action="https://example.org/" method="post"><input name="email" type="email" /><input type="submit" value="Envia" /></form>')
    expect(validateMarkup(markup).filter((p) => !(p.block === 'core/html' && /allowed list/.test(String(p.message))))).toEqual([])

    expect(markup).toContain('<!-- wp:html -->')
    expect(report.join('\n')).toContain('1 formularis (<form>)')
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

    expect(markup).toContain('<p><span class="sc-petit">Lletra petita.</span></p>')
    expect(markup).toContain('<p class="has-text-align-center">Centrat.</p>')
    expect(markup).not.toContain('#ff0000')
    expect(report.join('\n')).toContain('1 atributs style descartats')
  })

  it('turns .petit in table cells into Text petit on its own line', () => {
    const { markup } = convert('<table border="0"><tbody><tr><td><a href="/a.pdf">Ponència</a> <div class="petit" align="center">(32 KB)</div></td></tr></tbody></table>')

    expect(markup).toContain('<br><span class="sc-petit">(32 KB)</span>')
    expect(markup).not.toContain('<p></p>')
  })

  it('moves old named anchors onto the heading they mark', () => {
    const { markup } = convert('[llista-links]\nRecomanacions|#recomanacions\n[/llista-links]\n<a name="recomanacions"></a>\n<h2>Recomanacions generals</h2>\nText.')

    expect(markup).toContain('<!-- wp:heading {"anchor":"recomanacions"} -->\n<h2 id="recomanacions" class="wp-block-heading">Recomanacions generals</h2>')
    expect(markup).not.toContain('name=')
  })

  it('unwraps plain wrapper divs, so what they hold is converted too', () => {
    const { markup, report } = convert('<div class="contingut-footer">\n\nIntro.\n\n<div class="req-flex">\n<div class="req-banner">A</div>\n[ia-local-checker]\n</div>\n\n</div>')

    expect(markup).not.toContain('wp:html')
    expect(markup).not.toContain('wp:group')
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(markup).toContain('is-style-requadres')
    expect(report.join('\n')).toContain('classes perdudes: contingut-footer')
  })

  it('keeps a div with an id as a group with that anchor, so links to it work', () => {
    const { markup } = convert('<div id="embeddings-report">\n[ia-data url="https://example.org/d.json"]\n</div>')

    expect(markup).toContain('<!-- wp:group {"anchor":"embeddings-report"} -->\n<div id="embeddings-report" class="wp-block-group">')
    expect(markup).toContain('<!-- wp:sc/ia-data {"url":"https://example.org/d.json"} /-->')
  })

  it('reports repeated findings once, with a count', () => {
    const table = '<table class="wikitable"><tbody><tr><td>a</td></tr></tbody></table>'
    const { report } = convert(`${table}\n\n${table}`)

    expect(report).toContain('Taula .wikitable → estil per defecte (×2)')
  })
})
