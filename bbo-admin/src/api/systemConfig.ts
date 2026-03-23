import request from './request'

export interface SystemConfig {
  id: number
  group: string
  key: string
  value: string
  type: 'string' | 'number' | 'boolean' | 'json' | 'image'
  name: string
  description?: string
  sort: number
  created_at: string
  updated_at?: string
}

// 获取配置列表
export function getConfigList(params: {
  page?: number
  pageSize?: number
  group?: string
  keyword?: string
}) {
  return request.get('/configs', { params })
}

// 获取配置分组
export function getConfigGroups() {
  return request.get('/configs/groups')
}

// 获取配置详情
export function getConfigDetail(id: number) {
  return request.get(`/configs/${id}`)
}

// 创建配置
export function createConfig(data: {
  group: string
  key: string
  value: string
  type: string
  name: string
  description?: string
  sort?: number
}) {
  return request.post('/configs', data)
}

// 更新配置
export function updateConfig(id: number, data: Partial<SystemConfig>) {
  return request.put(`/configs/${id}`, data)
}

// 删除配置
export function deleteConfig(id: number) {
  return request.delete(`/configs/${id}`)
}

// 批量更新配置
export function batchUpdateConfigs(configs: { key: string; value: string }[]) {
  return request.post('/configs/batch', { configs })
}

// 获取汇率状态
export function getExchangeRateStatus() {
  return request.get('/configs/exchange-rate/status')
}

// 手动更新汇率
export function updateExchangeRate() {
  return request.post('/configs/exchange-rate/update')
}

// 发送测试邮件
export function sendTestMail(to: string) {
  return request.post('/configs/mail/test', { to })
}
