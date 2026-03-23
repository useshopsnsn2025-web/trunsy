<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('promotion.title')" />

    <!-- 活动类型筛选 - 横向滚动胶囊设计 -->
    <view class="filter-section">
      <scroll-view scroll-x class="filter-scroll" :show-scrollbar="false" enhanced>
        <view class="filter-list">
          <view
            v-for="(tab, index) in typeTabs"
            :key="index"
            class="filter-pill"
            :class="{ active: currentType === tab.value }"
            @click="selectType(tab.value)"
          >
            <text class="bi" :class="getTabIcon(tab.value)"></text>
            <text class="pill-text">{{ tab.label }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && !promotions.length" class="empty-state">
      <view class="empty-illustration">
        <text class="bi bi-tag-fill"></text>
      </view>
      <text class="empty-title">{{ t('promotion.empty') }}</text>
      <text class="empty-desc">{{ t('promotion.emptyDesc') }}</text>
    </view>

    <!-- 活动列表 -->
    <scroll-view
      v-if="!loading && promotions.length > 0"
      class="promotion-list"
      scroll-y
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <view class="promotion-container">
        <!-- 大图Banner活动卡片 -->
        <view
          v-for="promo in promotions"
          :key="promo.id"
          class="promo-card"
          @click="goDetail(promo.id)"
        >
          <!-- Banner区域 -->
          <view class="promo-banner">
            <image
              v-if="promo.banner"
              class="banner-img"
              :src="promo.banner"
              mode="aspectFill"
            />
            <view v-else class="banner-gradient" :class="'gradient-' + (promo.type || 1)">
              <text class="bi" :class="getTypeIcon(promo.type)"></text>
            </view>

            <!-- 状态标签 -->
            <view class="status-badges">
              <view class="badge badge-type" :class="'type-' + promo.type">
                {{ getTypeLabel(promo.type) }}
              </view>
              <view v-if="isUrgent(promo.endTime)" class="badge badge-urgent">
                <text class="bi bi-clock-fill"></text>
                <text>Ending Soon</text>
              </view>
            </view>

            <!-- 折扣信息 -->
            <view v-if="promo.maxDiscount" class="discount-tag">
              <text class="discount-text">UP TO</text>
              <text class="discount-value">{{ promo.maxDiscount }}%</text>
              <text class="discount-text">OFF</text>
            </view>
          </view>

          <!-- 信息区域 -->
          <view class="promo-content">
            <view class="promo-header">
              <text class="promo-name">{{ promo.name }}</text>
            </view>

            <text v-if="promo.description" class="promo-desc">{{ promo.description }}</text>

            <view class="promo-footer">
              <view class="promo-stats">
                <view class="stat-item">
                  <text class="bi bi-box-seam"></text>
                  <text>{{ promo.goodsCount || 0 }} items</text>
                </view>
              </view>

              <view class="countdown-wrapper" :class="{ urgent: isUrgent(promo.endTime) }">
                <text class="countdown-label">Ends in</text>
                <text class="countdown-time">{{ formatCountdown(promo.endTime) }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more">
        <view v-if="loadingMore" class="loading-more-spinner"></view>
        <text v-else class="load-more-text">{{ t('common.pullToLoad') }}</text>
      </view>

      <!-- 没有更多 -->
      <view v-else-if="promotions.length > 0" class="no-more">
        <view class="no-more-line"></view>
        <text>{{ t('common.noMore') }}</text>
        <view class="no-more-line"></view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { getPromotionList, getRemainTime, PROMOTION_TYPE, type Promotion } from '@/api/promotion'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()

// 状态
const loading = ref(false)
const loadingMore = ref(false)
const refreshing = ref(false)
const promotions = ref<Promotion[]>([])
const page = ref(1)
const pageSize = 10
const total = ref(0)
const currentType = ref<number | null>(null)

// 计算属性
const hasMore = computed(() => promotions.value.length < total.value)

// 活动类型标签 - 更简洁的国际化标签
const typeTabs = computed(() => [
  { value: null, label: t('promotion.all') },
  { value: PROMOTION_TYPE.SECKILL, label: t('promotion.typeSeckill') },
  { value: PROMOTION_TYPE.DISCOUNT, label: t('promotion.typeDiscount') },
  { value: PROMOTION_TYPE.REDUCTION, label: t('promotion.typeReduction') },
  { value: PROMOTION_TYPE.GROUP, label: t('promotion.typeGroup') },
])

// 获取活动类型图标
function getTypeIcon(type: number): string {
  const icons: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: 'bi-percent',
    [PROMOTION_TYPE.REDUCTION]: 'bi-gift-fill',
    [PROMOTION_TYPE.SECKILL]: 'bi-lightning-fill',
    [PROMOTION_TYPE.GROUP]: 'bi-people-fill',
  }
  return icons[type] || 'bi-tag-fill'
}

// 获取筛选标签图标
function getTabIcon(type: number | null): string {
  if (type === null) return 'bi-grid-fill'
  const icons: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: 'bi-percent',
    [PROMOTION_TYPE.REDUCTION]: 'bi-gift-fill',
    [PROMOTION_TYPE.SECKILL]: 'bi-lightning-fill',
    [PROMOTION_TYPE.GROUP]: 'bi-people-fill',
  }
  return icons[type] || 'bi-tag-fill'
}

