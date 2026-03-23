import request from './request'

export interface GoodsTranslation {
  id?: number
  goods_id?: number
  locale: string
  title: string
  description?: string
  specs?: Record<string, string>
}

export interface Goods {
  id: number
  goods_no: string
  user_id: number
  category_id: number
  type: number
  condition: number
  price: number
  original_price: number
  currency: string
  stock: number
  sold_count: number
  images: string[]
  video?: string
  location_country: string
  location_city: string
  shipping_fee: number
  free_shipping: number
  views: number
  likes: number
  is_negotiable: number
  status: number
  status_text: string
  created_at: string
  updated_at: string
  translations: Record<string, GoodsTranslation>
  user?: {
    id: number
    nickname: string
    avatar: string
  }
  category?: {
    id: number
    name?: string
  }
}

export interface GoodsForm {
  user_id: number
  category_id: number
  type: number
  condition: number
  price: number
  original_price: number
  currency: string
  stock: number
  images: string[]
  video?: string
  location_country: string
  location_city: string
  shipping_fee: number
  free_shipping: number
  is_negotiable: number
  status: number
  translations: Record<string, { title: string; description: string }>
}

export interface GoodsStatistics {
  total_goods: number
  status_counts: Array<{ status: number; text: string; count: number }>
  today_goods: number
  month_goods: number
  pending_count: number
}

export interface SimpleUser {
  id: number
  uuid: string
  nickname: string
  avatar?: string
  email?: string
  phone?: string
}

// 获取商品列表
export function getGoodsList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  category_id?: number | string
  status?: number | string
  user_id?: number | string
}) {
  return request.get('/goods', { params })
}

// 获取商品详情
export function getGoodsDetail(id: number) {
  return request.get(`/goods/${id}`)
}

// 更新商品
export function updateGoods(id: number, data: Partial<Goods>) {
  return request.put(`/goods/${id}`, data)
}

// 删除商品
export function deleteGoods(id: number) {
  return request.delete(`/goods/${id}`)
}

// 上架商品
export function onlineGoods(id: number) {
  return request.post(`/goods/${id}/online`)
}

// 下架商品
export function offlineGoods(id: number) {
  return request.post(`/goods/${id}/offline`)
}

// 审核通过
export function approveGoods(id: number) {
  return request.post(`/goods/${id}/approve`)
}

// 审核拒绝
export function rejectGoods(id: number, reason?: string) {
  return request.post(`/goods/${id}/reject`, { reason })
}

// 创建商品
export function createGoods(data: GoodsForm) {
  return request.post('/goods', data)
}

// 商品统计
export function getGoodsStatistics() {
  return request.get('/goods/statistics')
}

// 获取用户列表（用于选择发布者）
export function getGoodsUsers(keyword?: string) {
  return request.get('/goods/users', { params: { keyword } })
}

// 批量审核
export function batchApproveGoods(ids: number[]) {
  return request.post('/goods/batch/approve', { ids })
}

// 批量下架
export function batchOfflineGoods(ids: number[]) {
  return request.post('/goods/batch/offline', { ids })
}

// 批量删除
export function batchDeleteGoods(ids: number[]) {
  return request.post('/goods/batch/delete', { ids })
}

// 批量设为热门/取消热门
export function batchSetHotGoods(ids: number[], isHot: number) {
  return request.post('/goods/batch/set-hot', { ids, is_hot: isHot })
}

// 批量设为推荐/取消推荐
export function batchSetRecommendGoods(ids: number[], isRecommend: number) {
  return request.post('/goods/batch/set-recommend', { ids, is_recommend: isRecommend })
}

// 批量修改价格
export function batchUpdatePrice(ids: number[], data: { mode: string; action: string; value: number }) {
  return request.post('/goods/batch/update-price', { ids, ...data })
}

// 切换单个商品热门状态
export function toggleHotGoods(id: number) {
  return request.post(`/goods/${id}/toggle-hot`)
}

// 切换单个商品推荐状态
export function toggleRecommendGoods(id: number) {
  return request.post(`/goods/${id}/toggle-recommend`)
}

// 更新商品浏览量和收藏数
export function updateGoodsStats(id: number, data: { views?: number; likes?: number }) {
  return request.post(`/goods/${id}/update-stats`, data)
}

// 导出商品数据
export function exportGoodsData(ids: number[], countryCode: string) {
  return request.post('/goods/export', { ids, country_code: countryCode }, { timeout: 300000 })
}

// 查询导出进度
export function getExportProgress(taskId: string) {
  return request.get('/goods/export/progress', { params: { task_id: taskId } })
}

// 下载导出文件（使用原生 axios 绕过响应拦截器）
export function downloadExportFile(taskId: string) {
  const token = localStorage.getItem('admin_token')
  return import('axios').then(({ default: axios }) =>
    axios.get('/admin/goods/export/download', {
      params: { task_id: taskId },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${token}` },
      timeout: 120000,
    })
  )
}
