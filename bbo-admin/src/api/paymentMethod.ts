import request from './request'

export interface PaymentMethod {
  id: number
  code: string
  name: string
  description?: string
  icon?: string
  tag?: string
  link_text?: string
  link_url?: string
  config?: string
  sort: number
  status: number
  created_at: string
  updated_at?: string
  translations?: Record<string, {
    name?: string
    description?: string
    tag?: string
    link_text?: string
  }>
}

// 获取支付方式列表
export function getPaymentMethodList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
  locale?: string
}) {
  return request.get('/payment-methods', { params })
}

// 获取支付方式详情
export function getPaymentMethodDetail(id: number) {
  return request.get(`/payment-methods/${id}`)
}

// 创建支付方式
export function createPaymentMethod(data: Partial<PaymentMethod>) {
  return request.post('/payment-methods', data)
}

// 更新支付方式
export function updatePaymentMethod(id: number, data: Partial<PaymentMethod>) {
  return request.put(`/payment-methods/${id}`, data)
}

// 删除支付方式
export function deletePaymentMethod(id: number) {
  return request.delete(`/payment-methods/${id}`)
}

// 切换状态
export function togglePaymentMethodStatus(id: number) {
  return request.post(`/payment-methods/${id}/toggle-status`)
}

// 获取所有启用的支付方式
export function getAllPaymentMethods(locale?: string) {
  return request.get('/payment-methods/all', { params: { locale } })
}
