/**
 * The sc/llista-icones block's ACF data (Softcatala\Blocks\IconList), read and
 * written outside PHP: by the classic converter and by the editor's
 * list ↔ icon list transforms (editor-blocks.ts).
 *
 * ACF saves a block's fields in its `data` attribute by field name, each with
 * its key under "_name", and a repeater flattened (items_0_icon…). Blocks
 * created in the editor before their first save may hold the field-key form
 * instead ({ field_…_items: { row-0: { field_…_icon: … } } }); both are read.
 */

export interface IconRow {
  icon: string
  title: string
  text: string
}

const KEYS = {
  color: 'field_sc_llista_icones_color',
  items: 'field_sc_llista_icones_items',
  icon: 'field_sc_llista_icones_icon',
  title: 'field_sc_llista_icones_title',
  text: 'field_sc_llista_icones_text',
} as const

export function iconListData(rows: IconRow[], color = 'blanc'): Record<string, unknown> {
  const data: Record<string, unknown> = { color, _color: KEYS.color }
  rows.forEach((row, i) => {
    for (const field of ['icon', 'title', 'text'] as const) {
      data[`items_${i}_${field}`] = row[field]
      data[`_items_${i}_${field}`] = KEYS[field]
    }
  })
  data.items = rows.length
  data._items = KEYS.items
  return data
}

export function iconListRows(data: Record<string, unknown> = {}): IconRow[] {
  const byKey = data[KEYS.items]
  if (byKey && typeof byKey === 'object') {
    return Object.values(byKey as Record<string, Record<string, unknown>>).map((row) => ({
      icon: String(row[KEYS.icon] ?? ''),
      title: String(row[KEYS.title] ?? ''),
      text: String(row[KEYS.text] ?? ''),
    }))
  }

  const count = Number(data.items) || 0
  return Array.from({ length: count }, (_, i) => ({
    icon: String(data[`items_${i}_icon`] ?? ''),
    title: String(data[`items_${i}_title`] ?? ''),
    text: String(data[`items_${i}_text`] ?? ''),
  }))
}

export function iconListColor(data: Record<string, unknown> = {}): string {
  return String(data.color ?? data[KEYS.color] ?? 'blanc')
}

/** A list item's HTML as a row: a leading bold "Title:" becomes the title. */
export function itemToRow(content: string): IconRow {
  const match = /^\s*<(strong|b)>(.*?)<\/\1>\s*:?\s*([\s\S]*)$/.exec(content)
  return match ? { icon: '', title: match[2].trim(), text: match[3].trim() } : { icon: '', title: '', text: content.trim() }
}

/** A row as a list item's HTML (the icon has no place in a list). */
export function rowToItem(row: IconRow): string {
  if (row.title && row.text) return `<strong>${row.title}</strong>: ${row.text}`
  return row.title ? `<strong>${row.title}</strong>` : row.text
}
