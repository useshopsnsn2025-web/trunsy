import request from './request'

export interface Withdrawal {
  id: number
  withdrawal_no: string
  user_id: number
  amount: number
  fee: number
  actual_amount: number
  method: number
  account_name: string
  account_no: string
  bank_name?: string
  status: number
  reject_reason?: string
  admin_id?: number
  audited_at?: string
  completed_at?: string
  created_at: string
  updated_at?: string
}

// 获取提现列表
export function getWithdrawalList(params: {
  page?: number
  pageSize?: number
  user_id?: number | string
  status?: number | string
  method?: number | string
  withdrawal_no?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/withdrawals', { params })
}

// 获取提现详情
export function getWithdrawalDetail(id: number) {
  return request.get(`/withdrawals/${id}`)
}

// 审核通过
export function approveWithdrawal(id: number) {
  return request.post(`/withdrawals/${id}/approve`)
}

// 审核拒绝
export function rejectWithdrawal(id: number, reason: string) {
  return request.post(`/withdrawals/${id}/reject`, { reason })
}

// 标记完成
export function completeWithdrawal(id: number) {
  return request.post(`/withdrawals/${id}/complete`)
}

// 提现统计
export function getWithdrawalStatistics() {
  return request.get('/withdrawals/statistics')
}
