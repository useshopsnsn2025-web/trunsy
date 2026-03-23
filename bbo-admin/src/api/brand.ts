import request from './request'

export interface Brand {
  id: number
  name: string
  logo: string
  icon?: string
  sort: number
  status: number
  created_at: string
  updated_at?: string
  translations?: Record<string, { name: string; description: string }>
}

export interface BrandListParams {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
}

// 获取品牌列表
export function getBrandList(params: BrandListParams) {
  return request.get('/brands', { params })
}

// 获取品牌详情
export function getBrandDetail(id: number) {
  return request.get(`/brands/${id}`)
}

// 创建品牌
export function createBrand(data: Partial<Brand>) {
  return request.post('/brands', data)
}

// 更新品牌
export function updateBrand(id: number, data: Partial<Brand>) {
  return request.put(`/brands/${id}`, data)
}

// 删除品牌
export function deleteBrand(id: number) {
  return request.delete(`/brands/${id}`)
}

// 获取所有启用的品牌
export function getAllBrands() {
  return request.get('/brands/all')
}
