/// <reference types="node" />
import { readFileSync, readdirSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { patternMarkup, validateMarkup } from './blocks/validate'

const patternsDir = resolve(__dirname, '../../block-patterns')
const patterns = readdirSync(patternsDir).filter((file) => file.endsWith('.php'))

describe('block patterns', () => {
  it('finds the pattern files', () => {
    expect(patterns.length).toBeGreaterThan(0)
  })

  it.each(patterns)('%s is valid block markup', (file) => {
    const markup = patternMarkup(readFileSync(resolve(patternsDir, file), 'utf8'))
    expect(markup).not.toBe('')
    expect(validateMarkup(markup)).toEqual([])
  })

  it.each(patterns)('%s has no PHP after its header', (file) => {
    const markup = patternMarkup(readFileSync(resolve(patternsDir, file), 'utf8'))
    expect(markup).not.toContain('<?php')
  })
})

describe('spec fixtures', () => {
  // SoftcatalaBot's tests keep a copy of this file: update it there too.
  it('resum-mensual.html is valid block markup', () => {
    const markup = readFileSync(resolve(__dirname, '../../docs/specs/fixtures/resum-mensual.html'), 'utf8')
    expect(validateMarkup(markup)).toEqual([])
  })
})

describe('editor guide', () => {
  // Published as a page: it uses the blocks and styles it documents.
  it('editor-de-blocs.html is valid block markup', () => {
    const markup = readFileSync(resolve(__dirname, '../../docs/editors/editor-de-blocs.html'), 'utf8')
    expect(validateMarkup(markup)).toEqual([])
  })

  it('escapes every shortcode it names', () => {
    // On the page, [caption] would run as a shortcode and [1. nota] become a
    // sc-footnotes note. Shortcodes are named as [[tag]], WordPress's escape;
    // sc-footnotes has none, so its syntax is described in words.
    const markup = readFileSync(resolve(__dirname, '../../docs/editors/editor-de-blocs.html'), 'utf8')
    const unescaped = markup.replace(/\[\[[a-z-]+\]\]/g, '').match(/\[[^\]\n]*\]/g) ?? []
    expect(unescaped).toEqual([])
  })
})

describe('validateMarkup', () => {
  it('accepts a styled paragraph', () => {
    const markup = '<!-- wp:paragraph {"className":"is-style-avis-info"} -->\n<p class="is-style-avis-info">Hola</p>\n<!-- /wp:paragraph -->'
    expect(validateMarkup(markup)).toEqual([])
  })

  it('reports markup the block would not save', () => {
    const markup = '<!-- wp:paragraph -->\n<div>No és un paràgraf</div>\n<!-- /wp:paragraph -->'
    expect(validateMarkup(markup)).not.toEqual([])
  })

  it('reports markup in an older saved format', () => {
    const markup = '<!-- wp:heading -->\n<h2>Sense classe</h2>\n<!-- /wp:heading -->'
    expect(validateMarkup(markup)).toEqual([expect.objectContaining({ message: expect.stringContaining('current saved format') })])
  })

  it('reports blocks outside the allowed list', () => {
    const markup = '<!-- wp:spacer -->\n<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>\n<!-- /wp:spacer -->'
    expect(validateMarkup(markup)).toEqual([
      expect.objectContaining({ block: 'core/spacer', message: expect.stringContaining('allowed list') }),
    ])
  })

  it('reports Custom HTML unless allowed', () => {
    const markup = '<!-- wp:html -->\n<div>x</div>\n<!-- /wp:html -->'
    expect(validateMarkup(markup)).toHaveLength(1)
    expect(validateMarkup(markup, true)).toEqual([])
  })

  it('reports unregistered block styles', () => {
    const markup = '<!-- wp:paragraph {"className":"is-style-inventat"} -->\n<p class="is-style-inventat">Hola</p>\n<!-- /wp:paragraph -->'
    expect(validateMarkup(markup)).toEqual([
      expect.objectContaining({ message: 'unregistered block style "inventat"' }),
    ])
  })

  it('accepts the theme\'s server-rendered blocks', () => {
    expect(validateMarkup('<!-- wp:sc/index {"titol":"Sumari"} /-->')).toEqual([])
    expect(validateMarkup('<!-- wp:sc/ia-data {"url":"https://example.org/d.json","format":"graph"} /-->')).toEqual([])
  })

  it('reports unknown block types', () => {
    const markup = '<!-- wp:softcatala/inexistent /-->'
    expect(validateMarkup(markup)).toEqual([
      expect.objectContaining({ block: 'softcatala/inexistent', message: 'unknown block type' }),
    ])
  })
})
