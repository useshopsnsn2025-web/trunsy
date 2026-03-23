import { get } from '@/utils/request'

/**
 * 运输商类型
 */
export interface ShippingCarrier {
  id: number
  code: string
  name: string
  description?: string
  logo?: string
  shipping_fee: number
  original_fee: number
  currency: string
  is_free_shipping: boolean
  free_shipping_threshold?: number | null
  estimated_days_min?: number
  estimated_days_max?: number
  estimated_days?: string
}

/**
 * 获取指定国家可用的运输商列表
 * @param countryCode 国家代码（如 US, TW, JP）
 * @param orderAmount 订单金额（用于计算免运费）
 */
export function getShippingCarriers(countryCode: string, orderAmount: number = 0) {
  return get<ShippingCarrier[]>('/shipping/carriers', {
    country_code: countryCode,
    order_amount: orderAmount
  })
}

/**
 * 获取运输商详情
 * @param carrierId 运输商ID
 * @param countryCode 国家代码
 */
export function getShippingCarrierDetail(carrierId: number, countryCode: string) {
  return get<ShippingCarrier>('/shipping/detail', {
    id: carrierId,
    country_code: countryCode
  })
}
