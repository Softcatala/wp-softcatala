import { defineConfig } from 'vite'
import { resolve } from 'node:path'

export default defineConfig({
  // No HTML entry — pure asset pipeline
  publicDir: false,

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },

  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler',
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
        main: resolve(__dirname, 'src/js/main.js'),
      },
      output: {
        entryFileNames: 'js/[name].min.js',
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
