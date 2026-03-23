<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-content">
        <view class="nav-left" @click="goBack">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="nav-title">{{ t('user.changePhone') }}</text>
        <view class="nav-right"></view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-placeholder" :style="{ height: (statusBarHeight + 56) + 'px' }"></view>

    <scroll-view class="content" scroll-y>
      <!-- 步骤指示器 -->
      <view class="steps">
        <view class="step" :class="{ 'active': step >= 1, 'completed': step > 1 }">
          <view class="step-number">{{ step > 1 ? '✓' : '1' }}</view>
          <text class="step-label">{{ t('user.verifyIdentity') }}</text>
        </view>
        <view class="step-line" :class="{ 'active': step > 1 }"></view>
        <view class="step" :class="{ 'active': step >= 2, 'completed': step > 2 }">
          <view class="step-number">{{ step > 2 ? '✓' : '2' }}</view>
          <text class="step-label">{{ t('user.newPhone') }}</text>
        </view>
        <view class="step-line" :class="{ 'active': step > 2 }"></view>
        <view class="step" :class="{ 'active': step >= 3 }">
          <view class="step-number">3</view>
          <text class="step-label">{{ t('user.complete') }}</text>
        </view>
      </view>

      <!-- 步骤1: 验证身份（通过邮箱） -->
      <view v-if="step === 1" class="step-content">
        <view class="section">
          <text class="section-title">{{ t('user.verifyByEmail') }}</text>
          <text class="section-desc">{{ t('user.verifyByEmailDesc') }}</text>

          <view class="current-info">
            <text class="bi bi-envelope"></text>
            <text class="info-text">{{ maskEmail(userInfo?.email) }}</text>
          </view>

          <view class="input-group">
            <text class="input-label">{{ t('user.verificationCode') }}</text>
            <view class="code-input-wrap">
              <input
                v-model="verifyCode"
                class="code-input"
                type="number"
                maxlength="6"
                :placeholder="t('user.enterVerificationCode')"
              />
              <button
                class="send-code-btn"
                :class="{ 'disabled': countdown > 0 }"
                :disabled="countdown > 0 || isSendingCode"
                @click="sendVerifyCode"
              >
                {{ countdown > 0 ? `${countdown}s` : t('user.sendCode') }}
              </button>
            </view>
          </view>

          <button
            class="next-btn"
            :class="{ 'disabled': !verifyCode || verifyCode.length < 6 }"
            :disabled="!verifyCode || verifyCode.length < 6 || isVerifying"
            @click="verifyIdentity"
          >
            <text v-if="isVerifying" class="bi bi-arrow-repeat spinning"></text>
            <text v-else>{{ t('user.nextStep') }}</text>
          </button>
        </view>
      </view>

      <!-- 步骤2: 输入新手机号 -->
      <view v-if="step === 2" class="step-content">
        <view class="section">
          <text class="section-title">{{ t('user.enterNewPhone') }}</text>
          <text class="section-desc">{{ t('user.enterNewPhoneDesc') }}</text>

          <view class="input-group">
            <text class="input-label">{{ t('user.newPhoneNumber') }}</text>
            <view class="phone-input-wrap">
              <view class="country-code" @click="selectCountryCode">
                <text>{{ selectedCountryCode }}</text>
                <text class="bi bi-chevron-down"></text>
              </view>
              <input
                v-model="newPhone"
                class="phone-input"
                type="number"
                :placeholder="t('user.enterNewPhonePlaceholder')"
              />
            </view>
          </view>

          <view class="input-group">
            <text class="input-label">{{ t('user.verificationCode') }}</text>
            <view class="code-input-wrap">
              <input
                v-model="newPhoneCode"
                class="code-input"
                type="number"
                maxlength="6"
                :placeholder="t('user.enterVerificationCode')"
              />
              <button
                class="send-code-btn"
                :class="{ 'disabled': newPhoneCountdown > 0 || !newPhone }"
                :disabled="newPhoneCountdown > 0 || !newPhone || isSendingNewCode"
                @click="sendNewPhoneCode"
              >
                {{ newPhoneCountdown > 0 ? `${newPhoneCountdown}s` : t('user.sendCode') }}
              </button>
            </view>
          </view>

          <button
            class="next-btn"
            :class="{ 'disabled': !canSubmitNewPhone }"
            :disabled="!canSubmitNewPhone || isSubmitting"
            @click="submitNewPhone"
          >
            <text v-if="isSubmitting" class="bi bi-arrow-repeat spinning"></text>
            <text v-else>{{ t('user.confirmChange') }}</text>
          </button>
        </view>
      </view>

      <!-- 步骤3: 完成 -->
      <view v-if="step === 3" class="step-content">
        <view class="success-section">
          <view class="success-icon">
            <text class="bi bi-check-circle-fill"></text>
          </view>
          <text class="success-title">{{ t('user.phoneChangedSuccess') }}</text>
          <text class="success-desc">{{ t('user.phoneChangedSuccessDesc') }}</text>
          <text class="new-phone-display">{{ selectedCountryCode }} {{ newPhone }}</text>

          <button class="done-btn" @click="goBack">
            {{ t('common.done') }}
          </button>
        </view>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 全局 Toast -->
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'
import { sendEmailCode, verifyEmailCode, sendPhoneCode, changePhone } from '@/api/user'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()

