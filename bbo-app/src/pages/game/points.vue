<template>
  <view class="page">
    <NavBar :title="t('game.pointsHistory')" />

    <view class="content">
      <!-- 积分余额卡片 -->
      <view class="balance-card">
        <view class="balance-header">
          <text class="balance-label">{{ t('game.pointsBalance') }}</text>
        </view>
        <view class="balance-value">
          <text class="bi bi-coin"></text>
          <text class="value">{{ formatNumber(balance) }}</text>
        </view>
        <view class="balance-tip">
          <text>1000 {{ t('game.points') }} = {{ appStore.formatPrice(1, 'USD') }}</text>
        </view>
      </view>

      <!-- 积分使用说明 -->
      <view class="usage-card">
        <view class="usage-header">
          <text class="bi bi-info-circle"></text>
          <text class="usage-title">{{ t('game.pointsUsageTitle') }}</text>
        </view>

        <view class="usage-section">
          <view class="usage-item">
            <view class="usage-icon checkout-icon">
              <text class="bi bi-cart-check"></text>
            </view>
            <view class="usage-content">
              <text class="usage-name">{{ t('game.pointsUsageCheckout') }}</text>
              <text class="usage-desc">{{ t('game.checkoutDeductDesc') }} (1000 {{ t('game.points') }} = {{ appStore.formatPrice(1, 'USD') }})</text>
            </view>
          </view>

          <view class="usage-item">
            <view class="usage-icon coupon-icon">
              <text class="bi bi-ticket-perforated"></text>
            </view>
            <view class="usage-content">
              <text class="usage-name">{{ t('game.pointsUsageCoupon') }}</text>
              <text class="usage-desc">{{ t('game.pointsUsageCouponDesc') }}</text>
            </view>
          </view>

          <view class="usage-item">
            <view class="usage-icon game-icon">
              <text class="bi bi-stars"></text>
            </view>
            <view class="usage-content">
              <text class="usage-name">{{ t('game.pointsUsageGame') }}</text>
              <text class="usage-desc">{{ t('game.pointsUsageGameDesc') }}</text>
            </view>
          </view>
        </view>

        <view class="usage-note">
          <text class="bi bi-exclamation-circle"></text>
          <text>{{ t('game.pointsUsageNote') }}</text>
        </view>
      </view>

      <!-- 加载中 -->
      <LoadingPage v-if="loading && logs.length === 0" />

      <!-- 空状态 -->
      <view v-else-if="!loading && logs.length === 0" class="empty-state">
        <text class="bi bi-coin empty-icon"></text>
        <text class="empty-text">{{ t('common.noData') }}</text>
      </view>

      <!-- 积分记录列表 -->
      <view v-else class="logs-list">
        <view class="list-header">
          <text>{{ t('game.pointsHistory') }}</text>
        </view>

        <view
          v-for="log in logs"
          :key="log.id"
          class="log-item"
        >
          <view class="log-info">
            <text class="log-title">{{ log.description }}</text>
            <text class="log-time">{{ formatTime(log.created_at) }}</text>
          </view>

          <view class="log-amount" :class="log.type">
            <text>{{ log.type === 'earn' ? '+' : '-' }}{{ log.amount }}</text>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more" @click="loadMore">
          <text v-if="loadingMore">{{ t('common.loading') }}</text>
          <text v-else>{{ t('common.loadMore') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="logs.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getUserPoints, getPointLogs } from '@/api/game'
import { useAppStore } from '@/store/modules/app'

interface PointLog {
  id: number
  type: 'earn' | 'spend'
  amount: number
  balance_after: number
  source: string
  description: string
  created_at: string
}
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t, locale } = useI18n()
const appStore = useAppStore()


const loading = ref(true)
const loadingMore = ref(false)
const balance = ref(0)
const logs = ref<PointLog[]>([])
const page = ref(1)
const pageSize = 20
const hasMore = ref(false)

onMounted(() => {
  loadData()
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('game.pointsHistory') })
})

