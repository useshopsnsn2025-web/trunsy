import request from './request'

export interface ScheduledTask {
  id: number
  name: string
  description?: string
  command: string
  cron_expression: string
  cron_description?: string
  status: number
  status_text?: string
  last_run_at?: string
  next_run_at?: string
  last_result?: number
  last_result_text?: string
  last_output?: string
  run_count: number
  fail_count: number
  timeout: number
  is_singleton: number
  sort: number
  created_at: string
  updated_at?: string
}

export interface ScheduledTaskLog {
  id: number
  task_id: number
  started_at: string
  ended_at?: string
  duration?: number
  status: number
  status_text?: string
  output?: string
  error?: string
  created_at: string
}

// 获取任务列表
export function getTaskList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
}) {
  return request.get('/scheduled-tasks', { params })
}

// 获取任务详情
export function getTaskDetail(id: number) {
  return request.get(`/scheduled-tasks/${id}`)
}

// 创建任务
export function createTask(data: {
  name: string
  description?: string
  command: string
  cron_expression: string
  status?: number
  timeout?: number
  is_singleton?: number
  sort?: number
}) {
  return request.post('/scheduled-tasks', data)
}

// 更新任务
export function updateTask(id: number, data: Partial<ScheduledTask>) {
  return request.put(`/scheduled-tasks/${id}`, data)
}

// 删除任务
export function deleteTask(id: number) {
  return request.delete(`/scheduled-tasks/${id}`)
}

// 启用任务
export function enableTask(id: number) {
  return request.post(`/scheduled-tasks/${id}/enable`)
}

// 禁用任务
export function disableTask(id: number) {
  return request.post(`/scheduled-tasks/${id}/disable`)
}

// 立即执行任务
export function runTask(id: number) {
  return request.post(`/scheduled-tasks/${id}/run`)
}

// 获取任务执行日志
export function getTaskLogs(id: number, params: {
  page?: number
  pageSize?: number
}) {
  return request.get(`/scheduled-tasks/${id}/logs`, { params })
}

// 清除任务日志
export function clearTaskLogs(id: number, days?: number) {
  return request.post(`/scheduled-tasks/${id}/clear-logs`, { days })
}

// 获取可用命令列表
export function getCommands() {
  return request.get('/scheduled-tasks/commands')
}

// 获取常用 Cron 表达式预设
export function getCronPresets() {
  return request.get('/scheduled-tasks/cron-presets')
}
