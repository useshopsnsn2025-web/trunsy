// 钱包相关 API
import { get, post } from '@/utils/request'

// 钱包信息
export interface WalletInfo {
  balance: number        // 可用余额
  frozen: number         // 冻结金额
  processing: number     // 处理中金额
  totalIncome: number    // 累计收入
  totalWithdraw: number  // 累计提现
  currency: string       // 货币
}

// 交易记录
export interface Transaction {
  id: number
  transactionNo: string
  type: number           // 1收入 2支出 3冻结 4解冻
  amount: number
  balance: number        // 交易后余额
  title: string
  description?: string
  orderId?: number
  status: number         // 0处理中 1成功 2失败
  createdAt: string
}

// 交易类型
export const TRANSACTION_TYPES = {
  INCOME: 1,      // 收入
  EXPENSE: 2,     // 支出
  FREEZE: 3,      // 冻结
  UNFREEZE: 4,    // 解冻
}

// 交易列表参数
export interface TransactionListParams {
  page?: number
  pageSize?: number
  type?: number
  startDate?: string
  endDate?: string
}

// 交易列表响应
export interface TransactionListResponse {
  list: Transaction[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 提现记录
export interface Withdrawal {
  id: number
  withdrawalNo: string
  amount: number
  fee: number
  actualAmount: number
  method: number         // 1银行卡 2支付宝 3微信
  accountName: string
  accountNo: string
  bankName?: string
  status: number         // 0待审核 1处理中 2已完成 3已拒绝
  rejectReason?: string
  createdAt: string
  completedAt?: string
}

// 提现状态
export const WITHDRAWAL_STATUS = {
  PENDING: 0,      // 待审核
  PROCESSING: 1,   // 处理中
  COMPLETED: 2,    // 已完成
  REJECTED: 3,     // 已拒绝
}

// 提现方式
export const WITHDRAWAL_METHODS = {
  BANK: 1,         // 银行卡
  ALIPAY: 2,       // 支付宝
  WECHAT: 3,       // 微信
}

// 提现列表参数
export interface WithdrawalListParams {
  page?: number
  pageSize?: number
  status?: number
}

// 提现列表响应
export interface WithdrawalListResponse {
  list: Withdrawal[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 提现申请参数
export interface WithdrawParams {
  amount: number
  cardId?: number    // 银行卡ID（兼容旧版本）
  accountId?: number // OCBC银行账户ID
  displayCurrency?: string  // 用户显示货币
  displayAmount?: number    // 用户显示金额（用户货币）
}

// 获取钱包信息
export function getWalletInfo() {
  return get<WalletInfo>('/wallet')
}

// 获取交易记录列表
export function getTransactions(params: TransactionListParams) {
  return get<TransactionListResponse>('/wallet/transactions', params)
}

// 获取提现记录列表
export function getWithdrawals(params: WithdrawalListParams) {
  return get<WithdrawalListResponse>('/wallet/withdrawals', params)
}

// 申请提现
export function applyWithdraw(data: WithdrawParams) {
  return post<Withdrawal>('/wallet/withdraw', data, { showLoading: true })
}

// 取消提现申请
export function cancelWithdraw(id: number) {
  return post<{ success: boolean }>(`/wallet/withdrawals/${id}/cancel`, undefined, { showLoading: true })
}

// 获取提现配置（最小金额、手续费等）
export interface WithdrawConfig {
  minAmount: number       // 最小提现金额
  maxAmount: number       // 最大提现金额
  feeRate: number         // 手续费率 (0-1)
  fixedFee: number        // 固定手续费
  dailyLimit: number      // 每日提现限额
  remainingLimit: number  // 今日剩余限额
}

export function getWithdrawConfig() {
  return get<WithdrawConfig>('/wallet/withdraw-config')
}

// 提现方式
export interface WithdrawalMethod {
  id: number
  code: string
  name: string
  logo: string
  routePath: string
}

// 获取提现方式列表
export function getWithdrawalMethods(region?: string) {
  const params = region ? `?region=${region}` : ''
  return get<WithdrawalMethod[]>(`/wallet/withdrawal-methods${params}`)
}
