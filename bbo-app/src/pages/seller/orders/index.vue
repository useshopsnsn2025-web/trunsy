<template>
  <view class="page">
    <!-- 页面初始加载 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部白色背景层 -->
    <view class="header-bg" :style="{ height: (navBarHeight + 46) + 'px' }"></view>

    <!-- 顶部导航栏 -->
    <NavBar :title="t('sellingOverview.title')" />

    <!-- 状态Tab（吸顶） -->
    <view class="status-tabs" :style="{ top: navBarHeight + 'px' }">
      <scroll-view scroll-x class="tabs-scroll">
        <view class="tabs-wrapper">
          <view
            v-for="tab in statusTabs"
            :key="tab.value"
            class="tab-item"
            :class="{ active: currentStatus === tab.value }"
            @click="switchTab(tab.value)"
          >
            <text class="tab-label">{{ tab.label }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- Tab 占位 -->
    <view class="tabs-placeholder"></view>

    <!-- 切换分类加载中 -->
    <view v-if="switching" class="switching-loading">
      <text class="bi bi-arrow-repeat spinning"></text>
    </view>

    <!-- 订单列表 -->
    <scroll-view
      v-else
      class="order-list"
      scroll-y
      :style="{ height: scrollHeight + 'px' }"
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      :lower-threshold="100"
      :enhanced="true"
      :show-scrollbar="false"
      :bounces="true"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 订单卡片 -->
      <view v-for="order in orders" :key="order.id" class="order-card" @click="goDetail(order)">
        <!-- 订单头部 -->
        <view class="order-header">
          <text class="order-no">{{ t('sellingOverview.orderNo') }}: {{ order.order_no }}</text>
          <view class="order-header-right">
            <!-- 退货标签 -->
            <view v-if="order.return_request" class="return-tag" :class="getReturnTagClass(order.return_request.status)">
              <text class="bi bi-arrow-return-left"></text>
              <text>{{ getReturnStatusText(order.return_request.status) }}</text>
            </view>
            <text class="order-status" :class="getStatusClass(order.status)">{{ order.status_text }}</text>
          </view>
        </view>

        <!-- 商品信息 -->
        <view class="order-goods">
          <image :src="order.goods?.cover_image" class="goods-image" mode="aspectFill" lazy-load />
          <view class="goods-info">
            <text class="goods-title">{{ order.goods?.title }}</text>
            <view class="goods-meta">
              <text class="goods-price">{{ formatPrice(order.goods?.price, order.currency) }}</text>
              <text class="goods-quantity">x{{ order.quantity || 1 }}</text>
            </view>
            <view class="order-total">
              <text class="total-label">{{ t('sellingOverview.total') }}: </text>
              <text class="total-amount">{{ formatPrice(order.total_amount, order.currency) }}</text>
            </view>
          </view>
        </view>

        <!-- 买家信息 -->
        <view class="buyer-info">
          <text class="buyer-label">{{ t('sellingOverview.buyer') }}: </text>
          <text class="buyer-name">{{ order.buyer?.name || '-' }}</text>
        </view>

        <!-- 订单底部 -->
        <view class="order-footer">
          <text class="order-time">{{ formatTime(order.created_at) }}</text>
          <view class="order-actions">
            <!-- 有退货申请 - 显示处理退货按钮 -->
            <button v-if="order.return_request" class="action-btn warning" @click.stop="goReturnDetail(order.return_request.id)">
              {{ t('sellingOverview.handleReturn') }}
            </button>
            <!-- 待发货 - 发货按钮 -->
            <button v-if="order.status === 1 && !order.return_request" class="action-btn primary" @click.stop="openShipModal(order)">
              {{ t('sellingOverview.ship') }}
            </button>
            <!-- 待收货 - 查看物流 -->
            <button v-if="order.status === 2 && !order.return_request" class="action-btn" @click.stop="goDetail(order)">
              {{ t('sellingOverview.viewDetail') }}
            </button>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && orders.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('sellingOverview.noOrders') }}</text>
      </view>

      <!-- 加载更多 -->
      <view v-if="orders.length > 0" class="load-more">
        <text v-if="loadingMore" class="load-text">{{ t('common.loading') }}</text>
        <text v-else-if="noMore" class="load-text">{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 发货弹窗 -->
    <view v-if="showShipModal" class="modal-mask" @click="closeShipModal">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('sellingOverview.shipTitle') }}</text>
          <text class="bi bi-x modal-close" @click="closeShipModal"></text>
        </view>
        <view class="modal-body">
          <!-- 物流公司 -->
          <view class="form-item">
            <text class="form-label">{{ t('sellingOverview.carrierName') }}</text>
            <!-- 非COD订单：显示买家选择的运输商（只读） -->
            <view v-if="currentOrder && currentOrder.payment_type !== PAYMENT_TYPE_COD && currentOrder.carrier_id" class="form-input readonly">
              <text>{{ shipForm.carrier_name || '-' }}</text>
            </view>
            <!-- COD订单：选择运输商 -->
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
                <text v-else class="placeholder">{{ t('sellingOverview.carrierNamePlaceholder') }}</text>
                <text class="bi bi-chevron-down picker-arrow"></text>
              </view>
            </picker>
          </view>
          <!-- 物流单号 -->
          <view class="form-item">
            <text class="form-label">{{ t('sellingOverview.trackingNumber') }}</text>
            <input
              v-model="shipForm.tracking_number"
              class="form-input"
              :placeholder="t('sellingOverview.trackingNumberPlaceholder')"
              :adjust-position="false"
            />
          </view>
        </view>
        <view class="modal-footer">
          <button class="modal-btn cancel" @click="closeShipModal">{{ t('common.cancel') }}</button>
          <button class="modal-btn confirm" :disabled="!canShip" @click="confirmShip">{{ t('common.confirm') }}</button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow, onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import { getSellerOrders, shipSellerOrder, type SellerOrder } from '@/api/order'
