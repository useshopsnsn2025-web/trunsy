<template>
  <view class="page">
    <NavBar :title="t('guide.search')" />

    <view class="search-container">
      <!-- 搜索框 -->
      <view class="search-box">
        <text class="bi bi-search search-icon"></text>
        <input
          v-model="keyword"
          class="search-input"
          type="text"
          :placeholder="t('guide.searchPlaceholder')"
          :focus="true"
          @input="handleSearch"
          @confirm="handleSearch"
        />
        <text
          v-if="keyword"
          class="bi bi-x-circle-fill clear-icon"
          @click="clearSearch"
        ></text>
      </view>
    </view>

    <scroll-view class="content" scroll-y>
      <!-- 搜索结果 -->
      <template v-if="keyword && searchResults.length > 0">
        <view class="results-header">
          <text class="results-count">{{ searchResults.length }} {{ t('guide.resultsFound') }}</text>
        </view>
        <view class="results-list">
          <view class="section-card">
            <view
              v-for="result in searchResults"
              :key="result.id"
              class="result-item"
              @click="goArticle(result.id)"
            >
              <view class="result-icon">
                <text class="bi" :class="result.icon"></text>
              </view>
              <view class="result-content">
                <text class="result-title">{{ result.title }}</text>
                <text class="result-desc">{{ result.desc }}</text>
                <text class="result-category">{{ result.category }}</text>
              </view>
              <text class="bi bi-chevron-right result-arrow"></text>
            </view>
          </view>
        </view>
      </template>

      <!-- 无搜索结果 -->
      <template v-else-if="keyword && searchResults.length === 0">
        <view class="empty-state">
          <text class="bi bi-search empty-icon"></text>
          <text class="empty-title">{{ t('guide.noResults') }}</text>
          <text class="empty-desc">{{ t('guide.noResultsDesc') }}</text>
        </view>
      </template>

      <!-- 热门搜索 -->
      <template v-else>
        <view class="hot-section">
          <view class="section-header">
            <text class="section-title">{{ t('guide.popularTopics') }}</text>
          </view>
          <view class="hot-tags">
            <view
              v-for="tag in hotTags"
              :key="tag.id"
              class="hot-tag"
              @click="searchTag(tag)"
            >
              <text>{{ tag.label }}</text>
            </view>
          </view>
        </view>

        <view class="browse-section">
          <view class="section-header">
            <text class="section-title">{{ t('guide.browseByCategory') }}</text>
          </view>
          <view class="category-list">
            <view
              v-for="cat in categories"
              :key="cat.id"
              class="category-item"
              @click="goCategory(cat.id)"
            >
              <view class="category-icon" :class="cat.id">
                <text class="bi" :class="cat.icon"></text>
              </view>
              <text class="category-label">{{ cat.label }}</text>
              <text class="bi bi-chevron-right category-arrow"></text>
            </view>
          </view>
        </view>
      </template>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()

// 搜索关键词
const keyword = ref('')

// 所有文章数据
const allArticles = computed(() => [
  // 买家指南
  { id: 'how-to-buy', title: t('guide.howToBuy'), desc: t('guide.howToBuyDesc'), icon: 'bi-cart-check', category: t('guide.buying') },
  { id: 'payment-methods', title: t('guide.paymentMethods'), desc: t('guide.paymentMethodsDesc'), icon: 'bi-credit-card', category: t('guide.buying') },
  { id: 'track-order', title: t('guide.trackOrder'), desc: t('guide.trackOrderDesc'), icon: 'bi-geo-alt', category: t('guide.buying') },
  { id: 'returns-refunds', title: t('guide.returnsRefunds'), desc: t('guide.returnsRefundsDesc'), icon: 'bi-arrow-return-left', category: t('guide.buying') },
  // 卖家指南
  { id: 'how-to-sell', title: t('guide.howToSell'), desc: t('guide.howToSellDesc'), icon: 'bi-shop', category: t('guide.selling') },
  { id: 'listing-tips', title: t('guide.listingTips'), desc: t('guide.listingTipsDesc'), icon: 'bi-lightbulb', category: t('guide.selling') },
  { id: 'shipping-guide', title: t('guide.shippingGuide'), desc: t('guide.shippingGuideDesc'), icon: 'bi-box-seam', category: t('guide.selling') },
  { id: 'seller-fees', title: t('guide.sellerFees'), desc: t('guide.sellerFeesDesc'), icon: 'bi-percent', category: t('guide.selling') },
  // 账户安全
  { id: 'account-settings', title: t('guide.accountSettings'), desc: t('guide.accountSettingsDesc'), icon: 'bi-gear', category: t('guide.account') },
  { id: 'security-tips', title: t('guide.securityTips'), desc: t('guide.securityTipsDesc'), icon: 'bi-shield-check', category: t('guide.account') },
  { id: 'report-issue', title: t('guide.reportIssue'), desc: t('guide.reportIssueDesc'), icon: 'bi-flag', category: t('guide.account') },
])

