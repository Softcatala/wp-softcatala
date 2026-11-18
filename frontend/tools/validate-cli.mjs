#!/usr/bin/env node
/**
 * Validates files of block markup (tests/blocks/validate.ts): what the
 * editor would report as "unexpected content", markup not in the current
 * saved format, blocks outside the allowed list, unregistered styles.
 *
 *   npm run validate -- <file.html>... [--allow-html]
 *
 * Exits 0 when every file is valid, 1 otherwise. --allow-html accepts Custom
 * HTML blocks (only users with unfiltered_html get them in the editor).
 */

import { readFileSync } from 'node:fs'
import { withWordPress } from './wp-runtime.mjs'

const args = process.argv.slice(2)
const allowHtml = args.includes('--allow-html')
const files = args.filter((a) => a !== '--allow-html')
if (files.length === 0) {
  console.error('Ús: npm run validate -- <fitxer.html>... [--allow-html]')
  process.exit(2)
}

await withWordPress(async (load) => {
  const { validateMarkup } = await load('/tests/blocks/validate.ts')

  for (const file of files) {
    const problems = validateMarkup(readFileSync(file, 'utf8'), allowHtml)
    console.log(`${problems.length === 0 ? 'OK ' : 'ERR'} ${file}`)
    for (const p of problems) console.log(`    ${p.block}: ${p.message.slice(0, 400)}`)
    if (problems.length > 0) process.exitCode = 1
  }
})
