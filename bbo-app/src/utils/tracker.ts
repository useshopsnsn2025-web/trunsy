/**
 * Analytics Tracker SDK
 * Tracks user behavior events and page durations for funnel analysis.
 *
 * Usage:
 *   import { tracker } from '@/utils/tracker'
 *   tracker.event('click_add_cart', { goods_id: 123 })
 *
 * Auto-tracking:
 *   Use trackerMixin in pages for automatic page enter/leave + duration tracking.
 */
import { API_CONFIG } from '@/config/api'
import { getToken } from './storage'

interface TrackerEvent {
  event_type: string
  event_category: string
  page: string
  target: string
  properties?: Record<string, any>
  session_id: string
  device_type: string
  timestamp: number
}

interface PageDurationRecord {
  page: string
  page_title: string
  referrer_page: string
  session_id: string
  duration_ms: number
  enter_at: number
  leave_at: number
}

// Generate or retrieve session ID
function getSessionId(): string {
  let sid = uni.getStorageSync('_tracker_sid')
  if (!sid) {
    sid = 'sid_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10)
    uni.setStorageSync('_tracker_sid', sid)
  }
  return sid
}

// Detect device type
function getDeviceType(): string {
  // #ifdef APP-PLUS
  const os = uni.getSystemInfoSync().platform
  return os === 'ios' ? 'ios' : 'android'
  // #endif
  // #ifdef H5
  return 'h5'
  // #endif
  // #ifdef MP
  return 'miniprogram'
  // #endif
  return 'unknown'
}

class Tracker {
  private eventQueue: TrackerEvent[] = []
  private durationQueue: PageDurationRecord[] = []
  private sessionId: string
  private deviceType: string
  private flushTimer: ReturnType<typeof setInterval> | null = null
  private readonly FLUSH_INTERVAL = 5000 // 5 seconds
  private readonly MAX_QUEUE_SIZE = 10
  private currentPage: string = ''
  private currentPageTitle: string = ''
  private pageEnterTime: number = 0
  private previousPage: string = ''

  constructor() {
    this.sessionId = getSessionId()
    this.deviceType = getDeviceType()
    this.startFlushTimer()

    // H5: 页面关闭/刷新时立即发送队列中的事件
    // #ifdef H5
    if (typeof window !== 'undefined') {
      window.addEventListener('beforeunload', () => {
        this.flush()
      })
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
          this.flush()
        }
      })
    }
    // #endif
  }

  /**
   * Track a custom event
   */
  event(eventType: string, properties?: Record<string, any>, target?: string) {
    const currentPages = getCurrentPages()
    const page = currentPages.length > 0 ? currentPages[currentPages.length - 1].route || '' : ''

    const evt: TrackerEvent = {
      event_type: eventType,
      event_category: this.categorizeEvent(eventType),
      page: '/' + page,
      target: target || (properties?.goods_id ? String(properties.goods_id) : ''),
      properties,
      session_id: this.sessionId,
      device_type: this.deviceType,
      timestamp: Date.now(),
    }

    this.eventQueue.push(evt)

    if (this.eventQueue.length >= this.MAX_QUEUE_SIZE) {
      this.flush()
    }
  }

  /**
   * Track page enter (called by mixin)
   */
  pageEnter(page: string, title: string = '') {
    // 规范化路径：确保以 / 开头
    if (page && !page.startsWith('/')) {
      page = '/' + page
    }
    this.previousPage = this.currentPage
    this.currentPage = page
    this.currentPageTitle = title
    this.pageEnterTime = Date.now()

    // Map page path to event type
    const pageEvent = this.getPageEventType(page)
    if (pageEvent) {
      this.event(pageEvent)
    }
  }

  /**
   * Track page leave (called by mixin)
   */
  pageLeave() {
    if (!this.currentPage || !this.pageEnterTime) return

    const now = Date.now()
    const durationMs = now - this.pageEnterTime

    if (durationMs >= 500) {
      this.durationQueue.push({
        page: this.currentPage,
        page_title: this.currentPageTitle,
        referrer_page: this.previousPage,
        session_id: this.sessionId,
        duration_ms: durationMs,
        enter_at: this.pageEnterTime,
        leave_at: now,
      })
    }

    this.pageEnterTime = 0
  }

  /**
   * Flush queued data to server
   */
  async flush() {
    const events = this.eventQueue.splice(0)
    const durations = this.durationQueue.splice(0)

    if (events.length > 0) {
      this.send('/analytics/report', { events })
    }
    if (durations.length > 0) {
      this.send('/analytics/page-duration', { records: durations })
    }
  }

  private startFlushTimer() {
    if (this.flushTimer) return
    this.flushTimer = setInterval(() => {
      if (this.eventQueue.length > 0 || this.durationQueue.length > 0) {
        this.flush()
      }
    }, this.FLUSH_INTERVAL)
  }

  private send(path: string, data: any) {
    const token = getToken()
    const header: Record<string, string> = {
      'Content-Type': 'application/json',
    }
    if (token) {
      header['Authorization'] = `Bearer ${token}`
    }

    uni.request({
      url: API_CONFIG.baseUrl + path,
      method: 'POST',
      data,
      header,
      // Fire and forget - don't block user
      success: () => {},
      fail: () => {},
    })
  }

  private categorizeEvent(eventType: string): string {
    if (eventType.startsWith('page_view')) return 'page'
    if (eventType.startsWith('click_')) return 'click'
    if (['payment_success', 'click_submit_order'].includes(eventType)) return 'conversion'
    return 'interaction'
  }

  /**
   * Map page paths to page view event types
   */
  private getPageEventType(page: string): string {
    const pageMap: Record<string, string> = {
      '/pages/index/index': 'page_view_home',
      '/pages/goods/list': 'page_view_goods_list',
      '/pages/goods/detail': 'page_view_goods_detail',
      '/pages/search/index': 'page_view_goods_list',
      '/pages/category/index': 'page_view_goods_list',
      '/pages/order/checkout': 'page_view_checkout',
      '/pages/cart/index': 'page_view_cart',
      '/pages/order/payment-success': 'page_view_payment_success',
      '/pages/auth/login': 'page_view_login',
      '/pages/auth/register': 'page_view_register',
      '/pages/user/index': 'page_view_profile',
    }

    // Exact match
    if (pageMap[page]) return pageMap[page]

    // Prefix match
    for (const [prefix, event] of Object.entries(pageMap)) {
      if (page.startsWith(prefix)) return event
    }

    return ''
  }
}

export const tracker = new Tracker()

/**
 * Vue mixin for automatic page tracking.
 * Add to page's setup or use in onShow/onHide.
 *
 * Usage in pages:
 *   import { usePageTracker } from '@/utils/tracker'
 *   usePageTracker()
 */
export function usePageTracker(title?: string) {
  const pages = getCurrentPages()
  const currentPage = pages.length > 0 ? '/' + (pages[pages.length - 1].route || '') : ''

  // onShow
  const onShowHook = () => {
    tracker.pageEnter(currentPage, title || '')
  }

  // onHide
  const onHideHook = () => {
    tracker.pageLeave()
  }

  // Use uni-app lifecycle
  try {
    // Dynamic import to avoid circular deps in non-page contexts
    const uniApp = require('@dcloudio/uni-app') as { onShow: (cb: () => void) => void; onHide: (cb: () => void) => void }
    uniApp.onShow(onShowHook)
    uniApp.onHide(onHideHook)
  } catch {
    // Fallback: call manually
  }

  return { onShowHook, onHideHook }
}
