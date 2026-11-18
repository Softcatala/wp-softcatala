/**
 * The editor side of the theme's server-rendered blocks (blocks/<name>/block.json,
 * rendered by Softcatala\Blocks\ThemeBlocks). Each shows a live preview from the
 * server and, in the sidebar, a field per setting. Title, icon and attributes
 * come from block.json, which WordPress hands to the editor; this only adds the
 * edit view.
 *
 * Enqueued only in the block editor, with the block_editor flag on
 * (Softcatala\Blocks\Editor), and uses the editor's global `wp`.
 */

type Field =
  | { key: string; label: string; type: 'text'; help?: string }
  | { key: string; label: string; type: 'number'; help?: string }
  | { key: string; label: string; type: 'select'; options: Array<[string, string]>; help?: string }

interface BlockConfig {
  name: string
  panel?: string
  fields: Field[]
}

interface EditProps {
  attributes: Record<string, unknown>
  setAttributes: (attributes: Record<string, unknown>) => void
}

type CreateElement = (type: unknown, props?: Record<string, unknown> | null, ...children: unknown[]) => unknown

interface WordPressGlobals {
  blocks: { registerBlockType: (name: string, settings: Record<string, unknown>) => unknown }
  blockEditor: { InspectorControls: unknown; useBlockProps: (props?: Record<string, unknown>) => Record<string, unknown> }
  components: { PanelBody: unknown; TextControl: unknown; SelectControl: unknown }
  element: { createElement: CreateElement; Fragment: unknown }
  serverSideRender: unknown
  data?: { select: (store: string) => { getCurrentPostId?: () => number } | undefined }
}

export const BLOCKS: BlockConfig[] = [
  {
    name: 'sc/index',
    fields: [{ key: 'titol', label: 'Títol', type: 'text', help: 'Buit, sense títol.' }],
  },
  {
    name: 'sc/ia-data',
    panel: 'Dades',
    fields: [
      { key: 'url', label: 'URL del fitxer JSON', type: 'text' },
      { key: 'format', label: 'Format', type: 'select', options: [['table', 'Taula'], ['graph', 'Gràfic']] },
      { key: 'title', label: 'Títol (gràfic)', type: 'text' },
      { key: 'subtitle', label: 'Subtítol (gràfic)', type: 'text' },
      { key: 'caption', label: 'Peu', type: 'text' },
      { key: 'metric', label: 'Mètrica (gràfic)', type: 'text' },
      { key: 'label_field', label: 'Camp de l’etiqueta (gràfic)', type: 'text' },
      { key: 'direction', label: 'Direcció (gràfic)', type: 'text' },
      { key: 'scale', label: 'Escala (gràfic)', type: 'text' },
      { key: 'suffix', label: 'Sufix (gràfic)', type: 'text' },
      { key: 'threshold_label', label: 'Etiqueta del llindar (gràfic)', type: 'text' },
      { key: 'decimals', label: 'Decimals', type: 'text' },
      { key: 'highlight_field', label: 'Camp que marca les files destacades', type: 'text' },
      { key: 'link_column', label: 'Columna enllaçada (taula)', type: 'text' },
      { key: 'link_field', label: 'Camp de l’enllaç (taula)', type: 'text' },
      { key: 'cache', label: 'Memòria cau, en segons', type: 'number' },
    ],
  },
  { name: 'sc/ia-local-checker', fields: [] },
  { name: 'sc/navega-en-catala', fields: [] },
]

export function registerBlocks(wp: WordPressGlobals): void {
  const { createElement: el, Fragment } = wp.element
  const { InspectorControls, useBlockProps } = wp.blockEditor
  const { PanelBody, TextControl, SelectControl } = wp.components

  const control = (field: Field, props: EditProps): unknown => {
    const value = props.attributes[field.key]
    const onChange = (next: string) =>
      props.setAttributes({ [field.key]: field.type === 'number' ? Number(next) || 0 : next })
    const common = { key: field.key, label: field.label, help: field.help, value: value ?? '', onChange, __nextHasNoMarginBottom: true }

    return field.type === 'select'
      ? el(SelectControl, { ...common, options: field.options.map(([v, label]) => ({ value: v, label })) })
      : el(TextControl, { ...common, type: field.type, __next40pxDefaultSize: true })
  }

  for (const block of BLOCKS) {
    wp.blocks.registerBlockType(block.name, {
      edit: (props: EditProps) => {
        const postId = wp.data?.select('core/editor')?.getCurrentPostId?.()
        const preview = el(wp.serverSideRender, {
          block: block.name,
          attributes: props.attributes,
          ...(postId ? { urlQueryArgs: { post_id: postId } } : {}),
        })
        const settings =
          block.fields.length > 0
            ? el(InspectorControls, null, el(PanelBody, { title: block.panel ?? 'Opcions' }, ...block.fields.map((f) => control(f, props))))
            : null

        return el(Fragment, null, settings, el('div', useBlockProps(), preview))
      },
      // Rendered on the server: nothing is saved but the comment.
      save: () => null,
    })
  }
}

const globals = (globalThis as { wp?: WordPressGlobals }).wp
if (globals?.blocks && globals.serverSideRender) {
  registerBlocks(globals)
}
