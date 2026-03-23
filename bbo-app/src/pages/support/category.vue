<template>
  <view class="page">
    <NavBar :title="categoryName" />

    <scroll-view class="content" scroll-y>
      <!-- 分类描述 -->
      <view class="category-header">
        <view class="category-icon" :class="categoryType">
          <text class="bi" :class="categoryIcon"></text>
        </view>
        <text class="category-desc">{{ categoryDesc }}</text>
      </view>

      <!-- 子分类列表 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('support.selectSubCategory') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="subCat in subCategories"
            :key="subCat.code"
            class="sub-item"
            @click="selectSubCategory(subCat)"
          >
            <text class="sub-label">{{ subCat.name }}</text>
            <text class="bi bi-chevron-right sub-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 相关常见问题 -->
      <view v-if="relatedFaqs.length > 0" class="section">
        <view class="section-header">
          <text class="section-title">{{ t('support.relatedFaq') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="(faq, index) in relatedFaqs"
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

      <!-- 直接创建工单按钮 -->
      <view class="section">
        <view class="create-btn" @click="goCreateTicket">
          <text class="bi bi-pencil-square"></text>
          <text class="create-text">{{ t('support.createTicketDirectly') }}</text>
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

// 分类类型
const categoryType = ref('')

// 展开的 FAQ 索引
const expandedFaq = ref<number | null>(null)

// 分类信息
const categoryInfo = computed(() => {
  const categories: Record<string, { name: string; icon: string; desc: string }> = {
    order: {
      name: t('support.order'),
      icon: 'bi-box-seam',
      desc: t('support.orderDesc'),
    },
    refund: {
      name: t('support.refund'),
      icon: 'bi-arrow-return-left',
      desc: t('support.refundDesc'),
    },
    payment: {
      name: t('support.payment'),
      icon: 'bi-credit-card',
      desc: t('support.paymentDesc'),
    },
    account: {
      name: t('support.account'),
      icon: 'bi-person-circle',
      desc: t('support.accountDesc'),
    },
    report: {
      name: t('support.report'),
      icon: 'bi-flag',
      desc: t('support.reportDesc'),
    },
    other: {
      name: t('support.other'),
      icon: 'bi-question-circle',
      desc: t('support.otherDesc'),
    },
  }
  return categories[categoryType.value] || categories.other
})

const categoryName = computed(() => categoryInfo.value.name)
const categoryIcon = computed(() => categoryInfo.value.icon)
const categoryDesc = computed(() => categoryInfo.value.desc)

// 子分类列表
const subCategories = computed(() => {
  const subCats: Record<string, Array<{ code: string; name: string }>> = {
    order: [
      { code: 'order_status', name: t('support.subOrderStatus') },
      { code: 'not_received', name: t('support.subNotReceived') },
      { code: 'wrong_item', name: t('support.subWrongItem') },
      { code: 'not_shipped', name: t('support.subNotShipped') },
      { code: 'order_other', name: t('support.subOrderOther') },
    ],
    refund: [
      { code: 'return_apply', name: t('support.subReturnApply') },
      { code: 'refund_status', name: t('support.subRefundStatus') },
      { code: 'return_shipping', name: t('support.subReturnShipping') },
      { code: 'refund_other', name: t('support.subRefundOther') },
    ],
    payment: [
      { code: 'pay_failed', name: t('support.subPayFailed') },
      { code: 'double_charge', name: t('support.subDoubleCharge') },
      { code: 'refund_delay', name: t('support.subRefundDelay') },
      { code: 'payment_other', name: t('support.subPaymentOther') },
    ],
    account: [
      { code: 'login_issue', name: t('support.subLoginIssue') },
      { code: 'password_reset', name: t('support.subPasswordReset') },
      { code: 'account_security', name: t('support.subAccountSecurity') },
      { code: 'account_other', name: t('support.subAccountOther') },
    ],
    report: [
      { code: 'report_seller', name: t('support.subReportSeller') },
      { code: 'report_buyer', name: t('support.subReportBuyer') },
      { code: 'report_goods', name: t('support.subReportGoods') },
      { code: 'report_other', name: t('support.subReportOther') },
    ],
    other: [
      { code: 'other_question', name: t('support.subOtherQuestion') },
      { code: 'suggestion', name: t('support.subSuggestion') },
      { code: 'cooperation', name: t('support.subCooperation') },
    ],
  }
  return subCats[categoryType.value] || subCats.other
})

// 相关常见问题
const relatedFaqs = computed(() => {
  const faqs: Record<string, Array<{ question: string; answer: string }>> = {
    order: [
      { question: t('support.faqOrder1Q'), answer: t('support.faqOrder1A') },
      { question: t('support.faqOrder2Q'), answer: t('support.faqOrder2A') },
    ],
    refund: [
      { question: t('support.faqRefund1Q'), answer: t('support.faqRefund1A') },
      { question: t('support.faqRefund2Q'), answer: t('support.faqRefund2A') },
    ],
    payment: [
      { question: t('support.faqPayment1Q'), answer: t('support.faqPayment1A') },
      { question: t('support.faqPayment2Q'), answer: t('support.faqPayment2A') },
    ],
    account: [
      { question: t('support.faqAccount1Q'), answer: t('support.faqAccount1A') },
      { question: t('support.faqAccount2Q'), answer: t('support.faqAccount2A') },
    ],
  }
  return faqs[categoryType.value] || []
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: categoryName.value })
})

// 页面加载
onLoad((options) => {
  if (options?.type) {
    categoryType.value = options.type
  }
})

// 切换 FAQ 展开状态
function toggleFaq(index: number) {
  expandedFaq.value = expandedFaq.value === index ? null : index
}

// 选择子分类
function selectSubCategory(subCat: { code: string; name: string }) {
  uni.navigateTo({
    url: `/pages/support/create?type=${categoryType.value}&subType=${subCat.code}`,
  })
}

// 直接创建工单
function goCreateTicket() {
  uni.navigateTo({ url: `/pages/support/create?type=${categoryType.value}` })
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
  width: 72px;
  height: 72px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;

  .bi {
    font-size: 32px;
    color: #FFFFFF;
  }

  &.order {
    background: linear-gradient(135deg, #4A90D9 0%, #357ABD 100%);
  }

  &.refund {
    background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
  }

  &.payment {
    background: linear-gradient(135deg, #F5A623 0%, #E09915 100%);
  }

  &.account {
    background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
  }

  &.report {
    background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
  }

  &.other {
    background: linear-gradient(135deg, #95A5A6 0%, #7F8C8D 100%);
  }
}

.category-desc {
  font-size: 14px;
  color: $color-text-secondary;
  text-align: center;
  line-height: 1.5;
  padding: 0 24px;
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

// 子分类项
.sub-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  cursor: pointer;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.sub-label {
  font-size: 15px;
  color: $color-text;
}

.sub-arrow {
  font-size: 14px;
  color: $color-text-muted;
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

// 创建工单按钮
.create-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background-color: $color-primary;
  color: #FFFFFF;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s;

  &:active {
    opacity: 0.8;
  }

  .bi {
    font-size: 18px;
  }
}

.create-text {
  font-size: 15px;
  font-weight: 500;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
