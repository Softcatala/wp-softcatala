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

  it('reports unknown block types', () => {
    const markup = '<!-- wp:softcatala/inexistent /-->'
    expect(validateMarkup(markup)).toEqual([
      expect.objectContaining({ block: 'softcatala/inexistent', message: 'unknown block type' }),
    ])
  })
})