import { getShippingCarriers, type ShippingCarrier } from '@/api/shipping'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

// 支付类型常量
const PAYMENT_TYPE_COD = 2

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 导航栏高度（状态栏 + 48px）
const statusBarHeight = ref(20)
const windowHeight = ref(667)
const NAV_BAR_HEIGHT = 48
const TAB_HEIGHT = 46 // Tab 栏高度
const navBarHeight = computed(() => statusBarHeight.value + NAV_BAR_HEIGHT)
// scroll-view 高度 = 窗口高度 - 导航栏高度 - Tab栏高度
const scrollHeight = computed(() => windowHeight.value - navBarHeight.value - TAB_HEIGHT)

// 状态
const loading = ref(true)
const switching = ref(false)
const refreshing = ref(false)
const loadingMore = ref(false)
const noMore = ref(false)

// 数据
const orders = ref<SellerOrder[]>([])
const currentStatus = ref<string>('all')
const page = ref(1)
const pageSize = 10

// 发货弹窗
const showShipModal = ref(false)
const currentOrder = ref<SellerOrder | null>(null)
const carriers = ref<ShippingCarrier[]>([])
const carriersLoading = ref(false)
const shipForm = ref({
  carrier_id: null as number | null,
  carrier_name: '',
  tracking_number: '',
})

// 状态 Tab 配置
const statusTabs = computed(() => [
  { value: 'all', label: t('sellingOverview.statusAll') },
  { value: 'pending', label: t('sellingOverview.statusPending') },
  { value: 'toShip', label: t('sellingOverview.statusToShip') },
  { value: 'shipped', label: t('sellingOverview.statusShipped') },
  { value: 'completed', label: t('sellingOverview.statusCompleted') },
])

// 是否可以发货
const canShip = computed(() => {
  // 物流单号必填
  if (!shipForm.value.tracking_number.trim()) {
    return false
  }
  // 需要选择运输商的情况：COD订单 或 非COD但买家未选择运输商
  const needSelectCarrier = currentOrder.value?.payment_type === PAYMENT_TYPE_COD ||
    (!currentOrder.value?.carrier_id && !currentOrder.value?.carrier_snapshot?.id)
  if (needSelectCarrier) {
    return !!shipForm.value.carrier_id
  }
  // 买家已选择运输商，无需再选
  return true
})