// 搜索结果
const searchResults = computed(() => {
  if (!keyword.value.trim()) return []
  const kw = keyword.value.toLowerCase()
  return allArticles.value.filter(a =>
    a.title.toLowerCase().includes(kw) ||
    a.desc.toLowerCase().includes(kw) ||
    a.category.toLowerCase().includes(kw)
  )
})

// 热门标签
const hotTags = computed(() => [
  { id: 'returns', label: t('guide.returnsRefunds'), keyword: t('guide.returnsRefunds') },
  { id: 'payment', label: t('guide.paymentMethods'), keyword: t('guide.paymentMethods') },
  { id: 'shipping', label: t('guide.shipping'), keyword: t('guide.shipping') },
  { id: 'sell', label: t('guide.howToSell'), keyword: t('guide.howToSell') },
  { id: 'fees', label: t('guide.sellerFees'), keyword: t('guide.sellerFees') },
  { id: 'security', label: t('guide.securityTips'), keyword: t('guide.securityTips') },
])

// 分类
const categories = computed(() => [
  { id: 'buying', label: t('guide.buying'), icon: 'bi-bag' },
  { id: 'selling', label: t('guide.selling'), icon: 'bi-tag' },
  { id: 'account', label: t('guide.account'), icon: 'bi-person' },
  { id: 'shipping', label: t('guide.shipping'), icon: 'bi-truck' },
])

onShow(() => {
  uni.setNavigationBarTitle({ title: t('guide.search') })
})

// 搜索处理
function handleSearch() {
  // 已通过 computed 自动处理
}

// 清除搜索
function clearSearch() {
  keyword.value = ''
}

// 搜索标签
function searchTag(tag: { keyword: string }) {
  keyword.value = tag.keyword
}

// 跳转文章
function goArticle(id: string) {
  uni.navigateTo({ url: `/pages/guide/article?id=${id}` })
}

// 跳转分类
function goCategory(id: string) {
  uni.navigateTo({ url: `/pages/guide/category?id=${id}` })
}
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #FF6B35;
$color-primary-light: #FFF5F2;
$color-text: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;

.page {
  min-height: 100vh;
  background-color: $color-background;
}

// 搜索框容器
.search-container {
  background-color: $color-surface;
  padding: 12px 16px;
  border-bottom: 1px solid $color-border;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background-color: $color-background;
  border-radius: 10px;
  padding: 12px 16px;
}

.search-icon {
  font-size: 18px;
  color: $color-text-muted;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  font-size: 15px;
  color: $color-text;
  background: transparent;

  &::placeholder {
    color: $color-text-muted;
  }
}

.clear-icon {
  font-size: 18px;
  color: $color-text-muted;
  flex-shrink: 0;
  padding: 4px;
}

.content {
  flex: 1;
  width: auto;
}

// 搜索结果
.results-header {
  padding: 16px 16px 8px;
}

.results-count {
  font-size: 13px;
  color: $color-text-muted;
}

.results-list {
  padding: 0 16px;
}

.section-card {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.result-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background-color: $color-primary-light;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 18px;
    color: $color-primary;
  }
}

.result-content {
  flex: 1;
  min-width: 0;
}

.result-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.result-desc {
  display: block;
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
  margin-bottom: 4px;
}

.result-category {
  font-size: 11px;
  color: $color-primary;
  background-color: $color-primary-light;
  padding: 2px 8px;
  border-radius: 4px;
}

.result-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 32px;
}

.empty-icon {
  font-size: 48px;
  color: $color-text-muted;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 16px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 8px;
}

.empty-desc {
  font-size: 14px;
  color: $color-text-muted;
  text-align: center;
}

// 热门搜索
.hot-section {
  padding: 16px;
}

.section-header {
  padding: 0 0 12px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-text;
}

.hot-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.hot-tag {
  background-color: $color-surface;
  border: 1px solid $color-border;
  border-radius: 20px;
  padding: 8px 16px;
  transition: all 0.2s;

  text {
    font-size: 14px;
    color: $color-text-secondary;
  }

  &:active {
    background-color: $color-primary-light;
    border-color: $color-primary;

    text {
      color: $color-primary;
    }
  }
}

// 分类浏览
.browse-section {
  padding: 0 16px 16px;
}

.category-list {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

.category-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.category-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 16px;
    color: #FFFFFF;
  }

  &.buying {
    background: linear-gradient(135deg, #4A90D9 0%, #357ABD 100%);
  }

  &.selling {
    background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
  }

  &.account {
    background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
  }

  &.shipping {
    background: linear-gradient(135deg, #F5A623 0%, #E09915 100%);
  }
}

.category-label {
  flex: 1;
  font-size: 15px;
  color: $color-text;
}

.category-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
