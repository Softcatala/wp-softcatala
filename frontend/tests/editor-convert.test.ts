import { describe, expect, it, vi } from 'vitest'
import type { Block as BlockInstance } from '../src/js/classic-converter'
import { addClassicConversion, siteConversion, type ConvertWordPress } from '../src/js/editor-convert'
import { wordpressApi } from '../tools/convert-classic'
import { ensureRegistered } from '../tools/register-blocks'
import { validateMarkup } from './blocks/validate'

ensureRegistered()
const { createBlock, rawHandler, serialize } = wordpressApi

const CLASSIC = 'Cal saber-ho[1. Pot variar.] abans.\n\n[ia-local-checker]\n\n<p class="fons-gris">Nota.</p>'

/** The editor's stores, with one Classic block (or any block) in the post. */
function editor(block: BlockInstance, meta: Record<string, unknown> = {}) {
  const replaceBlocks = vi.fn()
  const editPost = vi.fn()
  const createInfoNotice = vi.fn()
  const stores: Record<string, any> = { 'core/block-editor': { replaceBlocks }, 'core/editor': { editPost }, 'core/notices': { createInfoNotice } } // eslint-disable-line @typescript-eslint/no-explicit-any
  const wp: ConvertWordPress = {
    blocks: wordpressApi,
    autop: { autop: wordpressApi.autop },
    shortcode: { attrs: wordpressApi.shortcodeAttrs },
    data: {
      select: (store: string) =>
        store === 'core/block-editor'
          ? { getBlock: (id: string) => (id === block.clientId ? block : null) }
          : { getEditedPostAttribute: () => meta },
      // One actions object per store, as in the editor.
      dispatch: (store: string) => stores[store],
    },
  }
  return { wp, replaceBlocks, editPost, createInfoNotice }
}

const classicBlock = () => createBlock('core/freeform', { content: CLASSIC })
/** What WordPress's own "Convert to blocks" passes to replaceBlocks. */
const wordpressConversion = (block: BlockInstance) => rawHandler({ HTML: serialize(block) })

describe('Convert to blocks in the editor', () => {
  it("gives WordPress's conversion of a Classic block the site's mappings", () => {
    const block = classicBlock()
    const { wp, editPost, createInfoNotice } = editor(block)

    const blocks = siteConversion(wp, block.clientId, wordpressConversion(block))!
    const markup = serialize(blocks)

    expect(validateMarkup(markup)).toEqual([])
    expect(markup).toContain('<!-- wp:sc/ia-local-checker /-->')
    expect(markup).toContain('is-style-avis-neutre')
    expect(markup).toMatch(/<sup data-fn="[\w-]+" class="fn"><a href="#[\w-]+" id="[\w-]+-link">1<\/a><\/sup>/)
    expect(markup).toContain('<!-- wp:footnotes /-->')

    const notes = JSON.parse(editPost.mock.calls[0][0].meta.footnotes)
    expect(notes).toEqual([{ id: expect.any(String), content: 'Pot variar.' }])
    expect(markup).toContain(`data-fn="${notes[0].id}"`)
    expect(createInfoNotice.mock.calls[0][0]).toContain('[ia-local-checker] convertit en bloc')
  })

  it("adds the notes after the post's existing ones", () => {
    const block = classicBlock()
    const { wp, editPost } = editor(block, { footnotes: JSON.stringify([{ id: 'abans', content: 'Ja hi era.' }]) })

    siteConversion(wp, block.clientId, wordpressConversion(block))

    expect(JSON.parse(editPost.mock.calls[0][0].meta.footnotes).map((n: { id: string }) => n.id)[0]).toBe('abans')
  })

  it('leaves any other replacement alone', () => {
    const block = classicBlock()
    const { wp, editPost } = editor(block)
    const paragraph = createBlock('core/paragraph', { content: 'Res' })

    // A transform or anything else that isn't WordPress's conversion.
    expect(siteConversion(wp, block.clientId, [createBlock('core/html', { content: CLASSIC })])).toBeNull()
    // Two blocks at once, or a block that isn't a Classic one.
    expect(siteConversion(wp, [block.clientId, 'altre'], wordpressConversion(block))).toBeNull()
    expect(siteConversion(editor(paragraph).wp, paragraph.clientId, [createBlock('core/heading')])).toBeNull()
    expect(editPost).not.toHaveBeenCalled()
  })

  it("wraps replaceBlocks, and falls back to WordPress's result if the converter fails", () => {
    const block = classicBlock()
    const { wp, replaceBlocks } = editor(block)
    const actions = wp.data.dispatch('core/block-editor')
    vi.spyOn(console, 'error').mockImplementation(() => {})

    addClassicConversion(wp)
    actions.replaceBlocks(block.clientId, wordpressConversion(block))
    expect(serialize(replaceBlocks.mock.calls[0][1])).toContain('wp:sc/ia-local-checker')

    const failing = editor(block)
    addClassicConversion(failing.wp, () => {
      throw new Error('boom')
    })
    const original = wordpressConversion(block)
    failing.wp.data.dispatch('core/block-editor').replaceBlocks(block.clientId, original)
    expect(failing.replaceBlocks.mock.calls[0][1]).toBe(original)
  })
})
