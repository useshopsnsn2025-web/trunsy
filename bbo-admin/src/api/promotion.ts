import request from './request'

export interface Promotion {
  id: number
  name: string
  type: number
  banner?: string
  description?: string
  rules?: any
  start_time: string
  end_time: string
  status: number
  sort: number
  created_at: string
  updated_at?: string
}

// 获取活动列表
export function getPromotionList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  type?: number | string
  status?: number | string
}) {
  return request.get('/promotions', { params })
}

// 获取活动详情
export function getPromotionDetail(id: number) {
  return request.get(`/promotions/${id}`)
}

// 创建活动
export function createPromotion(data: Partial<Promotion>) {
  return request.post('/promotions', data)
}

// 更新活动
export function updatePromotion(id: number, data: Partial<Promotion>) {
  return request.put(`/promotions/${id}`, data)
}

// 删除活动
export function deletePromotion(id: number) {
  return request.delete(`/promotions/${id}`)
}

// 开始活动
export function startPromotion(id: number) {
  return request.post(`/promotions/${id}/start`)
}

// 结束活动
export function stopPromotion(id: number) {
  return request.post(`/promotions/${id}/stop`)
}

// ===================== 活动商品管理 =====================

export interface PromotionGoods {
  id: number
  goods_id: number
  promotion_price: number
  discount: number
  stock: number
  sold_count: number
  limit_per_user: number
  created_at: string
  goods?: {
    id: number
    goods_no: string
    title: string
    price: number
    images: string[]
    stock: number
    status: number
  }
}

// 获取活动商品列表
export function getPromotionGoodsList(promotionId: number, params?: {
  page?: number
  pageSize?: number
}) {
  return request.get(`/promotions/${promotionId}/goods`, { params })
}

// 搜索可添加的商品
export function searchPromotionGoods(promotionId: number, params?: {
  keyword?: string
  category_id?: number | string
  brand_id?: number | string
  goods_type?: number | string
  page?: number
  pageSize?: number
}) {
  return request.get(`/promotions/${promotionId}/goods/search`, { params })
}

// 批量添加活动商品
export function batchAddPromotionGoods(promotionId: number, data: {
  goods_ids: number[]
  discount_rate: number
}) {
  return request.post(`/promotions/${promotionId}/goods/batch`, data)
}

// 添加活动商品
export function addPromotionGoods(promotionId: number, data: {
  goods_id: number
  promotion_price: number
  stock?: number
  limit_per_user?: number
}) {
  return request.post(`/promotions/${promotionId}/goods`, data)
}

// 更新活动商品
export function updatePromotionGoods(promotionId: number, goodsId: number, data: {
  promotion_price?: number
  stock?: number
  limit_per_user?: number
}) {
  return request.put(`/promotions/${promotionId}/goods/${goodsId}`, data)
}

// 删除活动商品
export function removePromotionGoods(promotionId: number, goodsId: number) {
  return request.delete(`/promotions/${promotionId}/goods/${goodsId}`)
}

// 批量更新活动商品折扣
export function batchUpdatePromotionGoods(promotionId: number, data: {
  goods_ids: number[]
  discount: number  // 1-10代表1折到10折
}) {
  return request.put(`/promotions/${promotionId}/goods/batch`, data)
}
