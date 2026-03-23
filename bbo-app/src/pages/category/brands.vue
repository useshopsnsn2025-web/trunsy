<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="categoryName" background="#FF6B35" color="#FFFFFF" />

    <!-- 加载中 -->
    <LoadingPage v-model="loading" />

    <!-- 内容（使用原生页面滚动，支持 onReachBottom） -->
    <view v-if="!loading" class="content-scroll">
      <!-- 品牌列表（最多5个） -->
      <!-- 有型号数据的品牌：显示品牌名+型号横向滚动 -->
      <template v-if="hasModels">
        <view v-for="brand in topBrands" :key="brand.brand_value" v-show="brand.models && brand.models.length > 0" class="brand-section">
          <view class="brand-header">
            <text class="brand-name">{{ brand.brand_name }}</text>
          </view>

          <scroll-view scroll-x class="models-scroll" :show-scrollbar="false">
            <view class="models-container">
              <view
                v-for="model in brand.models"
                :key="model.value"
                class="model-card"
                @click="goGoodsList(model)"
              >
                <view class="model-image-wrapper">
                  <image
                    v-if="model.image"
                    class="model-image"
                    :src="model.image"
                    mode="aspectFit"
                  />
                  <view v-else class="model-image-placeholder">
                    <text class="bi bi-phone"></text>
                  </view>
                </view>
                <view class="model-info">
                  <text class="model-name">{{ model.name }}</text>
                  <text v-if="model.min_price !== null" class="model-price">
                    {{ formatPrice(model.min_price) }}+
                  </text>
                </view>
              </view>
            </view>
          </scroll-view>
        </view>
      </template>

      <!-- 无型号数据的品牌：显示品牌卡片网格 -->
      <template v-else-if="topBrands.length > 0">
        <view class="brands-grid-section">
          <scroll-view scroll-x class="brands-grid-scroll" :show-scrollbar="false">
            <view class="brands-grid-container">
              <view
                v-for="brand in topBrands"
                :key="brand.brand_value"
                class="brand-card"
                @click="goBrandGoods(brand)"
              >
                <view class="brand-card-image">
                  <image
                    v-if="brand.brand_image"
                    class="brand-logo"
                    :src="brand.brand_image"
                    mode="aspectFit"
                  />
                  <view v-else class="brand-logo-placeholder">
                    <text class="brand-initial">{{ brand.brand_name.charAt(0).toUpperCase() }}</text>
                  </view>
                </view>
                <text class="brand-card-name">{{ brand.brand_name }}</text>
                <text v-if="brand.goods_count" class="brand-card-count">
                  {{ brand.goods_count }} {{ t('goods.items') }}
                </text>
                <text v-if="brand.min_price !== null && brand.min_price !== undefined" class="brand-card-price">
                  {{ formatPrice(brand.min_price) }}+
                </text>
              </view>
            </view>
          </scroll-view>
        </view>
      </template>

      <!-- 商品列表标题 + 筛选/排序栏 -->
      <view v-if="goods.length > 0 || goodsLoading" class="goods-section-header">
        <text class="goods-section-title">{{ t('home.allProducts') }}</text>
        <view class="header-actions">
          <view class="sort-btn" @click="showSortPopup = true">
            <text class="bi bi-arrow-down-up"></text>
            <text>{{ t('goods.sort') }}</text>
          </view>
          <view class="filter-btn" @click="openFilter">
            <text class="bi bi-sliders"></text>
            <text>{{ t('goods.filterBtn') }}</text>
            <view v-if="selectedFilterCount > 0" class="filter-badge">{{ selectedFilterCount }}</view>
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
              @click="removeFilter(String(key))"
            >
              <text class="chip-text">{{ getFilterLabel(String(key), values) }}</text>
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
      <view v-if="goods.length > 0" class="products-grid">
        <view
          v-for="item in goods"
          :key="item.id"
          class="product-card"
          @click="goDetail(item.id)"
        >
          <view class="product-image-wrapper">
            <image
              class="product-image"
              :src="item.images[0]"
              mode="aspectFit"
              lazy-load
            />
            <view v-if="item.stock === 0" class="sold-out-overlay">
              <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
            </view>
            <view v-if="item.freeShipping && item.stock > 0" class="shipping-badge free">
              <text class="bi bi-truck"></text>
              <text>{{ t('goods.free') }}</text>
            </view>
            <!-- 收藏按钮 -->
            <view class="goods-like-btn" @click.stop="handleToggleLike(item)">
              <text class="bi" :class="item.isLiked ? 'bi-heart-fill liked' : 'bi-heart'"></text>
            </view>
          </view>

          <view class="product-info">
            <text class="product-title">{{ item.title }}</text>

            <view class="product-meta">
              <view class="product-price-row">
                <text class="product-price" :class="{ 'promo-price': item.promotion }">
                  {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                </text>
                <text v-if="item.promotion" class="product-original-price">
                  {{ formatPrice(item.price, item.currency) }}
                </text>
              </view>

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
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 商品加载中 -->
      <view v-if="goodsLoading" class="loading-more">
        <view class="loader-small">
          <view class="loader-ring"></view>
        </view>
        <text>{{ t('common.loading') }}</text>
      </view>

      <!-- 没有更多 -->
      <view v-else-if="noMore && goods.length > 0" class="no-more">
        <view class="divider-line"></view>
        <text>{{ t('common.noMore') }}</text>
        <view class="divider-line"></view>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && brands.length === 0 && goods.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('common.noData') }}</text>
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

            <!-- 搜索框 -->
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
              <view
                v-if="!pickerSearchKeyword"
                class="options-picker-item"
                :class="{ selected: !pickerTempValues.length }"
                @click="togglePickerOption(null)"
              >
                <text class="options-picker-item-text">{{ t('common.all') }}</text>
                <text v-if="!pickerTempValues.length" class="bi bi-check-lg options-picker-check"></text>
              </view>
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
import {
  getCategoryBrands,
  getGoodsList,
  getCategoryAttributes,
  toggleLike,
  type BrandWithModels,
  type ModelInfo,
  type Goods,
  type CategoryAttribute
} from '@/api/goods'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'
import ActionSheet, { type ActionSheetOption } from '@/components/ActionSheet.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const categoryId = ref<number>(0)
const categoryName = ref<string>('')
const brands = ref<BrandWithModels[]>([])
const loading = ref(true)

