/**
 * Toast 提示 composable
 *
 * 用法：
 * ```ts
 * import { useToast } from '@/composables/useToast'
 *
 * const toast = useToast()
 *
 * toast.success('操作成功')
 * toast.error('操作失败')
 * toast.warning('请注意')
 * toast.info('提示信息')
 *
 * // 自定义配置
 * toast.show({
 *   message: '自定义提示',
 *   type: 'success',
 *   duration: 3000,
 *   position: 'top'
 * })
 * ```
 */

export interface ToastOptions {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
  position?: 'top' | 'center' | 'bottom'
}

export function useToast() {
  /**
   * 显示 Toast
   */
  function show(options: ToastOptions) {
    uni.$emit('toast:show', options)
  }

  /**
   * 隐藏 Toast
   */
  function hide() {
    uni.$emit('toast:hide')
  }

  /**
   * 成功提示
   */
  function success(message: string, duration?: number) {
    show({ message, type: 'success', duration })
  }

  /**
   * 错误提示
   */
  function error(message: string, duration?: number) {
    show({ message, type: 'error', duration })
  }

  /**
   * 警告提示
   */
  function warning(message: string, duration?: number) {
    show({ message, type: 'warning', duration })
  }

  /**
   * 信息提示
   */
  function info(message: string, duration?: number) {
    show({ message, type: 'info', duration })
  }

  return {
    show,
    hide,
    success,
    error,
    warning,
    info,
  }
}

/**
 * 全局 toast 实例（用于非组件环境）
 */
export const toast = {
  show: (options: ToastOptions) => uni.$emit('toast:show', options),
  hide: () => uni.$emit('toast:hide'),
  success: (message: string, duration?: number) =>
    uni.$emit('toast:show', { message, type: 'success', duration }),
  error: (message: string, duration?: number) =>
    uni.$emit('toast:show', { message, type: 'error', duration }),
  warning: (message: string, duration?: number) =>
    uni.$emit('toast:show', { message, type: 'warning', duration }),
  info: (message: string, duration?: number) =>
    uni.$emit('toast:show', { message, type: 'info', duration }),
}
