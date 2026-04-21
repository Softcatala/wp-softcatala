import { defineConfig } from 'vite'
import { resolve } from 'node:path'

export default defineConfig({
  // No HTML entry — pure asset pipeline
  publicDir: false,
  // Relative asset URLs so font paths work under any WordPress theme URL
  base: './',

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
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
    minify: 'esbuild',
    cssCodeSplit: false, // Single CSS bundle

    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/js/main.ts'),
        traductor: resolve(__dirname, 'src/js/traductor.ts'),
        conjugador: resolve(__dirname, 'src/js/conjugador.ts'),
        transcribe: resolve(__dirname, 'src/js/transcribe.ts'),
        dubbing: resolve(__dirname, 'src/js/dubbing.ts'),
      },
      output: {
        entryFileNames: (chunk) =>
          chunk.name === 'traductor' ? 'js/traductor.js'
          : chunk.name === 'conjugador' ? 'js/conjugador/conjugador.js'
          : chunk.name === 'transcribe' ? 'js/transcribe.js'
          : chunk.name === 'dubbing' ? 'js/dubbing.js'
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
