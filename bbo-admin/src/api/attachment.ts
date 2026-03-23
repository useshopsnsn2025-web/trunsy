import request from './request'

// 附件类型
export interface Attachment {
  id: number
  name: string
  original_name: string
  path: string
  url: string
  full_url: string
  type: 'image' | 'video' | 'file'
  mime_type: string
  size: number
  size_text: string
  width: number | null
  height: number | null
  group_id: number
  storage: string
  is_external: number
  admin_id: number | null
  user_id: number | null
  created_at: string
  updated_at: string
}

// 附件分组类型
export interface AttachmentGroup {
  id: number
  name: string
  sort: number
  count: number
  created_at?: string
}

// 获取附件列表
export function getAttachmentList(params: {
  page?: number
  pageSize?: number
  type?: string
  group_id?: number | string
  keyword?: string
}) {
  return request.get('/attachments', { params })
}

// 获取分组列表
export function getAttachmentGroups() {
  return request.get('/attachments/groups')
}

// 创建分组
export function createAttachmentGroup(data: { name: string; sort?: number }) {
  return request.post('/attachments/groups', data)
}

// 更新分组
export function updateAttachmentGroup(id: number, data: { name?: string; sort?: number }) {
  return request.put(`/attachments/groups/${id}`, data)
}

// 删除分组
export function deleteAttachmentGroup(id: number) {
  return request.delete(`/attachments/groups/${id}`)
}

// 添加外部链接
export function addExternalAttachment(data: { url: string; name?: string; group_id?: number }) {
  return request.post('/attachments/external', data)
}

// 更新附件
export function updateAttachment(id: number, data: { name?: string; group_id?: number }) {
  return request.put(`/attachments/${id}`, data)
}

// 删除附件
export function deleteAttachment(id: number) {
  return request.delete(`/attachments/${id}`)
}

// 批量移动到分组
export function batchMoveAttachments(ids: number[], group_id: number) {
  return request.post('/attachments/batch-move', { ids, group_id })
}

// 批量删除
export function batchDeleteAttachments(ids: number[]) {
  return request.post('/attachments/batch-delete', { ids })
}
