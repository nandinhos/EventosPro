import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { compression } from 'vite-plugin-compression2'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    // Gzip compression
    compression({
      algorithm: 'gzip',
      exclude: [/\.(br)$/, /\.(gz)$/],
    }),
    // Brotli compression (better than gzip)
    compression({
      algorithm: 'brotliCompress',
      exclude: [/\.(br)$/, /\.(gz)$/],
    }),
  ],
  build: {
    // Minification
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true, // Remove console.log in production
        drop_debugger: true,
        pure_funcs: ['console.log', 'console.info', 'console.debug'], // Remove specific functions
      },
      format: {
        comments: false, // Remove comments
      },
    },
    // Code splitting and chunking
    rollupOptions: {
      output: {
        manualChunks: {
          // Split vendor code
          'vendor': [
            'lodash',
            'axios',
          ],
          // Split Alpine.js if used
          'alpine': ['alpinejs'],
        },
        // Optimize chunk size
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js',
        assetFileNames: '[ext]/[name]-[hash].[ext]',
      },
    },
    // Chunk size warnings
    chunkSizeWarningLimit: 500, // KB
    // Source maps (disable in production)
    sourcemap: false,
  },
  server: {
    watch: {
      ignored: [
        '**/storage/framework/views/**',
        '**/storage/logs/**',
      ],
    },
  },
  // Tree shaking optimization
  optimizeDeps: {
    include: ['axios', 'lodash'],
  },
})

