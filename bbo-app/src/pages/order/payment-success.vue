<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="getPageTitle()" title-align="center" :show-back="false">
      <template #left>
        <view class="nav-action" @click="goHome">
          <text class="bi bi-house"></text>
        </view>
      </template>
    </NavBar>

    <!-- 成功内容 -->
    <view v-if="orderInfo" class="success-content">
      <!-- 成功图标和标题 -->
      <view class="success-header">
        <view class="success-icon">
          <text class="bi bi-check-lg"></text>
        </view>
        <text class="success-title">{{ getSuccessTitle() }}</text>
        <text class="success-subtitle">{{ getSuccessSubtitle() }}</text>
        <text class="order-no">{{ t('paymentSuccess.orderNo') }}: {{ orderInfo.order_no }}</text>
      </view>

      <!-- 订单摘要卡片 -->
      <view class="summary-card">
        <!-- 商品信息 -->
        <view class="goods-info">
          <image :src="orderInfo.goods_snapshot?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-detail">
            <text class="goods-title">{{ orderInfo.goods_snapshot?.title }}</text>
            <text v-if="orderInfo.goods_snapshot?.specs" class="goods-specs">
              {{ formatSpecs(orderInfo.goods_snapshot.specs) }}
            </text>
          </view>
        </view>

        <!-- 金额信息 - 根据支付类型显示 -->
        <view class="amount-section">
          <!-- 全款支付 -->
          <template v-if="orderInfo.payment_type === 1">
            <view class="amount-row">
              <text class="amount-label">{{ t('paymentSuccess.paidAmount') }}</text>
              <text class="amount-value success">{{ formatPrice(orderInfo.paid_amount, orderInfo.currency) }}</text>
            </view>
          </template>

          <!-- 货到付款（预授权） -->
          <template v-else-if="orderInfo.payment_type === 2">
            <view class="amount-row">
              <text class="amount-label">{{ t('paymentSuccess.preauthorizedAmount') }}</text>
              <text class="amount-value">{{ formatPrice(orderInfo.total_amount, orderInfo.currency) }}</text>
            </view>
            <view class="preauth-notice">
              <text class="bi bi-info-circle"></text>
              <text>{{ t('paymentSuccess.preauthNotice') }}</text>
            </view>
          </template>

          <!-- 分期付款 -->
          <template v-else-if="orderInfo.payment_type === 3">
            <view class="amount-row">
              <text class="amount-label">{{ t('paymentSuccess.firstPeriodPaid') }}</text>
              <text class="amount-value success">{{ formatPrice(orderInfo.paid_amount, orderInfo.currency) }}</text>
            </view>
            <view class="installment-info">
              <view class="installment-row">
                <text class="installment-label">{{ t('paymentSuccess.totalPeriods') }}</text>
                <text class="installment-value">{{ orderInfo.installment?.periods || 12 }} {{ t('paymentSuccess.periods') }}</text>
              </view>
              <view class="installment-row">
                <text class="installment-label">{{ t('paymentSuccess.perPeriod') }}</text>
                <text class="installment-value">{{ formatPrice(orderInfo.installment?.period_amount, orderInfo.currency) }}</text>
              </view>
              <view class="installment-row">
                <text class="installment-label">{{ t('paymentSuccess.nextDueDate') }}</text>
                <text class="installment-value">{{ orderInfo.installment?.next_due_date || '-' }}</text>
              </view>
            </view>
          </template>
        </view>

        <!-- 收货地址 -->
        <view v-if="orderInfo.address_snapshot" class="address-section">
          <text class="section-label">{{ t('paymentSuccess.shippingTo') }}</text>
          <view class="address-info">
            <text class="address-name">{{ orderInfo.address_snapshot.recipient_name }}</text>
            <text class="address-detail">{{ formatAddress(orderInfo.address_snapshot) }}</text>
          </view>
        </view>
      </view>

      <!-- 操作按钮 -->
      <view class="action-buttons">
        <button class="btn-primary" @click="goOrderDetail">
          {{ t('paymentSuccess.viewOrderDetail') }}
        </button>
        <button class="btn-secondary" @click="continueShopping">
          {{ t('paymentSuccess.continueShopping') }}
        </button>
      </view>

      <!-- 额外提示（分期付款） -->
      <view v-if="orderInfo.payment_type === 3" class="extra-tips">
        <text class="bi bi-calendar-check"></text>
        <text>{{ t('paymentSuccess.installmentTip') }}</text>
      </view>

      <!-- 额外提示（货到付款） -->
      <view v-if="orderInfo.payment_type === 2" class="extra-tips preauth-tips">
        <text class="bi bi-shield-check"></text>
        <text>{{ t('paymentSuccess.preauthTip') }}</text>
      </view>
    </view>

    <!-- 游戏奖励弹窗 -->
    <GameRewardModal
      :visible="showGameReward"
      :wheel-chances="rewardInfo.wheelChances"
      :egg-code="rewardInfo.eggCode"
      @update:visible="showGameReward = $event"
      @play-now="goToGame"
      @later="closeRewardModal"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import GameRewardModal from '@/components/GameRewardModal.vue'
