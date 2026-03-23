// 支付方式相关 API
import { get, post, put, del } from '@/utils/request'

// 支付方式类型
export interface PaymentMethod {
  id: string
  code: string
  name: string
  description?: string
  icon?: string
  brand_color?: string
  button_icon?: string
  tag?: string
  link_text?: string
  link_url?: string
}

// 用户银行卡类型
export interface UserCard {
  id: number
  cardType: string
  cardBrand: string
  lastFour: string
  expiryMonth: number
  expiryYear: number
  expiry: string
  cardholderName?: string
  billingAddressId: number
  isDefault: boolean
}

// 添加银行卡参数
export interface AddCardParams {
  card_number: string
  expiry: string
  cvv?: string
  cardholder_name?: string
  billing_address_id: number
  is_default?: boolean
}

// 获取支付方式列表
export function getPaymentMethods() {
  return get<PaymentMethod[]>('/payment-methods')
}

// 获取用户银行卡列表
export function getUserCards() {
  return get<UserCard[]>('/user/cards')
}

// 添加银行卡
export function addUserCard(params: AddCardParams) {
  return post<UserCard>('/user/cards', params)
}

// 获取银行卡详情
export function getUserCard(id: number) {
  return get<UserCard>(`/user/cards/${id}`)
}

// 更新银行卡
export function updateUserCard(id: number, params: Partial<AddCardParams>) {
  return put<UserCard>(`/user/cards/${id}`, params)
}

// 删除银行卡
export function deleteUserCard(id: number) {
  return del(`/user/cards/${id}`)
}

// 设置默认银行卡
export function setDefaultCard(id: number) {
  return post<UserCard>(`/user/cards/${id}/default`)
}