// 状态映射
function getStatusParam(status: string): number | undefined {
  const statusMap: Record<string, number | undefined> = {
    all: undefined,
    pending: 0,      // 待付款
    toShip: 1,       // 待发货
    shipped: 2,      // 待收货（已发货）
    completed: 3,    // 已完成
  }
  return statusMap[status]
}

// 格式化价格
function formatPrice(amount: number, currency: string) {
  return appStore.formatPrice(amount, currency)
}

// 格式化时间
function formatTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

// 获取状态样式类
function getStatusClass(status: number) {
  const classMap: Record<number, string> = {
    0: 'status-pending',
    1: 'status-toship',
    2: 'status-shipped',
    3: 'status-completed',
    4: 'status-cancelled',
  }
  return classMap[status] || ''
}

// 退货状态常量
const RETURN_STATUS = {
  PENDING: 0,
  APPROVED: 1,
  REJECTED: 2,
  CANCELLED: 3,
  IN_RETURN: 4,
  COMPLETED: 5,
}

// 获取退货标签样式类
function getReturnTagClass(status: number) {
  const classMap: Record<number, string> = {
    [RETURN_STATUS.PENDING]: 'return-tag-pending',
    [RETURN_STATUS.APPROVED]: 'return-tag-approved',
    [RETURN_STATUS.IN_RETURN]: 'return-tag-inreturn',
  }
  return classMap[status] || ''
}

// 获取退货状态文本
function getReturnStatusText(status: number) {
  const textMap: Record<number, string> = {
    [RETURN_STATUS.PENDING]: t('sellerReturn.statusPending'),
    [RETURN_STATUS.APPROVED]: t('sellerReturn.statusApproved'),
    [RETURN_STATUS.IN_RETURN]: t('sellerReturn.statusInReturn'),
  }
  return textMap[status] || ''
}

// 跳转退货详情
function goReturnDetail(returnId: number) {
  uni.navigateTo({ url: `/pages/seller/returns/detail?id=${returnId}` })
}

