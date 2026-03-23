<template>
  <view class="page">
    <NavBar :title="t('settings.privacyChoices')" />

    <scroll-view class="content" scroll-y>
      <!-- 说明 -->
      <view class="section">
        <view class="section-card intro-card">
          <text class="bi bi-shield-lock intro-icon"></text>
          <text class="intro-text">{{ t('privacyChoices.introText') }}</text>
        </view>
      </view>

      <!-- 数据使用 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('privacyChoices.dataUsage') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.essentialData') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.essentialDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="true"
              :disabled="true"
              color="#FF6B35"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.functionalData') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.functionalDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.functionalData"
              color="#FF6B35"
              @change="(e: any) => updateSetting('functionalData', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.analyticsData') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.analyticsDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.analyticsData"
              color="#FF6B35"
              @change="(e: any) => updateSetting('analyticsData', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.marketingData') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.marketingDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.marketingData"
              color="#FF6B35"
              @change="(e: any) => updateSetting('marketingData', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 数据共享 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('privacyChoices.dataSharing') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.shareWithSellers') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.shareWithSellersDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.shareWithSellers"
              color="#FF6B35"
              @change="(e: any) => updateSetting('shareWithSellers', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('privacyChoices.shareForResearch') }}</text>
                <text class="setting-desc">{{ t('privacyChoices.shareForResearchDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.shareForResearch"
              color="#FF6B35"
              @change="(e: any) => updateSetting('shareForResearch', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 数据管理 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('privacyChoices.dataManagement') }}</text>
        </view>
        <view class="section-card">
          <view class="action-item" @click="handleDownloadData">
            <view class="action-left">
              <text class="bi bi-download action-icon"></text>
              <view class="action-text">
                <text class="action-title">{{ t('privacyChoices.downloadData') }}</text>
                <text class="action-desc">{{ t('privacyChoices.downloadDataDesc') }}</text>
              </view>
            </view>
            <text class="bi bi-chevron-right action-arrow"></text>
          </view>
          <view class="action-item" @click="handleDeleteAccount">
            <view class="action-left">
              <text class="bi bi-trash action-icon danger"></text>
              <view class="action-text">
                <text class="action-title danger">{{ t('privacyChoices.deleteAccount') }}</text>
                <text class="action-desc">{{ t('privacyChoices.deleteAccountDesc') }}</text>
              </view>
            </view>
            <text class="bi bi-chevron-right action-arrow"></text>
          </view>
        </view>
      </view>

      <!-- 了解更多 -->
      <view class="section">
        <view class="section-card">
          <view class="link-item" @click="goPrivacyPolicy">
            <text class="link-text">{{ t('privacyChoices.viewPrivacyPolicy') }}</text>
            <text class="bi bi-chevron-right link-arrow"></text>
          </view>
        </view>
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
const STORAGE_KEY = 'privacy_choices_settings'

// 隐私设置
const settings = reactive({
  functionalData: true,
  analyticsData: true,
  marketingData: false,
  shareWithSellers: true,
  shareForResearch: false,
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.privacyChoices') })
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
    console.error('Failed to save privacy settings:', e)
  }
}

// 下载数据
function handleDownloadData() {
  toast.info(t('common.comingSoon'))
}

// 删除账户
function handleDeleteAccount() {
  toast.info(t('common.comingSoon'))
}

// 查看隐私政策
function goPrivacyPolicy() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=privacy-policy' })
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
$color-danger: #FF6B35;

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
  border-bottom: 1px solid $color-border;
  transition: background-color 0.2s;

  &:last-child {
    border-bottom: none;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.action-left {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
}

.action-icon {
  font-size: 20px;
  color: $color-primary;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
  margin-top: 2px;

  &.danger {
    color: $color-danger;
  }
}

.action-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.action-title {
  font-size: 15px;
  color: $color-text;

  &.danger {
    color: $color-danger;
  }
}

.action-desc {
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
}

.action-arrow {
  font-size: 12px;
  color: $color-text-muted;
}

// 链接项
.link-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.link-text {
  font-size: 15px;
  color: $color-primary;
}

.link-arrow {
  font-size: 12px;
  color: $color-text-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
