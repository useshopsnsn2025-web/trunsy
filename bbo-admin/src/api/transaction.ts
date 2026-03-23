import request from './request'

export interface Transaction {
  id: number
  transaction_no: string
  user_id: number
  type: number
  amount: number
  balance: number
  order_id?: number
  title: string
  description?: string
  status: number
  created_at: string
}

// 获取交易流水列表
export function getTransactionList(params: {
  page?: number
  pageSize?: number
  user_id?: number | string
  type?: number | string
  status?: number | string
  transaction_no?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/transactions', { params })
}

// 获取交易详情
export function getTransactionDetail(id: number) {
  return request.get(`/transactions/${id}`)
}

// 交易统计
export function getTransactionStatistics() {
  return request.get('/transactions/statistics')
}
