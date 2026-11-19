/**
 * The editor's "Convert to blocks", on a Classic block, runs the theme's
 * converter (classic-converter.ts) instead of WordPress's alone: footnotes,
 * the theme's shortcodes, legacy classes and old anchors come out as on the
 * command line (`npm run convert`). The notes go to the post's `footnotes`
 * meta, and what needs a look shows as a notice.
 *
 * WordPress's button has no hook: it replaces the Classic block with
 * rawHandler()'s blocks through the block editor's replaceBlocks action, so
 * that action is wrapped. Only a replacement that is exactly WordPress's own
 * conversion of one Classic block is changed (not a transform, not an undo,
 * not anything else), and if the converter fails, WordPress's result goes
 * through untouched.
 */

import { convertClassic, type Block as BlockInstance, type Footnote, type Options, type WordPressApi } from './classic-converter'

type ReplaceBlocks = (clientIds: string | string[], blocks: BlockInstance | BlockInstance[], ...rest: unknown[]) => unknown

export interface ConvertWordPress {
  blocks: Pick<WordPressApi, 'createBlock' | 'parse' | 'rawHandler' | 'serialize'>
  autop: { autop: WordPressApi['autop'] }
  shortcode: { attrs: WordPressApi['shortcodeAttrs'] }
  data: {
    select: (store: string) => any // eslint-disable-line @typescript-eslint/no-explicit-any
    dispatch: (store: string) => any // eslint-disable-line @typescript-eslint/no-explicit-any
  }
}

type Convert = (raw: string, api: WordPressApi, options?: Options) => ReturnType<typeof convertClassic>

const NOTICE_ID = 'sc-conversio-classica'

/**
 * The theme's blocks for replacing `clientIds` with `blocks`, or null to leave
 * the replacement alone.
 */
export function siteConversion(
  wp: ConvertWordPress,
  clientIds: string | string[],
  blocks: BlockInstance | BlockInstance[],
  convert: Convert = convertClassic
): BlockInstance[] | null {
  const ids = Array.isArray(clientIds) ? clientIds : [clientIds]
  if (ids.length !== 1) return null

  const block = wp.data.select('core/block-editor').getBlock(ids[0]) as BlockInstance | null
  if (block?.name !== 'core/freeform') return null

  // The Classic block's button: rawHandler() on the block, serialized.
  const { serialize, rawHandler, parse } = wp.blocks
  const raw = serialize(block)
  const incoming = Array.isArray(blocks) ? blocks : [blocks]
  if (serialize(incoming) !== serialize(rawHandler({ HTML: raw }))) return null

  const api: WordPressApi = { ...wp.blocks, autop: wp.autop.autop, shortcodeAttrs: wp.shortcode.attrs }
  const result = convert(String(block.attributes.content ?? ''), api)

  if (result.footnotes.length > 0) addFootnotes(wp, result.footnotes)
  if (result.report.length > 0) {
    wp.data.dispatch('core/notices').createInfoNotice(`Convertit a blocs amb els ajustos del web: ${result.report.join(' · ')}`, {
      id: NOTICE_ID,
      isDismissible: true,
    })
  }

  return parse(result.markup)
}

function addFootnotes(wp: ConvertWordPress, footnotes: Footnote[]): void {
  const editor = wp.data.select('core/editor')
  const meta = (editor.getEditedPostAttribute('meta') ?? {}) as Record<string, unknown>
  let existing: Footnote[] = []
  try {
    existing = JSON.parse(String(meta.footnotes || '[]'))
  } catch {
    existing = []
  }
  wp.data.dispatch('core/editor').editPost({ meta: { footnotes: JSON.stringify([...existing, ...footnotes]) } })
}

export function addClassicConversion(wp: ConvertWordPress, convert: Convert = convertClassic): void {
  const actions = wp.data.dispatch('core/block-editor') as { replaceBlocks: ReplaceBlocks }
  const replaceBlocks = actions.replaceBlocks
  if (typeof replaceBlocks !== 'function') return

  actions.replaceBlocks = (clientIds, blocks, ...rest) => {
    let converted: BlockInstance[] | null = null
    try {
      converted = siteConversion(wp, clientIds, blocks, convert)
    } catch (error) {
      console.error('Softcatalà: la conversió del bloc clàssic ha fallat; es fa servir la de WordPress.', error)
    }
    return replaceBlocks(clientIds, converted ?? blocks, ...rest)
  }
}
