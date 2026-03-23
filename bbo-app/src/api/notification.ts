// 通知相关 API
import { get, post, put, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 通知分类
export type NotificationCategory = 'all' | 'order' | 'important' | 'promotion' | 'account'

// 通知信息
export interface Notification {
  id: number
  type: string
  category: string
  title: string
  content: string
  data?: {
    order_id?: number
    order_no?: string
    goods_title?: string
    goods_image?: string
    tracking_no?: string
    carrier_name?: string
    [key: string]: any
  }
  is_read: boolean
  read_at?: string
  created_at: string
}

// 通知列表参数
export interface NotificationListParams {
  page?: number
  pageSize?: number
  category?: string
  type?: string
  is_read?: number // 0未读 1已读
}

// 通知列表响应
export interface NotificationListResponse {
  list: Notification[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 通知统计
export interface NotificationStats {
  unread_count: number
  total_count: number
  type_stats: Record<string, number>
  category_stats: Record<string, number>
}

// 通知分类常量
export const NOTIFICATION_CATEGORIES = {
  ALL: 'all',
  ORDER: 'order',
  IMPORTANT: 'important',
  PROMOTION: 'promotion',
  ACCOUNT: 'account',
} as const

// 分类列表（用于Tab）
export const CATEGORY_LIST: Array<{ key: NotificationCategory; icon: string }> = [
  { key: 'all', icon: 'bi-bell' },
  { key: 'important', icon: 'bi-exclamation-triangle' },
  { key: 'order', icon: 'bi-bag' },
  { key: 'promotion', icon: 'bi-gift' },
  { key: 'account', icon: 'bi-person' },
]

// 通知类型常量
export const NOTIFICATION_TYPES = {
  ORDER_CREATED: 'order_created',
  PAYMENT_SUCCESS: 'payment_success',
  ORDER_SHIPPED: 'order_shipped',
  ORDER_DELIVERED: 'order_delivered',
  ORDER_CANCELLED: 'order_cancelled',
  REFUND_SUCCESS: 'refund_success',
  PREAUTH_VOIDED: 'preauth_voided',
} as const

// 通知类型图标映射
export const NOTIFICATION_ICONS: Record<string, string> = {
  order_created: 'bi-bag-check',
  payment_success: 'bi-credit-card-2-front',
  order_shipped: 'bi-truck',
  order_delivered: 'bi-box-seam-fill',
  order_cancelled: 'bi-x-circle',
  refund_success: 'bi-arrow-counterclockwise',
  preauth_voided: 'bi-unlock',
}

// 获取通知列表
export function getNotifications(params: NotificationListParams = {}) {
  return get<NotificationListResponse>(API_PATHS.notifications.list, params)
}

// 获取通知详情
export function getNotificationDetail(id: number) {
  return get<Notification>(API_PATHS.notifications.detail(id))
}

// 标记单个通知为已读
export function markNotificationRead(id: number) {
  return put(API_PATHS.notifications.markRead(id))
}

// 标记所有通知为已读
export function markAllNotificationsRead() {
  return put(API_PATHS.notifications.markAllRead)
}

// 批量标记通知为已读
export function batchMarkRead(ids: number[]) {
  return put(API_PATHS.notifications.batchRead, { ids })
}

// 删除通知
export function deleteNotification(id: number) {
  return del(API_PATHS.notifications.delete(id))
}

// 获取未读通知数量
export function getUnreadCount() {
  return get<{ count: number }>(API_PATHS.notifications.unreadCount)
}

// 获取通知统计
export function getNotificationStats() {
  return get<NotificationStats>(API_PATHS.notifications.stats)
}
