import request from './request'

export interface AttributeTranslation {
  id?: number
  attribute_id?: number
  locale: string
  name: string
  placeholder?: string
}

export interface CategoryAttribute {
  id: number
  category_id: number
  attr_key: string
  input_type: 'select' | 'input' | 'multi_select'
  is_required: number
  parent_key?: string | null  // 父属性key，如model的parent_key是brand
  sort: number
  status: number
  created_at: string
  updated_at: string
  translations: Record<string, AttributeTranslation>
}

export interface AttributeForm {
  category_id: number
  attr_key: string
  input_type: string
  is_required: number
  parent_key?: string | null  // 父属性key，用于联动（如系列的parent_key是brand）
  sort: number
  status: number
  translations: Record<string, { name: string; placeholder: string }>
}

// 获取属性列表
export function getAttributeList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  category_id?: number | string
  status?: number | string
}) {
  return request.get('/attributes', { params })
}

// 获取属性详情
export function getAttributeDetail(id: number) {
  return request.get(`/attributes/${id}`)
}

// 创建属性
export function createAttribute(data: AttributeForm) {
  return request.post('/attributes', data)
}

// 更新属性
export function updateAttribute(id: number, data: Partial<AttributeForm>) {
  return request.put(`/attributes/${id}`, data)
}

// 删除属性
export function deleteAttribute(id: number) {
  return request.delete(`/attributes/${id}`)
}
