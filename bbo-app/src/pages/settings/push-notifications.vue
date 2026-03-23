<template>
  <view class="page">
    <NavBar :title="t('settings.pushNotifications')" />

    <LoadingPage v-if="loading" />

    <scroll-view v-else class="content" scroll-y>
      <!-- 主开关 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('notification.masterSwitch') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <text class="bi bi-bell setting-icon"></text>
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.enableNotifications') }}</text>
                <text class="setting-desc">{{ t('notification.enableNotificationsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.enableAll"
              color="#FF6B35"
              @change="onMasterSwitchChange"
            />
          </view>
        </view>
      </view>

      <!-- 订单通知 -->
      <view class="section" :class="{ disabled: !settings.enableAll }">
        <view class="section-header">
          <text class="section-title">{{ t('notification.orderNotifications') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.orderStatus') }}</text>
                <text class="setting-desc">{{ t('notification.orderStatusDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.orderStatus"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('orderStatus', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.orderShipped') }}</text>
                <text class="setting-desc">{{ t('notification.orderShippedDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.orderShipped"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('orderShipped', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.orderDelivered') }}</text>
                <text class="setting-desc">{{ t('notification.orderDeliveredDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.orderDelivered"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('orderDelivered', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 促销通知 -->
      <view class="section" :class="{ disabled: !settings.enableAll }">
        <view class="section-header">
          <text class="section-title">{{ t('notification.promotionNotifications') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.deals') }}</text>
                <text class="setting-desc">{{ t('notification.dealsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.deals"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('deals', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.priceDrops') }}</text>
                <text class="setting-desc">{{ t('notification.priceDropsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.priceDrops"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('priceDrops', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.coupons') }}</text>
                <text class="setting-desc">{{ t('notification.couponsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.coupons"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('coupons', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 社交通知 -->
      <view class="section" :class="{ disabled: !settings.enableAll }">
        <view class="section-header">
          <text class="section-title">{{ t('notification.socialNotifications') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.messages') }}</text>
                <text class="setting-desc">{{ t('notification.messagesDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.messages"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('messages', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.followers') }}</text>
                <text class="setting-desc">{{ t('notification.followersDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.followers"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('followers', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.likes') }}</text>
                <text class="setting-desc">{{ t('notification.likesDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.likes"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('likes', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 卖家通知 (仅卖家显示) -->
      <view v-if="isSeller" class="section" :class="{ disabled: !settings.enableAll }">
        <view class="section-header">
          <text class="section-title">{{ t('notification.sellerNotifications') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.newOrders') }}</text>
                <text class="setting-desc">{{ t('notification.newOrdersDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.newOrders"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('newOrders', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.reviews') }}</text>
                <text class="setting-desc">{{ t('notification.reviewsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.reviews"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('reviews', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.lowStock') }}</text>
                <text class="setting-desc">{{ t('notification.lowStockDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.lowStock"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('lowStock', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <!-- 邮件通知 -->
      <view class="section" :class="{ disabled: !settings.enableAll }">
        <view class="section-header">
          <text class="section-title">{{ t('notification.emailNotifications') }}</text>
        </view>
        <view class="section-card">
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.emailDigest') }}</text>
                <text class="setting-desc">{{ t('notification.emailDigestDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.emailDigest"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('emailDigest', e.detail.value)"
            />
          </view>
          <view class="setting-item">
            <view class="setting-left">
              <view class="setting-text">
                <text class="setting-title">{{ t('notification.marketingEmails') }}</text>
                <text class="setting-desc">{{ t('notification.marketingEmailsDesc') }}</text>
              </view>
            </view>
            <switch
              :checked="settings.marketingEmails"
              :disabled="!settings.enableAll"
              color="#FF6B35"
              @change="(e: any) => updateSetting('marketingEmails', e.detail.value)"
            />
          </view>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useToast } from '@/composables/useToast'
import { getNotificationSettings, updateNotificationSettings } from '@/api/user'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const userStore = useUserStore()
const toast = useToast()

// 状态
const loading = ref(true)
const saving = ref(false)

// 是否为卖家
const isSeller = computed(() => userStore.userInfo?.isSeller ?? false)

// 通知设置
const settings = reactive({
  enableAll: true,
  // 订单通知
  orderStatus: true,
  orderShipped: true,
  orderDelivered: true,
  // 促销通知
  deals: true,
  priceDrops: true,
  coupons: true,
  // 社交通知
  messages: true,
  followers: true,
  likes: true,
  // 卖家通知
  newOrders: true,
  reviews: true,
  lowStock: true,
  // 邮件通知
  emailDigest: false,
  marketingEmails: false,
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('settings.pushNotifications') })
})

onMounted(async () => {
  await loadSettings()
})

// 加载设置
async function loadSettings() {
  loading.value = true
  try {
    const res = await getNotificationSettings()
    if (res.code === 0 && res.data) {
      Object.assign(settings, res.data)
    }
  } catch (e) {
    // 使用默认设置
  } finally {
    loading.value = false
  }
}

// 主开关变更
function onMasterSwitchChange(e: any) {
  settings.enableAll = e.detail.value
  saveSettings()
}

// 更新单个设置
function updateSetting(key: keyof typeof settings, value: boolean) {
  settings[key] = value
  saveSettings()
}

// 保存设置
async function saveSettings() {
  if (saving.value) return
  saving.value = true

  try {
    const res = await updateNotificationSettings(settings)
    if (res.code === 0) {
      // 静默保存成功
    } else {
      toast.error(res.message || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    saving.value = false
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

  &.disabled {
    opacity: 0.5;
    pointer-events: none;

    .setting-item:first-child {
      pointer-events: auto;
    }
  }
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

.setting-icon {
  font-size: 20px;
  color: $color-primary;
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

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
