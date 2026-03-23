<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('settings.title')" />

    <!-- 设置列表 -->
    <scroll-view class="settings-content" scroll-y>
      <!-- 帳戶 Account -->
      <view class="settings-section">
        <view class="section-header">
          <text class="section-title">{{ t('settings.account') }}</text>
        </view>
        <view class="settings-group">
          <view class="settings-item" @click="goLoginSecurity">
            <text class="item-title">{{ t('settings.loginSecurity') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goPushNotifications">
            <text class="item-title">{{ t('settings.pushNotifications') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goEmailSettings">
            <text class="item-title">{{ t('settings.email') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goAnalytics">
            <text class="item-title">{{ t('settings.metricsAnalytics') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item logout-item" @click="handleLogout">
            <text class="item-title logout-text">{{ t('settings.logout') }}</text>
          </view>
        </view>
      </view>

      <!-- 一般 General -->
      <view class="settings-section">
        <view class="section-header">
          <text class="section-title">{{ t('settings.general') }}</text>
        </view>
        <view class="settings-group">
          <view class="settings-item" @click="goTheme">
            <view class="item-content">
              <text class="item-title">{{ t('settings.theme') }}</text>
              <text class="item-subtitle">{{ t('settings.themeDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goTranslation">
            <text class="item-title">{{ t('settings.translation') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="clearSearchHistory">
            <text class="item-title">{{ t('settings.clearSearchHistory') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="handleClearCache">
            <view class="item-content">
              <text class="item-title">{{ t('settings.clearCache') }}</text>
              <text class="item-subtitle">{{ cacheSize }}</text>
            </view>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 支援 Support -->
      <view class="settings-section">
        <view class="section-header">
          <text class="section-title">{{ t('settings.support') }}</text>
        </view>
        <view class="settings-group">
          <view class="settings-item" @click="goCustomerService">
            <view class="item-content">
              <text class="item-title">{{ t('settings.customerService') }}</text>
              <text class="item-subtitle">{{ t('settings.customerServiceDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goShakeToReport">
            <text class="item-title">{{ t('settings.shakeToReport') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 關於 About -->
      <view class="settings-section">
        <view class="section-header">
          <text class="section-title">{{ t('settings.about') }}</text>
        </view>
        <view class="settings-group">
          <view class="settings-item" @click="goMemberAgreement">
            <text class="item-title">{{ t('settings.memberAgreement') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goPrivacy">
            <text class="item-title">{{ t('settings.privacy') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goPrivacyChoices">
            <text class="item-title">{{ t('settings.privacyChoices') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goAdPreferences">
            <text class="item-title">{{ t('settings.adPreferences') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goAccessibility">
            <text class="item-title">{{ t('settings.accessibility') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item" @click="goLegal">
            <text class="item-title">{{ t('settings.legal') }}</text>
            <text class="bi bi-chevron-right item-arrow"></text>
          </view>
          <view class="settings-item">
            <view class="item-content">
              <text class="item-title">{{ t('settings.version') }}</text>
              <text class="item-subtitle">{{ appVersion }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 退出登录确认弹窗 -->
    <ConfirmDialog
      :visible="showLogoutDialog"
      :title="t('user.logout')"
      :content="t('user.logoutConfirm')"
      icon="bi-box-arrow-right"
      icon-type="warning"
      @update:visible="showLogoutDialog = $event"
      @confirm="confirmLogout"
    />

    <!-- 清除搜索记录确认弹窗 -->
    <ConfirmDialog
      :visible="showClearDialog"
      :title="t('settings.clearSearchHistory')"
      :content="t('settings.clearSearchConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showClearDialog = $event"
      @confirm="confirmClearSearch"
    />

    <!-- 清理缓存确认弹窗 -->
    <ConfirmDialog
      :visible="showClearCacheDialog"
      :title="t('settings.clearCache')"
      :content="t('settings.clearCacheConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showClearCacheDialog = $event"
      @confirm="confirmClearCache"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/store/modules/user'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const userStore = useUserStore()
const toast = useToast()

// 状态
const showLogoutDialog = ref(false)
const showClearDialog = ref(false)
const showClearCacheDialog = ref(false)
const cacheSize = ref('0 KB')

// 计算属性
const appVersion = computed(() => '1.0.0')

// 帳戶相关
function goLoginSecurity() {
  uni.navigateTo({ url: '/pages/settings/login-security' })
}

function goPushNotifications() {
  uni.navigateTo({ url: '/pages/settings/push-notifications' })
}

function goEmailSettings() {
  uni.navigateTo({ url: '/pages/settings/email-settings' })
}

function goAnalytics() {
  uni.navigateTo({ url: '/pages/settings/analytics' })
}

function handleLogout() {
  showLogoutDialog.value = true
}

function confirmLogout() {
  userStore.logout()
  toast.success(t('user.logoutSuccess'))
  uni.reLaunch({ url: '/pages/index/index' })
}

// 一般相关
function goTheme() {
  uni.navigateTo({ url: '/pages/settings/theme' })
}


function goTranslation() {
  uni.navigateTo({ url: '/pages/settings/translation' })
}

function clearSearchHistory() {
  showClearDialog.value = true
}

function confirmClearSearch() {
  // 清除本地存储的搜索历史
  uni.removeStorageSync('searchHistory')
  toast.success(t('settings.searchHistoryCleared'))
}

function handleClearCache() {
  showClearCacheDialog.value = true
}

function confirmClearCache() {
  // 清除所有本地缓存（保留用户登录信息）
  const token = uni.getStorageSync('token')
  const userInfo = uni.getStorageSync('userInfo')

  uni.clearStorageSync()

  // 恢复登录信息
  if (token) uni.setStorageSync('token', token)
  if (userInfo) uni.setStorageSync('userInfo', userInfo)

  // 重新计算缓存大小
  calculateCacheSize()
  toast.success(t('settings.cacheCleared'))
}

// 计算缓存大小
function calculateCacheSize() {
  try {
    const res = uni.getStorageInfoSync()
    const sizeKB = res.currentSize || 0
    if (sizeKB >= 1024) {
      cacheSize.value = (sizeKB / 1024).toFixed(2) + ' MB'
    } else {
      cacheSize.value = sizeKB + ' KB'
    }
  } catch {
    cacheSize.value = '0 KB'
  }
}

// 支援相关
function goCustomerService() {
  uni.navigateTo({ url: '/pages/support/index' })
}

function goShakeToReport() {
  toast.info(t('common.comingSoon'))
}

// 關於相关
function goMemberAgreement() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=user-agreement' })
}

function goPrivacy() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=privacy-policy' })
}

function goPrivacyChoices() {
  uni.navigateTo({ url: '/pages/settings/privacy-choices' })
}

function goAdPreferences() {
  uni.navigateTo({ url: '/pages/settings/ad-preferences' })
}

function goAccessibility() {
  uni.navigateTo({ url: '/pages/settings/accessibility' })
}

function goLegal() {
  uni.navigateTo({ url: '/pages/settings/legal' })
}

// 初始化
onMounted(() => {
  calculateCacheSize()
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统 - eBay 风格，简洁高级感
// ==========================================

// 色彩系统
$color-primary: #191919;
$color-secondary: #707070;
$color-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-accent: #FF6B35;
$color-danger: #E53935;

// 字体
$font-size-xs: 11px;
$font-size-sm: 12px;
$font-size-base: 14px;
$font-size-md: 15px;
$font-size-lg: 17px;
$font-size-xl: 20px;

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// 圆角
$radius-sm: 4px;
$radius-md: 8px;
$radius-lg: 12px;
$radius-full: 9999px;

// ==========================================
// 基础样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  color: $color-primary;
}

// 设置内容区域
.settings-content {
 flex: 1;
  width: auto;
  padding: $spacing-base;
}

// 设置分组
.settings-section {
  margin-bottom: $spacing-lg;
}

.section-header {
  padding: $spacing-sm 0 $spacing-md;
}

.section-title {
  font-size: $font-size-sm;
  font-weight: 600;
  color: $color-secondary;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

// 设置组
.settings-group {
  background-color: $color-surface;
  border-radius: $radius-lg;
  overflow: hidden;
}

// 设置项
.settings-item {
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
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.item-title {
  font-size: $font-size-md;
  font-weight: 400;
  color: $color-primary;
  line-height: 1.4;
}

.item-subtitle {
  font-size: $font-size-sm;
  color: $color-muted;
  line-height: 1.3;
}

.item-arrow {
  font-size: $font-size-sm;
  color: $color-muted;
  margin-left: $spacing-sm;
}

// 退出登录项
.logout-item {
  .logout-text {
    color: $color-danger;
  }
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}

// 动画简化
@media (prefers-reduced-motion: reduce) {
  .settings-item {
    transition: none;
  }
}
</style>
