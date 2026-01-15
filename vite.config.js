import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  base: '/stg/', // ← AGREGA ESTA LÍNEA
  plugins: [react()],
  build: {
    outDir: 'build',
    emptyOutDir: true,
    rollupOptions: {
      // 🔑 ENTRY POINT REAL (NO index.html)
      input: {
        app: './src/main.jsx'
      },
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: '[name].js',
        assetFileNames: 'index.[ext]'
      }
    }
  }
})