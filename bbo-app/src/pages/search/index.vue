<template>
  <view class="page">
    <!-- 顶部搜索栏 -->
    <view class="search-header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="search-bar">
        <view class="search-input-container">
          <text class="bi bi-search search-icon"></text>
          <input
            class="search-input"
            type="text"
            v-model="keyword"
            :placeholder="t('search.placeholder')"
            :focus="inputFocused"
            confirm-type="search"
            :adjust-position="false"
            @focus="onInputFocus"
            @input="onInputChange"
            @confirm="doSearch"
          />
          <view v-if="keyword" class="clear-btn" @click="clearKeyword">
            <text class="bi bi-x-circle-fill"></text>
          </view>
        </view>
        <text class="cancel-btn" @click="goBack">{{ t('common.cancel') }}</text>
      </view>
    </view>

    <!-- 搜索建议（输入时显示） -->
    <scroll-view
      v-if="keyword && !hasSearched && suggestions.length > 0"
      scroll-y
      class="suggestions-section"
      :show-scrollbar="false"
    >
      <view
        v-for="(item, index) in suggestions"
        :key="index"
        class="suggestion-item"
        @click="searchByKeyword(item.text)"
      >
        <text class="bi bi-search suggestion-icon"></text>
        <view class="suggestion-content">
          <view class="suggestion-text">{{ item.text }}</view>
          <text v-if="item.category" class="suggestion-category">{{ formatSearchInText(item.category) }}</text>
        </view>
        <text class="bi bi-arrow-up-left suggestion-arrow"></text>
      </view>
    </scroll-view>

    <!-- 默认内容（未搜索时） -->
    <scroll-view
      v-if="!keyword && !hasSearched"
      scroll-y
      class="default-content"
      :show-scrollbar="false"
    >
      <!-- 搜索历史 -->
      <view v-if="searchHistory.length > 0" class="section history-section">
        <view class="section-header">
          <text class="section-title">{{ t('search.history') }}</text>
          <view class="clear-history-btn" @click="showClearConfirm">
            <text class="bi bi-archive"></text>
          </view>
        </view>
        <view class="history-list">
          <view
            v-for="(item, index) in searchHistory.slice(0, 8)"
            :key="index"
            class="history-item"
            @click="searchByKeyword(item)"
          >
            <text class="bi bi-clock-history history-icon"></text>
            <text class="history-text">{{ item }}</text>
          </view>
        </view>
      </view>

      <!-- 热门搜索/趋势 -->
      <view v-if="trendingSearches.length > 0" class="section trending-section">
        <view class="section-header">
          <text class="section-title">{{ t('search.trendingSearches') }}</text>
          <view class="refresh-btn" @click="refreshTrending">
            <text class="bi bi-arrow-clockwise" :class="{ spinning: refreshingTrending }"></text>
          </view>
        </view>
        <view class="trending-grid">
          <view
            v-for="(item, index) in trendingSearches"
            :key="index"
            class="trending-item"
            @click="searchByKeyword(item)"
          >
            <view class="trending-rank" :class="getRankClass(index)">
              <text v-if="index < 3" class="bi bi-fire"></text>
              <text v-else>{{ index + 1 }}</text>
            </view>
            <view class="trending-content">
              <text class="trending-keyword">{{ item }}</text>
            </view>
            <view v-if="index < 3" class="hot-badge">HOT</view>
          </view>
        </view>
      </view>

      <!-- 热门分类 -->
      <view v-if="popularCategories.length > 0" class="section categories-section">
        <view class="section-header">
          <text class="section-title">{{ t('search.categories') }}</text>
        </view>
        <view class="categories-grid">
          <view
            v-for="cat in popularCategories"
            :key="cat.id"
            class="category-card"
            @click="searchInCategory(cat)"
          >
            <view class="category-image-wrapper">
              <image
                class="category-image"
                :src="cat.icon || cat.image || '/static/category-default.png'"
                mode="aspectFit"
              />
            </view>
            <text class="category-name">{{ cat.name }}</text>
          </view>
        </view>
      </view>

      <!-- 推荐搜索词 -->
      <view class="section discover-section">
        <view class="section-header">
          <text class="section-title">{{ t('search.trySearching') }}</text>
        </view>
        <view class="discover-tags">
          <view
            v-for="(tag, index) in discoverTags"
            :key="index"
            class="discover-tag"
            @click="searchByKeyword(tag)"
          >
            <text>{{ tag }}</text>
          </view>
        </view>
      </view>

      <!-- 底部安全区 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 搜索结果 -->
    <view v-if="hasSearched" class="results-container">
      <!-- 结果头部 -->
      <view class="results-header">
        <text class="results-title">{{ resultsForText }}</text>
        <text class="results-count">{{ resultsCountText }}</text>
      </view>

      <!-- 筛选排序栏 -->
      <view class="filter-bar">
        <view class="filter-left">
          <view class="sort-dropdown" @click="openSortSheet">
            <text class="bi bi-arrow-down-up"></text>
            <text class="sort-label">{{ currentSortLabel }}</text>
            <text class="bi bi-chevron-down sort-arrow"></text>
          </view>
        </view>
        <view class="filter-right">
          <view class="filter-btn" @click="openFilter">
            <text class="bi bi-sliders2"></text>
            <text>{{ t('search.filterResults') }}</text>
            <view v-if="activeFiltersCount > 0" class="filter-badge">{{ activeFiltersCount }}</view>
          </view>
        </view>
      </view>

      <!-- 排序选择弹窗 -->
      <view v-if="showSortSheet" class="sort-sheet-overlay" @click="showSortSheet = false">
        <view class="sort-sheet" @click.stop>
          <view class="sort-sheet-header">
            <text class="sort-sheet-title">{{ t('search.sortBy') }}</text>
            <view class="sort-sheet-close" @click="showSortSheet = false">
              <text class="bi bi-x-lg"></text>
            </view>
          </view>
          <view class="sort-sheet-options">
            <view
              v-for="(option, index) in sortOptions"
              :key="index"
              class="sort-sheet-option"
              :class="{ active: currentSort === option.value }"
              @click="selectSort(option.value)"
            >
              <text class="sort-sheet-option-label">{{ option.label }}</text>
              <text v-if="currentSort === option.value" class="bi bi-check-lg sort-sheet-check"></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 筛选弹窗 -->
      <view v-if="showFilterSheet" class="filter-sheet-overlay" @click="showFilterSheet = false">
        <view class="filter-sheet" @click.stop>
          <view class="filter-sheet-header">
            <text class="filter-sheet-title">{{ t('search.filterResults') }}</text>
            <view class="filter-sheet-close" @click="showFilterSheet = false">
              <text class="bi bi-x-lg"></text>
            </view>
          </view>
          <scroll-view scroll-y class="filter-sheet-content">
            <!-- 价格范围 -->
            <view class="filter-section">
              <text class="filter-section-title">{{ t('search.priceRange') }} ({{ userCurrency }})</text>
              <view class="price-range-inputs">
                <view class="price-input-wrapper">
                  <text class="price-currency">{{ currencySymbol }}</text>
                  <input
                    class="price-input"
                    type="number"
                    v-model="tempFilters.minPrice"
                    :placeholder="t('search.minPrice')"
                  />
                </view>
                <text class="price-separator">-</text>
                <view class="price-input-wrapper">
                  <text class="price-currency">{{ currencySymbol }}</text>
                  <input
                    class="price-input"
                    type="number"
                    v-model="tempFilters.maxPrice"
                    :placeholder="t('search.maxPrice')"
                  />
                </view>
              </view>
            </view>

            <!-- 商品成色 -->
            <view class="filter-section">
              <text class="filter-section-title">{{ t('search.condition') }}</text>
              <view class="condition-options">
                <view
                  class="condition-option"
                  :class="{ active: tempFilters.condition === 0 }"
                  @click="tempFilters.condition = 0"
                >
                  <text>{{ t('search.allConditions') }}</text>
                </view>
                <view
                  v-for="opt in conditionOptions"
                  :key="opt.value"
                  class="condition-option"
                  :class="{ active: tempFilters.condition === opt.value }"
                  @click="tempFilters.condition = opt.value"
                >
                  <text>{{ opt.label }}</text>
                </view>
              </view>
            </view>
          </scroll-view>
          <view class="filter-sheet-footer">
            <view class="filter-reset-btn" @click="resetFilters">
              <text>{{ t('search.resetFilter') }}</text>
            </view>
            <view class="filter-apply-btn" @click="applyFilters">
              <text>{{ t('search.applyFilter') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 结果列表 -->
      <scroll-view
        scroll-y
        class="results-scroll"
        @scrolltolower="loadMore"
        refresher-enabled
        :refresher-triggered="isRefreshing"
        @refresherrefresh="onRefresh"
        :show-scrollbar="false"
      >
        <!-- 加载中 -->
        <view v-if="loading && goods.length === 0" class="loading-state">
          <view class="loader">
            <view class="loader-ring"></view>
          </view>
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <!-- 空结果 -->
        <view v-else-if="goods.length === 0" class="empty-state">
          <view class="empty-icon">
            <text class="bi bi-search"></text>
          </view>
          <text class="empty-title">{{ t('search.noResults') }}</text>
          <text class="empty-desc">{{ t('search.trySearching') }}</text>
          <view class="empty-suggestions">
            <view
              v-for="(tag, index) in discoverTags.slice(0, 4)"
              :key="index"
              class="empty-tag"
              @click="searchByKeyword(tag)"
            >
              {{ tag }}
            </view>
          </view>
        </view>

        <!-- 商品网格 -->
        <view v-else class="goods-grid">
          <view
            v-for="(item, index) in goods"
            :key="item.id"
            class="goods-card"
            :style="{ animationDelay: `${index * 30}ms` }"
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
              <view v-if="item.promotion && item.stock > 0" class="promo-badge">
                <text class="bi bi-lightning-fill"></text>
                {{ item.promotion.discountPercent }}% OFF
              </view>
              <view v-if="item.freeShipping && item.stock > 0" class="shipping-badge">
                <text class="bi bi-truck"></text>
                Free
              </view>
            </view>
            <view class="goods-info">
              <text class="goods-title">{{ item.title }}</text>
              <view class="goods-price-row">
                <text class="goods-price" :class="{ promo: item.promotion }">
                  {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                </text>
                <text v-if="item.promotion" class="goods-original-price">
                  {{ formatPrice(item.price, item.currency) }}
                </text>
              </view>
              <view class="goods-meta">
                <view v-if="item.condition" class="condition-tag">
                  {{ getConditionLabel(item.condition) }}
                </view>
                <text v-if="!item.freeShipping" class="shipping-fee">
                  +{{ formatPrice(item.shippingFee, item.currency) }}
                </text>
              </view>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="loading && goods.length > 0" class="loading-more">
          <view class="loader-small">
            <view class="loader-ring"></view>
          </view>
          <text>{{ t('common.loading') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="noMore && goods.length > 0" class="no-more">
          <view class="divider"></view>
          <text>{{ t('common.noMore') }}</text>
          <view class="divider"></view>
        </view>

        <view class="safe-bottom"></view>
      </scroll-view>
    </view>

    <!-- 清除历史确认弹窗 -->
    <view v-if="showClearDialog" class="dialog-overlay" @click="showClearDialog = false">
      <view class="dialog-content" @click.stop>
        <text class="dialog-title">{{ t('search.clearHistory') }}</text>
        <text class="dialog-message">{{ t('search.clearHistoryConfirm') }}</text>
        <view class="dialog-actions">
          <view class="dialog-btn cancel" @click="showClearDialog = false">
            <text>{{ t('common.cancel') }}</text>
          </view>
          <view class="dialog-btn confirm" @click="doClearHistory">
            <text>{{ t('common.confirm') }}</text>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAppStore } from '@/store/modules/app'
import { getGoodsList, getHotSearches, getSearchSuggestions, getHotCategories, type Goods, type Category } from '@/api/goods'
import { getSearchHistory, addSearchHistory, clearSearchHistory } from '@/utils/storage'
import { useI18n } from 'vue-i18n'
import { convertAmount, getCurrencySymbol } from '@/utils/currency'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 搜索状态
const keyword = ref('')
const inputFocused = ref(true)
const searchHistory = ref<string[]>([])
const suggestions = ref<{ text: string; category?: string }[]>([])
const hasSearched = ref(false)
const showClearDialog = ref(false)

// 热门搜索数据 - 从 API 获取
const trendingSearches = ref<string[]>([])
const loadingTrending = ref(false)
const refreshingTrending = ref(false)

// 热门分类 - 从 API 获取
const hotCategories = ref<Category[]>([])
const loadingCategories = ref(false)

// 热门分类（使用真实数据）
const popularCategories = computed(() => {
  return hotCategories.value.slice(0, 6).map(cat => ({
    id: cat.id,
    name: cat.name,
    icon: cat.icon || '',
    image: cat.image || ''
  }))
})

// 发现标签 - 使用 i18n
const discoverTags = computed(() => [
  t('search.tagWirelessEarbuds'),
  t('search.tagSmartWatch'),
  t('search.tagCameraLens'),
  t('search.tagSneakers'),
  t('search.tagVintage'),
  t('search.tagLimitedEdition'),
  t('search.tagUnder50'),
  t('search.tagBrandNew'),
])

// 商品列表
const goods = ref<Goods[]>([])
const loading = ref(false)
const isRefreshing = ref(false)
const noMore = ref(false)
const page = ref(1)
const pageSize = 20
const totalResults = ref(0)

// 排序和筛选
const currentSort = ref('relevance')
const showSortSheet = ref(false)
const showFilterSheet = ref(false)

// 筛选状态
const filters = reactive({
  minPrice: '',
  maxPrice: '',
  condition: 0
})
const tempFilters = reactive({
  minPrice: '',
  maxPrice: '',
  condition: 0
})

// 激活的筛选数量
const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.minPrice || filters.maxPrice) count++
  if (filters.condition > 0) count++
  return count
})
const sortOptions = computed(() => [
  { label: t('search.relevance'), value: 'relevance' },
  { label: t('search.newest'), value: 'newest' },
  { label: t('search.priceLowHigh'), value: 'price_asc' },
  { label: t('search.priceHighLow'), value: 'price_desc' },
])

// 成色选项
const conditionOptions = computed(() => [
  { value: 1, label: t('goods.conditionNew') },
  { value: 2, label: t('goods.conditionLikeNew') },
  { value: 3, label: t('goods.conditionGood') },
  { value: 4, label: t('goods.conditionFair') },
  { value: 5, label: t('goods.conditionPoor') },
])

// 当前排序标签
const currentSortLabel = computed(() => {
  const option = sortOptions.value.find(o => o.value === currentSort.value)
  return option?.label || t('search.relevance')
})

const statusBarHeight = computed(() => appStore.statusBarHeight)

// 搜索结果标题文本（APP端和H5端分别处理）
const resultsForText = computed(() => {
  // 尝试使用 vue-i18n 插值
  const interpolated = t('search.resultsFor', { keyword: keyword.value })

  // 如果插值失败（仍包含占位符），说明是 APP端，需要手动替换
  if (interpolated.includes('[KEYWORD]')) {
    const template = t('search.resultsFor')
    return template.replace('[KEYWORD]', keyword.value)
  }

  // H5端正常返回插值结果
  return interpolated
})

const resultsCountText = computed(() => {
  // 尝试使用 vue-i18n 插值
  const interpolated = t('search.resultsCount', { count: totalResults.value })

  // 如果插值失败（仍包含占位符），说明是 APP端，需要手动替换
  if (interpolated.includes('[COUNT]')) {
    const template = t('search.resultsCount')
    return template.replace('[COUNT]', String(totalResults.value))
  }

  // H5端正常返回插值结果
  return interpolated
})

// 当前用户货币
const userCurrency = computed(() => appStore.currency)
const currencySymbol = computed(() => getCurrencySymbol(userCurrency.value))

// 将用户货币金额转换为 USD（用于 API 请求）
function convertToUSD(amount: number): number {
  if (!amount) return 0
  const rates = appStore.exchangeRates
  // 如果用户货币就是 USD，无需转换
  if (userCurrency.value === 'USD') return amount
  // 如果没有汇率数据，先尝试获取
  if (Object.keys(rates).length === 0) {
    console.warn('Exchange rates not loaded, price filter may be inaccurate')
    return amount
  }
  // 从用户货币转换到 USD
  return convertAmount(amount, userCurrency.value, 'USD', rates)
}

// 格式化价格
function formatPrice(amount: number, currency: string) {
  return appStore.formatPrice(amount, currency)
}

// 获取成色标签
function getConditionLabel(condition: number): string {
  const keys: Record<number, string> = {
    1: 'conditionNew',
    2: 'conditionLikeNew',
    3: 'conditionGood',
    4: 'conditionFair',
    5: 'conditionPoor'
  }
  return keys[condition] ? t(`goods.${keys[condition]}`) : ''
}

// 格式化搜索分类文本（手动替换占位符，解决 UniApp APP 端 vue-i18n 插值不生效问题）
function formatSearchInText(category: string): string {
  const template = t('search.searchIn')
  return template.replace('[CATEGORY]', category)
}

// 获取排名样式
function getRankClass(index: number) {
  if (index === 0) return 'rank-1'
  if (index === 1) return 'rank-2'
  if (index === 2) return 'rank-3'
  return ''
}

// 输入框聚焦
function onInputFocus() {
  inputFocused.value = true
}

// 输入变化时获取建议
let suggestTimer: ReturnType<typeof setTimeout> | null = null
async function onInputChange() {
  if (keyword.value.length >= 2) {
    // 防抖处理
    if (suggestTimer) {
      clearTimeout(suggestTimer)
    }
    suggestTimer = setTimeout(async () => {
      try {
        const res = await getSearchSuggestions(keyword.value)
        suggestions.value = res.data.map((text: string) => ({ text }))
      } catch {
        // 如果 API 失败，使用简单建议
        suggestions.value = [{ text: keyword.value }]
      }
    }, 300)
  } else {
    suggestions.value = []
  }
  hasSearched.value = false
}

// 清除关键词
function clearKeyword() {
  keyword.value = ''
  suggestions.value = []
  hasSearched.value = false
  goods.value = []
}

// 加载搜索历史
function loadHistory() {
  searchHistory.value = getSearchHistory()
}

// 保存搜索历史
function saveHistory(kw: string) {
  addSearchHistory(kw)
  // 重新加载以保持同步
  searchHistory.value = getSearchHistory()
}

// 显示清除确认
function showClearConfirm() {
  showClearDialog.value = true
}

// 清除历史
function doClearHistory() {
  clearSearchHistory()
  searchHistory.value = []
  showClearDialog.value = false
}

// 加载热门搜索
async function loadTrendingSearches() {
  if (loadingTrending.value) return
  loadingTrending.value = true
  try {
    const res = await getHotSearches()
    trendingSearches.value = res.data || []
  } catch {
    console.error('Failed to load trending searches')
  } finally {
    loadingTrending.value = false
  }
}

// 加载热门分类
async function loadHotCategories() {
  if (loadingCategories.value) return
  loadingCategories.value = true
  try {
    const res = await getHotCategories()
    hotCategories.value = res.data || []
  } catch {
    console.error('Failed to load hot categories')
  } finally {
    loadingCategories.value = false
  }
}

// 刷新热门搜索
async function refreshTrending() {
  refreshingTrending.value = true
  try {
    const res = await getHotSearches()
    trendingSearches.value = res.data || []
  } catch {
    // 如果刷新失败，随机打乱现有顺序
    trendingSearches.value = [...trendingSearches.value].sort(() => Math.random() - 0.5)
  } finally {
    refreshingTrending.value = false
  }
}

// 执行搜索
async function doSearch() {
  if (!keyword.value.trim()) return

  saveHistory(keyword.value.trim())
  hasSearched.value = true
  suggestions.value = []
  page.value = 1
  noMore.value = false
  await loadGoods(true)
}

// 通过关键词搜索
function searchByKeyword(kw: string) {
  keyword.value = kw
  doSearch()
}

// 在分类中搜索
function searchInCategory(cat: { id: number; name: string }) {
  // 跳转到分类品牌页面
  uni.navigateTo({ url: `/pages/category/brands?id=${cat.id}&title=${encodeURIComponent(cat.name)}` })
}

// 打开排序弹窗
function openSortSheet() {
  showSortSheet.value = true
}

// 选择排序
function selectSort(sort: string) {
  currentSort.value = sort
  showSortSheet.value = false
  if (hasSearched.value) {
    loadGoods(true)
  }
}

// 打开筛选
function openFilter() {
  // 复制当前筛选到临时筛选
  tempFilters.minPrice = filters.minPrice
  tempFilters.maxPrice = filters.maxPrice
  tempFilters.condition = filters.condition
  showFilterSheet.value = true
}

// 重置筛选
function resetFilters() {
  tempFilters.minPrice = ''
  tempFilters.maxPrice = ''
  tempFilters.condition = 0
}

// 应用筛选
function applyFilters() {
  // 复制临时筛选到正式筛选
  filters.minPrice = tempFilters.minPrice
  filters.maxPrice = tempFilters.maxPrice
  filters.condition = tempFilters.condition
  showFilterSheet.value = false
  // 重新加载商品
  if (hasSearched.value) {
    loadGoods(true)
  }
}

// 加载商品
async function loadGoods(isRefresh = false) {
  if (loading.value) return

  if (isRefresh) {
    page.value = 1
    noMore.value = false
  }

  loading.value = true

  try {
    // 构建请求参数
    const params: Record<string, unknown> = {
      page: page.value,
      pageSize,
      keyword: keyword.value,
      sort: currentSort.value,
    }
    // 添加筛选参数（价格需要从用户货币转换为 USD）
    if (filters.minPrice) {
      const minPriceUSD = convertToUSD(Number(filters.minPrice))
      params.minPrice = Math.floor(minPriceUSD * 100) / 100 // 保留两位小数
    }
    if (filters.maxPrice) {
      const maxPriceUSD = convertToUSD(Number(filters.maxPrice))
      params.maxPrice = Math.ceil(maxPriceUSD * 100) / 100 // 保留两位小数，向上取整
    }
    if (filters.condition > 0) {
      params.condition = filters.condition
    }

    const res = await getGoodsList(params as Parameters<typeof getGoodsList>[0])

    if (isRefresh) {
      goods.value = res.data.list
    } else {
      goods.value = [...goods.value, ...res.data.list]
    }

    totalResults.value = res.data.total

    if (res.data.list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Search failed:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

// 下拉刷新
function onRefresh() {
  isRefreshing.value = true
  loadGoods(true)
}

// 加载更多
function loadMore() {
  if (noMore.value || loading.value) return
  page.value++
  loadGoods()
}

// 返回
function goBack() {
  uni.navigateBack()
}

// 跳转详情
function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

// 监听关键词变化
watch(keyword, (newVal) => {
  if (!newVal) {
    hasSearched.value = false
    goods.value = []
  }
})

onMounted(() => {
  appStore.initSystemInfo()
  loadHistory()
  // 加载热门搜索和热门分类
  loadTrendingSearches()
  loadHotCategories()
  // 加载汇率数据（用于价格筛选转换）
  if (Object.keys(appStore.exchangeRates).length === 0) {
    appStore.fetchExchangeRates()
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$primary: #FF6B35;
$primary-light: #FF8555;
$text-primary: #1E293B;
$text-secondary: #64748B;
$text-muted: #94A3B8;
$background: #F8FAFC;
$surface: #FFFFFF;
$border: #E2E8F0;
$success: #10B981;
$error: #EF4444;

$radius-sm: 8px;
$radius-md: 12px;
$radius-lg: 16px;
$radius-full: 9999px;

$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
$shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);

.page {
  height: 100vh;
  background-color: $background;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

// 搜索头部
.search-header {
  background-color: $surface;
  padding-bottom: 12px;
  flex-shrink: 0;
  z-index: 100;
}

.search-bar {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  gap: 12px;
}

.search-input-container {
  flex: 1;
  height: 44px;
  background-color: $background;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  padding: 0 16px;
  transition: all 0.2s ease;

  &:focus-within {
    background-color: $surface;
    box-shadow: 0 0 0 2px rgba($primary, 0.2);
  }
}

.search-icon {
  font-size: 18px;
  color: $text-muted;
  margin-right: 12px;
}

.search-input {
  flex: 1;
  height: 100%;
  font-size: 16px;
  color: $text-primary;
}

.clear-btn {
  padding: 4px;

  .bi {
    font-size: 18px;
    color: $text-muted;
  }
}

.cancel-btn {
  font-size: 16px;
  color: $primary;
  font-weight: 500;
}

// 搜索建议
.suggestions-section {
  background-color: $surface;
  border-top: 1px solid $border;
  flex: 1;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  gap: 12px;
  border-bottom: 1px solid rgba($border, 0.5);

  &:active {
    background-color: $background;
  }
}

.suggestion-icon {
  font-size: 16px;
  color: $text-muted;
}

.suggestion-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.suggestion-text {
  font-size: 15px;
  color: $text-primary;
}

.suggestion-category {
  font-size: 12px;
  color: $text-muted;
}

.suggestion-arrow {
  font-size: 14px;
  color: $text-muted;
}

// 默认内容
.default-content {
  flex: 1;
  overflow-y: auto;
}

// 通用 section 样式
.section {
  background-color: $surface;
  margin-bottom: 8px;
  padding: 16px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-primary;
}

// 搜索历史
.clear-history-btn,
.refresh-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-full;

  &:active {
    background-color: $background;
  }

  .bi {
    font-size: 18px;
    color: $text-muted;
  }

  .spinning {
    animation: spin 0.5s linear;
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.history-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  background-color: $background;
  border-radius: $radius-full;

  &:active {
    background-color: rgba($primary, 0.1);
  }
}

.history-icon {
  font-size: 14px;
  color: $text-muted;
}

.history-text {
  font-size: 14px;
  color: $text-secondary;
}

// 热门搜索
.trending-grid {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.trending-item {
  display: flex;
  align-items: center;
  padding: 12px 0;
  gap: 12px;
  border-bottom: 1px solid rgba($border, 0.5);

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: $background;
    margin: 0 -16px;
    padding: 12px 16px;
  }
}

.trending-rank {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: $text-muted;
  border-radius: $radius-sm;

  &.rank-1 {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: white;
  }

  &.rank-2 {
    background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
    color: white;
  }

  &.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #B87333);
    color: white;
  }
}

.trending-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.trending-keyword {
  font-size: 15px;
  color: $text-primary;
  font-weight: 500;
}

.trending-change {
  font-size: 12px;
  color: $error;
  display: flex;
  align-items: center;
  gap: 2px;

  &.up {
    color: $success;
  }

  .bi {
    font-size: 10px;
  }
}

.hot-badge {
  padding: 4px 8px;
  background: linear-gradient(135deg, $primary, $primary-light);
  color: white;
  font-size: 10px;
  font-weight: 700;
  border-radius: $radius-sm;
}

// 分类网格
.categories-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;

  &:active {
    opacity: 0.7;
  }
}

.category-image-wrapper {
  width: 64px;
  height: 64px;
  border-radius: $radius-md;
  overflow: hidden;
  background-color: $background;
}

.category-image {
  width: 100%;
  height: 100%;
}

.category-name {
  font-size: 13px;
  color: $text-secondary;
  text-align: center;
}

// 发现标签
.discover-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.discover-tag {
  padding: 10px 16px;
  background-color: $background;
  border: 1px solid $border;
  border-radius: $radius-full;
  font-size: 14px;
  color: $text-secondary;

  &:active {
    background-color: rgba($primary, 0.1);
    border-color: $primary;
    color: $primary;
  }
}

// 搜索结果
.results-container {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}

.results-header {
  background-color: $surface;
  padding: 12px 16px;
  border-bottom: 1px solid $border;
}

.results-title {
  font-size: 15px;
  color: $text-primary;
  font-weight: 500;
  display: block;
}

.results-count {
  font-size: 13px;
  color: $text-muted;
}

// 筛选栏
.filter-bar {
  background-color: $surface;
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid $border;
}

.filter-left,
.filter-right {
  display: flex;
  align-items: center;
}

// 排序下拉按钮
.sort-dropdown {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background-color: $background;
  border-radius: $radius-full;
  font-size: 14px;
  color: $text-primary;

  .bi {
    font-size: 14px;
    color: $text-secondary;
  }

  .sort-label {
    font-weight: 500;
  }

  .sort-arrow {
    font-size: 12px;
    color: $text-muted;
    transition: transform 0.2s ease;
  }

  &:active {
    background-color: #EDF2F7; // 比 $background (#F8FAFC) 暗 3%
  }
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background-color: $background;
  border-radius: $radius-full;
  font-size: 14px;
  color: $text-primary;
  position: relative;

  .bi {
    font-size: 14px;
    color: $text-secondary;
  }

  &:active {
    background-color: #EDF2F7; // 比 $background (#F8FAFC) 暗 3%
  }
}

.filter-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 18px;
  height: 18px;
  background-color: $primary;
  color: white;
  font-size: 11px;
  font-weight: 600;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

// 排序弹窗
.sort-sheet-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.sort-sheet {
  width: 100%;
  background-color: $surface;
  border-radius: $radius-lg $radius-lg 0 0;
  padding-bottom: env(safe-area-inset-bottom);
  animation: slideUp 0.25s ease-out;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.sort-sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid $border;
}

.sort-sheet-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-primary;
}

.sort-sheet-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-full;
  background-color: $background;

  .bi {
    font-size: 16px;
    color: $text-secondary;
  }
}

.sort-sheet-options {
  padding: 8px 0;
}

.sort-sheet-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  font-size: 16px;
  color: $text-primary;

  &.active {
    color: $primary;
    font-weight: 500;
  }

  &:active {
    background-color: $background;
  }
}

.sort-sheet-check {
  font-size: 18px;
  color: $primary;
}

// 筛选弹窗
.filter-sheet-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.filter-sheet {
  width: 100%;
  max-height: 80vh;
  background-color: $surface;
  border-radius: $radius-lg $radius-lg 0 0;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.25s ease-out;
}

.filter-sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid $border;
  flex-shrink: 0;
}

.filter-sheet-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-primary;
}

