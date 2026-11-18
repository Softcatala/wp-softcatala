import { describe, expect, it, vi } from 'vitest'
import { FORMATS, registerFormats } from '../src/js/editor-formats'

function fakeWp() {
  return {
    richText: {
      registerFormatType: vi.fn(),
      toggleFormat: vi.fn((value: unknown, format: { type: string }) => ({ value, toggled: format.type })),
    },
    blockEditor: { RichTextToolbarButton: 'RichTextToolbarButton' },
    element: { createElement: vi.fn((type: unknown, props: Record<string, unknown>) => ({ type, props })) },
  }
}

describe('editor formats', () => {
  it('registers each format as a classed span', () => {
    const wp = fakeWp()
    registerFormats(wp)

    expect(wp.richText.registerFormatType).toHaveBeenCalledTimes(FORMATS.length)
    expect(wp.richText.registerFormatType).toHaveBeenCalledWith(
      'sc/secundari',
      expect.objectContaining({ tagName: 'span', className: 'sc-secundari' })
    )
    expect(wp.richText.registerFormatType).toHaveBeenCalledWith(
      'sc/nota',
      expect.objectContaining({ tagName: 'span', className: 'sc-nota' })
    )
  })

  it('toggles its own format from the toolbar button', () => {
    const wp = fakeWp()
    registerFormats(wp)

    const settings = wp.richText.registerFormatType.mock.calls[0][1] as {
      edit: (props: { isActive: boolean; value: unknown; onChange: (v: unknown) => void }) => { props: { onClick: () => void; isActive: boolean } }
    }
    const onChange = vi.fn()
    const button = settings.edit({ isActive: true, value: 'VALUE', onChange })

    expect(button.props.isActive).toBe(true)
    button.props.onClick()
    expect(onChange).toHaveBeenCalledWith({ value: 'VALUE', toggled: 'sc/secundari' })
  })
})
