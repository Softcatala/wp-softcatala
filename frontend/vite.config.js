import { defineConfig } from 'vite'
import { existsSync, readdirSync, rmSync } from 'node:fs'
import { basename, join, resolve } from 'node:path'

/**
 * Delete the hashed chunks earlier builds left behind.
 *
 * emptyOutDir has to stay off — static/ also holds some 45 hand-maintained
 * scripts and the corrector's build output — so a rebuild that changes a
 * shared module used to drop the new `utils-<hash>.js` next to every older
 * one and keep them all forever. Nothing references the old ones.
 *
 * Deliberately narrow about what it will delete, because a mistake here
 * removes a file no build can regenerate. A candidate has to look like our own
 * output three ways over: the chunkFileNames shape, a name matching a module
 * in src/js, and a sibling source map. That last one is what saves
 * dubbing-feedback.js, a hand-written script whose name happens to parse as
 * the module `dubbing` plus an 8-character hash. If sourcemaps are ever turned
 * off the sweep stops finding anything, which is the safe way to fail.
 */
function cleanStaleChunks() {
  // chunkFileNames is 'js/[name]-[hash].js'; the hashes are 8 characters.
  const CHUNK = /^([a-z0-9-]+)-[A-Za-z0-9_-]{8}\.js$/

  return {
    name: 'sc-clean-stale-chunks',
    writeBundle(_options, bundle) {
      const jsDir = resolve(__dirname, '../static/js')
      const modules = new Set(
        readdirSync(resolve(__dirname, 'src/js'))
          .filter((file) => file.endsWith('.ts'))
          .map((file) => file.slice(0, -3))
      )
      const justWritten = new Set(Object.keys(bundle).map((file) => basename(file)))

      for (const file of readdirSync(jsDir)) {
        const match = CHUNK.exec(file)
        if (!match || justWritten.has(file)) continue
        if (!modules.has(match[1])) continue
        if (!existsSync(join(jsDir, `${file}.map`))) continue

        rmSync(join(jsDir, file))
        rmSync(join(jsDir, `${file}.map`))
        this.info(`removed stale chunk js/${file}`)
      }
    },
  }
}

export default defineConfig({
  plugins: [cleanStaleChunks()],

  // No HTML entry — pure asset pipeline
  publicDir: false,
  // Relative asset URLs so font paths work under any WordPress theme URL
  base: './',

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },

  test: {
    environment: 'jsdom',
    include: ['tests/**/*.test.ts'],
  },

  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
        loadPaths: [
          resolve(__dirname, 'node_modules/@fortawesome/fontawesome-free/scss'),
        ],
      },
    },
    devSourcemap: true,
  },

  build: {
    outDir: resolve(__dirname, '../static'),
    emptyOutDir: false, // Preserve per-page CSS/JS files in static/
    sourcemap: true,
    // oxc is Rolldown's built-in minifier. 'esbuild' here would need the
    // esbuild package installed separately, which nothing declares -- builds
    // only kept working because Node resolved a stray esbuild from a parent
    // directory of the checkout, so any clean install failed.
    minify: 'oxc',
    cssCodeSplit: false, // Single CSS bundle

    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/js/main.ts'),
        kanban: resolve(__dirname, 'src/js/kanban.ts'),
        traductor: resolve(__dirname, 'src/js/traductor.ts'),
        conjugador: resolve(__dirname, 'src/js/conjugador.ts'),
        transcribe: resolve(__dirname, 'src/js/transcribe.ts'),
        'transcribe-results': resolve(__dirname, 'src/js/transcribe-results.ts'),
        dubbing: resolve(__dirname, 'src/js/dubbing.ts'),
        pmf: resolve(__dirname, 'src/js/pmf.ts'),
        'diccionari-engcat': resolve(__dirname, 'src/js/diccionari-engcat.ts'),
        sinonims: resolve(__dirname, 'src/js/sinonims.ts'),
        programes: resolve(__dirname, 'src/js/programes.ts'),
      },
      output: {
        entryFileNames: (chunk) =>
          chunk.name === 'kanban' ? 'js/kanban.js'
          : chunk.name === 'traductor' ? 'js/traductor.js'
          : chunk.name === 'conjugador' ? 'js/conjugador/conjugador.js'
          : chunk.name === 'transcribe' ? 'js/transcribe.js'
          : chunk.name === 'transcribe-results' ? 'js/transcribe-results.js'
          : chunk.name === 'dubbing' ? 'js/dubbing.js'
          : chunk.name === 'pmf' ? 'js/pmf.js'
          : chunk.name === 'diccionari-engcat' ? 'js/diccionari-engcat/diccionari-engcat.js'
          : chunk.name === 'sinonims' ? 'js/sinonims.js'
          : chunk.name === 'programes' ? 'js/programes.js'
          : 'js/[name].min.js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.names?.[0] ?? assetInfo.name ?? ''
          // Force the single CSS bundle to be named main.min.css
          if (name.endsWith('.css')) return 'css/main.min[extname]'
          if (/\.(woff2?|eot|ttf|otf)$/.test(name)) return 'fonts/[name][extname]'
          if (/\.(png|jpe?g|gif|svg|webp|ico)$/.test(name)) return 'img/[name][extname]'
          return 'assets/[name][extname]'
        },
      },
    },
  },
})
