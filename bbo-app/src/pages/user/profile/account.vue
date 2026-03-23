<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-content">
        <view class="nav-left" @click="goBack">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="nav-title">{{ t('user.changeAccount') }}</text>
        <view class="nav-right"></view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-placeholder" :style="{ height: (statusBarHeight + 56) + 'px' }"></view>

    <scroll-view class="content" scroll-y>
      <!-- 账户信息区域 -->
      <view class="section">
        <text class="section-title">{{ t('user.accountInfo') }}</text>

        <!-- 邮箱 -->
        <view class="info-item" @click="changeEmail">
          <view class="item-left">
            <text class="bi bi-envelope item-icon"></text>
            <view class="item-content">
              <text class="item-label">{{ t('user.email') }}</text>
              <text class="item-value">{{ maskEmail(userInfo?.email) }}</text>
            </view>
          </view>
          <text class="bi bi-chevron-right item-arrow"></text>
        </view>

        <!-- 手机号 -->
        <view class="info-item" @click="changePhone">
          <view class="item-left">
            <text class="bi bi-phone item-icon"></text>
            <view class="item-content">
              <text class="item-label">{{ t('user.phone') }}</text>
              <text class="item-value">{{ maskPhone(userInfo?.phone) || t('user.notSet') }}</text>
            </view>
          </view>
          <text class="bi bi-chevron-right item-arrow"></text>
        </view>
      </view>

      <!-- 安全设置区域 -->
      <view class="section">
        <text class="section-title">{{ t('user.securitySettings') }}</text>

        <!-- 修改密码 -->
        <view class="info-item" @click="changePassword">
          <view class="item-left">
            <text class="bi bi-lock item-icon"></text>
            <view class="item-content">
              <text class="item-label">{{ t('user.changePassword') }}</text>
              <text class="item-desc">{{ t('user.changePasswordDesc') }}</text>
            </view>
          </view>
          <text class="bi bi-chevron-right item-arrow"></text>
        </view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()

// 状态栏高度
const statusBarHeight = computed(() => appStore.statusBarHeight)

// 用户信息
const userInfo = computed(() => userStore.userInfo)

// 返回上一页
function goBack() {
  uni.navigateBack()
}

// 邮箱脱敏显示
function maskEmail(email?: string): string {
  if (!email) return t('user.notSet')
  const [name, domain] = email.split('@')
  if (!domain) return email
  const maskedName = name.length > 3
    ? name.substring(0, 3) + '***'
    : name.substring(0, 1) + '***'
  return `${maskedName}@${domain}`
}

// 手机号脱敏显示
function maskPhone(phone?: string): string {
  if (!phone) return ''
  if (phone.length > 7) {
    return phone.substring(0, 3) + '****' + phone.substring(phone.length - 4)
  }
  return phone
}

// 修改邮箱
function changeEmail() {
  uni.navigateTo({ url: '/pages/user/profile/change-email' })
}

// 修改手机号
function changePhone() {
  uni.navigateTo({ url: '/pages/user/profile/change-phone' })
}

// 修改密码
function changePassword() {
  uni.navigateTo({ url: '/pages/settings/login-security' })
}

onMounted(() => {
  appStore.initSystemInfo()
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('user.changeAccount') })
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - eBay 风格
// ==========================================

// 色彩系统
$color-primary: #FF6B35;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;

// 字体系统
$font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

// 字号
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;

// 圆角
$radius-md: 12px;

// 间距
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

// 导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: $color-surface;
  z-index: 100;
  border-bottom: 1px solid $color-border;
}

.nav-content {
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 $spacing-base;
}

.nav-left,
.nav-right {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;

  .bi {
    font-size: 20px;
    color: $color-text-primary;
  }

  &:active {
    opacity: 0.7;
  }
}

.nav-title {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
}

.nav-placeholder {
  flex-shrink: 0;
}

.content {
  flex: 1;
}

// 区块
.section {
  background-color: $color-surface;
  margin-bottom: $spacing-md;
  padding: $spacing-lg $spacing-base;
}

.section-title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  display: block;
  margin-bottom: $spacing-base;
}

// 信息项
.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base 0;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    opacity: 0.7;
  }
}

.item-left {
  display: flex;
  align-items: center;
  flex: 1;
}

.item-icon {
  font-size: 20px;
  color: $color-text-secondary;
  margin-right: $spacing-md;
}

.item-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.item-label {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
}

.item-value {
  font-size: $font-size-sm;
  color: $color-text-secondary;
}

.item-desc {
  font-size: $font-size-sm;
  color: $color-text-muted;
}

.item-arrow {
  font-size: 16px;
  color: $color-text-muted;
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}
</style>
