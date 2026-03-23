// Store 入口
import { createPinia } from 'pinia'

const pinia = createPinia()

export default pinia

// 导出所有模块
export { useUserStore } from './modules/user'
export { useAppStore } from './modules/app'
export { useCartStore } from './modules/cart'
