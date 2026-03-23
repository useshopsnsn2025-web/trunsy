import request from '@/api/request'

export interface WithdrawalMethod {
  id: number
  code: string
  logo: string
  route_path: string
  sort: number
  status: number
  name?: string
  country_names?: string
  country_count?: number
  translations?: Record<string, string>
  country_ids?: number[]
  created_at: string
  updated_at: string
}

export interface WithdrawalMethodForm {
  code: string
  logo: string
  route_path: string
  sort: number
  status: number
  translations: Record<string, string>
  country_ids: number[]
}

export interface CountryOption {
  id: number
  code: string
  name: string
}

// 获取提现方式列表
export function getWithdrawalMethodList(params: {
  page?: number
  pageSize?: number
  code?: string
  status?: number | string
}) {
  return request({
    url: '/withdrawal-methods',
    method: 'get',
    params
  })
}

// 获取提现方式详情
export function getWithdrawalMethod(id: number) {
  return request({
    url: `/withdrawal-methods/${id}`,
    method: 'get'
  })
}

// 创建提现方式
export function createWithdrawalMethod(data: WithdrawalMethodForm) {
  return request({
    url: '/withdrawal-methods',
    method: 'post',
    data
  })
}

// 更新提现方式
export function updateWithdrawalMethod(id: number, data: WithdrawalMethodForm) {
  return request({
    url: `/withdrawal-methods/${id}`,
    method: 'put',
    data
  })
}

// 删除提现方式
export function deleteWithdrawalMethod(id: number) {
  return request({
    url: `/withdrawal-methods/${id}`,
    method: 'delete'
  })
}

// 切换状态
export function toggleWithdrawalMethodStatus(id: number) {
  return request({
    url: `/withdrawal-methods/${id}/toggle-status`,
    method: 'post'
  })
}

// 获取国家列表（用于选择）
export function getCountryOptions() {
  return request({
    url: '/withdrawal-methods/countries',
    method: 'get'
  })
}
