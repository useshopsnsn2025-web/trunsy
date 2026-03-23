<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('user.profile.title')" />

    <scroll-view class="content" scroll-y>
      <!-- 用户头像和信息 -->
      <view class="profile-header">
        <view class="avatar-section">
          <view v-if="userInfo?.avatar" class="avatar-wrap">
            <image class="avatar" :src="userInfo.avatar" mode="aspectFill" />
          </view>
          <view v-else class="avatar-wrap avatar-default">
            <text class="avatar-letter">{{ getAvatarLetter() }}</text>
          </view>
        </view>
        <text class="username">{{ userInfo?.nickname || 'User' }}</text>
        <view class="edit-btn" @click="goEditProfile">
          <text>{{ t('user.editProfile') }}</text>
        </view>
      </view>

      <!-- 标签页 -->
      <view class="tabs">
        <view
          class="tab-item"
          :class="{ 'active': activeTab === 'about' }"
          @click="activeTab = 'about'"
        >
          <text>{{ t('user.aboutTab') }}</text>
        </view>
        <view
          class="tab-item"
          :class="{ 'active': activeTab === 'rating' }"
          @click="activeTab = 'rating'"
        >
          <text>{{ t('user.creditRating') }}</text>
        </view>
      </view>

      <!-- 关于内容 -->
      <view v-if="activeTab === 'about'" class="tab-content">
        <!-- 所在地 -->
        <view class="info-item">
          <text class="bi bi-geo-alt info-icon"></text>
          <text class="info-text">{{ t('user.location') }}{{ currentRegion }}</text>
        </view>

        <!-- 注册日期 -->
        <view class="info-item">
          <text class="bi bi-calendar3 info-icon"></text>
          <text class="info-text">{{ t('user.memberSince') }}{{ formatDate(userInfo?.createdAt) }}</text>
        </view>

        <!-- 个人简介 -->
        <view v-if="userInfo?.bio" class="bio-section">
          <text class="bio-label">{{ t('user.bio') }}</text>
          <text class="bio-text">{{ userInfo.bio }}</text>
        </view>

        <!-- 慈善捐款卡片 -->
        <view class="charity-card">
          <view class="charity-icon-wrap">
            <text class="bi bi-heart-fill charity-icon"></text>
          </view>
          <view class="charity-content">
            <text class="charity-title">{{ t('user.charityDonation') }}</text>
            <text class="charity-desc">{{ t('user.charityDonationDesc') }}</text>
          </view>
          <view class="charity-btn" @click="goCharitySetup">
            <text>{{ t('user.setUp') }}</text>
          </view>
        </view>
      </view>

      <!-- 信用评价内容 -->
      <view v-if="activeTab === 'rating'" class="tab-content">
        <!-- 评分统计 -->
        <view class="rating-summary">
          <view class="rating-score">
            <text class="score-value">{{ ratingStats.score || '--' }}</text>
            <text class="score-label">{{ t('user.overallRating') }}</text>
          </view>
          <view class="rating-details">
            <view class="rating-row">
              <text class="rating-label">{{ t('user.positive') }}</text>
              <view class="rating-bar">
                <view class="rating-bar-fill positive" :style="{ width: ratingStats.positivePercent + '%' }"></view>
              </view>
              <text class="rating-percent">{{ ratingStats.positivePercent || 0 }}%</text>
            </view>
            <view class="rating-row">
              <text class="rating-label">{{ t('user.neutral') }}</text>
              <view class="rating-bar">
                <view class="rating-bar-fill neutral" :style="{ width: ratingStats.neutralPercent + '%' }"></view>
              </view>
              <text class="rating-percent">{{ ratingStats.neutralPercent || 0 }}%</text>
            </view>
            <view class="rating-row">
              <text class="rating-label">{{ t('user.negative') }}</text>
              <view class="rating-bar">
                <view class="rating-bar-fill negative" :style="{ width: ratingStats.negativePercent + '%' }"></view>
              </view>
              <text class="rating-percent">{{ ratingStats.negativePercent || 0 }}%</text>
            </view>
          </view>
        </view>

        <!-- 评价列表 -->
        <view class="reviews-section">
          <view class="section-title">{{ t('user.recentReviews') }}</view>
          <view v-if="reviews.length === 0" class="empty-reviews">
            <text class="bi bi-chat-dots empty-icon"></text>
            <text class="empty-text">{{ t('user.noReviewsYet') }}</text>
          </view>
          <view v-else class="reviews-list">
            <view v-for="review in reviews" :key="review.id" class="review-item">
              <view class="review-header">
                <view class="reviewer-avatar">
                  <text>{{ review.reviewerName?.charAt(0) || 'U' }}</text>
                </view>
                <view class="review-meta">
                  <text class="reviewer-name">{{ review.reviewerName }}</text>
                  <text class="review-date">{{ formatDate(review.createdAt) }}</text>
                </view>
                <view class="review-rating">
                  <text class="bi" :class="getRatingIcon(review.rating)"></text>
                </view>
              </view>
              <text class="review-content">{{ review.content }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

// 当前选中的标签
const activeTab = ref<'about' | 'rating'>('about')

// 用户信息
const userInfo = computed(() => userStore.userInfo)

// 当前地区名称
const currentRegion = computed(() => {
  const regionCode = uni.getStorageSync('region') || 'US'
  const country = appStore.availableCountries.find(c => c.code === regionCode)
  return country?.name || regionCode
})

// 评分统计（模拟数据，后续可以从 API 获取）
const ratingStats = ref({
  score: 0,
  positivePercent: 0,
  neutralPercent: 0,
  negativePercent: 0,
  totalReviews: 0,
})

// 评价列表（模拟数据）
const reviews = ref<Array<{
  id: number
  reviewerName: string
  rating: number
  content: string
  createdAt: string
}>>([])

// 获取头像字母
function getAvatarLetter(): string {
  const nickname = (userInfo.value?.nickname || '').trim()
  return nickname.charAt(0).toUpperCase() || 'U'
}

// 格式化日期
function formatDate(dateStr?: string): string {
  if (!dateStr) return '--'
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  })
}

