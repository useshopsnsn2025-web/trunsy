<template>
  <view class="page">
    <NavBar :title="t('settings.translation')" />

    <scroll-view class="content" scroll-y>
      <!-- 当前语言 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('translation.currentLanguage') }}</text>
        </view>
        <view class="section-card">
          <view class="current-language">
            <text class="language-flag">{{ currentLanguageFlag }}</text>
            <view class="language-info">
              <text class="language-name">{{ currentLanguageName }}</text>
              <text class="language-code">{{ appStore.locale }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 选择语言 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('translation.selectLanguage') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="lang in supportedLanguages"
            :key="lang.code"
            class="language-item"
            :class="{ active: appStore.locale === lang.code }"
            @click="selectLanguage(lang)"
          >
            <view class="language-left">
              <text class="language-flag">{{ lang.flag }}</text>
              <view class="language-text">
                <text class="language-native">{{ lang.nativeName }}</text>
                <text class="language-english">{{ lang.name }}</text>
              </view>
            </view>
            <text
              v-if="appStore.locale === lang.code"
              class="bi bi-check-circle-fill language-check"
            ></text>
          </view>
        </view>
      </view>

      <!-- 说明 -->
      <view class="section">
        <view class="section-card info-card">
          <text class="bi bi-info-circle info-icon"></text>
          <text class="info-text">{{ t('translation.hint') }}</text>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 加载中遮罩 -->
    <view v-if="switching" class="loading-overlay">
      <view class="loading-content">
        <view class="loading-spinner"></view>
        <text class="loading-text">{{ t('translation.switching') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store/modules/user'
import { useToast } from '@/composables/useToast'
import { updateLanguage } from '@/api/user'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()
const toast = useToast()

// 状态
const switching = ref(false)

// 支持的语言列表
const supportedLanguages = computed(() => appStore.supportedLanguages)

// 当前语言名称
const currentLanguageName = computed(() => {
  const lang = supportedLanguages.value.find((l) => l.code === appStore.locale)
  return lang?.nativeName || appStore.locale
})

// 当前语言国旗
const currentLanguageFlag = computed(() => {
  const lang = supportedLanguages.value.find((l) => l.code === appStore.locale)
  return lang?.flag || '🌐'
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.translation') })
})

onMounted(() => {
  // 确保语言列表已加载
  if (supportedLanguages.value.length === 0) {
    appStore.fetchAvailableLanguages()
  }
})

// 选择语言
async function selectLanguage(lang: { code: string; nativeName: string }) {
  if (lang.code === appStore.locale) return
  if (switching.value) return

  switching.value = true

  try {
    // 切换本地语言
    await appStore.setLocale(lang.code)

    // 如果已登录，同步到服务器
    if (userStore.isLoggedIn) {
      try {
        await updateLanguage(lang.code)
      } catch (e) {
        // 服务器同步失败不影响本地切换
        console.warn('Failed to sync language to server:', e)
      }
    }

    // 保存到本地存储
    uni.setStorageSync('locale', lang.code)

    toast.success(t('translation.switched'))
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    switching.value = false
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

// 当前语言
.current-language {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
}

.language-flag {
  font-size: 32px;
  line-height: 1;
}

.language-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.language-name {
  font-size: 18px;
  font-weight: 600;
  color: $color-text;
}

.language-code {
  font-size: 13px;
  color: $color-text-muted;
  text-transform: lowercase;
}

// 语言选项
.language-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }

  &.active {
    .language-native {
      color: $color-primary;
      font-weight: 500;
    }
  }
}

.language-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.language-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.language-native {
  font-size: 15px;
  color: $color-text;
}

.language-english {
  font-size: 12px;
  color: $color-text-muted;
}

.language-check {
  font-size: 20px;
  color: $color-primary;
}

// 信息卡片
.info-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
}

.info-icon {
  font-size: 18px;
  color: $color-primary;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-text {
  font-size: 13px;
  color: $color-text-secondary;
  line-height: 1.5;
}

// 加载遮罩
.loading-overlay {
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
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 32px 48px;
  background-color: $color-surface;
  border-radius: 16px;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid $color-border;
  border-top-color: $color-primary;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  font-size: 14px;
  color: $color-text-secondary;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
