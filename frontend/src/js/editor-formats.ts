/**
 * Inline formats for the block editor's rich text (paragraphs, list items…):
 *
 * - "Text secundari" → <span class="sc-secundari">: grey italics, for asides
 *   such as "(resposta tardana)".
 * - "Nota" → <span class="sc-nota">: its own line, italics, for an extra line
 *   under a list item.
 * - "Text petit" → <span class="sc-petit">: smaller text, such as a file size
 *   under a download link (the legacy .petit).
 *
 * Their look is in frontend/src/scss/_blocks.scss. Enqueued only in the block
 * editor, with the block_editor flag on (Softcatala\Blocks\Editor), and uses
 * the editor's own global `wp` rather than bundling WordPress packages.
 */

interface FormatEditProps {
  isActive: boolean
  value: unknown
  onChange: (value: unknown) => void
}

interface WordPressGlobals {
  richText: {
    registerFormatType: (name: string, settings: Record<string, unknown>) => unknown
    toggleFormat: (value: unknown, format: { type: string }) => unknown
  }
  blockEditor: { RichTextToolbarButton: unknown }
  element: { createElement: (type: unknown, props: Record<string, unknown>) => unknown }
}

export const FORMATS = [
  { name: 'sc/secundari', title: 'Text secundari', className: 'sc-secundari', icon: 'editor-textcolor' },
  { name: 'sc/nota', title: 'Nota (línia pròpia)', className: 'sc-nota', icon: 'format-aside' },
  { name: 'sc/petit', title: 'Text petit', className: 'sc-petit', icon: 'editor-contract' },
] as const

export function registerFormats(wp: WordPressGlobals): void {
  const { registerFormatType, toggleFormat } = wp.richText
  const { RichTextToolbarButton } = wp.blockEditor
  const { createElement } = wp.element

  for (const format of FORMATS) {
    registerFormatType(format.name, {
      title: format.title,
      tagName: 'span',
      className: format.className,
      edit: ({ isActive, value, onChange }: FormatEditProps) =>
        createElement(RichTextToolbarButton, {
          icon: format.icon,
          title: format.title,
          isActive,
          onClick: () => onChange(toggleFormat(value, { type: format.name })),
        }),
    })
  }
}

const globals = (globalThis as { wp?: WordPressGlobals }).wp
if (globals?.richText) {
  registerFormats(globals)
}
