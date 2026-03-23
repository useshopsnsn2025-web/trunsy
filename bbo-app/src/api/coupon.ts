// 优惠券相关 API
import { get, post } from '@/utils/request'

// 优惠券类型
export interface Coupon {
  id: number
  couponId: number
  name: string
  description?: string
  type: number // 1: 满减, 2: 折扣, 3: 固定金额
  value: number // 满减金额或折扣率
  minAmount: number // 最低消费
  maxDiscount?: number // 最高折扣金额（折扣券用）
  scope: number // 1: 全场, 2: 指定分类, 3: 指定商品
  scopeIds?: number[]
  status: number // 0: 未使用, 1: 已使用, 2: 已过期
  receivedAt?: string
  usedAt?: string
  expiredAt: string
}

// 可领取优惠券
export interface ClaimableCoupon {
  id: number
  name: string
  description?: string
  type: number
  value: number
  minAmount: number
  maxDiscount?: number
  scope: number
  validDays: number
  remaining?: number
  perLimit: number
  claimed: boolean
  claimedCount: number
}

// 优惠券列表响应
export interface CouponListResponse {
  list: Coupon[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 获取用户优惠券列表
export function getCoupons(params: {
  status?: 'available' | 'used' | 'expired'
  page?: number
  pageSize?: number
}) {
  return get<CouponListResponse>('/coupons', params)
}

// 获取可用优惠券数量
export function getCouponCount() {
  return get<{ count: number }>('/coupons/count')
}

// 获取可领取的优惠券列表
export function getClaimableCoupons() {
  return get<ClaimableCoupon[]>('/coupons/claimable')
}

// 领取优惠券
export function claimCoupon(couponId: number) {
  return post<{ id: number; message: string }>('/coupons/claim', { coupon_id: couponId })
}

// 获取订单可用优惠券
export interface AvailableCoupon extends Coupon {
  discount: number // 可优惠金额
  reason?: string // 不可用原因
}

export function getAvailableCoupons(params: {
  amount: number
  goodsIds?: string
  categoryIds?: string
}) {
  // 转换参数名为后端格式（下划线命名）
  return get<{
    available: AvailableCoupon[]
    unavailable: AvailableCoupon[]
  }>('/coupons/available', {
    amount: params.amount,
    goods_ids: params.goodsIds,
    category_ids: params.categoryIds
  })
}
