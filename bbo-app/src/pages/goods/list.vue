<template>
  <view class="page">
    <!-- 排序筛选栏 -->
    <view class="filter-bar">
      <view
        v-for="item in sortOptions"
        :key="item.value"
        class="filter-item"
        :class="{ active: currentSort === item.value }"
        @click="changeSort(item.value)"
      >
        <text>{{ item.label }}</text>
      </view>
    </view>

    <!-- 商品列表 -->
    <scroll-view
      class="goods-list"
      scroll-y
      :style="{ height: scrollHeight }"
      @scrolltolower="loadMore"
      refresher-enabled
      :refresher-triggered="isRefreshing"
      @refresherrefresh="onRefresh"
    >
      <view v-if="goods.length === 0 && !loading" class="empty-state">
        <text>{{ t('goods.noGoods') }}</text>
      </view>

      <view v-else class="goods-grid">
        <view
          v-for="item in goods"
          :key="item.id"
          class="goods-card"
          @click="goDetail(item.id)"
        >
          <view class="goods-image-wrapper">
            <image
              class="goods-image"
              :src="item.images[0]"
              mode="aspectFit"
              lazy-load
            />
            <!-- 已售罄遮罩 -->
            <view v-if="item.stock === 0" class="sold-out-overlay">
              <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
            </view>
          </view>
          <view class="goods-info">
            <text class="goods-title">{{ item.title }}</text>
            <view class="goods-meta">
              <text class="goods-price">{{ formatPrice(item.price, item.currency) }}</text>
            </view>
            <view class="goods-footer">
              <text class="goods-location">{{ item.locationCity }}</text>
              <text class="goods-likes">{{ item.likes }}</text>
            </view>
          </view>
        </view>
      </view>

      <view v-if="loading" class="loading">
        <text>{{ t('common.loading') }}</text>
      </view>
      <view v-else-if="noMore && goods.length > 0" class="no-more">
        <text>{{ t('common.noMore') }}</text>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/modules/app'
import { getGoodsList, type Goods } from '@/api/goods'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const appStore = useAppStore()

const categoryId = ref<number>(0)
const brandValue = ref<string>('')
const goods = ref<Goods[]>([])
const loading = ref(false)
const isRefreshing = ref(false)
const noMore = ref(false)
const page = ref(1)
const pageSize = 20
const currentSort = ref<string>('newest')

const sortOptions = [
  { label: t('goods.sortNewest'), value: 'newest' },
  { label: t('goods.sortPriceAsc'), value: 'price_asc' },
  { label: t('goods.sortPriceDesc'), value: 'price_desc' },
  { label: t('goods.sortPopular'), value: 'popular' },
]

// 计算滚动区域高度（减去筛选栏高度）
const scrollHeight = computed(() => {
  const filterBarHeight = 44 // 筛选栏高度: 12px padding * 2 + 约20px内容
  return `calc(100vh - ${filterBarHeight}px)`
})

// 使用 formatPrice 进行汇率转换后显示
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

async function loadGoods(isRefresh = false) {
  if (loading.value) return

  if (isRefresh) {
    page.value = 1
    noMore.value = false
  }

  loading.value = true

  try {
    const res = await getGoodsList({
      page: page.value,
      pageSize,
      categoryId: categoryId.value || undefined,
      brand: brandValue.value || undefined,
      sort: currentSort.value as any,
    })

    if (isRefresh) {
      goods.value = res.data.list
    } else {
      goods.value = [...goods.value, ...res.data.list]
    }

    if (res.data.list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Failed to load goods:', e)
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

function changeSort(sort: string) {
  if (currentSort.value === sort) return
  currentSort.value = sort
  loadGoods(true)
}

function onRefresh() {
  isRefreshing.value = true
  loadGoods(true)
}

function loadMore() {
  if (noMore.value || loading.value) return
  page.value++
  loadGoods()
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

onLoad((options) => {
  if (options?.categoryId) {
    categoryId.value = parseInt(options.categoryId)
  }
  if (options?.brand) {
    brandValue.value = decodeURIComponent(options.brand)
  }
  if (options?.title) {
    uni.setNavigationBarTitle({ title: decodeURIComponent(options.title) })
  }
  loadGoods()
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.filter-bar {
  display: flex;
  background-color: #fff;
  padding: 12px 16px;
  gap: 24px;
}

.filter-item {
  font-size: 14px;
  color: #666;

  &.active {
    color: #FF6B35;
    font-weight: 500;
  }
}

.goods-list {
  /* 高度通过 :style 动态计算，减去筛选栏高度 */
  width: auto;
  padding: 8px;
}

.empty-state {
  text-align: center;
  padding: 60px 0;
  font-size: 14px;
  color: #999;
}

.goods-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.goods-card {
  width: calc(50% - 4px);
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
}

.goods-image-wrapper {
  position: relative;
  width: 100%;
  height: 180px;
  padding: 12px;
  background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  border-radius: 8px;
}

.goods-image {
  width: 100%;
  height: 100%;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
}

.sold-out-text {
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.goods-info {
  padding: 8px;
}

.goods-title {
  font-size: 14px;
  color: #333;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  line-height: 1.4;
}

.goods-meta {
  margin-top: 8px;
}

.goods-price {
  font-size: 16px;
  font-weight: 600;
  color: #FF6B35;
}

.goods-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 12px;
  color: #999;
}

.loading, .no-more {
  text-align: center;
  padding: 16px;
  font-size: 14px;
  color: #999;
}
</style>
