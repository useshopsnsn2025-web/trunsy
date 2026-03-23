import request from './request'

export interface User {
  id: number
  uuid: string
  nickname: string
  avatar?: string
  email?: string
  phone?: string
  gender: number
  birthday?: string
  bio?: string
  language: string
  currency: string
  is_seller: number
  is_verified: number
  status: number
  is_online: number
  last_heartbeat_at?: string
  online_device?: string
  online_ip?: string
  created_at: string
  updated_at?: string
  last_login_at?: string
  last_login_ip?: string
  statistics?: {
    goods_count: number
    order_buy_count: number
    order_sell_count: number
  }
}

export interface UserStatistics {
  total_users: number
  today_users: number
  month_users: number
  seller_count: number
  verified_count: number
  active_users: number
  online_users: number
}

// 创建用户
export function createUser(data: {
  email: string
  password?: string
  nickname?: string
  is_official?: number
}) {
  return request.post('/users', data)
}

// 获取用户列表
export function getUserList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  status?: number | string
  is_seller?: number | string
  is_verified?: number | string
  is_online?: number | string
  start_date?: string
  end_date?: string
}) {
  return request.get('/users', { params })
}

// 获取用户详情
export function getUserDetail(id: number) {
  return request.get(`/users/${id}`)
}

// 更新用户
export function updateUser(id: number, data: Partial<User>) {
  return request.put(`/users/${id}`, data)
}

// 禁用用户
export function disableUser(id: number) {
  return request.post(`/users/${id}/disable`)
}

// 启用用户
export function enableUser(id: number) {
  return request.post(`/users/${id}/enable`)
}

// 重置密码
export function resetUserPassword(id: number, password: string) {
  return request.post(`/users/${id}/reset-password`, { password })
}

// 用户统计
export function getUserStatistics() {
  return request.get('/users/statistics')
}

// 获取官方用户列表
export function getOfficialUsers() {
  return request.get('/users/official')
}
