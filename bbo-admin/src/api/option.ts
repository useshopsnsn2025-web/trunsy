import request from './request'

export interface OptionTranslation {
  id?: number
  option_id?: number
  locale: string
  label: string
}

export interface AttributeOption {
  id: number
  attribute_id: number
  option_value: string
  image?: string | null  // 选项图片
  parent_value?: string | null  // 父属性值，如型号属于哪个品牌
  sort: number
  status: number
  created_at: string
  translations: Record<string, OptionTranslation>
}

export interface OptionForm {
  attribute_id: number
  option_value: string
  image?: string  // 选项图片
  parent_value?: string | null  // 父属性值
  sort: number
  status: number
  translations: Record<string, { label: string }>
}

export interface BatchOptionItem {
  option_value: string
  parent_value?: string | null  // 父属性值
  sort?: number
  status?: number
  translations: Record<string, { label: string }>
}

// 获取选项列表
export function getOptionList(params: {
  page?: number
  pageSize?: number
  keyword?: string
  attribute_id?: number | string
  parent_value?: string  // 父属性值筛选
  status?: number | string
}) {
  return request.get('/options', { params })
}

// 获取选项详情
export function getOptionDetail(id: number) {
  return request.get(`/options/${id}`)
}

// 创建选项
export function createOption(data: OptionForm) {
  return request.post('/options', data)
}

// 批量创建选项
export function batchCreateOptions(attribute_id: number, options: BatchOptionItem[]) {
  return request.post('/options/batch', { attribute_id, options })
}

// 更新选项
export function updateOption(id: number, data: Partial<OptionForm>) {
  return request.put(`/options/${id}`, data)
}

// 删除选项
export function deleteOption(id: number) {
  return request.delete(`/options/${id}`)
}

// 批量删除选项
export function batchDeleteOptions(ids: number[]) {
  return request.post('/options/batch-delete', { ids })
}
