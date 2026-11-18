#!/usr/bin/env node
/**
 * Converts a classic page's raw content to block markup (tools/convert-classic.ts).
 *
 *   npm run convert -- <input.html> [<output prefix>]
 *
 * Reads the raw post_content (e.g. `wp post get 123 --field=content > in.html`,
 * or the REST API's content.raw with context=edit), writes:
 *
 *   <prefix>.html            the block markup, validated
 *   <prefix>.footnotes.json  the notes for the post's `footnotes` meta (only with notes)
 *
 * and prints the report. Exits 1 if the markup doesn't validate. It never
 * touches a site: saving the result (as a draft or an autosave, for review)
 * is a separate step; see docs/blocks/README.md.
 *
 * Runs the TypeScript through Vite, like the tests: WordPress's packages
 * import JSON in a way plain Node rejects. They also need a DOM, from jsdom.
 */

import { readFileSync, writeFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { JSDOM, VirtualConsole } from 'jsdom'
import { createServer } from 'vite'

const [input, prefixArg] = process.argv.slice(2)
if (!input) {
  console.error('Ús: npm run convert -- <entrada.html> [<prefix de sortida>]')
  process.exit(2)
}
const prefix = prefixArg ?? input.replace(/\.[^./]+$/, '') + '.blocs'

// WordPress's raw handling parses HTML with the DOM.
// Its console stays quiet: the block library's styles trip jsdom's CSS parser.
const { window } = new JSDOM('<!doctype html><html><body></body></html>', { url: 'https://www.softcatala.org/', virtualConsole: new VirtualConsole() })
globalThis.window = window
// Every browser global Node lacks, as a test environment would provide.
for (const key of Object.getOwnPropertyNames(window)) {
  if (key in globalThis) continue
  const value = window[key]
  Object.defineProperty(globalThis, key, {
    value: typeof value === 'function' && /^[a-z]/.test(key) ? value.bind(window) : value,
    configurable: true,
    writable: true,
  })
}

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const server = await createServer({
  root,
  configFile: false,
  logLevel: 'error',
  appType: 'custom',
  server: { middlewareMode: true, hmr: false, ws: false },
  ssr: { noExternal: [/@wordpress\//] },
})

try {
  const { convertClassic } = await server.ssrLoadModule('/tools/convert-classic.ts')
  const { validateMarkup } = await server.ssrLoadModule('/tests/blocks/validate.ts')

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
} finally {
  await server.close()
}
