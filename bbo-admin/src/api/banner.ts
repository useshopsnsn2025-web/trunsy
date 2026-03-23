import request from './request'

export interface Banner {
  id: number
  title: string
  image: string
  link_type: number
  link_value?: string
  position: string
  sort: number
  start_time?: string
  end_time?: string
  status: number
  click_count: number
  created_at: string
  updated_at?: string
}

// 获取Banner列表
export function getBannerList(params: {
  page?: number
  pageSize?: number
  position?: string
  status?: number | string
}) {
  return request.get('/banners', { params })
}

// 获取Banner详情
export function getBannerDetail(id: number) {
  return request.get(`/banners/${id}`)
}

// 创建Banner
export function createBanner(data: Partial<Banner>) {
  return request.post('/banners', data)
}

// 更新Banner
export function updateBanner(id: number, data: Partial<Banner>) {
  return request.put(`/banners/${id}`, data)
}

// 删除Banner
export function deleteBanner(id: number) {
  return request.delete(`/banners/${id}`)
}

// 获取展示位置列表
export function getBannerPositions() {
  return request.get('/banners/positions')
}
