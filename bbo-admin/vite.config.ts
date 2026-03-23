import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  base: './', // <-- 关键，打包后使用相对路径
  server: {
    host: '0.0.0.0',
    port: 3000,
    proxy: {
      '/admin': {
        target: 'http://localhost:8000',
        changeOrigin: true
      },
      '/storage': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})
