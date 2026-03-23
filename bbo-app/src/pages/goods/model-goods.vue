<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar
      :title="modelName"
      title-align="center"
      background="#FF6B35"
      color="#FFFFFF"
      titleAlign="left"
    >
      <template #right>
        <view class="nav-action" @click="handleShare">
          <text class="bi bi-share"></text>
        </view>
      </template>
    </NavBar>

    <!-- 内容区域（使用原生页面滚动） -->
    <view class="content-scroll">
      <!-- Hero 系列卡片 -->
      <view class="hero-section">
        <view class="hero-card">
          <!-- 背景渐变 -->
          <view class="hero-bg"></view>

          <view class="hero-content">
            <view class="hero-image-container">
              <image
                v-if="modelInfo.image"
                class="hero-image"
                :src="modelInfo.image"
                mode="aspectFit"
              />
              <view v-else class="hero-image-placeholder">
                <text class="bi bi-phone"></text>
              </view>
            </view>

            <view class="hero-info">
              <text class="hero-title">{{ modelInfo.name }}</text>
              <view class="hero-price">
                <text class="price-value">{{ formatPrice(modelInfo.min_price) }}</text>
                <text class="price-label">{{ t('goods.priceFrom') }}</text>
              </view>

              <view class="hero-stats">
                <view class="stat-pill">
                  <text class="bi bi-box-seam"></text>
                  <text>{{ modelInfo.goods_count || 0 }} {{ t('goods.items') }}</text>
                </view>
                <view class="stat-pill">
                  <text class="bi bi-eye"></text>
                  <text>{{ modelInfo.views || 0 }}</text>
                </view>
              </view>
            </view>
          </view>

          <!-- 快捷操作栏 -->
          <view class="hero-actions">
            <view class="action-pill" :class="{ active: isFavorited }" @click="handleFavorite">
              <text :class="['bi', isFavorited ? 'bi-heart-fill' : 'bi-heart']"></text>
              <text>{{ t('common.favorite') }}</text>
            </view>
            <view class="action-pill primary" @click="openFilter">
              <text class="bi bi-sliders"></text>
              <text>{{ t('goods.filterBtn') }}</text>
              <view v-if="selectedFilterCount > 0" class="action-badge">{{ selectedFilterCount }}</view>
            </view>
          </view>
        </view>
      </view>

      <!-- 已选筛选条件 -->
      <view v-if="selectedFilterCount > 0" class="active-filters">
        <scroll-view scroll-x :show-scrollbar="false" class="filters-scroll">
          <view class="filters-list">
            <view
              v-for="(values, key) in selectedFilters"
              :key="key"
              v-show="values && values.length > 0"
              class="filter-chip"
              @click="removeFilter(key)"
            >
              <text class="chip-text">{{ getFilterLabel(key, values) }}</text>
              <text class="bi bi-x-lg chip-close"></text>
            </view>
            <view class="filter-chip clear-chip" @click="clearAllFilters">
              <text class="bi bi-arrow-counterclockwise"></text>
              <text class="chip-text">{{ t('goods.clearAll') }}</text>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 商品网格 -->
      <view class="products-section">
        <!-- 结果统计 -->
        <view v-if="!loading || goods.length > 0" class="results-header">
          <text class="results-count">{{ formatResultsCountText(modelInfo.goods_count || goods.length) }}</text>
          <view class="sort-btn" @click="openSort">
            <text class="bi bi-arrow-down-up"></text>
            <text>{{ t('goods.sort') }}</text>
          </view>
        </view>

        <!-- 加载状态 -->
        <view v-if="loading && goods.length === 0" class="loading-state">
          <view class="loader">
            <view class="loader-ring"></view>
          </view>
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <!-- 空状态 -->
        <view v-else-if="goods.length === 0" class="empty-state">
          <view class="empty-icon-wrapper">
            <text class="bi bi-inbox"></text>
          </view>
          <text class="empty-title">{{ t('goods.noProductsFound') }}</text>
          <text class="empty-desc">{{ t('goods.noGoods') }}</text>
        </view>

        <!-- 商品列表 -->
        <view v-else class="products-grid">
          <view
            v-for="(item, index) in goods"
            :key="item.id"
            class="product-card"
            :style="{ animationDelay: `${index * 50}ms` }"
            @click="goDetail(item.id)"
          >
            <view class="product-image-wrapper">
              <image
                class="product-image"
                :src="item.images[0]"
                mode="aspectFit"
                lazy-load
              />
              <!-- 已售罄遮罩 -->
              <view v-if="item.stock === 0" class="sold-out-overlay">
                <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
              </view>
              <!-- 运费标签 -->
              <view v-if="item.freeShipping && item.stock > 0" class="shipping-badge free">
                <text class="bi bi-truck"></text>
                <text>{{ t('goods.free') }}</text>
              </view>
            </view>

            <view class="product-info">
              <text class="product-title">{{ item.title }}</text>

              <view class="product-meta">
                <view class="product-price-row">
                  <!-- 活动价格优先显示 -->
                  <text class="product-price" :class="{ 'promo-price': item.promotion }">
                    {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                  </text>
                  <!-- 有活动时显示原价 -->
                  <text v-if="item.promotion" class="product-original-price">
                    {{ formatPrice(item.price, item.currency) }}
                  </text>
                  <text v-if="!item.freeShipping" class="product-shipping">
                    +{{ formatPrice(item.shippingFee, item.currency) }} {{ t('goods.shippingCost') }}
                  </text>
                </view>

                <!-- 活动标签 -->
                <view v-if="item.promotion" class="promo-tag">
                  <text class="bi bi-lightning-fill"></text>
                  <text>{{ item.promotion.discountPercent }}% OFF</text>
                </view>

                <view class="product-tags">
                  <view v-if="item.condition" class="condition-tag">
                    {{ getConditionLabel(item.condition) }}
                  </view>
                  <view v-if="item.isNegotiable" class="negotiable-tag">
                    {{ t('goods.negotiable') }}
                  </view>
                  <view class="star-rating">
                    <text class="bi bi-star-fill star-icon"></text>
                    <text class="bi bi-star-fill star-icon"></text>
                    <text class="bi bi-star-fill star-icon"></text>
                    <text class="bi bi-star-fill star-icon"></text>
                    <text class="bi bi-star-fill star-icon"></text>
                  </view>
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="loading && goods.length > 0" class="loading-more">
          <view class="loader-small">
            <view class="loader-ring"></view>
          </view>
          <text>{{ t('goods.loadingMore') }}</text>
        </view>

        <view v-else-if="noMore && goods.length > 0" class="no-more">
          <view class="divider-line"></view>
          <text>{{ t('common.noMore') }}</text>
          <view class="divider-line"></view>
        </view>
      </view>

      <!-- 底部安全区 -->
      <view class="safe-bottom"></view>
    </view>

    <!-- 筛选底部弹窗 -->
    <view
      v-if="showFilterPopup"
      class="filter-overlay"
      :class="{ visible: filterVisible }"
      @click="closeFilter"
    >
      <view
        class="filter-sheet"
        :class="{ visible: filterVisible }"
        @click.stop
      >
        <!-- 拖动指示器 -->
        <view class="sheet-handle">
          <view class="handle-bar"></view>
        </view>

        <view class="sheet-header">
          <text class="sheet-title">{{ t('goods.filterConditions') }}</text>
          <view class="sheet-close" @click="closeFilter">
            <text class="bi bi-x-lg"></text>
          </view>
        </view>

        <scroll-view scroll-y class="sheet-content">
          <view v-for="attr in filterAttributes" :key="attr.key" class="filter-group">
            <view class="filter-group-header">
              <text class="filter-group-title">{{ attr.name }}</text>
              <text
                v-if="tempFilters[attr.key]?.length"
                class="filter-group-count"
              >{{ tempFilters[attr.key].length }}</text>
            </view>

            <!-- 选项少时：标签按钮布局 -->
            <view v-if="!shouldCollapseGroup(attr)" class="filter-options-grid">
              <view
                class="filter-option-btn"
                :class="{ selected: !tempFilters[attr.key] || tempFilters[attr.key].length === 0 }"
                @click="selectFilterOption(attr.key, null)"
              >
                <text>{{ t('common.all') }}</text>
              </view>
              <view
                v-for="option in attr.options"
                :key="option.value"
                class="filter-option-btn"
                :class="{ selected: tempFilters[attr.key]?.includes(option.value) }"
                @click="selectFilterOption(attr.key, option.value)"
              >
                <text>{{ option.label }}</text>
              </view>
            </view>

            <!-- 选项多时：选择框设计 -->
            <view v-else class="filter-select-box" @click="openOptionsPicker(attr)">
              <view class="filter-select-content">
                <text v-if="!tempFilters[attr.key]?.length" class="filter-select-placeholder">
                  {{ t('common.all') }}
                </text>
                <view v-else class="filter-selected-tags">
                  <view
                    v-for="value in tempFilters[attr.key].slice(0, 3)"
                    :key="value"
                    class="filter-selected-tag"
                  >
                    <text>{{ getOptionLabel(attr, value) }}</text>
                  </view>
                  <text v-if="tempFilters[attr.key].length > 3" class="filter-more-tag">
                    +{{ tempFilters[attr.key].length - 3 }}
                  </text>
                </view>
              </view>
              <text class="bi bi-chevron-down filter-select-arrow"></text>
            </view>
          </view>
        </scroll-view>

        <!-- 选项选择弹窗 -->
        <view
          v-if="showOptionsPicker"
          class="options-picker-overlay"
          @click="closeOptionsPicker"
        >
          <view class="options-picker-sheet" @click.stop>
            <view class="options-picker-header">
              <view class="options-picker-cancel" @click="closeOptionsPicker">
                <text>{{ t('common.cancel') }}</text>
              </view>
              <text class="options-picker-title">{{ currentPickerAttr?.name }}</text>
              <view class="options-picker-confirm" @click="confirmOptionsPicker">
                <text>{{ t('common.confirm') }}</text>
              </view>
            </view>

            <!-- 搜索框：选项超过20个时显示 -->
            <view v-if="shouldShowPickerSearch" class="options-picker-search">
              <view class="search-input-wrapper">
                <text class="bi bi-search search-icon"></text>
                <input
                  v-model="pickerSearchKeyword"
                  class="search-input"
                  type="text"
                  :placeholder="t('common.search')"
                  confirm-type="search"
                />
                <view
                  v-if="pickerSearchKeyword"
                  class="search-clear"
                  @click="pickerSearchKeyword = ''"
                >
                  <text class="bi bi-x-circle-fill"></text>
                </view>
              </view>
            </view>

            <scroll-view scroll-y class="options-picker-list">
              <!-- 全部选项（搜索时隐藏） -->
              <view
                v-if="!pickerSearchKeyword"
                class="options-picker-item"
                :class="{ selected: !pickerTempValues.length }"
                @click="togglePickerOption(null)"
              >
                <text class="options-picker-item-text">{{ t('common.all') }}</text>
                <text v-if="!pickerTempValues.length" class="bi bi-check-lg options-picker-check"></text>
              </view>
              <!-- 选项列表（支持搜索过滤） -->
              <view
                v-for="option in filteredPickerOptions"
                :key="option.value"
                class="options-picker-item"
                :class="{ selected: pickerTempValues.includes(option.value) }"
                @click="togglePickerOption(option.value)"
              >
                <text class="options-picker-item-text">{{ option.label }}</text>
                <text v-if="pickerTempValues.includes(option.value)" class="bi bi-check-lg options-picker-check"></text>
              </view>
              <!-- 无搜索结果 -->
              <view v-if="pickerSearchKeyword && filteredPickerOptions.length === 0" class="options-picker-empty">
                <text class="bi bi-search options-picker-empty-icon"></text>
                <text class="options-picker-empty-text">{{ t('common.noResults') }}</text>
              </view>
            </scroll-view>
          </view>
        </view>

        <view class="sheet-footer">
          <view class="footer-btn secondary" @click="resetFilters">
            <text>{{ t('common.reset') }}</text>
          </view>
          <view class="footer-btn primary" @click="applyFilters">
            <text>{{ t('common.confirm') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 分享弹窗 -->
    <SharePopup
      :visible="showSharePopup"
      :share-url="getShareUrl()"
      :share-title="modelInfo.name"
      :share-text="`${modelInfo.name} - ${formatItemsAvailableText(modelInfo.goods_count)}`"
      :preview-image="modelInfo.image || ''"
      :preview-title="modelInfo.name"
      :preview-price="modelInfo.min_price ? formatPrice(modelInfo.min_price, 'USD') + '+' : ''"
      @update:visible="showSharePopup = $event"
    />

    <!-- 排序弹窗 -->
    <ActionSheet
      :visible="showSortPopup"
      :options="sortOptions"
      :title="t('goods.sort')"
      :model-value="currentSort"
      :show-cancel="false"
      @update:visible="showSortPopup = $event"
      @select="handleSortSelect"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { onLoad, onReachBottom } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import {
  getGoodsList,
  getCategoryAttributes,
  getModelStats,
  type Goods,
  type CategoryAttribute
} from '@/api/goods'
import NavBar from '@/components/NavBar.vue'
import SharePopup from '@/components/SharePopup.vue'
import ActionSheet, { type ActionSheetOption } from '@/components/ActionSheet.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 页面参数
const categoryId = ref<number>(0)
const modelValue = ref<string>('')
const modelName = ref<string>('')

// 分享弹窗
const showSharePopup = ref(false)

// 系列信息
const modelInfo = reactive({
  name: '',
  image: null as string | null,
  min_price: null as number | null,
  goods_count: 0,
  views: 0
})

// 商品列表
const goods = ref<Goods[]>([])
const loading = ref(false)
const noMore = ref(false)
const page = ref(1)
const pageSize = 20

// 收藏状态
const isFavorited = ref(false)

// 筛选相关
const showFilterPopup = ref(false)
const filterVisible = ref(false)
const filterAttributes = ref<CategoryAttribute[]>([])
const selectedFilters = reactive<Record<string, string[]>>({})
const tempFilters = reactive<Record<string, string[]>>({})
// 阈值配置：超过此数量则使用选择框
const FILTER_COLLAPSE_THRESHOLD = 8

// 选项选择弹窗相关
const showOptionsPicker = ref(false)
const currentPickerAttr = ref<CategoryAttribute | null>(null)
const pickerTempValues = ref<string[]>([])
const pickerSearchKeyword = ref('')
const PICKER_SEARCH_THRESHOLD = 20  // 超过此数量显示搜索框

// 排序相关
const showSortPopup = ref(false)
const currentSort = ref('newest')

const sortOptions = computed(() => [
  { value: 'newest', label: t('goods.filter.newest') },
  { value: 'price_asc', label: t('goods.filter.priceAsc') },
  { value: 'price_desc', label: t('goods.filter.priceDesc') },
  { value: 'popular', label: t('goods.filter.popular') }
])

// 是否显示选项弹窗搜索框
const shouldShowPickerSearch = computed(() => {
  return (currentPickerAttr.value?.options?.length || 0) > PICKER_SEARCH_THRESHOLD
})

// 过滤后的选项列表
const filteredPickerOptions = computed(() => {
  if (!currentPickerAttr.value?.options) return []
  if (!pickerSearchKeyword.value.trim()) {
    return currentPickerAttr.value.options
  }
  const keyword = pickerSearchKeyword.value.trim().toLowerCase()
  return currentPickerAttr.value.options.filter(option =>
    option.label.toLowerCase().includes(keyword) ||
    option.value.toLowerCase().includes(keyword)
  )
})

// 已选筛选数量
const selectedFilterCount = computed(() => {
  return Object.values(selectedFilters).filter(v => v && v.length > 0).length
})

// 格式化价格
function formatPrice(price: number | null, currency?: string): string {
  if (price === null || price === undefined) return '--'
  return appStore.formatPrice(price, currency)
}

// 格式化结果数量文本（手动替换占位符，解决 UniApp 中 vue-i18n 插值不生效的问题）
function formatResultsCountText(count: number): string {
  const template = t('goods.resultsCount')
  return template
    .replace('##COUNT##', String(count))
    .replace('__COUNT__', String(count))
    .replace('[COUNT]', String(count))
    .replace('{count}', String(count))
}

// 格式化可用商品数量文本
function formatItemsAvailableText(count: number): string {
  const template = t('goods.itemsAvailable')
  return template
    .replace('##COUNT##', String(count))
    .replace('__COUNT__', String(count))
    .replace('[COUNT]', String(count))
    .replace('{count}', String(count))
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

// 打开筛选弹窗
function openFilter() {
  Object.keys(selectedFilters).forEach(key => {
    tempFilters[key] = [...(selectedFilters[key] || [])]
  })
  showFilterPopup.value = true
  nextTick(() => {
    filterVisible.value = true
  })
}

// 关闭筛选弹窗
function closeFilter() {
  filterVisible.value = false
  setTimeout(() => {
    showFilterPopup.value = false
  }, 300)
}

// 打开排序弹窗
function openSort() {
  showSortPopup.value = true
}

// 选择排序方式
function handleSortSelect(option: ActionSheetOption) {
  currentSort.value = option.value as string
  loadGoods(true)
}

// 选择筛选选项
function selectFilterOption(key: string, value: string | null) {
  if (!tempFilters[key]) {
    tempFilters[key] = []
  }

  if (value === null) {
    tempFilters[key] = []
  } else {
    const index = tempFilters[key].indexOf(value)
    if (index > -1) {
      tempFilters[key].splice(index, 1)
    } else {
      tempFilters[key].push(value)
    }
  }
}

// 重置筛选
function resetFilters() {
  Object.keys(tempFilters).forEach(key => {
    tempFilters[key] = []
  })
}

// 应用筛选
function applyFilters() {
  Object.keys(tempFilters).forEach(key => {
    selectedFilters[key] = [...(tempFilters[key] || [])]
  })
  closeFilter()
  loadGoods(true)
}

// 移除单个筛选
function removeFilter(key: string) {
  selectedFilters[key] = []
  loadGoods(true)
}

// 判断筛选组是否需要使用选择框（选项数量超过阈值）
function shouldCollapseGroup(attr: CategoryAttribute): boolean {
  // +1 是因为有一个"全部"选项
  return (attr.options?.length || 0) + 1 > FILTER_COLLAPSE_THRESHOLD
}

// 获取选项的显示标签
function getOptionLabel(attr: CategoryAttribute, value: string): string {
  const option = attr.options?.find(o => o.value === value)
  return option?.label || value
}

// 打开选项选择弹窗
function openOptionsPicker(attr: CategoryAttribute) {
  currentPickerAttr.value = attr
  // 复制当前临时筛选值
  pickerTempValues.value = [...(tempFilters[attr.key] || [])]
  showOptionsPicker.value = true
}

// 关闭选项选择弹窗
function closeOptionsPicker() {
  showOptionsPicker.value = false
  currentPickerAttr.value = null
  pickerTempValues.value = []
  pickerSearchKeyword.value = ''
}

// 切换选项选中状态
function togglePickerOption(value: string | null) {
  if (value === null) {
    // 选择"全部"，清空所有选择
    pickerTempValues.value = []
  } else {
    const index = pickerTempValues.value.indexOf(value)
    if (index > -1) {
      pickerTempValues.value.splice(index, 1)
    } else {
      pickerTempValues.value.push(value)
    }
  }
}

// 确认选项选择
function confirmOptionsPicker() {
  if (currentPickerAttr.value) {
    tempFilters[currentPickerAttr.value.key] = [...pickerTempValues.value]
  }
  closeOptionsPicker()
}

// 清空所有筛选
function clearAllFilters() {
  Object.keys(selectedFilters).forEach(key => {
    selectedFilters[key] = []
  })
  loadGoods(true)
}

// 获取筛选标签文本
function getFilterLabel(key: string, values: string[]): string {
  const attr = filterAttributes.value.find(a => a.key === key)
  if (!attr) return ''

  const labels = values.map(v => {
    const opt = attr.options.find(o => o.value === v)
    return opt?.label || v
  })

  return labels.join(', ')
}

// 获取分享链接
function getShareUrl() {
  const baseUrl = window?.location?.origin || 'https://bbo.com'
  return `${baseUrl}/pages/goods/model-goods?category_id=${categoryId.value}&model=${encodeURIComponent(modelValue.value)}&name=${encodeURIComponent(modelInfo.name)}`
}

// 分享
function handleShare() {
  showSharePopup.value = true
}

// 收藏
function handleFavorite() {
  isFavorited.value = !isFavorited.value
  toast.success(isFavorited.value ? t('common.favoriteSuccess') : t('common.unfavoriteSuccess'))
}

// 加载商品列表
async function loadGoods(isRefresh = false) {
  if (loading.value) return

  if (isRefresh) {
    page.value = 1
    noMore.value = false
  }

  loading.value = true

  try {
    const filterParams: Record<string, string> = {}
    Object.keys(selectedFilters).forEach(key => {
      if (selectedFilters[key] && selectedFilters[key].length > 0) {
        filterParams[key] = selectedFilters[key].join(',')
      }
    })

    const res = await getGoodsList({
      page: page.value,
      pageSize,
      categoryId: categoryId.value || undefined,
      model: modelValue.value || undefined,
      sort: currentSort.value as 'newest' | 'price_asc' | 'price_desc' | 'popular',
      ...filterParams
    })

    if (isRefresh) {
      goods.value = res.data.list
    } else {
      goods.value = [...goods.value, ...res.data.list]
    }

    if (isRefresh && res.data.list.length > 0) {
      const prices = res.data.list.map(g => g.price).filter(p => p > 0)
      if (prices.length > 0) {
        modelInfo.min_price = Math.min(...prices)
      }
      modelInfo.goods_count = res.data.total
    }

    if (res.data.list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Failed to load goods:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 加载筛选属性
async function loadFilterAttributes() {
  if (!categoryId.value) return

  try {
    const res = await getCategoryAttributes(categoryId.value)
    filterAttributes.value = res.data.attributes.filter(
      attr => attr.key !== 'brand' && attr.key !== 'model'
    )

    filterAttributes.value.forEach(attr => {
      if (!selectedFilters[attr.key]) {
        selectedFilters[attr.key] = []
      }
      if (!tempFilters[attr.key]) {
        tempFilters[attr.key] = []
      }
    })
  } catch (e) {
    console.error('Failed to load filter attributes:', e)
  }
}

// 触底加载更多（使用页面生命周期钩子）
onReachBottom(() => {
  if (noMore.value || loading.value) return
  page.value++
  loadGoods()
})

// 跳转详情
function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

onLoad((options) => {
  categoryId.value = Number(options?.categoryId || 0)
  modelValue.value = options?.model ? decodeURIComponent(options.model) : ''
  modelName.value = options?.title ? decodeURIComponent(options.title) : ''

  modelInfo.name = modelName.value
  if (options?.image) {
    modelInfo.image = decodeURIComponent(options.image)
  }
  if (options?.min_price) {
    modelInfo.min_price = Number(options.min_price)
  }
})

onMounted(() => {
  loadFilterAttributes()
  loadGoods(true)
  loadModelStats()
})

// 加载型号统计数据
async function loadModelStats() {
  if (!modelValue.value) return

  try {
    const res = await getModelStats(modelValue.value, categoryId.value || undefined)
    modelInfo.views = res.data.totalViews
    // 如果 API 返回了更准确的数据，更新 modelInfo
    if (res.data.goodsCount > 0) {
      modelInfo.goods_count = res.data.goodsCount
    }
    if (res.data.minPrice !== null) {
      modelInfo.min_price = res.data.minPrice
    }
  } catch (e) {
    console.error('Failed to load model stats:', e)
  }
}
</script>

<style lang="scss" scoped>
// 设计系统变量
$primary: #FF6B35;
$primary-light: #FF8555;
$secondary: #64748B;
$accent: #FF6B35;
$success: #10B981;
$background: #F8FAFC;
$surface: #FFFFFF;
$text-primary: #1E293B;
$text-secondary: #64748B;
$text-muted: #94A3B8;
$border: #E2E8F0;
$border-light: #F1F5F9;
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
$shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
$shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
$radius-sm: 8rpx;
$radius-md: 16rpx;
$radius-lg: 24rpx;
$radius-full: 9999rpx;

.page {
  min-height: 100vh;
  background-color: $background;
}

// 导航栏右侧操作按钮
.nav-action {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 36rpx;
    color: #fff;
  }

  &:active {
    opacity: 0.7;
  }
}

// 使用原生页面滚动，无需设置固定高度

// Hero 区域
.hero-section {
  padding: 24rpx;
}

.hero-card {
  position: relative;
  background: $surface;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-lg;
}

.hero-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 200rpx;
  background: linear-gradient(135deg, rgba($primary, 0.08) 0%, rgba($primary-light, 0.04) 100%);
}

.hero-content {
  position: relative;
  display: flex;
  padding: 32rpx;
  gap: 28rpx;
}

.hero-image-container {
  width: 200rpx;
  height: 200rpx;
  flex-shrink: 0;
  background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
}

.hero-image {
  width: 180rpx;
  height: 180rpx;
  transition: transform 0.3s ease;
}

.hero-image-placeholder {
  font-size: 100rpx;
  color: $border;
}

.hero-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-title {
  font-size: 36rpx;
  font-weight: 700;
  color: $text-primary;
  line-height: 1.3;
  margin-bottom: 12rpx;
  letter-spacing: -0.5rpx;
}

.hero-price {
  display: flex;
  align-items: baseline;
  gap: 8rpx;
  margin-bottom: 16rpx;

  .price-label {
    font-size: 24rpx;
    color: $text-muted;
  }

  .price-value {
    font-size: 40rpx;
    font-weight: 700;
    color: $primary;
    letter-spacing: -1rpx;
  }
}

.hero-stats {
  display: flex;
  gap: 12rpx;
  flex-wrap: wrap;
}

.stat-pill {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 16rpx;
  background: $background;
  border-radius: $radius-full;
  font-size: 24rpx;
  color: $text-secondary;

  .bi {
    font-size: 24rpx;
    color: $text-muted;
  }
}

// 操作栏
.hero-actions {
  display: flex;
  gap: 16rpx;
  padding: 0 32rpx 32rpx;
}

.action-pill {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  height: 88rpx;
  background: $background;
  border-radius: $radius-full;
  font-size: 28rpx;
  font-weight: 500;
  color: $text-secondary;
  position: relative;
  transition: all 0.2s ease-out;

  .bi {
    font-size: 32rpx;
  }

  &:active {
    transform: scale(0.98);
  }

  &.active {
    background: #FEF2F2;
    color: #EF4444;

    .bi {
      color: #EF4444;
    }
  }

  &.primary {
    background: $primary;
    color: #fff;

    .bi {
      color: #fff;
    }
  }
}

.action-badge {
  position: absolute;
  top: 12rpx;
  right: 32rpx;
  min-width: 36rpx;
  height: 36rpx;
  background: $accent;
  color: #fff;
  font-size: 22rpx;
  font-weight: 600;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 10rpx;
}

// 已选筛选条件
.active-filters {
  padding: 0 24rpx 16rpx;
}

.filters-scroll {
  white-space: nowrap;
}

.filters-list {
  display: inline-flex;
  gap: 12rpx;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 20rpx;
  background: $primary;
  border-radius: $radius-full;
  transition: all 0.2s ease;

  .chip-text {
    font-size: 24rpx;
    color: #fff;
  }

  .chip-close {
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.8);
  }

  &:active {
    transform: scale(0.95);
  }
}

.clear-chip {
  background: $surface;
  border: 2rpx solid $border;

  .bi {
    font-size: 24rpx;
    color: $text-muted;
  }

  .chip-text {
    color: $text-secondary;
  }
}

// 商品区域
.products-section {
  padding: 0 24rpx;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.results-count {
  font-size: 26rpx;
  color: $text-muted;
}

.sort-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 20rpx;
  background: $surface;
  border-radius: $radius-full;
  font-size: 26rpx;
  color: $text-secondary;
  box-shadow: $shadow-sm;

  .bi {
    font-size: 24rpx;
  }
}

// 加载状态
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 0;
}

.loader,
.loader-small {
  position: relative;
  width: 80rpx;
  height: 80rpx;
}

.loader-small {
  width: 48rpx;
  height: 48rpx;
}

.loader-ring {
  position: absolute;
  inset: 0;
  border: 4rpx solid $border;
  border-top-color: $primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  margin-top: 20rpx;
  font-size: 28rpx;
  color: $text-muted;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 48rpx;
}

.empty-icon-wrapper {
  width: 160rpx;
  height: 160rpx;
  background: $background;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;

  .bi {
    font-size: 80rpx;
    color: $border;
  }
}

.empty-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-primary;
  margin-bottom: 12rpx;
}

.empty-desc {
  font-size: 28rpx;
  color: $text-muted;
}

// 商品网格
.products-grid {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.product-card {
  display: flex;
  align-items: center;
  background: $surface;
  border-radius: $radius-lg;
  overflow: hidden;
  box-shadow: $shadow-sm;
  transition: all 0.2s ease-out;
  animation: fadeInUp 0.4s ease-out backwards;
  // padding: 12rpx 0;

  &:active {
    transform: scale(0.99);
    box-shadow: $shadow-md;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20rpx);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-image-wrapper {
  position: relative;
  width: 240rpx;
  height: 240rpx;
  flex-shrink: 0;
  padding: 24rpx;
  border-radius: 16rpx;
}

.product-image {
  width: 100%;
  height: 100%;
  border-radius: 16rpx;
  background-color: #fff;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
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
  font-size: 26rpx;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 2rpx;
}

.shipping-badge {
  position: absolute;
  top: 12rpx;
  left: 12rpx;
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 8rpx 14rpx;
  background: $success;
  border-radius: $radius-full;
  font-size: 22rpx;
  font-weight: 500;
  color: #fff;

  .bi {
    font-size: 22rpx;
  }
}

.product-info {
  flex: 1;
  padding: 20rpx 24rpx;
  display: flex;
  flex-direction: column;
}

.product-title {
  font-size: 28rpx;
  font-weight: 500;
  color: $text-primary;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 12rpx;
}

.product-meta {
  margin-top: auto;
}

.product-price-row {
  display: flex;
  align-items: baseline;
  gap: 12rpx;
  margin-bottom: 12rpx;
}

.product-price {
  font-size: 36rpx;
  font-weight: 700;
  color: $text-primary;
  letter-spacing: -0.5rpx;

  &.promo-price {
    color: #EF4444;
  }
}

.product-original-price {
  font-size: 24rpx;
  color: $text-muted;
  text-decoration: line-through;
  margin-left: 8rpx;
}

.product-shipping {
  font-size: 22rpx;
  color: $text-muted;
}

.promo-tag {
  display: inline-flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 14rpx;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: $radius-full;
  font-size: 22rpx;
  font-weight: 600;
  color: #fff;
  margin-bottom: 8rpx;

  .bi {
    font-size: 20rpx;
  }
}

.product-tags {
  display: flex;
  gap: 8rpx;
}

.condition-tag {
  padding: 6rpx 12rpx;
  background: $background;
  border-radius: $radius-sm;
  font-size: 22rpx;
  color: $text-secondary;
}

.negotiable-tag {
  padding: 6rpx 12rpx;
  background: rgba($primary, 0.1);
  border-radius: $radius-sm;
  font-size: 22rpx;
  color: $primary;
  font-weight: 500;
}

.star-rating {
  display: flex;
  align-items: center;
  gap: 2rpx;
}

.star-icon {
  font-size: 20rpx;
  color: #facc15;
}

// 加载更多
.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  padding: 32rpx;
  font-size: 26rpx;
  color: $text-muted;
}

.no-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24rpx;
  padding: 32rpx;
  font-size: 26rpx;
  color: $text-muted;
}

.divider-line {
  width: 80rpx;
  height: 2rpx;
  background: $border;
}

.safe-bottom {
  height: calc(32rpx + env(safe-area-inset-bottom));
}

// 筛选弹窗
.filter-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0);
  z-index: 1000;
  transition: background 0.3s ease;

  &.visible {
    background: rgba(0, 0, 0, 0.5);
  }
}