async function loadData() {
  loading.value = true
  try {
    // 并行加载余额和记录
    const [balanceRes, logsRes] = await Promise.all([
      getUserPoints(),
      getPointLogs({ page: 1, pageSize }),
    ])

    if (balanceRes.code === 0) {
      balance.value = balanceRes.data.balance || 0
    }

    if (logsRes.code === 0) {
      logs.value = logsRes.data.list || []
      hasMore.value = logs.value.length >= pageSize
      page.value = 1
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return

  loadingMore.value = true
  try {
    const res = await getPointLogs({ page: page.value + 1, pageSize })
    if (res.code === 0) {
      const newList = res.data.list || []
      logs.value = [...logs.value, ...newList]
      hasMore.value = newList.length >= pageSize
      page.value++
    }
  } catch (error) {
    console.error('Failed to load more:', error)
  } finally {
    loadingMore.value = false
  }
}

function formatNumber(num: number): string {
  return num.toLocaleString()
}

function formatTime(time: string): string {
  if (!time) return ''
  const date = new Date(time)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
}
</script>

<style lang="scss" scoped>
$primary: #FF6B35;
$surface: #FFFFFF;
$background: #F8FAFC;
$text-primary: #1E293B;
$text-secondary: #64748B;
$text-muted: #94A3B8;
$border: #E2E8F0;
$success: #10B981;
$destructive: #EF4444;
$gold: #FFD700;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.page {
  min-height: 100vh;
  background: $background;
}

.content {
  padding: 24rpx;
}

.balance-card {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  padding: 40rpx;
  margin-bottom: 32rpx;
}

.balance-header {
  margin-bottom: 16rpx;

  .balance-label {
    font-size: 26rpx;
    color: rgba(255, 255, 255, 0.7);
  }
}

.balance-value {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-bottom: 16rpx;

  .bi-coin {
    font-size: 48rpx;
    color: $gold;
  }

  .value {
    font-size: 64rpx;
    font-weight: 700;
    color: $gold;
  }
}

.balance-tip {
  text {
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.5);
  }
}

// 积分使用说明卡片
.usage-card {
  background: $surface;
  border-radius: $radius-lg;
  padding: 28rpx;
  margin-bottom: 32rpx;
}

.usage-header {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid $border;

  .bi-info-circle {
    font-size: 32rpx;
    color: $primary;
  }

  .usage-title {
    font-size: 30rpx;
    font-weight: 600;
    color: $text-primary;
  }
}

.usage-section {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.usage-item {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
}

.usage-icon {
  width: 72rpx;
  height: 72rpx;
  border-radius: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 32rpx;
    color: #fff;
  }

  &.checkout-icon {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
  }

  &.coupon-icon {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
  }

  &.game-icon {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
  }
}

.usage-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6rpx;
  padding-top: 4rpx;

  .usage-name {
    font-size: 28rpx;
    font-weight: 500;
    color: $text-primary;
  }

  .usage-desc {
    font-size: 24rpx;
    color: $text-secondary;
    line-height: 1.5;
  }
}

.usage-note {
  display: flex;
  align-items: flex-start;
  gap: 8rpx;
  margin-top: 24rpx;
  padding: 20rpx;
  background: rgba($primary, 0.06);
  border-radius: 12rpx;

  .bi-exclamation-circle {
    font-size: 26rpx;
    color: $primary;
    flex-shrink: 0;
    margin-top: 2rpx;
  }

  text:last-child {
    font-size: 24rpx;
    color: $text-secondary;
    line-height: 1.5;
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 120rpx 48rpx;

  .empty-icon {
    font-size: 120rpx;
    color: $text-muted;
    margin-bottom: 24rpx;
  }

  .empty-text {
    font-size: 28rpx;
    color: $text-secondary;
  }
}

.logs-list {
  background: $surface;
  border-radius: $radius-lg;
  overflow: hidden;
}

.list-header {
  padding: 24rpx 28rpx;
  border-bottom: 1rpx solid $border;

  text {
    font-size: 28rpx;
    font-weight: 600;
    color: $text-primary;
  }
}

.log-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx;
  border-bottom: 1rpx solid $border;

  &:last-child {
    border-bottom: none;
  }
}

.log-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;

  .log-title {
    font-size: 28rpx;
    color: $text-primary;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .log-time {
    font-size: 24rpx;
    color: $text-muted;
  }
}

.log-amount {
  flex-shrink: 0;
  font-size: 32rpx;
  font-weight: 600;

  &.earn {
    color: $success;
  }

  &.spend {
    color: $destructive;
  }
}

.load-more,
.no-more {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx;

  text {
    font-size: 24rpx;
    color: $text-muted;
  }
}

.load-more {
  text {
    color: $primary;
  }
}
</style>
