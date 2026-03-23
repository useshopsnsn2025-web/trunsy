// 用户银行卡相关 API
import { get, post, put, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 卡片状态
export type CardStatus = 'active' | 'expired' | 'disabled' | 'rejected' | 'pending'

// 银行卡信息
export interface UserCard {
  id: number
  cardType: string
  cardBrand: string
  lastFour: string
  expiryMonth: number
  expiryYear: number
  expiry: string
  cardholderName?: string
  billingAddressId?: number
  isDefault: boolean
  status?: CardStatus
  statusReason?: string
  billingAddress?: {
    id: number
    name: string
    phone: string
    country: string
    state?: string
    city?: string
    street: string
    postalCode?: string
    fullAddress: string
  }
}

// 添加银行卡参数
export interface AddCardParams {
  cardNumber: string
  expiry: string
  cvv: string
  billingAddressId: number
  isDefault?: boolean
}

// 更新银行卡参数
export interface UpdateCardParams {
  billingAddressId?: number
  cardholderName?: string
  isDefault?: boolean
}

// 获取银行卡列表
export function getUserCards() {
  return get<UserCard[]>(API_PATHS.userCard.list)
}

// 获取银行卡详情
export function getUserCardDetail(id: number) {
  return get<UserCard>(API_PATHS.userCard.detail(id))
}

// 添加银行卡
export function addUserCard(data: AddCardParams) {
  return post<UserCard>(API_PATHS.userCard.create, {
    card_number: data.cardNumber,
    expiry: data.expiry,
    cvv: data.cvv,
    billing_address_id: data.billingAddressId,
    is_default: data.isDefault ? 1 : 0,
  })
}

// 更新银行卡
export function updateUserCard(id: number, data: UpdateCardParams) {
  return put<UserCard>(API_PATHS.userCard.update(id), {
    billing_address_id: data.billingAddressId,
    cardholder_name: data.cardholderName,
    is_default: data.isDefault ? 1 : 0,
  })
}

// 删除银行卡
export function deleteUserCard(id: number) {
  return del(API_PATHS.userCard.delete(id))
}

// 设置默认银行卡
export function setDefaultCard(id: number) {
  return post<UserCard>(API_PATHS.userCard.setDefault(id))
}

// 卡品牌图标类名映射 (Bootstrap Icons)
export const CARD_BRAND_ICONS: Record<string, string> = {
  visa: 'bi-credit-card-2-front',
  mastercard: 'bi-credit-card',
  amex: 'bi-credit-card-2-back',
  discover: 'bi-credit-card-fill',
  unionpay: 'bi-credit-card-2-front-fill',
  unknown: 'bi-credit-card-2-front',
}

// 获取卡品牌显示名称
export function getCardBrandName(brand: string): string {
  const brandNames: Record<string, string> = {
    visa: 'Visa',
    mastercard: 'Mastercard',
    amex: 'American Express',
    discover: 'Discover',
    unionpay: 'UnionPay',
    unknown: 'Card',
  }
  return brandNames[brand.toLowerCase()] || 'Card'
}