.filter-sheet {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  max-height: 80vh;
  background: $surface;
  border-radius: 32rpx 32rpx 0 0;
  display: flex;
  flex-direction: column;
  transform: translateY(100%);
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);

  &.visible {
    transform: translateY(0);
  }
}

.sheet-handle {
  display: flex;
  justify-content: center;
  padding: 16rpx 0;
}

.handle-bar {
  width: 80rpx;
  height: 8rpx;
  background: $border;
  border-radius: 4rpx;
}

.sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8rpx 32rpx 24rpx;
}

.sheet-title {
  font-size: 36rpx;
  font-weight: 700;
  color: $text-primary;
}

.sheet-close {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: $background;

  .bi {
    font-size: 32rpx;
    color: $text-secondary;
  }
}

.sheet-content {
  flex: 1;
  padding: 0 32rpx;
  max-height: 50vh;
  width: auto;
}

.filter-group {
  margin-bottom: 36rpx;
}

.filter-group-header {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 20rpx;
}

.filter-group-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $text-primary;
}

.filter-group-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36rpx;
  height: 36rpx;
  padding: 0 10rpx;
  background: $primary;
  color: #fff;
  font-size: 22rpx;
  font-weight: 600;
  border-radius: $radius-full;
}

.filter-options-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.filter-option-btn {
  padding: 18rpx 32rpx;
  background: $background;
  border-radius: $radius-full;
  font-size: 28rpx;
  color: $text-secondary;
  border: 2rpx solid transparent;
  transition: all 0.2s ease;

  &.selected {
    background: rgba($primary, 0.1);
    color: $primary;
    border-color: $primary;
    font-weight: 500;
  }

  &:active {
    transform: scale(0.96);
  }
}

