<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('credit.title')" />

    <LoadingPage v-if="pageLoading" />

    <template v-else>
      <!-- 状态A：已有信用额度 -->
      <template v-if="creditInfo?.has_credit">
        <!-- 额度卡片 -->
        <view class="credit-hero">
          <view class="hero-bg"></view>
          <view class="hero-content">
            <text class="hero-label">{{ t('credit.availableCredit') }}</text>
            <view class="hero-amount">
              <text class="amount-value">{{ formatPrice(creditInfo.available_limit || 0) }}</text>
            </view>

            <!-- 额度进度条 -->
            <view class="credit-progress">
              <view class="progress-bar">
                <view
                  class="progress-fill"
                  :style="{ width: `${usedPercentage}%` }"
                ></view>
              </view>
              <view class="progress-labels">
                <text class="progress-used">{{ t('credit.used') }}: {{ formatPrice(creditInfo.used_limit || 0) }}</text>
                <text class="progress-total">{{ t('credit.total') }}: {{ formatPrice(creditInfo.total_limit || 0) }}</text>
              </view>
            </view>

            <!-- 有效期 -->
            <view class="credit-meta">
              <text class="bi bi-calendar3"></text>
              <text class="meta-text">{{ t('credit.validUntil').replace('[DATE]', formatDate(creditInfo.expires_at)) }}</text>
            </view>
          </view>
        </view>

        <!-- 快捷操作 -->
        <view class="quick-actions">
          <view class="action-card" @click="goOrders">
            <text class="bi bi-receipt action-icon"></text>
            <text class="action-label">{{ t('credit.myOrders') }}</text>
          </view>
          <view class="action-card" @click="showPlansPopup = true">
            <text class="bi bi-list-check action-icon"></text>
            <text class="action-label">{{ t('credit.paymentPlans') }}</text>
          </view>
          <view class="action-card" @click="goIncreaseLimit">
            <text class="bi bi-graph-up-arrow action-icon"></text>
            <text class="action-label">{{ t('credit.increaseLimit') }}</text>
          </view>
        </view>
      </template>

      <!-- 状态B：申请审核中 -->
      <template v-else-if="creditInfo?.pending_application">
        <view class="status-card pending">
          <view class="status-icon-wrap">
            <text class="bi bi-hourglass-split status-icon"></text>
          </view>
          <text class="status-title">{{ t('credit.applicationInReview') }}</text>
          <text class="status-desc">{{ t('credit.reviewTimeDesc') }}</text>

          <view class="status-timeline">
            <view class="timeline-item completed">
              <view class="timeline-dot"></view>
              <text class="timeline-text">{{ t('credit.submitted') }}</text>
            </view>
            <view class="timeline-line"></view>
            <view class="timeline-item active">
              <view class="timeline-dot"></view>
              <text class="timeline-text">{{ t('credit.underReview') }}</text>
            </view>
            <view class="timeline-line"></view>
            <view class="timeline-item">
              <view class="timeline-dot"></view>
              <text class="timeline-text">{{ t('credit.decision') }}</text>
            </view>
          </view>

          <!-- 补充资料提示 -->
          <view v-if="creditInfo.pending_application.supplement_request" class="supplement-alert" @click="goSupplement">
            <text class="bi bi-exclamation-circle"></text>
            <view class="alert-content">
              <text class="alert-title">{{ t('credit.actionRequired') }}</text>
              <text class="alert-desc">{{ creditInfo.pending_application.supplement_request }}</text>
            </view>
            <text class="bi bi-chevron-right alert-arrow"></text>
          </view>

          <view class="status-meta">
            <text class="bi bi-clock"></text>
            <text>{{ t('credit.appliedOn').replace('[DATE]', formatDate(creditInfo.pending_application.created_at)) }}</text>
          </view>
        </view>
      </template>

      <!-- 状态C：未申请 -->
      <template v-else>
        <view class="promo-section">
          <!-- 主标题区 -->
          <view class="promo-hero">
            <text class="promo-tagline">{{ t('credit.promoTagline') }}</text>
            <text class="promo-title">{{ t('credit.promoTitle') }}</text>
            <text class="promo-subtitle">{{ t('credit.promoSubtitle') }}</text>
          </view>

          <!-- 优势列表 -->
          <view class="benefits-list">
            <view class="benefit-item">
              <text class="bi bi-check-circle-fill benefit-icon"></text>
              <text class="benefit-text">{{ t('credit.benefit1') }}</text>
            </view>
            <view class="benefit-item">
              <text class="bi bi-check-circle-fill benefit-icon"></text>
              <text class="benefit-text">{{ t('credit.benefit2') }}</text>
            </view>
            <view class="benefit-item">
              <text class="bi bi-check-circle-fill benefit-icon"></text>
              <text class="benefit-text">{{ t('credit.benefit3') }}</text>
            </view>
          </view>

          <!-- 申请按钮 -->
          <view class="cta-section">
            <view class="cta-btn" @click="goApply">
              <text>{{ t('credit.getStarted') }}</text>
              <text class="bi bi-arrow-right"></text>
            </view>
            <text class="cta-note">{{ t('credit.applyNote') }}</text>
          </view>
        </view>

        <!-- 流程说明 -->
        <view class="how-it-works">
          <text class="section-title">{{ t('credit.howItWorks') }}</text>
          <view class="steps-container">
            <view class="step-item">
              <view class="step-number">1</view>
              <view class="step-info">
                <text class="step-title">{{ t('credit.step1Title') }}</text>
                <text class="step-desc">{{ t('credit.step1Desc') }}</text>
              </view>
            </view>
            <view class="step-item">
              <view class="step-number">2</view>
              <view class="step-info">
                <text class="step-title">{{ t('credit.step2Title') }}</text>
                <text class="step-desc">{{ t('credit.step2Desc') }}</text>
              </view>
            </view>
            <view class="step-item">
              <view class="step-number">3</view>
              <view class="step-info">
                <text class="step-title">{{ t('credit.step3Title') }}</text>
                <text class="step-desc">{{ t('credit.step3Desc') }}</text>
              </view>
            </view>
          </view>
        </view>

        <!-- 常见问题入口 -->
        <view class="faq-link" @click="goFAQ">
          <text class="bi bi-question-circle"></text>
          <text class="faq-text">{{ t('credit.haveQuestions') }}</text>
          <text class="bi bi-chevron-right faq-arrow"></text>
        </view>
      </template>

      <!-- 分期方案弹窗 -->
      <view v-if="showPlansPopup" class="popup-mask" @click="showPlansPopup = false">
        <view class="popup-container" @click.stop>
          <view class="popup-header">
            <text class="popup-title">{{ t('credit.paymentPlans') }}</text>
            <view class="popup-close" @click="showPlansPopup = false">
              <text class="bi bi-x-lg"></text>
            </view>
          </view>
          <scroll-view scroll-y class="popup-body">
            <view v-for="plan in plans" :key="plan.id" class="plan-card">
              <view class="plan-top">
                <text class="plan-periods">{{ plan.periods }} {{ t('credit.months') }}</text>
                <view v-if="plan.is_interest_free" class="plan-badge">
                  <text>{{ t('credit.interestFree') }}</text>
                </view>
              </view>
              <text v-if="plan.description" class="plan-description">{{ plan.description }}</text>
              <view class="plan-bottom">
                <text v-if="!plan.is_interest_free" class="plan-rate">{{ (plan.interest_rate * 100).toFixed(1) }}% {{ t('credit.apr') }}</text>
                <text v-else class="plan-rate zero">0% {{ t('credit.apr') }}</text>
              </view>
            </view>
          </scroll-view>
        </view>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { getCreditLimit, getInstallmentPlans, type CreditLimit, type InstallmentPlan } from '@/api/credit'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import { navigateToLogin } from '@/utils/request'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

