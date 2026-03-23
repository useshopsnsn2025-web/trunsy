<template>
  <view class="page">
    <NavBar :title="articleTitle" />

    <scroll-view class="content" scroll-y>
      <!-- 文章内容 -->
      <view class="article-container">
        <!-- 文章标题 -->
        <view class="article-header">
          <text class="article-title">{{ articleTitle }}</text>
          <text class="article-meta">{{ t('guide.lastUpdated') }}: {{ lastUpdated }}</text>
        </view>

        <!-- 文章正文 -->
        <view class="article-body">
          <!-- 如何购买 -->
          <template v-if="articleId === 'how-to-buy'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToBuy.step1Title') }}</text>
              <text class="section-text">{{ t('guide.article.howToBuy.step1Text') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToBuy.step2Title') }}</text>
              <text class="section-text">{{ t('guide.article.howToBuy.step2Text') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToBuy.step3Title') }}</text>
              <text class="section-text">{{ t('guide.article.howToBuy.step3Text') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToBuy.step4Title') }}</text>
              <text class="section-text">{{ t('guide.article.howToBuy.step4Text') }}</text>
            </view>
          </template>

          <!-- 付款方式 -->
          <template v-else-if="articleId === 'payment-methods'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.paymentMethods.creditCardTitle') }}</text>
              <text class="section-text">{{ t('guide.article.paymentMethods.creditCardText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.paymentMethods.codTitle') }}</text>
              <text class="section-text">{{ t('guide.article.paymentMethods.codText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.paymentMethods.installmentTitle') }}</text>
              <text class="section-text">{{ t('guide.article.paymentMethods.installmentText') }}</text>
            </view>
          </template>

          <!-- 追踪订单 -->
          <template v-else-if="articleId === 'track-order'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.trackOrder.howToTrackTitle') }}</text>
              <text class="section-text">{{ t('guide.article.trackOrder.howToTrackText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.trackOrder.statusTitle') }}</text>
              <text class="section-text">{{ t('guide.article.trackOrder.statusText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.trackOrder.issueTitle') }}</text>
              <text class="section-text">{{ t('guide.article.trackOrder.issueText') }}</text>
            </view>
          </template>

          <!-- 退货退款 -->
          <template v-else-if="articleId === 'returns-refunds'">
            <!-- 概述 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.overviewTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.overviewText') }}</text>
            </view>

            <!-- 买家保障 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.buyerProtectionTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.buyerProtectionText') }}</text>
            </view>

            <!-- 退货政策 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.policyTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.policyText') }}</text>
            </view>

            <!-- 二手商品特殊说明 -->
            <view class="section highlight-section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.usedItemsTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.usedItemsText') }}</text>
              <view class="info-list">
                <view class="info-item">
                  <text class="bi bi-check-circle-fill info-icon success"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.usedAccept1') }}</text>
                </view>
                <view class="info-item">
                  <text class="bi bi-check-circle-fill info-icon success"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.usedAccept2') }}</text>
                </view>
                <view class="info-item">
                  <text class="bi bi-check-circle-fill info-icon success"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.usedAccept3') }}</text>
                </view>
              </view>
            </view>

            <!-- 不可退货情况 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.nonReturnableTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.nonReturnableText') }}</text>
              <view class="info-list">
                <view class="info-item">
                  <text class="bi bi-x-circle-fill info-icon error"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.nonReturnable1') }}</text>
                </view>
                <view class="info-item">
                  <text class="bi bi-x-circle-fill info-icon error"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.nonReturnable2') }}</text>
                </view>
                <view class="info-item">
                  <text class="bi bi-x-circle-fill info-icon error"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.nonReturnable3') }}</text>
                </view>
                <view class="info-item">
                  <text class="bi bi-x-circle-fill info-icon error"></text>
                  <text class="info-text">{{ t('guide.article.returnsRefunds.nonReturnable4') }}</text>
                </view>
              </view>
            </view>

            <!-- 如何申请退货 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.howToTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.howToText') }}</text>
              <view class="steps-list">
                <view class="step-item">
                  <view class="step-number">1</view>
                  <text class="step-text">{{ t('guide.article.returnsRefunds.step1') }}</text>
                </view>
                <view class="step-item">
                  <view class="step-number">2</view>
                  <text class="step-text">{{ t('guide.article.returnsRefunds.step2') }}</text>
                </view>
                <view class="step-item">
                  <view class="step-number">3</view>
                  <text class="step-text">{{ t('guide.article.returnsRefunds.step3') }}</text>
                </view>
                <view class="step-item">
                  <view class="step-number">4</view>
                  <text class="step-text">{{ t('guide.article.returnsRefunds.step4') }}</text>
                </view>
              </view>
            </view>

            <!-- 退货运费 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.shippingCostTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.shippingCostText') }}</text>
            </view>

            <!-- 退款流程 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.refundTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.refundText') }}</text>
            </view>

            <!-- 退款时间 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.refundTimeTitle') }}</text>
              <view class="table-container">
                <view class="table-row table-header">
                  <text class="table-cell">{{ t('guide.article.returnsRefunds.paymentMethod') }}</text>
                  <text class="table-cell">{{ t('guide.article.returnsRefunds.refundTime') }}</text>
                </view>
                <view class="table-row">
                  <text class="table-cell">{{ t('guide.article.returnsRefunds.creditCard') }}</text>
                  <text class="table-cell">3-5 {{ t('guide.article.returnsRefunds.businessDays') }}</text>
                </view>
                <view class="table-row">
                  <text class="table-cell">PayPal</text>
                  <text class="table-cell">1-3 {{ t('guide.article.returnsRefunds.businessDays') }}</text>
                </view>
                <view class="table-row">
                  <text class="table-cell">{{ t('guide.article.returnsRefunds.bankTransfer') }}</text>
                  <text class="table-cell">5-7 {{ t('guide.article.returnsRefunds.businessDays') }}</text>
                </view>
              </view>
            </view>

            <!-- 争议处理 -->
            <view class="section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.disputeTitle') }}</text>
              <text class="section-text">{{ t('guide.article.returnsRefunds.disputeText') }}</text>
            </view>

            <!-- 温馨提示 -->
            <view class="section tips-section">
              <text class="section-title">{{ t('guide.article.returnsRefunds.tipsTitle') }}</text>
              <view class="tips-list">
                <view class="tip-item">
                  <text class="bi bi-lightbulb tip-icon"></text>
                  <text class="tip-text">{{ t('guide.article.returnsRefunds.tip1') }}</text>
                </view>
                <view class="tip-item">
                  <text class="bi bi-lightbulb tip-icon"></text>
                  <text class="tip-text">{{ t('guide.article.returnsRefunds.tip2') }}</text>
                </view>
                <view class="tip-item">
                  <text class="bi bi-lightbulb tip-icon"></text>
                  <text class="tip-text">{{ t('guide.article.returnsRefunds.tip3') }}</text>
                </view>
              </view>
            </view>
          </template>

          <!-- 如何出售 -->
          <template v-else-if="articleId === 'how-to-sell'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToSell.getStartedTitle') }}</text>
              <text class="section-text">{{ t('guide.article.howToSell.getStartedText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToSell.listingTitle') }}</text>
              <text class="section-text">{{ t('guide.article.howToSell.listingText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.howToSell.pricingTitle') }}</text>
              <text class="section-text">{{ t('guide.article.howToSell.pricingText') }}</text>
            </view>
          </template>

          <!-- 刊登技巧 -->
          <template v-else-if="articleId === 'listing-tips'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.listingTips.photosTitle') }}</text>
              <text class="section-text">{{ t('guide.article.listingTips.photosText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.listingTips.descTitle') }}</text>
              <text class="section-text">{{ t('guide.article.listingTips.descText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.listingTips.tagsTitle') }}</text>
              <text class="section-text">{{ t('guide.article.listingTips.tagsText') }}</text>
            </view>
          </template>

          <!-- 出货指南 -->
          <template v-else-if="articleId === 'shipping-guide'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.shippingGuide.packagingTitle') }}</text>
              <text class="section-text">{{ t('guide.article.shippingGuide.packagingText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.shippingGuide.carriersTitle') }}</text>
              <text class="section-text">{{ t('guide.article.shippingGuide.carriersText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.shippingGuide.labelsTitle') }}</text>
              <text class="section-text">{{ t('guide.article.shippingGuide.labelsText') }}</text>
            </view>
          </template>

          <!-- 卖家费用 -->
          <template v-else-if="articleId === 'seller-fees'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.sellerFees.overviewTitle') }}</text>
              <text class="section-text">{{ t('guide.article.sellerFees.overviewText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.sellerFees.ratesTitle') }}</text>
              <text class="section-text">{{ t('guide.article.sellerFees.ratesText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.sellerFees.payoutTitle') }}</text>
              <text class="section-text">{{ t('guide.article.sellerFees.payoutText') }}</text>
            </view>
          </template>

          <!-- 账户设定 -->
          <template v-else-if="articleId === 'account-settings'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.accountSettings.profileTitle') }}</text>
              <text class="section-text">{{ t('guide.article.accountSettings.profileText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.accountSettings.notificationsTitle') }}</text>
              <text class="section-text">{{ t('guide.article.accountSettings.notificationsText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.accountSettings.languageTitle') }}</text>
              <text class="section-text">{{ t('guide.article.accountSettings.languageText') }}</text>
            </view>
          </template>

          <!-- 安全提示 -->
          <template v-else-if="articleId === 'security-tips'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.securityTips.passwordTitle') }}</text>
              <text class="section-text">{{ t('guide.article.securityTips.passwordText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.securityTips.scamsTitle') }}</text>
              <text class="section-text">{{ t('guide.article.securityTips.scamsText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.securityTips.privacyTitle') }}</text>
              <text class="section-text">{{ t('guide.article.securityTips.privacyText') }}</text>
            </view>
          </template>

          <!-- 举报问题 -->
          <template v-else-if="articleId === 'report-issue'">
            <view class="section">
              <text class="section-title">{{ t('guide.article.reportIssue.whatTitle') }}</text>
              <text class="section-text">{{ t('guide.article.reportIssue.whatText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.reportIssue.howTitle') }}</text>
              <text class="section-text">{{ t('guide.article.reportIssue.howText') }}</text>
            </view>
            <view class="section">
              <text class="section-title">{{ t('guide.article.reportIssue.followupTitle') }}</text>
              <text class="section-text">{{ t('guide.article.reportIssue.followupText') }}</text>
            </view>
          </template>
        </view>

        <!-- 有帮助吗 -->
        <view class="feedback-section">
          <text class="feedback-title">{{ t('guide.wasHelpful') }}</text>
          <view class="feedback-buttons">
            <view
              class="feedback-btn"
              :class="{ active: feedback === 'yes' }"
              @click="submitFeedback('yes')"
            >
              <text class="bi bi-hand-thumbs-up"></text>
              <text>{{ t('guide.yes') }}</text>
            </view>
            <view
              class="feedback-btn"
              :class="{ active: feedback === 'no' }"
              @click="submitFeedback('no')"
            >
              <text class="bi bi-hand-thumbs-down"></text>
              <text>{{ t('guide.no') }}</text>
            </view>
          </view>
          <text v-if="feedbackSubmitted" class="feedback-thanks">{{ t('guide.thanksFeedback') }}</text>
        </view>

        <!-- 相关文章 -->
        <view class="related-section">
          <text class="related-title">{{ t('guide.relatedArticles') }}</text>
          <view class="related-list">
            <view
              v-for="article in relatedArticles"
              :key="article.id"
              class="related-item"
              @click="goArticle(article.id)"
            >
              <text class="related-text">{{ article.title }}</text>
              <text class="bi bi-chevron-right related-arrow"></text>
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

// 文章 ID
const articleId = ref('')

// 反馈状态
const feedback = ref<'yes' | 'no' | null>(null)
const feedbackSubmitted = ref(false)

// 文章标题映射
const articleTitles: Record<string, string> = {
  'how-to-buy': 'guide.howToBuy',
  'payment-methods': 'guide.paymentMethods',
  'track-order': 'guide.trackOrder',
  'returns-refunds': 'guide.returnsRefunds',
  'how-to-sell': 'guide.howToSell',
  'listing-tips': 'guide.listingTips',
  'shipping-guide': 'guide.shippingGuide',
  'seller-fees': 'guide.sellerFees',
  'account-settings': 'guide.accountSettings',
  'security-tips': 'guide.securityTips',
  'report-issue': 'guide.reportIssue',
}

// 相关文章映射
const relatedArticlesMap: Record<string, string[]> = {
  'how-to-buy': ['payment-methods', 'track-order', 'returns-refunds'],
  'payment-methods': ['how-to-buy', 'returns-refunds'],
  'track-order': ['how-to-buy', 'returns-refunds'],
  'returns-refunds': ['how-to-buy', 'track-order'],
  'how-to-sell': ['listing-tips', 'shipping-guide', 'seller-fees'],
  'listing-tips': ['how-to-sell', 'shipping-guide'],
  'shipping-guide': ['how-to-sell', 'listing-tips', 'seller-fees'],
  'seller-fees': ['how-to-sell', 'shipping-guide'],
  'account-settings': ['security-tips', 'report-issue'],
  'security-tips': ['account-settings', 'report-issue'],
  'report-issue': ['security-tips', 'account-settings'],
}

// 文章标题
const articleTitle = computed(() => {
  const key = articleTitles[articleId.value]
  return key ? t(key) : t('guide.title')
})

// 最后更新日期
const lastUpdated = computed(() => {
  return '2025-01-15'
})

// 相关文章
const relatedArticles = computed(() => {
  const ids = relatedArticlesMap[articleId.value] || []
  return ids.map(id => ({
    id,
    title: t(articleTitles[id] || ''),
  }))
})

onLoad((options: any) => {
  if (options?.id) {
    articleId.value = options.id
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: articleTitle.value })
})

// 提交反馈
function submitFeedback(value: 'yes' | 'no') {
  feedback.value = value
  feedbackSubmitted.value = true
  // 这里可以调用 API 提交反馈
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
$color-success: #52C41A;

.page {
  min-height: 100vh;
  background-color: $color-background;
}

.content {
  flex: 1;
  width: auto;
}

.article-container {
  padding: 16px;
}

// 文章头部
.article-header {
  background-color: $color-surface;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}

.article-title {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: $color-text;
  margin-bottom: 8px;
  line-height: 1.4;
}

.article-meta {
  font-size: 13px;
  color: $color-text-muted;
}

// 文章正文
.article-body {
  background-color: $color-surface;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
}

.section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.section-title {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
  margin-bottom: 8px;
}

.section-text {
  font-size: 15px;
  color: $color-text-secondary;
  line-height: 1.7;
}

// 高亮区块（二手商品说明）
.highlight-section {
  background-color: $color-primary-light;
  border-radius: 8px;
  padding: 16px;
  margin-top: -8px;
}

// 信息列表（可退/不可退）
.info-list {
  margin-top: 12px;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 8px;

  &:last-child {
    margin-bottom: 0;
  }
}

.info-icon {
  font-size: 16px;
  flex-shrink: 0;
  margin-top: 2px;

  &.success {
    color: $color-success;
  }

  &.error {
    color: #EF4444;
  }
}

.info-text {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.5;
}

// 步骤列表
.steps-list {
  margin-top: 16px;
}

.step-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;

  &:last-child {
    margin-bottom: 0;
  }
}

.step-number {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.step-text {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.6;
  flex: 1;
  padding-top: 2px;
}

// 表格样式
.table-container {
  margin-top: 12px;
  border: 1px solid $color-border;
  border-radius: 8px;
  overflow: hidden;
}

.table-row {
  display: flex;

  &:not(:last-child) {
    border-bottom: 1px solid $color-border;
  }

  &.table-header {
    background-color: $color-background;

    .table-cell {
      font-weight: 600;
      color: $color-text;
    }
  }
}

.table-cell {
  flex: 1;
  padding: 12px;
  font-size: 14px;
  color: $color-text-secondary;

  &:not(:last-child) {
    border-right: 1px solid $color-border;
  }
}

// 提示区块
.tips-section {
  background-color: #FEF3C7;
  border-radius: 8px;
  padding: 16px;
  margin-top: -8px;
}

.tips-list {
  margin-top: 12px;
}

.tip-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 10px;

  &:last-child {
    margin-bottom: 0;
  }
}

.tip-icon {
  font-size: 16px;
  color: #F59E0B;
  flex-shrink: 0;
  margin-top: 1px;
}

.tip-text {
  font-size: 14px;
  color: #92400E;
  line-height: 1.5;
}

// 反馈区域
.feedback-section {
  background-color: $color-surface;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  text-align: center;
}

.feedback-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 16px;
}

.feedback-buttons {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-bottom: 12px;
}

.feedback-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 24px;
  border: 1px solid $color-border;
  border-radius: 8px;
  font-size: 14px;
  color: $color-text-secondary;
  transition: all 0.2s;

  .bi {
    font-size: 18px;
  }

  &:active {
    background-color: $color-background;
  }

  &.active {
    border-color: $color-primary;
    background-color: $color-primary-light;
    color: $color-primary;
  }
}

.feedback-thanks {
  font-size: 13px;
  color: $color-success;
}

// 相关文章
.related-section {
  background-color: $color-surface;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
}

.related-title {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: $color-text;
  margin-bottom: 12px;
  padding: 0 4px;
}

.related-list {
  border-radius: 8px;
  overflow: hidden;
}

.related-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 4px;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: $color-background;
  }
}

.related-text {
  font-size: 14px;
  color: $color-primary;
}

.related-arrow {
  font-size: 12px;
  color: $color-text-muted;
}

// 支持区域
.support-section {
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
