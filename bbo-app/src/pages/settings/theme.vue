<template>
  <view class="page">
    <NavBar :title="t('settings.theme')" />

    <scroll-view class="content" scroll-y>
      <!-- 外观模式 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('theme.appearance') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="option in themeOptions"
            :key="option.value"
            class="theme-item"
            :class="{ active: currentTheme === option.value }"
            @click="selectTheme(option.value)"
          >
            <view class="theme-preview" :class="option.value">
              <view class="preview-header"></view>
              <view class="preview-content">
                <view class="preview-card"></view>
                <view class="preview-card"></view>
              </view>
            </view>
            <view class="theme-info">
              <text class="theme-name">{{ option.label }}</text>
              <text
                v-if="currentTheme === option.value"
                class="bi bi-check-circle-fill theme-check"
              ></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 说明 -->
      <view class="section">
        <view class="section-card info-card">
          <text class="bi bi-info-circle info-icon"></text>
          <text class="info-text">{{ t('theme.systemHint') }}</text>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

// 当前主题
const currentTheme = ref<'light' | 'dark' | 'system'>('system')

// 存储键
const STORAGE_KEY = 'app_theme'

// 主题选项
const themeOptions = computed(() => [
  {
    value: 'light' as const,
    label: t('theme.light'),
  },
  {
    value: 'dark' as const,
    label: t('theme.dark'),
  },
  {
    value: 'system' as const,
    label: t('theme.system'),
  },
])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.theme') })
})

onMounted(() => {
  loadTheme()
})

// 加载主题设置
function loadTheme() {
  try {
    const saved = uni.getStorageSync(STORAGE_KEY)
    if (saved && ['light', 'dark', 'system'].includes(saved)) {
      currentTheme.value = saved
    }
  } catch (e) {
    // 使用默认设置
  }
}

// 选择主题
function selectTheme(theme: 'light' | 'dark' | 'system') {
  currentTheme.value = theme
  saveTheme(theme)
  applyTheme(theme)
  toast.success(t('theme.applied'))
}

// 保存主题设置
function saveTheme(theme: string) {
  try {
    uni.setStorageSync(STORAGE_KEY, theme)
  } catch (e) {
    console.error('Failed to save theme:', e)
  }
}

// 应用主题
function applyTheme(theme: 'light' | 'dark' | 'system') {
  // 获取实际主题
  let actualTheme = theme
  if (theme === 'system') {
    // 获取系统主题
    const systemInfo = uni.getSystemInfoSync()
    actualTheme = systemInfo.theme === 'dark' ? 'dark' : 'light'
  }

  // 设置导航栏颜色
  const navBarColor = actualTheme === 'dark' ? '#191919' : '#FFFFFF'
  const navBarTextStyle = actualTheme === 'dark' ? 'white' : 'black'

  uni.setNavigationBarColor({
    frontColor: navBarTextStyle === 'white' ? '#ffffff' : '#000000',
    backgroundColor: navBarColor,
  })

  // 注意：UniApp 目前不支持动态切换全局主题
  // 完整的暗色模式需要在 App.vue 中配置 CSS 变量
  // 这里只是保存用户偏好，完整实现需要额外工作
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

// 主题选项
.theme-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }

  &.active {
    .theme-name {
      color: $color-primary;
      font-weight: 500;
    }
  }
}

// 主题预览
.theme-preview {
  width: 100%;
  max-width: 200px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid $color-border;
  transition: border-color 0.2s;

  .active & {
    border-color: $color-primary;
  }

  // 浅色主题预览
  &.light {
    background-color: #F7F7F7;

    .preview-header {
      background-color: #FFFFFF;
    }

    .preview-card {
      background-color: #FFFFFF;
    }
  }

  // 深色主题预览
  &.dark {
    background-color: #1a1a1a;

    .preview-header {
      background-color: #2d2d2d;
    }

    .preview-card {
      background-color: #2d2d2d;
    }
  }

  // 跟随系统预览
  &.system {
    background: linear-gradient(135deg, #F7F7F7 50%, #1a1a1a 50%);

    .preview-header {
      background: linear-gradient(90deg, #FFFFFF 50%, #2d2d2d 50%);
    }

    .preview-card {
      background: linear-gradient(90deg, #FFFFFF 50%, #2d2d2d 50%);
    }
  }
}

.preview-header {
  height: 24px;
  margin: 8px 8px 0;
  border-radius: 4px;
}

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 8px;
}

.preview-card {
  height: 28px;
  border-radius: 4px;
}

// 主题信息
.theme-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
}

.theme-name {
  font-size: 15px;
  color: $color-text;
}

.theme-check {
  font-size: 18px;
  color: $color-primary;
}

// 信息卡片
.info-card {
  display: flex;
  align-items: flex-start;
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

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
