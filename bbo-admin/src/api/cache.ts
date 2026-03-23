import request from './request'

export interface CacheStatus {
  cache_size: number
  cache_size_text: string
  temp_size: number
  temp_size_text: string
  log_size: number
  log_size_text: string
  cache_types: Record<string, string>
}

export interface ClearResult {
  cleared: string[]
}

export interface ClearLogsResult {
  cleared_count: number
  cleared_size: number
  cleared_size_text: string
}

// 获取缓存状态
export function getCacheStatus() {
  return request.get<CacheStatus>('/cache/status')
}

// 清理缓存
export function clearCache(type: string = 'all') {
  return request.post<ClearResult>('/cache/clear', { type })
}

// 清理日志
export function clearLogs(days: number = 0) {
  return request.post<ClearLogsResult>('/cache/clear-logs', { days })
}