// 选择框样式
.filter-select-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx 28rpx;
  background: $background;
  border-radius: $radius-lg;
  border: 2rpx solid $border;
  transition: all 0.2s ease;

  &:active {
    border-color: $primary;
    background: rgba($primary, 0.02);
  }
}

.filter-select-content {
  flex: 1;
  min-width: 0;
}

.filter-select-placeholder {
  font-size: 28rpx;
  color: $text-muted;
}

.filter-selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  align-items: center;
}

.filter-selected-tag {
  padding: 8rpx 20rpx;
  background: rgba($primary, 0.1);
  border-radius: $radius-full;
  font-size: 24rpx;
  color: $primary;
  font-weight: 500;
}

.filter-more-tag {
  font-size: 24rpx;
  color: $primary;
  font-weight: 600;
}

.filter-select-arrow {
  font-size: 28rpx;
  color: $text-muted;
  margin-left: 16rpx;
  flex-shrink: 0;
}

// 选项选择弹窗
.options-picker-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1100;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.2s ease;
}

.options-picker-sheet {
  width: 100%;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  animation: slideUp 0.3s ease;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}

.options-picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 32rpx;
  border-bottom: 2rpx solid $border;
}

.options-picker-cancel,
.options-picker-confirm {
  font-size: 30rpx;
  padding: 8rpx 16rpx;
}

