/// <reference types="node" />
/**
 * Registers WordPress's core blocks and the theme's own (blocks/<name>/block.json)
 * outside the editor, once. Shared by the markup validator and the classic
 * converter; needs a DOM (jsdom).
 */

import { readFileSync, readdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { registerBlockType } from '@wordpress/blocks'
import { registerCoreBlocks } from '@wordpress/block-library'

// ES module under Vite (the convert command), CommonJS-like under Vitest.
const here = typeof __dirname !== 'undefined' ? __dirname : dirname(fileURLToPath(import.meta.url))
const themeBlocksDir = resolve(here, '../../blocks')

let registered = false

export function ensureRegistered(): void {
  if (registered) return

  registerCoreBlocks()

  // The theme's blocks render on the server, so their saved form is the
  // comment alone.
  for (const folder of readdirSync(themeBlocksDir)) {
    const metadata = JSON.parse(readFileSync(resolve(themeBlocksDir, folder, 'block.json'), 'utf8'))
    registerBlockType(metadata, { save: () => null })
  }

  registered = true
}