// 加载订单列表
async function loadOrders(reset = false) {
  if (reset) {
    page.value = 1
    noMore.value = false
  }

  try {
    const statusParam = getStatusParam(currentStatus.value)
    const res = await getSellerOrders({
      page: page.value,
      pageSize,
      status: statusParam,
    })

    const list = res.data?.list || []

    if (reset) {
      orders.value = list
    } else {
      orders.value = [...orders.value, ...list]
    }

    if (list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Failed to load seller orders:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
    switching.value = false
    refreshing.value = false
    loadingMore.value = false
  }
}

// 切换 Tab
function switchTab(status: string) {
  if (currentStatus.value === status) return
  currentStatus.value = status
  switching.value = true
  loadOrders(true)
}

// 下拉刷新
function onRefresh() {
  refreshing.value = true
  loadOrders(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || noMore.value) return
  loadingMore.value = true
  page.value++
  loadOrders()
}

// 跳转订单详情
function goDetail(order: SellerOrder) {
  uni.navigateTo({ url: `/pages/seller/orders/detail?id=${order.id}` })
}

// 跳转退货管理
function goReturns() {
  uni.navigateTo({ url: '/pages/seller/returns/index' })
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
async function openShipModal(order: SellerOrder) {
  currentOrder.value = order
  // 重置表单
  shipForm.value = {
    carrier_id: null,
    carrier_name: '',
    tracking_number: '',
  }

  // 根据订单类型处理运输商
  if (order.payment_type === PAYMENT_TYPE_COD) {
    // COD订单：根据收货国家加载可用运输商，允许卖家选择
    const countryCode = order.address_snapshot?.country_code || order.address_snapshot?.country || ''
    await loadCarriers(countryCode)
  } else if (order.carrier_id || order.carrier_snapshot?.id) {
    // 非COD订单且买家已选择快递：使用买家选择的快递（只读）
    shipForm.value.carrier_id = order.carrier_id || order.carrier_snapshot?.id || null
    shipForm.value.carrier_name = order.carrier_snapshot?.name || ''
    // 不需要加载运输商列表
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
  currentOrder.value = null
  carriers.value = []
}

// 选择运输商（picker）
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
  if (!currentOrder.value || !canShip.value) return

  try {
    await shipSellerOrder(currentOrder.value.id, {
      tracking_number: shipForm.value.tracking_number,
      carrier_id: shipForm.value.carrier_id || undefined,
      carrier_name: shipForm.value.carrier_name,
    })

    toast.success(t('sellingOverview.shipSuccess'))
    closeShipModal()
    loadOrders(true)
  } catch (e) {
    console.error('Failed to ship order:', e)
  }
}

// 获取系统信息
onMounted(() => {
  uni.getSystemInfo({
    success: (res) => {
      statusBarHeight.value = res.statusBarHeight || 20
      windowHeight.value = res.windowHeight || 667
    },
  })
})

// 页面加载
onLoad((options: any) => {
  if (options?.status) {
    currentStatus.value = options.status
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('sellingOverview.title') })
  loadOrders(true)
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
$color-success: #228B22;
$color-warning: #F5A623;
$color-danger: #E74C3C;

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;

$radius-sm: 8px;
$radius-md: 12px;

.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: $color-background;
}

// 顶部白色背景层（覆盖导航栏到 Tab 区域）
.header-bg {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: $color-surface;
  z-index: 1;
}

// 状态 Tab（吸顶）
.status-tabs {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 99;
  background: $color-surface;
  padding: 0 24rpx;
  border-bottom: 1rpx solid $color-border;
  display: flex;
  align-items: center;
}

// Tab 占位（与 status-tabs 实际高度一致：padding 40rpx + tab高度 约50rpx + border 1rpx）
.tabs-placeholder {
  height: 92rpx;
}

.tabs-scroll {
  flex: 1;
  white-space: nowrap;
  min-width: 0;

  // 隐藏滚动条
  ::-webkit-scrollbar {
    display: none;
  }
}

.tabs-wrapper {
  display: inline-flex;
  padding: 20rpx 0;
  gap: 24rpx;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  padding: 12rpx 24rpx;
  border-radius: 32rpx;
  background: $color-background;
  font-size: 26rpx;
  color: $color-text-secondary;
  transition: all 0.2s;

  &.active {
    background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
    color: #fff;
  }

  &:active {
    opacity: 0.8;
  }
}

.tab-label {
  white-space: nowrap;
}

// 切换分类加载中
.switching-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 80rpx 0;

  .bi {
    font-size: 48rpx;
    color: $color-primary;
  }

  .spinning {
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

// 订单列表
.order-list {
  flex: 1;
  padding: $spacing-md;
  width: auto;

  // 隐藏滚动条
  ::-webkit-scrollbar {
    display: none;
  }
}

// 订单卡片
.order-card {
  background-color: $color-surface;
  border-radius: $radius-md;
  margin-bottom: $spacing-md;
  overflow: hidden;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-base;
  border-bottom: 1px solid $color-border;
}

.order-header-right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.order-no {
  font-size: 12px;
  color: $color-text-muted;
}

// 退货标签
.return-tag {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  padding: 4rpx 12rpx;
  border-radius: 4rpx;
  font-size: 22rpx;
  font-weight: 500;

  .bi {
    font-size: 20rpx;
  }

  &.return-tag-pending {
    background: rgba($color-warning, 0.12);
    color: $color-warning;
  }

  &.return-tag-approved {
    background: rgba(#3498db, 0.12);
    color: #3498db;
  }

  &.return-tag-inreturn {
    background: rgba($color-primary, 0.12);
    color: $color-primary;
  }
}

.order-status {
  font-size: 13px;
  font-weight: 500;

  &.status-pending {
    color: $color-warning;
  }

  &.status-toship {
    color: $color-primary;
  }

  &.status-shipped {
    color: #3498db;
  }

  &.status-completed {
    color: $color-success;
  }

  &.status-cancelled {
    color: $color-text-muted;
  }
}

.order-goods {
  display: flex;
  padding: $spacing-base;
  gap: $spacing-md;
}

.goods-image {
  width: 80px;
  height: 80px;
  border-radius: $radius-sm;
  background-color: $color-background;
  flex-shrink: 0;
}

.goods-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
}

.goods-title {
  font-size: 14px;
  color: $color-text-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.goods-price {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
}

.goods-quantity {
  font-size: 13px;
  color: $color-text-muted;
}

.order-total {
  display: flex;
  align-items: center;
  margin-top: $spacing-xs;
}

.total-label {
  font-size: 12px;
  color: $color-text-muted;
}

.total-amount {
  font-size: 14px;
  font-weight: 600;
  color: $color-text-primary;
}

// 买家信息
.buyer-info {
  display: flex;
  align-items: center;
  padding: $spacing-sm $spacing-base;
  background-color: $color-background;
  margin: 0 $spacing-base;
  border-radius: $radius-sm;
}

.buyer-label {
  font-size: 12px;
  color: $color-text-muted;
}

.buyer-name {
  font-size: 12px;
  color: $color-text-primary;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-base;
  border-top: 1px solid $color-border;
}

.order-time {
  font-size: 12px;
  color: $color-text-muted;
}

.order-actions {
  display: flex;
  gap: $spacing-sm;
}

.action-btn {
  margin: 0;
  padding: $spacing-sm $spacing-md;
  font-size: 13px;
  line-height: 1.5;
  border-radius: $radius-sm;
  background-color: $color-surface;
  border: 1px solid $color-border;
  color: $color-text-secondary;

  &::after {
    display: none;
  }

  &.primary {
    background-color: $color-primary;
    border-color: $color-primary;
    color: #fff;
  }

  &.warning {
    background-color: $color-warning;
    border-color: $color-warning;
    color: #fff;
  }
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px $spacing-base;
}

.empty-icon {
  font-size: 60px;
  color: $color-text-muted;
  margin-bottom: $spacing-lg;
}

.empty-text {
  font-size: 15px;
  color: $color-text-muted;
}

// 加载更多
.load-more {
  text-align: center;
  padding: $spacing-base;
}

.load-text {
  font-size: 13px;
  color: $color-text-muted;
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// 发货弹窗
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
  border-radius: $radius-md;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base;
  border-bottom: 1px solid $color-border;
}

.modal-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text-primary;
}

.modal-close {
  font-size: 24px;
  color: $color-text-muted;
  cursor: pointer;
}

.modal-body {
  padding: $spacing-base;
}

.form-item {
  margin-bottom: $spacing-base;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  font-size: 13px;
  color: $color-text-secondary;
  margin-bottom: $spacing-sm;
}

.form-input {
  width: 100%;
  height: 44px;
  padding: 0 $spacing-md;
  font-size: 14px;
  border: 1px solid $color-border;
  border-radius: $radius-sm;
  background-color: $color-background;
  box-sizing: border-box;

  &::placeholder {
    color: $color-text-muted;
  }

  // 只读状态
  &.readonly {
    display: flex;
    align-items: center;
    background-color: darken($color-background, 3%);
    color: $color-text-secondary;
  }

  // 选择器样式
  &.picker {
    display: flex;
    align-items: center;
    justify-content: space-between;

    .placeholder {
      color: $color-text-muted;
    }

    .picker-arrow {
      font-size: 12px;
      color: $color-text-muted;
    }
  }
}

.modal-footer {
  display: flex;
  gap: $spacing-md;
  padding: $spacing-base;
  border-top: 1px solid $color-border;
}

.modal-btn {
  flex: 1;
  height: 44px;
  font-size: 15px;
  border-radius: $radius-sm;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;

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

    &[disabled] {
      opacity: 0.5;
    }
  }
}
</style>