// 获取活动类型标签
function getTypeLabel(type: number): string {
  const labels: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: t('promotion.typeDiscount'),
    [PROMOTION_TYPE.REDUCTION]: t('promotion.typeReduction'),
    [PROMOTION_TYPE.SECKILL]: t('promotion.typeSeckill'),
    [PROMOTION_TYPE.GROUP]: t('promotion.typeGroup'),
  }
  return labels[type] || ''
}

// 判断是否紧迫（24小时内结束）
function isUrgent(endTime: string): boolean {
  const remain = getRemainTime(endTime)
  return !remain.expired && remain.days === 0 && remain.hours < 24
}

// 格式化倒计时 - 更国际化的格式
function formatCountdown(endTime: string): string {
  const remain = getRemainTime(endTime)
  if (remain.expired) {
    return t('promotion.ended')
  }

  if (remain.days > 0) {
    return `${remain.days}d ${remain.hours}h`
  }
  if (remain.hours > 0) {
    return `${remain.hours}h ${remain.minutes}m`
  }
  return `${remain.minutes}m ${remain.seconds}s`
}

// 加载活动列表
async function loadPromotions(isRefresh = false) {
  if (isRefresh) {
    page.value = 1
    refreshing.value = true
  } else if (page.value === 1) {
    loading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const params: any = {
      page: page.value,
      pageSize,
    }
    if (currentType.value !== null) {
      params.type = currentType.value
    }

    const res = await getPromotionList(params)
    if (res.code === 0) {
      const list = res.data.list || []
      if (isRefresh || page.value === 1) {
        promotions.value = list
      } else {
        promotions.value = [...promotions.value, ...list]
      }
      total.value = res.data.total || 0
    }
  } catch (e) {
    console.error('Failed to load promotions:', e)
  } finally {
    loading.value = false
    loadingMore.value = false
    refreshing.value = false
  }
}

// 选择类型
function selectType(type: number | null) {
  if (currentType.value === type) return
  currentType.value = type
  loadPromotions(true)
}