.filter-sheet-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-full;
  background-color: $background;

  .bi {
    font-size: 16px;
    color: $text-secondary;
  }
}

.filter-sheet-content {
  flex: 1;
  width: auto;
  overflow-y: auto;
  padding: 16px 20px;
}

.filter-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.filter-section-title {
  font-size: 15px;
  font-weight: 600;
  color: $text-primary;
  display: block;
  margin-bottom: 12px;
}

// 价格范围输入
.price-range-inputs {
  display: flex;
  align-items: center;
  gap: 12px;
}

.price-input-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  height: 44px;
  background-color: $background;
  border-radius: $radius-md;
  padding: 0 12px;
  border: 1px solid $border;

  &:focus-within {
    border-color: $primary;
    background-color: $surface;
  }
}

.price-currency {
  font-size: 14px;
  color: $text-muted;
  margin-right: 4px;
}

.price-input {
  flex: 1;
  height: 100%;
  font-size: 15px;
  color: $text-primary;
  background: transparent;
}

.price-separator {
  font-size: 16px;
  color: $text-muted;
}

// 成色选项
.condition-options {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.condition-option {
  padding: 10px 16px;
  background-color: $background;
  border: 1px solid $border;
  border-radius: $radius-full;
  font-size: 14px;
  color: $text-secondary;
  transition: all 0.2s ease;

  &.active {
    background-color: rgba($primary, 0.1);
    border-color: $primary;
    color: $primary;
  }

  &:active {
    opacity: 0.7;
  }
}

// 筛选底部按钮
.filter-sheet-footer {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  border-top: 1px solid $border;
  flex-shrink: 0;
}

.filter-reset-btn,
.filter-apply-btn {
  flex: 1;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-md;
  font-size: 16px;
  font-weight: 500;
}

.filter-reset-btn {
  background-color: $background;
  color: $text-secondary;

  &:active {
    background-color: #E2E8F0; // 比 $background (#F8FAFC) 暗 5%
  }
}

.filter-apply-btn {
  background-color: $primary;
  color: white;

  &:active {
    background-color: #E65A2E; // 比 $primary (#FF6B35) 暗 10%
  }
}

// 结果滚动区
.results-scroll {
  flex: 1;
  height: 0;
  width: auto;
  padding: 12px;
}

// 加载状态
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 0;
}

