<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('history.title')">
      <template #right>
        <view v-if="items.length > 0" class="nav-action" @click="clearHistory">
          <text class="bi bi-archive"></text>
        </view>
      </template>
    </NavBar>

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && !items.length" class="empty-state">
      <view class="empty-icon-wrap">
        <text class="bi bi-clock-history"></text>
      </view>
      <text class="empty-title">{{ t('history.empty') }}</text>
      <text class="empty-desc">{{ t('history.emptyDesc') }}</text>
      <button class="btn-explore" @click="goExplore">
        {{ t('history.explore') }}
      </button>
    </view>

    <!-- 浏览历史列表 -->
    <scroll-view
      v-if="!loading && items.length > 0"
      class="history-list"
      scroll-y
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 时间分组 -->
      <view v-for="group in groupedItems" :key="group.date" class="history-group">
        <view class="group-header">
          <text class="group-date">{{ group.label }}</text>
          <text class="group-count">{{ group.items.length }} {{ t('history.items') }}</text>
        </view>

        <!-- 商品网格 -->
        <view class="goods-grid">
          <view
            v-for="item in group.items"
            :key="item.id"
            class="goods-card"
            @click="goDetail(item.id)"
          >
            <!-- 商品图片 -->
            <view class="goods-image-wrap">
              <image
                class="goods-image"
                :src="item.images?.[0] || '/static/placeholder.png'"
                mode="aspectFit"
                lazy-load
              />
              <!-- 免运费标签 -->
              <view v-if="item.freeShipping" class="badge-shipping">
                <text>{{ t('goods.freeShipping') }}</text>
              </view>
            </view>

            <!-- 商品信息 -->
            <view class="goods-info">
              <!-- 价格区域 -->
              <view class="price-row">
                <text class="goods-price">{{ formatPrice(getDisplayPrice(item), item.currency) }}</text>
                <text v-if="item.promotion" class="original-price">{{ formatPrice(item.price, item.currency) }}</text>
              </view>
              <!-- 折扣标签 -->
              <view v-if="item.promotion" class="discount-badge">
                <text>-{{ item.promotion.discountPercent }}%</text>
              </view>
              <text class="goods-title">{{ item.title }}</text>
              <view class="goods-meta">
                <text v-if="item.condition" class="goods-condition">{{ getConditionText(item.condition) }}</text>
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
      <view v-else-if="items.length > 0" class="no-more">
        <text>{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 清空历史确认弹窗 -->
    <ConfirmDialog
      :visible="showClearDialog"
      :title="t('history.clearTitle')"
      :content="t('history.clearConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showClearDialog = $event"
      @confirm="confirmClearHistory"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAppStore } from '@/store/modules/app'
import { getBrowseHistory, type Goods } from '@/api/goods'
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
const showClearDialog = ref(false)

// 计算属性
const hasMore = computed(() => items.value.length < total.value)

// 按日期分组
interface GroupedHistory {
  date: string
  label: string
  items: Goods[]
}

const groupedItems = computed((): GroupedHistory[] => {
  const groups: Record<string, Goods[]> = {}
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  items.value.forEach(item => {
    const itemDate = new Date(item.updatedAt || item.createdAt)
    itemDate.setHours(0, 0, 0, 0)
    const dateKey = itemDate.toISOString().split('T')[0]

    if (!groups[dateKey]) {
      groups[dateKey] = []
    }
    groups[dateKey].push(item)
  })

  // 转换为数组并排序
  return Object.entries(groups)
    .sort(([a], [b]) => b.localeCompare(a))
    .map(([date, groupItems]) => {
      const itemDate = new Date(date)
      let label = ''

      if (itemDate.getTime() === today.getTime()) {
        label = t('history.today')
      } else if (itemDate.getTime() === yesterday.getTime()) {
        label = t('history.yesterday')
      } else {
        // 格式化日期
        label = itemDate.toLocaleDateString(undefined, {
          month: 'short',
          day: 'numeric'
        })
      }

      return { date, label, items: groupItems }
    })
})

