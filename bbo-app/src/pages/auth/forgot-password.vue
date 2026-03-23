<template>
  <view class="page">
    <NavBar :title="t('auth.resetPassword')" />

    <view class="content-wrapper">
      <!-- Step 1: 输入邮箱 -->
      <view v-if="step === 1" class="step-content">
        <view class="step-header">
          <text class="step-icon bi bi-envelope"></text>
          <text class="step-title">{{ t('auth.enterEmail') }}</text>
          <text class="step-desc">{{ t('auth.enterEmailDesc') }}</text>
        </view>

        <view class="form">
          <view class="input-group">
            <text class="input-label">{{ t('auth.email') }}</text>
            <input
              class="input"
              type="text"
              v-model="form.email"
              :placeholder="t('auth.emailPlaceholder')"
            />
          </view>

          <button
            class="submit-btn"
            :loading="loading"
            :disabled="!form.email || loading"
            @click="handleSendCode"
          >
            {{ t('auth.sendCode') }}
          </button>
        </view>
      </view>

      <!-- Step 2: 输入验证码 -->
      <view v-if="step === 2" class="step-content">
        <view class="step-header">
          <text class="step-icon bi bi-shield-lock"></text>
          <text class="step-title">{{ t('auth.enterCode') }}</text>
          <text class="step-desc">{{ codeSentText }}</text>
        </view>

        <view class="form">
          <view class="code-input-wrapper">
            <input
              v-for="(_, index) in 6"
              :key="index"
              class="code-input"
              type="number"
              maxlength="1"
              v-model="codeDigits[index]"
              :focus="focusIndex === index"
              @input="onCodeInput(index)"
              @keydown="onCodeKeydown($event, index)"
            />
          </view>

          <view class="countdown-wrapper">
            <text v-if="countdown > 0" class="countdown-text">
              {{ resendInText }}
            </text>
            <text v-else class="resend-link" @click="handleSendCode">
              {{ t('auth.resendCode') }}
            </text>
          </view>

          <button
            class="submit-btn"
            :loading="loading"
            :disabled="codeDigits.join('').length !== 6 || loading"
            @click="handleVerifyCode"
          >
            {{ t('auth.verifyCode') }}
          </button>
        </view>
      </view>

      <!-- Step 3: 设置新密码 -->
      <view v-if="step === 3" class="step-content">
        <view class="step-header">
          <text class="step-icon bi bi-key"></text>
          <text class="step-title">{{ t('auth.newPassword') }}</text>
          <text class="step-desc">{{ t('auth.newPasswordDesc') }}</text>
        </view>

        <view class="form">
          <view class="input-group">
            <text class="input-label">{{ t('auth.newPassword') }}</text>
            <view class="input-wrapper">
              <input
                class="input"
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                :placeholder="t('auth.newPasswordPlaceholder')"
              />
              <view class="toggle-password" @click="showPassword = !showPassword">
                <text :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
              </view>
            </view>
          </view>

          <view class="input-group">
            <text class="input-label">{{ t('auth.confirmNewPassword') }}</text>
            <view class="input-wrapper">
              <input
                class="input"
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="form.confirmPassword"
                :placeholder="t('auth.confirmNewPasswordPlaceholder')"
              />
              <view class="toggle-password" @click="showConfirmPassword = !showConfirmPassword">
                <text :class="['bi', showConfirmPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
              </view>
            </view>
          </view>

          <view class="password-requirements">
            <text class="requirements-title">{{ t('auth.passwordRequirements') }}</text>
            <view class="requirement-item" :class="{ valid: form.password.length >= 6 }">
              <text class="bi" :class="form.password.length >= 6 ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text>{{ minLengthText }}</text>
            </view>
            <view class="requirement-item" :class="{ valid: form.password === form.confirmPassword && form.password.length > 0 }">
              <text class="bi" :class="form.password === form.confirmPassword && form.password.length > 0 ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text>{{ t('auth.passwordsMatch') }}</text>
            </view>
          </view>

          <button
            class="submit-btn"
            :loading="loading"
            :disabled="!canSubmitPassword || loading"
            @click="handleResetPassword"
          >
            {{ t('auth.resetPassword') }}
          </button>
        </view>
      </view>

      <!-- Step 4: 成功 -->
      <view v-if="step === 4" class="step-content success-content">
        <view class="success-icon-wrapper">
          <text class="bi bi-check-circle-fill success-icon"></text>
        </view>
        <text class="success-title">{{ t('auth.resetSuccess') }}</text>
        <text class="success-desc">{{ t('auth.resetSuccessDesc') }}</text>
        <button class="submit-btn" @click="goLogin">
          {{ t('auth.backToLogin') }}
        </button>
      </view>

      <!-- 返回登录链接 -->
      <view v-if="step < 4" class="back-link" @click="goLogin">
        <text class="bi bi-arrow-left"></text>
        <text>{{ t('auth.backToLogin') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { sendResetCode, verifyResetCode, resetPassword } from '@/api/user'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('auth.resetPassword') })
})

