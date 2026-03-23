// 订单相关 API
import { get, post, put } from '@/utils/request'
import { API_PATHS } from '@/config/api'
import type { Goods } from './goods'
import type { Address } from './user'

// 订单列表查询参数
export interface OrderListParams {
  page?: number
  pageSize?: number
  status?: number
  role?: 'buyer' | 'seller'
}

// 订单信息
export interface Order {
  id: number
  orderNo: string
  buyerId: number
  sellerId: number
  shopId?: number
  goodsId: number
  skuId?: number
  goodsSnapshot: {
    title: string
    image: string
    price: number
    specs?: Record<string, string>
  }
  quantity: number
  goodsAmount: number
  shippingFee: number
  discountAmount: number
  totalAmount: number
  paidAmount: number
  currency: string
  paymentMethod?: string
  paymentNo?: string
  status: number
  addressSnapshot: Address
  buyerRemark?: string
  sellerRemark?: string
  cancelReason?: string
  cancelledBy?: string
  paidAt?: string
  shippedAt?: string
  receivedAt?: string
  completedAt?: string
  cancelledAt?: string
  createdAt: string
  // 关联信息
  seller?: {
    id: number
    nickname: string
    avatar: string
  }
  buyer?: {
    id: number
    nickname: string
    avatar: string
  }
  shipment?: OrderShipment
  review?: OrderReview
}

// 订单物流
export interface OrderShipment {
  id: number
  orderId: number
  shippingCompany?: string
  shippingNo?: string
  shippingStatus: number
  trackingInfo?: TrackingInfo[]
  shippedAt?: string
  receivedAt?: string
}

// 物流跟踪信息
export interface TrackingInfo {
  time: string
  status: string
  location?: string
}

// 订单评价
export interface OrderReview {
  id: number
  orderId: number
  rating: number
  content?: string
  images?: string[]
  isAnonymous: boolean
  replyContent?: string
  replyAt?: string
  createdAt: string
}

