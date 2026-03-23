<template>
  <view class="page">
    <NavBar :title="categoryTitle" />

    <scroll-view class="content" scroll-y>
      <!-- 分类描述 -->
      <view class="category-header">
        <view class="category-icon" :class="categoryId">
          <text class="bi" :class="categoryIcon"></text>
        </view>
        <text class="category-title">{{ categoryTitle }}</text>
        <text class="category-desc">{{ categoryDesc }}</text>
      </view>

      <!-- 文章列表 -->
      <view class="article-list">
        <view class="section-card">
          <view
            v-for="article in articles"
            :key="article.id"
            class="article-item"
            @click="goArticle(article.id)"
          >
            <view class="article-icon">
              <text class="bi" :class="article.icon"></text>
            </view>
            <view class="article-content">
              <text class="article-title">{{ article.title }}</text>
              <text class="article-desc">{{ article.desc }}</text>
            </view>
            <text class="bi bi-chevron-right article-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 常见问题 -->
      <view v-if="faqs.length > 0" class="faq-section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.faq') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="(faq, index) in faqs"
            :key="index"
            class="faq-item"
            @click="toggleFaq(index)"
          >
            <view class="faq-header">
              <text class="faq-question">{{ faq.question }}</text>
              <text
                class="bi faq-arrow"
                :class="expandedFaq === index ? 'bi-chevron-up' : 'bi-chevron-down'"
              ></text>
            </view>
            <view v-if="expandedFaq === index" class="faq-answer">
              <text>{{ faq.answer }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 联系支持 -->
      <view class="support-section">
        <view class="support-card">
          <text class="bi bi-headset support-icon"></text>
          <view class="support-content">
            <text class="support-title">{{ t('guide.stillNeedHelp') }}</text>
            <text class="support-desc">{{ t('guide.contactSupportDesc') }}</text>
          </view>
          <view class="support-btn" @click="goTickets">
            <text>{{ t('guide.contactUs') }}</text>
          </view>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()

// 分类 ID
const categoryId = ref('')

// 展开的 FAQ 索引
const expandedFaq = ref<number | null>(null)

// 分类配置
const categoryConfig: Record<string, {
  title: string
  desc: string
  icon: string
  articles: { id: string; title: string; desc: string; icon: string }[]
  faqs: { question: string; answer: string }[]
}> = {
  buying: {
    title: 'guide.buying',
    desc: 'guide.buyingDesc',
    icon: 'bi-bag',
    articles: [
      { id: 'how-to-buy', title: 'guide.howToBuy', desc: 'guide.howToBuyDesc', icon: 'bi-cart-check' },
      { id: 'payment-methods', title: 'guide.paymentMethods', desc: 'guide.paymentMethodsDesc', icon: 'bi-credit-card' },
      { id: 'track-order', title: 'guide.trackOrder', desc: 'guide.trackOrderDesc', icon: 'bi-geo-alt' },
      { id: 'returns-refunds', title: 'guide.returnsRefunds', desc: 'guide.returnsRefundsDesc', icon: 'bi-arrow-return-left' },
    ],
    faqs: [
      { question: 'guide.faq1Question', answer: 'guide.faq1Answer' },
      { question: 'guide.faq3Question', answer: 'guide.faq3Answer' },
    ],
  },
  selling: {
    title: 'guide.selling',
    desc: 'guide.sellingDesc',
    icon: 'bi-tag',
    articles: [
      { id: 'how-to-sell', title: 'guide.howToSell', desc: 'guide.howToSellDesc', icon: 'bi-shop' },
      { id: 'listing-tips', title: 'guide.listingTips', desc: 'guide.listingTipsDesc', icon: 'bi-lightbulb' },
      { id: 'shipping-guide', title: 'guide.shippingGuide', desc: 'guide.shippingGuideDesc', icon: 'bi-box-seam' },
      { id: 'seller-fees', title: 'guide.sellerFees', desc: 'guide.sellerFeesDesc', icon: 'bi-percent' },
    ],
    faqs: [
      { question: 'guide.faq5Question', answer: 'guide.faq5Answer' },
    ],
  },
  account: {
    title: 'guide.account',
    desc: 'guide.accountDesc',
    icon: 'bi-person',
    articles: [
      { id: 'account-settings', title: 'guide.accountSettings', desc: 'guide.accountSettingsDesc', icon: 'bi-gear' },
      { id: 'security-tips', title: 'guide.securityTips', desc: 'guide.securityTipsDesc', icon: 'bi-shield-check' },
      { id: 'report-issue', title: 'guide.reportIssue', desc: 'guide.reportIssueDesc', icon: 'bi-flag' },
    ],
    faqs: [
      { question: 'guide.faq2Question', answer: 'guide.faq2Answer' },
    ],
  },
  shipping: {
    title: 'guide.shipping',
    desc: 'guide.shippingCategoryDesc',
    icon: 'bi-truck',
    articles: [
      { id: 'track-order', title: 'guide.trackOrder', desc: 'guide.trackOrderDesc', icon: 'bi-geo-alt' },
      { id: 'shipping-guide', title: 'guide.shippingGuide', desc: 'guide.shippingGuideDesc', icon: 'bi-box-seam' },
      { id: 'returns-refunds', title: 'guide.returnsRefunds', desc: 'guide.returnsRefundsDesc', icon: 'bi-arrow-return-left' },
    ],
    faqs: [
      { question: 'guide.faq4Question', answer: 'guide.faq4Answer' },
      { question: 'guide.faq5Question', answer: 'guide.faq5Answer' },
    ],
  },
}

// 分类标题
const categoryTitle = computed(() => {
  const config = categoryConfig[categoryId.value]
  return config ? t(config.title) : t('guide.title')
})

// 分类描述
const categoryDesc = computed(() => {
  const config = categoryConfig[categoryId.value]
  return config ? t(config.desc) : ''
})

// 分类图标
const categoryIcon = computed(() => {
  const config = categoryConfig[categoryId.value]
  return config?.icon || 'bi-book'
})

// 文章列表
const articles = computed(() => {
  const config = categoryConfig[categoryId.value]
  if (!config) return []
  return config.articles.map(a => ({
    id: a.id,
    title: t(a.title),
    desc: t(a.desc),
    icon: a.icon,
  }))
})

// FAQ 列表
const faqs = computed(() => {
  const config = categoryConfig[categoryId.value]
  if (!config) return []
  return config.faqs.map(f => ({
    question: t(f.question),
    answer: t(f.answer),
  }))
})

onLoad((options: any) => {
  if (options?.id) {
    categoryId.value = options.id
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: categoryTitle.value })
})

