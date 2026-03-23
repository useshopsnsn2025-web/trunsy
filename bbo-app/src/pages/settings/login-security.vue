<template>
  <view class="page">
    <NavBar :title="t('settings.loginSecurity')" />

    <LoadingPage v-if="loading" />

    <scroll-view v-else class="content" scroll-y>
      <!-- 账户信息 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('security.accountInfo') }}</text>
        </view>
        <view class="section-card">
          <view class="info-item">
            <text class="info-label">{{ t('security.email') }}</text>
            <text class="info-value">{{ userEmail }}</text>
          </view>
          <view class="info-item">
            <text class="info-label">{{ t('security.registeredAt') }}</text>
            <text class="info-value">{{ registeredAt }}</text>
          </view>
        </view>
      </view>

      <!-- 密码安全 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('security.passwordSecurity') }}</text>
        </view>
        <view class="section-card">
          <view class="menu-item" @click="showChangePassword = true">
            <view class="menu-left">
              <text class="bi bi-key menu-icon"></text>
              <view class="menu-text">
                <text class="menu-title">{{ t('security.changePassword') }}</text>
                <text class="menu-desc">{{ t('security.changePasswordDesc') }}</text>
              </view>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 登录活动 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('security.loginActivity') }}</text>
        </view>
        <view class="section-card">
          <view class="activity-item current">
            <view class="activity-left">
              <view class="device-icon-wrapper">
                <text class="bi bi-phone device-icon"></text>
              </view>
              <view class="activity-info">
                <text class="activity-device">{{ t('security.currentDevice') }}</text>
                <text class="activity-detail">{{ currentDeviceInfo }}</text>
              </view>
            </view>
            <view class="current-badge">
              <text>{{ t('security.current') }}</text>
            </view>
          </view>

          <view v-if="lastLoginInfo" class="activity-item">
            <view class="activity-left">
              <view class="device-icon-wrapper">
                <text class="bi bi-clock-history device-icon"></text>
              </view>
              <view class="activity-info">
                <text class="activity-device">{{ t('security.lastLogin') }}</text>
                <text class="activity-detail">{{ lastLoginInfo }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="security-tips">
        <text class="bi bi-shield-check tips-icon"></text>
        <view class="tips-content">
          <text class="tips-title">{{ t('security.tipsTitle') }}</text>
          <text class="tips-text">{{ t('security.tipsContent') }}</text>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 修改密码弹窗 -->
    <view v-if="showChangePassword" class="modal-overlay" @click="closePasswordModal">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('security.changePassword') }}</text>
          <text class="bi bi-x modal-close" @click="closePasswordModal"></text>
        </view>

        <view class="modal-body">
          <!-- 当前密码 -->
          <view class="input-group">
            <text class="input-label">{{ t('security.currentPassword') }}</text>
            <view class="input-wrapper">
              <input
                class="input"
                :type="showCurrentPassword ? 'text' : 'password'"
                v-model="passwordForm.currentPassword"
                :placeholder="t('security.currentPasswordPlaceholder')"
              />
              <view class="toggle-password" @click="showCurrentPassword = !showCurrentPassword">
                <text :class="['bi', showCurrentPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
              </view>
            </view>
          </view>

          <!-- 新密码 -->
          <view class="input-group">
            <text class="input-label">{{ t('security.newPassword') }}</text>
            <view class="input-wrapper">
              <input
                class="input"
                :type="showNewPassword ? 'text' : 'password'"
                v-model="passwordForm.newPassword"
                :placeholder="t('security.newPasswordPlaceholder')"
              />
              <view class="toggle-password" @click="showNewPassword = !showNewPassword">
                <text :class="['bi', showNewPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
              </view>
            </view>
          </view>

          <!-- 确认新密码 -->
          <view class="input-group">
            <text class="input-label">{{ t('security.confirmNewPassword') }}</text>
            <view class="input-wrapper">
              <input
                class="input"
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="passwordForm.confirmPassword"
                :placeholder="t('security.confirmNewPasswordPlaceholder')"
              />
              <view class="toggle-password" @click="showConfirmPassword = !showConfirmPassword">
                <text :class="['bi', showConfirmPassword ? 'bi-eye-slash' : 'bi-eye']"></text>
              </view>
            </view>
          </view>

          <!-- 密码强度指示 -->
          <view class="password-strength">
            <view class="strength-bars">
              <view
                v-for="i in 4"
                :key="i"
                :class="['strength-bar', { active: passwordStrength >= i }]"
              />
            </view>
            <text class="strength-text">{{ passwordStrengthText }}</text>
          </view>

          <!-- 密码匹配提示 -->
          <view v-if="passwordForm.confirmPassword.length > 0" class="password-match-hint">
            <text class="bi" :class="passwordsMatch ? 'bi-check-circle-fill match' : 'bi-x-circle-fill mismatch'"></text>
            <text :class="passwordsMatch ? 'match' : 'mismatch'">{{ passwordsMatch ? t('auth.passwordsMatch') : t('auth.passwordsNotMatch') }}</text>
          </view>
        </view>

        <view class="modal-footer">
          <button class="btn-cancel" @click="closePasswordModal">
            {{ t('common.cancel') }}
          </button>
          <button
            class="btn-confirm"
            :loading="submitting"
            :disabled="!canSubmitPassword || submitting"
            @click="handleChangePassword"
          >
            {{ t('common.confirm') }}
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useToast } from '@/composables/useToast'
import { changePassword } from '@/api/user'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const userStore = useUserStore()
const toast = useToast()