const pageLoading = ref(true)
const creditInfo = ref<CreditLimit | null>(null)
const plans = ref<InstallmentPlan[]>([])
const showPlansPopup = ref(false)

// 计算已使用百分比
const usedPercentage = computed(() => {
  if (!creditInfo.value?.total_limit) return 0
  return Math.round((creditInfo.value.used_limit / creditInfo.value.total_limit) * 100)
})

// 格式化价格
function formatPrice(amount: number): string {
  return appStore.formatPrice(amount, 'USD')
}

// 格式化日期
function formatDate(dateStr: string | undefined): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// 加载信用信息
async function loadCreditInfo() {
  if (!userStore.isLoggedIn) {
    pageLoading.value = false
    return
  }

  try {
    const res = await getCreditLimit()
    creditInfo.value = res.data
  } catch (e) {
    console.error('Failed to load credit info:', e)
  } finally {
    pageLoading.value = false
  }
}

// 加载分期方案
async function loadPlans() {
  try {
    const res = await getInstallmentPlans(1000)
    plans.value = res.data || []
  } catch (e) {
    console.error('Failed to load plans:', e)
  }
}

// 导航函数
function goApply() {
  if (!userStore.isLoggedIn) {
    navigateToLogin()
    return
  }
  uni.navigateTo({ url: '/pages/credit/apply' })
}

