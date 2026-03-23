import request from './request'

export interface Country {
  id: number
  code: string
  name: string
  currency_code: string
  flag: string
  locale: string
  sort: number
  is_active: boolean
  translations?: Record<string, string>
  created_at: string
  updated_at: string
}

// 获取国家列表
export function getCountryList(params?: { page?: number; pageSize?: number; keyword?: string }) {
  return request.get('/countries', { params })
}

// 获取国家详情
export function getCountryDetail(id: number) {
  return request.get(`/countries/${id}`)
}

// 新增国家
export function createCountry(data: Partial<Country>) {
  return request.post('/countries', data)
}

// 更新国家
export function updateCountry(id: number, data: Partial<Country>) {
  return request.put(`/countries/${id}`, data)
}

// 删除国家
export function deleteCountry(id: number) {
  return request.delete(`/countries/${id}`)
}

// 切换国家状态
export function updateCountryStatus(id: number) {
  return request.put(`/countries/${id}/status`)
}

// 获取国家选项（下拉）
export function getCountryOptions() {
  return request.get('/countries/options')
}

// 获取可用语言列表（用于翻译）
export function getCountryLanguages() {
  return request.get('/countries/languages')
}

// 更新排序
export function updateCountrySort(ids: number[]) {
  return request.post('/countries/sort', { ids })
}

// 设置默认国家
export function setDefaultCountry(id: number) {
  return request.put(`/countries/${id}/set-default`)
}

// 获取默认国家
export function getDefaultCountry() {
  return request.get('/countries/default')
}
