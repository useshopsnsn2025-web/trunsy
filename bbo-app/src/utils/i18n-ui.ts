/**
 * 多语言 UI 工具函数
 * 封装 uni.showModal 等 API，自动支持多语言按钮文字
 */

import { useI18n } from 'vue-i18n'

/**
 * 显示模态对话框（支持多语言按钮）
 * @param options uni.showModal 的选项
 * @returns Promise
 */
export function showModal(options: UniApp.ShowModalOptions): Promise<UniApp.ShowModalRes> {
  const { t } = useI18n()

  return new Promise((resolve) => {
    uni.showModal({
      ...options,
      // 如果没有指定 confirmText，使用翻译的"确认"
      confirmText: options.confirmText || t('common.confirm'),
      // 如果没有指定 cancelText，使用翻译的"取消"
      cancelText: options.cancelText || t('common.cancel'),
      success: (res) => {
        resolve(res)
        options.success?.(res)
      },
      fail: options.fail,
      complete: options.complete,
    })
  })
}

/**
 * 显示确认对话框（只有确认按钮）
 * @param options uni.showModal 的选项
 * @returns Promise
 */
export function showAlert(options: Omit<UniApp.ShowModalOptions, 'showCancel'>): Promise<UniApp.ShowModalRes> {
  const { t } = useI18n()

  return new Promise((resolve) => {
    uni.showModal({
      ...options,
      showCancel: false,
      confirmText: options.confirmText || t('common.ok'),
      success: (res) => {
        resolve(res)
        options.success?.(res)
      },
      fail: options.fail,
      complete: options.complete,
    })
  })
}

/**
 * 设置导航栏标题（根据页面类型自动获取翻译）
 * @param pageKey 页面键名（对应 page.xxx 翻译键）
 * @param customTitle 自定义标题（优先使用）
 */
export function setNavigationTitle(pageKey: string, customTitle?: string) {
  const { t } = useI18n()

  const title = customTitle || t(`page.${pageKey}`)
  uni.setNavigationBarTitle({ title })
}

/**
 * 页面标题键名映射
 */
export const PAGE_TITLE_KEYS: Record<string, string> = {
  '/pages/index/index': 'home',
  '/pages/category/index': 'category',
  '/pages/publish/index': 'publish',
  '/pages/message/index': 'messages',
  '/pages/profile/index': 'profile',
  '/pages/goods/detail': 'details',
  '/pages/goods/list': 'products',
  '/pages/search/index': 'search',
  '/pages/auth/login': 'login',
  '/pages/auth/register': 'register',
  '/pages/chat/index': 'chat',
  '/pages/credit/index': 'credit',
  '/pages/credit/apply': 'applyCredit',
  '/pages/credit/orders': 'installmentOrders',
  '/pages/credit/order-detail': 'orderDetail',
}

/**
 * 根据当前页面路径自动设置导航栏标题
 */
export function setCurrentPageTitle() {
  const pages = getCurrentPages()
  if (pages.length === 0) return

  const currentPage = pages[pages.length - 1]
  const route = '/' + currentPage.route
  const pageKey = PAGE_TITLE_KEYS[route]

  if (pageKey) {
    setNavigationTitle(pageKey)
  }
}
