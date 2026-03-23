import request from './request'

// ===================== 类型定义 =====================

// 审核记录
export interface CreditReviewLog {
  id: number
  application_id: number
  admin_id: number
  admin_name?: string
  action: string
  content: string
  extra_data?: Record<string, unknown>
  created_at: string
}

// 信用申请
export interface CreditApplication {
  id: number
  application_no: string
  user_id: number
  status: number
  status_text?: string
  // 身份信息
  full_name: string
  id_type: string
  id_type_text?: string
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
  // 信用卡信息
  card_holder_name?: string
  card_last_four?: string
  card_brand?: string
  billing_address?: string
  card_verified?: boolean
  card_verify_amount?: number
  card_verified_at?: string
  // 工作信息
  employment_status?: string
  employer_name?: string
  monthly_income?: number
  // 证明材料
  income_proof_image?: string
  statement_images?: string // JSON字符串
  // 额度信息
  requested_limit?: number
  approved_limit?: number
  credit_level?: number
  // 审核信息
  reject_reason?: string
  supplement_request?: string
  review_notes?: string
  reviewer_id?: number
  reviewer?: {
    id: number
    username: string
    nickname?: string
  }
  reviewed_at?: string
  // 时间
  created_at: string
  updated_at?: string
  // 关联数据
  user?: {
    id: number
    uuid: string
    nickname: string
    avatar?: string
    email?: string
    phone?: string
    created_at?: string
  }
  review_logs?: CreditReviewLog[]
}

// 用户信用额度
export interface UserCreditLimit {
  id: number
  user_id: number
  credit_level: number
  total_limit: number  // 后端返回的是 total_limit
  used_limit: number
  available_limit: number
  frozen_limit: number
  status: number
  status_text?: string
  overdue_count: number
  total_repaid: number
  created_at: string
  updated_at?: string
  user?: {
    id: number
    uuid: string
    nickname: string
    avatar?: string
    email?: string
  }
}

// 分期订单
export interface InstallmentOrder {
  id: number
  user_id: number
  order_id?: number
  installment_no: string
  plan_id: number
  total_amount: number
  down_payment: number
  financed_amount: number
  periods: number
  paid_periods: number
  period_amount: number
  total_interest: number
  total_fee: number
  currency: string
  status: number
  status_text?: string
  auto_deduct: number
  next_due_date?: string
  overdue_days: number
  completed_at?: string
  created_at: string
  updated_at?: string
  user?: {
    id: number
    uuid: string
    nickname: string
    email?: string
  }
  plan?: {
    id: number
    name: string
    periods: number
  }
  schedules?: InstallmentSchedule[]
}

// 分期还款计划
export interface InstallmentSchedule {
  id: number
  installment_id: number
  period: number
  principal: number
  interest: number
  fee: number
  amount: number
  due_date: string
  paid_at?: string
  status: number
  status_text?: string
  overdue_days: number
  overdue_fee: number
}

// 分期方案翻译
export interface InstallmentPlanTranslation {
  locale: string
  name: string
  description?: string
}

// 分期方案
export interface InstallmentPlan {
  id: number
  name?: string // 兼容旧数据
  description?: string // 兼容旧数据
  translations?: InstallmentPlanTranslation[]
  periods: number
  interest_rate: number
  fee_rate: number
  min_amount: number
  max_amount?: number
  min_credit_level: number
  is_enabled: number
  status?: number
  sort: number
  created_at: string
  updated_at?: string
}

// 统计数据
export interface CreditStatistics {
  total_applications?: number
  pending_applications?: number
  reviewing_applications?: number
  approved_applications?: number
  rejected_applications?: number
  supplement_applications?: number
  today_applications?: number
  today_approved?: number
  month_new?: number
  month_approved?: number
  approval_rate?: number
}

export interface CreditLimitStatistics {
  total_users: number
  active_users: number
  frozen_users: number
  total_limit: number
  total_used: number
  total_available: number
}

export interface InstallmentStatistics {
  total_orders: number
  active_orders: number
  completed_orders: number
  overdue_orders: number
  total_amount: number
  total_repaid: number
}

// ===================== 信用申请 API =====================

// 获取信用申请列表
export function getCreditApplicationList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
  id_type?: number | string
  start_date?: string
  end_date?: string
}) {
  return request.get('/credit-applications', { params })
}

