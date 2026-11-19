/**
 * The classic converter (src/js/classic-converter.ts) outside the editor, with
 * WordPress's npm packages: for `npm run convert` (convert-classic-cli.mjs) and
 * the tests. Needs a DOM (jsdom).
 */

import { autop } from '@wordpress/autop'
import { createBlock, parse, rawHandler, serialize } from '@wordpress/blocks'
import { attrs } from '@wordpress/shortcode'
import { convertClassic as convert, type Conversion, type Options, type WordPressApi } from '../src/js/classic-converter'
import { ensureRegistered } from './register-blocks'

export type { Conversion, Footnote, Options } from '../src/js/classic-converter'

export const wordpressApi = { autop, createBlock, parse, rawHandler, serialize, shortcodeAttrs: attrs } as unknown as WordPressApi

export function convertClassic(raw: string, options: Options = {}): Conversion {
  ensureRegistered()
  return convert(raw, wordpressApi, options)
}