// 商品列表
const goods = ref<Goods[]>([])
const goodsLoading = ref(false)
const noMore = ref(false)
const page = ref(1)
const pageSize = 20

// 只显示前5个品牌
const topBrands = computed(() => brands.value.slice(0, 5))

// 是否有型号数据（判断第一个品牌是否有 models）
const hasModels = computed(() => {
  return topBrands.value.some(b => b.models && b.models.length > 0)
})

// 排序相关
const showSortPopup = ref(false)
const currentSort = ref('popular')

const sortOptions = computed(() => [
  { value: 'newest', label: t('goods.filter.newest') },
  { value: 'price_asc', label: t('goods.filter.priceAsc') },
  { value: 'price_desc', label: t('goods.filter.priceDesc') },
  { value: 'popular', label: t('goods.filter.popular') }
])

// 筛选相关
const showFilterPopup = ref(false)
const filterVisible = ref(false)
const filterAttributes = ref<CategoryAttribute[]>([])
const selectedFilters = reactive<Record<string, string[]>>({})
const tempFilters = reactive<Record<string, string[]>>({})
const FILTER_COLLAPSE_THRESHOLD = 8

// 选项选择弹窗相关
const showOptionsPicker = ref(false)
const currentPickerAttr = ref<CategoryAttribute | null>(null)
const pickerTempValues = ref<string[]>([])
const pickerSearchKeyword = ref('')
const PICKER_SEARCH_THRESHOLD = 20