.options-picker-cancel {
  color: $text-muted;
}

.options-picker-confirm {
  color: $primary;
  font-weight: 600;
}

.options-picker-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-primary;
}

.options-picker-list {
  flex: 1;
  max-height: 60vh;
  padding-bottom: env(safe-area-inset-bottom);
}

.options-picker-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx;
  border-bottom: 2rpx solid $border-light;
  transition: background 0.15s ease;

  &:active {
    background: $background;
  }

  &.selected {
    background: rgba($primary, 0.04);

    .options-picker-item-text {
      color: $primary;
      font-weight: 500;
    }
  }
}

.options-picker-item-text {
  font-size: 30rpx;
  color: $text-primary;
}

.options-picker-check {
  font-size: 36rpx;
  color: $primary;
}

// 搜索框样式
.options-picker-search {
  padding: 20rpx 32rpx;
  border-bottom: 2rpx solid $border-light;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  background: $background;
  border-radius: $radius-lg;
  padding: 0 24rpx;
  height: 80rpx;
}

.search-icon {
  font-size: 32rpx;
  color: $text-muted;
  margin-right: 16rpx;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  font-size: 28rpx;
  color: $text-primary;
  background: transparent;
}

.search-clear {
  padding: 8rpx;
  margin-left: 8rpx;
  flex-shrink: 0;

  .bi {
    font-size: 32rpx;
    color: $text-muted;
  }
}

// 无搜索结果
.options-picker-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80rpx 32rpx;
  gap: 16rpx;
}

.options-picker-empty-icon {
  font-size: 64rpx;
  color: $text-muted;
  opacity: 0.5;
}

.options-picker-empty-text {
  font-size: 28rpx;
  color: $text-muted;
}

.sheet-footer {
  display: flex;
  gap: 20rpx;
  padding: 24rpx 32rpx;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
  border-top: 2rpx solid $border;
}

.footer-btn {
  flex: 1;
  height: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  border-radius: $radius-full;
  font-size: 32rpx;
  font-weight: 600;
  transition: all 0.2s ease;

  .bi {
    font-size: 32rpx;
  }

  &.secondary {
    background: $background;
    color: $text-secondary;
  }

  &.primary {
    background: $primary;
    color: #fff;
  }

  &:active {
    transform: scale(0.98);
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .product-card,
  .filter-sheet,
  .filter-overlay {
    animation: none;
    transition: none;
  }
}
</style>
