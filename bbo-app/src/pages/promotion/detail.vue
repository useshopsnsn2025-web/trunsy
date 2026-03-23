<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="promotion?.name || t('promotion.title')" title-align="center" />

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <template v-if="!loading && promotion">
      <!-- 活动头部 -->
      <view class="promo-header" :class="'type-' + promotion.type">
        <!-- Banner -->
        <image
          v-if="promotion.banner"
          class="header-banner"
          :src="promotion.banner"
          mode="aspectFit"
        />
        <view v-else class="header-placeholder">
          <text class="bi" :class="getTypeIcon(promotion.type)"></text>
        </view>

        <!-- 活动信息覆盖层 -->
        <view class="header-overlay">
          <view class="type-badge">
            <text>{{ getTypeLabel(promotion.type) }}</text>
          </view>
          <text class="promo-name">{{ promotion.name }}</text>
          <text v-if="promotion.description" class="promo-desc">{{ promotion.description }}</text>

          <!-- 倒计时 -->
          <view class="countdown-wrapper">
            <text class="countdown-label">{{ t('promotion.endsIn') }}</text>
            <view class="countdown-timer">
              <view v-if="countdown.days > 0" class="time-block">
                <text class="time-value">{{ countdown.days }}</text>
                <text class="time-unit">{{ t('promotion.days') }}</text>
              </view>
              <view class="time-block">
                <text class="time-value">{{ String(countdown.hours).padStart(2, '0') }}</text>
                <text class="time-unit">{{ t('promotion.hours') }}</text>
              </view>
              <view class="time-block">
                <text class="time-value">{{ String(countdown.minutes).padStart(2, '0') }}</text>
                <text class="time-unit">{{ t('promotion.minutes') }}</text>
              </view>
              <view class="time-block">
                <text class="time-value">{{ String(countdown.seconds).padStart(2, '0') }}</text>
                <text class="time-unit">{{ t('promotion.seconds') }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 商品列表 -->
      <scroll-view
        class="goods-list"
        scroll-y
        :refresher-enabled="false"
        @scrolltolower="loadMore"
      >
        <view class="goods-grid">
          <view
            v-for="item in goods"
            :key="item.id"
            class="goods-card"
            @click="goGoodsDetail(item.goods.id)"
          >
            <!-- 商品图片 -->
            <view class="goods-image-wrapper">
              <image
                class="goods-image"
                :src="item.goods.images?.[0] || '/static/placeholder.png'"
                mode="aspectFit"
                lazy-load
              />
              <!-- 已售罄遮罩 -->
              <view v-if="item.stock === 0" class="sold-out-overlay">
                <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
              </view>
              <!-- 折扣标签 -->
              <view v-else-if="item.discount" class="discount-badge">
                <text>{{ Math.round(item.discount * 100) }}%</text>
                <text class="off-text">{{ t('promotion.off') }}</text>
              </view>
              <!-- 库存提示 -->
              <view v-if="item.stock <= 5 && item.stock > 0" class="stock-badge">
                <text>{{ t('promotion.limitedStock') }}</text>
              </view>
              <!-- 收藏按钮 - 图片右上角 -->
              <view class="goods-like-btn" @click.stop="toggleFavorite(item.goods.id)">
                <text class="bi" :class="favoriteMap[item.goods.id] ? 'bi-heart-fill liked' : 'bi-heart'"></text>
              </view>
            </view>

            <!-- 商品信息 -->
            <view class="goods-info">
              <text class="goods-title">{{ item.goods.title }}</text>
              <view class="price-row">
                <text class="promo-price">{{ formatPrice(item.promotionPrice, item.goods.currency) }}</text>
                <text class="original-price">{{ formatPrice(item.goods.price, item.goods.currency) }}</text>
              </view>
              <text v-if="item.limitPerUser" class="limit-text">
                {{ formatLimitPerUserText(item.limitPerUser) }}
              </text>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more">
          <view v-if="loadingMore" class="loading-more-spinner"></view>
          <text v-else class="load-more-text">{{ t('common.pullToLoad') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="goods.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>

        <!-- 空状态 -->
        <view v-else class="empty-goods">
          <text class="bi bi-box empty-icon"></text>
          <text class="empty-text">{{ t('promotion.empty') }}</text>
        </view>

        <!-- 底部安全区域 -->
        <view class="safe-area-bottom"></view>
      </scroll-view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import {
  getPromotionDetail,
  getPromotionGoods,
  getRemainTime,
  PROMOTION_TYPE,
  type Promotion,
  type PromotionGoods
} from '@/api/promotion'
import { toggleLike } from '@/api/goods'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 路由参数
const id = ref<number>(0)

// 状态
const loading = ref(false)
const loadingMore = ref(false)
const promotion = ref<Promotion | null>(null)
const goods = ref<PromotionGoods[]>([])
const page = ref(1)
const pageSize = 20
const total = ref(0)

// 倒计时
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0, expired: false })
let countdownTimer: number | null = null

// 收藏状态
const favoriteMap = ref<Record<number, boolean>>({})
const togglingFavorite = ref<Record<number, boolean>>({})

// 计算属性
const hasMore = computed(() => goods.value.length < total.value)

// 格式化价格
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 格式化限购数量文本（手动替换占位符，解决 UniApp vue-i18n 插值不生效问题）
function formatLimitPerUserText(count: number): string {
  const template = t('promotion.limitPerUser')
  return template.replace('[COUNT]', String(count))
}

// 获取活动类型图标
function getTypeIcon(type: number): string {
  const icons: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: 'bi-percent',
    [PROMOTION_TYPE.REDUCTION]: 'bi-gift',
    [PROMOTION_TYPE.SECKILL]: 'bi-lightning',
    [PROMOTION_TYPE.GROUP]: 'bi-people',
  }
  return icons[type] || 'bi-tag'
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

// 更新倒计时
function updateCountdown() {
  if (!promotion.value) return
  countdown.value = getRemainTime(promotion.value.endTime)

  if (countdown.value.expired && countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

// 启动倒计时
function startCountdown() {
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000) as unknown as number
}

// 加载活动详情
async function loadPromotion() {
  loading.value = true
  try {
    const res = await getPromotionDetail(id.value)
    if (res.code === 0) {
      promotion.value = res.data
      startCountdown()
      loadGoods()
    }
  } catch (e) {
    console.error('Failed to load promotion:', e)
    toast.error(t('common.error'))
  } finally {
    loading.value = false
  }
}

// 加载活动商品
async function loadGoods(isLoadMore = false) {
  if (!isLoadMore) {
    page.value = 1
  }
  if (isLoadMore) {
    loadingMore.value = true
  }

  try {
    const res = await getPromotionGoods(id.value, {
      page: page.value,
      pageSize,
    })
    if (res.code === 0) {
      const list = res.data.list || []
      if (isLoadMore) {
        goods.value = [...goods.value, ...list]
      } else {
        goods.value = list
      }
      total.value = res.data.total || 0

      // 初始化收藏状态
      list.forEach(item => {
        if (item.goods?.isLiked !== undefined) {
          favoriteMap.value[item.goods.id] = item.goods.isLiked
        }
      })
    }
  } catch (e) {
    console.error('Failed to load goods:', e)
  } finally {
    loadingMore.value = false
  }
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadGoods(true)
}

// 跳转商品详情
function goGoodsDetail(goodsId: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${goodsId}` })
}

// 切换收藏状态
async function toggleFavorite(goodsId: number) {
  if (togglingFavorite.value[goodsId]) return

  togglingFavorite.value[goodsId] = true
  try {
    const res = await toggleLike(goodsId)
    if (res.code === 0) {
      favoriteMap.value[goodsId] = res.data.isLiked
      // uni.showToast({
      //   title: res.data.isLiked ? t('common.favoriteSuccess') : t('common.unfavoriteSuccess'),
      //   icon: 'none'
      // })
    }
  } catch (e) {
    console.error('Failed to toggle favorite:', e)
  } finally {
    togglingFavorite.value[goodsId] = false
  }
}

onMounted(() => {
  // 获取路由参数
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1]
  // @ts-ignore
  const options = currentPage?.$page?.options || currentPage?.options || {}
  id.value = parseInt(options.id) || 0

  if (id.value) {
    loadPromotion()
  }
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-accent-light: #FEF3C7;
$color-success: #059669;
$color-danger: #DC2626;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;

$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 22px;

$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-full: 9999px;

$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

.page {
  min-height: 100vh;
  background-color: $color-background;
  display: flex;
  flex-direction: column;
}

// 活动头部
.promo-header {
  position: relative;
  height: 200px;
  overflow: hidden;

  &.type-1 { --promo-color: #FF6B35; }
  &.type-2 { --promo-color: #059669; }
  &.type-3 { --promo-color: #DC2626; }
  &.type-4 { --promo-color: #7C3AED; }
}

.header-banner {
  width: 100%;
  height: 100%;
}

.header-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--promo-color, $color-accent) 0%, #cc4a1a 100%);

  .bi {
    font-size: 60px;
    color: rgba(255, 255, 255, 0.3);
  }
}

.header-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: $spacing-base;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
}

.type-badge {
  display: inline-block;
  padding: 2px 10px;
  background-color: var(--promo-color, $color-accent);
  border-radius: $radius-sm;
  margin-bottom: $spacing-sm;

  text {
    font-size: $font-size-xs;
    color: #fff;
    font-weight: $font-weight-medium;
  }
}

.promo-name {
  display: block;
  font-size: $font-size-lg;
  font-weight: $font-weight-bold;
  color: #fff;
  margin-bottom: $spacing-xs;
}

.promo-desc {
  display: block;
  font-size: $font-size-sm;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: $spacing-sm;
}

// 倒计时
.countdown-wrapper {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.countdown-label {
  font-size: $font-size-sm;
  color: rgba(255, 255, 255, 0.8);
}

.countdown-timer {
  display: flex;
  gap: $spacing-xs;
}

.time-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.5);
  border-radius: $radius-sm;
  padding: $spacing-xs $spacing-sm;
  min-width: 36px;
}

.time-value {
  font-size: $font-size-md;
  font-weight: $font-weight-bold;
  color: #fff;
}

.time-unit {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.7);
}

// 商品列表
// 高度通过 :style 动态计算：windowHeight - navBarHeight - promoHeaderHeight
.goods-list {
  flex: 1;
  width: 100%;
}

.goods-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
  padding: $spacing-base;
}

// 商品卡片
.goods-card {
  position: relative;
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-sm;

  &:active {
    opacity: 0.9;
  }
}

.goods-image-wrapper {
  position: relative;
  width: 100%;
  padding-top: 100%;
  border-radius: 8px;
}

.goods-image {
  position: absolute;
  top: 8px;
  left: 8px;
  right: 8px;
  bottom: 8px;
  width: calc(100% - 16px);
  height: calc(100% - 16px);
  object-fit: contain;
  background-color: #fff;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.sold-out-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.sold-out-text {
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.discount-badge {
  position: absolute;
  top: $spacing-sm;
  left: $spacing-sm;
  padding: 2px 6px;
  background-color: $color-danger;
  border-radius: $radius-sm;
  display: flex;
  align-items: baseline;
  gap: 2px;

  text {
    font-size: $font-size-sm;
    font-weight: $font-weight-bold;
    color: #fff;
  }

  .off-text {
    font-size: $font-size-xs;
    font-weight: $font-weight-medium;
  }
}

// 收藏按钮 - 图片右上角
.goods-like-btn {
  position: absolute;
  top: $spacing-sm;
  right: $spacing-sm;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, background-color 0.2s ease;
  z-index: 1;

  .bi {
    font-size: 16px;
    color: $color-muted;
    transition: color 0.2s ease;
  }

  .liked {
    color: $color-accent;
  }

  &:active {
    transform: scale(1.15);
    background-color: #fff;
  }
}

.stock-badge {
  position: absolute;
  display: flex;
  align-items: center;
  bottom: $spacing-sm;
  left: $spacing-sm;
  padding: 4px 8px;
  background-color: $color-accent;
  border-radius: $radius-sm;

  &.sold-out {
    background-color: $color-muted;
  }

  text {
    font-size: $font-size-xs;
    color: #fff;
    font-weight: $font-weight-medium;
  }
}

.goods-info {
  padding: $spacing-md;
}

.goods-title {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: $spacing-sm;
  line-height: 1.3;
}

.price-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-bottom: $spacing-xs;
}

.promo-price {
  font-size: $font-size-lg;
  font-weight: $font-weight-bold;
  color: $color-danger;
}

.original-price {
  font-size: $font-size-sm;
  color: $color-muted;
  text-decoration: line-through;
}

.limit-text {
  font-size: $font-size-xs;
  color: $color-muted;
}

// 加载更多
.load-more, .no-more {
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

.load-more-text, .no-more text {
  font-size: $font-size-sm;
  color: $color-muted;
}

// 空状态
.empty-goods {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-xl * 2;
}

.empty-icon {
  font-size: 48px;
  color: $color-muted;
  margin-bottom: $spacing-md;
}

.empty-text {
  font-size: $font-size-base;
  color: $color-muted;
}

.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}
</style>
