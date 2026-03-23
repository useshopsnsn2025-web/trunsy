<template>
  <view
    class="page"
    @touchmove.stop.prevent="onTouchMove"
  >
    <view class="content-wrapper">
      <view class="header">
        <image class="logo" src="/static/logo.png" mode="aspectFit" />
        <text class="title">{{ t('auth.welcomeBack') }}</text>
        <text class="subtitle">{{ t('auth.loginSubtitle') }}</text>
      </view>

      <view class="form">
        <view class="input-group">
          <text class="input-label">{{ t('auth.email') }}</text>
          <input
            class="input"
            type="text"
            v-model="form.email"
            :placeholder="t('auth.emailPlaceholder')"
            :adjust-position="false"
          />
        </view>

        <view class="input-group">
          <text class="input-label">{{ t('auth.password') }}</text>
          <input
            class="input"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            :placeholder="t('auth.passwordPlaceholder')"
            :adjust-position="false"
          />
          <view class="toggle-password" @click="showPassword = !showPassword">
            <text :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
          </view>
        </view>

        <view class="forgot-password" @click="goForgotPassword">
          <text>{{ t('auth.forgotPassword') }}</text>
        </view>

        <button class="login-btn" :loading="loading" @click="handleLogin">
          {{ t('auth.login') }}
        </button>

        <view class="divider">
          <text class="divider-line"></text>
          <text class="divider-text">{{ t('auth.or') }}</text>
          <text class="divider-line"></text>
        </view>

        <view class="social-login" v-if="oauthConfig.google.enabled || oauthConfig.apple.enabled">
          <button
            v-if="oauthConfig.google.enabled"
            class="social-btn google"
            @click="socialLogin('google')"
          >
            <text class="bi bi-google social-icon"></text>
            <text>Google</text>
          </button>
          <button
            v-if="oauthConfig.apple.enabled"
            class="social-btn apple"
            @click="socialLogin('apple')"
          >
            <text class="bi bi-apple social-icon"></text>
            <text>Apple</text>
          </button>
        </view>

        <view class="register-link">
          <text>{{ t('auth.noAccount') }}</text>
          <text class="link" @click="goRegister">{{ t('auth.register') }}</text>
        </view>
      </view>
    </view>

    <!-- 自定义 Toast -->
    <view v-if="toastVisible" class="toast-container" :class="[`toast-${toastPosition}`]">
      <view class="toast-content" :class="[`toast-${toastType}`]">
        <view class="toast-icon">
          <text :class="toastIconClass"></text>
        </view>
        <text class="toast-message">{{ toastMessage }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onShow, onLoad } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useI18n } from 'vue-i18n'
import { initGoogleAuth } from '@/utils/googleAuth'
import { fetchOAuthConfig, type OAuthConfig } from '@/config/oauth'

const { t } = useI18n()

// ==========================================
// 自定义 Toast 功能（登录页没有 NavBar，需要自己实现）
// ==========================================
const toastVisible = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error' | 'warning' | 'info'>('success')
const toastPosition = ref<'top' | 'center' | 'bottom'>('center')
let toastTimer: ReturnType<typeof setTimeout> | null = null

const toastIconClass = computed(() => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    error: 'bi bi-x-circle-fill',
    warning: 'bi bi-exclamation-circle-fill',
    info: 'bi bi-info-circle-fill',
  }
  return icons[toastType.value]
})

function showToastFn(options: {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
  position?: 'top' | 'center' | 'bottom'
}) {
  if (toastTimer) {
    clearTimeout(toastTimer)
    toastTimer = null
  }

  toastMessage.value = options.message
  toastType.value = options.type || 'success'
  toastPosition.value = options.position || 'center'
  toastVisible.value = true

  const duration = options.duration ?? 2000
  if (duration > 0) {
    toastTimer = setTimeout(() => {
      toastVisible.value = false
    }, duration)
  }
}

// Toast 快捷方法
const toast = {
  success: (message: string) => showToastFn({ message, type: 'success' }),
  error: (message: string) => showToastFn({ message, type: 'error' }),
  warning: (message: string) => showToastFn({ message, type: 'warning' }),
  info: (message: string) => showToastFn({ message, type: 'info' }),
}

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.login') })
})

// 检查 URL 参数中的错误信息（OAuth 回调失败时）
onLoad((options) => {
  if (options?.error) {
    toast.error(decodeURIComponent(options.error))
  }
})

const userStore = useUserStore()

const loading = ref(false)
const showPassword = ref(false)
const form = reactive({
  email: '',
  password: '',
})

// OAuth 配置
const oauthConfig = reactive<OAuthConfig>({
  google: { enabled: false, clientId: '' },
  apple: { enabled: false, clientId: '' },
})

// 加载 OAuth 配置
onMounted(async () => {
  try {
    const config = await fetchOAuthConfig()
    oauthConfig.google = config.google
    oauthConfig.apple = config.apple
  } catch (error: any) {
    // OAuth 配置加载失败，静默处理
  }
})

// 阻止触摸滑动
function onTouchMove() {
  // 阻止页面滚动
  return false
}