// 状态
const loading = ref(true)
const showChangePassword = ref(false)
const submitting = ref(false)

// 密码可见性
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// 密码表单
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

// 用户信息
const userEmail = computed(() => {
  const email = userStore.userInfo?.email || ''
  if (!email) return '-'
  // 部分遮蔽邮箱
  const [name, domain] = email.split('@')
  if (name.length <= 2) return email
  return `${name.substring(0, 2)}***@${domain}`
})

const registeredAt = computed(() => {
  const date = userStore.userInfo?.createdAt
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
})

// 当前设备信息
const currentDeviceInfo = computed(() => {
  const platform = uni.getSystemInfoSync()
  return `${platform.platform} · ${platform.system}`
})

// 上次登录信息
const lastLoginInfo = computed(() => {
  const userInfo = userStore.userInfo
  if (!userInfo?.lastLoginAt) return null
  const date = new Date(userInfo.lastLoginAt)
  return date.toLocaleString()
})

// 密码强度计算（与注册页一致）
const passwordStrength = computed(() => {
  const pwd = passwordForm.newPassword
  if (!pwd) return 0
  let strength = 0
  if (pwd.length >= 8) strength++
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++
  if (/\d/.test(pwd)) strength++
  if (/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) strength++
  return strength
})

// 密码强度文本（与注册页一致）
const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value
  if (strength === 0) return ''
  if (strength === 1) return t('auth.passwordWeak')
  if (strength === 2) return t('auth.passwordFair')
  if (strength === 3) return t('auth.passwordGood')
  return t('auth.passwordStrong')
})

// 密码匹配检查
const passwordsMatch = computed(() => {
  return passwordForm.newPassword.length > 0 &&
    passwordForm.newPassword === passwordForm.confirmPassword
})

// 是否可以提交密码修改
const canSubmitPassword = computed(() => {
  return passwordForm.currentPassword.length > 0 &&
    passwordStrength.value >= 1 &&
    passwordsMatch.value
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.loginSecurity') })
})

onMounted(() => {
  setTimeout(() => {
    loading.value = false
  }, 300)
})

// 关闭密码弹窗
function closePasswordModal() {
  showChangePassword.value = false
  // 重置表单
  passwordForm.currentPassword = ''
  passwordForm.newPassword = ''
  passwordForm.confirmPassword = ''
  showCurrentPassword.value = false
  showNewPassword.value = false
  showConfirmPassword.value = false
}

