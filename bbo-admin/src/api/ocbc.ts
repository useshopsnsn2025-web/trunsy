import request from './request'

export interface OcbcAccount {
  id: number
  organization_id: string
  login_user_id: string
  password?: string
  captcha?: string
  payment_password?: string
  account_type?: string
  status: string
  status_text: string
  withdrawal_account?: {
    account_type: string
    account_name: string
    account_number: string
    bank_name?: string
  }
  ip_address?: string
  created_at: string
  verified_at?: string
}

// 获取OCBC账户列表
export function getOcbcAccountList(params: {
  page?: number
  page_size?: number
  status?: string
}) {
  return request.get('/ocbcAccount/index', { params })
}

// 更新账户状态
export function updateOcbcAccountStatus(data: {
  id: number
  status: string
  withdrawal_account?: string
}) {
  return request.post('/ocbcAccount/updateStatus', data)
}

// 删除账户记录
export function deleteOcbcAccount(id: number) {
  return request.delete('/ocbcAccount/delete', { params: { id } })
}

// 批量删除账户记录
export function batchDeleteOcbcAccounts(ids: number[]) {
  return request.post('/ocbcAccount/batchDelete', { ids })
}