// 已选筛选数量
const selectedFilterCount = computed(() => {
  return Object.values(selectedFilters).filter(v => v && v.length > 0).length
})

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

// 格式化价格
function formatPrice(price: number, currency?: string): string {
  return appStore.formatPrice(price, currency)
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

// 加载品牌数据
async function loadData() {
  if (!categoryId.value) return

  loading.value = true
  try {
    const res = await getCategoryBrands(categoryId.value)
    brands.value = res.data.brands
    categoryName.value = res.data.category_name

    uni.setNavigationBarTitle({ title: categoryName.value })

    // 品牌加载完后加载商品列表和筛选属性
    loadGoods()
    loadFilterAttributes()
  } catch (e) {
    console.error('Failed to load category brands:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 加载商品列表
async function loadGoods(isRefresh = false) {
  if (goodsLoading.value) return

  if (isRefresh) {
    page.value = 1
    noMore.value = false
  }

  goodsLoading.value = true
  try {
    const filterParams: Record<string, string> = {}
    Object.keys(selectedFilters).forEach(key => {
      if (selectedFilters[key] && selectedFilters[key].length > 0) {
        filterParams[key] = selectedFilters[key].join(',')
      }
    })

    const res = await getGoodsList({
      categoryId: categoryId.value,
      page: page.value,
      pageSize,
      sort: currentSort.value as 'newest' | 'price_asc' | 'price_desc' | 'popular',
      ...filterParams
    })

    const list = res.data.list || []
    if (isRefresh) {
      goods.value = list
    } else {
      goods.value.push(...list)
    }

    if (list.length < pageSize) {
      noMore.value = true
    }
    page.value++
  } catch (e) {
    console.error('Failed to load goods:', e)
  } finally {
    goodsLoading.value = false
  }
}

// 加载筛选属性
async function loadFilterAttributes() {
  if (!categoryId.value) return

  try {
    const res = await getCategoryAttributes(categoryId.value)
    filterAttributes.value = res.data.attributes

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

// 收藏/取消收藏
async function handleToggleLike(item: Goods) {
  try {
    const res = await toggleLike(item.id)
    if (res.code === 0) {
      item.isLiked = res.data.isLiked
    }
  } catch (e) {
    console.error('Failed to toggle like:', e)
  }
}

// 排序选择
function handleSortSelect(option: ActionSheetOption) {
  currentSort.value = option.value as string
  loadGoods(true)
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

// 清空所有筛选
function clearAllFilters() {
  Object.keys(selectedFilters).forEach(key => {
    selectedFilters[key] = []
  })
  loadGoods(true)
}

// 判断筛选组是否需要使用选择框
function shouldCollapseGroup(attr: CategoryAttribute): boolean {
  return (attr.options?.length || 0) + 1 > FILTER_COLLAPSE_THRESHOLD
}

// 获取选项的显示标签
function getOptionLabel(attr: CategoryAttribute, value: string): string {
  const option = attr.options?.find(o => o.value === value)
  return option?.label || value
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

// 打开选项选择弹窗
function openOptionsPicker(attr: CategoryAttribute) {
  currentPickerAttr.value = attr
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

// 跳转到商品详情
function goDetail(id: number) {
  uni.navigateTo({
    url: `/pages/goods/detail?id=${id}`
  })
}

// 跳转到品牌商品列表（无型号的分类）
function goBrandGoods(brand: BrandWithModels) {
  uni.navigateTo({
    url: `/pages/goods/list?categoryId=${categoryId.value}&brand=${encodeURIComponent(brand.brand_value)}&title=${encodeURIComponent(brand.brand_name)}`
  })
}

// 跳转到型号商品列表
function goGoodsList(model: ModelInfo) {
  const params: string[] = [
    `categoryId=${categoryId.value}`,
    `model=${encodeURIComponent(model.value)}`,
    `title=${encodeURIComponent(model.name)}`
  ]

  if (model.image) {
    params.push(`image=${encodeURIComponent(model.image)}`)
  }
  if (model.min_price !== null) {
    params.push(`min_price=${model.min_price}`)
  }

  uni.navigateTo({
    url: `/pages/goods/model-goods?${params.join('&')}`
  })
}

// 触底加载更多
onReachBottom(() => {
  if (!noMore.value && !goodsLoading.value) {
    loadGoods()
  }
})

onLoad((options) => {
  categoryId.value = Number(options?.id || 0)
  if (options?.title) {
    categoryName.value = decodeURIComponent(options.title)
    uni.setNavigationBarTitle({ title: categoryName.value })
  }
})

onMounted(() => {
  loadData()
})
</script>

<style lang="scss" scoped>
$primary: #FF6B35;
$primary-light: #FF8555;
$text-primary: #1E293B;
$text-secondary: #64748B;
$text-muted: #94A3B8;
$border: #E2E8F0;
$background: #F8FAFC;
$surface: #FFFFFF;
$radius-sm: 8rpx;
$radius-md: 16rpx;
$radius-lg: 24rpx;
$radius-full: 9999rpx;
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);

.page {
  min-height: 100vh;
}

.content-scroll {
  flex: 1;
}

.brand-section {
  margin-bottom: 20rpx;
  background-color: #fff;
}

.brand-header {
  padding: 20rpx;
  text-align: center;
}

.brand-name {
  font-size: 34rpx;
  font-weight: 600;
  color: #333;
}

.models-scroll {
  white-space: nowrap;
  padding: 20rpx 20rpx;

  ::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
    background: transparent;
  }
}

.models-container {
  display: inline-flex;
  padding: 0 20rpx;
  gap: 20rpx;
}

.model-card {
  display: inline-flex;
  flex-direction: row;
  width: 300rpx;
  min-height: 220rpx;
  background-color: #fff;
  border-radius: 30rpx;
  border: 2rpx solid #e8e8e8;
  overflow: hidden;
  flex-shrink: 0;
}

.model-image-wrapper {
  width: 130rpx;
  height: 220rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fff;
  flex-shrink: 0;
}

.model-image {
  width: 110rpx;
  height: 110rpx;
}

.model-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background-color: #f5f5f5;
  font-size: 60rpx;
  color: #ccc;
}

.model-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 16rpx 20rpx 16rpx 0;
}

.model-name {
  display: block;
  font-size: 26rpx;
  color: #333;
  white-space: normal;
  word-break: break-word;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.model-price {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: $primary;
}

/* 品牌卡片网格（无型号的分类） */
.brands-grid-section {
  background-color: #fff;
  padding: 20rpx 0;
  margin-bottom: 20rpx;
}

.brands-grid-scroll {
  white-space: nowrap;

  ::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
    background: transparent;
  }
}

.brands-grid-container {
  display: inline-flex;
  padding: 0 24rpx;
  gap: 20rpx;
}

.brand-card {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  width: 200rpx;
  padding: 24rpx 16rpx;
  background: $background;
  border-radius: $radius-lg;
  flex-shrink: 0;
}

.brand-card-image {
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16rpx;
}

.brand-logo {
  width: 100%;
  height: 100%;
}

.brand-logo-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, $primary, $primary-light);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-initial {
  font-size: 48rpx;
  font-weight: 700;
  color: #fff;
}

.brand-card-name {
  font-size: 26rpx;
  font-weight: 600;
  color: $text-primary;
  text-align: center;
  white-space: normal;
  word-break: break-word;
  line-height: 1.3;
  margin-bottom: 4rpx;
}

.brand-card-count {
  font-size: 22rpx;
  color: $text-muted;
}

.brand-card-price {
  font-size: 24rpx;
  font-weight: 600;
  color: $primary;
  margin-top: 4rpx;
}

/* 商品列表区域 */
.goods-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 30rpx 16rpx;
  background-color: $background;
}

.goods-section-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-primary;
}

.header-actions {
  display: flex;
  gap: 16rpx;
}

.sort-btn,
.filter-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 20rpx;
  background: $surface;
  border-radius: $radius-full;
  font-size: 24rpx;
  color: $text-secondary;
  box-shadow: $shadow-sm;
  position: relative;

  .bi {
    font-size: 24rpx;
  }
}

.filter-badge {
  position: absolute;
  top: -8rpx;
  right: -8rpx;
  min-width: 32rpx;
  height: 32rpx;
  background: $primary;
  color: #fff;
  font-size: 20rpx;
  font-weight: 600;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 8rpx;
}

/* 已选筛选条件 */
.active-filters {
  padding: 0 24rpx 16rpx;
  background-color: $background;
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

/* 商品网格 */
.products-grid {
  display: flex;
  flex-wrap: wrap;
  padding: 16rpx;
  gap: 16rpx;
  background-color: $background;
}

.product-card {
  width: calc(50% - 8rpx);
  background-color: #fff;
  border-radius: $radius-md;
  overflow: hidden;
}

.product-image-wrapper {
  position: relative;
  width: 100%;
  height: 340rpx;
  background-color: #f8f8f8;
}

.product-image {
  width: 100%;
  height: 100%;
}

.sold-out-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.sold-out-text {
  color: #fff;
  font-size: 28rpx;
  font-weight: 600;
}

.shipping-badge {
  position: absolute;
  top: 12rpx;
  left: 12rpx;
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 8rpx 14rpx;
  background: #10b981;
  border-radius: $radius-full;
  font-size: 22rpx;
  font-weight: 500;
  color: #fff;

  .bi {
    font-size: 22rpx;
  }
}

// 收藏按钮
.goods-like-btn {
  position: absolute;
  top: 12rpx;
  right: 12rpx;
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease;

  .bi {
    font-size: 28rpx;
    color: #999;
    transition: color 0.2s ease;
  }

  .liked {
    color: $primary;
  }

  &:active {
    transform: scale(1.15);
    background-color: #fff;
  }
}

.product-info {
  padding: 16rpx;
}

.product-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 26rpx;
  color: #333;
  line-height: 1.4;
  margin-bottom: 8rpx;
}

.product-meta {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.product-price-row {
  display: flex;
  align-items: baseline;
  gap: 8rpx;
  flex-wrap: wrap;
}

.product-price {
  font-size: 30rpx;
  font-weight: 700;
  color: $primary;

  &.promo-price {
    color: #e53935;
  }
}

.product-original-price {
  font-size: 22rpx;
  color: #999;
  text-decoration: line-through;
}

.promo-tag {
  display: inline-flex;
  align-items: center;
  gap: 4rpx;
  padding: 2rpx 10rpx;
  background-color: #fce4ec;
  color: #e53935;
  border-radius: 6rpx;
  font-size: 20rpx;
  align-self: flex-start;
}

.product-tags {
  display: flex;
  gap: 8rpx;
  flex-wrap: wrap;
}

.condition-tag {
  padding: 2rpx 10rpx;
  background-color: #f0f0f0;
  border-radius: 6rpx;
  font-size: 20rpx;
  color: #666;
}

.negotiable-tag {
  padding: 2rpx 10rpx;
  background-color: #e3f2fd;
  border-radius: 6rpx;
  font-size: 20rpx;
  color: #1565c0;
}

/* 加载更多 */
.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 30rpx 0;
  color: #999;
  font-size: 24rpx;
}

.loader-small {
  width: 32rpx;
  height: 32rpx;
}

.loader-ring {
  width: 100%;
  height: 100%;
  border: 3rpx solid #e0e0e0;
  border-top-color: $primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.no-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  padding: 30rpx 0;
  color: #ccc;
  font-size: 24rpx;
}

.divider-line {
  width: 60rpx;
  height: 1rpx;
  background-color: #e0e0e0;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 0;
}

.empty-icon {
  font-size: 120rpx;
  color: #ddd;
  margin-bottom: 20rpx;
}

.empty-text {
  font-size: 28rpx;
  color: #999;
}

.safe-bottom {
  height: env(safe-area-inset-bottom);
}

/* 筛选弹窗 */
.filter-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
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
  background: $surface;
  border-radius: 32rpx 32rpx 0 0;
  max-height: 80vh;
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
  padding: 16rpx 0 8rpx;
}

