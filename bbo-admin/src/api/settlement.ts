import request from './request'

export interface Settlement {
  id: number
  settlement_no: string
  user_id: number
  order_id: number
  order_amount: number
  commission_rate: number
  commission_amount: number
  settlement_amount: number
  status: number
  settled_at?: string
  created_at: string
}

// 获取结算列表
export function getSettlementList(params: {
  page?: number
  pageSize?: number
  user_id?: number | string
  status?: number | string
  settlement_no?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/settlements', { params })
}

// 获取结算详情
export function getSettlementDetail(id: number) {
  return request.get(`/settlements/${id}`)
}

// 执行结算
export function settleOne(id: number) {
  return request.post(`/settlements/${id}/settle`)
}

// 批量结算
export function batchSettle(ids: number[]) {
  return request.post('/settlements/batch-settle', { ids })
}

// 结算统计
export function getSettlementStatistics() {
  return request.get('/settlements/statistics')
}
