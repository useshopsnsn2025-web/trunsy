<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('legal.title')" />

    <!-- 内容区域 -->
    <scroll-view class="legal-content" scroll-y>
      <!-- 法规目录 -->
      <view class="legal-sections">
        <!-- 用户协议 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.userAgreement') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('user-agreement')">
              <view class="item-content">
                <text class="bi bi-file-text item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.userAgreementTitle') }}</text>
                  <text class="item-desc">{{ t('legal.userAgreementDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('terms-of-service')">
              <view class="item-content">
                <text class="bi bi-shield-check item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.termsOfService') }}</text>
                  <text class="item-desc">{{ t('legal.termsOfServiceDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 隐私与数据 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.privacyData') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('privacy-policy')">
              <view class="item-content">
                <text class="bi bi-shield-lock item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.privacyPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.privacyPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('cookie-policy')">
              <view class="item-content">
                <text class="bi bi-cookie item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.cookiePolicy') }}</text>
                  <text class="item-desc">{{ t('legal.cookiePolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('data-protection')">
              <view class="item-content">
                <text class="bi bi-database-lock item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.dataProtection') }}</text>
                  <text class="item-desc">{{ t('legal.dataProtectionDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 买家政策 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.buyerPolicies') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('buyer-protection')">
              <view class="item-content">
                <text class="bi bi-patch-check item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.buyerProtection') }}</text>
                  <text class="item-desc">{{ t('legal.buyerProtectionDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('refund-policy')">
              <view class="item-content">
                <text class="bi bi-arrow-return-left item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.refundPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.refundPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('purchase-conditions')">
              <view class="item-content">
                <text class="bi bi-cart-check item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.purchaseConditions') }}</text>
                  <text class="item-desc">{{ t('legal.purchaseConditionsDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 卖家政策 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.sellerPolicies') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('listing-policy')">
              <view class="item-content">
                <text class="bi bi-list-check item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.listingPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.listingPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('fee-policy')">
              <view class="item-content">
                <text class="bi bi-percent item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.feePolicy') }}</text>
                  <text class="item-desc">{{ t('legal.feePolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('seller-standards')">
              <view class="item-content">
                <text class="bi bi-star item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.sellerStandards') }}</text>
                  <text class="item-desc">{{ t('legal.sellerStandardsDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 禁止与限制 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.prohibitedRestricted') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('prohibited-items')">
              <view class="item-content">
                <text class="bi bi-x-octagon item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.prohibitedItems') }}</text>
                  <text class="item-desc">{{ t('legal.prohibitedItemsDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('restricted-items')">
              <view class="item-content">
                <text class="bi bi-exclamation-triangle item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.restrictedItems') }}</text>
                  <text class="item-desc">{{ t('legal.restrictedItemsDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('counterfeit-policy')">
              <view class="item-content">
                <text class="bi bi-shield-x item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.counterfeitPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.counterfeitPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 知识产权 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.intellectualProperty') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('ip-policy')">
              <view class="item-content">
                <text class="bi bi-c-circle item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.ipPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.ipPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('trademark-policy')">
              <view class="item-content">
                <text class="bi bi-r-circle item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.trademarkPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.trademarkPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('dmca-policy')">
              <view class="item-content">
                <text class="bi bi-file-earmark-lock item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.dmcaPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.dmcaPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 支付与交易 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.paymentTransaction') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('payment-terms')">
              <view class="item-content">
                <text class="bi bi-credit-card item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.paymentTerms') }}</text>
                  <text class="item-desc">{{ t('legal.paymentTermsDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('billing-policy')">
              <view class="item-content">
                <text class="bi bi-receipt item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.billingPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.billingPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('escrow-service')">
              <view class="item-content">
                <text class="bi bi-safe item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.escrowService') }}</text>
                  <text class="item-desc">{{ t('legal.escrowServiceDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 争议解决 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.disputeResolution') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('dispute-policy')">
              <view class="item-content">
                <text class="bi bi-people item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.disputePolicy') }}</text>
                  <text class="item-desc">{{ t('legal.disputePolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('arbitration-agreement')">
              <view class="item-content">
                <text class="bi bi-balance-scale item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.arbitrationAgreement') }}</text>
                  <text class="item-desc">{{ t('legal.arbitrationAgreementDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('liability-limitation')">
              <view class="item-content">
                <text class="bi bi-shield-exclamation item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.liabilityLimitation') }}</text>
                  <text class="item-desc">{{ t('legal.liabilityLimitationDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 社区准则 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.communityGuidelines') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('member-conduct')">
              <view class="item-content">
                <text class="bi bi-person-check item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.memberConduct') }}</text>
                  <text class="item-desc">{{ t('legal.memberConductDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('feedback-policy')">
              <view class="item-content">
                <text class="bi bi-chat-dots item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.feedbackPolicy') }}</text>
                  <text class="item-desc">{{ t('legal.feedbackPolicyDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
            <view class="legal-item" @click="goToDetail('anti-fraud')">
              <view class="item-content">
                <text class="bi bi-bug item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.antiFraud') }}</text>
                  <text class="item-desc">{{ t('legal.antiFraudDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>

        <!-- 更新日志 -->
        <view class="legal-section">
          <view class="section-header">
            <text class="section-title">{{ t('legal.changelog') }}</text>
          </view>
          <view class="section-items">
            <view class="legal-item" @click="goToDetail('policy-updates')">
              <view class="item-content">
                <text class="bi bi-clock-history item-icon"></text>
                <view class="item-text">
                  <text class="item-title">{{ t('legal.policyUpdates') }}</text>
                  <text class="item-desc">{{ t('legal.policyUpdatesDesc') }}</text>
                </view>
              </view>
              <text class="bi bi-chevron-right item-arrow"></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 联系信息 -->
      <view class="contact-section">
        <text class="contact-title">{{ t('legal.contactUs') }}</text>
        <text class="contact-desc">{{ t('legal.contactUsDesc') }}</text>
        <view class="contact-email" @click="copyEmail">
          <text class="bi bi-envelope"></text>
          <text class="email-text">turnsyshop@gmail.com</text>
          <text class="bi bi-copy copy-icon"></text>
        </view>
      </view>

      <!-- 版权声明 -->
      <view class="copyright-section">
        <text class="copyright-text">© {{ currentYear }} TURNSY. {{ t('legal.allRightsReserved') }}</text>
        <text class="update-text">{{ t('legal.lastUpdated') }}: 2024-01-15</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

const currentYear = computed(() => new Date().getFullYear())

// 跳转到详情页
function goToDetail(type: string) {
  uni.navigateTo({ url: `/pages/settings/legal-detail?type=${type}` })
}

// 复制邮箱
function copyEmail() {
  uni.setClipboardData({
    data: 'turnsyshop@gmail.com',
    showToast: false, // 禁用 uni 自带的提示
    success: () => {
      toast.success(t('common.copied'))
    }
  })
}
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #191919;
$color-secondary: #707070;
$color-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-accent: #FF6B35;

$font-size-xs: 11px;
$font-size-sm: 12px;
$font-size-base: 14px;
$font-size-md: 15px;
$font-size-lg: 17px;

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

$radius-sm: 4px;
$radius-md: 8px;
$radius-lg: 12px;

.page {
  min-height: 100vh;
  background-color: $color-background;
  color: $color-primary;
}

.legal-content {
  flex: 1;
  padding: $spacing-base;
  width: auto;
}

// 法规分组
.legal-sections {
  display: flex;
  flex-direction: column;
  gap: $spacing-lg;
}

.legal-section {
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
}

.section-header {
  padding: $spacing-base $spacing-base $spacing-sm;
  border-bottom: 1px solid $color-border;
}

.section-title {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $color-secondary;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-items {
  display: flex;
  flex-direction: column;
}

// 法规项目
.legal-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.item-content {
  display: flex;
  align-items: flex-start;
  gap: $spacing-md;
  flex: 1;
}

.item-icon {
  font-size: 20px;
  color: $color-accent;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.item-text {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
  flex: 1;
}

.item-title {
  font-size: $font-size-md;
  font-weight: 500;
  color: $color-primary;
  line-height: 1.4;
}

.item-desc {
  font-size: $font-size-sm;
  color: $color-muted;
  line-height: 1.4;
}

.item-arrow {
  font-size: $font-size-sm;
  color: $color-muted;
  margin-left: $spacing-sm;
}

// 联系信息
.contact-section {
  margin-top: $spacing-lg;
  padding: $spacing-lg;
  background-color: $color-surface;
  border-radius: $radius-lg;
  text-align: center;
}

.contact-title {
  display: block;
  font-size: $font-size-md;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.contact-desc {
  display: block;
  font-size: $font-size-sm;
  color: $color-muted;
  margin-bottom: $spacing-base;
  line-height: 1.5;
}

.contact-email {
  display: inline-flex;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;
  color: $color-accent;
  font-size: $font-size-base;

  .email-text {
    font-weight: 500;
  }

  .copy-icon {
    font-size: $font-size-sm;
    color: $color-muted;
  }

  &:active {
    opacity: 0.8;
  }
}

// 版权声明
.copyright-section {
  margin-top: $spacing-lg;
  padding: $spacing-lg;
  text-align: center;
}

.copyright-text {
  display: block;
  font-size: $font-size-sm;
  color: $color-muted;
  margin-bottom: $spacing-xs;
}

.update-text {
  display: block;
  font-size: $font-size-xs;
  color: $color-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}

// 动画简化
@media (prefers-reduced-motion: reduce) {
  .legal-item {
    transition: none;
  }
}
</style>