// Toast 功能
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

function showToast(options: {
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

// 状态栏高度
const statusBarHeight = computed(() => appStore.statusBarHeight)

// 用户信息
const userInfo = computed(() => userStore.userInfo)

// 步骤
const step = ref(1)

// 步骤1相关
const verifyCode = ref('')
const countdown = ref(0)
const isSendingCode = ref(false)
const isVerifying = ref(false)
let countdownTimer: number | null = null

// 步骤2相关
const selectedCountryCode = ref('+886')
const newPhone = ref('')
const newPhoneCode = ref('')
const newPhoneCountdown = ref(0)
const isSendingNewCode = ref(false)
const isSubmitting = ref(false)
let newPhoneCountdownTimer: number | null = null
const verifyToken = ref('')

// 是否可以提交新手机号
const canSubmitNewPhone = computed(() => {
  return newPhone.value.length >= 8 && newPhoneCode.value.length === 6
})

// 返回上一页
function goBack() {
  uni.navigateBack()
}

// 邮箱脱敏显示
function maskEmail(email?: string): string {
  if (!email) return ''
  const [name, domain] = email.split('@')
  if (!domain) return email
  const maskedName = name.length > 3
    ? name.substring(0, 3) + '***'
    : name.substring(0, 1) + '***'
  return `${maskedName}@${domain}`
}

// 选择国家代码
function selectCountryCode() {
  uni.showActionSheet({
    itemList: ['+886 (台湾)', '+86 (中国)', '+81 (日本)', '+1 (美国)'],
    success: (res) => {
      const codes = ['+886', '+86', '+81', '+1']
      selectedCountryCode.value = codes[res.tapIndex]
    },
  })
}

// 发送验证码（通过邮箱验证身份）
async function sendVerifyCode() {
  if (countdown.value > 0 || isSendingCode.value || !userInfo.value?.email) return

  isSendingCode.value = true
  try {
    const res = await sendEmailCode({
      email: userInfo.value.email,
      type: 'verify',
    })
    if (res.code === 0) {
      showToast({ message: t('user.codeSent'), type: 'success' })
      startCountdown()
    } else {
      showToast({ message: res.msg || t('user.sendCodeFailed'), type: 'error' })
    }
  } catch (e: any) {
    showToast({ message: e.message || t('user.sendCodeFailed'), type: 'error' })
  } finally {
    isSendingCode.value = false
  }
}

// 开始倒计时
function startCountdown() {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
      }
    }
  }, 1000) as unknown as number
}

// 验证身份
async function verifyIdentity() {
  if (!verifyCode.value || verifyCode.value.length < 6 || isVerifying.value || !userInfo.value?.email) return

  isVerifying.value = true
  try {
    const res = await verifyEmailCode({
      email: userInfo.value.email,
      code: verifyCode.value,
      type: 'verify',
    })
    if (res.code === 0 && res.data.verified) {
      verifyToken.value = res.data.verifyToken
      step.value = 2
      showToast({ message: t('user.verifySuccess'), type: 'success' })
    } else {
      showToast({ message: res.msg || t('user.verifyFailed'), type: 'error' })
    }
  } catch (e: any) {
    showToast({ message: e.message || t('user.verifyFailed'), type: 'error' })
  } finally {
    isVerifying.value = false
  }
}

