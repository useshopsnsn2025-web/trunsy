import request from './request'

export interface Coupon {
  id: number
  name: string
  code: string
  type: number
  value: number
  min_amount: number
  max_discount?: number
  total_count: number
  used_count: number
  received_count: number
  per_limit: number
  start_time: string
  end_time: string
  scope: number
  scope_ids?: number[]
  description?: string
  status: number
  created_at: string
  updated_at?: string
}

// 获取优惠券列表
export function getCouponList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  type?: number | string
  status?: number | string
}) {
  return request.get('/coupons', { params })
}

// 获取优惠券详情
export function getCouponDetail(id: number) {
  return request.get(`/coupons/${id}`)
}

// 创建优惠券
export function createCoupon(data: Partial<Coupon>) {
  return request.post('/coupons', data)
}

// 更新优惠券
export function updateCoupon(id: number, data: Partial<Coupon>) {
  return request.put(`/coupons/${id}`, data)
}

// 删除优惠券
export function deleteCoupon(id: number) {
  return request.delete(`/coupons/${id}`)
}

// 优惠券统计
export function getCouponStatistics() {
  return request.get('/coupons/statistics')
}
