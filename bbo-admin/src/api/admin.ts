import request from './request'

export interface Admin {
  id: number
  username: string
  nickname?: string
  avatar?: string
  email?: string
  phone?: string
  role_id?: number
  role_name?: string
  status: number
  last_login_at?: string
  last_login_ip?: string
  created_at: string
  updated_at?: string
}

// 获取管理员列表
export function getAdminList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  status?: number | string
  role_id?: number | string
}) {
  return request.get('/admins', { params })
}

// 获取管理员详情
export function getAdminDetail(id: number) {
  return request.get(`/admins/${id}`)
}

// 创建管理员
export function createAdmin(data: {
  username: string
  password: string
  nickname?: string
  email?: string
  phone?: string
  role_id?: number
  status?: number
}) {
  return request.post('/admins', data)
}

// 更新管理员
export function updateAdmin(id: number, data: Partial<Admin>) {
  return request.put(`/admins/${id}`, data)
}

// 删除管理员
export function deleteAdmin(id: number) {
  return request.delete(`/admins/${id}`)
}

// 重置密码
export function resetAdminPassword(id: number, password: string) {
  return request.post(`/admins/${id}/reset-password`, { password })
}

// 切换状态
export function toggleAdminStatus(id: number) {
  return request.post(`/admins/${id}/toggle-status`)
}