// 发送验证码到新手机
async function sendNewPhoneCode() {
  if (newPhoneCountdown.value > 0 || !newPhone.value || isSendingNewCode.value) return

  isSendingNewCode.value = true
  try {
    const res = await sendPhoneCode({
      phone: newPhone.value,
      countryCode: selectedCountryCode.value,
    })
    if (res.code === 0) {
      showToast({ message: t('user.codeSent'), type: 'success' })
      startNewPhoneCountdown()
    } else {
      showToast({ message: res.msg || t('user.sendCodeFailed'), type: 'error' })
    }
  } catch (e: any) {
    showToast({ message: e.message || t('user.sendCodeFailed'), type: 'error' })
  } finally {
    isSendingNewCode.value = false
  }
}

// 开始新手机验证码倒计时
function startNewPhoneCountdown() {
  newPhoneCountdown.value = 60
  newPhoneCountdownTimer = setInterval(() => {
    newPhoneCountdown.value--
    if (newPhoneCountdown.value <= 0) {
      if (newPhoneCountdownTimer) {
        clearInterval(newPhoneCountdownTimer)
        newPhoneCountdownTimer = null
      }
    }
  }, 1000) as unknown as number
}

// 提交新手机号
async function submitNewPhone() {
  if (!canSubmitNewPhone.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    const res = await changePhone({
      verifyToken: verifyToken.value,
      phone: newPhone.value,
      countryCode: selectedCountryCode.value,
      code: newPhoneCode.value,
    })
    if (res.code === 0) {
      // 刷新用户信息
      await userStore.fetchUserInfo()
      step.value = 3
      showToast({ message: t('user.phoneChangedSuccess'), type: 'success' })
    } else {
      showToast({ message: res.msg || t('user.changePhoneFailed'), type: 'error' })
    }
  } catch (e: any) {
    showToast({ message: e.message || t('user.changePhoneFailed'), type: 'error' })
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  appStore.initSystemInfo()
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('user.changePhone') })
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
  if (newPhoneCountdownTimer) {
    clearInterval(newPhoneCountdownTimer)
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #FF6B35;
$color-primary-light: #FFF5F0;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #228B22;
$color-disabled: #C4C4C4;

$font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 24px;

$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;

$radius-sm: 8px;
$radius-md: 12px;
$radius-full: 9999px;

$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

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

// 步骤指示器
.steps {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: $spacing-xl $spacing-base;
  background-color: $color-surface;
  margin-bottom: $spacing-md;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $spacing-sm;
}

.step-number {
  width: 32px;
  height: 32px;
  border-radius: $radius-full;
  background-color: $color-border;
  color: $color-text-muted;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;

  .step.active & {
    background-color: $color-primary;
    color: #fff;
  }

  .step.completed & {
    background-color: $color-success;
    color: #fff;
  }
}

.step-label {
  font-size: $font-size-sm;
  color: $color-text-muted;

  .step.active & {
    color: $color-primary;
    font-weight: $font-weight-medium;
  }

  .step.completed & {
    color: $color-success;
  }
}

.step-line {
  width: 40px;
  height: 2px;
  background-color: $color-border;
  margin: 0 $spacing-sm;
  margin-bottom: 24px;

  &.active {
    background-color: $color-success;
  }
}

// 步骤内容
.step-content {
  padding: 0 $spacing-base;
}

.section {
  background-color: $color-surface;
  border-radius: $radius-md;
  padding: $spacing-xl $spacing-base;
}

.section-title {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  display: block;
  margin-bottom: $spacing-sm;
}

.section-desc {
  font-size: $font-size-base;
  color: $color-text-secondary;
  line-height: 1.5;
  display: block;
  margin-bottom: $spacing-lg;
}

// 当前信息显示
.current-info {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-base;
  background-color: $color-primary-light;
  border-radius: $radius-sm;
  margin-bottom: $spacing-lg;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }

  .info-text {
    font-size: $font-size-base;
    color: $color-text-primary;
    font-weight: $font-weight-medium;
  }
}

// 输入组
.input-group {
  margin-bottom: $spacing-lg;
}