// 获取评分图标
function getRatingIcon(rating: number): string {
  if (rating >= 4) return 'bi-hand-thumbs-up-fill'
  if (rating === 3) return 'bi-dash-circle'
  return 'bi-hand-thumbs-down-fill'
}

// 跳转编辑资料
function goEditProfile() {
  uni.navigateTo({ url: '/pages/user/profile/edit' })
}

// 跳转慈善设置
function goCharitySetup() {
  uni.navigateTo({ url: '/pages/user/charity/index' })
}

// 加载评分数据
async function loadRatingStats() {
  // TODO: 从 API 获取评分统计
  // 目前使用模拟数据
  ratingStats.value = {
    score: 0,
    positivePercent: 0,
    neutralPercent: 0,
    negativePercent: 0,
    totalReviews: 0,
  }
}

// 加载评价列表
async function loadReviews() {
  // TODO: 从 API 获取评价列表
  // 目前使用空数据
  reviews.value = []
}

onMounted(() => {
  loadRatingStats()
  loadReviews()
})

onShow(() => {
  if (userStore.isLoggedIn) {
    userStore.fetchUserInfo()
  }
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - eBay 风格
// ==========================================

// 色彩系统
$color-primary: #FF6B35;        // eBay 蓝
$color-primary-light: #EBF0FF;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #228B22;
$color-warning: #F5A623;
$color-danger: #FF6B35;

// 字体系统
$font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

// 字号
$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 20px;
$font-size-xxl: 28px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 圆角
$radius-sm: 8px;
$radius-md: 12px;
$radius-lg: 16px;
$radius-full: 9999px;

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// ==========================================
// 页面样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  font-family: $font-family;
}

.content {
  height: calc(100vh - var(--nav-height, 88px));
}

// 用户头像区域
.profile-header {
  background-color: $color-surface;
  padding: $spacing-xl $spacing-base;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: $spacing-sm;
}

.avatar-section {
  margin-bottom: $spacing-md;
}

.avatar-wrap {
  width: 80px;
  height: 80px;
  border-radius: $radius-full;
  overflow: hidden;

  &.avatar-default {
    background-color: $color-primary;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.avatar {
  width: 100%;
  height: 100%;
}

.avatar-letter {
  font-size: 36px;
  font-weight: $font-weight-bold;
  color: #fff;
  line-height: 80px;
  text-align: center;
}

.username {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  margin-bottom: $spacing-md;
}

.edit-btn {
  padding: $spacing-sm $spacing-lg;
  border: 1px solid $color-text-primary;
  border-radius: $radius-full;
  cursor: pointer;
  transition: background-color 0.2s;

  text {
    font-size: $font-size-sm;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
  }

  &:active {
    background-color: $color-background;
  }
}

// 标签页
.tabs {
  display: flex;
  background-color: $color-surface;
  border-bottom: 1px solid $color-border;
}

.tab-item {
  flex: 1;
  padding: $spacing-base;
  text-align: center;
  cursor: pointer;
  position: relative;
  transition: color 0.2s;

  text {
    font-size: $font-size-base;
    font-weight: $font-weight-medium;
    color: $color-text-secondary;
  }

  &.active {
    text {
      color: $color-text-primary;
      font-weight: $font-weight-semibold;
    }

    &::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: $spacing-base;
      right: $spacing-base;
      height: 2px;
      background-color: $color-text-primary;
    }
  }
}

// 标签内容
.tab-content {
  background-color: $color-surface;
  padding: $spacing-base;
}

// 信息项
.info-item {
  display: flex;
  align-items: center;
  padding: $spacing-md 0;
  border-bottom: 1px solid $color-border;

  &:last-of-type {
    border-bottom: none;
  }
}

.info-icon {
  font-size: 18px;
  color: $color-text-secondary;
  margin-right: $spacing-md;
  width: 24px;
  text-align: center;
}

.info-text {
  font-size: $font-size-base;
  color: $color-text-primary;
}

// 个人简介
.bio-section {
  padding: $spacing-base 0;
  border-top: 1px solid $color-border;
  margin-top: $spacing-md;
}

.bio-label {
  font-size: $font-size-sm;
  color: $color-text-muted;
  display: block;
  margin-bottom: $spacing-sm;
}

.bio-text {
  font-size: $font-size-base;
  color: $color-text-primary;
  line-height: 1.5;
}

// 慈善捐款卡片
.charity-card {
  display: flex;
  align-items: center;
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;
  margin-top: $spacing-lg;
}

.charity-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: $radius-full;
  background-color: #FFF0F0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: $spacing-md;
}

.charity-icon {
  font-size: 20px;
  color: $color-danger;
}

.charity-content {
  flex: 1;
}

.charity-title {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  display: block;
  margin-bottom: $spacing-xs;
}

.charity-desc {
  font-size: $font-size-sm;
  color: $color-text-muted;
}

.charity-btn {
  padding: $spacing-sm $spacing-md;
  background-color: $color-primary;
  border-radius: $radius-full;
  cursor: pointer;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: $font-size-sm;
    font-weight: $font-weight-medium;
    color: #fff;
  }

  &:active {
    opacity: 0.8;
  }
}

