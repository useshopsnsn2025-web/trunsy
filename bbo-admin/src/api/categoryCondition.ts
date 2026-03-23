import request from './request'

// 状态组
export interface ConditionGroup {
  id: number
  category_id: number
  name: string
  icon: string
  sort: number
  is_required: number
  status: number
  option_count?: number
  translations: Record<string, { name: string }>
  options?: ConditionOption[]
}

// 状态选项
export interface ConditionOption {
  id: number
  group_id: number
  name: string
  sort: number
  impact_level: number | null
  status: number
  translations: Record<string, { name: string }>
}

// 状态组表单
export interface ConditionGroupForm {
  category_id: number
  icon: string
  sort: number
  is_required: number
  status: number
  translations: Record<string, { name: string }>
}

// 状态选项表单
export interface ConditionOptionForm {
  group_id: number
  sort: number
  impact_level: number | null
  status: number
  translations: Record<string, { name: string }>
}

// 获取状态组列表
export function getConditionGroups(params: { category_id?: number; status?: number | string }) {
  return request.get('/category-conditions/groups', { params })
}

// 获取状态组详情（含选项）
export function getConditionGroupDetail(id: number) {
  return request.get(`/category-conditions/groups/${id}`)
}

// 创建状态组
export function createConditionGroup(data: ConditionGroupForm) {
  return request.post('/category-conditions/groups', data)
}

// 更新状态组
export function updateConditionGroup(id: number, data: Partial<ConditionGroupForm>) {
  return request.put(`/category-conditions/groups/${id}`, data)
}

// 删除状态组
export function deleteConditionGroup(id: number) {
  return request.delete(`/category-conditions/groups/${id}`)
}

// 批量更新状态组排序
export function sortConditionGroups(items: { id: number; sort: number }[]) {
  return request.post('/category-conditions/groups/sort', { items })
}

// 获取选项列表
export function getConditionOptions(groupId: number) {
  return request.get('/category-conditions/options', { params: { group_id: groupId } })
}

// 创建选项
export function createConditionOption(data: ConditionOptionForm) {
  return request.post('/category-conditions/options', data)
}

// 更新选项
export function updateConditionOption(id: number, data: Partial<ConditionOptionForm>) {
  return request.put(`/category-conditions/options/${id}`, data)
}

// 删除选项
export function deleteConditionOption(id: number) {
  return request.delete(`/category-conditions/options/${id}`)
}

// 批量更新选项排序
export function sortConditionOptions(items: { id: number; sort: number }[]) {
  return request.post('/category-conditions/options/sort', { items })
}

// 复制状态配置到其他分类
export function copyConditionToCategory(sourceCategoryId: number, targetCategoryId: number) {
  return request.post('/category-conditions/copy', {
    source_category_id: sourceCategoryId,
    target_category_id: targetCategoryId
  })
}

// 获取分类的状态组（含选项）- 用于商品表单
export function getCategoryConditionGroupsWithOptions(categoryId: number) {
  return request.get('/category-conditions/groups-with-options', {
    params: { category_id: categoryId }
  })
}
