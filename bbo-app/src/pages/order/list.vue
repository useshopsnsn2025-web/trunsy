<template>
  <view class="page">
    <!-- 页面初始加载 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('orderList.title')" />

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
            <view v-if="tab.count > 0" class="tab-badge">{{ tab.count > 99 ? '99+' : tab.count }}</view>
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
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 订单卡片 -->
      <view v-for="order in orders" :key="order.id" class="order-card" @click="goDetail(order)">
        <!-- 订单头部 -->
        <view class="order-header">
          <text class="order-no">{{ t('orderList.orderNo') }}: {{ order.order_no }}</text>
          <text class="order-status" :class="getStatusClass(order.status)">{{ order.status_text }}</text>
        </view>

        <!-- 商品信息 -->
        <view class="order-goods">
          <image :src="order.goods?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <text class="goods-title">{{ order.goods?.title }}</text>
            <view class="goods-meta">
              <text class="goods-price">{{ formatPrice(order.goods?.price, order.currency) }}</text>
              <text class="goods-quantity">x{{ order.quantity || 1 }}</text>
            </view>
            <view class="order-total">
              <text class="total-label">{{ t('orderList.total') }}: </text>
              <text class="total-amount">{{ formatPrice(order.total_amount, order.currency) }}</text>
            </view>
          </view>
        </view>

        <!-- 订单底部 -->
        <view class="order-footer">
          <text class="order-time">{{ formatTime(order.created_at) }}</text>
          <view class="order-actions">
            <!-- 待付款 - 去支付 -->
            <button v-if="order.status === 0" class="action-btn primary" @click.stop="goPay(order)">
              {{ t('orderList.payNow') }}
            </button>
            <!-- 待付款 - 取消订单 -->
            <button v-if="order.status === 0" class="action-btn" @click.stop="cancelOrder(order)">
              {{ t('orderList.cancel') }}
            </button>
            <!-- 待收货 - 确认收货 -->
            <button v-if="order.status === 2" class="action-btn primary" @click.stop="confirmReceipt(order)">
              {{ t('orderList.confirmReceipt') }}
            </button>
            <!-- 待收货 - 查看物流 -->
            <button v-if="order.status === 2 && order.shipment?.tracking_url" class="action-btn" @click.stop="trackPackage(order)">
              {{ t('orderList.trackPackage') }}
            </button>
            <!-- 已完成 - 评价 -->
            <button v-if="order.status === 3 && !order.review" class="action-btn primary" @click.stop="goReview(order)">
              {{ t('orderList.writeReview') }}
            </button>
            <!-- 已完成 - 申请退货 -->
            <button v-if="order.status === 3" class="action-btn" @click.stop="requestReturn(order)">
              {{ t('orderList.requestReturn') }}
            </button>
            <!-- 已完成 - 再次购买 -->
            <button v-if="order.status === 3" class="action-btn" @click.stop="buyAgain(order)">
              {{ t('orderList.buyAgain') }}
            </button>
            <!-- 退款中/已退款 - 查看退货 -->
            <button v-if="order.status === 5 || order.status === 6" class="action-btn" @click.stop="viewReturn(order)">
              {{ t('orderList.viewReturn') }}
            </button>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && orders.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('orderList.noOrders') }}</text>
        <button class="empty-btn" @click="goShopping">{{ t('orderList.goShopping') }}</button>
      </view>

      <!-- 加载更多 -->
      <view v-if="orders.length > 0" class="load-more">
        <text v-if="loadingMore" class="load-text">{{ t('common.loading') }}</text>
        <text v-else-if="noMore" class="load-text">{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 取消订单弹窗 -->
    <ConfirmDialog
      :visible="showCancelOrderDialog"
      :title="t('orderList.cancelTitle')"
      :content="t('orderList.cancelConfirm')"
      icon="bi-x-circle"
      icon-type="warning"
      @update:visible="showCancelOrderDialog = $event"
      @confirm="handleCancelOrder"
    />

    <!-- 确认收货弹窗 -->
    <ConfirmDialog
      :visible="showConfirmReceiptDialog"
      :title="t('orderList.confirmReceiptTitle')"
      :content="t('orderList.confirmReceiptContent')"
      icon="bi-check-circle"
      icon-type="warning"
      @update:visible="showConfirmReceiptDialog = $event"
      @confirm="handleConfirmReceipt"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow, onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import { getOrders, confirmOrder, cancelOrder as cancelOrderApi } from '@/api/order'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 系统信息
const statusBarHeight = ref(0) // H5 端为 0，APP 端由 getSystemInfo 动态获取
const NAV_BAR_HEIGHT = 48

// 导航栏高度（用于 Tab 吸顶定位）
const navBarHeight = computed(() => statusBarHeight.value + NAV_BAR_HEIGHT)

// 状态
const loading = ref(true)
const switching = ref(false) // 切换分类加载
const refreshing = ref(false)
const loadingMore = ref(false)
const noMore = ref(false)

// 数据
const orders = ref<any[]>([])
const currentStatus = ref<string>('all')
const page = ref(1)
const pageSize = 10

// 弹窗状态
const showCancelOrderDialog = ref(false)
const showConfirmReceiptDialog = ref(false)
const targetOrder = ref<any>(null)

// 状态 Tab 配置
const statusTabs = computed(() => [
  { value: 'all', label: t('orderList.statusAll'), count: 0 },
  { value: 'pending', label: t('orderList.statusPending'), count: 0 },
  { value: 'shipping', label: t('orderList.statusShipping'), count: 0 },
  { value: 'completed', label: t('orderList.statusCompleted'), count: 0 },
  { value: 'refund', label: t('orderList.statusRefund'), count: 0 },
])

// 状态映射
function getStatusParam(status: string): number | undefined {
  const statusMap: Record<string, number | undefined> = {
    all: undefined,
    pending: 0,      // 待付款
    shipping: 2,     // 待收货
    completed: 3,    // 已完成
    refund: 5,       // 退款中
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
    1: 'status-pending',
    2: 'status-shipping',
    3: 'status-completed',
    4: 'status-cancelled',
    5: 'status-refund',
    6: 'status-refund',
    7: 'status-cancelled',
  }
  return classMap[status] || ''
}

// 加载订单列表
async function loadOrders(reset = false) {
  if (reset) {
    page.value = 1
    noMore.value = false
  }

  try {
    const statusParam = getStatusParam(currentStatus.value)
    const res = await getOrders({
      page: page.value,
      pageSize,
      status: statusParam,
      role: 'buyer',
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
    console.error('Failed to load orders:', e)
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
function goDetail(order: any) {
  uni.navigateTo({ url: `/pages/order/detail?id=${order.id}` })
}

// 去支付
function goPay(order: any) {
  uni.navigateTo({ url: `/pages/order/detail?id=${order.id}` })
}

// 取消订单
function cancelOrder(order: any) {
  targetOrder.value = order
  showCancelOrderDialog.value = true
}

// 取消订单回调
async function handleCancelOrder() {
  const order = targetOrder.value
  if (!order) return
  try {
    await cancelOrderApi(order.id)
    toast.success(t('orderList.cancelSuccess'))
    loadOrders(true)
  } catch (e) {
    console.error('Failed to cancel order:', e)
  }
}

// 确认收货
function confirmReceipt(order: any) {
  targetOrder.value = order
  showConfirmReceiptDialog.value = true
}

// 确认收货回调
async function handleConfirmReceipt() {
  const order = targetOrder.value
  if (!order) return
  try {
    await confirmOrder(order.id)
    toast.success(t('orderList.confirmSuccess'))
    loadOrders(true)
  } catch (e) {
    console.error('Failed to confirm order:', e)
  }
}

// 查看物流
function trackPackage(order: any) {
  if (order.shipment?.tracking_url) {
    // #ifdef H5
    window.open(order.shipment.tracking_url, '_blank')
    // #endif
    // #ifndef H5
    uni.navigateTo({
      url: `/pages/webview/index?url=${encodeURIComponent(order.shipment.tracking_url)}`,
    })
    // #endif
  }
}

// 去评价
function goReview(order: any) {
  toast.info(t('common.comingSoon'))
}

// 再次购买
function buyAgain(order: any) {
  const goodsId = order.goods?.id
  if (goodsId) {
    uni.navigateTo({ url: `/pages/goods/detail?id=${goodsId}` })
  }
}

// 申请退货
function requestReturn(order: any) {
  uni.navigateTo({
    url: `/pages/order/return-request?orderId=${order.id}`
  })
}

// 查看退货（通过订单ID跳转）
function viewReturn(order: any) {
  // 跳转到退货详情页，使用订单ID查找
  uni.navigateTo({
    url: `/pages/order/return-detail?orderId=${order.id}`
  })
}

// 去购物
function goShopping() {
  uni.switchTab({ url: '/pages/index/index' })
}

// 页面加载
onLoad((options: any) => {
  if (options?.status) {
    currentStatus.value = options.status
  }
})

onMounted(() => {
  // 获取系统信息
  uni.getSystemInfo({
    success: (res) => {
      // H5 端没有状态栏，statusBarHeight 为 0 是正确的
      statusBarHeight.value = res.statusBarHeight || 0
    },
  })
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('orderList.title') })
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
  background-color: $color-background;
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
}

// Tab 占位
.tabs-placeholder {
  height: 92rpx;
}

.tabs-scroll {
  white-space: nowrap;

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
  position: relative;

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

.tab-badge {
  position: absolute;
  top: -8rpx;
  right: -8rpx;
  min-width: 32rpx;
  height: 32rpx;
  line-height: 32rpx;
  padding: 0 8rpx;
  font-size: 20rpx;
  color: #fff;
  background: $color-danger;
  border-radius: 16rpx;
  text-align: center;
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
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

// 订单列表
.order-list {
  flex: 1;
  padding: $spacing-md;
  width: auto;

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

.order-no {
  font-size: 12px;
  color: $color-text-muted;
}

.order-status {
  font-size: 13px;
  font-weight: 500;

  &.status-pending {
    color: $color-warning;
  }

  &.status-shipping {
    color: $color-primary;
  }

  &.status-completed {
    color: $color-success;
  }

  &.status-cancelled,
  &.status-refund {
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
  // 重置 uni-button 默认样式
  margin: 0;
  padding: $spacing-sm $spacing-md;
  font-size: 13px;
  line-height: 1.5;
  border-radius: $radius-sm;
  background-color: $color-surface;
  border: 1px solid $color-border;
  color: $color-text-secondary;

  &::after {
    display: none; // 移除 uni-button 默认的边框伪元素
  }

  &.primary {
    background-color: $color-primary;
    border-color: $color-primary;
    color: #fff;
  }

  &[disabled],
  &.disabled {
    opacity: 0.5;
    pointer-events: none;
    background-color: $color-background;
    border-color: $color-border;
    color: $color-text-muted;
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
  margin-bottom: $spacing-lg;
}

.empty-btn {
  padding: $spacing-md $spacing-lg;
  background-color: $color-primary;
  color: #fff;
  font-size: 14px;
  border-radius: $radius-sm;
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
</style>