function goOrders() {
  if (!userStore.isLoggedIn) {
    navigateToLogin()
    return
  }
  uni.navigateTo({ url: '/pages/credit/orders' })
}

function goIncreaseLimit() {
  toast.info(t('common.comingSoon'))
}

function goFAQ() {
  toast.info(t('common.comingSoon'))
}

function goSupplement() {
  uni.navigateTo({ url: '/pages/credit/supplement' })
}

onMounted(() => {
  loadPlans()
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.credit') })
  loadCreditInfo()
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-success: #059669;
$color-warning: #D97706;
$color-border: #E7E5E4;
$color-bg: #FAFAF9;

.page {
  min-height: 100vh;
  background-color: $color-bg;
}

// ==================
// 已有额度状态
// ==================
.credit-hero {
  position: relative;
  margin: 16px;
  border-radius: 20px;
  overflow: hidden;
  background: linear-gradient(135deg, #1C1917 0%, #44403C 100%);
}

.hero-bg {
  position: absolute;
  top: -60px;
  right: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: rgba(255, 107, 53, 0.15);
}

.hero-content {
  position: relative;
  padding: 28px 24px;
  color: #fff;
}

.hero-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  display: block;
  margin-bottom: 8px;
}

.hero-amount {
  margin-bottom: 24px;
}

.amount-value {
  font-size: 36px;
  font-weight: 700;
  letter-spacing: -1px;
}

.credit-progress {
  margin-bottom: 20px;
}

.progress-bar {
  height: 6px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 10px;
}

.progress-fill {
  height: 100%;
  background: $color-accent;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
}

.progress-used, .progress-total {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.credit-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);

  .bi {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.5);
  }
}

.meta-text {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

// 快捷操作
.quick-actions {
  display: flex;
  gap: 12px;
  padding: 0 16px;
  margin-bottom: 24px;
}

.action-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 12px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.action-icon {
  font-size: 22px;
  color: $color-accent;
}

.action-label {
  font-size: 12px;
  color: $color-secondary;
  text-align: center;
}

// ==================
// 审核中状态
// ==================
.status-card {
  margin: 16px;
  padding: 32px 24px;
  background: #fff;
  border-radius: 20px;
  text-align: center;

  &.pending {
    .status-icon-wrap {
      background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    }
    .status-icon {
      color: $color-warning;
    }
  }
}

.status-icon-wrap {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.status-icon {
  font-size: 32px;
}

.status-title {
  font-size: 20px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 8px;
}

.status-desc {
  font-size: 14px;
  color: $color-muted;
  display: block;
  margin-bottom: 28px;
}

// 时间线
.status-timeline {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-bottom: 24px;
}

.timeline-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;

  &.completed .timeline-dot {
    background: $color-success;
    border-color: $color-success;
  }

  &.active .timeline-dot {
    background: $color-warning;
    border-color: $color-warning;
    animation: pulse 2s infinite;
  }
}

.timeline-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #E5E5E5;
  border: 2px solid #E5E5E5;
}

