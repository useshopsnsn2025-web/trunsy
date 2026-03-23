<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-content">
        <view class="nav-left" @click="goBack">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="nav-title">{{ t('user.editProfileTitle') }}</text>
        <view class="nav-right" @click="showMoreMenu">
          <text class="bi bi-three-dots-vertical"></text>
        </view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-placeholder" :style="{ height: (statusBarHeight + 56) + 'px' }"></view>

    <scroll-view class="content" scroll-y>
      <!-- 头像和用户名区域 -->
      <view class="profile-header">
        <view class="avatar-section" @click="changeAvatar">
          <view class="avatar-wrap">
            <view v-if="formData.avatar" class="avatar-inner">
              <image class="avatar" :src="formData.avatar" mode="aspectFill" />
            </view>
            <view v-else class="avatar-inner avatar-default">
              <text class="avatar-letter">{{ getAvatarLetter() }}</text>
            </view>
            <view class="avatar-edit-icon">
              <text class="bi bi-pencil"></text>
            </view>
          </view>
        </view>
        <view class="user-info">
          <text class="username">{{ formData.nickname || 'User' }}</text>
          <text class="change-account" @click="goChangeAccount">{{ t('user.changeAccount') }}</text>
        </view>
      </view>

      <!-- 关于区域 -->
      <view class="bio-section">
        <text class="section-title">{{ t('user.aboutTab') }}</text>
        <text class="section-desc">{{ t('user.bioPlaceholder') }}</text>
        <view class="bio-input-wrap">
          <textarea
            v-model="formData.bio"
            class="bio-input"
            :placeholder="''"
            :maxlength="500"
          />
          <text class="bio-count">{{ formData.bio?.length || 0 }}/500</text>
        </view>
      </view>

      <!-- 按钮区域 -->
      <view class="button-section">
        <button
          class="save-btn"
          :class="{ 'disabled': !hasChanges }"
          :disabled="!hasChanges || isSaving"
          @click="saveProfile"
        >
          <text v-if="isSaving" class="bi bi-arrow-repeat spinning"></text>
          <text v-else>{{ t('common.save') }}</text>
        </button>
        <button class="cancel-btn" @click="goBack">
          <text>{{ t('common.cancel') }}</text>
        </button>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { updateUserProfile } from '@/api/user'
import { uploadAvatar } from '@/api/upload'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

// 状态栏高度
const statusBarHeight = computed(() => appStore.statusBarHeight)

// 表单数据
const formData = reactive({
  nickname: '',
  avatar: '',
  bio: '',
})

// 原始数据（用于比较是否有变化）
const originalData = reactive({
  nickname: '',
  avatar: '',
  bio: '',
})

// 保存中状态
const isSaving = ref(false)

// 是否有变化
const hasChanges = computed(() => {
  return formData.nickname !== originalData.nickname ||
    formData.avatar !== originalData.avatar ||
    formData.bio !== originalData.bio
})

// 获取头像字母
function getAvatarLetter(): string {
  const nickname = formData.nickname || ''
  return nickname.charAt(0).toUpperCase() || 'U'
}

// 返回上一页
function goBack() {
  uni.navigateBack()
}

// 显示更多菜单
function showMoreMenu() {
  uni.showActionSheet({
    itemList: [t('common.reset')],
    success: (res) => {
      if (res.tapIndex === 0) {
        // 重置表单
        formData.nickname = originalData.nickname
        formData.avatar = originalData.avatar
        formData.bio = originalData.bio
      }
    },
  })
}

// 更换头像
function changeAvatar() {
  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      const tempPath = res.tempFilePaths[0]
      // 上传头像
      try {
        uni.showLoading({ title: t('common.uploading') })
        const result = await uploadAvatar(tempPath)
        uni.hideLoading()
        if (result.data?.url) {
          formData.avatar = result.data.url
          toast.success(t('common.uploadSuccess'))
        } else {
          toast.error(t('common.uploadFailed'))
        }
      } catch (e: any) {
        uni.hideLoading()
        toast.error(e.message || t('common.uploadFailed'))
      }
    },
  })
}

// 跳转更改账号
function goChangeAccount() {
  uni.navigateTo({ url: '/pages/user/profile/account' })
}

