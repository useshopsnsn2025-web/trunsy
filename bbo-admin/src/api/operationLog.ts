import request from './request'

export interface OperationLog {
  id: number
  admin_id?: number
  admin_name?: string
  module: string
  action: string
  method: string
  url: string
  params?: any
  result?: any
  ip?: string
  user_agent?: string
  created_at: string
}

export interface LogStatistics {
  total_logs: number
  today_logs: number
  week_logs: number
  module_stats: { module: string; count: number }[]
  daily_stats: { date: string; count: number }[]
}

// 获取日志列表
export function getOperationLogList(params: {
  page?: number
  pageSize?: number
  admin_id?: number | string
  module?: string
  action?: string
  keyword?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/operation-logs', { params })
}

// 获取日志详情
export function getOperationLogDetail(id: number) {
  return request.get(`/operation-logs/${id}`)
}

// 获取模块列表
export function getOperationLogModules() {
  return request.get('/operation-logs/modules')
}

// 获取日志统计
export function getOperationLogStatistics() {
  return request.get('/operation-logs/statistics')
}

// 清理日志
export function clearOperationLogs(days: number) {
  return request.post('/operation-logs/clear', { days })
}