// 格式化价格
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 获取显示价格（优先显示活动价格）
const getDisplayPrice = (item: Goods) => {
  if (item.promotion && item.promotion.promotionPrice) {
    return item.promotion.promotionPrice
  }
  return item.price
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

// 加载浏览历史
async function loadHistory(isRefresh = false) {
  if (isRefresh) {
    page.value = 1
    refreshing.value = true
  } else if (page.value === 1) {
    loading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const res = await getBrowseHistory({
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
    console.error('Failed to load history:', e)
  } finally {
    loading.value = false
    loadingMore.value = false
    refreshing.value = false
  }
}

// 下拉刷新
function onRefresh() {
  loadHistory(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadHistory()
}

// 清空历史
function clearHistory() {
  showClearDialog.value = true
}

// 确认清空历史
function confirmClearHistory() {
  // TODO: 调用清空历史 API
  items.value = []
  total.value = 0
  toast.success(t('history.cleared'))
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
  loadHistory()
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统 - eBay 风格，简洁高级感
// ==========================================

// 色彩系统
$color-primary: #191919;
$color-secondary: #707070;
$color-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-accent: #FF6B35;

// 字体
$font-size-xs: 11px;
$font-size-sm: 12px;
$font-size-base: 14px;
$font-size-md: 15px;
$font-size-lg: 17px;
$font-size-xl: 20px;

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// 圆角
$radius-sm: 4px;
$radius-md: 8px;
$radius-lg: 12px;
$radius-full: 9999px;

// ==========================================
// 基础样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  color: $color-primary;
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

.empty-icon-wrap {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-surface;
  border-radius: $radius-full;
  margin-bottom: $spacing-lg;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  .bi {
    font-size: 36px;
    color: $color-muted;
  }
}

.empty-title {
  font-size: $font-size-lg;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.empty-desc {
  font-size: $font-size-base;
  color: $color-muted;
  text-align: center;
  margin-bottom: $spacing-xl;
}

.btn-explore {
  background-color: $color-primary;
  color: #fff;
  font-size: $font-size-md;
  font-weight: 500;
  padding: $spacing-md $spacing-xl;
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

// 历史列表
.history-list {
  width: auto;
  flex: 1;
  padding: 0 $spacing-base;
}

// 时间分组
.history-group {
  margin-bottom: $spacing-lg;
}

.group-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base 0 $spacing-md;
}

.group-date {
  font-size: $font-size-md;
  font-weight: 600;
  color: $color-primary;
}

.group-count {
  font-size: $font-size-sm;
  color: $color-muted;
}

// 商品网格 - 两列布局
.goods-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: $spacing-md;
}

// 商品卡片
.goods-card {
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
  transition: transform 0.2s;

  &:active {
    transform: scale(0.98);
  }
}

.goods-image-wrap {
  position: relative;
  width: 100%;
  padding-bottom: 100%; // 1:1 比例
  // background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  border-radius: 8px;
}

.goods-image {
  position: absolute;
  top: 0px;
  left:0px;
  right:0px;
  bottom: 0px;
  width: 100%;
  height: 100%;
  object-fit: contain;
  background-color: #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.badge-shipping {
  position: absolute;
  bottom: $spacing-sm;
  left: $spacing-sm;
  padding: 4px 8px;
  background-color: #059669;
  border-radius: $radius-sm;
  display: flex;
  align-items: center;

  text {
    font-size: $font-size-xs;
    color: #fff;
    font-weight: 500;
  }
}

.goods-info {
  padding: $spacing-md;
}

.price-row {
  display: flex;
  align-items: baseline;
  gap: $spacing-xs;
  margin-bottom: $spacing-xs;
}

.goods-price {
  font-size: $font-size-md;
  font-weight: 700;
  color: $color-primary;
}

.original-price {
  font-size: $font-size-xs;
  color: $color-muted;
  text-decoration: line-through;
}

.discount-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1px 4px;
  background-color: $color-accent;
  border-radius: $radius-sm;
  margin-bottom: $spacing-xs;

  text {
    font-size: 10px;
    color: #fff;
    font-weight: 600;
    line-height: 1;
  }
}

.goods-title {
  display: block;
  font-size: $font-size-sm;
  color: $color-secondary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: $spacing-xs;
}

.goods-meta {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.goods-condition {
  font-size: $font-size-xs;
  color: $color-muted;
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
  width: 20px;
  height: 20px;
  border: 2px solid $color-border;
  border-top-color: $color-accent;
  border-radius: $radius-full;
  animation: spin 0.8s linear infinite;
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

// 动画简化
@media (prefers-reduced-motion: reduce) {
  .loading-spinner,
  .loading-more-spinner,
  .goods-card {
    animation: none;
    transition: none;
  }
}
</style>
