import request from './request'

export interface CategoryTranslation {
  id?: number
  category_id?: number
  locale: string
  name: string
  description?: string
}

export interface Category {
  id: number
  parent_id: number
  icon: string
  sort: number
  status: number
  is_hot: number
  created_at: string
  updated_at: string
  translations: Record<string, CategoryTranslation>
  children?: Category[]
}

export interface CategoryForm {
  parent_id: number
  icon: string
  sort: number
  status: number
  is_hot: number
  translations: Record<string, { name: string; description: string }>
}

// 获取分类列表
export function getCategoryList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  parent_id?: number | string
  status?: number | string
}) {
  return request.get('/categories', { params })
}

// 获取分类树
export function getCategoryTree() {
  return request.get('/categories/tree')
}

// 获取分类详情
export function getCategoryDetail(id: number) {
  return request.get(`/categories/${id}`)
}

// 创建分类
export function createCategory(data: CategoryForm) {
  return request.post('/categories', data)
}

// 更新分类
export function updateCategory(id: number, data: Partial<CategoryForm>) {
  return request.put(`/categories/${id}`, data)
}

// 删除分类
export function deleteCategory(id: number) {
  return request.delete(`/categories/${id}`)
}

// 分类属性接口
export interface CategoryAttributeOption {
  value: string
  label: string
  parent_value?: string | null  // 用于联动筛选
}

export interface CategoryAttribute {
  key: string
  name: string
  placeholder: string
  input_type: 'input' | 'select' | 'multi_select'
  is_required: boolean
  parent_key?: string | null  // 父属性key，如model的parent_key是brand
  options: CategoryAttributeOption[]
}

// 获取分类属性（用于商品表单动态属性）
export function getCategoryAttributes(categoryId: number, locale: string = 'zh-tw') {
  return request.get<CategoryAttribute[]>(`/categories/${categoryId}/attributes`, { params: { locale } })
}