.timeline-line {
  width: 40px;
  height: 2px;
  background: #E5E5E5;
  margin-bottom: 20px;
}

.timeline-text {
  font-size: 11px;
  color: $color-muted;
  white-space: nowrap;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

// 补充资料提示
.supplement-alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #FEF2F2;
  border-radius: 12px;
  margin-bottom: 20px;
  text-align: left;

  > .bi:first-child {
    font-size: 20px;
    color: #DC2626;
    flex-shrink: 0;
  }
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-size: 14px;
  font-weight: 600;
  color: #DC2626;
  display: block;
  margin-bottom: 2px;
}

.alert-desc {
  font-size: 12px;
  color: #7F1D1D;
}

.alert-arrow {
  font-size: 16px;
  color: #DC2626;
}

.status-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 13px;
  color: $color-muted;

  .bi {
    font-size: 14px;
  }
}

// ==================
// 未申请状态
// ==================
.promo-section {
  margin: 16px;
  padding: 32px 24px;
  background: #fff;
  border-radius: 20px;
}

.promo-hero {
  text-align: center;
  margin-bottom: 28px;
}

.promo-tagline {
  font-size: 12px;
  font-weight: 600;
  color: $color-accent;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: block;
  margin-bottom: 12px;
}

.promo-title {
  font-size: 28px;
  font-weight: 700;
  color: $color-primary;
  display: block;
  line-height: 1.2;
  margin-bottom: 12px;
}

.promo-subtitle {
  font-size: 15px;
  color: $color-muted;
  line-height: 1.5;
}

// 优势列表
.benefits-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 28px;
}

.benefit-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.benefit-icon {
  font-size: 18px;
  color: $color-success;
}

.benefit-text {
  font-size: 15px;
  color: $color-secondary;
}

// CTA区域
.cta-section {
  text-align: center;
}

.cta-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 16px 24px;
  background: $color-accent;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  margin-bottom: 12px;
  box-sizing: border-box;

  .bi {
    font-size: 18px;
  }
}

.cta-note {
  font-size: 12px;
  color: $color-muted;
}

// 流程说明
.how-it-works {
  margin: 0 16px 16px;
  padding: 24px;
  background: #fff;
  border-radius: 20px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 20px;
}

.steps-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.step-item {
  display: flex;
  gap: 16px;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: $color-bg;
  color: $color-accent;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.step-info {
  flex: 1;
  padding-top: 4px;
}

.step-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 4px;
}

.step-desc {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

// FAQ链接
.faq-link {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 16px 32px;
  padding: 16px 20px;
  background: #fff;
  border-radius: 12px;

  > .bi:first-child {
    font-size: 20px;
    color: $color-accent;
  }
}

.faq-text {
  flex: 1;
  font-size: 14px;
  color: $color-secondary;
}

.faq-arrow {
  font-size: 16px;
  color: $color-muted;
}

// ==================
// 分期方案弹窗
// ==================
.popup-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.popup-container {
  width: 100%;
  max-height: 70vh;
  background: #fff;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
}

.popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid $color-border;
}

.popup-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
}

.popup-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $color-bg;
  border-radius: 50%;

  .bi {
    font-size: 16px;
    color: $color-muted;
  }
}

.popup-body {
  width: auto;
  max-height: calc(70vh - 80px);
  padding: 20px 24px;
}

.plan-card {
  padding: 20px;
  background: $color-bg;
  border-radius: 16px;
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.plan-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.plan-description {
  display: block;
  font-size: 12px;
  color: $color-muted;
  line-height: 1.4;
  margin-bottom: 8px;
}

.plan-periods {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
}

.plan-badge {
  display: flex;
  align-items: center;
  padding: 4px 10px;
  background: $color-success;
  border-radius: 20px;

  text {
    font-size: 11px;
    font-weight: 600;
    color: #fff;
  }
}

.plan-bottom {
  display: flex;
  align-items: center;
}

.plan-rate {
  font-size: 14px;
  color: $color-muted;

  &.zero {
    color: $color-success;
    font-weight: 500;
  }
}
</style>
