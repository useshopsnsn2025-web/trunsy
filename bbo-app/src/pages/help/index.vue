<template>
  <view class="page">
    <NavBar :title="t('help.title')" />

    <scroll-view class="content" scroll-y>
      <!-- 帮助区块 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('help.howCanWeHelp') }}</text>
        </view>
        <text class="section-desc">{{ t('help.howCanWeHelpDesc') }}</text>
        <view class="action-btn" @click="goSupport">
          <text>{{ t('help.goToSupport') }}</text>
        </view>
      </view>

      <!-- 检举内容区块 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('help.reportContent') }}</text>
        </view>
        <text class="section-desc">{{ t('help.reportContentDesc') }}</text>
        <view class="action-btn" @click="goReport">
          <text>{{ t('help.submitReport') }}</text>
        </view>
      </view>

      <!-- 反馈区块 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('help.tellUsYourOpinion') }}</text>
        </view>

        <view class="link-list">
          <view class="link-item" @click="goFeedback">
            <text class="link-text">{{ t('help.submitFeedback') }}</text>
            <text class="bi bi-chevron-right link-arrow"></text>
          </view>
          <view class="link-item" @click="goTechIssue">
            <text class="link-text">{{ t('help.reportTechIssue') }}</text>
            <text class="bi bi-chevron-right link-arrow"></text>
          </view>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()

onShow(() => {
  uni.setNavigationBarTitle({ title: t('help.title') })
})

// 前往支援及联络
function goSupport() {
  uni.navigateTo({ url: '/pages/support/index' })
}

// 提出检举
function goReport() {
  uni.navigateTo({ url: '/pages/support/create?type=report' })
}

// 送出意见
function goFeedback() {
  uni.navigateTo({ url: '/pages/support/create?type=other&subType=suggestion' })
}

// 举报技术问题
function goTechIssue() {
  uni.navigateTo({ url: '/pages/support/create?type=other&subType=other_question' })
}
</script>

<style lang="scss" scoped>
$color-primary: #FF6B35;
$color-text: #191919;
$color-text-secondary: #707070;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;

.page {
  min-height: 100vh;
  background-color: $color-background;
  display: flex;
  flex-direction: column;
}

.content {
  flex: 1;
}

.section {
  margin: 16px;
  padding: 20px;
  background-color: $color-surface;
  border-radius: 12px;
}

.section-header {
  margin-bottom: 12px;
}

.section-title {
  font-size: 17px;
  font-weight: 600;
  color: $color-text;
}

.section-desc {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.6;
  display: block;
  margin-bottom: 16px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  background-color: $color-primary;
  border-radius: 22px;
  cursor: pointer;
  transition: opacity 0.2s;

  text {
    font-size: 15px;
    font-weight: 500;
    color: #FFFFFF;
  }

  &:active {
    opacity: 0.8;
  }
}

.link-list {
  margin-top: 4px;
}

.link-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    opacity: 0.7;
  }
}

.link-text {
  font-size: 15px;
  color: $color-text;
}

.link-arrow {
  font-size: 14px;
  color: #959595;
}

.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