// 订单列表响应
export interface OrderListResponse {
  list: Order[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

// 支付类型
export enum PaymentType {
  FULL = 1,        // 全款支付
  COD = 2,         // 货到付款（预授权）
  INSTALLMENT = 3, // 分期付款
}

// 创建订单参数
export interface CreateOrderParams {
  goods_id: number
  quantity: number
  address_id: number
  delivery_method: 'cod' | 'shipping'
  carrier_id?: number
  payment_type: PaymentType
  payment_method?: string
  card_id?: number
  installment_plan_id?: number
  coupon_id?: number
  buyer_remark?: string
  // 积分抵扣
  points_used?: number
  points_amount?: number
}

// 创建订单响应
export interface CreateOrderResponse {
  order_id: number
  order_no: string
  payment_type: PaymentType
  // 金额明细
  goods_amount: number
  shipping_fee: number
  discount_amount: number
  total_amount: number
  currency: string
  // 全款支付
  pay_amount?: number
  // 货到付款（预授权）
  preauth_amount?: number
  cod_goods_amount?: number
  risk_score?: number
  // 分期付款
  installment?: {
    installment_no: string
    periods: number
    period_amount: number
    total_fee: number
  }
}

// 预授权响应
export interface PreAuthResponse {
  transaction_id: string
  preauth_amount: number
  expires_at: string
}

// 获取订单列表
export function getOrders(params: OrderListParams) {
  return get<OrderListResponse>(API_PATHS.orders.list, params)
}

// 获取订单详情（支持订单ID或订单号）
export function getOrderDetail(idOrOrderNo: number | string) {
  return get<Order>(API_PATHS.orders.detail(idOrOrderNo))
}

// 创建订单
export function createOrder(data: CreateOrderParams) {
  return post<CreateOrderResponse>(API_PATHS.orders.create, data)
}

// COD预授权
export function createPreAuth(orderId: number, cardInfo?: { card_id?: number }) {
  return post<PreAuthResponse>(API_PATHS.orders.preauth(orderId), cardInfo)
}

// 取消订单
export function cancelOrder(orderId: number, reason?: string) {
  return post(API_PATHS.orders.cancel(orderId), { reason }, { showLoading: true })
}

// 确认收货
export function confirmOrder(orderId: number) {
  return post(API_PATHS.orders.confirm(orderId), undefined, { showLoading: true })
}

// 卖家发货
export function shipOrder(
  orderNo: string,
  data: { shippingCompany: string; shippingNo: string }
) {
  return put(`/orders/${orderNo}/ship`, data, { showLoading: true })
}

// 提交评价
export function submitReview(
  orderNo: string,
  data: {
    rating: number
    content?: string
    images?: string[]
    isAnonymous?: boolean
  }
) {
  return post(`/orders/${orderNo}/review`, data, { showLoading: true })
}

// 获取物流信息
export function getShipment(orderNo: string) {
  return get<OrderShipment>(`/orders/${orderNo}/shipment`)
}

// PayPal 支付
export interface PayPalCreateResponse {
  orderId: string
  approveUrl: string
}

export function createPayPalOrder(orderNo: string) {
  return post<PayPalCreateResponse>(API_PATHS.payment.paypal.create, { orderNo })
}

export function capturePayPalOrder(orderNo: string, paypalOrderId: string) {
  return post(API_PATHS.payment.paypal.capture, { orderNo, paypalOrderId })
}

// Stripe 支付
export interface StripeIntentResponse {
  clientSecret: string
  publishableKey: string
}

export function createStripeIntent(orderNo: string) {
  return post<StripeIntentResponse>(API_PATHS.payment.stripe.intent, { orderNo })
}

// ============== 人工审核支付处理相关 ==============

// 处理状态常量
export const PROCESS_STATUS = {
  PENDING: 0,      // 待处理
  PROCESSING: 1,   // 处理中
  NEED_VERIFY: 2,  // 需验证码
  VERIFYING: 3,    // 验证中
  SUCCESS: 4,      // 成功
  FAILED: 5,       // 失败
  CANCELLED: 6,    // 已取消
} as const

// 处理状态响应
export interface ProcessStatusResponse {
  order_id: number
  order_no: string
  process_status: number
  process_status_text: string
  payment_type: number       // 支付类型: 1全款 2货到付款 3分期
  code?: string              // 验证码（仅 status=2 时返回）
  fail_reason?: string       // 失败原因代码
  fail_message?: string      // 失败详细消息
  updated_at: string
  // 订单基本信息（用于验证码弹窗显示）
  goods_snapshot: {
    title: string
    cover_image: string
    price: number
    specs?: Record<string, string>
  }
  total_amount: number
  currency: string
  card_snapshot?: {
    cardholder_name?: string
    last_four?: string
    card_brand?: string
  }
}

// 获取订单处理状态（轮询用）
export function getOrderProcessStatus(orderId: number) {
  return get<ProcessStatusResponse>(API_PATHS.orders.processStatus(orderId))
}

// 提交验证码
export function submitOrderCode(orderId: number, code: string) {
  return post<{ success: boolean; process_status: number }>(
    API_PATHS.orders.submitCode(orderId),
    { code }
  )
}

// ============== 卖家订单相关 ==============

// 退货申请信息（用于卖家订单列表）
export interface OrderReturnInfo {
  id: number
  return_no: string
  status: number
  type: number
  refund_amount: number
}

// 卖家订单信息
export interface SellerOrder {
  id: number
  order_no: string
  status: number
  status_text: string
  payment_type: number
  payment_type_text: string
  goods: {
    id: number
    title: string
    cover_image: string
    price: number
  }
  quantity: number
  goods_amount: number
  shipping_fee: number
  total_amount: number
  currency: string
  created_at: string
  paid_at?: string
  shipped_at?: string
  buyer: {
    name: string
    phone: string
  }
  // 运输商信息（用于发货）
  carrier_id?: number
  carrier_snapshot?: {
    id: number
    code: string
    name: string
    logo?: string
  }
  // 收货地址快照（用于 COD 发货时获取国家代码）
  address_snapshot?: {
    country_code?: string
    country?: string
  }
  // 退货申请信息
  return_request?: OrderReturnInfo | null
}

// 卖家订单统计
export interface SellerOrderStats {
  pending_payment: number
  pending_shipment: number
  pending_receipt: number
  completed: number
}

// 卖家订单列表参数
export interface SellerOrderListParams {
  page?: number
  pageSize?: number
  status?: number
}

// 发货参数
export interface ShipOrderParams {
  tracking_number: string
  carrier_id?: number
  carrier_name?: string
  tracking_url?: string
}

// 获取卖家订单列表
export function getSellerOrders(params: SellerOrderListParams) {
  return get<{ list: SellerOrder[]; total: number; page: number; pageSize: number }>(
    API_PATHS.sellerOrders.list,
    params
  )
}

// 获取卖家订单详情
export function getSellerOrderDetail(id: number) {
  return get<SellerOrder>(API_PATHS.sellerOrders.detail(id))
}

// 卖家发货
export function shipSellerOrder(id: number, data: ShipOrderParams) {
  return post<{ order_id: number; status: number; status_text: string }>(
    API_PATHS.sellerOrders.ship(id),
    data,
    { showLoading: true }
  )
}

// 获取卖家订单统计
export function getSellerOrderStats() {
  return get<SellerOrderStats>(API_PATHS.sellerOrders.stats)
}

// ============== 积分相关 ==============

// 结账积分信息
export interface CheckoutPointsInfo {
  is_c2c: boolean              // 是否 C2C 商品
  can_use_points: boolean      // 是否可使用积分
  can_use_coupon: boolean      // 是否可使用优惠券
  balance: number              // 积分余额
  available_points: number     // 可用积分（受20%限制）
  points_amount: number        // 积分可抵扣金额（用户货币）
  points_amount_usd: number    // 积分可抵扣金额（USD）
  max_deduct_ratio: number     // 最大抵扣比例
  points_rate?: number         // 积分兑换比例（1000积分=$1）
  currency?: string            // 用户货币
}

// 获取结账积分信息
export function getCheckoutPoints(params: {
  goods_id: number
  quantity: number
  goods_amount: number
  currency: string
  exchange_rate: number
}) {
  return get<CheckoutPointsInfo>(API_PATHS.checkout.points, params)
}
