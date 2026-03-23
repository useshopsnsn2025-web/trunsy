import request from './request'

export interface QuickReplyTranslation {
  id: number
  reply_id: number
  locale: string
  title: string
  content: string
}

export interface QuickReply {
  id: number
  group_id?: number
  service_id?: number
  title: string
  content: string
  shortcut?: string
  use_count: number
  sort: number
  is_enabled: number
  created_at?: string
  group?: QuickReplyGroup
  translations?: QuickReplyTranslation[]
}

export interface QuickReplyGroup {
  id: number
  name: string
  sort: number
  replies?: QuickReply[]
}

// 获取快捷回复列表
export function getQuickReplyList(params: Record<string, any>) {
  return request.get('/quick-replies', { params })
}

// 获取快捷回复详情
export function getQuickReply(id: number) {
  return request.get<QuickReply>(`/quick-replies/${id}`)
}

export interface QuickReplyFormData {
  group_id?: number | null
  shortcut?: string
  sort?: number
  is_enabled?: number
  translations?: Record<string, { title: string; content: string }>
}

// 创建快捷回复
export function createQuickReply(data: QuickReplyFormData) {
  return request.post('/quick-replies', data)
}

// 更新快捷回复
export function updateQuickReply(id: number, data: QuickReplyFormData) {
  return request.put(`/quick-replies/${id}`, data)
}

// 删除快捷回复
export function deleteQuickReply(id: number) {
  return request.delete(`/quick-replies/${id}`)
}

// 获取所有快捷回复（按分组）
export function getAllQuickReplies() {
  return request.get<QuickReplyGroup[]>('/quick-replies/all')
}

// 获取分组列表
export function getQuickReplyGroups() {
  return request.get<QuickReplyGroup[]>('/quick-replies/groups')
}

// 创建分组
export function createQuickReplyGroup(data: { name: string; sort?: number }) {
  return request.post('/quick-replies/groups', data)
}

// 更新分组
export function updateQuickReplyGroup(id: number, data: { name?: string; sort?: number }) {
  return request.put(`/quick-replies/groups/${id}`, data)
}

// 删除分组
export function deleteQuickReplyGroup(id: number) {
  return request.delete(`/quick-replies/groups/${id}`)
}
