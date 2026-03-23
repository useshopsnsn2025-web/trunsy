<template>
  <view class="page">
    <NavBar :title="t('guide.title')" />

    <scroll-view class="content" scroll-y>
      <!-- 顶部搜索 -->
      <view class="search-section">
        <view class="search-header">
          <text class="search-title">{{ t('guide.searchTitle') }}</text>
          <text class="search-subtitle">{{ t('guide.searchSubtitle') }}</text>
        </view>
        <view class="search-box" @click="goSearch">
          <text class="bi bi-search search-icon"></text>
          <text class="search-placeholder">{{ t('guide.searchPlaceholder') }}</text>
        </view>
      </view>

      <!-- 快速入口 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.quickActions') }}</text>
        </view>
        <view class="quick-grid">
          <view class="quick-item" @click="goCategory('buying')">
            <view class="quick-icon buying">
              <text class="bi bi-bag"></text>
            </view>
            <text class="quick-label">{{ t('guide.buying') }}</text>
          </view>
          <view class="quick-item" @click="goCategory('selling')">
            <view class="quick-icon selling">
              <text class="bi bi-tag"></text>
            </view>
            <text class="quick-label">{{ t('guide.selling') }}</text>
          </view>
          <view class="quick-item" @click="goCategory('account')">
            <view class="quick-icon account">
              <text class="bi bi-person"></text>
            </view>
            <text class="quick-label">{{ t('guide.account') }}</text>
          </view>
          <view class="quick-item" @click="goCategory('shipping')">
            <view class="quick-icon shipping">
              <text class="bi bi-truck"></text>
            </view>
            <text class="quick-label">{{ t('guide.shipping') }}</text>
          </view>
        </view>
      </view>

      <!-- 买家指南 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.buyerGuide') }}</text>
        </view>
        <view class="section-card">
          <view class="guide-item" @click="goArticle('how-to-buy')">
            <view class="guide-icon">
              <text class="bi bi-cart-check"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.howToBuy') }}</text>
              <text class="guide-desc">{{ t('guide.howToBuyDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('payment-methods')">
            <view class="guide-icon">
              <text class="bi bi-credit-card"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.paymentMethods') }}</text>
              <text class="guide-desc">{{ t('guide.paymentMethodsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('track-order')">
            <view class="guide-icon">
              <text class="bi bi-geo-alt"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.trackOrder') }}</text>
              <text class="guide-desc">{{ t('guide.trackOrderDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('returns-refunds')">
            <view class="guide-icon">
              <text class="bi bi-arrow-return-left"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.returnsRefunds') }}</text>
              <text class="guide-desc">{{ t('guide.returnsRefundsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 卖家指南 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.sellerGuide') }}</text>
        </view>
        <view class="section-card">
          <view class="guide-item" @click="goArticle('how-to-sell')">
            <view class="guide-icon">
              <text class="bi bi-shop"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.howToSell') }}</text>
              <text class="guide-desc">{{ t('guide.howToSellDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('listing-tips')">
            <view class="guide-icon">
              <text class="bi bi-lightbulb"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.listingTips') }}</text>
              <text class="guide-desc">{{ t('guide.listingTipsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('shipping-guide')">
            <view class="guide-icon">
              <text class="bi bi-box-seam"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.shippingGuide') }}</text>
              <text class="guide-desc">{{ t('guide.shippingGuideDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('seller-fees')">
            <view class="guide-icon">
              <text class="bi bi-percent"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.sellerFees') }}</text>
              <text class="guide-desc">{{ t('guide.sellerFeesDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 账户与安全 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.accountSecurity') }}</text>
        </view>
        <view class="section-card">
          <view class="guide-item" @click="goArticle('account-settings')">
            <view class="guide-icon">
              <text class="bi bi-gear"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.accountSettings') }}</text>
              <text class="guide-desc">{{ t('guide.accountSettingsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('security-tips')">
            <view class="guide-icon">
              <text class="bi bi-shield-check"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.securityTips') }}</text>
              <text class="guide-desc">{{ t('guide.securityTipsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
          <view class="guide-item" @click="goArticle('report-issue')">
            <view class="guide-icon">
              <text class="bi bi-flag"></text>
            </view>
            <view class="guide-content">
              <text class="guide-title">{{ t('guide.reportIssue') }}</text>
              <text class="guide-desc">{{ t('guide.reportIssueDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right guide-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 联系我们 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('guide.needMoreHelp') }}</text>
        </view>
        <view class="section-card">
          <view class="contact-banner">
            <view class="contact-info">
              <text class="contact-title">{{ t('guide.contactSupport') }}</text>
              <text class="contact-desc">{{ t('guide.contactSupportDesc') }}</text>
            </view>
            <view class="contact-btn" @click="goTickets">
              <text>{{ t('guide.contactUs') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 常见问题 -->
      <view class="section">
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

// 展开的 FAQ 索引
const expandedFaq = ref<number | null>(null)

// FAQ 列表
const faqs = computed(() => [
  {
    question: t('guide.faq1Question'),
    answer: t('guide.faq1Answer'),
  },
  {
    question: t('guide.faq2Question'),
    answer: t('guide.faq2Answer'),
  },
  {
    question: t('guide.faq3Question'),
    answer: t('guide.faq3Answer'),
  },
  {
    question: t('guide.faq4Question'),
    answer: t('guide.faq4Answer'),
  },
  {
    question: t('guide.faq5Question'),
    answer: t('guide.faq5Answer'),
  },
])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('guide.title') })
})

// 切换 FAQ 展开状态
function toggleFaq(index: number) {
  expandedFaq.value = expandedFaq.value === index ? null : index
}

// 搜索
function goSearch() {
  uni.navigateTo({ url: '/pages/guide/search' })
}

// 分类
function goCategory(category: string) {
  uni.navigateTo({ url: `/pages/guide/category?id=${category}` })
}

// 文章详情
function goArticle(articleId: string) {
  uni.navigateTo({ url: `/pages/guide/article?id=${articleId}` })
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

// 搜索区域
.search-section {
  background: linear-gradient(135deg, $color-primary 0%, darken($color-primary, 10%) 100%);
  padding: 24px 16px 32px;
  margin-bottom: 16px;
}

.search-header {
  text-align: center;
  margin-bottom: 20px;
}

.search-title {
  display: block;
  font-size: 22px;
  font-weight: 700;
  color: #FFFFFF;
  margin-bottom: 8px;
}

.search-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
}

.search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background-color: #FFFFFF;
  border-radius: 12px;
  padding: 14px 16px;
  transition: box-shadow 0.2s;

  &:active {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
}

.search-icon {
  font-size: 18px;
  color: $color-text-muted;
}

.search-placeholder {
  font-size: 15px;
  color: $color-text-muted;
}

// 区块
.section {
  padding: 0 16px;
  margin-bottom: 20px;
}

.section-header {
  padding: 8px 4px 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
}

.section-card {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

// 快速入口网格
.quick-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  background-color: $color-surface;
  border-radius: 12px;
  padding: 16px;
}

.quick-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  cursor: pointer;

  &:active {
    opacity: 0.7;
  }
}

.quick-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 24px;
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

.quick-label {
  font-size: 12px;
  color: $color-text-secondary;
  text-align: center;
}

// 指南项目
.guide-item {
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

.guide-icon {
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

.guide-content {
  flex: 1;
  min-width: 0;
}

.guide-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.guide-desc {
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
}

.guide-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// 联系横幅
.contact-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 16px;
  background: linear-gradient(135deg, $color-primary-light 0%, #FFF 100%);
}

.contact-info {
  flex: 1;
}

.contact-title {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: $color-text;
  margin-bottom: 4px;
}

.contact-desc {
  font-size: 13px;
  color: $color-text-secondary;
}

.contact-btn {
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 14px;
  font-weight: 500;
  padding: 10px 20px;
  border-radius: 8px;
  flex-shrink: 0;
  transition: opacity 0.2s;

  &:active {
    opacity: 0.8;
  }
}

// FAQ
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

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