.loader,
.loader-small {
  width: 48px;
  height: 48px;
  position: relative;
}

.loader-small {
  width: 24px;
  height: 24px;
}

.loader-ring {
  position: absolute;
  inset: 0;
  border: 3px solid $border;
  border-top-color: $primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  margin-top: 16px;
  font-size: 14px;
  color: $text-muted;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 24px;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background-color: $background;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;

  .bi {
    font-size: 36px;
    color: $text-muted;
  }
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: $text-primary;
  margin-bottom: 8px;
}

.empty-desc {
  font-size: 14px;
  color: $text-muted;
  margin-bottom: 20px;
}

.empty-suggestions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}

.empty-tag {
  padding: 8px 16px;
  background-color: $background;
  border-radius: $radius-full;
  font-size: 14px;
  color: $primary;

  &:active {
    background-color: rgba($primary, 0.1);
  }
}

// 商品网格
.goods-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.goods-card {
  background-color: $surface;
  border-radius: $radius-md;
  overflow: hidden;
  box-shadow: $shadow-sm;
  animation: fadeInUp 0.3s ease-out backwards;

  &:active {
    transform: scale(0.98);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.goods-image-wrapper {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  // background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  box-sizing: border-box;
}

.goods-image {
  width: calc(100% - 16px);
  height: calc(100% - 16px);
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
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.promo-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  padding: 4px 8px;
  background: linear-gradient(135deg, $error, #F97316);
  color: white;
  font-size: 11px;
  font-weight: 600;
  border-radius: $radius-sm;
  display: flex;
  align-items: center;
  gap: 4px;

  .bi {
    font-size: 10px;
  }
}

.shipping-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 4px 8px;
  background-color: $success;
  color: white;
  font-size: 11px;
  font-weight: 600;
  border-radius: $radius-sm;
  display: flex;
  align-items: center;
  gap: 4px;

  .bi {
    font-size: 10px;
  }
}

.goods-info {
  padding: 12px;
}

.goods-title {
  font-size: 14px;
  color: $text-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  margin-bottom: 8px;
}

.goods-price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-bottom: 6px;
}

.goods-price {
  font-size: 18px;
  font-weight: 700;
  color: $text-primary;

  &.promo {
    color: $error;
  }
}

.goods-original-price {
  font-size: 13px;
  color: $text-muted;
  text-decoration: line-through;
}

.goods-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.condition-tag {
  padding: 3px 8px;
  background-color: $background;
  border-radius: $radius-sm;
  font-size: 11px;
  color: $text-secondary;
}

.shipping-fee {
  font-size: 12px;
  color: $text-muted;
}

// 加载更多
.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  font-size: 13px;
  color: $text-muted;
}

// 没有更多
.no-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 20px;
  font-size: 13px;
  color: $text-muted;
}

.divider {
  width: 40px;
  height: 1px;
  background-color: $border;
}

// 安全区
.safe-bottom {
  height: calc(20px + env(safe-area-inset-bottom));
}

// 对话框
.dialog-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.dialog-content {
  background-color: $surface;
  border-radius: $radius-lg;
  padding: 24px;
  width: 100%;
  max-width: 320px;
}

.dialog-title {
  font-size: 18px;
  font-weight: 600;
  color: $text-primary;
  display: block;
  margin-bottom: 12px;
}

.dialog-message {
  font-size: 14px;
  color: $text-secondary;
  display: block;
  margin-bottom: 24px;
  line-height: 1.5;
}

.dialog-actions {
  display: flex;
  gap: 12px;
}

.dialog-btn {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-md;
  font-size: 15px;
  font-weight: 500;

  &.cancel {
    background-color: $background;
    color: $text-secondary;
  }

  &.confirm {
    background-color: $primary;
    color: white;
  }

  &:active {
    opacity: 0.8;
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .goods-card,
  .loader-ring,
  .spinning {
    animation: none;
  }
}
</style>
