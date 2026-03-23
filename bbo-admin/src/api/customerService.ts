import request from './request'

export interface ServiceTranslation {
  id: number
  service_id: number
  locale: string
  name: string
}

export interface CustomerService {
  id: number
  admin_id?: number
  name: string
  avatar?: string
  email?: string
  phone?: string
  max_sessions: number
  current_sessions: number
  status: number
  status_text?: string
  is_enabled: number
  last_online_at?: string
  created_at?: string
  admin?: {
    id: number
    username: string
    nickname?: string
  }
  groups?: ServiceGroup[]
  translations?: ServiceTranslation[]
}

export interface ServiceGroup {
  id: number
  name: string
  description?: string
  sort: number
  is_enabled: number
}

// 获取客服列表
export function getCustomerServiceList(params: Record<string, any>) {
  return request.get('/customer-services', { params })
}

// 获取客服详情
export function getCustomerService(id: number) {
  return request.get<CustomerService>(`/customer-services/${id}`)
}

export interface ServiceFormData {
  admin_id?: number | null
  avatar?: string
  email?: string
  phone?: string
  max_sessions?: number
  is_enabled?: number
  group_ids?: number[]
  translations?: Record<string, { name: string }>
}

// 创建客服
export function createCustomerService(data: ServiceFormData) {
  return request.post('/customer-services', data)
}

// 更新客服
export function updateCustomerService(id: number, data: ServiceFormData) {
  return request.put(`/customer-services/${id}`, data)
}

// 删除客服
export function deleteCustomerService(id: number) {
  return request.delete(`/customer-services/${id}`)
}

// 切换客服状态
export function toggleServiceStatus(id: number, status: number) {
  return request.post(`/customer-services/${id}/toggle-status`, { status })
}

// 获取所有客服（下拉选择用）
export function getAllServices() {
  return request.get<CustomerService[]>('/customer-services/all')
}

// 获取工作组列表
export function getServiceGroups() {
  return request.get<ServiceGroup[]>('/customer-services/groups')
}

// 获取在线客服列表（用于转接）
export interface OnlineService {
  id: number
  name: string
  avatar?: string
  currentSessions: number
  maxSessions: number
}

export function getOnlineServices() {
  return request.get<OnlineService[]>('/customer-services/all', {
    params: { status: 1, is_enabled: 1 }
  })
}
