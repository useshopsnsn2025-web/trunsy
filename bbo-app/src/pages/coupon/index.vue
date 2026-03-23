<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-bar-content">
        <view class="nav-back" @click="goBack">
          <text class="bi bi-arrow-left icon-back"></text>
        </view>
        <text class="nav-title">{{ t('coupon.title') }}</text>
        <view class="nav-placeholder"></view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-bar-placeholder" :style="{ height: (statusBarHeight + 48) + 'px' }"></view>

    <!-- 标签切换 -->
    <view class="tabs-wrapper">
      <view class="tabs">
        <view
          v-for="tab in tabs"
          :key="tab.value"
          class="tab-item"
          :class="{ active: currentTab === tab.value }"
          @click="switchTab(tab.value)"
        >
          <text class="tab-text">{{ tab.label }}</text>
          <view v-if="tab.count > 0" class="tab-badge">{{ tab.count }}</view>
        </view>
      </view>
    </view>

    <!-- 领取优惠券入口 -->
    <view class="claim-section" @click="showClaimCenter">
      <view class="claim-left">
        <view class="claim-icon-wrapper">
          <text class="bi bi-gift-fill claim-icon"></text>
        </view>
        <view class="claim-info">
          <text class="claim-title">{{ t('coupon.claimCenter') }}</text>
          <text class="claim-desc">{{ t('coupon.claimDesc') }}</text>
        </view>
      </view>
      <text class="bi bi-chevron-right claim-arrow"></text>
    </view>

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && !coupons.length" class="empty-state">
      <view class="empty-icon-wrapper">
        <text class="bi bi-ticket-perforated empty-icon"></text>
      </view>
      <text class="empty-title">{{ getEmptyTitle() }}</text>
      <text class="empty-desc">{{ getEmptyDesc() }}</text>
      <button v-if="currentTab === 'available'" class="btn-claim" @click="showClaimCenter">
        {{ t('coupon.goGetCoupons') }}
      </button>
    </view>

    <!-- 优惠券列表 -->
    <scroll-view
      v-if="!loading && coupons.length > 0"
      class="coupon-list"
      scroll-y
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <view
        v-for="coupon in coupons"
        :key="coupon.id"
        class="coupon-card"
        :class="{
          disabled: currentTab !== 'available',
          'type-fixed': coupon.type === 1,
          'type-discount': coupon.type === 2,
          'type-amount': coupon.type === 3
        }"
        @click="useCoupon(coupon)"
      >
        <!-- 左侧金额区域 -->
        <view class="coupon-left">
          <view class="coupon-value">
            <!-- 类型: 1=满减(固定金额), 2=折扣, 3=固定金额(代金券) -->
            <template v-if="coupon.type === 2">
              <!-- 折扣券：显示折扣百分比 -->
              <text class="value-number">{{ coupon.discount }}</text>
              <text class="value-unit">% OFF</text>
            </template>
            <template v-else>
              <!-- 满减券/代金券：显示金额 -->
              <text class="value-number">{{ formatAmount(coupon.value || 0) }}</text>
            </template>
          </view>
          <text class="coupon-condition">
            {{ formatMinSpendText(coupon.minAmount) }}
          </text>
        </view>

        <!-- 分隔线装饰 -->
        <view class="coupon-divider">
          <view class="divider-circle top"></view>
          <view class="divider-line"></view>
          <view class="divider-circle bottom"></view>
        </view>

        <!-- 右侧信息区域 -->
        <view class="coupon-right">
          <view class="coupon-header">
            <text class="coupon-name">{{ coupon.name }}</text>
            <view class="coupon-type-badge" :class="'type-' + coupon.type">
              {{ getCouponTypeName(coupon.type) }}
            </view>
          </view>
          <text class="coupon-desc">{{ coupon.description || getScopeName(coupon.scope) }}</text>
          <view class="coupon-footer">
            <text class="coupon-expire">
              {{ currentTab === 'used' ? `${t('coupon.usedAtPrefix')}${formatDate(coupon.usedAt || '')}${t('coupon.usedAtSuffix')}` :
                 currentTab === 'expired' ? `${t('coupon.expiredAtPrefix')}${formatDate(coupon.expireTime)}${t('coupon.expiredAtSuffix')}` :
                 `${t('coupon.validUntilPrefix')}${formatDate(coupon.expireTime)}${t('coupon.validUntilSuffix')}` }}
            </text>
            <view v-if="currentTab === 'available'" class="use-btn">
              <text>{{ t('coupon.useNow') }}</text>
            </view>
          </view>
        </view>

        <!-- 已使用/已过期标记 -->
        <view v-if="currentTab !== 'available'" class="coupon-status-overlay">
          <view class="status-stamp">
            <text>{{ currentTab === 'used' ? t('coupon.statusUsed') : t('coupon.statusExpired') }}</text>
          </view>
        </view>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more">
        <view v-if="loadingMore" class="loading-more-spinner"></view>
        <text v-else class="load-more-text">{{ t('common.pullToLoad') }}</text>
      </view>

      <!-- 没有更多 -->
      <view v-else-if="coupons.length > 0" class="no-more">
        <text>{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 领券中心弹窗 -->
    <view v-if="showClaimPopup" class="claim-popup-mask" @click="closeClaimPopup">
      <view class="claim-popup" @click.stop>
        <view class="claim-popup-header">
          <text class="claim-popup-title">{{ t('coupon.claimCenter') }}</text>
          <view class="claim-popup-close" @click="closeClaimPopup">
            <text class="bi bi-x-lg"></text>
          </view>
        </view>

        <scroll-view class="claim-popup-content" scroll-y>
          <!-- 加载状态 -->
          <view v-if="loadingClaimable" class="claim-loading">
            <view class="loading-spinner small"></view>
          </view>

          <!-- 空状态 -->
          <view v-else-if="!claimableCoupons.length" class="claim-empty">
            <text class="bi bi-inbox claim-empty-icon"></text>
            <text class="claim-empty-text">{{ t('coupon.noClaimable') }}</text>
          </view>

          <!-- 可领取优惠券列表 -->
          <view v-else class="claimable-list">
            <view
              v-for="coupon in claimableCoupons"
              :key="coupon.id"
              class="claimable-item"
            >
              <view class="claimable-left">
                <view class="claimable-value">
                  <!-- 类型: 1=满减, 2=折扣, 3=代金券 -->
                  <template v-if="coupon.type === 2">
                    <text class="claimable-number">{{ coupon.discount }}%</text>
                  </template>
                  <template v-else>
                    <text class="claimable-number">{{ formatAmount(coupon.value || 0) }}</text>
                  </template>
                </view>
                <text class="claimable-condition">
                  {{ formatMinSpendText(coupon.minAmount) }}
                </text>
              </view>
              <view class="claimable-right">
                <text class="claimable-name">{{ coupon.name }}</text>
                <text v-if="coupon.description" class="claimable-desc">{{ coupon.description }}</text>
                <text class="claimable-expire">{{ formatValidDaysText(coupon.validDays || 0) }}</text>
              </view>
              <button
                class="claim-btn"
                :class="{ claimed: coupon.claimed }"
                :disabled="coupon.claimed"
                @click="handleClaimCoupon(coupon)"
              >
                {{ coupon.claimed ? t('coupon.claimed') : t('coupon.claim') }}
              </button>
            </view>
          </view>
        </scroll-view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import {
  getCoupons,
  getCouponCount,
  getClaimableCoupons,
  claimCoupon as claimCouponApi,
  type Coupon as CouponType,
  type ClaimableCoupon,
} from '@/api/coupon'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 前端展示用的优惠券类型
interface DisplayCoupon {
  id: number
  couponId?: number
  name: string
  description?: string // 优惠券描述
  type: number // 1: 满减, 2: 折扣, 3: 代金券
  value?: number // 优惠值（金额或折扣率）
  discount?: number // 折扣百分比（仅折扣券使用，如 10 表示 10% OFF）
  minAmount: number // 最低消费
  scope?: number // 适用范围
  expireTime: string
  usedAt?: string
  claimed?: boolean
  validDays?: number
}

// 状态
const loading = ref(false)
const loadingMore = ref(false)
const refreshing = ref(false)
const currentTab = ref<'available' | 'used' | 'expired'>('available')
const coupons = ref<DisplayCoupon[]>([])
const page = ref(1)
const pageSize = 20
const total = ref(0)

// 领券中心
const showClaimPopup = ref(false)
const loadingClaimable = ref(false)
const claimableCoupons = ref<DisplayCoupon[]>([])

// 标签列表
type TabValue = 'available' | 'used' | 'expired'
const tabs = computed<{ value: TabValue; label: string; count: number }[]>(() => [
  { value: 'available', label: t('coupon.tabAvailable'), count: availableCount.value },
  { value: 'used', label: t('coupon.tabUsed'), count: 0 },
  { value: 'expired', label: t('coupon.tabExpired'), count: 0 },
])

// 可用优惠券数量
const availableCount = ref(0)

// 计算属性
const statusBarHeight = ref(0)
const hasMore = computed(() => coupons.value.length < total.value)

// 获取系统信息
uni.getSystemInfo({
  success: (res) => {
    statusBarHeight.value = res.statusBarHeight || 20
  }
})

// 格式化价格（使用汇率转换）
function formatPrice(amount: number, currency: string = 'USD'): string {
  return appStore.formatPrice(amount, currency)
}

// 格式化金额（优惠券金额，使用汇率转换）
function formatAmount(amount: number) {
  return formatPrice(amount, 'USD')
}

// 格式化日期
function formatDate(dateStr: string) {
  if (!dateStr) return ''
  // 处理各种日期格式，确保兼容性
  const date = new Date(dateStr.replace(/-/g, '/'))
  if (isNaN(date.getTime())) return ''
  return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

// ==========================================
// 插值翻译辅助函数（解决 UniApp APP 端 vue-i18n 插值不生效的问题）
// ==========================================

// 格式化最低消费文本
function formatMinSpendText(minAmount: number): string {
  if (minAmount > 0) {
    const template = t('coupon.minSpend')
    return template.replace('[AMOUNT]', formatAmount(minAmount))
  }
  return t('coupon.noMinimum')
}

// 注意：有效期/已使用/已过期文本现在直接在模板中使用 t() 函数
// 以确保翻译的响应式更新

// 格式化有效天数文本
function formatValidDaysText(days: number): string {
  const template = t('coupon.validDays')
  return template.replace('[DAYS]', String(days))
}

// 获取优惠券类型名称
// 后端类型: 1=满减(固定金额), 2=折扣, 3=代金券
function getCouponTypeName(type: number) {
  const names: Record<number, string> = {
    1: t('coupon.typeFixed'),       // 满减券
    2: t('coupon.typeDiscount'),    // 折扣券
    3: t('coupon.typeAmount'),      // 代金券
  }
  return names[type] || ''
}

// 获取适用范围名称
function getScopeName(scope?: number) {
  if (!scope || scope === 1) return t('coupon.allProducts')
  if (scope === 2) return t('coupon.categoryOnly')
  if (scope === 3) return t('coupon.goodsOnly')
  return t('coupon.allProducts')
}

// 获取空状态标题
function getEmptyTitle() {
  const titles: Record<string, string> = {
    available: t('coupon.emptyAvailable'),
    used: t('coupon.emptyUsed'),
    expired: t('coupon.emptyExpired'),
  }
  return titles[currentTab.value]
}

// 获取空状态描述
function getEmptyDesc() {
  const descs: Record<string, string> = {
    available: t('coupon.emptyAvailableDesc'),
    used: t('coupon.emptyUsedDesc'),
    expired: t('coupon.emptyExpiredDesc'),
  }
  return descs[currentTab.value]
}

// 切换标签
function switchTab(tab: 'available' | 'used' | 'expired') {
  if (currentTab.value === tab) return
  currentTab.value = tab
  page.value = 1
  coupons.value = []
  loadCoupons()
}

// 加载优惠券列表
async function loadCoupons(isRefresh = false) {
  if (isRefresh) {
    page.value = 1
    refreshing.value = true
  } else if (page.value === 1) {
    loading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const res = await getCoupons({ status: currentTab.value, page: page.value, pageSize })
    const data = res.data

    // 转换API数据为显示格式
    // 后端类型: 1=满减(固定金额), 2=折扣, 3=代金券
    const items: DisplayCoupon[] = data.list.map((item: CouponType) => ({
      id: item.id,
      couponId: item.couponId,
      name: item.name,
      description: item.description,
      type: item.type,
      value: item.value,
      // 折扣券: value 是折扣率（如 0.9 表示9折），转换为折扣百分比（如 10% OFF）
      discount: item.type === 2 ? Math.round((1 - item.value) * 100) : undefined,
      minAmount: item.minAmount,
      scope: item.scope,
      expireTime: item.expiredAt,
      usedAt: item.usedAt,
    }))

    if (isRefresh || page.value === 1) {
      coupons.value = items
    } else {
      coupons.value = [...coupons.value, ...items]
    }
    total.value = data.total

    // 同时更新可用优惠券数量
    if (currentTab.value === 'available' && page.value === 1) {
      availableCount.value = data.total
    }
  } catch (e) {
    console.error('Failed to load coupons:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
    loadingMore.value = false
    refreshing.value = false
  }
}

// 下拉刷新
function onRefresh() {
  loadCoupons(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadCoupons()
}

// 使用优惠券
function useCoupon(coupon: Coupon) {
  if (currentTab.value !== 'available') return

  // 跳转到首页或商品列表
  uni.switchTab({ url: '/pages/index/index' })
}

// 显示领券中心
function showClaimCenter() {
  showClaimPopup.value = true
  loadClaimableCoupons()
}

// 关闭领券中心
function closeClaimPopup() {
  showClaimPopup.value = false
}

// 加载可领取优惠券
async function loadClaimableCoupons() {
  loadingClaimable.value = true

  try {
    const res = await getClaimableCoupons()
    const data = res.data

    // 转换为显示格式
    // 后端类型: 1=满减, 2=折扣, 3=代金券
    claimableCoupons.value = data.map((item: ClaimableCoupon) => ({
      id: item.id,
      name: item.name,
      description: item.description,
      type: item.type,
      value: item.value,
      // 折扣券: value 是折扣率（如 0.9 表示9折），转换为折扣百分比
      discount: item.type === 2 ? Math.round((1 - item.value) * 100) : undefined,
      minAmount: item.minAmount,
      validDays: item.validDays,
      expireTime: '',
      claimed: item.claimed,
    }))
  } catch (e) {
    console.error('Failed to load claimable coupons:', e)
  } finally {
    loadingClaimable.value = false
  }
}

// 领取优惠券
async function handleClaimCoupon(coupon: DisplayCoupon) {
  if (coupon.claimed) return

  try {
    await claimCouponApi(coupon.id)

    coupon.claimed = true
    availableCount.value++

    toast.success(t('coupon.claimSuccess'))

    // 刷新我的优惠券列表
    if (currentTab.value === 'available') {
      loadCoupons(true)
    }
  } catch (e) {
    toast.error(t('coupon.claimFailed'))
  }
}

// 返回
function goBack() {
  uni.navigateBack({ delta: 1 })
}

onMounted(() => {
  loadCoupons()
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量
// ==========================================

// 色彩系统
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-accent-light: #FFF4F0;
$color-success: #059669;
$color-success-light: #D1FAE5;
$color-warning: #D97706;
$color-warning-light: #FEF3C7;
$color-danger: #DC2626;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;
$color-border-light: #F5F5F4;

// 字体系统
$font-family-base: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;
$font-family-display: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Helvetica Neue', sans-serif;

// 字号系统
$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 22px;
$font-size-2xl: 28px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 圆角
$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-xl: 18px;
$radius-full: 9999px;

// 阴影
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
$shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// ==========================================
// 基础样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  font-family: $font-family-base;
  display: flex;
  flex-direction: column;
  color: $color-primary;
  -webkit-font-smoothing: antialiased;
}

// 导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: $color-surface;
  z-index: 100;
  box-shadow: $shadow-sm;
}

.nav-bar-content {
  height: 48px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 $spacing-base;
}

.nav-bar-placeholder {
  flex-shrink: 0;
}

.nav-title {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  font-family: $font-family-display;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.nav-back {
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: $color-background;
  border-radius: $radius-full;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.95);
    background-color: $color-border;
  }
}

.nav-placeholder {
  width: 38px;
}

.icon-back {
  font-size: 28px;
  color: $color-primary;
}

// 标签切换
.tabs-wrapper {
  background-color: $color-surface;
  padding: $spacing-md $spacing-base;
  border-bottom: 1px solid $color-border-light;
}

.tabs {
  display: flex;
  background-color: $color-background;
  border-radius: $radius-lg;
  padding: $spacing-xs;
}

.tab-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-xs;
  padding: $spacing-sm $spacing-md;
  border-radius: $radius-md;
  transition: all 0.2s ease;
  cursor: pointer;

  &.active {
    background-color: $color-surface;
    box-shadow: $shadow-sm;
  }
}

.tab-text {
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  color: $color-muted;

  .active & {
    color: $color-primary;
  }
}

.tab-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 $spacing-xs;
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

// 领券入口
.claim-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, $color-accent 0%, #FF8E53 100%);
  margin: $spacing-base;
  padding: $spacing-base;
  border-radius: $radius-lg;
  cursor: pointer;
  transition: transform 0.2s ease;

  &:active {
    transform: scale(0.98);
  }
}

.claim-left {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.claim-icon-wrapper {
  width: 44px;
  height: 44px;
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
}

.claim-icon {
  font-size: 24px;
  color: #fff;
}

.claim-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.claim-title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: #fff;
}

.claim-desc {
  font-size: $font-size-xs;
  color: rgba(255, 255, 255, 0.8);
}

.claim-arrow {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.8);
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-xl * 2;
  min-height: 40vh;
}

