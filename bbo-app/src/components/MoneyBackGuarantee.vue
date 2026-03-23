<template>
  <view class="guarantee-section">
    <!-- 保障卡片 (点击打开弹窗) -->
    <view class="guarantee-card" @click="showGuaranteePopup = true">
      <view class="guarantee-icon">
        <text class="bi bi-shield-check"></text>
      </view>
      <view class="guarantee-content">
        <text class="guarantee-title">{{ t('goods.guarantee.title') }}</text>
        <text class="guarantee-desc">{{ t('goods.guarantee.shortDesc') }}</text>
      </view>
      <text class="bi bi-chevron-right guarantee-arrow"></text>
    </view>

    <!-- 退款保障说明弹窗 -->
    <view v-if="showGuaranteePopup" class="popup-overlay" @click="showGuaranteePopup = false">
      <view class="popup-content popup-content-large" @click.stop>
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <scroll-view class="popup-body" scroll-y>
          <!-- 标题 -->
          <view class="guarantee-popup-header">
            <view class="guarantee-popup-icon">
              <text class="bi bi-shield-check"></text>
            </view>
            <text class="guarantee-popup-title">{{ t('goods.guarantee.popupTitle') }}</text>
          </view>

          <!-- 保障范围 -->
          <view class="guarantee-block">
            <text class="guarantee-block-title">{{ t('goods.guarantee.coverage') }}</text>
            <text class="guarantee-block-content">{{ t('goods.guarantee.coverageDesc') }}</text>
          </view>

          <!-- 申请流程 -->
          <view class="guarantee-block">
            <text class="guarantee-block-title">{{ t('goods.guarantee.process') }}</text>
            <view class="guarantee-steps">
              <view class="guarantee-step">
                <view class="step-number">1</view>
                <text class="step-text">{{ t('goods.guarantee.step1') }}</text>
              </view>
              <view class="guarantee-step">
                <view class="step-number">2</view>
                <text class="step-text">{{ t('goods.guarantee.step2') }}</text>
              </view>
              <view class="guarantee-step">
                <view class="step-number">3</view>
                <text class="step-text">{{ t('goods.guarantee.step3') }}</text>
              </view>
            </view>
          </view>

          <!-- 退款时效 -->
          <view class="guarantee-block">
            <text class="guarantee-block-title">{{ t('goods.guarantee.timeline') }}</text>
            <text class="guarantee-block-content">{{ t('goods.guarantee.timelineDesc') }}</text>
          </view>

          <!-- 联系客服 -->
          <view class="guarantee-contact" @click="goToCustomerService">
            <text class="bi bi-headset contact-icon"></text>
            <text class="contact-text">{{ t('goods.guarantee.contactSupport') }}</text>
            <text class="bi bi-chevron-right contact-arrow"></text>
          </view>
        </scroll-view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const showGuaranteePopup = ref(false)

function goToCustomerService() {
  showGuaranteePopup.value = false
  uni.navigateTo({ url: '/pages/support/index' })
}
</script>

<style lang="scss" scoped>
// 设计系统变量 - 珊瑚橙主题
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;

$font-weight-medium: 500;
$font-weight-semibold: 600;

$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;
$spacing-sm: 8px;

.guarantee-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

// 保障卡片（可点击）
.guarantee-card {
  display: flex;
  align-items: center;
  padding: $spacing-base;
  background-color: #FFF4F0;
  border-radius: 12px;
  cursor: pointer;

  &:active {
    opacity: 0.8;
  }
}

.guarantee-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: $color-accent;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-right: 12px;
  flex-shrink: 0;
}

.guarantee-content {
  flex: 1;
}

.guarantee-title {
  display: block;
  font-size: 15px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: 4px;
}

.guarantee-desc {
  display: block;
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

.guarantee-arrow {
  color: $color-muted;
  font-size: 16px;
}

// 弹窗样式（复用详情页的弹窗样式）
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.popup-content {
  width: 100%;
  max-height: 80vh;
  background-color: $color-surface;
  border-radius: 20px 20px 0 0;
  overflow: hidden;

  &.popup-content-large {
    max-height: 85vh;
  }
}

.popup-header {
  padding: 12px 0 8px;
  display: flex;
  justify-content: center;
}

.popup-handle {
  width: 40px;
  height: 4px;
  background-color: $color-border;
  border-radius: 2px;
}

.popup-body {
  width: auto;
  padding: 0 $spacing-base $spacing-xl;
  max-height: calc(85vh - 40px);
}

// 退款保障弹窗内容
.guarantee-popup-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 24px;
}

.guarantee-popup-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background-color: #FFF4F0;
  color: $color-accent;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin-bottom: 12px;
}

.guarantee-popup-title {
  font-size: 18px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.guarantee-block {
  margin-bottom: 20px;
}

.guarantee-block-title {
  display: block;
  font-size: 15px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: 8px;
}

.guarantee-block-content {
  display: block;
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.6;
}

// 申请流程步骤
.guarantee-steps {
  margin-top: 12px;
}

.guarantee-step {
  display: flex;
  align-items: flex-start;
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.step-number {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: $color-accent;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: $font-weight-semibold;
  margin-right: 12px;
  flex-shrink: 0;
}

.step-text {
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.5;
  padding-top: 2px;
}

// 联系客服
.guarantee-contact {
  display: flex;
  align-items: center;
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: 10px;
  margin-top: 20px;
}

.contact-icon {
  font-size: 20px;
  color: $color-accent;
  margin-right: 12px;
}

.contact-text {
  flex: 1;
  font-size: 14px;
  font-weight: $font-weight-medium;
  color: $color-primary;
}

.contact-arrow {
  color: $color-muted;
  font-size: 14px;
}
</style>
