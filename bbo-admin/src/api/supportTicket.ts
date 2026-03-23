import request from './request'

export interface SupportTicket {
  id: number
  ticket_no: string
  user_id: number
  service_id?: number
  category: string
  category_text?: string
  sub_category?: string
  sub_category_text?: string
  subject: string
  content: string
  images?: string[]
  related_order_id?: number
  related_goods_id?: number
  priority: number
  priority_text?: string
  status: number
  status_text?: string
  is_rated: number
  rating?: number
  rating_comment?: string
  first_reply_at?: string
  resolved_at?: string
  closed_at?: string
  created_at?: string
  user?: {
    id: number
    nickname?: string
    phone?: string
    avatar?: string
  }
  service?: {
    id: number
    name: string
  }
  replies?: TicketReply[]
  // 关联订单
  related_order?: {
    id: number
    order_no: string
    status: number
    status_text?: string
    goods_snapshot?: {
      title: string
      image: string
      price: number
    }
    total_amount: number
    currency: string
    created_at?: string
  }
  // 关联商品
  goods?: {
    id: number
    title: string
    cover_image: string
    price: number
  }
}

export interface TicketReply {
  id: number
  ticket_id: number
  from_type: number
  from_id: number
  content: string
  images?: string[]
  is_internal: number
  created_at?: string
}

export interface TicketStatistics {
  pending: number
  processing: number
  replied: number
  resolved: number
  today_new: number
  today_resolved: number
  avg_first_reply_minutes: number
}

// 获取工单列表
export function getTicketList(params: Record<string, any>) {
  return request.get('/support-tickets', { params })
}

// 获取工单详情
export function getTicket(id: number) {
  return request.get<SupportTicket>(`/support-tickets/${id}`)
}

// 分配客服
export function assignTicket(id: number, serviceId: number) {
  return request.post(`/support-tickets/${id}/assign`, { service_id: serviceId })
}

// 回复工单
export function replyTicket(id: number, data: { content: string; images?: string[]; is_internal?: number }) {
  return request.post(`/support-tickets/${id}/reply`, data)
}

// 关闭工单
export function closeTicket(id: number) {
  return request.post(`/support-tickets/${id}/close`)
}

// 解决工单
export function resolveTicket(id: number) {
  return request.post(`/support-tickets/${id}/resolve`)
}

// 修改优先级
export function setTicketPriority(id: number, priority: number) {
  return request.post(`/support-tickets/${id}/priority`, { priority })
}

// 获取统计数据
export function getTicketStatistics() {
  return request.get<TicketStatistics>('/support-tickets/statistics')
}

// 获取分类列表
export function getTicketCategories() {
  return request.get<{ key: string; name: string }[]>('/support-tickets/categories')
}
