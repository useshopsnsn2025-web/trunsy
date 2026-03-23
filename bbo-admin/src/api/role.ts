import request from './request'

export interface Role {
  id: number
  name: string
  code: string
  description?: string
  permissions: string[]
  status: number
  sort: number
  created_at: string
  updated_at?: string
}

export interface Permission {
  key: string
  name: string
  children?: Permission[]
}

// 获取角色列表
export function getRoleList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  status?: number | string
}) {
  return request.get('/roles', { params })
}

// 获取所有角色（下拉选择用）
export function getAllRoles() {
  return request.get('/roles/all')
}

// 获取角色详情
export function getRoleDetail(id: number) {
  return request.get(`/roles/${id}`)
}

// 创建角色
export function createRole(data: {
  name: string
  code: string
  description?: string
  permissions?: string[]
  status?: number
  sort?: number
}) {
  return request.post('/roles', data)
}

// 更新角色
export function updateRole(id: number, data: Partial<Role>) {
  return request.put(`/roles/${id}`, data)
}

// 删除角色
export function deleteRole(id: number) {
  return request.delete(`/roles/${id}`)
}

// 获取权限列表
export function getPermissions() {
  return request.get('/roles/permissions', {
    params: { _t: Date.now() } // 添加时间戳破坏缓存
  })
}
