<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('favorites.title')" />

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && !items.length" class="empty-state">
      <view class="empty-icon-wrapper">
        <text class="bi bi-heart empty-icon"></text>
      </view>
      <text class="empty-title">{{ t('favorites.empty') }}</text>
      <text class="empty-desc">{{ t('favorites.emptyDesc') }}</text>
      <button class="btn-explore" @click="goExplore">
        {{ t('favorites.explore') }}
      </button>
    </view>

    <!-- 收藏列表 -->
    <scroll-view
      v-if="!loading && items.length > 0"
      class="favorites-list"
      scroll-y
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 商品列表 -->
      <view class="goods-list">
        <view
          v-for="item in items"
          :key="item.id"
          class="goods-item"
          @click="goDetail(item.id)"
        >
          <!-- 商品图片 -->
          <view class="goods-image-wrapper">
            <image
              class="goods-image"
              :src="item.images?.[0] || '/static/placeholder.png'"
              mode="aspectFit"
              lazy-load
            />
            <!-- 已售罄遮罩 -->
            <view v-if="item.stock === 0" class="sold-out-overlay">
              <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
            </view>
            <!-- 免运费标签 -->
            <view v-else-if="item.freeShipping" class="shipping-badge">
              <text>{{ t('goods.freeShipping') }}</text>
            </view>
          </view>

          <!-- 商品信息 -->
          <view class="goods-info">
            <text class="goods-title">{{ item.title }}</text>
            <view class="goods-meta">
              <text class="goods-condition">{{ getConditionText(item.condition) }}</text>
            </view>
            <!-- 活动标签 -->
            <view v-if="item.promotion" class="promo-tag">
              <text class="bi bi-lightning-fill"></text>
              <text>{{ item.promotion.discountPercent }}% OFF</text>
            </view>

            <view class="goods-footer">
              <view class="price-wrapper">
                <!-- 活动价格优先显示 -->
                <text class="goods-price" :class="{ 'promo-price': item.promotion }">
                  {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                </text>
                <!-- 有活动时显示原价 -->
                <text v-if="item.promotion" class="goods-original-price">
                  {{ formatPrice(item.price, item.currency) }}
                </text>
                <!-- 无活动但有原价时显示原价 -->
                <text v-else-if="item.originalPrice && item.originalPrice > item.price" class="goods-original-price">
                  {{ formatPrice(item.originalPrice, item.currency) }}
                </text>
              </view>
            </view>
          </view>

          <!-- 取消收藏按钮 -->
          <view class="remove-btn" @click.stop="removeFavorite(item)">
            <text class="bi bi-heart-fill"></text>
          </view>
        </view>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more">
        <view v-if="loadingMore" class="loading-more-spinner"></view>
        <text v-else class="load-more-text">{{ t('common.pullToLoad') }}</text>
      </view>

      <!-- 没有更多 -->
      <view v-else-if="items.length > 0" class="no-more">
        <text>{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 取消收藏确认弹窗 -->
    <ConfirmDialog
      :visible="showRemoveDialog"
      :title="t('favorites.removeTitle')"
      :content="t('favorites.removeConfirm')"
      icon="bi-heartbreak"
      icon-type="warning"
      :confirm-text="t('common.confirm')"
      :cancel-text="t('common.cancel')"
      @update:visible="showRemoveDialog = $event"
      @confirm="confirmRemove"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAppStore } from '@/store/modules/app'
import { getFavorites, toggleLike, type Goods } from '@/api/goods'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 状态
const loading = ref(false)
const loadingMore = ref(false)
const refreshing = ref(false)
const items = ref<Goods[]>([])
const page = ref(1)
const pageSize = 20
const total = ref(0)

// 删除确认弹窗
const showRemoveDialog = ref(false)
const removeTarget = ref<Goods | null>(null)

// 计算属性
const hasMore = computed(() => items.value.length < total.value)

// 格式化价格
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 获取商品状态文本
const getConditionText = (condition: number) => {
  const conditions: Record<number, string> = {
    1: t('goods.conditionNew'),
    2: t('goods.conditionLikeNew'),
    3: t('goods.conditionGood'),
    4: t('goods.conditionFair'),
    5: t('goods.conditionPoor'),
  }
  return conditions[condition] || ''
}

// 加载收藏列表
async function loadFavorites(isRefresh = false) {
  if (isRefresh) {
    page.value = 1
    refreshing.value = true
  } else if (page.value === 1) {
    loading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const res = await getFavorites({
      page: page.value,
      pageSize,
    })

    if (res.code === 0) {
      const list = res.data.list || []
      if (isRefresh || page.value === 1) {
        items.value = list
      } else {
        items.value = [...items.value, ...list]
      }
      total.value = res.data.total || 0
    }
  } catch (e) {
    console.error('Failed to load favorites:', e)
  } finally {
    loading.value = false
    loadingMore.value = false
    refreshing.value = false
  }
}

// 下拉刷新
function onRefresh() {
  loadFavorites(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadFavorites()
}

// 取消收藏 - 显示确认弹窗
function removeFavorite(item: Goods) {
  removeTarget.value = item
  showRemoveDialog.value = true
}

// 确认取消收藏
async function confirmRemove() {
  if (!removeTarget.value) return

  try {
    const result = await toggleLike(removeTarget.value.id)
    if (result.code === 0) {
      // 从列表中移除
      items.value = items.value.filter(i => i.id !== removeTarget.value?.id)
      total.value = Math.max(0, total.value - 1)
      toast.success(t('favorites.removed'))
    }
  } catch (e) {
    toast.error(t('common.error'))
  } finally {
    removeTarget.value = null
  }
}

// 跳转商品详情
function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

// 去探索
function goExplore() {
  uni.switchTab({ url: '/pages/index/index' })
}

onMounted(() => {
  loadFavorites()
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
$color-accent-light: #FEF3C7;
$color-success: #059669;
$color-success-light: #D1FAE5;
$color-danger: #DC2626;
$color-danger-light: #FEE2E2;
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

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 行高
$line-height-tight: 1.25;
$line-height-normal: 1.5;

// 圆角
$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-xl: 18px;
$radius-full: 9999px;

// 阴影
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);

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
  color: $color-primary;
  -webkit-font-smoothing: antialiased;
  display: flex;
  flex-direction: column;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: $spacing-xl * 2;
  min-height: 60vh;
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
  line-height: $line-height-normal;
}

.btn-explore {
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

// 收藏列表
.favorites-list {
  flex: 1;
  width: auto;
  padding: $spacing-md;
}

// 商品列表
.goods-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

// 商品项（横向布局）
.goods-item {
  display: flex;
  align-items: center;
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-sm;
  padding: $spacing-md;
  gap: $spacing-md;
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:active {
    transform: scale(0.99);
    background-color: $color-border-light;
  }
}

.goods-image-wrapper {
  position: relative;
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  // background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  box-sizing: border-box;
}

.goods-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background-color: #e6e6e6;
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
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.shipping-badge {
  position: absolute;
  bottom: $spacing-xs;
  left: $spacing-xs;
  padding: 2px 6px;
  background-color: $color-success;
  border-radius: $radius-sm;

  text {
    font-size: $font-size-xs;
    color: #fff;
    font-weight: $font-weight-medium;
  }
}

.goods-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.goods-title {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
  line-height: $line-height-tight;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-meta {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  flex-wrap: wrap;
}

.goods-condition {
  font-size: $font-size-xs;
  color: $color-muted;
  padding: 2px 6px;
  background-color: $color-border-light;
  border-radius: $radius-sm;
}

.goods-location {
  font-size: $font-size-xs;
  color: $color-muted;
}

.promo-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  color: #fff;
  margin-top: $spacing-xs;

  .bi {
    font-size: 10px;
  }
}

.goods-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.price-wrapper {
  display: flex;
  align-items: baseline;
  gap: $spacing-xs;
}

.goods-price {
  font-size: $font-size-lg;
  font-weight: $font-weight-bold;
  color: $color-primary;

  &.promo-price {
    color: #EF4444;
  }
}

.goods-original-price {
  font-size: $font-size-sm;
  color: $color-muted;
  text-decoration: line-through;
}

// 取消收藏按钮
.remove-btn {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f1f1f1;
  border-radius: $radius-full;
  transition: transform 0.2s ease, background-color 0.2s ease;

  .bi {
    font-size: 18px;
    color: $color-accent;
  }

  &:active {
    transform: scale(0.9);
    background-color: #FDE68A; // 比 $color-accent-light (#FEF3C7) 暗 5%
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

.load-more-text {
  font-size: $font-size-sm;
  color: $color-muted;
}

.no-more text {
  font-size: $font-size-sm;
  color: $color-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// 响应式
@media (prefers-reduced-motion: reduce) {
  .loading-spinner,
  .loading-more-spinner,
  .goods-item,
  .remove-btn {
    animation: none;
    transition: none;
  }
}
</style>