// 修改密码
async function handleChangePassword() {
  if (!canSubmitPassword.value || submitting.value) return

  submitting.value = true
  try {
    const res = await changePassword({
      currentPassword: passwordForm.currentPassword,
      newPassword: passwordForm.newPassword,
      confirmPassword: passwordForm.confirmPassword,
    })

    if (res.code === 0) {
      toast.success(t('security.passwordChanged'))
      closePasswordModal()
    } else {
      toast.error(res.message || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #FF6B35;
$color-text: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #52c41a;

.page {
  min-height: 100vh;
  background-color: $color-background;
}

.content {
  flex: 1;
  padding: 16px;
  width: auto;
}

// 区块
.section {
  margin-bottom: 20px;
}

.section-header {
  padding: 8px 4px 12px;
}

.section-title {
  font-size: 12px;
  font-weight: 600;
  color: $color-text-secondary;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-card {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

// 账户信息
.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }
}

.info-label {
  font-size: 15px;
  color: $color-text;
}

.info-value {
  font-size: 15px;
  color: $color-text-secondary;
}

// 菜单项
.menu-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.menu-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.menu-icon {
  font-size: 20px;
  color: $color-primary;
}

.menu-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.menu-title {
  font-size: 15px;
  color: $color-text;
}

.menu-desc {
  font-size: 12px;
  color: $color-text-muted;
}

.menu-arrow {
  font-size: 12px;
  color: $color-text-muted;
}

// 登录活动
.activity-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }

  &.current {
    background-color: rgba($color-primary, 0.02);
  }
}

.activity-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.device-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background-color: $color-background;
  display: flex;
  align-items: center;
  justify-content: center;
}

.device-icon {
  font-size: 18px;
  color: $color-text-secondary;
}

.activity-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.activity-device {
  font-size: 15px;
  color: $color-text;
}

.activity-detail {
  font-size: 12px;
  color: $color-text-muted;
}

.current-badge {
  padding: 4px 10px;
  background-color: rgba($color-success, 0.1);
  border-radius: 12px;

  text {
    font-size: 12px;
    color: $color-success;
    font-weight: 500;
  }
}

// 安全提示
.security-tips {
  display: flex;
  gap: 12px;
  padding: 16px;
  background-color: rgba($color-primary, 0.05);
  border-radius: 12px;
  margin-top: 8px;
}

.tips-icon {
  font-size: 20px;
  color: $color-primary;
  flex-shrink: 0;
}

.tips-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tips-title {
  font-size: 14px;
  font-weight: 500;
  color: $color-text;
}

.tips-text {
  font-size: 12px;
  color: $color-text-secondary;
  line-height: 1.5;
}

// 弹窗
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  width: 100%;
  max-width: 400px;
  background-color: $color-surface;
  border-radius: 16px;
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid $color-border;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-text;
}

.modal-close {
  font-size: 24px;
  color: $color-text-muted;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 16px 20px 20px;
}

// 表单
.input-group {
  margin-bottom: 16px;
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
  padding: 0 48px 0 16px;
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

// 密码强度指示器
.password-strength {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
}

.strength-bars {
  display: flex;
  gap: 4px;
}

.strength-bar {
  width: 40px;
  height: 4px;
  background-color: $color-border;
  border-radius: 2px;
  transition: background-color 0.2s;

  &.active {
    &:nth-child(1) { background-color: #ff4d4f; } // 弱
    &:nth-child(2) { background-color: #faad14; } // 一般
    &:nth-child(3) { background-color: #52c41a; } // 好
    &:nth-child(4) { background-color: #1890ff; } // 强
  }
}

.strength-text {
  font-size: 12px;
  color: $color-text-secondary;
}

// 密码匹配提示
.password-match-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 12px;

  .bi {
    font-size: 14px;
  }

  .match {
    color: $color-success;
  }

  .mismatch {
    color: #ff4d4f;
  }
}

// 按钮
.btn-cancel,
.btn-confirm {
  flex: 1;
  height: 44px;
  border-radius: 22px;
  font-size: 15px;
  font-weight: 500;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  

  &::after {
    border: none;
  }
}

.btn-cancel {
  background-color: $color-background !important;
  color: $color-text !important;
}

.btn-confirm {
  background-color: $color-primary !important;
  color: #fff !important;

  &[disabled] {
    background-color: #ccc !important;
  }
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