// 获取信用申请详情
export function getCreditApplicationDetail(id: number) {
  return request.get(`/credit-applications/${id}`)
}

// 开始审核
export function startReviewApplication(id: number) {
  return request.post(`/credit-applications/${id}/start-review`)
}

// 批准申请
export function approveApplication(id: number, data: { approved_amount: number; remark?: string }) {
  return request.post(`/credit-applications/${id}/approve`, data)
}

// 拒绝申请
export function rejectApplication(id: number, data: { reject_reason: string }) {
  return request.post(`/credit-applications/${id}/reject`, data)
}

// 要求补充材料
export function supplementApplication(id: number, data: { supplement_reason: string }) {
  return request.post(`/credit-applications/${id}/supplement`, data)
}

// 获取申请统计
export function getCreditApplicationStatistics() {
  return request.get('/credit-applications/statistics')
}

// 获取状态列表
export function getCreditApplicationStatusList() {
  return request.get('/credit-applications/status-list')
}

// 获取证件类型列表
export function getCreditApplicationIdTypeList() {
  return request.get('/credit-applications/id-type-list')
}

// ===================== 用户信用额度 API =====================

// 获取用户信用额度列表
export function getUserCreditLimitList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
  credit_level?: number | string
}) {
  return request.get('/credit-limits', { params })
}

// 获取用户信用额度详情
export function getUserCreditLimitDetail(id: number) {
  return request.get(`/credit-limits/${id}`)
}

// 调整额度
export function adjustCreditLimit(id: number, data: { adjustment: number; reason: string }) {
  return request.post(`/credit-limits/${id}/adjust`, data)
}

// 冻结额度
export function freezeCreditLimit(id: number, data: { amount: number; reason: string }) {
  return request.post(`/credit-limits/${id}/freeze`, data)
}

// 解冻额度
export function unfreezeCreditLimit(id: number, data: { amount: number; reason: string }) {
  return request.post(`/credit-limits/${id}/unfreeze`, data)
}

// 更新信用等级
export function updateCreditLevel(id: number, data: { credit_level: number; reason: string }) {
  return request.post(`/credit-limits/${id}/update-level`, data)
}

// 获取额度调整日志
export function getCreditLimitLogs(id: number) {
  return request.get(`/credit-limits/${id}/logs`)
}

// 获取额度统计
export function getCreditLimitStatistics() {
  return request.get('/credit-limits/statistics')
}

// ===================== 分期订单 API =====================

// 获取分期订单列表
export function getInstallmentOrderList(params: {
  page?: number
  pageSize?: number
  status?: number | string
  keyword?: string
  start_date?: string
  end_date?: string
}) {
  return request.get('/installment-orders', { params })
}

// 获取分期订单详情
export function getInstallmentOrderDetail(id: number) {
  return request.get(`/installment-orders/${id}`)
}

// 获取还款计划
export function getInstallmentSchedules(id: number) {
  return request.get(`/installment-orders/${id}/schedules`)
}

// 获取还款记录
export function getInstallmentPayments(id: number) {
  return request.get(`/installment-orders/${id}/payments`)
}

// 获取订单统计
export function getInstallmentStatistics() {
  return request.get('/installment-orders/statistics')
}

// 获取状态列表
export function getInstallmentStatusList() {
  return request.get('/installment-orders/status-list')
}

// ===================== 分期方案 API =====================

// 获取分期方案列表
export function getInstallmentPlanList(params?: {
  page?: number
  pageSize?: number
  is_enabled?: number | string
}) {
  return request.get('/installment-plans', { params })
}

// 获取分期方案详情
export function getInstallmentPlanDetail(id: number) {
  return request.get(`/installment-plans/${id}`)
}

// 创建分期方案
export function createInstallmentPlan(data: Partial<InstallmentPlan>) {
  return request.post('/installment-plans', data)
}

// 更新分期方案
export function updateInstallmentPlan(id: number, data: Partial<InstallmentPlan>) {
  return request.put(`/installment-plans/${id}`, data)
}

// 删除分期方案
export function deleteInstallmentPlan(id: number) {
  return request.delete(`/installment-plans/${id}`)
}

// 切换方案状态
export function toggleInstallmentPlanStatus(id: number) {
  return request.post(`/installment-plans/${id}/toggle-status`)
}