async function handleLogin() {
  if (!form.email || !form.password) {
    toast.warning(t('auth.fillAllFields'))
    return
  }

  loading.value = true
  try {
    await userStore.login({
      email: form.email,
      password: form.password,
    })
    toast.success(t('auth.loginSuccess'))
    setTimeout(() => {
      uni.switchTab({ url: '/pages/index/index' })
    }, 1500)
  } catch (e: any) {
    toast.error(e.message || t('auth.loginFailed'))
  } finally {
    loading.value = false
  }
}

async function socialLogin(provider: string) {
  if (provider === 'google') {
    await handleGoogleLogin()
  } else if (provider === 'apple') {
    toast.info(t('common.comingSoon'))
  }
}

async function handleGoogleLogin() {
  loading.value = true
  try {
    // 1. 获取 Google ID Token
    const idToken = await initGoogleAuth()

    // 2. 调用后端社交登录 API
    await userStore.socialLogin({
      platform: 'google',
      accessToken: idToken,
    })

    toast.success(t('auth.loginSuccess'))
    setTimeout(() => {
      uni.switchTab({ url: '/pages/index/index' })
    }, 1500)
  } catch (e: any) {
    console.error('Google login failed:', e)
    toast.error(e.message || t('auth.loginFailed'))
  } finally {
    loading.value = false
  }
}

function goForgotPassword() {
  uni.navigateTo({ url: '/pages/auth/forgot-password' })
}

function goRegister() {
  uni.navigateTo({ url: '/pages/auth/register' })
}
</script>

<style lang="scss" scoped>
.page {
  height: 100vh;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.content-wrapper {
  padding: 60px 24px 40px;
}

.header {
  text-align: center;
  margin-bottom: 40px;
}

.logo {
  width: 150px;
  height: 80px;
  margin-bottom: 24px;
}

.title {
  font-size: 28px;
  font-weight: 700;
  color: #333;
  display: block;
  margin-bottom: 8px;
}

.subtitle {
  font-size: 14px;
  color: #999;
}

.form {
  max-width: 400px;
}

.input-group {
  margin-bottom: 20px;
  position: relative;
}

.input-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
  display: block;
}

.input {
  width: 100%;
  height: 48px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 0 16px;
  font-size: 16px;
  box-sizing: border-box;

  &:focus {
    border-color: #FF6B35;
  }
}

.toggle-password {
  position: absolute;
  right: 16px;
  top: 38px;
  font-size: 18px;
}

.forgot-password {
  text-align: right;
  margin-bottom: 24px;

  text {
    font-size: 14px;
    color: #FF6B35;
  }
}

.login-btn {
  width: 100%;
  height: 48px;
  line-height: 48px;
  padding: 0;
  background-color: #FF6B35 !important;
  color: #fff !important;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;

  // 移除 uni-app 按钮默认边框
  &::after {
    border: none;
  }
}

.divider {
  display: flex;
  align-items: center;
  margin: 32px 0;
}

.divider-line {
  flex: 1;
  height: 1px;
  background-color: #e0e0e0;
}

.divider-text {
  padding: 0 16px;
  font-size: 14px;
  color: #999;
}

.social-login {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
}

.social-btn {
  flex: 1;
  height: 48px;
  line-height: 48px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;

  // 移除 uni-app 按钮默认边框
  &::after {
    border: none;
  }

  &.google {
    background-color: #fff !important;
    border: 1px solid #e0e0e0;
    color: #333 !important;
  }

  &.apple {
    background-color: #000 !important;
    color: #fff !important;
  }
}

.social-icon {
  font-size: 18px;
  font-weight: 700;
}

.register-link {
  text-align: center;
  font-size: 14px;
  color: #666;

  .link {
    color: #FF6B35;
    font-weight: 500;
    margin-left: 4px;
  }
}

// ==========================================
// Toast 样式
// ==========================================
$color-success: #10B981;
$color-error: #EF4444;
$color-warning: #F59E0B;
$color-info: #3B82F6;

.toast-container {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 10000;
  display: flex;
  justify-content: center;
  pointer-events: none;
  padding: 0 48rpx;

  &.toast-top {
    top: calc(env(safe-area-inset-top) + 120rpx);
  }

  &.toast-center {
    top: 50%;
    transform: translateY(-50%);
  }

  &.toast-bottom {
    bottom: calc(env(safe-area-inset-bottom) + 120rpx);
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx 36rpx;
  border-radius: 48rpx;
  background: rgba(0, 0, 0, 0.85);
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15);
  animation: toastFadeIn 0.25s ease-out;

  &.toast-success {
    .toast-icon {
      color: $color-success;
    }
  }

  &.toast-error {
    .toast-icon {
      color: $color-error;
    }
  }

  &.toast-warning {
    .toast-icon {
      color: $color-warning;
    }
  }

  &.toast-info {
    .toast-icon {
      color: $color-info;
    }
  }
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 40rpx;
  }
}

.toast-message {
  font-size: 28rpx;
  color: #fff;
  font-weight: 500;
  max-width: 480rpx;
  word-break: break-word;
}

@keyframes toastFadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