// 状态
const step = ref(1)
const loading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const countdown = ref(0)
const countdownTimer = ref<number | null>(null)
const maskedEmail = ref('')
const resetToken = ref('')

// 表单
const form = reactive({
  email: '',
  password: '',
  confirmPassword: '',
})

// 验证码输入
const codeDigits = ref<string[]>(['', '', '', '', '', ''])
const focusIndex = ref<number>(0) // 当前聚焦的输入框索引

// 计算属性
const canSubmitPassword = computed(() => {
  return (
    form.password.length >= 6 &&
    form.password === form.confirmPassword
  )
})

// 插值翻译文本（解决 UniApp APP 端 vue-i18n 插值不生效的问题）
const codeSentText = computed(() => {
  // 手动替换 {email} 占位符
  const template = t('auth.codeSent')
  return template.replace('[EMAIL]', maskedEmail.value)
})

const resendInText = computed(() => {
  // 手动替换 [SECONDS] 占位符
  const template = t('auth.resendIn')
  return template.replace('[SECONDS]', String(countdown.value))
})

const minLengthText = computed(() => {
  // 手动替换 [LENGTH] 占位符
  const template = t('auth.minLength')
  return template.replace('[LENGTH]', '6')
})

// 验证码输入处理
function onCodeInput(index: number) {
  const value = codeDigits.value[index]
  if (value && index < 5) {
    // 自动跳转到下一个输入框
    focusIndex.value = index + 1
  }
}

function onCodeKeydown(event: any, index: number) {
  // 处理删除键
  if (event.key === 'Backspace' && !codeDigits.value[index] && index > 0) {
    focusIndex.value = index - 1
  }
}

// 开始倒计时
function startCountdown() {
  countdown.value = 60
  countdownTimer.value = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer.value!)
      countdownTimer.value = null
    }
  }, 1000) as unknown as number
}

