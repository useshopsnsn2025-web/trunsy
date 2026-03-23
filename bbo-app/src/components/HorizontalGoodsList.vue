<template>
  <view v-if="!loading && goods.length > 0" class="horizontal-goods-section">
    <!-- 标题行 -->
    <view class="section-header">
      <text class="section-title">{{ title }}</text>
      <view v-if="showMore" class="see-more" @click="handleSeeMore">
        <text>{{ t('goods.recommendation.seeMore') }}</text>
        <text class="bi bi-chevron-right"></text>
      </view>
    </view>

    <!-- 横向滚动商品列表 -->
    <scroll-view class="goods-scroll" scroll-x :show-scrollbar="false">
      <view class="goods-list">
        <view
          v-for="item in goods"
          :key="item.id"
          class="goods-card-wrapper"
          @click="goGoodsDetail(item.id)"
        >
          <view class="goods-image-wrapper">
            <image
              class="goods-image"
              :src="item.images?.[0] || '/static/placeholder.png'"
              mode="aspectFill"
              lazy-load
            />
            <!-- 已售罄遮罩 -->
            <view v-if="item.stock === 0" class="sold-out-overlay">
              <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
            </view>
            <!-- 活动折扣标签 -->
            <view v-if="item.promotion && item.stock !== 0" class="goods-discount-tag">
              <text>{{ item.promotion.discountPercent }}%</text>
              <text class="off-text">OFF</text>
            </view>
          </view>
          <view class="goods-info">
            <text class="goods-title">{{ item.title }}</text>
            <text class="goods-price" :class="{ 'promo-price': item.promotion }">
              {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
            </text>
            <!-- 有活动时显示原价 -->
            <text v-if="item.promotion" class="goods-original-price">
              {{ formatPrice(item.price, item.currency) }}
            </text>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>

  <!-- 加载状态 -->
  <view v-else-if="loading" class="horizontal-goods-section loading-section">
    <view class="section-header">
      <text class="section-title">{{ title }}</text>
    </view>
    <scroll-view class="goods-scroll" scroll-x :show-scrollbar="false">
      <view class="goods-list">
        <view v-for="i in 4" :key="i" class="goods-card-wrapper skeleton">
          <view class="skeleton-image"></view>
          <view class="skeleton-info">
            <view class="skeleton-title"></view>
            <view class="skeleton-price"></view>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'

interface Goods {
  id: number
  title: string
  price: number
  currency: string
  images: string[]
  stock?: number
  promotion?: {
    promotionPrice: number
    discountPercent: number
  }
}

const props = withDefaults(defineProps<{
  title: string
  goods: Goods[]
  showMore?: boolean
  loading?: boolean
  emptyText?: string
}>(), {
  showMore: false,
  loading: false,
  emptyText: '',
})

const emit = defineEmits<{
  (e: 'seeMore'): void
}>()

const { t } = useI18n()
const appStore = useAppStore()

function formatPrice(amount: number, currency: string): string {
  return appStore.formatPrice(amount, currency)
}

function goGoodsDetail(id: number) {
  uni.navigateTo({
    url: `/pages/goods/detail?id=${id}`,
  })
}

function handleSeeMore() {
  emit('seeMore')
}
</script>

<style lang="scss" scoped>
// 设计系统变量 - 珊瑚橙主题
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;

$font-size-sm: 13px;
$font-weight-medium: 500;
$font-weight-semibold: 600;

$spacing-sm: 8px;
$spacing-base: 16px;
$spacing-md: 12px;
$spacing-lg: 20px;

$radius-lg: 10px;
$radius-full: 9999px;

.horizontal-goods-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-base;
}

.section-title {
  font-size: 18px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.see-more {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: $font-size-sm;
  color: $color-accent;
  cursor: pointer;

  &:active {
    opacity: 0.7;
  }
}

.goods-scroll {
  width: 100%;
  white-space: nowrap;
}

.goods-list {
  display: inline-flex;
  gap: 12px;
  padding-bottom: 4px;
}

.goods-card-wrapper {
  width: 156px;
  background: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  &:active {
    opacity: 0.9;
    transform: scale(0.98);
  }
}

.goods-image-wrapper {
  position: relative;
  width: 156px;
  height: 156px;
  background-color: $color-background;
}

.goods-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: $radius-lg $radius-lg 0 0;
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
  border-radius: $radius-lg $radius-lg 0 0;
}

.sold-out-text {
  color: #fff;
  font-size: 12px;
  font-weight: $font-weight-semibold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.goods-discount-tag {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 3px 8px;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: $radius-full;
  font-size: 11px;
  font-weight: $font-weight-semibold;
  color: #fff;

  .off-text {
    font-size: 10px;
  }
}

.goods-info {
  padding: $spacing-sm $spacing-sm $spacing-md;
}

.goods-title {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  font-size: 13px;
  line-height: 1.4;
  color: $color-primary;
  white-space: normal;
  min-height: 36px;
}

.goods-price {
  display: block;
  font-size: 16px;
  font-weight: $font-weight-semibold;
  color: $color-accent;
  margin-top: 4px;

  &.promo-price {
    color: #EF4444;
  }
}

.goods-original-price {
  display: block;
  font-size: 12px;
  color: $color-muted;
  text-decoration: line-through;
  margin-top: 2px;
}

// 骨架屏样式
.loading-section {
  .skeleton {
    background: $color-surface;
  }

  .skeleton-image {
    width: 156px;
    height: 156px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
  }

  .skeleton-info {
    padding: $spacing-sm;
  }

  .skeleton-title {
    width: 100%;
    height: 32px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
    margin-bottom: 8px;
  }

  .skeleton-price {
    width: 60%;
    height: 20px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
  }
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
