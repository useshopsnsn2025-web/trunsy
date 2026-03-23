<template>
  <view class="page">
    <NavBar :title="t('settings.adPreferences')" />

    <scroll-view class="content" scroll-y>
      <!-- 说明 -->
      <view class="section">
        <view class="section-card intro-card">
          <text class="bi bi-megaphone intro-icon"></text>
          <text class="intro-text">{{ t('adPreferences.introText') }}</text>
        </view>
      </view>

      <!-- 广告个性化 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('adPreferences.personalization') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('adPreferences.personalizedAds') }}</text>
                <text class="setting-desc">{{ t('adPreferences.personalizedAdsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.personalizedAds"
              color="#FF6B35"
              @change="(e: any) => updateSetting('personalizedAds', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('adPreferences.browsingHistory') }}</text>
                <text class="setting-desc">{{ t('adPreferences.browsingHistoryDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.browsingHistory"
              color="#FF6B35"
              @change="(e: any) => updateSetting('browsingHistory', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('adPreferences.purchaseHistory') }}</text>
                <text class="setting-desc">{{ t('adPreferences.purchaseHistoryDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.purchaseHistory"
              color="#FF6B35"
              @change="(e: any) => updateSetting('purchaseHistory', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 广告来源 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('adPreferences.adSources') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('adPreferences.inAppAds') }}</text>
                <text class="setting-desc">{{ t('adPreferences.inAppAdsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.inAppAds"
              color="#FF6B35"
              @change="(e: any) => updateSetting('inAppAds', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('adPreferences.partnerAds') }}</text>
                <text class="setting-desc">{{ t('adPreferences.partnerAdsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.partnerAds"
              color="#FF6B35"
              @change="(e: any) => updateSetting('partnerAds', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 兴趣类别 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('adPreferences.interests') }}</text>
        </view>
        <view class="section-card">
          <view class="interests-intro">
            <text class="interests-text">{{ t('adPreferences.interestsDesc') }}</text>
          </view>
          <view class="interests-list">
            <view
              v-for="interest in interests"
              :key="interest.key"
              class="interest-item"
              :class="{ active: settings.interests.includes(interest.key) }"
              @click="toggleInterest(interest.key)"
            >
              <text class="interest-name">{{ interest.label }}</text>
              <text
                v-if="settings.interests.includes(interest.key)"
                class="bi bi-check interest-check"
              ></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 重置 -->
      <view class="section">
        <view class="section-card">
          <view class="action-item" @click="handleResetPreferences">
            <text class="action-text">{{ t('adPreferences.resetPreferences') }}</text>
            <text class="bi bi-chevron-right action-arrow"></text>
          </view>
        </view>
        <text class="section-hint">{{ t('adPreferences.resetHint') }}</text>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { reactive, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

// 存储键
const STORAGE_KEY = 'ad_preferences_settings'

// 广告设置
const settings = reactive({
  personalizedAds: true,
  browsingHistory: true,
  purchaseHistory: true,
  inAppAds: true,
  partnerAds: false,
  interests: ['electronics', 'fashion'] as string[],
})

// 兴趣类别
const interests = computed(() => [
  { key: 'electronics', label: t('adPreferences.categoryElectronics') },
  { key: 'fashion', label: t('adPreferences.categoryFashion') },
  { key: 'home', label: t('adPreferences.categoryHome') },
  { key: 'sports', label: t('adPreferences.categorySports') },
  { key: 'beauty', label: t('adPreferences.categoryBeauty') },
  { key: 'toys', label: t('adPreferences.categoryToys') },
  { key: 'books', label: t('adPreferences.categoryBooks') },
  { key: 'automotive', label: t('adPreferences.categoryAutomotive') },
])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.adPreferences') })
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
function updateSetting(key: string, value: boolean) {
  (settings as any)[key] = value
  saveSettings()
}

// 切换兴趣
function toggleInterest(key: string) {
  const index = settings.interests.indexOf(key)
  if (index > -1) {
    settings.interests.splice(index, 1)
  } else {
    settings.interests.push(key)
  }
  saveSettings()
}

// 保存设置
function saveSettings() {
  try {
    uni.setStorageSync(STORAGE_KEY, JSON.stringify(settings))
  } catch (e) {
    console.error('Failed to save ad preferences:', e)
  }
}

// 重置偏好
function handleResetPreferences() {
  settings.personalizedAds = true
  settings.browsingHistory = true
  settings.purchaseHistory = true
  settings.inAppAds = true
  settings.partnerAds = false
  settings.interests = ['electronics', 'fashion']
  saveSettings()
  toast.success(t('adPreferences.resetSuccess'))
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

// 兴趣类别
.interests-intro {
  padding: 16px;
  border-bottom: 1px solid $color-border;
}

.interests-text {
  font-size: 13px;
  color: $color-text-secondary;
  line-height: 1.5;
}

.interests-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 16px;
}

.interest-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background-color: $color-background;
  border-radius: 20px;
  border: 1px solid $color-border;
  transition: all 0.2s;

  &.active {
    background-color: rgba($color-primary, 0.1);
    border-color: $color-primary;

    .interest-name {
      color: $color-primary;
    }
  }

  &:active {
    transform: scale(0.96);
  }
}

.interest-name {
  font-size: 13px;
  color: $color-text;
}

.interest-check {
  font-size: 14px;
  color: $color-primary;
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
