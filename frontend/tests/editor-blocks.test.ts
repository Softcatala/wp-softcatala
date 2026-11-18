/// <reference types="node" />
import { readFileSync, readdirSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { BLOCKS, registerBlocks } from '../src/js/editor-blocks'

const blocksDir = resolve(__dirname, '../../blocks')

function metadata(name: string) {
  for (const folder of readdirSync(blocksDir)) {
    const json = JSON.parse(readFileSync(resolve(blocksDir, folder, 'block.json'), 'utf8'))
    if (json.name === name) return json
  }
  throw new Error(`no block.json for ${name}`)
}

function fakeWp(postId = 42) {
  return {
    blocks: { registerBlockType: vi.fn() },
    blockEditor: { InspectorControls: 'InspectorControls', useBlockProps: vi.fn(() => ({ className: 'wrapper' })) },
    components: { PanelBody: 'PanelBody', TextControl: 'TextControl', SelectControl: 'SelectControl' },
    element: {
      createElement: vi.fn((type: unknown, props: Record<string, unknown> | null, ...children: unknown[]) => ({ type, props, children })),
      Fragment: 'Fragment',
    },
    serverSideRender: 'ServerSideRender',
    data: { select: vi.fn(() => ({ getCurrentPostId: () => postId })) },
  }
}

function register(wp: ReturnType<typeof fakeWp>): void {
  registerBlocks(wp as unknown as Parameters<typeof registerBlocks>[0])
}

type Node = { type: unknown; props: Record<string, unknown> | null; children: unknown[] }

function settingsFor(wp: ReturnType<typeof fakeWp>, name: string) {
  const call = wp.blocks.registerBlockType.mock.calls.find((c) => c[0] === name)
  return call?.[1] as { edit: (p: unknown) => Node; save: () => unknown }
}

function find(node: unknown, type: string): Node[] {
  if (!node || typeof node !== 'object') return []
  const n = node as Node
  return [...(n.type === type ? [n] : []), ...(n.children ?? []).flatMap((c) => find(c, type))]
}

describe('editor blocks', () => {
  it('registers every native theme block, saving only the comment', () => {
    const wp = fakeWp()
    register(wp)

    for (const block of BLOCKS) {
      expect(settingsFor(wp, block.name).save()).toBeNull()
    }
    expect(BLOCKS.map((b) => b.name).sort()).toEqual(['sc/ia-data', 'sc/ia-local-checker', 'sc/index', 'sc/navega-en-catala'])
  })

  it('only offers fields for attributes the block declares', () => {
    for (const block of BLOCKS) {
      const attributes = Object.keys(metadata(block.name).attributes ?? {})
      for (const field of block.fields) {
        expect(attributes, `${block.name}.${field.key}`).toContain(field.key)
      }
    }
  })

  it('previews from the server for the post being edited', () => {
    const wp = fakeWp(7)
    register(wp)

    const tree = settingsFor(wp, 'sc/index').edit({ attributes: { titol: 'Sumari' }, setAttributes: vi.fn() })
    const [preview] = find(tree, 'ServerSideRender')
    expect(preview.props).toMatchObject({ block: 'sc/index', attributes: { titol: 'Sumari' }, urlQueryArgs: { post_id: 7 } })
  })

  it('writes field changes back to the attributes, numbers as numbers', () => {
    const wp = fakeWp()
    register(wp)
    const setAttributes = vi.fn()

    const tree = settingsFor(wp, 'sc/ia-data').edit({ attributes: { url: '', format: 'table', cache: 3600 }, setAttributes })
    const cache = find(tree, 'TextControl').find((n) => n.props?.label === 'Memòria cau, en segons')!
    ;(cache.props!.onChange as (v: string) => void)('60')
    expect(setAttributes).toHaveBeenCalledWith({ cache: 60 })

    const format = find(tree, 'SelectControl')[0]
    expect(format.props?.options).toEqual([{ value: 'table', label: 'Taula' }, { value: 'graph', label: 'Gràfic' }])
  })

  it('has no settings panel for blocks without fields', () => {
    const wp = fakeWp()
    register(wp)

    const tree = settingsFor(wp, 'sc/ia-local-checker').edit({ attributes: {}, setAttributes: vi.fn() })
    expect(find(tree, 'InspectorControls')).toEqual([])
  })
})