.empty-icon-wrapper {
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-accent-light;
  border-radius: $radius-full;
  margin-bottom: $spacing-xl;
}

.empty-icon {
  font-size: 48px;
  color: $color-accent;
}

.empty-title {
  font-family: $font-family-display;
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.empty-desc {
  font-size: $font-size-base;
  color: $color-muted;
  text-align: center;
  margin-bottom: $spacing-xl;
}

.btn-claim {
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  padding: $spacing-md $spacing-xl * 1.5;
  border-radius: $radius-full;
  border: none;
  margin: 0;
  line-height: 1.5;

  &::after {
    border: none;
  }

  &:active {
    opacity: 0.9;
  }
}

// 优惠券列表
.coupon-list {
  flex: 1;
  width: auto;
  padding: 0 $spacing-base $spacing-base;
}

// 优惠券卡片
.coupon-card {
  display: flex;
  align-items: stretch;
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
  margin-bottom: $spacing-md;
  box-shadow: $shadow-md;
  position: relative;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:active:not(.disabled) {
    transform: scale(0.98);
  }

  &.disabled {
    cursor: default;
    opacity: 0.7;
  }

  // 不同类型优惠券的左侧背景色
  // 类型: 1=满减, 2=折扣, 3=代金券
  &.type-fixed .coupon-left {
    background: linear-gradient(135deg, #FF6B35 0%, #FF8E53 100%);
  }

  &.type-discount .coupon-left {
    background: linear-gradient(135deg, #059669 0%, #10B981 100%);
  }

  &.type-amount .coupon-left {
    background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
  }
}

.coupon-left {
  width: 110px;
  min-height: 100px;
  padding: $spacing-base $spacing-md;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.coupon-value {
  display: flex;
  align-items: baseline;
  color: #fff;
}

.value-symbol {
  font-size: $font-size-lg;
  font-weight: $font-weight-bold;
}

.value-number {
  font-size: $font-size-2xl;
  font-weight: $font-weight-bold;
  line-height: 1;
}

.value-unit {
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  margin-left: 2px;
}

.value-icon {
  font-size: $font-size-2xl;
}

.coupon-condition {
  font-size: $font-size-xs;
  color: rgba(255, 255, 255, 0.85);
  margin-top: $spacing-xs;
  text-align: center;
}

// 分隔线
.coupon-divider {
  width: 16px;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}

.divider-circle {
  width: 16px;
  height: 8px;
  background-color: $color-background;
  position: absolute;
  left: 0;

  &.top {
    top: -1px;
    border-radius: 0 0 8px 8px;
  }

  &.bottom {
    bottom: -1px;
    border-radius: 8px 8px 0 0;
  }
}

.divider-line {
  flex: 1;
  width: 0;
  border-left: 2px dashed $color-border;
  margin: 12px 0;
}

.coupon-right {
  flex: 1;
  padding: $spacing-base;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.coupon-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $spacing-sm;
  margin-bottom: $spacing-xs;
}

.coupon-name {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.coupon-type-badge {
  font-size: $font-size-xs;
  padding: 2px 6px;
  border-radius: $radius-sm;
  flex-shrink: 0;

  &.type-1 {
    background-color: $color-accent-light;
    color: $color-accent;
  }

  &.type-2 {
    background-color: $color-success-light;
    color: $color-success;
  }

  &.type-3 {
    background-color: #EEF2FF;
    color: #6366F1;
  }
}

.coupon-desc {
  font-size: $font-size-sm;
  color: $color-muted;
  margin-bottom: $spacing-sm;
}

.coupon-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.coupon-expire {
  font-size: $font-size-xs;
  color: $color-muted;
}

.use-btn {
  display: flex;
  align-items: center;
  background-color: $color-accent;
  padding: $spacing-xs $spacing-md;
  border-radius: $radius-full;

  text {
    font-size: $font-size-xs;
    font-weight: $font-weight-semibold;
    color: #fff;
  }
}

// 已使用/已过期标记
.coupon-status-overlay {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: $spacing-xl;
  pointer-events: none;
}

.status-stamp {
  padding: $spacing-sm $spacing-base;
  border: 2px solid $color-muted;
  border-radius: $radius-sm;
  transform: rotate(-15deg);
  opacity: 0.6;

  text {
    font-size: $font-size-sm;
    font-weight: $font-weight-bold;
    color: $color-muted;
    text-transform: uppercase;
  }
}

// 加载更多
.load-more,
.no-more {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: $spacing-lg;
}

.loading-more-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid $color-border;
  border-top-color: $color-accent;
  border-radius: $radius-full;
  animation: spin 1s linear infinite;
}

.load-more-text,
.no-more text {
  font-size: $font-size-sm;
  color: $color-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// ==========================================
// 领券中心弹窗
// ==========================================

.claim-popup-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.claim-popup {
  width: 100%;
  max-height: 70vh;
  background-color: $color-surface;
  border-radius: $radius-xl $radius-xl 0 0;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.claim-popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base $spacing-lg;
  border-bottom: 1px solid $color-border-light;
}

.claim-popup-title {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.claim-popup-close {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-background;
  border-radius: $radius-full;
  cursor: pointer;

  .bi {
    font-size: 18px;
    color: $color-muted;
  }

  &:active {
    background-color: $color-border;
  }
}

.claim-popup-content {
  width: auto;
  flex: 1;
  padding: $spacing-base;
  max-height: 50vh;
}

.claim-loading {
  display: flex;
  justify-content: center;
  padding: $spacing-xl * 2;
}

.claim-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-xl * 2;
}

.claim-empty-icon {
  font-size: 48px;
  color: $color-border;
  margin-bottom: $spacing-md;
}

.claim-empty-text {
  font-size: $font-size-base;
  color: $color-muted;
}

.claimable-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.claimable-item {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-lg;
}

.claimable-left {
  width: 80px;
  flex-shrink: 0;
  text-align: center;
}

.claimable-value {
  display: flex;
  align-items: baseline;
  justify-content: center;
}

.claimable-number {
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-accent;
}

.claimable-condition {
  font-size: $font-size-xs;
  color: $color-muted;
  margin-top: 2px;
}

.claimable-right {
  flex: 1;
  min-width: 0;
}

.claimable-name {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
  display: block;
  margin-bottom: 2px;
}

.claimable-desc {
  font-size: $font-size-xs;
  color: $color-secondary;
  display: block;
  margin-bottom: 2px;
}

.claimable-expire {
  font-size: $font-size-xs;
  color: $color-muted;
}

.claim-btn {
  flex-shrink: 0;
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  padding: $spacing-sm $spacing-base;
  border-radius: $radius-full;
  border: none;
  margin: 0;
  line-height: 1.5;

  &::after {
    border: none;
  }

  &:active {
    opacity: 0.9;
  }

  &.claimed {
    background-color: $color-border;
    color: $color-muted;
  }
}

// 响应式
@media (prefers-reduced-motion: reduce) {
  .loading-spinner,
  .loading-more-spinner,
  .coupon-card,
  .claim-popup-mask,
  .claim-popup {
    animation: none;
    transition: none;
  }
}
</style>