// 下拉刷新
function onRefresh() {
  loadPromotions(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadPromotions()
}

// 跳转活动详情
function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/promotion/detail?id=${id}` })
}

onMounted(() => {
  loadPromotions()
})
</script>

<style lang="scss" scoped>
// ============================================
// Design System - International E-commerce Style
// ============================================

// Colors - Trust Blue + Orange CTA
$color-primary: #3B82F6;
$color-primary-light: #DBEAFE;
$color-secondary: #64748B;
$color-text: #1E293B;
$color-text-secondary: #64748B;
$color-text-muted: #94A3B8;
$color-background: #F8FAFC;
$color-surface: #FFFFFF;
$color-border: #E2E8F0;
$color-cta: #F97316;
$color-success: #10B981;
$color-danger: #EF4444;

// Type gradients
$gradient-1: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
$gradient-2: linear-gradient(135deg, #10B981 0%, #059669 100%);
$gradient-3: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
$gradient-4: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);

// Typography
$font-size-xs: 11px;
$font-size-sm: 12px;
$font-size-base: 14px;
$font-size-md: 15px;
$font-size-lg: 16px;
$font-size-xl: 18px;
$font-size-2xl: 22px;

$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// Spacing
$space-xs: 4px;
$space-sm: 8px;
$space-md: 12px;
$space-base: 16px;
$space-lg: 20px;
$space-xl: 24px;
$space-2xl: 32px;

// Border Radius
$radius-sm: 6px;
$radius-md: 8px;
$radius-lg: 12px;
$radius-xl: 16px;
$radius-full: 9999px;

// Shadows
$shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
$shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
$shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);

// ============================================
// Page Layout
// ============================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  display: flex;
  flex-direction: column;
}

// ============================================
// Filter Section - Horizontal Scroll Pills
// ============================================

.filter-section {
  background-color: $color-surface;
  padding: $space-md 0;
}

.filter-scroll {
  width: 100%;
  white-space: nowrap;
}

.filter-list {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  padding: 0 $space-base;
  gap: 10px;
}

.filter-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 36px;
  padding: 0 16px;
  background-color: $color-background;
  border-radius: 18px;
  border: 1.5px solid $color-border;
  white-space: nowrap;
  transition: all 0.25s ease;

  .bi {
    font-size: 14px;
    color: $color-text-muted;
    transition: color 0.25s ease;
  }

  .pill-text {
    font-size: $font-size-sm;
    font-weight: $font-weight-medium;
    color: $color-text-secondary;
    transition: color 0.25s ease;
  }

  &:active {
    transform: scale(0.96);
  }

  &.active {
    background: linear-gradient(135deg, $color-primary 0%, #2563EB 100%);
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);

    .bi {
      color: #fff;
    }

    .pill-text {
      color: #fff;
      font-weight: $font-weight-semibold;
    }
  }
}

// ============================================
// Empty State
// ============================================

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $space-2xl;
  min-height: 50vh;
}

.empty-illustration {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-primary-light;
  border-radius: $radius-full;
  margin-bottom: $space-lg;

  .bi {
    font-size: 36px;
    color: $color-primary;
  }
}

.empty-title {
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  color: $color-text;
  margin-bottom: $space-sm;
}

.empty-desc {
  font-size: $font-size-base;
  color: $color-text-muted;
  text-align: center;
}

// ============================================
// Promotion List
// ============================================

.promotion-list {
  flex: 1;
  box-sizing: border-box;
}

.promotion-container {
  padding: $space-base;
  display: flex;
  flex-direction: column;
  gap: $space-base;
}

// ============================================
// Promotion Card - Modern Banner Style
// ============================================

.promo-card {
  background-color: $color-surface;
  border-radius: $radius-xl;
  overflow: hidden;
  box-shadow: $shadow-sm;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.99);
    box-shadow: $shadow-md;
  }
}

// Banner Area
.promo-banner {
  position: relative;
  width: 100%;
  height: 160px;
  overflow: hidden;
}

.banner-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.banner-gradient {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;

  &.gradient-1 { background: $gradient-1; }
  &.gradient-2 { background: $gradient-2; }
  &.gradient-3 { background: $gradient-3; }
  &.gradient-4 { background: $gradient-4; }

  .bi {
    font-size: 56px;
    color: rgba(255, 255, 255, 0.3);
  }
}

// Status Badges
.status-badges {
  position: absolute;
  top: $space-md;
  left: $space-md;
  display: flex;
  gap: $space-sm;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: $space-xs;
  padding: $space-xs $space-sm;
  border-radius: $radius-sm;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  color: #fff;
}

.badge-type {
  &.type-1 { background-color: $color-cta; }
  &.type-2 { background-color: $color-success; }
  &.type-3 { background-color: $color-danger; }
  &.type-4 { background-color: #8B5CF6; }
}

.badge-urgent {
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);

  .bi {
    font-size: 10px;
  }
}

// Discount Tag
.discount-tag {
  position: absolute;
  bottom: $space-md;
  right: $space-md;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $space-sm $space-md;
  background-color: $color-danger;
  border-radius: $radius-md;
  color: #fff;
}

.discount-text {
  font-size: $font-size-xs;
  font-weight: $font-weight-medium;
  opacity: 0.9;
}

.discount-value {
  font-size: $font-size-2xl;
  font-weight: $font-weight-bold;
  line-height: 1;
}

// Content Area
.promo-content {
  padding: $space-base;
  display: flex;
  flex-direction: column;
  gap: $space-sm;
}

.promo-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.promo-name {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-text;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.promo-desc {
  font-size: $font-size-sm;
  color: $color-text-secondary;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.promo-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: $space-sm;
  border-top: 1px solid $color-border;
  margin-top: $space-xs;
}

.promo-stats {
  display: flex;
  align-items: center;
  gap: $space-md;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: $space-xs;
  font-size: $font-size-sm;
  color: $color-text-muted;

  .bi {
    font-size: 14px;
  }
}

.countdown-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  &.urgent {
    .countdown-time {
      color: $color-danger;
    }
  }
}

.countdown-label {
  font-size: $font-size-xs;
  color: $color-text-muted;
}

.countdown-time {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-text;
  font-variant-numeric: tabular-nums;
}

// ============================================
// Load More
// ============================================

.load-more, .no-more {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: $space-lg;
  gap: $space-md;
}

.loading-more-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid $color-border;
  border-top-color: $color-primary;
  border-radius: $radius-full;
  animation: spin 0.8s linear infinite;
}

.load-more-text {
  font-size: $font-size-sm;
  color: $color-text-muted;
}

.no-more {
  color: $color-text-muted;
  font-size: $font-size-sm;
}

.no-more-line {
  width: 40px;
  height: 1px;
  background-color: $color-border;
}

.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}
</style>
