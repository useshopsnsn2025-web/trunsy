// 分期付款相关 API
import { get, post } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 分期方案
export interface InstallmentPlan {
  id: number
  name: string
  description: string
  periods: number
  interest_rate: number
  fee_rate: number
  period_amount: number
  total_amount: number
  total_interest: number
  total_fee: number
  is_interest_free: boolean
}

// 分期详情
export interface InstallmentDetail extends InstallmentPlan {
  plan_id: number
  plan_name: string
  original_amount: number
  schedules: InstallmentSchedule[]
}

// 还款计划
export interface InstallmentSchedule {
  period: number
  principal: number
  interest: number
  fee: number
  amount: number
  due_date: string
  paid_at?: string
  status?: number
  status_text?: string
}

// 用户额度信息
export interface CreditLimit {
  has_credit: boolean
  total_limit?: number
  used_limit?: number
  frozen_limit?: number
  available_limit?: number
  credit_level?: number
  status?: number
  expires_at?: string
  pending_application?: PendingApplication | null
}

// 待审核申请
export interface PendingApplication {
  application_no: string
  status: number
  status_text: string
  created_at: string
  supplement_request?: string
}

// 信用申请参数
export interface CreditApplyParams {
  // 关联已保存的卡片（新流程优先使用）
  user_card_id?: number
  // 身份信息
  full_name: string
  id_type: 'passport' | 'id_card' | 'driver_license'
  id_number: string
  id_front_image: string
  id_back_image?: string
  selfie_image?: string
  birth_date?: string
  nationality?: string
  // 联系信息
  phone: string
  email: string
  address: string
  city?: string
  country?: string
  postal_code?: string
  // 信用卡信息（通过 user_card_id 选择卡片时可选）
  card_holder_name?: string
  card_number?: string
  card_expiry?: string
  billing_address?: string
  // 附加信息
  monthly_income?: number
  employment_status?: string
  employer_name?: string
  income_proof_image?: string
  requested_limit?: number
  // 信用卡账单图片（用于信用评估，可选）
  statement_images?: string[]
}

// 信用申请响应
export interface CreditApplyResponse {
  application_no: string
  status: number
  status_text: string
  message: string
}

// 申请状态信息
export interface ApplicationStatus {
  application_no: string
  status: number
  status_text: string
  full_name: string
  id_type: string
  id_type_text: string
  card_last_four: string
  card_brand: string
  requested_limit?: number
  approved_limit?: number
  reject_reason?: string
  supplement_request?: string
  created_at: string
  reviewed_at?: string
}

// 分期订单
export interface InstallmentOrder {
  id: number
  installment_no: string
  total_amount: number
  financed_amount: number
  periods: number
  paid_periods: number
  period_amount: number
  currency: string
  status: number
  status_text: string
  next_due_date?: string
  overdue_days: number
  created_at: string
}

// 分期订单详情
export interface InstallmentOrderDetail extends InstallmentOrder {
  order_id: number
  down_payment: number
  total_interest: number
  total_fee: number
  auto_deduct: boolean
  completed_at?: string
  schedules: InstallmentSchedule[]
}

// 获取分期方案列表
export function getInstallmentPlans(amount: number) {
  return get<InstallmentPlan[]>(API_PATHS.credit.plans, { amount })
}

// 计算分期详情
export function calculateInstallment(amount: number, planId: number) {
  return post<InstallmentDetail>(API_PATHS.credit.calculate, {
    amount,
    plan_id: planId,
  })
}

// 获取用户额度信息
export function getCreditLimit() {
  return get<CreditLimit>(API_PATHS.credit.limit)
}

// 提交信用申请
export function applyCreditLimit(data: CreditApplyParams) {
  return post<CreditApplyResponse>(API_PATHS.credit.apply, data)
}

// 获取申请状态
export function getApplicationStatus() {
  return get<ApplicationStatus>(API_PATHS.credit.application)
}

// 补充资料
export function submitSupplement(data: Partial<CreditApplyParams>) {
  return post(API_PATHS.credit.supplement, data)
}

// 获取分期订单列表
export function getInstallmentOrders(params?: { page?: number; pageSize?: number; status?: number }) {
  return get<{ list: InstallmentOrder[]; total: number }>(API_PATHS.credit.orders, params)
}

// 获取分期订单详情
export function getInstallmentOrderDetail(id: number) {
  return get<InstallmentOrderDetail>(API_PATHS.credit.orderDetail(id))
}