.handle-bar {
  width: 64rpx;
  height: 8rpx;
  background: #E0E0E0;
  border-radius: 4rpx;
}

.sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 32rpx;
}

.sheet-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-primary;
}

.sheet-close {
  width: 64rpx;
  height: 64rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: $background;

  .bi {
    font-size: 28rpx;
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
  margin-bottom: 32rpx;
}

.filter-group-header {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 16rpx;
}

.filter-group-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $text-primary;
}

.filter-group-count {
  min-width: 36rpx;
  height: 36rpx;
  background: $primary;
  color: #fff;
  font-size: 22rpx;
  font-weight: 600;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 10rpx;
}

.filter-options-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.filter-option-btn {
  padding: 14rpx 24rpx;
  background: $background;
  border-radius: $radius-full;
  font-size: 26rpx;
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
    transform: scale(0.95);
  }
}

.filter-select-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 24rpx;
  background: $background;
  border-radius: $radius-md;
  border: 2rpx solid $border;
}

.filter-select-content {
  flex: 1;
}

.filter-select-placeholder {
  font-size: 26rpx;
  color: $text-muted;
}

.filter-selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.filter-selected-tag {
  padding: 6rpx 16rpx;
  background: rgba($primary, 0.1);
  color: $primary;
  border-radius: $radius-full;
  font-size: 24rpx;
}

