/// <reference types="node" />
/**
 * Block markup validation, the same check the block editor runs when it opens
 * a post ("This block contains unexpected or invalid content").
 *
 * Parses serialized blocks with WordPress's own packages, so each block's
 * save() output is compared with the markup. On top of that, it enforces the
 * theme's rules from config/block-editor.json: only allowed blocks, and only
 * block styles that are registered for that block.
 *
 * Used by blocks.test.ts for the pattern files; meant for any markup the
 * theme or its tools produce (the resum fixture, agent output).
 */

import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { getBlockType, parse, serialize } from '@wordpress/blocks'
import { registerCoreBlocks } from '@wordpress/block-library'

interface EditorConfig {
  blocks: string[]
  html_blocks: string[]
  styles: Record<string, Record<string, string>>
}

interface ParsedBlock {
  name: string
  isValid?: boolean
  validationIssues?: Array<{ args?: unknown[] }>
  attributes: Record<string, unknown>
  innerBlocks: ParsedBlock[]
}

export interface Problem {
  block: string
  message: string
}

export const config: EditorConfig = JSON.parse(
  readFileSync(resolve(__dirname, '../../../config/block-editor.json'), 'utf8')
)

let registered = false

function ensureRegistered(): void {
  if (!registered) {
    registerCoreBlocks()
    registered = true
  }
}

function describeIssues(block: ParsedBlock): string {
  const issues = block.validationIssues ?? []
  if (issues.length === 0) return 'invalid content'
  return issues
    .map((issue) => (issue.args ?? []).map((arg) => (typeof arg === 'string' ? arg : JSON.stringify(arg))).join(' '))
    .join('; ')
}

/**
 * Problems found in the markup; an empty array means it is valid.
 *
 * @param markup     Serialized blocks.
 * @param allowHtml  Whether Custom HTML blocks are acceptable (they are only
 *                   offered to users with unfiltered_html).
 */
export function validateMarkup(markup: string, allowHtml = false): Problem[] {
  ensureRegistered()

  const allowed = new Set([...config.blocks, ...(allowHtml ? config.html_blocks : [])])
  const problems: Problem[] = []

  const walk = (blocks: ParsedBlock[]): void => {
    for (const block of blocks) {
      if (block.name === 'core/missing') {
        problems.push({ block: String(block.attributes.originalName), message: 'unknown block type' })
        continue
      }

      if (!allowed.has(block.name)) {
        problems.push({ block: block.name, message: 'block not in the allowed list (config/block-editor.json)' })
      }

      if (block.isValid === false) {
        problems.push({ block: block.name, message: describeIssues(block) })
      }

      const className = typeof block.attributes.className === 'string' ? block.attributes.className : ''
      for (const token of className.split(/\s+/)) {
        if (!token.startsWith('is-style-')) continue
        const style = token.slice('is-style-'.length)
        const coreStyles = (getBlockType(block.name)?.styles ?? []).map((s: { name: string }) => s.name)
        const themeStyles = Object.keys(config.styles[block.name] ?? {})
        if (!coreStyles.includes(style) && !themeStyles.includes(style)) {
          problems.push({ block: block.name, message: `unregistered block style "${style}"` })
        }
      }

      walk(block.innerBlocks)
    }
  }

  const parsed = parse(markup) as ParsedBlock[]
  walk(parsed)

  // Markup in an older format still parses (WordPress migrates it through the
  // block's deprecations), but it is not what the editor saves today.
  if (problems.length === 0 && serialize(parsed as never).trim() !== markup.trim()) {
    problems.push({ block: '(document)', message: 'not in the current saved format: re-serializing the blocks changes the markup' })
  }

  return problems
}

/** The block markup of a pattern file: everything after its PHP header. */
export function patternMarkup(source: string): string {
  const end = source.indexOf('?>')
  return (end === -1 ? source : source.slice(end + 2)).trim()
}