import { getOrderDetail } from '@/api/order'
import { getOrderReward } from '@/api/game'
import { tracker } from '@/utils/tracker'

const { t } = useI18n()
const appStore = useAppStore()

const loading = ref(true)
const orderId = ref<number | null>(null)
const orderNo = ref('')
const orderInfo = ref<any>(null)

// Game reward modal state
const showGameReward = ref(false)
const rewardInfo = reactive({
  wheelChances: 0,
  eggCode: null as string | null
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
    address.address,        // 详细地址
    address.district,       // 区县
    address.city,           // 城市
    address.province,       // 省/州
    address.postal_code,    // 邮编
    address.country_code    // 国家
  ].filter(Boolean)
  return parts.join(', ')
}

// 格式化规格
function formatSpecs(specs: Record<string, string>) {
  if (!specs) return ''
  return Object.entries(specs).map(([k, v]) => `${k}: ${v}`).join(', ')
}

// 获取页面标题
function getPageTitle() {
  if (!orderInfo.value) return ''
  switch (orderInfo.value.payment_type) {
    case 1: return t('paymentSuccess.titleFull')
    case 2: return t('paymentSuccess.titleCod')
    case 3: return t('paymentSuccess.titleInstallment')
    default: return t('paymentSuccess.titleFull')
  }
}

// 获取成功标题
function getSuccessTitle() {
  if (!orderInfo.value) return ''
  switch (orderInfo.value.payment_type) {
    case 1: return t('paymentSuccess.successTitleFull')
    case 2: return t('paymentSuccess.successTitleCod')
    case 3: return t('paymentSuccess.successTitleInstallment')
    default: return t('paymentSuccess.successTitleFull')
  }
}

// 获取成功副标题
function getSuccessSubtitle() {
  if (!orderInfo.value) return ''
  switch (orderInfo.value.payment_type) {
    case 1: return t('paymentSuccess.subtitleFull')
    case 2: return t('paymentSuccess.subtitleCod')
    case 3: return t('paymentSuccess.subtitleInstallment')
    default: return ''
  }
}

// 加载订单信息
async function loadOrderInfo() {
  // 优先使用 orderId，其次使用 orderNo
  const idOrNo = orderId.value || orderNo.value
  if (!idOrNo) {
    loading.value = false
    return
  }

  try {
    const res = await getOrderDetail(idOrNo)
    if (res.code === 0) {
      orderInfo.value = res.data
      // 同步 orderNo（用于跳转订单详情）
      if (!orderNo.value && res.data.order_no) {
        orderNo.value = res.data.order_no
      }
      // Load game reward info
      await loadGameReward()
    }
  } catch (e) {
    console.error('Failed to load order info:', e)
  } finally {
    loading.value = false
  }
}

// Load game reward for this order
async function loadGameReward() {
  if (!orderNo.value) return

  try {
    const res = await getOrderReward(orderNo.value)
    if (res.code === 0 && res.data) {
      const data = res.data
      // Only show modal if there are rewards
      if (data.wheel_chances > 0 || data.egg_granted) {
        rewardInfo.wheelChances = data.wheel_chances
        rewardInfo.eggCode = data.egg_code
        // Show modal after a short delay
        setTimeout(() => {
          showGameReward.value = true
        }, 500)
      }
    }
  } catch (e) {
    console.error('Failed to load game reward:', e)
  }
}

// Go to game page
function goToGame() {
  showGameReward.value = false
  uni.navigateTo({
    url: '/pages/game/index'
  })
}

// Close reward modal
function closeRewardModal() {
  showGameReward.value = false
}

// 跳转到订单详情
function goOrderDetail() {
  if (!orderInfo.value) return

  // 使用订单ID跳转到订单详情页
  uni.redirectTo({
    url: `/pages/order/detail?id=${orderInfo.value.id}`
  })
}

// 继续购物
function continueShopping() {
  uni.switchTab({
    url: '/pages/index/index'
  })
}

// 返回首页
function goHome() {
  uni.switchTab({
    url: '/pages/index/index'
  })
}

