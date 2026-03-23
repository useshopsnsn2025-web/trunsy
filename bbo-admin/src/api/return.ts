import request from './request'

/**
 * 退货管理 API
 */

export interface ReturnInfo {
  id: number
  return_no: string
  order_id: number
  order_no: string
  user_id: number
  seller_id: number
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
  admin_remark?: string
  seller_reply?: string
  seller_replied_at?: string
  return_tracking_no?: string
  return_carrier?: string
  shipped_at?: string
  received_at?: string
  refunded_at?: string
  expired_at?: string
  created_at: string
  updated_at: string
  goods_snapshot?: any
  buyer?: {
    id: number
    nickname: string
    avatar: string
  }
  seller?: {
    id: number
    nickname: string
    avatar: string
  }
}

export interface ReturnListParams {
  page?: number
  pageSize?: number
  return_no?: string
  order_no?: string
  status?: number | string
  type?: number | string
  start_date?: string
  end_date?: string
}

export interface ReturnStatistics {
  total: number
  pending: number
  approved: number
  rejected: number
  completed: number
  today: number
}

// 退货状态常量（与后端 OrderReturn 模型保持一致）
export const RETURN_STATUS = {
  PENDING: 0,      // 待处理
  APPROVED: 1,     // 已同意
  REJECTED: 2,     // 已拒绝
  CANCELLED: 3,    // 已取消
  IN_RETURN: 4,    // 退货中（买家已发货）
  COMPLETED: 5,    // 已完成（退款完成）
}

// 退货类型常量
export const RETURN_TYPE = {
  REFUND_ONLY: 1,     // 仅退款
  RETURN_REFUND: 2,   // 退货退款
}

/**
 * 获取退货列表
 */
export function getReturnList(params: ReturnListParams) {
  return request({
    url: '/returns',
    method: 'get',
    params
  })
}

/**
 * 获取退货详情
 */
export function getReturnDetail(id: number) {
  return request({
    url: `/returns/${id}`,
    method: 'get'
  })
}

/**
 * 获取退货统计
 */
export function getReturnStatistics() {
  return request({
    url: '/returns/statistics',
    method: 'get'
  })
}

/**
 * 同意退货申请
 */
export function approveReturn(id: number, data?: { remark?: string }) {
  return request({
    url: `/returns/${id}/approve`,
    method: 'post',
    data
  })
}

/**
 * 拒绝退货申请
 */
export function rejectReturn(id: number, data: { reason: string; remark?: string }) {
  return request({
    url: `/returns/${id}/reject`,
    method: 'post',
    data
  })
}

/**
 * 确认收货（卖家已收到退货商品）
 */
export function confirmReceive(id: number, data?: { remark?: string }) {
  return request({
    url: `/returns/${id}/receive`,
    method: 'post',
    data
  })
}

/**
 * 执行退款
 */
export function processRefund(id: number, data?: { remark?: string }) {
  return request({
    url: `/returns/${id}/refund`,
    method: 'post',
    data
  })
}

/**
 * 关闭退货申请
 */
export function closeReturn(id: number, data: { reason: string; remark?: string }) {
  return request({
    url: `/returns/${id}/close`,
    method: 'post',
    data
  })
}

/**
 * 更新备注
 */
export function updateReturnRemark(id: number, data: { remark: string }) {
  return request({
    url: `/returns/${id}/remark`,
    method: 'put',
    data
  })
}

/**
 * 删除退货记录
 */
export function deleteReturn(id: number) {
  return request({
    url: `/returns/${id}`,
    method: 'delete'
  })
}

/**
 * 批量删除退货记录
 */
export function batchDeleteReturns(ids: number[]) {
  return request({
    url: '/returns/batch/delete',
    method: 'post',
    data: { ids }
  })
}
