<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('orderDetail.title')"  />

    <!-- 订单内容 -->
    <view v-if="order" class="order-content">
      <!-- 订单状态卡片 -->
      <view class="status-card" :class="getStatusClass()">
        <view class="status-icon">
          <text :class="getStatusIcon()"></text>
        </view>
        <view class="status-info">
          <text class="status-text">{{ getStatusText() }}</text>
          <text class="status-desc">{{ getStatusDesc() }}</text>
        </view>
      </view>

      <!-- 收货地址 -->
      <view v-if="order.address_snapshot" class="section-card">
        <view class="section-header">
          <text class="bi bi-geo-alt"></text>
          <text class="section-title">{{ t('orderDetail.shippingAddress') }}</text>
        </view>
        <view class="address-content">
          <view class="address-row">
            <text class="recipient-name">{{ order.address_snapshot.recipient_name }}</text>
            <text class="recipient-phone">{{ order.address_snapshot.phone }}</text>
          </view>
          <text class="address-detail">{{ formatAddress(order.address_snapshot) }}</text>
        </view>
      </view>

      <!-- 物流信息卡片（已发货时显示） -->
      <view v-if="order.shipment && (order.status === 2 || order.status === 3)" class="section-card shipping-card">
        <view class="section-header">
          <text class="bi bi-truck"></text>
          <text class="section-title">{{ t('orderDetail.shippingInfo') }}</text>
        </view>
        <view class="shipping-content">
          <!-- 运输商信息 -->
          <view class="carrier-info">
            <image v-if="order.shipment.carrier_logo" :src="order.shipment.carrier_logo" class="carrier-logo" mode="aspectFit" />
            <view class="carrier-detail">
              <text class="carrier-name">{{ order.shipment.carrier_name || order.shipment.shipping_company }}</text>
              <view class="tracking-row">
                <text class="tracking-no">{{ order.shipment.shipping_no }}</text>
                <text class="bi bi-copy copy-btn" @click="copyTrackingNo"></text>
              </view>
            </view>
          </view>
          <!-- 发货时间 -->
          <view v-if="order.shipment.shipped_at" class="shipped-time">
            <text class="bi bi-clock"></text>
            <text class="time-text">{{ t('orderDetail.shippedAt') }}: {{ order.shipment.shipped_at }}</text>
          </view>
          <!-- 追踪物流按钮 -->
          <view v-if="order.shipment.tracking_url" class="track-btn-wrapper">
            <button class="track-btn" @click="trackPackage">
              <text class="bi bi-box-arrow-up-right"></text>
              <text>{{ t('orderDetail.trackPackage') }}</text>
            </button>
          </view>
        </view>
      </view>

      <!-- 商品信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-box-seam"></text>
          <text class="section-title">{{ t('orderDetail.goodsInfo') }}</text>
        </view>
        <view class="goods-item">
          <image :src="order.goods_snapshot?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <text class="goods-title">{{ order.goods_snapshot?.title }}</text>
            <text v-if="order.goods_snapshot?.specs" class="goods-specs">
              {{ formatSpecs(order.goods_snapshot.specs) }}
            </text>
            <view class="goods-price-row">
              <text class="goods-price">{{ formatPrice(order.goods_snapshot?.user_price || order.goods_snapshot?.price, order.currency) }}</text>
              <text class="goods-quantity">x{{ order.goods_snapshot?.quantity || 1 }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 支付信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-credit-card"></text>
          <text class="section-title">{{ t('orderDetail.paymentInfo') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.paymentType') }}</text>
            <text class="info-value">{{ getPaymentTypeText() }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.goodsAmount') }}</text>
            <text class="info-value">{{ formatPrice(order.goods_amount, order.currency) }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.shippingFee') }}</text>
            <text class="info-value">{{ formatPrice(order.shipping_fee, order.currency) }}</text>
          </view>
          <view v-if="order.discount_amount > 0" class="info-row">
            <text class="info-label">{{ t('orderDetail.discount') }}</text>
            <text class="info-value discount">-{{ formatPrice(order.discount_amount, order.currency) }}</text>
          </view>
          <view class="info-row total">
            <text class="info-label">{{ t('orderDetail.totalAmount') }}</text>
            <text class="info-value highlight">{{ formatPrice(order.total_amount, order.currency) }}</text>
          </view>
          <view v-if="order.paid_amount > 0" class="info-row">
            <text class="info-label">{{ t('orderDetail.paidAmount') }}</text>
            <text class="info-value success">{{ formatPrice(order.paid_amount, order.currency) }}</text>
          </view>
        </view>

        <!-- 预授权信息（货到付款） -->
        <view v-if="order.payment_type === 2 && order.preauth_amount > 0" class="preauth-info">
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.preauthAmount') }}</text>
            <text class="info-value">{{ formatPrice(order.preauth_amount, order.currency) }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.preauthStatus') }}</text>
            <text class="info-value" :class="'preauth-' + order.preauth_status">{{ getPreauthStatusText() }}</text>
          </view>
        </view>

        <!-- 分期信息 -->
        <view v-if="order.payment_type === 3 && order.installment" class="installment-info">
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.installmentPeriods') }}</text>
            <text class="info-value">{{ order.installment.paid_periods }}/{{ order.installment.periods }} {{ t('orderDetail.periods') }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.periodAmount') }}</text>
            <text class="info-value">{{ formatPrice(order.installment.period_amount, order.currency) }}/{{ t('orderDetail.perPeriod') }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.nextDueDate') }}</text>
            <text class="info-value">{{ order.installment.next_due_date || '-' }}</text>
          </view>
        </view>
      </view>

      <!-- 订单信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-file-text"></text>
          <text class="section-title">{{ t('orderDetail.orderInfo') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.orderNo') }}</text>
            <view class="info-value-with-copy">
              <text class="info-value">{{ order.order_no }}</text>
              <text class="bi bi-copy copy-icon" @click="copyOrderNo"></text>
            </view>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('orderDetail.createTime') }}</text>
            <text class="info-value">{{ order.created_at }}</text>
          </view>
          <view v-if="order.paid_at" class="info-row">
            <text class="info-label">{{ t('orderDetail.paidTime') }}</text>
            <text class="info-value">{{ order.paid_at }}</text>
          </view>
          <view v-if="order.shipped_at" class="info-row">
            <text class="info-label">{{ t('orderDetail.shippedTime') }}</text>
            <text class="info-value">{{ order.shipped_at }}</text>
          </view>
          <view v-if="order.received_at" class="info-row">
            <text class="info-label">{{ t('orderDetail.receivedTime') }}</text>
            <text class="info-value">{{ order.received_at }}</text>
          </view>
          <view v-if="order.completed_at" class="info-row">
            <text class="info-label">{{ t('orderDetail.completedTime') }}</text>
            <text class="info-value">{{ order.completed_at }}</text>
          </view>
          <view v-if="order.buyer_remark" class="info-row">
            <text class="info-label">{{ t('orderDetail.buyerRemark') }}</text>
            <text class="info-value remark">{{ order.buyer_remark }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部操作按钮 -->
    <view v-if="order" class="bottom-actions">
      <!-- 退款中状态显示查看退货按钮 -->
      <template v-if="order.status === 5 || order.status === 6">
        <button class="btn-secondary" @click="viewReturn">
          {{ t('orderDetail.viewReturn') }}
        </button>
        <button class="btn-primary" @click="continueShopping">
          {{ t('orderDetail.continueShopping') }}
        </button>
      </template>
      <!-- 待收货状态显示确认收货按钮 -->
      <template v-else-if="order.status === 2">
        <button v-if="canReturn" class="btn-secondary" @click="requestReturn">
          {{ t('orderDetail.requestReturn') }}
        </button>
        <button class="btn-primary" @click="confirmReceipt">
          {{ t('orderDetail.confirmReceipt') }}
        </button>
      </template>
      <!-- 已完成状态可申请退货 -->
      <template v-else-if="order.status === 3">
        <button v-if="canReturn" class="btn-secondary" @click="requestReturn">
          {{ t('orderDetail.requestReturn') }}
        </button>
        <button v-else-if="existingReturnId" class="btn-secondary" @click="viewReturn">
          {{ t('orderDetail.viewReturn') }}
        </button>
        <button class="btn-primary" @click="continueShopping">
          {{ t('orderDetail.continueShopping') }}
        </button>
      </template>
      <!-- 货到付款-待确认显示确认/拒绝按钮 -->
      <template v-else-if="order.payment_type === 2 && order.cod_status === 'pending_confirm'">
        <button class="btn-secondary" @click="refuseDelivery">
          {{ t('orderDetail.refuseDelivery') }}
        </button>
        <button class="btn-primary" @click="confirmDelivery">
          {{ t('orderDetail.confirmDelivery') }}
        </button>
      </template>
      <!-- 继续购物 -->
      <button v-else class="btn-secondary" @click="continueShopping">
        {{ t('orderDetail.continueShopping') }}
      </button>
    </view>

    <!-- 确认收货弹窗 -->
    <ConfirmDialog
      :visible="showConfirmReceiptDialog"
      :title="t('orderDetail.confirmReceiptTitle')"
      :content="t('orderDetail.confirmReceiptContent')"
      icon="bi-check-circle"
      icon-type="warning"
      @update:visible="showConfirmReceiptDialog = $event"
      @confirm="handleConfirmReceipt"
    />

    <!-- 确认收货（货到付款）弹窗 -->
    <ConfirmDialog
      :visible="showConfirmDeliveryDialog"
      :title="t('orderDetail.confirmDeliveryTitle')"
      :content="t('orderDetail.confirmDeliveryContent')"
      icon="bi-check-circle"
      icon-type="warning"
      @update:visible="showConfirmDeliveryDialog = $event"
      @confirm="handleConfirmDelivery"
    />

    <!-- 拒绝收货弹窗 -->
    <ConfirmDialog
      :visible="showRefuseDeliveryDialog"
      :title="t('orderDetail.refuseDeliveryTitle')"
      :content="t('orderDetail.refuseDeliveryContent')"
      icon="bi-x-circle"
      icon-type="warning"
      @update:visible="showRefuseDeliveryDialog = $event"
      @confirm="handleRefuseDelivery"
    />
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { getOrderDetail, confirmOrder } from '@/api/order'
import { checkCanReturn } from '@/api/return'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const orderId = ref<number | null>(null)
const orderNo = ref('')
const order = ref<any>(null)

// 弹窗状态
const showConfirmReceiptDialog = ref(false)
const showConfirmDeliveryDialog = ref(false)
const showRefuseDeliveryDialog = ref(false)

// 退货相关状态
const canReturn = ref(false)
const existingReturnId = ref<number | null>(null)

// 格式化价格
function formatPrice(amount: number | undefined, currency?: string) {
  if (amount === undefined || amount === null) return ''
  return appStore.formatPrice(amount, currency || 'USD')
}

// 格式化地址
function formatAddress(address: any) {
  if (!address) return ''
  const parts = [
    address.address,
    address.district,
    address.city,
    address.province,
    address.postal_code,
    address.country_code
  ].filter(Boolean)
  return parts.join(', ')
}

// 格式化规格
function formatSpecs(specs: Record<string, string>) {
  if (!specs) return ''
  return Object.entries(specs).map(([k, v]) => `${k}: ${v}`).join(', ')
}

// 获取状态样式类
function getStatusClass() {
  if (!order.value) return ''
  const statusMap: Record<number, string> = {
    0: 'status-pending',
    1: 'status-processing',
    2: 'status-shipping',
    3: 'status-completed',
    4: 'status-cancelled',
    5: 'status-refunding',
    6: 'status-refunded',
    7: 'status-closed'
  }
  return statusMap[order.value.status] || ''
}

// 获取状态图标
function getStatusIcon() {
  if (!order.value) return 'bi bi-clock'
  const iconMap: Record<number, string> = {
    0: 'bi bi-clock',
    1: 'bi bi-box-seam',
    2: 'bi bi-truck',
    3: 'bi bi-check-circle',
    4: 'bi bi-x-circle',
    5: 'bi bi-arrow-counterclockwise',
    6: 'bi bi-check2-circle',
    7: 'bi bi-slash-circle'
  }
  return iconMap[order.value.status] || 'bi bi-clock'
}

// 获取状态文字
function getStatusText() {
  if (!order.value) return ''
  return order.value.status_text || ''
}

// 获取状态描述
function getStatusDesc() {
  if (!order.value) return ''
  const status = order.value.status
  const paymentType = order.value.payment_type

  if (status === 0) return t('orderDetail.statusDescPending')
  if (status === 1) return t('orderDetail.statusDescShipping')
  if (status === 2) {
    if (paymentType === 2) return t('orderDetail.statusDescCodConfirm')
    return t('orderDetail.statusDescReceipt')
  }
  if (status === 3) return t('orderDetail.statusDescCompleted')
  if (status === 4) return t('orderDetail.statusDescCancelled')
  return ''
}

// 获取支付类型文字（优先使用后端翻译，fallback 到前端翻译）
function getPaymentTypeText() {
  if (!order.value) return ''
  // 后端已返回翻译后的文本
  if (order.value.payment_type_text) {
    return order.value.payment_type_text
  }
  // Fallback 到前端翻译
  const typeMap: Record<number, string> = {
    1: t('orderDetail.paymentTypeFull'),
    2: t('orderDetail.paymentTypeCod'),
    3: t('orderDetail.paymentTypeInstallment')
  }
  return typeMap[order.value.payment_type] || ''
}

// 获取预授权状态文字（优先使用后端翻译，fallback 到前端翻译）
function getPreauthStatusText() {
  if (!order.value) return ''
  // 后端已返回翻译后的文本
  if (order.value.preauth_status_text) {
    return order.value.preauth_status_text
  }
  // Fallback 到前端翻译
  const statusMap: Record<string, string> = {
    'pending': t('orderDetail.preauthPending'),
    'authorized': t('orderDetail.preauthAuthorized'),
    'captured': t('orderDetail.preauthCaptured'),
    'voided': t('orderDetail.preauthVoided'),
    'expired': t('orderDetail.preauthExpired')
  }
  return statusMap[order.value.preauth_status] || order.value.preauth_status
}

// 加载订单信息
async function loadOrderInfo() {
  const idOrNo = orderId.value || orderNo.value
  if (!idOrNo) {
    loading.value = false
    return
  }

  try {
    const res = await getOrderDetail(idOrNo)
    if (res.code === 0) {
      order.value = res.data
      // 检查是否可以退货
      await checkReturnStatus()
    }
  } catch (e) {
    console.error('Failed to load order:', e)
  } finally {
    loading.value = false
  }
}

// 检查退货状态
async function checkReturnStatus() {
  if (!order.value?.id) return

  // 待收货(2)、已完成(3)、退款中(5)、已退款(6)状态都需要检查
  const allowedStatuses = [2, 3, 5, 6]
  if (!allowedStatuses.includes(order.value.status)) {
    canReturn.value = false
    return
  }

  try {
    const res = await checkCanReturn(order.value.id)
    if (res.code === 0) {
      canReturn.value = res.data.can_return
      existingReturnId.value = res.data.return_id || null
    }
  } catch (e) {
    console.error('Failed to check return status:', e)
  }
}

// 申请退货
function requestReturn() {
  if (!order.value?.id) return
  uni.navigateTo({
    url: `/pages/order/return-request?orderId=${order.value.id}`
  })
}

// 查看退货详情
function viewReturn() {
  if (existingReturnId.value) {
    uni.navigateTo({
      url: `/pages/order/return-detail?id=${existingReturnId.value}`
    })
  } else if (order.value?.id) {
    // 通过订单ID查找退货记录
    uni.navigateTo({
      url: `/pages/order/return-detail?orderId=${order.value.id}`
    })
  }
}

// 复制订单号
function copyOrderNo() {
  if (!order.value?.order_no) return
  uni.setClipboardData({
    data: order.value.order_no,
    showToast: false, // 禁用 UniApp 默认提示
    success: () => {
      toast.success(t('common.copied'))
    }
  })
}

// 复制物流单号
function copyTrackingNo() {
  if (!order.value?.shipment?.shipping_no) return
  uni.setClipboardData({
    data: order.value.shipment.shipping_no,
    showToast: false, // 禁用 UniApp 默认提示
    success: () => {
      toast.success(t('common.copied'))
    }
  })
}

// 追踪物流
function trackPackage() {
  if (!order.value?.shipment?.tracking_url) return
  // #ifdef H5
  window.open(order.value.shipment.tracking_url, '_blank')
  // #endif
  // #ifndef H5
  uni.navigateTo({
    url: `/pages/webview/index?url=${encodeURIComponent(order.value.shipment.tracking_url)}`
  })
  // #endif
}

// 确认收货
function confirmReceipt() {
  showConfirmReceiptDialog.value = true
}

// 确认收货回调
async function handleConfirmReceipt() {
  if (order.value?.id) {
    try {
      uni.showLoading({ title: '' })
      const result = await confirmOrder(order.value.id)
      if (result.code === 0) {
        toast.success(t('orderDetail.confirmReceiptSuccess'))
        loadOrderInfo()
      } else {
        toast.error(result.msg || t('common.error'))
      }
    } finally {
      uni.hideLoading()
    }
  }
}

// 确认收货（货到付款）
function confirmDelivery() {
  showConfirmDeliveryDialog.value = true
}

// 确认收货（货到付款）回调
function handleConfirmDelivery() {
  // TODO: 调用确认收货API
  toast.success(t('common.success'))
}

// 拒绝收货
function refuseDelivery() {
  showRefuseDeliveryDialog.value = true
}

// 拒绝收货回调
function handleRefuseDelivery() {
  // TODO: 调用拒绝收货API
  toast.success(t('common.success'))
}

// 继续购物
function continueShopping() {
  uni.switchTab({
    url: '/pages/index/index'
  })
}

onLoad((options) => {
  if (options?.id) {
    orderId.value = parseInt(options.id)
  }
  if (options?.orderNo) {
    orderNo.value = options.orderNo
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('orderDetail.title') })
  if (orderId.value || orderNo.value) {
    loadOrderInfo()
  } else {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.order-content {
  padding: 24rpx;
}

/* 状态卡片 */
.status-card {
  display: flex;
  align-items: center;
  padding: 32rpx;
  background: linear-gradient(135deg, #FF6B35 0%, #ff8f5a 100%);
  border-radius: 16rpx;
  margin-bottom: 24rpx;

  &.status-completed {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
  }

  &.status-cancelled,
  &.status-closed {
    background: linear-gradient(135deg, #8c8c8c 0%, #bfbfbf 100%);
  }

  &.status-refunding,
  &.status-refunded {
    background: linear-gradient(135deg, #faad14 0%, #ffc53d 100%);
  }
}

.status-icon {
  width: 80rpx;
  height: 80rpx;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;

  .bi {
    font-size: 40rpx;
    color: #fff;
  }
}

.status-info {
  flex: 1;
}

.status-text {
  display: block;
  font-size: 36rpx;
  font-weight: 600;
  color: #fff;
  margin-bottom: 8rpx;
}

.status-desc {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.9);
}

/* 通用卡片样式 */
.section-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 24rpx;
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid #f0f0f0;

  .bi {
    font-size: 32rpx;
    color: #FF6B35;
    margin-right: 12rpx;
  }
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;
}

/* 地址内容 */
.address-content {
  padding-left: 44rpx;
}

.address-row {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
}

.recipient-name {
  font-size: 30rpx;
  font-weight: 500;
  color: #333;
  margin-right: 20rpx;
}

.recipient-phone {
  font-size: 28rpx;
  color: #666;
}

.address-detail {
  font-size: 26rpx;
  color: #666;
  line-height: 1.5;
}

/* 商品信息 */
.goods-item {
  display: flex;
  padding-left: 44rpx;
}

.goods-image {
  width: 160rpx;
  height: 160rpx;
  border-radius: 12rpx;
  background: #f5f5f5;
  margin-right: 24rpx;
  flex-shrink: 0;
}

.goods-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.goods-title {
  font-size: 28rpx;
  color: #333;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-specs {
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

.goods-price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12rpx;
}

.goods-price {
  font-size: 30rpx;
  font-weight: 600;
  color: #FF6B35;
}

.goods-quantity {
  font-size: 26rpx;
  color: #999;
}

/* 信息列表 */
.info-list {
  padding-left: 44rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 12rpx 0;

  &.total {
    margin-top: 16rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #f0f0f0;
  }
}

.info-label {
  font-size: 26rpx;
  color: #666;
  flex-shrink: 0;
}

.info-value {
  font-size: 26rpx;
  color: #333;
  text-align: right;
  flex: 1;
  margin-left: 20rpx;

  &.highlight {
    font-size: 32rpx;
    font-weight: 600;
    color: #FF6B35;
  }

  &.success {
    color: #52c41a;
  }

  &.discount {
    color: #ff4d4f;
  }

  &.remark {
    color: #999;
  }
}

.info-value-with-copy {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex: 1;
  margin-left: 20rpx;
}

.copy-icon {
  font-size: 28rpx;
  color: #999;
  margin-left: 16rpx;
  padding: 8rpx;

  &:active {
    color: #FF6B35;
  }
}

/* 预授权状态颜色 */
.preauth-pending {
  color: #faad14 !important;
}

.preauth-authorized {
  color: #1890ff !important;
}

.preauth-captured {
  color: #52c41a !important;
}

.preauth-voided,
.preauth-expired {
  color: #8c8c8c !important;
}

/* 预授权信息和分期信息 */
.preauth-info,
.installment-info {
  margin-top: 20rpx;
  padding-top: 20rpx;
  padding-left: 44rpx;
  border-top: 1rpx solid #f0f0f0;
}

/* 底部操作按钮 */
.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 20rpx;
  padding: 20rpx 24rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-primary,
.btn-secondary {
  flex: 1;
  height: 88rpx;
  font-size: 32rpx;
  font-weight: 500;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;

  &::after {
    border: none;
  }
}

.btn-primary {
  background: #FF6B35 !important;
  color: #fff !important;
  border: none;
}

.btn-secondary {
  background: #fff !important;
  color: #333 !important;
  border: 2rpx solid #ddd !important;
}

/* 物流信息卡片 */
.shipping-card {
  .shipping-content {
    padding-left: 44rpx;
  }
}

.carrier-info {
  display: flex;
  align-items: center;
}

.carrier-logo {
  width: 80rpx;
  height: 80rpx;
  border-radius: 12rpx;
  background: #f5f5f5;
  margin-right: 20rpx;
  flex-shrink: 0;
}

.carrier-detail {
  flex: 1;
}

.carrier-name {
  font-size: 30rpx;
  font-weight: 500;
  color: #333;
  display: block;
  margin-bottom: 8rpx;
}

.tracking-row {
  display: flex;
  align-items: center;
}

.tracking-no {
  font-size: 26rpx;
  color: #666;
  font-family: monospace;
}

.copy-btn {
  font-size: 28rpx;
  color: #999;
  margin-left: 16rpx;
  padding: 8rpx;

  &:active {
    color: #FF6B35;
  }
}

.shipped-time {
  display: flex;
  align-items: center;
  margin-top: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid #f0f0f0;

  .bi {
    font-size: 24rpx;
    color: #999;
    margin-right: 8rpx;
  }

  .time-text {
    font-size: 24rpx;
    color: #999;
  }
}

.track-btn-wrapper {
  margin-top: 24rpx;
}

.track-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 72rpx;
  background: #FF6B35;
  color: #fff;
  font-size: 28rpx;
  font-weight: 500;
  border-radius: 36rpx;
  border: none;

  .bi {
    margin-right: 8rpx;
    font-size: 28rpx;
  }

  &::after {
    border: none;
  }
}
</style>
