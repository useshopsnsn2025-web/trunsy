<template>
  <view class="page">
    <NavBar :title="t('settings.email')" />

    <LoadingPage v-if="loading" />

    <scroll-view v-else class="content" scroll-y>
      <!-- 当前邮箱 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('email.currentEmail') }}</text>
        </view>
        <view class="section-card">
          <view class="email-info">
            <view class="email-left">
              <text class="bi bi-envelope email-icon"></text>
              <view class="email-text">
                <text class="email-address">{{ userEmail }}</text>
                <text v-if="isVerified" class="email-status verified">
                  <text class="bi bi-check-circle-fill"></text>
                  {{ t('email.verified') }}
                </text>
                <text v-else class="email-status unverified">
                  <text class="bi bi-exclamation-circle-fill"></text>
                  {{ t('email.unverified') }}
                </text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 邮件订阅 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('email.subscriptions') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('email.orderUpdates') }}</text>
                <text class="setting-desc">{{ t('email.orderUpdatesDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="emailSettings.orderUpdates"
              color="#FF6B35"
              @change="(e: any) => updateSetting('orderUpdates', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('email.promotions') }}</text>
                <text class="setting-desc">{{ t('email.promotionsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="emailSettings.promotions"
              color="#FF6B35"
              @change="(e: any) => updateSetting('promotions', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('email.newsletter') }}</text>
                <text class="setting-desc">{{ t('email.newsletterDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="emailSettings.newsletter"
              color="#FF6B35"
              @change="(e: any) => updateSetting('newsletter', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('email.productRecommendations') }}</text>
                <text class="setting-desc">{{ t('email.productRecommendationsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="emailSettings.productRecommendations"
              color="#FF6B35"
              @change="(e: any) => updateSetting('productRecommendations', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 邮件频率 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('email.frequency') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="option in frequencyOptions"
            :key="option.value"
            class="frequency-item"
            :class="{ active: emailSettings.frequency === option.value }"
            @click="updateSetting('frequency', option.value)"
          >
            <view class="frequency-left">
              <text class="frequency-title">{{ option.label }}</text>
              <text class="frequency-desc">{{ option.desc }}</text>
            </view>
            <text
              v-if="emailSettings.frequency === option.value"
              class="bi bi-check-circle-fill frequency-check"
            ></text>
          </view>
        </view>
      </view>

      <!-- 取消订阅所有 -->
      <view class="section">
        <view class="section-card">
          <view class="unsubscribe-item" @click="handleUnsubscribeAll">
            <text class="unsubscribe-text">{{ t('email.unsubscribeAll') }}</text>
            <text class="bi bi-chevron-right unsubscribe-arrow"></text>
          </view>
        </view>
        <text class="section-hint">{{ t('email.unsubscribeHint') }}</text>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 取消订阅确认弹窗 -->
    <view v-if="showUnsubscribeDialog" class="modal-overlay" @click="showUnsubscribeDialog = false">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('email.unsubscribeAll') }}</text>
        </view>
        <view class="modal-body">
          <text class="modal-message">{{ t('email.unsubscribeConfirm') }}</text>
        </view>
        <view class="modal-footer">
          <button class="btn-cancel" @click="showUnsubscribeDialog = false">
            {{ t('common.cancel') }}
          </button>
          <button class="btn-confirm" @click="confirmUnsubscribeAll">
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
import { getEmailSettings, updateEmailSettings } from '@/api/user'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const userStore = useUserStore()
const toast = useToast()

// 状态
const loading = ref(true)
const saving = ref(false)
const showUnsubscribeDialog = ref(false)

// 用户邮箱
const userEmail = computed(() => {
  const email = userStore.userInfo?.email || ''
  if (!email) return t('email.noEmail')
  return email
})

// 是否已验证（暂时默认为 true）
const isVerified = computed(() => true)

// 邮件设置
const emailSettings = reactive({
  orderUpdates: true,
  promotions: true,
  newsletter: false,
  productRecommendations: true,
  frequency: 'daily' as 'realtime' | 'daily' | 'weekly',
})

// 频率选项
const frequencyOptions = computed(() => [
  {
    value: 'realtime',
    label: t('email.frequencyRealtime'),
    desc: t('email.frequencyRealtimeDesc'),
  },
  {
    value: 'daily',
    label: t('email.frequencyDaily'),
    desc: t('email.frequencyDailyDesc'),
  },
  {
    value: 'weekly',
    label: t('email.frequencyWeekly'),
    desc: t('email.frequencyWeeklyDesc'),
  },
])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.email') })
})

onMounted(async () => {
  await loadSettings()
})

// 加载设置
async function loadSettings() {
  loading.value = true
  try {
    const res = await getEmailSettings()
    if (res.code === 0 && res.data) {
      Object.assign(emailSettings, res.data)
    }
  } catch (e) {
    // 使用默认设置
  } finally {
    loading.value = false
  }
}

// 更新单个设置
function updateSetting(key: keyof typeof emailSettings, value: any) {
  (emailSettings as any)[key] = value
  saveSettings()
}

// 保存设置
async function saveSettings() {
  if (saving.value) return
  saving.value = true

  try {
    const res = await updateEmailSettings(emailSettings)
    if (res.code !== 0) {
      toast.error(res.message || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    saving.value = false
  }
}

// 取消订阅所有
function handleUnsubscribeAll() {
  showUnsubscribeDialog.value = true
}

// 确认取消订阅
async function confirmUnsubscribeAll() {
  emailSettings.orderUpdates = false
  emailSettings.promotions = false
  emailSettings.newsletter = false
  emailSettings.productRecommendations = false
  await saveSettings()
  showUnsubscribeDialog.value = false
  toast.success(t('email.unsubscribed'))
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
$color-warning: #faad14;
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

// 当前邮箱
.email-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
}

.email-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.email-icon {
  font-size: 24px;
  color: $color-primary;
}

.email-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.email-address {
  font-size: 15px;
  color: $color-text;
  font-weight: 500;
}

.email-status {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;

  .bi {
    font-size: 12px;
  }

  &.verified {
    color: $color-success;
  }

  &.unverified {
    color: $color-warning;
  }
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

// 频率选项
.frequency-item {
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
    .frequency-title {
      color: $color-primary;
    }
  }
}

.frequency-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.frequency-title {
  font-size: 15px;
  color: $color-text;
}

.frequency-desc {
  font-size: 12px;
  color: $color-text-muted;
}

.frequency-check {
  font-size: 20px;
  color: $color-primary;
}

// 取消订阅
.unsubscribe-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.unsubscribe-text {
  font-size: 15px;
  color: $color-danger;
}

.unsubscribe-arrow {
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
