import request from './request'

export interface Currency {
  id: number
  code: string
  name: string
  symbol: string
  decimals: number
  sort: number
  is_active: boolean
  created_at: string
  updated_at: string
}

// 获取货币列表
export function getCurrencyList(params?: { page?: number; pageSize?: number; keyword?: string }) {
  return request.get('/currencies', { params })
}

// 获取货币详情
export function getCurrencyDetail(id: number) {
  return request.get(`/currencies/${id}`)
}

// 新增货币
export function createCurrency(data: Partial<Currency>) {
  return request.post('/currencies', data)
}

// 更新货币
export function updateCurrency(id: number, data: Partial<Currency>) {
  return request.put(`/currencies/${id}`, data)
}

// 删除货币
export function deleteCurrency(id: number) {
  return request.delete(`/currencies/${id}`)
}

// 切换货币状态
export function updateCurrencyStatus(id: number) {
  return request.put(`/currencies/${id}/status`)
}

// 获取货币选项（下拉）
export function getCurrencyOptions() {
  return request.get('/currencies/options')
}

// 更新排序
export function updateCurrencySort(ids: number[]) {
  return request.post('/currencies/sort', { ids })
}