// 保存资料
async function saveProfile() {
  if (!hasChanges.value || isSaving.value) return

  isSaving.value = true
  try {
    await updateUserProfile({
      nickname: formData.nickname,
      avatar: formData.avatar,
      bio: formData.bio,
    })

    // 更新原始数据
    originalData.nickname = formData.nickname
    originalData.avatar = formData.avatar
    originalData.bio = formData.bio

    // 刷新用户信息
    await userStore.fetchUserInfo()

    toast.success(t('common.saveSuccess'))

    // 返回上一页
    setTimeout(() => {
      uni.navigateBack()
    }, 500)
  } catch (e: any) {
    toast.error(e.message || t('common.saveFailed'))
  } finally {
    isSaving.value = false
  }
}

// 初始化表单数据
function initFormData() {
  const userInfo = userStore.userInfo
  if (userInfo) {
    formData.nickname = userInfo.nickname || ''
    formData.avatar = userInfo.avatar || ''
    formData.bio = userInfo.bio || ''

    originalData.nickname = userInfo.nickname || ''
    originalData.avatar = userInfo.avatar || ''
    originalData.bio = userInfo.bio || ''
  }
}

onMounted(() => {
  appStore.initSystemInfo()
  initFormData()
})

onShow(() => {
  if (userStore.isLoggedIn) {
    initFormData()
  }
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - eBay 风格
// ==========================================

// 色彩系统
$color-primary: #FF6B35;        // eBay 蓝
$color-primary-light: #EBF0FF;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-disabled: #C4C4C4;

// 字体系统
$font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

// 字号
$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 20px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 圆角
$radius-sm: 8px;
$radius-md: 12px;
$radius-lg: 16px;
$radius-full: 9999px;

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// ==========================================
// 页面样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-surface;
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

// 头像和用户名区域
.profile-header {
  display: flex;
  align-items: center;
  padding: $spacing-xl $spacing-base;
  background-color: $color-surface;
}

.avatar-section {
  margin-right: $spacing-lg;
  cursor: pointer;
}

.avatar-wrap {
  position: relative;
  width: 100px;
  height: 100px;
}

.avatar-inner {
  width: 100%;
  height: 100%;
  border-radius: $radius-full;
  overflow: hidden;
  background-color: $color-primary;

  &.avatar-default {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.avatar {
  width: 100%;
  height: 100%;
}

.avatar-letter {
  font-size: 42px;
  font-weight: $font-weight-bold;
  color: #fff;
}

.avatar-edit-icon {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 32px;
  height: 32px;
  background-color: $color-surface;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);

  .bi {
    font-size: 14px;
    color: $color-text-primary;
  }
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
}

.username {
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  color: $color-text-primary;
}

.change-account {
  font-size: $font-size-sm;
  color: $color-primary;
  cursor: pointer;

  &:active {
    opacity: 0.7;
  }
}

// 关于区域
.bio-section {
  padding: $spacing-lg $spacing-base;
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
  margin-bottom: $spacing-base;
}

.bio-input-wrap {
  position: relative;
  border: 1px solid $color-border;
  border-radius: $radius-md;
  padding: $spacing-base;
}

.bio-input {
  width: 100%;
  height: 120px;
  font-size: $font-size-base;
  color: $color-text-primary;
  line-height: 1.5;
  background: transparent;
  border: none;
  resize: none;

  &::placeholder {
    color: $color-text-muted;
  }
}

.bio-count {
  position: absolute;
  bottom: $spacing-sm;
  right: $spacing-base;
  font-size: $font-size-sm;
  color: $color-text-muted;
}

// 按钮区域
.button-section {
  padding: $spacing-xl $spacing-base;
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.save-btn,
.cancel-btn {
  width: 100%;
  height: 48px;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-md;
  font-weight: $font-weight-medium;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s, background-color 0.2s;

  &::after {
    border: none;
  }
}

.save-btn {
  background-color: $color-primary;
  color: #fff;

  &.disabled {
    background-color: $color-disabled;
    cursor: not-allowed;
  }

  &:active:not(.disabled) {
    opacity: 0.8;
  }

  .spinning {
    animation: spin 1s linear infinite;
  }
}

.cancel-btn {
  background-color: transparent;
  color: $color-primary;
  border: 1px solid $color-primary;

  &:active {
    background-color: $color-primary-light;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

// 底部安全区域
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// 响应式
@media (prefers-reduced-motion: reduce) {
  .save-btn,
  .cancel-btn {
    transition: none;
  }

  .spinning {
    animation: none;
  }
}
</style>
