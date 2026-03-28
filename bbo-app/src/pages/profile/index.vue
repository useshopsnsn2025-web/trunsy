<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :class="{ 'nav-bar-shadow': isScrolled }" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-content">
        <text class="nav-title">{{ t('user.myProfile') }}</text>
        <view class="nav-actions">
          <view class="nav-btn" @click="goCart">
            <text class="bi bi-cart3"></text>
            <view v-if="cartCount > 0" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</view>
          </view>
          <view class="nav-btn" @click="goNotifications">
            <text class="bi bi-bell"></text>
            <view v-if="unreadCount > 0" class="cart-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</view>
          </view>
        </view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-placeholder" :style="{ height: (statusBarHeight + 92) + 'px' }"></view>

    <scroll-view class="content" scroll-y @scroll="handleScroll" :style="{ height: contentHeight + 'px' }">
      <!-- 用户卡片 -->
      <view class="user-card" hover-class="none">
        <view
          class="user-header"
          :class="{ 'is-pressing': isHeaderPressing }"
          @click="isLoggedIn ? goProfile() : goLogin()"
          @touchstart="isHeaderPressing = true"
          @touchend="isHeaderPressing = false"
          @touchcancel="isHeaderPressing = false"
        >
          <view class="user-main">
            <view v-if="userInfo?.avatar" class="avatar-wrap">
              <image class="avatar" :src="userInfo.avatar" mode="aspectFill" />
            </view>
            <view v-else class="avatar-wrap avatar-default">
              <text class="avatar-letter">{{ getAvatarLetter() }}</text>
            </view>
            <view class="user-info">
              <text class="username">{{ isLoggedIn ? (userInfo?.nickname || 'User') : t('user.signIn') }}</text>
              <text class="user-sub">{{ isLoggedIn ? t('user.registerTime') + ': ' + getRegisterYear() : t('user.signInHint') }}</text>
            </view>
          </view>
          <text class="bi bi-chevron-right user-arrow"></text>
        </view>
        <!-- 用户快捷入口 - 独立区域，不受 user-header 影响 -->
        <view class="user-shortcuts" @touchstart.stop @touchend.stop @touchcancel.stop>
          <view
            class="shortcut-item"
            :class="{ 'is-pressing': pressingShortcut === 'payment' }"
            @click.stop="goPayment"
            @touchstart="pressingShortcut = 'payment'"
            @touchend="pressingShortcut = null"
            @touchcancel="pressingShortcut = null"
          >
            <text class="bi bi-wallet2"></text>
            <text class="shortcut-label">{{ t('user.payment') }}</text>
          </view>
          <view
            class="shortcut-item"
            :class="{ 'is-pressing': pressingShortcut === 'notifications' }"
            @click.stop="goNotifications"
            @touchstart="pressingShortcut = 'notifications'"
            @touchend="pressingShortcut = null"
            @touchcancel="pressingShortcut = null"
          >
            <text class="bi bi-bell"></text>
            <text class="shortcut-label">{{ t('user.notifications') }}</text>
            <view v-if="unreadCount > 0" class="shortcut-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</view>
          </view>
          <view
            class="shortcut-item"
            :class="{ 'is-pressing': pressingShortcut === 'watchlist' }"
            @click.stop="goTracking"
            @touchstart="pressingShortcut = 'watchlist'"
            @touchend="pressingShortcut = null"
            @touchcancel="pressingShortcut = null"
          >
            <text class="bi bi-heart"></text>
            <text class="shortcut-label">{{ t('user.watchlist') }}</text>
          </view>
        </view>
      </view>

      <!-- 快捷入口 - 购买/出售 -->
      <view class="quick-actions">
        <view class="quick-section">
          <view class="section-header">
            <text class="section-title">{{ t('user.buying') }}</text>
            <view class="section-link" @click="goOrders('all')">
              <text>{{ t('user.viewAll') }}</text>
              <text class="bi bi-chevron-right"></text>
            </view>
          </view>
          <view class="action-grid">
            <view class="action-item" @click="goOrders('pending')">
              <view class="action-icon-wrap">
                <text class="bi bi-clock"></text>
              </view>
              <text class="action-label">{{ t('user.pendingPayment') }}</text>
            </view>
            <view class="action-item" @click="goOrders('shipping')">
              <view class="action-icon-wrap">
                <text class="bi bi-truck"></text>
              </view>
              <text class="action-label">{{ t('user.shipping') }}</text>
            </view>
            <view class="action-item" @click="goOrders('delivered')">
              <view class="action-icon-wrap">
                <text class="bi bi-box-seam"></text>
              </view>
              <text class="action-label">{{ t('user.delivered') }}</text>
            </view>
            <view class="action-item" @click="goOrders('review')">
              <view class="action-icon-wrap">
                <text class="bi bi-star"></text>
              </view>
              <text class="action-label">{{ t('user.toReview') }}</text>
            </view>
          </view>
        </view>

        <view class="quick-section">
          <view class="section-header">
            <text class="section-title">{{ t('user.selling') }}</text>
            <view class="section-link" @click="goMyGoods('all')">
              <text>{{ t('user.viewAll') }}</text>
              <text class="bi bi-chevron-right"></text>
            </view>
          </view>
          <view class="action-grid">
            <view class="action-item" @click="goMyGoods('active')">
              <view class="action-icon-wrap">
                <text class="bi bi-tags"></text>
              </view>
              <text class="action-label">{{ t('user.activeListing') }}</text>
            </view>
            <view class="action-item" @click="goMyGoods('sold')">
              <view class="action-icon-wrap">
                <text class="bi bi-check-circle"></text>
              </view>
              <text class="action-label">{{ t('user.soldItems') }}</text>
            </view>
            <view class="action-item" @click="goMyGoods('draft')">
              <view class="action-icon-wrap">
                <text class="bi bi-pencil-square"></text>
              </view>
              <text class="action-label">{{ t('user.drafts') }}</text>
            </view>
            <view class="action-item" @click="goPublish">
              <view class="action-icon-wrap publish">
                <text class="bi bi-plus-lg"></text>
              </view>
              <text class="action-label">{{ t('user.listItem') }}</text>
            </view>
          </view>
        </view>

        <view class="quick-section">
          <view class="section-header">
            <text class="section-title">{{ t('user.sellingOverview') }}</text>
            <view class="section-link" @click="goSellerOrders('all')">
              <text>{{ t('user.viewAll') }}</text>
              <text class="bi bi-chevron-right"></text>
            </view>
          </view>
          <view class="action-grid">
            <view class="action-item" @click="goSellerOrders('pending')">
              <view class="action-icon-wrap">
                <text class="bi bi-hourglass-split"></text>
              </view>
              <text class="action-label">{{ t('user.awaitingPayment') }}</text>
            </view>
            <view class="action-item" @click="goSellerOrders('toShip')">
              <view class="action-icon-wrap">
                <text class="bi bi-box-seam"></text>
              </view>
              <text class="action-label">{{ t('user.toShip') }}</text>
            </view>
            <view class="action-item" @click="goSellerOrders('shipped')">
              <view class="action-icon-wrap">
                <text class="bi bi-truck"></text>
              </view>
              <text class="action-label">{{ t('user.shipped') }}</text>
            </view>
            <view class="action-item" @click="goSellerOrders('completed')">
              <view class="action-icon-wrap">
                <text class="bi bi-check-circle"></text>
              </view>
              <text class="action-label">{{ t('user.completed') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 功能列表 -->
      <view class="menu-list">
        <!-- 游戏 & 积分 -->
        <view class="menu-group">
          <view class="menu-item" @click="goGameCenter">
            <view class="menu-icon-wrap game-icon">
              <text class="bi bi-gift"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('game.gameCenter') }}</text>
              <text class="menu-subtitle">{{ t('game.freeSpinAvailable') }}</text>
            </view>
            <view class="menu-right">
              <view v-if="totalChances > 0" class="chances-badge">{{ totalChances }}</view>
              <text class="bi bi-chevron-right menu-arrow"></text>
            </view>
          </view>

          <view class="menu-item" @click="goPoints">
            <view class="menu-icon-wrap points-icon">
              <text class="bi bi-coin"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('game.myPoints') }}</text>
              <text v-if="pointsBalance > 0" class="menu-subtitle">{{ pointsBalance.toLocaleString() }} {{ t('game.points') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
        </view>

        <!-- 优惠券 & 收藏 -->
        <view class="menu-group">
          <view class="menu-item" @click="goCoupons">
            <view class="menu-icon-wrap">
              <text class="bi bi-ticket-perforated"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.myCoupons') }}</text>
              <text v-if="couponCount > 0" class="menu-subtitle">{{ couponCount }} {{ t('user.available') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goFavorites">
            <view class="menu-icon-wrap">
              <text class="bi bi-heart"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.savedItems') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goHistory">
            <view class="menu-icon-wrap">
              <text class="bi bi-clock-history"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.recentlyViewed') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
        </view>

        <!-- 账户服务 -->
        <view class="menu-group">
          <view class="menu-item" @click="goWallet">
            <view class="menu-icon-wrap">
              <text class="bi bi-wallet2"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.myWallet') }}</text>
              <text class="menu-subtitle">{{ t('user.walletDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goCredit">
            <view class="menu-icon-wrap">
              <text class="bi bi-credit-card-2-front"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.creditCenter') }}</text>
              <text class="menu-subtitle">{{ t('user.creditDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goAddresses">
            <view class="menu-icon-wrap">
              <text class="bi bi-geo-alt"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.addresses') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

         
        </view>

        <!-- 帮助与设置 -->
        <view class="menu-group">
          <view class="menu-item" @click="goRegion">
            <view class="menu-icon-wrap">
              <text class="bi bi-geo"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.selectRegion') }}</text>
              <text class="menu-subtitle">{{ currentRegion }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goGuide">
            <view class="menu-icon-wrap">
              <text class="bi bi-book"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.userGuide') }}</text>
              <text class="menu-subtitle">{{ t('user.userGuideDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goSupport">
            <view class="menu-icon-wrap">
              <text class="bi bi-headset"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.helpCenter') }}</text>
              <text class="menu-subtitle">{{ t('user.helpCenterDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>

          <view class="menu-item" @click="goAbout">
            <view class="menu-icon-wrap">
              <text class="bi bi-info-circle"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.about') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
          <view class="menu-item" @click="goSettings">
            <view class="menu-icon-wrap">
              <text class="bi bi-gear"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('user.settings') }}</text>
              <text class="menu-subtitle">{{ t('user.settingsDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 国家/地区选择器 -->
    <RegionPicker
      :visible="showRegionPicker"
      :current-region="selectedRegion"
      @update:visible="showRegionPicker = $event"
      @select="handleRegionSelect"
    />

    <!-- 自定义底部导航栏 -->
    <CustomTabBar :current="4" />

  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/modules/app'
import RegionPicker from '@/components/RegionPicker.vue'
import { useUserStore } from '@/store/modules/user'
import { useI18n } from 'vue-i18n'
import { put, navigateToLogin } from '@/utils/request'
import { getCouponCount } from '@/api/coupon'
import { getUnreadCount } from '@/api/notification'
import { getUserGameChances, getUserPoints } from '@/api/game'
import CustomTabBar from '@/components/CustomTabBar.vue'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()
const toast = useToast()

// 优惠券数量
const couponCount = ref(0)
// 购物车数量
const cartCount = ref(0)
// 未读通知数量
const unreadCount = ref(0)
// 游戏次数
const totalChances = ref(0)
// 积分余额
const pointsBalance = ref(0)
// 滚动状态
const isScrolled = ref(false)
// 点击状态（用于手动控制点击效果，避免 :active 伪类冒泡）
const isHeaderPressing = ref(false)
const pressingShortcut = ref<string | null>(null)

// 响应式的国家/地区代码
const selectedRegion = ref(uni.getStorageSync('region') || 'US')
// 国家选择器弹窗
const showRegionPicker = ref(false)

const statusBarHeight = computed(() => appStore.statusBarHeight)
const windowHeight = computed(() => appStore.windowHeight)

// 动态计算内容区域高度（窗口高度 - 导航栏高度）
// 导航栏高度 = statusBarHeight + 92px（nav-content 52px + padding 40px）
const contentHeight = computed(() => {
  const navBarHeight = statusBarHeight.value + 92
  return windowHeight.value - navBarHeight
})
const isLoggedIn = computed(() => userStore.isLoggedIn)
const userInfo = computed(() => userStore.userInfo)

// 获取当前选择的国家名称（从 API 数据）
const currentRegion = computed(() => {
  const region = selectedRegion.value
  const country = appStore.availableCountries.find(c => c.code === region)
  return country?.name || region
})

// 处理滚动事件
function handleScroll(e: any) {
  const scrollTop = e.detail?.scrollTop || 0
  isScrolled.value = scrollTop > 0
}

// 加载优惠券数量
async function loadCouponCount() {
  if (!isLoggedIn.value) return
  try {
    const res = await getCouponCount()
    couponCount.value = res.data?.count || 0
  } catch (e) {
    console.error('Failed to load coupon count:', e)
  }
}

// 加载未读通知数量
async function loadUnreadCount() {
  if (!isLoggedIn.value) return
  try {
    const res = await getUnreadCount()
    unreadCount.value = res.data?.count || 0
  } catch (e) {
    console.error('Failed to load unread count:', e)
  }
}

// 获取头像字母
function getAvatarLetter(): string {
  if (!isLoggedIn.value) return 'G'
  const nickname = (userInfo.value?.nickname || '').trim()
  return nickname.charAt(0).toUpperCase() || 'U'
}

// 获取注册年份
function getRegisterYear(): string {
  return new Date().getFullYear().toString()
}

function goLogin() {
  navigateToLogin()
}

function goProfile() {
  uni.navigateTo({ url: '/pages/user/profile/index' })
}

function goCart() {
  uni.navigateTo({ url: '/pages/cart/index' })
}

function goPublish() {
  uni.switchTab({ url: '/pages/sell/index' })
}

function goMyGoods(type: string) {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  // 映射类型：all, active, sold, draft
  uni.navigateTo({ url: `/pages/user/listings/index?tab=${type}` })
}

function goOrders(type: string) {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  // 映射个人中心的类型到订单列表的状态
  const statusMap: Record<string, string> = {
    all: 'all',
    pending: 'pending',      // 待付款
    shipping: 'shipping',    // 配送中
    delivered: 'completed',  // 已送达 -> 已完成
    review: 'completed',     // 待评价 -> 已完成（筛选未评价的）
  }
  const status = statusMap[type] || 'all'
  uni.navigateTo({ url: `/pages/order/list?status=${status}` })
}

function goSellerOrders(type: string) {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  // 跳转到卖家订单页面
  uni.navigateTo({ url: `/pages/seller/orders/index?status=${type}` })
}

function goFavorites() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/favorites/index' })
}

function goMessages() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  toast.info(t('common.comingSoon'))
}

function goNotifications() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/notification/index' })
}

function goTracking() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/favorites/index' })
}

function goHistory() {
  uni.navigateTo({ url: '/pages/history/index' })
}

function goCoupons() {
  uni.navigateTo({ url: '/pages/coupon/index' })
}

function goPayment() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/payment/index' })
}

function goWallet() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/wallet/index' })
}

function goCredit() {
  uni.navigateTo({ url: '/pages/credit/index' })
}

function goAddresses() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/address/index' })
}

function goGuide() {
  uni.navigateTo({ url: '/pages/guide/index' })
}

function goSettings() {
  uni.navigateTo({ url: '/pages/settings/index' })
}

function goRegion() {
  showRegionPicker.value = true
}

function handleRegionSelect(region: { code: string; locale: string }) {
  selectedRegion.value = region.code
  appStore.setRegion(region.code)
  // 切换到该国家的默认语言
  if (region.locale) {
    // locale 格式转换：后端返回 en-us，前端需要 en-US
    const frontendLocale = region.locale.replace(/-(\w+)$/, (_, p1) => `-${p1.toUpperCase()}`)
    appStore.setLocale(frontendLocale)
  }
  // 同步国家到后端
  if (isLoggedIn.value) {
    put('/user/profile', { country: region.code }).catch(() => {})
  }
}

function goSupport() {
  uni.navigateTo({ url: '/pages/support/index' })
}

function goAbout() {
  uni.navigateTo({ url: '/pages/about/index' })
}

function goGameCenter() {
  uni.navigateTo({ url: '/pages/game/index' })
}

function goPoints() {
  if (!isLoggedIn.value) {
    goLogin()
    return
  }
  uni.navigateTo({ url: '/pages/game/points' })
}

// 加载游戏次数
async function loadGameChances() {
  if (!isLoggedIn.value) return
  try {
    const res = await getUserGameChances()
    if (res.code === 0 && res.data) {
      // 计算所有游戏的总次数
      totalChances.value = res.data.reduce((sum: number, item: any) => sum + (item.chances || 0), 0)
    }
  } catch (e) {
    console.error('Failed to load game chances:', e)
  }
}

// 加载积分余额
async function loadPointsBalance() {
  if (!isLoggedIn.value) return
  try {
    const res = await getUserPoints()
    if (res.code === 0 && res.data) {
      pointsBalance.value = res.data.balance || 0
    }
  } catch (e) {
    console.error('Failed to load points balance:', e)
  }
}

onMounted(() => {
  appStore.initSystemInfo()
})

onShow(() => {
  // #ifdef APP-PLUS
  uni.hideTabBar({ animation: false })
  // #endif
  if (isLoggedIn.value) {
    userStore.fetchUserInfo()
    loadCouponCount()
    loadUnreadCount()
    loadGameChances()
    loadPointsBalance()
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

// 导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: $color-surface;
  z-index: 100;
  transition: box-shadow 0.2s ease;

  &.nav-bar-shadow {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }
}

.nav-content {
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px $spacing-base;
}

.nav-title {
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-text-primary;
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.nav-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $radius-full;
  position: relative;
  transition: background-color 0.2s;

  &:active {
    background-color: $color-background;
  }

  .bi {
    font-size: 22px;
    color: $color-text-primary;
  }
}

.cart-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  background-color: $color-danger;
  color: #fff;
  font-size: 11px;
  font-weight: $font-weight-bold;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-placeholder {
  flex-shrink: 0;
}

// 内容区域 .content
// 高度通过 :style 动态计算（windowHeight - statusBarHeight - 92px）

// 用户卡片
.user-card {
  background-color: $color-surface;
  margin-bottom: $spacing-sm;
  -webkit-tap-highlight-color: transparent;
  // 覆盖 App.vue 全局 [class*="-card"]:active 的 transform 效果
  &:active {
    transform: none !important;
  }
}

.user-header {
  display: flex;
  align-items: center;
  padding:  0  $spacing-lg $spacing-base;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: background-color 0.15s ease;

  &.is-pressing {
    background-color: #FAFAFA; // 比 $color-surface (#FFFFFF) 暗 2%
  }
}

.user-main {
  flex: 1;
  display: flex;
  align-items: center;
  gap: $spacing-md;
}

.avatar-wrap {
  width: 48px;
  height: 48px;
  border-radius: $radius-full;
  overflow: hidden;
  flex-shrink: 0;

  &.avatar-default {
    background-color: #FF6B35;
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
  font-size: 22px;
  font-weight: $font-weight-bold;
  color: #fff;
  line-height: 48px;
  text-align: center;
}

.user-info {
  flex: 1;
}

.username {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  display: block;
  margin-bottom: 2px;
}

.user-sub {
  font-size: $font-size-sm;
  color: $color-text-muted;
}

.user-arrow {
  font-size: 18px;
  color: $color-text-muted;
}

// 用户快捷入口
.user-shortcuts {
  display: flex;
  padding: $spacing-sm $spacing-base $spacing-lg;
  gap: $spacing-sm;
  background-color: $color-surface;
  -webkit-tap-highlight-color: transparent;
}

.shortcut-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-base $spacing-sm;
  background-color: $color-background;
  border-radius: $radius-md;
  cursor: pointer;
  transition: background-color 0.15s ease, transform 0.15s ease;
  -webkit-tap-highlight-color: transparent;
  // 使用 touch-action 优化触摸体验
  touch-action: manipulation;

  // 使用 JS 控制的 class 而非 :active，避免伪类冒泡
  &.is-pressing {
    background-color: darken($color-background, 5%);
    transform: scale(0.96);
  }

  .bi {
    font-size: 22px;
    color: $color-text-primary;
  }
}

.shortcut-label {
  font-size: $font-size-sm;
  color: $color-text-primary;
}

.shortcut-badge {
  position: absolute;
  top: $spacing-xs;
  right: $spacing-xs;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  background-color: $color-danger;
  color: #fff;
  font-size: 11px;
  font-weight: $font-weight-bold;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

.shortcut-item {
  position: relative;
}

// 快捷入口区域
.quick-actions {
  background-color: $color-surface;
  margin-bottom: $spacing-sm;
}

.quick-section {
  padding: $spacing-base;

  &:not(:last-child) {
    border-bottom: 1px solid $color-border;
  }
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: $spacing-md;
}

.section-title {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
}

.section-link {
  display: flex;
  align-items: center;
  gap: 2px;
  cursor: pointer;

  text {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  .bi {
    font-size: 14px;
    color: $color-text-muted;
  }
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-sm;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $spacing-sm;
  padding: $spacing-sm;
  cursor: pointer;
  border-radius: $radius-sm;
  transition: background-color 0.2s;

  &:active {
    background-color: $color-background;
  }
}

.action-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: $radius-md;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-background;

  .bi {
    font-size: 20px;
    color: $color-text-primary;
  }

  &.publish {
    background-color: $color-primary;
    .bi { color: #fff; }
  }
}

.action-label {
  font-size: $font-size-xs;
  color: $color-text-secondary;
  text-align: center;
  line-height: 1.3;
}

// 菜单列表
.menu-list {
  padding: 0 0 $spacing-base;
}

.menu-group {
  background-color: $color-surface;
  margin-bottom: $spacing-sm;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: $spacing-base;
  gap: $spacing-md;
  cursor: pointer;
  transition: background-color 0.2s;

  &:not(:last-child) {
    border-bottom: 1px solid $color-border;
    margin: 0 $spacing-base;
    padding-left: 0;
    padding-right: 0;
  }

  &:active {
    background-color: $color-background;
  }

  &.logout {
    justify-content: center;
    padding: $spacing-base;
  }
}

.menu-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: $radius-sm;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background-color: $color-background;

  .bi {
    font-size: 18px;
    color: $color-text-primary;
  }
}

.menu-content {
  flex: 1;
  min-width: 0;
}

.menu-title {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
  display: block;
}

.menu-subtitle {
  font-size: $font-size-sm;
  color: $color-text-muted;
  margin-top: 2px;
  display: block;
}

.menu-arrow {
  font-size: 16px;
  color: $color-text-muted;
  flex-shrink: 0;
}

.menu-right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.chances-badge {
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F5A 100%);
  color: #fff;
  font-size: 11px;
  font-weight: $font-weight-bold;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-icon-wrap {
  &.game-icon {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    .bi { color: #fff; }
  }

  &.points-icon {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    .bi { color: #fff; }
  }
}

.logout-text {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-danger;
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 80px);
}

// 响应式
@media (prefers-reduced-motion: reduce) {
  .nav-btn,
  .user-card,
  .action-item,
  .menu-item {
    transition: none;
  }
}
</style>
