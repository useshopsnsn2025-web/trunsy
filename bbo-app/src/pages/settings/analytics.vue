<template>
  <view class="page">
    <NavBar :title="t('settings.metricsAnalytics')" />

    <scroll-view class="content" scroll-y>
      <!-- 说明区块 -->
      <view class="section">
        <view class="section-card intro-card">
          <text class="bi bi-bar-chart-line intro-icon"></text>
          <text class="intro-text">{{ t('analytics.introText') }}</text>
        </view>
      </view>

      <!-- 数据收集 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('analytics.dataCollection') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('analytics.usageData') }}</text>
                <text class="setting-desc">{{ t('analytics.usageDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.usageData"
              color="#FF6B35"
              @change="(e: any) => updateSetting('usageData', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('analytics.crashReports') }}</text>
                <text class="setting-desc">{{ t('analytics.crashReportsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.crashReports"
              color="#FF6B35"
              @change="(e: any) => updateSetting('crashReports', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('analytics.performanceData') }}</text>
                <text class="setting-desc">{{ t('analytics.performanceDataDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.performanceData"
              color="#FF6B35"
              @change="(e: any) => updateSetting('performanceData', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 个性化 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('analytics.personalization') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('analytics.personalizedAds') }}</text>
                <text class="setting-desc">{{ t('analytics.personalizedAdsDesc') }}</text>
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
                <text class="setting-title">{{ t('analytics.personalizedRecommendations') }}</text>
                <text class="setting-desc">{{ t('analytics.personalizedRecommendationsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.personalizedRecommendations"
              color="#FF6B35"
              @change="(e: any) => updateSetting('personalizedRecommendations', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 第三方分享 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('analytics.thirdPartySharing') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('analytics.shareWithPartners') }}</text>
                <text class="setting-desc">{{ t('analytics.shareWithPartnersDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.shareWithPartners"
              color="#FF6B35"
              @change="(e: any) => updateSetting('shareWithPartners', e.detail.value)"
            />
          </view>
        </view>
        <text class="section-hint">{{ t('analytics.thirdPartyHint') }}</text>
      </view>

      <!-- 重置数据 -->
      <view class="section">
        <view class="section-card">
          <view class="action-item" @click="handleResetData">
            <text class="action-text danger">{{ t('analytics.resetData') }}</text>
            <text class="bi bi-chevron-right action-arrow"></text>
          </view>
        </view>
        <text class="section-hint">{{ t('analytics.resetDataHint') }}</text>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 重置确认弹窗 -->
    <view v-if="showResetDialog" class="modal-overlay" @click="showResetDialog = false">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('analytics.resetData') }}</text>
        </view>
        <view class="modal-body">
          <text class="modal-message">{{ t('analytics.resetConfirm') }}</text>
        </view>
        <view class="modal-footer">
          <button class="btn-cancel" @click="showResetDialog = false">
            {{ t('common.cancel') }}
          </button>
          <button class="btn-confirm" @click="confirmReset">
            {{ t('common.confirm') }}
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

// 状态
const saving = ref(false)
const showResetDialog = ref(false)

// 分析设置（本地存储）
const settings = reactive({
  usageData: true,
  crashReports: true,
  performanceData: true,
  personalizedAds: true,
  personalizedRecommendations: true,
  shareWithPartners: false,
})

// 存储键
const STORAGE_KEY = 'analytics_settings'

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.metricsAnalytics') })
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

// 更新单个设置
function updateSetting(key: keyof typeof settings, value: boolean) {
  settings[key] = value
  saveSettings()
}

// 保存设置
function saveSettings() {
  if (saving.value) return
  saving.value = true

  try {
    uni.setStorageSync(STORAGE_KEY, JSON.stringify(settings))
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    saving.value = false
  }
}

// 重置数据
function handleResetData() {
  showResetDialog.value = true
}

// 确认重置
function confirmReset() {
  // 重置为默认设置
  settings.usageData = true
  settings.crashReports = true
  settings.performanceData = true
  settings.personalizedAds = true
  settings.personalizedRecommendations = true
  settings.shareWithPartners = false
  saveSettings()
  showResetDialog.value = false
  toast.success(t('analytics.resetSuccess'))
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
  color: $color-text;

  &.danger {
    color: $color-danger;
  }
}

.action-arrow {
  font-size: 12px;
  color: $color-text-muted;
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
  max-width: 320px;
  background-color: $color-surface;
  border-radius: 16px;
  overflow: hidden;
}

.modal-header {
  padding: 20px 20px 0;
  text-align: center;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-text;
}

.modal-body {
  padding: 16px 20px 20px;
  text-align: center;
}

.modal-message {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 0 20px 20px;
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
  background-color: $color-danger !important;
  color: #fff !important;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
