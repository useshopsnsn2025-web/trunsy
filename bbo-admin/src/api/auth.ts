import request from './request'

export interface LoginParams {
  username: string
  password: string
}

export interface LoginResult {
  token: string
  expires_in: number
  admin: AdminInfo
}

export interface AdminInfo {
  id: number
  username: string
  nickname: string
  avatar?: string
  email?: string
  phone?: string
  role?: {
    id: number
    name: string
    code: string
    permissions: string[]
  }
  last_login_at?: string
  last_login_ip?: string
}

// 登录
export function login(data: LoginParams) {
  return request.post<LoginResult>('/auth/login', data)
}

// 退出登录
export function logout() {
  return request.post('/auth/logout')
}

// 获取当前登录信息
export function getAdminInfo() {
  return request.get<AdminInfo>('/auth/info')
}

// 修改密码
export function changePassword(data: { old_password: string; new_password: string }) {
  return request.post('/auth/change-password', data)
}