// 发送验证码
async function handleSendCode() {
  if (!form.email) {
    toast.warning(t('auth.emailRequired'))
    return
  }

  // 简单的邮箱格式验证
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.email)) {
    toast.warning(t('auth.invalidEmail'))
    return
  }

  loading.value = true
  try {
    const res = await sendResetCode({ email: form.email })
    if (res.code === 0) {
      maskedEmail.value = res.data.email
      step.value = 2
      // 重置验证码输入状态
      codeDigits.value = ['', '', '', '', '', '']
      focusIndex.value = 0
      startCountdown()
      toast.success(t('auth.codeSentSuccess'))
    } else {
      toast.error(res.message || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    loading.value = false
  }
}

// 验证验证码
async function handleVerifyCode() {
  const code = codeDigits.value.join('')
  if (code.length !== 6) {
    toast.warning(t('auth.invalidCode'))
    return
  }

  loading.value = true
  try {
    const res = await verifyResetCode({ email: form.email, code })
    if (res.code === 0 && res.data.verified) {
      resetToken.value = res.data.resetToken
      step.value = 3
    } else {
      toast.error(res.message || t('auth.invalidCode'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    loading.value = false
  }
}

// 重置密码
async function handleResetPassword() {
  if (!canSubmitPassword.value) {
    return
  }

  loading.value = true
  try {
    const res = await resetPassword({
      resetToken: resetToken.value,
      password: form.password,
      confirmPassword: form.confirmPassword,
    })
    if (res.code === 0) {
      step.value = 4
    } else {
      toast.error(res.message || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    loading.value = false
  }
}

// 返回登录
function goLogin() {
  uni.navigateBack()
}

// 清理计时器
onUnmounted(() => {
  if (countdownTimer.value) {
    clearInterval(countdownTimer.value)
  }
})
</script>

<style lang="scss" scoped>
$color-primary: #FF6B35;
$color-text: #333;
$color-text-secondary: #666;
$color-text-muted: #999;
$color-border: #e0e0e0;
$color-success: #52c41a;
$color-background: #f5f5f5;

.page {
  min-height: 100vh;
  background-color: #fff;
}

.content-wrapper {
  padding: 24px;
  padding-top: calc(var(--nav-height, 44px) + 24px);
}

.step-content {
  max-width: 400px;
}

.step-header {
  text-align: center;
  margin-bottom: 32px;
}

.step-icon {
  font-size: 48px;
  color: $color-primary;
  display: block;
  margin-bottom: 16px;
}

.step-title {
  font-size: 24px;
  font-weight: 600;
  color: $color-text;
  display: block;
  margin-bottom: 8px;
}

.step-desc {
  font-size: 14px;
  color: $color-text-muted;
  line-height: 1.5;
}

.form {
  margin-bottom: 24px;
}

.input-group {
  margin-bottom: 20px;
}

.input-label {
  font-size: 14px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 8px;
  display: block;
}

.input-wrapper {
  position: relative;
}

.input {
  width: 100%;
  height: 48px;
  border: 1px solid $color-border;
  border-radius: 8px;
  padding: 0 16px;
  font-size: 16px;
  box-sizing: border-box;

  &:focus {
    border-color: $color-primary;
  }
}

.toggle-password {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
  color: $color-text-muted;
}

// 验证码输入
.code-input-wrapper {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-bottom: 24px;
}

.code-input {
  width: 48px;
  height: 56px;
  border: 2px solid $color-border;
  border-radius: 8px;
  font-size: 24px;
  font-weight: 600;
  text-align: center;

  &:focus {
    border-color: $color-primary;
  }
}

.countdown-wrapper {
  text-align: center;
  margin-bottom: 24px;
}

.countdown-text {
  font-size: 14px;
  color: $color-text-muted;
}

.resend-link {
  font-size: 14px;
  color: $color-primary;
  font-weight: 500;
}

// 密码要求
.password-requirements {
  background-color: $color-background;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.requirements-title {
  font-size: 14px;
  font-weight: 500;
  color: $color-text;
  display: block;
  margin-bottom: 12px;
}

.requirement-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: $color-text-muted;
  margin-bottom: 8px;

  &:last-child {
    margin-bottom: 0;
  }

  .bi {
    font-size: 14px;
  }

  &.valid {
    color: $color-success;

    .bi {
      color: $color-success;
    }
  }
}

// 提交按钮
.submit-btn {
  width: 100%;
  height: 48px;
  line-height: 48px;
  padding: 0;
  background-color: $color-primary !important;
  color: #fff !important;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;

  &::after {
    border: none;
  }

  &[disabled] {
    background-color: #ccc !important;
    color: #fff !important;
  }
}

// 成功页面
.success-content {
  text-align: center;
  padding-top: 40px;
}

.success-icon-wrapper {
  margin-bottom: 24px;
}

.success-icon {
  font-size: 72px;
  color: $color-success;
}

.success-title {
  font-size: 24px;
  font-weight: 600;
  color: $color-text;
  display: block;
  margin-bottom: 8px;
}

.success-desc {
  font-size: 14px;
  color: $color-text-muted;
  display: block;
  margin-bottom: 32px;
}

// 返回登录链接
.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  color: $color-text-secondary;
  margin-top: 24px;

  .bi {
    font-size: 16px;
  }
}
</style>
