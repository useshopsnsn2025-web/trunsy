import request from './request'

export interface SellFaqTranslation {
  question: string
  answer: string
}

export interface SellFaq {
  id: number
  sort_order: number
  status: number
  created_at?: string
  updated_at?: string
  translations: Record<string, SellFaqTranslation>
}

export interface SellFaqListParams {
  page?: number
  pageSize?: number
  status?: number | string
}

// 获取FAQ列表
export function getSellFaqList(params: SellFaqListParams) {
  return request.get('/sell-faq/list', { params })
}

// 获取FAQ详情
export function getSellFaqDetail(id: number) {
  return request.get('/sell-faq/detail', { params: { id } })
}

export interface SellFaqFormData {
  id?: number
  sort_order?: number
  status?: number
  translations?: Record<string, SellFaqTranslation>
}

// 创建FAQ
export function createSellFaq(data: SellFaqFormData) {
  return request.post('/sell-faq/create', data)
}

// 更新FAQ
export function updateSellFaq(data: SellFaqFormData) {
  return request.post('/sell-faq/update', data)
}

// 删除FAQ
export function deleteSellFaq(id: number) {
  return request.post('/sell-faq/delete', { id })
}

// 更新状态
export function updateSellFaqStatus(id: number, status: number) {
  return request.post('/sell-faq/updateStatus', { id, status })
}

// 批量更新排序
export function updateSellFaqSort(items: { id: number; sort_order: number }[]) {
  return request.post('/sell-faq/updateSort', { items })
}