.input-label {
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  color: $color-text-primary;
  display: block;
  margin-bottom: $spacing-sm;
}

.phone-input-wrap {
  display: flex;
  gap: $spacing-md;
}

.country-code {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  height: 48px;
  padding: 0 $spacing-base;
  border: 1px solid $color-border;
  border-radius: $radius-sm;
  background-color: $color-surface;
  cursor: pointer;

  .bi {
    font-size: 12px;
    color: $color-text-muted;
  }

  &:active {
    background-color: $color-background;
  }
}

.phone-input {
  flex: 1;
  height: 48px;
  padding: 0 $spacing-base;
  border: 1px solid $color-border;
  border-radius: $radius-sm;
  font-size: $font-size-base;
  color: $color-text-primary;
  background-color: $color-surface;

  &::placeholder {
    color: $color-text-muted;
  }
}

.code-input-wrap {
  display: flex;
  gap: $spacing-md;
}

.code-input {
  flex: 1;
  height: 48px;
  padding: 0 $spacing-base;
  border: 1px solid $color-border;
  border-radius: $radius-sm;
  font-size: $font-size-base;
  color: $color-text-primary;
  background-color: $color-surface;

  &::placeholder {
    color: $color-text-muted;
  }
}

.send-code-btn {
  min-width: 100px;
  height: 48px;
  padding: 0 $spacing-base;
  background-color: $color-surface;
  border: 1px solid $color-primary;
  border-radius: $radius-sm;
  color: $color-primary;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  display: flex;
  align-items: center;
  justify-content: center;

  &.disabled {
    border-color: $color-disabled;
    color: $color-disabled;
  }

  &::after {
    border: none;
  }
}

// 按钮
.next-btn,
.done-btn {
  width: 100%;
  height: 48px;
  border-radius: $radius-full;
  background-color: $color-primary;
  color: #fff;
  font-size: $font-size-md;
  font-weight: $font-weight-medium;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  margin-top: $spacing-lg;
  display: flex;
  align-items: center;
  justify-content: center;

  &.disabled {
    background-color: $color-disabled;
  }

  &::after {
    border: none;
  }

  .spinning {
    animation: spin 1s linear infinite;
  }
}

// 成功页面
.success-section {
  background-color: $color-surface;
  border-radius: $radius-md;
  padding: $spacing-xl * 2 $spacing-base;
  text-align: center;
}

.success-icon {
  margin-bottom: $spacing-lg;

  .bi {
    font-size: 64px;
    color: $color-success;
  }
}

.success-title {
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
  display: block;
  margin-bottom: $spacing-sm;
}

.success-desc {
  font-size: $font-size-base;
  color: $color-text-secondary;
  display: block;
  margin-bottom: $spacing-base;
}

.new-phone-display {
  font-size: $font-size-md;
  font-weight: $font-weight-medium;
  color: $color-primary;
  display: block;
  padding: $spacing-base;
  background-color: $color-primary-light;
  border-radius: $radius-sm;
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

// Toast 样式
.toast-container {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 10000;
  display: flex;
  justify-content: center;
  padding: 0 $spacing-xl;
  pointer-events: none;

  &.toast-top {
    top: calc(env(safe-area-inset-top, 44px) + 60px);
  }

  &.toast-center {
    top: 50%;
    transform: translateY(-50%);
  }

  &.toast-bottom {
    bottom: calc(env(safe-area-inset-bottom, 0px) + 100px);
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-base $spacing-lg;
  border-radius: $radius-md;
  background-color: rgba(0, 0, 0, 0.8);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  animation: toastIn 0.3s ease-out;

  &.toast-success {
    background-color: rgba(34, 139, 34, 0.95);
  }

  &.toast-error {
    background-color: rgba(220, 53, 69, 0.95);
  }

  &.toast-warning {
    background-color: rgba(255, 193, 7, 0.95);

    .toast-message {
      color: #333;
    }
  }

  &.toast-info {
    background-color: rgba(0, 123, 255, 0.95);
  }
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 20px;
    color: #fff;
  }
}

.toast-message {
  font-size: $font-size-base;
  color: #fff;
  max-width: 250px;
  word-break: break-word;
}

@keyframes toastIn {
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
