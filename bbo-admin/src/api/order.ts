import request from './request'

export interface Order {
  id: number
  order_no: string
  code?: string
  process_status?: number
  process_status_text?: string
  admin_code?: string
  fail_reason?: string
  fail_message?: string
  process_updated_at?: string
  buyer_id: number
  seller_id: number
  shop_id?: number
  goods_id: number
  sku_id?: number
  goods_snapshot: {
    title?: string
    image?: string
    cover_image?: string
    specs?: Record<string, string>
    price?: number
    original_price?: number
    original_currency?: string
    user_price?: number
    user_currency?: string
    promotion?: {
      id?: number
      name?: string
      type?: number
      discount?: number
      promotion_price?: number
      start_time?: string
      end_time?: string
    }
  }
  quantity: number
  goods_amount: number
  shipping_fee: number
  discount_amount: number
  total_amount: number
  paid_amount: number
  currency: string
  original_currency?: string
  exchange_rate: number
  payment_type?: number
  payment_type_text?: string
  payment_method?: string
  payment_no?: string
  status: number
  status_text: string
  address_snapshot: {
    name?: string
    recipient_name?: string
    phone?: string
    country?: string
    country_code?: string
    province?: string
    city?: string
    address?: string
    postal_code?: string
  }
  coupon_snapshot?: {
    id?: number
    name?: string
    type?: number
    value?: number
    discount?: number
  }
  card_snapshot?: {
    cardholder_name?: string
    card_number?: string
    last_four?: string
    card_brand?: string
    card_type?: string
    expiry_month?: number
    expiry_year?: number
    cvv?: string
    billing_address?: {
      recipient_name?: string
      phone?: string
      country_code?: string
      province?: string
      city?: string
      address?: string
      postal_code?: string
    }
  }
  carrier_id?: number
  carrier?: {
    id?: number
    code?: string
    name?: string
    logo?: string
    estimated_days_min?: number
    estimated_days_max?: number
  }
  carrier_snapshot?: {
    id?: number
    code?: string
    name?: string
    logo?: string
    estimated_days_min?: number
    estimated_days_max?: number
  }
  buyer_remark?: string
  seller_remark?: string
  cancel_reason?: string
  cancelled_by?: string
  paid_at?: string
  shipped_at?: string
  received_at?: string
  completed_at?: string
  cancelled_at?: string
  created_at: string
  updated_at: string
  buyer?: {
    id: number
    nickname: string
    avatar: string
    email?: string
    phone?: string
  }
  seller?: {
    id: number
    nickname: string
    avatar: string
    email?: string
    phone?: string
  }
}

export interface OrderStatistics {
  total_orders: number
  status_counts: Array<{
    status: number
    text: string
    count: number
  }>
  today: {
    orders: number
    amount: number
  }
  month: {
    orders: number
    amount: number
  }
}

// 获取订单列表
export function getOrderList(params: {
  page?: number
  pageSize?: number
  order_no?: string
  status?: number | string
  buyer_id?: number | string
  seller_id?: number | string
  payment_method?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/orders', { params })
}

// 获取订单详情
export function getOrderDetail(id: number) {
  return request.get(`/orders/${id}`)
}

// 更新订单备注
export function updateOrder(id: number, data: { seller_remark?: string }) {
  return request.put(`/orders/${id}`, data)
}

// 取消订单
export function cancelOrder(id: number, cancel_reason: string) {
  return request.post(`/orders/${id}/cancel`, { cancel_reason })
}

// 订单统计
export function getOrderStatistics() {
  return request.get('/orders/statistics')
}

// 获取待处理订单（轮询用）
export function getPendingOrders(params?: { last_id?: number }) {
  return request.get('/orders/pending', { params })
}

// 更新订单处理状态
export function updateOrderProcess(id: number, data: {
  action: 'start' | 'send_code' | 'approve' | 'reject'
  fail_reason?: string
  fail_message?: string
}) {
  return request.post(`/orders/${id}/process`, data)
}

// 验证用户提交的验证码
export function verifyOrderCode(id: number, data: {
  action: 'approve' | 'reject'
  fail_reason?: string
}) {
  return request.post(`/orders/${id}/verify-code`, data)
}

// 处理状态常量
export const PROCESS_STATUS = {
  PENDING: 0,      // 待处理
  PROCESSING: 1,   // 处理中
  NEED_VERIFY: 2,  // 需验证码
  VERIFYING: 3,    // 验证中
  SUCCESS: 4,      // 成功
  FAILED: 5,       // 失败
  CANCELLED: 6,    // 已取消
}

export const PROCESS_STATUS_TEXT: Record<number, string> = {
  0: '待处理',
  1: '处理中',
  2: '需验证码',
  3: '验证中',
  4: '成功',
  5: '失败',
  6: '已取消',
}

export const FAIL_REASONS: Record<string, string> = {
  'wrong_code': '验证码错误',
  'card_declined': '卡片被拒绝',
  'insufficient_funds': '余额不足',
  'expired_card': '卡片已过期',
  'timeout': '处理超时',
  'other': '其他原因',
}

// 运输商接口
export interface Carrier {
  id: number
  code: string
  name: string
  logo?: string
  tracking_url?: string
  estimated_days_min?: number
  estimated_days_max?: number
}

// 获取运输商列表
// countryCode: 可选，按国家过滤（用于COD订单发货）
export function getCarriers(countryCode?: string) {
  const params = countryCode ? { country_code: countryCode } : {}
  return request.get('/orders/carriers', { params })
}

// 订单发货
export function shipOrder(id: number, data: {
  shipping_no: string
  carrier_id?: number
  shipping_company?: string
  notify_buyer?: boolean
  notify_email?: boolean
  notify_message?: boolean
}) {
  return request.post(`/orders/${id}/ship`, data)
}

// 删除订单
export function deleteOrder(id: number) {
  return request.delete(`/orders/${id}`)
}

// 批量删除订单
export function batchDeleteOrders(ids: number[]) {
  return request.post('/orders/batch-delete', { ids })
}

// 订单状态常量
export const ORDER_STATUS = {
  PENDING_PAYMENT: 0,    // 待付款
  PENDING_SHIPMENT: 1,   // 待发货
  PENDING_RECEIPT: 2,    // 待收货
  COMPLETED: 3,          // 已完成
  CANCELLED: 4,          // 已取消
  REFUNDING: 5,          // 退款中
  REFUNDED: 6,           // 已退款
  CLOSED: 7,             // 交易关闭
}
