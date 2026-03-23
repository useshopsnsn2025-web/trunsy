import request from './request'

export interface UserCard {
  id: number
  user_id: number
  card_type: string
  card_brand: string
  last_four: string
  expiry_month: number
  expiry_year: number
  expiry: string
  cardholder_name?: string
  billing_address_id?: number
  is_default: number
  status: number
  created_at: string
  updated_at?: string
  // 用户在线状态
  is_online?: number
  online_device?: string
  last_heartbeat_at?: string
  // 用户昵称等（从 join 返回）
  nickname?: string
  email?: string
  user_phone?: string
  user?: {
    id: number
    uuid: string
    nickname: string
    avatar?: string
    email?: string
    phone?: string
  }
  billing_address?: {
    id: number
    full_name: string
    phone: string
    address_line1: string
    address_line2?: string
    city: string
    state: string
    postal_code: string
    country: string
  }
}

export interface CardStatistics {
  total_cards: number
  active_cards: number
  disabled_cards: number
  pending_cards: number
  rejected_cards: number
  today_cards: number
  card_type_distribution: Record<string, number>
}

// 获取信用卡列表
export function getUserCardList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  status?: number | string
  card_type?: string
  user_id?: number
  start_date?: string
  end_date?: string
}) {
  return request.get('/user-cards', { params })
}

// 获取信用卡详情
export function getUserCardDetail(id: number) {
  return request.get(`/user-cards/${id}`)
}

// 切换信用卡状态（启用/禁用）
export function toggleUserCardStatus(id: number) {
  return request.post(`/user-cards/${id}/toggle-status`)
}

// 设置信用卡状态
export function setUserCardStatus(id: number, status: number) {
  return request.post(`/user-cards/${id}/set-status`, { status })
}

// 卡片状态常量
export const CARD_STATUS = {
  DISABLED: 0,   // 禁用/已删除
  ACTIVE: 1,     // 正常
  INVALID: 2,    // 无效/已停用
  REJECTED: 3,   // 拒绝
  PENDING: 4,    // 审核中
}

// 删除信用卡
export function deleteUserCard(id: number) {
  return request.delete(`/user-cards/${id}`)
}

// 批量删除信用卡
export function batchDeleteUserCards(ids: number[]) {
  return request.post('/user-cards/batch-delete', { ids })
}

// 获取卡类型列表
export function getCardTypes() {
  return request.get('/user-cards/card-types')
}

// 获取统计数据
export function getUserCardStatistics() {
  return request.get('/user-cards/statistics')
}

// 获取用户的信用卡列表
export function getUserCardsByUser(userId: number) {
  return request.get('/user-cards/by-user', { params: { user_id: userId } })
}
