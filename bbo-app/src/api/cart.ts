// 购物车相关 API
import { get, post, put, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'
import type { Goods } from './goods'

// 购物车商品活动信息
export interface CartGoodsPromotion {
  id: number
  name: string
  type: number
  promotionPrice: number
  discount: number
  discountPercent: number
  endTime: string
}

// 购物车项
export interface CartItem {
  id: number
  goodsId: number
  quantity: number
  selected: boolean
  createdAt: string
  updatedAt: string
  // 关联商品信息
  goods: Pick<Goods, 'id' | 'goodsNo' | 'title' | 'price' | 'originalPrice' | 'currency' | 'images' | 'stock' | 'status' | 'freeShipping' | 'shippingFee' | 'condition'> & {
    promotion?: CartGoodsPromotion
  }
}

// 购物车列表响应
export interface CartListResponse {
  list: CartItem[]
  total: number
  selectedCount: number
  totalAmount: number
  totalShipping: number
}

// 添加到购物车参数
export interface AddToCartParams {
  goodsId: number
  quantity?: number
}

// 更新购物车参数
export interface UpdateCartParams {
  quantity?: number
  selected?: boolean
}

// 购物车数量响应
export interface CartCountResponse {
  count: number
}

// 获取购物车列表
export function getCartList() {
  return get<CartListResponse>(API_PATHS.cart.list)
}

// 添加到购物车
export function addToCart(data: AddToCartParams) {
  return post<CartItem>(API_PATHS.cart.add, data)
}

// 更新购物车项
export function updateCartItem(id: number | string, data: UpdateCartParams) {
  return put<CartItem>(API_PATHS.cart.update(id), data)
}

// 从购物车移除
export function removeFromCart(id: number | string) {
  return del(API_PATHS.cart.remove(id))
}

// 清空购物车
export function clearCart() {
  return del(API_PATHS.cart.clear)
}

// 获取购物车数量
export function getCartCount() {
  return get<CartCountResponse>(API_PATHS.cart.count)
}

// 批量更新选中状态
export function updateCartSelection(ids: number[], selected: boolean) {
  return post<{ success: boolean }>(API_PATHS.cart.list + '/selection', { ids, selected })
}

// 全选/取消全选
export function selectAllCart(selected: boolean) {
  return post<{ success: boolean }>(API_PATHS.cart.list + '/select-all', { selected })
}
