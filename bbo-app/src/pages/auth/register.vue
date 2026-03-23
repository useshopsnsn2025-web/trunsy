<template>
  <view class="page">
    <!-- 步骤指示器 -->
    <view class="steps-indicator">
      <view
        v-for="i in 4"
        :key="i"
        :class="['step-dot', { active: currentStep >= i }]"
      />
    </view>

    <!-- 顶部导航栏 -->
    <view class="page-header">
      <text class="bi bi-x-lg close-btn" @click="goBack"></text>
      <text class="page-title">{{ t('auth.createAccount') }}</text>
      <view class="header-placeholder"></view>
    </view>

    <!-- 步骤 1: 基本信息 -->
    <view v-if="currentStep === 1" class="step-content">
      <view class="header">
        <text class="title">{{ t('auth.getStarted') }}</text>
        <text class="subtitle">{{ t('auth.registerSubtitle') }}</text>
      </view>

      <view class="form">
        <view class="form-fields">
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
            <text class="input-label">{{ t('auth.lastName') }}</text>
            <input
              class="input"
              type="text"
              v-model="form.lastName"
              :placeholder="t('auth.lastNamePlaceholder')"
              :adjust-position="false"
            />
          </view>

          <view class="input-group">
            <text class="input-label">{{ t('auth.firstName') }}</text>
            <input
              class="input"
              type="text"
              v-model="form.firstName"
              :placeholder="t('auth.firstNamePlaceholder')"
              :adjust-position="false"
            />
          </view>
        </view>

        <view class="form-actions">
          <button class="primary-btn" :disabled="!canProceedStep1" @click="goStep2">
            {{ t('common.continue') }}
          </button>

          <view class="login-link">
            <text>{{ t('auth.hasAccount') }}</text>
            <text class="link" @click="goLogin">{{ t('auth.login') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 步骤 2: 创建密码 -->
    <view v-if="currentStep === 2" class="step-content">
      <view class="header">
        <text class="title">{{ t('auth.createPassword') }}</text>
        <text class="subtitle">{{ t('auth.passwordRequirements') }}</text>
      </view>

      <view class="form">
        <view class="form-fields">
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

          <!-- 密码要求清单 -->
          <view class="password-rules">
            <view :class="['rule-item', { fulfilled: hasMinLength }]">
              <text class="bi" :class="hasMinLength ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text class="rule-text">{{ t('auth.pwdRuleLength') || 'More than 8 characters' }}</text>
            </view>
            <view :class="['rule-item', { fulfilled: hasUppercase }]">
              <text class="bi" :class="hasUppercase ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text class="rule-text">{{ t('auth.pwdRuleUppercase') || 'At least one uppercase letter' }}</text>
            </view>
            <view :class="['rule-item', { fulfilled: hasLowercase }]">
              <text class="bi" :class="hasLowercase ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text class="rule-text">{{ t('auth.pwdRuleLowercase') || 'At least one lowercase letter' }}</text>
            </view>
            <view :class="['rule-item', { fulfilled: hasSpecialChar }]">
              <text class="bi" :class="hasSpecialChar ? 'bi-check-circle-fill' : 'bi-circle'"></text>
              <text class="rule-text">{{ t('auth.pwdRuleSpecial') || 'At least one special character' }}</text>
            </view>
          </view>

          <view class="agreement">
            <view class="agreement-row">
              <checkbox-group @change="onAgreementChange">
                <label class="checkbox-label">
                  <checkbox :checked="agreed" iconColor="#FF6B35"/>
                </label>
              </checkbox-group>
              <view class="agreement-text">
                <text>{{ t('auth.agreeTo') }}</text>
                <view class="link" @tap="openTerms">{{ t('auth.terms') }}</view>
                <text>{{ t('auth.and') }}</text>
                <view class="link" @tap="openPrivacy">{{ t('auth.privacy') }}</view>
              </view>
            </view>
          </view>
        </view>

        <view class="form-actions">
          <button
            class="primary-btn"
            :loading="loading"
            :disabled="!canProceedStep2"
            @click="goStep3"
          >
            {{ t('auth.createAccountBtn') }}
          </button>

          <view class="back-link" @click="currentStep = 1">
            <text class="bi bi-chevron-left"></text>
            <text>{{ t('common.back') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 步骤 3: 邮箱验证 -->
    <view v-if="currentStep === 3" class="step-content">
      <view class="header">
        <text class="title">{{ t('auth.verifyEmail') || 'Verify your email' }}</text>
        <text class="subtitle">{{ t('auth.verifyEmailSubtitle') || 'We will send a verification code to your email' }}</text>
      </view>

      <view class="form">
        <view class="form-fields">
          <view class="input-group">
            <text class="input-label">{{ t('auth.email') }}</text>
            <view class="email-display">
              <text class="bi bi-envelope"></text>
              <text class="email-text">{{ form.email }}</text>
            </view>
          </view>
        </view>

        <view class="form-actions">
          <button
            class="primary-btn"
            :loading="sendingCode"
            :disabled="sendingCode"
            @click="sendEmailVerificationCode"
          >
            {{ t('auth.sendVerificationCode') || 'Send verification code' }}
          </button>

          <view class="back-link" @click="currentStep = 2">
            <text class="bi bi-chevron-left"></text>
            <text>{{ t('common.back') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 步骤 4: 输入验证码 -->
    <view v-if="currentStep === 4" class="step-content">
      <view class="header">
        <text class="title">{{ t('auth.enterCode') }}</text>
        <text class="subtitle">{{ codeSentToText }}</text>
      </view>

      <view class="form">
        <view class="form-fields">
          <view class="code-input-wrapper">
            <input
              v-for="i in 6"
              :key="i"
              class="code-input"
              type="number"
              maxlength="1"
              :value="codeDigits[i - 1]"
              :focus="focusIndex === i - 1"
              @input="onCodeInput($event, i - 1)"
              @focus="focusIndex = i - 1"
            />
          </view>

          <view class="resend-wrapper">
            <text v-if="countdown > 0" class="countdown">
              {{ resendInText }}
            </text>
            <text v-else class="resend-link" @click="resendCode">
              {{ t('auth.resendCode') }}
            </text>
          </view>
        </view>

        <view class="form-actions">
          <button
            class="primary-btn"
            :loading="loading"
            :disabled="!canProceedStep4"
            @click="handleRegister"
          >
            {{ t('auth.verify') }}
          </button>

          <view class="back-link" @click="currentStep = 3">
            <text class="bi bi-chevron-left"></text>
            <text>{{ t('common.back') }}</text>
          </view>
        </view>
      </view>
    </view>


  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { sendRegisterEmailCode, verifyRegisterEmailCode, completeRegister } from '@/api/user'
import { registerWithInviteCode } from '@/api/share'

const { t } = useI18n()
const toast = useToast()
const userStore = useUserStore()

// 邀请码（从URL参数或本地存储获取）
const inviteCode = ref('')

// 页面加载时获取邀请码
onLoad((options) => {
  // 优先使用URL参数中的邀请码
  if (options?.invite_code) {
    inviteCode.value = options.invite_code
    // 同步保存到本地存储
    uni.setStorageSync('invite_code', options.invite_code)
  } else {
    // 否则尝试从本地存储获取
    const storedCode = uni.getStorageSync('invite_code')
    if (storedCode) {
      inviteCode.value = storedCode
    }
  }
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.register') })
})

// 步骤控制
const currentStep = ref(1)
const loading = ref(false)
const sendingCode = ref(false)
const showPassword = ref(false)
const agreed = ref(false)

// 表单数据
const form = reactive({
  email: '',
  firstName: '',
  lastName: '',
  password: '',
})

// 安全码相关
const codeDigits = ref<string[]>(['', '', '', '', '', ''])
const focusIndex = ref(0)
const countdown = ref(0)
const verifyToken = ref('')
const maskedEmail = ref('')


// 密码要求检查
const hasMinLength = computed(() => form.password.length > 8)
const hasUppercase = computed(() => /[A-Z]/.test(form.password))
const hasLowercase = computed(() => /[a-z]/.test(form.password))
const hasSpecialChar = computed(() => /[^a-zA-Z0-9]/.test(form.password))

// 手动替换 vue-i18n 插值（UniApp APP 端兼容性修复）
const codeSentToText = computed(() => {
  const template = t('auth.codeSentToEmail') || 'We sent a code to [EMAIL]'
  return template.replace('[EMAIL]', maskedEmail.value)
})

const resendInText = computed(() => {
  const template = t('auth.resendIn')
  return template.replace('[SECONDS]', String(countdown.value))
})

// 步骤验证
const canProceedStep1 = computed(() => {
  return form.email && form.firstName && form.lastName && isValidEmail(form.email)
})

const canProceedStep2 = computed(() => {
  const pwd = form.password
  return pwd.length > 8
    && /[a-z]/.test(pwd)
    && /[A-Z]/.test(pwd)
    && /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/;'`~]/.test(pwd)
    && agreed.value
})

const canProceedStep4 = computed(() => {
  return codeDigits.value.join('').length === 6
})

// 邮箱验证
function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

// 安全码输入处理
function onCodeInput(event: any, index: number) {
  const value = event.detail.value
  if (value) {
    codeDigits.value[index] = value.slice(-1)
    // 自动跳转到下一个输入框
    if (index < 5) {
      focusIndex.value = index + 1
    }
  } else {
    // 删除时返回上一个输入框
    if (index > 0) {
      focusIndex.value = index - 1
    }
  }
}

// 同意条款
function onAgreementChange(e: any) {
  agreed.value = e.detail.value.length > 0
}

// 打开条款页面
function openTerms() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=terms-of-service' })
}

function openPrivacy() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=privacy-policy' })
}

// 步骤导航
function goStep2() {
  if (!canProceedStep1.value) {
    toast.warning(t('auth.fillAllFields'))
    return
  }
  currentStep.value = 2
}

function goStep3() {
  if (!canProceedStep2.value) {
    if (!agreed.value) {
      toast.warning(t('auth.mustAgree'))
    } else {
      toast.warning(t('auth.passwordRequirementsFull') || 'Password must be more than 8 characters, include uppercase, lowercase, and a special character')
    }
    return
  }
  currentStep.value = 3
}

// 发送邮箱验证码
async function sendEmailVerificationCode() {
  if (!form.email) {
    toast.warning(t('auth.enterValidEmail') || 'Please enter a valid email')
    return
  }

  sendingCode.value = true
  try {
    const res = await sendRegisterEmailCode({
      email: form.email,
    })

    // 保存脱敏邮箱
    maskedEmail.value = res.data.email

    // 开始倒计时
    countdown.value = 60
    const timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(timer)
      }
    }, 1000)

    // 跳转到步骤4
    currentStep.value = 4
    // 重置验证码输入
    codeDigits.value = ['', '', '', '', '', '']
    focusIndex.value = 0

  } catch (e: any) {
    toast.error(e.message || t('auth.sendCodeFailed'))
  } finally {
    sendingCode.value = false
  }
}

// 重新发送验证码
async function resendCode() {
  if (countdown.value > 0) return
  await sendEmailVerificationCode()
}

// 完成注册
async function handleRegister() {
  const code = codeDigits.value.join('')
  if (code.length !== 6) {
    toast.warning(t('auth.enterCompleteCode'))
    return
  }

  loading.value = true
  try {
    // 先验证邮箱验证码
    const verifyRes = await verifyRegisterEmailCode({
      email: form.email,
      code: code,
    })

    if (!verifyRes.data.verified) {
      toast.error(t('auth.invalidCode'))
      return
    }

    verifyToken.value = verifyRes.data.verifyToken

    // 完成注册
    const res = await completeRegister({
      email: form.email,
      firstName: form.firstName,
      lastName: form.lastName,
      password: form.password,
      verifyToken: verifyToken.value,
    })

    // 处理登录响应
    userStore.handleLoginResponse(res.data)

    // 如果有邀请码，绑定邀请关系
    if (inviteCode.value) {
      try {
        await registerWithInviteCode(inviteCode.value)
        // 清除本地存储的邀请码
        uni.removeStorageSync('invite_code')
      } catch (e) {
        // 邀请码绑定失败不影响注册流程，静默处理
        // Invite code binding failed silently
      }
    }

    toast.success(t('auth.registerSuccess'))
    setTimeout(() => {
      uni.switchTab({ url: '/pages/index/index' })
    }, 1500)
  } catch (e: any) {
    toast.error(e.message || t('auth.registerFailed'))
  } finally {
    loading.value = false
  }
}

// 返回登录
function goLogin() {
  uni.navigateBack()
}

// 关闭页面返回
function goBack() {
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff;
  padding: 20px 24px 40px;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}

// 顶部导航栏
.page-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  margin-bottom: 16px;
  gap: 10px;
}