// 评分统计
.rating-summary {
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;
  margin-bottom: $spacing-lg;
}

.rating-score {
  text-align: center;
  margin-bottom: $spacing-lg;
}

.score-value {
  font-size: $font-size-xxl;
  font-weight: $font-weight-bold;
  color: $color-text-primary;
  display: block;
}

.score-label {
  font-size: $font-size-sm;
  color: $color-text-muted;
}

.rating-details {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.rating-label {
  width: 50px;
  font-size: $font-size-sm;
  color: $color-text-secondary;
}

.rating-bar {
  flex: 1;
  height: 8px;
  background-color: $color-border;
  border-radius: $radius-full;
  overflow: hidden;
}

.rating-bar-fill {
  height: 100%;
  border-radius: $radius-full;
  transition: width 0.3s ease;

  &.positive {
    background-color: $color-success;
  }

  &.neutral {
    background-color: $color-warning;
  }

  &.negative {
    background-color: $color-danger;
  }
}

.rating-percent {
  width: 40px;
  font-size: $font-size-sm;
  color: $color-text-secondary;
  text-align: right;
}

// 评价列表
.reviews-section {
  margin-top: $spacing-base;
}

.section-title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  margin-bottom: $spacing-md;
}

.empty-reviews {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-xl;
}

.empty-icon {
  font-size: 48px;
  color: $color-text-muted;
  margin-bottom: $spacing-md;
}

.empty-text {
  font-size: $font-size-base;
  color: $color-text-muted;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.review-item {
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;
}

.review-header {
  display: flex;
  align-items: center;
  margin-bottom: $spacing-sm;
}

.reviewer-avatar {
  width: 32px;
  height: 32px;
  border-radius: $radius-full;
  background-color: $color-primary;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: $spacing-sm;

  text {
    font-size: $font-size-sm;
    font-weight: $font-weight-bold;
    color: #fff;
  }
}

.review-meta {
  flex: 1;
}

.reviewer-name {
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
  display: block;
}

.review-date {
  font-size: $font-size-xs;
  color: $color-text-muted;
}

.review-rating {
  .bi {
    font-size: 18px;

    &.bi-hand-thumbs-up-fill {
      color: $color-success;
    }

    &.bi-dash-circle {
      color: $color-warning;
    }

    &.bi-hand-thumbs-down-fill {
      color: $color-danger;
    }
  }
}

.review-content {
  font-size: $font-size-base;
  color: $color-text-primary;
  line-height: 1.5;
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// 响应式
@media (prefers-reduced-motion: reduce) {
  .tab-item,
  .edit-btn,
  .charity-btn {
    transition: none;
  }
}
</style>