// 切换 FAQ
function toggleFaq(index: number) {
  expandedFaq.value = expandedFaq.value === index ? null : index
}

// 跳转文章
function goArticle(id: string) {
  uni.navigateTo({ url: `/pages/guide/article?id=${id}` })
}

// 联系客服
function goTickets() {
  uni.navigateTo({ url: '/pages/support/index' })
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

.content {
  flex: 1;
  width: auto;
}

// 分类头部
.category-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 16px;
  background-color: $color-surface;
  margin-bottom: 16px;
}

.category-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;

  .bi {
    font-size: 28px;
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

.category-title {
  font-size: 20px;
  font-weight: 700;
  color: $color-text;
  margin-bottom: 8px;
}

.category-desc {
  font-size: 14px;
  color: $color-text-secondary;
  text-align: center;
  line-height: 1.5;
}

// 文章列表
.article-list {
  padding: 0 16px;
  margin-bottom: 16px;
}

.section-card {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

.article-item {
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

.article-icon {
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

.article-content {
  flex: 1;
  min-width: 0;
}

.article-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.article-desc {
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
}

.article-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// FAQ 区域
.faq-section {
  padding: 0 16px;
  margin-bottom: 16px;
}

.section-header {
  padding: 8px 4px 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
}

.faq-item {
  padding: 16px;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:last-child {
    border-bottom: none;
  }
}

.faq-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.faq-question {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  line-height: 1.4;
}

.faq-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
  margin-top: 2px;
}

.faq-answer {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed $color-border;

  text {
    font-size: 14px;
    color: $color-text-secondary;
    line-height: 1.6;
  }
}

// 支持区域
.support-section {
  padding: 0 16px;
  margin-bottom: 16px;
}

.support-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background-color: $color-surface;
  border-radius: 12px;
  padding: 16px;
}

.support-icon {
  font-size: 28px;
  color: $color-primary;
  flex-shrink: 0;
}

.support-content {
  flex: 1;
  min-width: 0;
}

.support-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.support-desc {
  font-size: 12px;
  color: $color-text-muted;
}

.support-btn {
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 13px;
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 6px;
  flex-shrink: 0;
  transition: opacity 0.2s;

  &:active {
    opacity: 0.8;
  }
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
