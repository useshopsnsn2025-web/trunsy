<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('sellerOrderDetail.title')" />

    <!-- 订单内容 -->
    <view v-if="order" class="order-content">
      <!-- 订单状态卡片 -->
      <view class="status-card" :class="getStatusClass()">
        <view class="status-icon">
          <text :class="getStatusIcon()"></text>
        </view>
        <view class="status-info">
          <text class="status-text">{{ order.status_text }}</text>
          <text class="status-desc">{{ getStatusDesc() }}</text>
        </view>
      </view>

      <!-- 买家信息 -->
      <view v-if="order.address_snapshot" class="section-card">
        <view class="section-header">
          <text class="bi bi-person"></text>
          <text class="section-title">{{ t('sellerOrderDetail.buyerInfo') }}</text>
        </view>
        <view class="buyer-content">
          <view class="buyer-row">
            <text class="buyer-name">{{ order.address_snapshot.recipient_name }}</text>
            <text class="buyer-phone">{{ order.address_snapshot.phone }}</text>
          </view>
          <text class="buyer-address">{{ formatAddress(order.address_snapshot) }}</text>
        </view>
      </view>

      <!-- 物流信息卡片（已发货时显示） -->
      <view v-if="order.shipment && (order.status === 2 || order.status === 3)" class="section-card shipping-card">
        <view class="section-header">
          <text class="bi bi-truck"></text>
          <text class="section-title">{{ t('sellerOrderDetail.shippingInfo') }}</text>
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
            <text class="time-text">{{ t('sellerOrderDetail.shippedAt') }}: {{ order.shipment.shipped_at }}</text>
          </view>
          <!-- 追踪物流按钮 -->
          <view v-if="order.shipment.tracking_url" class="track-btn-wrapper">
            <button class="track-btn" @click="trackPackage">
              <text class="bi bi-box-arrow-up-right"></text>
              <text>{{ t('sellerOrderDetail.trackPackage') }}</text>
            </button>
          </view>
        </view>
      </view>

      <!-- 商品信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-box-seam"></text>
          <text class="section-title">{{ t('sellerOrderDetail.goodsInfo') }}</text>
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

      <!-- 金额明细 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-calculator"></text>
          <text class="section-title">{{ t('sellerOrderDetail.amountInfo') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.goodsAmount') }}</text>
            <text class="info-value">{{ formatPrice(order.goods_amount, order.currency) }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.shippingFee') }}</text>
            <text class="info-value">{{ formatPrice(order.shipping_fee, order.currency) }}</text>
          </view>
          <view v-if="order.discount_amount > 0" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.discount') }}</text>
            <text class="info-value discount">-{{ formatPrice(order.discount_amount, order.currency) }}</text>
          </view>
          <view class="info-row total">
            <text class="info-label">{{ t('sellerOrderDetail.totalAmount') }}</text>
            <text class="info-value highlight">{{ formatPrice(order.total_amount, order.currency) }}</text>
          </view>
        </view>
      </view>

      <!-- 订单信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-file-text"></text>
          <text class="section-title">{{ t('sellerOrderDetail.orderInfo') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.orderNo') }}</text>
            <view class="info-value-with-copy">
              <text class="info-value">{{ order.order_no }}</text>
              <text class="bi bi-copy copy-icon" @click="copyOrderNo"></text>
            </view>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.paymentType') }}</text>
            <text class="info-value">{{ order.payment_type_text }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.createTime') }}</text>
            <text class="info-value">{{ order.created_at }}</text>
          </view>
          <view v-if="order.paid_at" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.paidTime') }}</text>
            <text class="info-value">{{ order.paid_at }}</text>
          </view>
          <view v-if="order.shipped_at" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.shippedTime') }}</text>
            <text class="info-value">{{ order.shipped_at }}</text>
          </view>
          <view v-if="order.received_at" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.receivedTime') }}</text>
            <text class="info-value">{{ order.received_at }}</text>
          </view>
          <view v-if="order.completed_at" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.completedTime') }}</text>
            <text class="info-value">{{ order.completed_at }}</text>
          </view>
          <view v-if="order.buyer_remark" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.buyerRemark') }}</text>
            <text class="info-value remark">{{ order.buyer_remark }}</text>
          </view>
          <view v-if="order.seller_remark" class="info-row">
            <text class="info-label">{{ t('sellerOrderDetail.sellerRemark') }}</text>
            <text class="info-value remark">{{ order.seller_remark }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部操作按钮 -->
    <view v-if="order" class="bottom-actions">
      <!-- 待发货状态显示发货按钮 -->
      <button v-if="order.status === 1" class="btn-primary" @click="openShipModal">
        {{ t('sellerOrderDetail.ship') }}
      </button>
      <!-- 其他状态显示返回列表按钮 -->
      <button v-else class="btn-secondary" @click="goBack">
        {{ t('sellerOrderDetail.backToList') }}
      </button>
    </view>

    <!-- 发货弹窗 -->
    <view v-if="showShipModal" class="modal-mask" @click="closeShipModal">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('sellerOrderDetail.shipTitle') }}</text>
          <text class="bi bi-x modal-close" @click="closeShipModal"></text>
        </view>
        <view class="modal-body">
          <!-- 物流公司 -->
          <view class="form-item">
            <text class="form-label">{{ t('sellerOrderDetail.carrierName') }}</text>
            <!-- 非COD订单且已选择运输商：只读显示 -->
            <view v-if="order && order.payment_type !== PAYMENT_TYPE_COD && order.carrier_id" class="form-input readonly">
              <text>{{ shipForm.carrier_name || '-' }}</text>
            </view>
            <!-- COD订单或未选择运输商：选择运输商 -->
            <picker
              v-else
              mode="selector"
              :range="carriers"
              range-key="name"
              :disabled="carriersLoading"
              @change="onCarrierChange"
            >
              <view class="form-input picker">
                <text v-if="carriersLoading">{{ t('common.loading') }}</text>
                <text v-else-if="shipForm.carrier_name">{{ shipForm.carrier_name }}</text>
                <text v-else class="placeholder">{{ t('sellerOrderDetail.carrierNamePlaceholder') }}</text>
                <text class="bi bi-chevron-down picker-arrow"></text>
              </view>
            </picker>
          </view>
          <!-- 物流单号 -->
          <view class="form-item">
            <text class="form-label">{{ t('sellerOrderDetail.trackingNumber') }}</text>
            <input
              v-model="shipForm.tracking_number"
              class="form-input"
              :placeholder="t('sellerOrderDetail.trackingNumberPlaceholder')"
              :adjust-position="false"
            />
          </view>
        </view>
        <view class="modal-footer">
          <button class="modal-btn cancel" :disabled="shipping" @click="closeShipModal">{{ t('common.cancel') }}</button>
          <button class="modal-btn confirm" :disabled="!canShip || shipping" @click="confirmShip">
            <text v-if="shipping" class="bi bi-arrow-repeat spinning"></text>
            <text>{{ shipping ? t('common.submitting') : t('common.confirm') }}</text>
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import { getSellerOrderDetail, shipSellerOrder } from '@/api/order'
import { getShippingCarriers, type ShippingCarrier } from '@/api/shipping'

// 支付类型常量
const PAYMENT_TYPE_COD = 2

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const orderId = ref<number | null>(null)
const order = ref<any>(null)

// 发货弹窗
const showShipModal = ref(false)
const carriers = ref<ShippingCarrier[]>([])
const carriersLoading = ref(false)
const shipping = ref(false)
const shipForm = ref({
  carrier_id: null as number | null,
  carrier_name: '',
  tracking_number: '',
})

// 是否可以发货
const canShip = computed(() => {
  // 物流单号必填
  if (!shipForm.value.tracking_number.trim()) {
    return false
  }
  // 需要选择运输商的情况：COD订单 或 非COD但买家未选择运输商
  const needSelectCarrier = order.value?.payment_type === PAYMENT_TYPE_COD ||
    (!order.value?.carrier_id && !order.value?.carrier_snapshot?.id)
  if (needSelectCarrier) {
    return !!shipForm.value.carrier_id
  }
  // 买家已选择运输商，无需再选
  return true
})

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
  }
  return iconMap[order.value.status] || 'bi bi-clock'
}

// 获取状态描述
function getStatusDesc() {
  if (!order.value) return ''
  const status = order.value.status
  if (status === 0) return t('sellerOrderDetail.statusDescPending')
  if (status === 1) return t('sellerOrderDetail.statusDescToShip')
  if (status === 2) return t('sellerOrderDetail.statusDescShipped')
  if (status === 3) return t('sellerOrderDetail.statusDescCompleted')
  if (status === 4) return t('sellerOrderDetail.statusDescCancelled')
  return ''
}

// 加载订单信息
async function loadOrderInfo() {
  if (!orderId.value) {
    loading.value = false
    return
  }

  try {
    const res = await getSellerOrderDetail(orderId.value)
    if (res.code === 0) {
      order.value = res.data
    }
  } catch (e) {
    console.error('Failed to load seller order:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 复制订单号
function copyOrderNo() {
  if (!order.value?.order_no) return
  uni.setClipboardData({
    data: order.value.order_no,
    showToast: false,
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
    showToast: false,
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

// 加载运输商列表
async function loadCarriers(countryCode?: string) {
  carriersLoading.value = true
  try {
    const res = await getShippingCarriers(countryCode || '', 0)
    carriers.value = res.data || []
  } catch (e) {
    console.error('Failed to load carriers:', e)
    carriers.value = []
  } finally {
    carriersLoading.value = false
  }
}

// 打开发货弹窗
async function openShipModal() {
  if (!order.value) return

  // 重置表单
  shipForm.value = {
    carrier_id: null,
    carrier_name: '',
    tracking_number: '',
  }

  // 根据订单类型处理运输商
  if (order.value.payment_type === PAYMENT_TYPE_COD) {
    // COD订单：根据收货国家加载可用运输商
    const countryCode = order.value.address_snapshot?.country_code || order.value.address_snapshot?.country || ''
    await loadCarriers(countryCode)
  } else if (order.value.carrier_id || order.value.carrier_snapshot?.id) {
    // 非COD订单且买家已选择快递：使用买家选择的快递（只读）
    shipForm.value.carrier_id = order.value.carrier_id || order.value.carrier_snapshot?.id || null
    shipForm.value.carrier_name = order.value.carrier_snapshot?.name || ''
    carriers.value = []
  } else {
    // 非COD订单但买家未选择快递：加载所有运输商供卖家选择
    await loadCarriers()
  }

  showShipModal.value = true
}

// 关闭发货弹窗
function closeShipModal() {
  showShipModal.value = false
  carriers.value = []
}

// 选择运输商
function onCarrierChange(e: any) {
  const index = Number(e.detail.value)
  if (index >= 0 && index < carriers.value.length) {
    const carrier = carriers.value[index]
    shipForm.value.carrier_id = carrier.id
    shipForm.value.carrier_name = carrier.name
  }
}

// 确认发货
async function confirmShip() {
  if (!order.value || !canShip.value || shipping.value) return

  shipping.value = true
  try {
    await shipSellerOrder(order.value.id, {
      tracking_number: shipForm.value.tracking_number,
      carrier_id: shipForm.value.carrier_id || undefined,
      carrier_name: shipForm.value.carrier_name,
    })

    toast.success(t('sellerOrderDetail.shipSuccess'))
    closeShipModal()
    // 重新加载订单信息以更新页面状态
    await loadOrderInfo()
  } catch (e) {
    console.error('Failed to ship order:', e)
  } finally {
    shipping.value = false
  }
}

// 返回列表
function goBack() {
  uni.navigateBack()
}

onLoad((options) => {
  if (options?.id) {
    orderId.value = parseInt(options.id)
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('sellerOrderDetail.title') })
  if (orderId.value) {
    loadOrderInfo()
  } else {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
// 设计变量
$color-primary: #FF6B35;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #52c41a;

.page {
  min-height: 100vh;
  background-color: $color-background;
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
  background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
  border-radius: 16rpx;
  margin-bottom: 24rpx;

  &.status-completed {
    background: linear-gradient(135deg, $color-success 0%, #73d13d 100%);
  }

  &.status-cancelled {
    background: linear-gradient(135deg, #8c8c8c 0%, #bfbfbf 100%);
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
  background: $color-surface;
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
    color: $color-primary;
    margin-right: 12rpx;
  }
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
}

/* 买家信息 */
.buyer-content {
  padding-left: 44rpx;
}

.buyer-row {
  display: flex;
  align-items: center;
  margin-bottom: 12rpx;
}

.buyer-name {
  font-size: 30rpx;
  font-weight: 500;
  color: $color-text-primary;
  margin-right: 20rpx;
}

.buyer-phone {
  font-size: 28rpx;
  color: $color-text-secondary;
}

.buyer-address {
  font-size: 26rpx;
  color: $color-text-secondary;
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
  background: $color-background;
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
  color: $color-text-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-specs {
  font-size: 24rpx;
  color: $color-text-muted;
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
  color: $color-primary;
}

.goods-quantity {
  font-size: 26rpx;
  color: $color-text-muted;
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
  color: $color-text-secondary;
  flex-shrink: 0;
}

.info-value {
  font-size: 26rpx;
  color: $color-text-primary;
  text-align: right;
  flex: 1;
  margin-left: 20rpx;

  &.highlight {
    font-size: 32rpx;
    font-weight: 600;
    color: $color-primary;
  }

  &.discount {
    color: #ff4d4f;
  }

  &.remark {
    color: $color-text-muted;
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
  color: $color-text-muted;
  margin-left: 16rpx;
  padding: 8rpx;
  cursor: pointer;

  &:active {
    color: $color-primary;
  }
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
  background: $color-surface;
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
  background: $color-primary !important;
  color: #fff !important;
  border: none;
}

.btn-secondary {
  background: $color-surface !important;
  color: $color-text-primary !important;
  border: 2rpx solid $color-border !important;
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
  background: $color-background;
  margin-right: 20rpx;
  flex-shrink: 0;
}

.carrier-detail {
  flex: 1;
}

.carrier-name {
  font-size: 30rpx;
  font-weight: 500;
  color: $color-text-primary;
  display: block;
  margin-bottom: 8rpx;
}

.tracking-row {
  display: flex;
  align-items: center;
}

.tracking-no {
  font-size: 26rpx;
  color: $color-text-secondary;
  font-family: monospace;
}

.copy-btn {
  font-size: 28rpx;
  color: $color-text-muted;
  margin-left: 16rpx;
  padding: 8rpx;
  cursor: pointer;

  &:active {
    color: $color-primary;
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
    color: $color-text-muted;
    margin-right: 8rpx;
  }

  .time-text {
    font-size: 24rpx;
    color: $color-text-muted;
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
  background: $color-primary;
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

/* 发货弹窗 */
.modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-content {
  width: 80%;
  max-width: 400px;
  background-color: $color-surface;
  border-radius: 16rpx;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx;
  border-bottom: 1rpx solid $color-border;
}

.modal-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $color-text-primary;
}

.modal-close {
  font-size: 48rpx;
  color: $color-text-muted;
  cursor: pointer;
}

.modal-body {
  padding: 32rpx;
}

.form-item {
  margin-bottom: 32rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  font-size: 26rpx;
  color: $color-text-secondary;
  margin-bottom: 16rpx;
}

.form-input {
  width: 100%;
  height: 88rpx;
  padding: 0 24rpx;
  font-size: 28rpx;
  border: 1rpx solid $color-border;
  border-radius: 12rpx;
  background-color: $color-background;
  box-sizing: border-box;

  &::placeholder {
    color: $color-text-muted;
  }

  &.readonly {
    display: flex;
    align-items: center;
    background-color: darken($color-background, 3%);
    color: $color-text-secondary;
  }

  &.picker {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .placeholder {
      color: $color-text-muted;
    }

    .picker-arrow {
      font-size: 24rpx;
      color: $color-text-muted;
    }
  }
}

.modal-footer {
  display: flex;
  gap: 24rpx;
  padding: 32rpx;
  border-top: 1rpx solid $color-border;
}

.modal-btn {
  flex: 1;
  height: 88rpx;
  font-size: 30rpx;
  border-radius: 12rpx;
  border: none;

  &::after {
    display: none;
  }

  &.cancel {
    background-color: $color-background;
    color: $color-text-secondary;
  }

  &.confirm {
    background-color: $color-primary;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;

    &[disabled] {
      opacity: 0.5;
    }

    .spinning {
      animation: spin 1s linear infinite;
    }
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