.filter-more-tag {
  padding: 6rpx 16rpx;
  color: $text-muted;
  font-size: 24rpx;
}

.filter-select-arrow {
  font-size: 24rpx;
  color: $text-muted;
  margin-left: 12rpx;
}

.sheet-footer {
  display: flex;
  gap: 20rpx;
  padding: 24rpx 32rpx;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
  border-top: 1rpx solid $border;
}

.footer-btn {
  flex: 1;
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-full;
  font-size: 30rpx;
  font-weight: 500;

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

/* 选项选择弹窗 */
.options-picker-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 1001;
  display: flex;
  align-items: flex-end;
}

.options-picker-sheet {
  width: 100%;
  background: $surface;
  border-radius: 32rpx 32rpx 0 0;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}

.options-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 32rpx;
  border-bottom: 1rpx solid $border;
}

.options-picker-cancel,
.options-picker-confirm {
  padding: 8rpx 16rpx;
  font-size: 28rpx;
}

.options-picker-cancel {
  color: $text-secondary;
}

.options-picker-confirm {
  color: $primary;
  font-weight: 600;
}

.options-picker-title {
  font-size: 30rpx;
  font-weight: 600;
  color: $text-primary;
}

.options-picker-search {
  padding: 16rpx 32rpx;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  background: $background;
  border-radius: $radius-full;
  padding: 0 24rpx;
  height: 72rpx;
}

.search-icon {
  font-size: 28rpx;
  color: $text-muted;
  margin-right: 12rpx;
}

.search-input {
  flex: 1;
  font-size: 28rpx;
  color: $text-primary;
}

.search-clear {
  padding: 8rpx;

  .bi {
    font-size: 28rpx;
    color: $text-muted;
  }
}

.options-picker-list {
  flex: 1;
  max-height: 50vh;
}

.options-picker-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 32rpx;
  border-bottom: 1rpx solid $border;

  &.selected {
    background: rgba($primary, 0.05);
  }
}

.options-picker-item-text {
  font-size: 28rpx;
  color: $text-primary;
}

.options-picker-check {
  font-size: 32rpx;
  color: $primary;
}

.options-picker-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60rpx 0;
}

.options-picker-empty-icon {
  font-size: 60rpx;
  color: $text-muted;
  margin-bottom: 16rpx;
}

.options-picker-empty-text {
  font-size: 28rpx;
  color: $text-muted;
}
</style>
