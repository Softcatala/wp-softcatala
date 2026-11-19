import { describe, expect, it, vi } from 'vitest'
import { iconListColor, iconListData, iconListRows, itemToRow, rowToItem } from '../src/js/icon-list-data'
import { addIconListTransforms, iconListTransforms } from '../src/js/editor-blocks'

const createBlock = (name: string, attributes: Record<string, unknown> = {}, innerBlocks: unknown[] = []) => ({ name, attributes, innerBlocks })

describe('icon list data', () => {
  const rows = [
    { icon: 'language', title: '', text: 'La satisfacció.' },
    { icon: 'users', title: 'Comunitat', text: 'Bon ambient.' },
  ]

  it('round-trips through ACF\'s saved form', () => {
    const data = iconListData(rows, 'gris')
    expect(data).toMatchObject({ color: 'gris', items: 2, items_1_icon: 'users', _items_1_icon: 'field_sc_llista_icones_icon' })
    expect(iconListRows(data)).toEqual(rows)
    expect(iconListColor(data)).toBe('gris')
  })

  it('reads the field-key form of a block not saved yet', () => {
    const data = {
      field_sc_llista_icones_color: 'blancgris',
      field_sc_llista_icones_items: {
        'row-0': { field_sc_llista_icones_icon: 'globe', field_sc_llista_icones_title: 'Món', field_sc_llista_icones_text: 'Text.' },
      },
    }
    expect(iconListRows(data)).toEqual([{ icon: 'globe', title: 'Món', text: 'Text.' }])
    expect(iconListColor(data)).toBe('blancgris')
  })

  it('splits a bold lead into the title, and joins it back', () => {
    expect(itemToRow('<strong>Traductors</strong>: Només cal un bon català.')).toEqual({ icon: '', title: 'Traductors', text: 'Només cal un bon català.' })
    expect(itemToRow('Sense títol, <em>amb</em> format.')).toEqual({ icon: '', title: '', text: 'Sense títol, <em>amb</em> format.' })
    expect(rowToItem({ icon: 'x', title: 'Traductors', text: 'Text.' })).toBe('<strong>Traductors</strong>: Text.')
    expect(rowToItem({ icon: 'x', title: '', text: 'Text.' })).toBe('Text.')
  })
})

describe('list ↔ icon list transforms', () => {
  const transforms = iconListTransforms(createBlock as never)

  it('turns any list into the icon list block', () => {
    const items = [
      { name: 'core/list-item', attributes: { content: '<strong>A</strong>: u' }, innerBlocks: [] },
      { name: 'core/list-item', attributes: { content: 'dos' }, innerBlocks: [] },
    ]
    const block = transforms.from[0].transform({ className: 'is-style-amb-icones' }, items) as ReturnType<typeof createBlock>

    expect(transforms.from[0].blocks).toEqual(['core/list'])
    expect(block.name).toBe('sc/llista-icones')
    expect(iconListRows(block.attributes.data as Record<string, unknown>)).toEqual([
      { icon: '', title: 'A', text: 'u' },
      { icon: '', title: '', text: 'dos' },
    ])
  })

  it('turns the icon list block back into a list', () => {
    const data = iconListData([{ icon: 'users', title: 'Comunitat', text: 'Bon ambient.' }])
    const block = transforms.to[0].transform({ data }) as ReturnType<typeof createBlock>

    expect(block.name).toBe('core/list')
    expect(block.innerBlocks).toEqual([createBlock('core/list-item', { content: '<strong>Comunitat</strong>: Bon ambient.' })])
  })

  it('adds the transforms when ACF registers the block, or re-registers it if it already did', () => {
    const filters: Array<(s: Record<string, unknown>, n: string) => Record<string, unknown>> = []
    let ready: () => void = () => {}
    const registered = { name: 'sc/llista-icones', title: 'Llista amb icones' }
    const wp = {
      blocks: {
        registerBlockType: vi.fn(),
        createBlock,
        getBlockType: vi.fn(() => registered),
        unregisterBlockType: vi.fn(),
      },
      hooks: { addFilter: vi.fn((_h: string, _n: string, cb: (s: Record<string, unknown>, n: string) => Record<string, unknown>) => filters.push(cb)) },
      domReady: (cb: () => void) => { ready = cb },
    }
    addIconListTransforms(wp as never)

    expect(filters[0]({ title: 'x' }, 'sc/llista-icones')).toHaveProperty('transforms')
    expect(filters[0]({ title: 'x' }, 'core/list')).not.toHaveProperty('transforms')

    ready()
    expect(wp.blocks.unregisterBlockType).toHaveBeenCalledWith('sc/llista-icones')
    expect(wp.blocks.registerBlockType).toHaveBeenCalledWith('sc/llista-icones', expect.objectContaining({ title: 'Llista amb icones', transforms: expect.any(Object) }))
  })
})
