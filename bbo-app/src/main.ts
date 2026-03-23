import { createSSRApp } from 'vue'
import App from './App.vue'
import pinia from './store'
import i18n from './locale'

export function createApp() {
  const app = createSSRApp(App)

  // 使用 Pinia 状态管理
  app.use(pinia)

  // 使用 i18n 国际化
  app.use(i18n)

  return {
    app,
  }
}
