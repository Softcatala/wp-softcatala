/**
 * Runs the frontend's TypeScript tools from the command line with WordPress's
 * block packages: a DOM from jsdom (raw handling and the block library need
 * one) and Vite's SSR loader (WordPress's packages import JSON in a way plain
 * Node rejects; the tests have the same setup).
 */

import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { JSDOM, VirtualConsole } from 'jsdom'
import { createServer } from 'vite'

/**
 * Calls run(load) with a loader for modules under frontend/ (paths like
 * '/tools/convert-classic.ts'), then shuts the loader down.
 */
export async function withWordPress(run) {
  // Quiet console: the block library's styles trip jsdom's CSS parser.
  const { window } = new JSDOM('<!doctype html><html><body></body></html>', {
    url: 'https://www.softcatala.org/',
    virtualConsole: new VirtualConsole(),
  })
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

  const server = await createServer({
    root: resolve(dirname(fileURLToPath(import.meta.url)), '..'),
    configFile: false,
    logLevel: 'error',
    appType: 'custom',
    server: { middlewareMode: true, hmr: false, ws: false },
    ssr: { noExternal: [/@wordpress\//] },
  })

  try {
    return await run((path) => server.ssrLoadModule(path))
  } finally {
    await server.close()
  }
}
