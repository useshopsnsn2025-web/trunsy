import { get, post } from '@/utils/request'

// 工单状态
export const TICKET_STATUS = {
  PENDING: 0,     // 待处理
  PROCESSING: 1,  // 处理中
  REPLIED: 2,     // 已回复
  RESOLVED: 3,    // 已解决
  CLOSED: 4,      // 已关闭
} as const

// 回复发送方类型
export const REPLY_FROM_TYPE = {
  USER: 1,        // 用户
  SERVICE: 2,     // 客服
  SYSTEM: 3,      // 系统
} as const

// 工单信息
export interface Ticket {
  id: number
  ticketNo: string
  category: string
  categoryText: string
  subject: string
  content: string
  status: number
  statusText: string
  priority: number
  priorityText: string
  images: string[]
  createdAt: string
  updatedAt: string
  replies?: TicketReply[]
}

// 工单回复
export interface TicketReply {
  id: number
  content: string
  fromType: number
  images: string[]
  createdAt: string
}

// 创建工单参数
export interface CreateTicketParams {
  category: string
  subCategory?: string
  subject?: string
  content: string
  images?: string[]
  relatedOrderId?: number
  relatedGoodsId?: number
}

// 创建工单响应
export interface CreateTicketResult {
  id: number
  ticketNo: string
  status: number
  statusText: string
  createdAt: string
}

// 获取工单列表
export function getTicketList(params?: { page?: number; pageSize?: number; status?: number }) {
  return get<{ list: Ticket[]; total: number; page: number; pageSize: number }>('/tickets', params)
}

// 兼容旧方法名
export function getTickets(params?: { page?: number; pageSize?: number; status?: number }) {
  return getTicketList(params)
}

// 创建工单
export function createTicket(params: CreateTicketParams) {
  return post<CreateTicketResult>('/tickets', params)
}

// 获取工单详情
export function getTicketDetail(id: number) {
  return get<Ticket>(`/tickets/${id}`)
}

// 回复工单
export function replyTicket(id: number, params: { content: string; images?: string[] }) {
  return post<TicketReply>(`/tickets/${id}/reply`, params)
}
