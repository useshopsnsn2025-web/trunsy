// 活动相关 API
import { get } from '@/utils/request'

// 活动类型
export const PROMOTION_TYPE = {
  DISCOUNT: 1,    // 限时折扣
  REDUCTION: 2,   // 满减活动
  SECKILL: 3,     // 秒杀
  GROUP: 4,       // 拼团
} as const

// 活动状态
export const PROMOTION_STATUS = {
  DRAFT: 0,       // 草稿
  RUNNING: 1,     // 进行中
  ENDED: 2,       // 已结束
  CANCELLED: 3,   // 已取消
} as const

// 活动信息
export interface Promotion {
  id: number
  name: string
  type: number
  banner?: string
  description?: string
  rules?: PromotionRules
  startTime: string
  endTime: string
  status: number
  sort: number
  goodsCount?: number
  createdAt: string
}

// 活动规则
export interface PromotionRules {
  // 满减规则
  reduction?: {
    threshold: number  // 满多少
    amount: number     // 减多少
  }[]
  // 折扣规则
  discount?: {
    rate: number       // 折扣率 0-1
  }
  // 秒杀规则
  seckill?: {
    limitPerUser: number  // 每人限购
  }
  // 拼团规则
  group?: {
    minUsers: number      // 最少成团人数
    validHours: number    // 拼团有效期(小时)
  }
}

// 活动商品
export interface PromotionGoods {
  id: number
  goodsId: number
  promotionPrice: number
  discount: number
  stock: number
  soldCount: number
  limitPerUser: number
  // 商品信息
  goods: {
    id: number
    goodsNo: string
    title: string
    price: number
    originalPrice?: number
    currency: string
    images: string[]
    condition: number
    freeShipping: boolean
    stock: number
    likes: number
    isLiked?: boolean
  }
}

// 活动列表参数
export interface PromotionListParams {
  page?: number
  pageSize?: number
  type?: number
}

// 活动列表响应
export interface PromotionListResponse {
  list: Promotion[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 活动商品列表响应
export interface PromotionGoodsResponse {
  list: PromotionGoods[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 首页活动（包含商品）
export interface HomePromotion extends Promotion {
  goods?: PromotionGoods[]
}

// 获取活动列表（进行中的）
export function getPromotionList(params: PromotionListParams = {}) {
  return get<PromotionListResponse>('/promotions', params)
}

// 获取活动详情
export function getPromotionDetail(id: number | string) {
  return get<Promotion>(`/promotions/${id}`)
}

// 获取活动商品列表
export function getPromotionGoods(id: number | string, params: { page?: number; pageSize?: number } = {}) {
  return get<PromotionGoodsResponse>(`/promotions/${id}/goods`, params)
}

// 首页活动响应
export interface HomePromotionsResponse {
  seckill?: HomePromotion
  discount?: HomePromotion
  promotions: Promotion[]
}

// 获取首页活动（限时秒杀等）
export function getHomePromotions() {
  return get<HomePromotionsResponse>('/promotions/home')
}

// 获取活动类型名称
export function getTypeName(type: number): string {
  const names: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: 'promotion.typeDiscount',
    [PROMOTION_TYPE.REDUCTION]: 'promotion.typeReduction',
    [PROMOTION_TYPE.SECKILL]: 'promotion.typeSeckill',
    [PROMOTION_TYPE.GROUP]: 'promotion.typeGroup',
  }
  return names[type] || ''
}

// 计算活动剩余时间
export function getRemainTime(endTime: string): {
  total: number
  days: number
  hours: number
  minutes: number
  seconds: number
  expired: boolean
} {
  const end = new Date(endTime).getTime()
  const now = Date.now()
  const diff = end - now

  if (diff <= 0) {
    return { total: 0, days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }
  }

  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)

  return { total: diff, days, hours, minutes, seconds, expired: false }
}
