<template>
  <view class="page">
    <NavBar :title="t('settings.accessibility')" />

    <scroll-view class="content" scroll-y>
      <!-- 说明 -->
      <view class="section">
        <view class="section-card intro-card">
          <text class="bi bi-universal-access intro-icon"></text>
          <text class="intro-text">{{ t('accessibility.introText') }}</text>
        </view>
      </view>

      <!-- 视觉 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('accessibility.visual') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.largeText') }}</text>
                <text class="setting-desc">{{ t('accessibility.largeTextDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.largeText"
              color="#FF6B35"
              @change="(e: any) => updateSetting('largeText', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.boldText') }}</text>
                <text class="setting-desc">{{ t('accessibility.boldTextDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.boldText"
              color="#FF6B35"
              @change="(e: any) => updateSetting('boldText', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.highContrast') }}</text>
                <text class="setting-desc">{{ t('accessibility.highContrastDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.highContrast"
              color="#FF6B35"
              @change="(e: any) => updateSetting('highContrast', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 动效 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('accessibility.motion') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.reduceMotion') }}</text>
                <text class="setting-desc">{{ t('accessibility.reduceMotionDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.reduceMotion"
              color="#FF6B35"
              @change="(e: any) => updateSetting('reduceMotion', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.reduceTransparency') }}</text>
                <text class="setting-desc">{{ t('accessibility.reduceTransparencyDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.reduceTransparency"
              color="#FF6B35"
              @change="(e: any) => updateSetting('reduceTransparency', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 交互 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('accessibility.interaction') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.hapticFeedback') }}</text>
                <text class="setting-desc">{{ t('accessibility.hapticFeedbackDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.hapticFeedback"
              color="#FF6B35"
              @change="(e: any) => updateSetting('hapticFeedback', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.soundEffects') }}</text>
                <text class="setting-desc">{{ t('accessibility.soundEffectsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.soundEffects"
              color="#FF6B35"
              @change="(e: any) => updateSetting('soundEffects', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 阅读辅助 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('accessibility.reading') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('accessibility.screenReader') }}</text>
                <text class="setting-desc">{{ t('accessibility.screenReaderDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.screenReader"
              color="#FF6B35"
              @change="(e: any) => updateSetting('screenReader', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 重置 -->
      <view class="section">
        <view class="section-card">
          <view class="action-item" @click="handleResetSettings">
            <text class="action-text">{{ t('accessibility.resetSettings') }}</text>
            <text class="bi bi-chevron-right action-arrow"></text>
          </view>
        </view>
        <text class="section-hint">{{ t('accessibility.resetHint') }}</text>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

// 存储键
const STORAGE_KEY = 'accessibility_settings'

// 无障碍设置
const settings = reactive({
  largeText: false,
  boldText: false,
  highContrast: false,
  reduceMotion: false,
  reduceTransparency: false,
  hapticFeedback: true,
  soundEffects: true,
  screenReader: false,
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.accessibility') })
})

onMounted(() => {
  loadSettings()
})

// 加载设置
function loadSettings() {
  try {
    const saved = uni.getStorageSync(STORAGE_KEY)
    if (saved) {
      Object.assign(settings, JSON.parse(saved))
    }
  } catch (e) {
    // 使用默认设置
  }
}

// 更新设置
function updateSetting(key: keyof typeof settings, value: boolean) {
  settings[key] = value
  saveSettings()
}

// 保存设置
function saveSettings() {
  try {
    uni.setStorageSync(STORAGE_KEY, JSON.stringify(settings))
  } catch (e) {
    console.error('Failed to save accessibility settings:', e)
  }
}

// 重置设置
function handleResetSettings() {
  settings.largeText = false
  settings.boldText = false
  settings.highContrast = false
  settings.reduceMotion = false
  settings.reduceTransparency = false
  settings.hapticFeedback = true
  settings.soundEffects = true
  settings.screenReader = false
  saveSettings()
  toast.success(t('accessibility.resetSuccess'))
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

.section-hint {
  font-size: 12px;
  color: $color-text-muted;
  padding: 8px 4px 0;
  line-height: 1.5;
}

// 介绍卡片
.intro-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px 20px;
  text-align: center;
}

.intro-icon {
  font-size: 40px;
  color: $color-primary;
  margin-bottom: 12px;
}

.intro-text {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.6;
}

// 设置项
.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }
}

.setting-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  padding-right: 12px;
}

.setting-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.setting-title {
  font-size: 15px;
  color: $color-text;
}

.setting-desc {
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
}

// 操作项
.action-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.action-text {
  font-size: 15px;
  color: $color-primary;
}

.action-arrow {
  font-size: 12px;
  color: $color-text-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
