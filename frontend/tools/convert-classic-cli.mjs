#!/usr/bin/env node
/**
 * Converts a classic page's raw content to block markup (tools/convert-classic.ts).
 *
 *   npm run convert -- <input.html> [<output prefix>]
 *
 * Reads the raw post_content (`wp post get 123 --field=content`, or the REST
 * API's content.raw with context=edit) and writes:
 *
 *   <prefix>.html            the block markup, validated
 *   <prefix>.footnotes.json  the notes for the post's `footnotes` meta (only with notes)
 *
 * then prints the report. Exits 1 if the markup doesn't validate. It never
 * touches a site: see docs/blocks/README.md for saving the result for review.
 */

import { readFileSync, writeFileSync } from 'node:fs'
import { withWordPress } from './wp-runtime.mjs'

const [input, prefixArg] = process.argv.slice(2)
if (!input) {
  console.error('Ús: npm run convert -- <entrada.html> [<prefix de sortida>]')
  process.exit(2)
}
const prefix = prefixArg ?? input.replace(/\.[^./]+$/, '') + '.blocs'

await withWordPress(async (load) => {
  const { convertClassic } = await load('/tools/convert-classic.ts')
  const { validateMarkup } = await load('/tests/blocks/validate.ts')

  const result = convertClassic(readFileSync(input, 'utf8'))
  const problems = validateMarkup(result.markup)

  writeFileSync(`${prefix}.html`, result.markup + '\n')
  if (result.footnotes.length > 0) writeFileSync(`${prefix}.footnotes.json`, JSON.stringify(result.footnotes, null, 2) + '\n')

  console.log(`Blocs: ${prefix}.html${result.footnotes.length > 0 ? `\nNotes: ${prefix}.footnotes.json` : ''}`)
  console.log('\nInforme:')
  for (const line of result.report) console.log(`  - ${line}`)

  if (problems.length > 0) {
    console.log('\nEl resultat no valida:')
    for (const p of problems) console.log(`  - ${p.block}: ${p.message.slice(0, 300)}`)
    process.exitCode = 1
  }
})
