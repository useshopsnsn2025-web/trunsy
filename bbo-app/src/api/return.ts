import { get, post } from '@/utils/request'

/**
 * 退货申请相关接口
 */

export interface ReturnRequest {
  order_id: number
  type: number // 1=仅退款 2=退货退款
  reason: string
  reason_detail?: string
  images?: string[]
}

export interface ReturnInfo {
  id: number
  return_no: string
  order_id: number
  order_no: string
  type: number
  type_text: string
  reason: string
  reason_text: string
  reason_detail?: string
  images?: string[]
  refund_amount: number
  currency: string
  status: number
  status_text: string
  reject_reason?: string
  seller_reply?: string
  seller_replied_at?: string
  return_tracking_no?: string
  return_carrier?: string
  shipped_at?: string
  received_at?: string
  refunded_at?: string
  expired_at?: string
  created_at: string
  goods_snapshot?: any
}

/**
 * 检查订单是否可以申请退货
 */
export function checkCanReturn(orderId: number) {
  return get<{
    can_return: boolean
    reason?: string
    return_id?: number
  }>(`/returns/check/${orderId}`)
}

/**
 * 创建退货申请
 */
export function createReturn(data: ReturnRequest) {
  return post<{
    id: number
    return_no: string
    status: number
  }>('/returns', data)
}

/**
 * 获取退货申请列表
 */
export function getReturnList(params?: { page?: number; pageSize?: number; status?: number }) {
  return get<ReturnInfo[]>('/returns', params)
}

/**
 * 获取退货申请详情
 */
export function getReturnDetail(id: number) {
  return get<ReturnInfo>(`/returns/${id}`)
}

/**
 * 根据订单ID获取退货申请
 */
export function getReturnByOrder(orderId: number) {
  return get<ReturnInfo>(`/returns/by-order/${orderId}`)
}

/**
 * 取消退货申请
 */
export function cancelReturn(id: number) {
  return post(`/returns/${id}/cancel`)
}

/**
 * 提交退货物流信息
 */
export function shipReturn(id: number, data: { tracking_no: string; carrier?: string }) {
  return post(`/returns/${id}/ship`, data)
}

// ==================== 卖家退货管理接口 ====================

export interface SellerReturnInfo extends ReturnInfo {
  buyer?: {
    id: number
    nickname: string
    avatar: string
  }
  order?: {
    id: number
    order_no: string
    status: number
    total_amount: number
    currency: string
    created_at: string
  }
}

export interface SellerReturnStatistics {
  pending: number
  approved: number
  in_return: number
  completed: number
  need_action: number
}

/**
 * 获取卖家退货统计
 */
export function getSellerReturnStatistics() {
  return get<SellerReturnStatistics>('/seller/returns/statistics')
}

/**
 * 获取卖家退货列表
 */
export function getSellerReturnList(params?: { page?: number; pageSize?: number; status?: number }) {
  return get<SellerReturnInfo[]>('/seller/returns', params)
}

/**
 * 获取卖家退货详情
 */
export function getSellerReturnDetail(id: number) {
  return get<SellerReturnInfo>(`/seller/returns/${id}`)
}

/**
 * 卖家同意退货
 */
export function approveReturn(id: number, data?: { reply?: string }) {
  return post(`/seller/returns/${id}/approve`, data)
}

/**
 * 卖家拒绝退货
 */
export function rejectReturn(id: number, data: { reason: string; reply?: string }) {
  return post(`/seller/returns/${id}/reject`, data)
}

/**
 * 卖家确认收货
 */
export function receiveReturn(id: number) {
  return post(`/seller/returns/${id}/receive`)
}

/**
 * 卖家执行退款
 */
export function refundReturn(id: number) {
  return post(`/seller/returns/${id}/refund`)
}
