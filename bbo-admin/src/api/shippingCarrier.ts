import request from './request'

export interface ShippingCarrier {
  id: number
  code: string
  name: string
  description?: string
  logo?: string
  tracking_url?: string
  estimated_days_min?: number
  estimated_days_max?: number
  sort: number
  status: number
  country_count?: number
  created_at: string
  updated_at?: string
  translations?: Record<string, {
    name?: string
    description?: string
  }>
  countries?: CarrierCountry[]
}

export interface CarrierCountry {
  id?: number
  carrier_id?: number
  country_code: string
  shipping_fee: number
  currency: string
  free_shipping_threshold?: number | null
  estimated_days_min?: number | null
  estimated_days_max?: number | null
  is_enabled: number
}

// 获取运输商列表
export function getShippingCarrierList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
  locale?: string
}) {
  return request.get('/shipping-carriers', { params })
}

// 获取运输商详情
export function getShippingCarrierDetail(id: number) {
  return request.get(`/shipping-carriers/${id}`)
}

// 创建运输商
export function createShippingCarrier(data: Partial<ShippingCarrier>) {
  return request.post('/shipping-carriers', data)
}

// 更新运输商
export function updateShippingCarrier(id: number, data: Partial<ShippingCarrier>) {
  return request.put(`/shipping-carriers/${id}`, data)
}

// 删除运输商
export function deleteShippingCarrier(id: number) {
  return request.delete(`/shipping-carriers/${id}`)
}

// 切换状态
export function toggleShippingCarrierStatus(id: number) {
  return request.post(`/shipping-carriers/${id}/toggle-status`)
}

// 获取运输商的国家配置
export function getCarrierCountries(id: number) {
  return request.get(`/shipping-carriers/${id}/countries`)
}

// 保存运输商的国家配置
export function saveCarrierCountries(id: number, countries: CarrierCountry[]) {
  return request.post(`/shipping-carriers/${id}/countries`, { countries })
}