onLoad((options) => {
  if (options?.orderId) {
    orderId.value = parseInt(options.orderId)
  }
  if (options?.orderNo) {
    orderNo.value = options.orderNo
  }
})

onShow(() => {
  tracker.event('payment_success', {
    order_id: orderId.value,
    order_no: orderNo.value,
  })
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
  background-color: #ffffff;
}

// NavBar 左侧首页图标样式
.nav-action {
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;

  .bi {
    font-size: 20px;
    color: #191919;
  }

  &:active {
    background-color: #f7f7f7;
  }
}

.success-content {
  padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

/* 成功头部 */
.success-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60rpx 40rpx 40rpx;
  background: #FFFFFF;
}

.success-icon {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24rpx;
  box-shadow: 0 8rpx 24rpx rgba(82, 196, 26, 0.3);

  .bi {
    font-size: 64rpx;
    color: #52c41a;
  }
}

.success-title {
  font-size: 40rpx;
  font-weight: 600;
  color: #000000;
  margin-bottom: 12rpx;
}

.success-subtitle {
  font-size: 28rpx;
  color: rgba(0, 0, 0, 0.9);
  margin-bottom: 16rpx;
}

.order-no {
  font-size: 24rpx;
  color: rgba(0, 0, 0, 0.8);
  background: rgba(255, 255, 255, 0.2);
  padding: 8rpx 24rpx;
  border-radius: 20rpx;
}

/* 摘要卡片 */
.summary-card {
  margin: -20rpx 24rpx 24rpx;
  background: #fff;
  border-radius: 16rpx;
  padding: 32rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

/* 商品信息 */
.goods-info {
  display: flex;
  gap: 24rpx;
  padding-bottom: 24rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.goods-image {
  width: 140rpx;
  height: 140rpx;
  border-radius: 12rpx;
  background: #f5f5f5;
}

.goods-detail {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
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

/* 金额区域 */
.amount-section {
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.amount-label {
  font-size: 28rpx;
  color: #666;
}

.amount-value {
  font-size: 36rpx;
  font-weight: 600;
  color: #333;

  &.success {
    color: #52c41a;
  }
}

/* 预授权提示 */
.preauth-notice {
  display: flex;
  align-items: flex-start;
  gap: 12rpx;
  margin-top: 16rpx;
  padding: 16rpx;
  background: #fff7e6;
  border-radius: 8rpx;

  .bi {
    font-size: 28rpx;
    color: #fa8c16;
    flex-shrink: 0;
  }

  text {
    font-size: 24rpx;
    color: #d46b08;
    line-height: 1.5;
  }
}

/* 分期信息 */
.installment-info {
  margin-top: 20rpx;
  padding: 20rpx;
  background: #f6ffed;
  border-radius: 8rpx;
}

.installment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;

  &:not(:last-child) {
    margin-bottom: 12rpx;
  }
}

.installment-label {
  font-size: 26rpx;
  color: #666;
}

.installment-value {
  font-size: 26rpx;
  color: #333;
  font-weight: 500;
}

/* 地址区域 */
.address-section {
  padding-top: 24rpx;
}

.section-label {
  font-size: 24rpx;
  color: #999;
  margin-bottom: 12rpx;
}

.address-info {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.address-name {
  font-size: 28rpx;
  color: #333;
  font-weight: 500;
}

.address-detail {
  font-size: 26rpx;
  color: #666;
  line-height: 1.5;
}

/* 操作按钮 */
.action-buttons {
  padding: 0 24rpx;
  margin-bottom: 24rpx;
}

.btn-primary,
.btn-secondary {
  // 重置 uni-app 按钮默认样式
  &::after {
    border: none;
  }
}

.btn-primary {
  width: 100%;
  height: 88rpx;
  background: #FF6B35 !important;
  color: #fff !important;
  font-size: 32rpx;
  font-weight: 500;
  border-radius: 44rpx;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20rpx;
  line-height: 1;
}

.btn-secondary {
  width: 100%;
  height: 88rpx;
  background: #fff !important;
  color: #333 !important;
  font-size: 32rpx;
  font-weight: 500;
  border-radius: 44rpx;
  border: 2rpx solid #ddd !important;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

/* 额外提示 */
.extra-tips {
  margin: 0 24rpx;
  padding: 24rpx;
  background: #fff;
  border-radius: 12rpx;
  display: flex;
  align-items: flex-start;
  gap: 16rpx;

  .bi {
    font-size: 36rpx;
    color: #52c41a;
    flex-shrink: 0;
  }

  text:last-child {
    font-size: 26rpx;
    color: #666;
    line-height: 1.6;
  }

  &.preauth-tips {
    .bi {
      color: #1890ff;
    }
  }
}
</style>