.close-btn {
  font-size: 20px;
  color: #333;
  padding: 8px;
}

.page-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.header-placeholder {
  width: 36px;
}

// 步骤指示器
.steps-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding-top: 10px;
  margin-bottom: 16px;
  min-height: 28px;
}

.step-dot {
  width: 8px;
  height: 8px;
  border-radius: 4px;
  background-color: #e0e0e0;
  transition: all 0.3s;

  &.active {
    width: 24px;
    background-color: #FF6B35;
  }
}

// 步骤内容
.step-content {
  animation: fadeIn 0.3s ease;
  flex: 1;
  display: flex;
  flex-direction: column;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.header {
  margin-bottom: 32px;
}

.title {
  font-size: 26px;
  font-weight: 700;
  color: #333;
  display: block;
  margin-bottom: 8px;
}

.subtitle {
  font-size: 14px;
  color: #666;
  line-height: 1.5;
}

.form {
  max-width: 400px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.form-fields {
  flex: 1;
}

.form-actions {
  margin-top: auto;
  padding-top: 24px;
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
  color: #666;
}

// 密码要求清单
.password-rules {
  margin-bottom: 20px;
}

.rule-item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;

  .bi {
    font-size: 14px;
    color: #ccc;
  }

  .rule-text {
    font-size: 13px;
    color: #999;
  }

  &.fulfilled {
    .bi {
      color: #34C759;
    }
    .rule-text {
      color: #34C759;
    }
  }
}

// 邮箱显示
.email-display {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 16px;
  height: 48px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background-color: #f5f5f5;
}

.email-display .bi {
  font-size: 18px;
  color: #999;
}

.email-text {
  font-size: 15px;
  color: #333;
  font-weight: 500;
}

// 验证码输入
.code-input-wrapper {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 20px;
}

.code-input {
  width: 44px;
  height: 52px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  text-align: center;
  font-size: 24px;
  font-weight: 600;
  box-sizing: border-box;

  &:focus {
    border-color: #FF6B35;
  }
}

.resend-wrapper {
  text-align: center;
  margin-bottom: 24px;
}

.countdown {
  font-size: 14px;
  color: #999;
}

.resend-link {
  font-size: 14px;
  color: #FF6B35;
  font-weight: 500;
}

// 同意条款
.agreement {
  margin-bottom: 24px;
}

.agreement-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.agreement-text {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  font-size: 12px;
  color: #666;
  gap: 2px;
  line-height: 1.5;
  padding-top: 2px;
}

.link {
  color: #FF6B35;
}

// 按钮
.primary-btn {
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

  &::after {
    border: none;
  }

  &[disabled] {
    background-color: #ccc !important;
    color: #fff !important;
  }
}

.login-link {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: #666;

  .link {
    color: #FF6B35;
    font-weight: 500;
    margin-left: 4px;
  }
}

.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 24px;
  font-size: 14px;
  color: #666;
}


</style>
