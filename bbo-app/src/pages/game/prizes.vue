<template>
  <view class="page">
    <NavBar :title="t('game.prizeHistory')" />

    <view class="content">
      <!-- 加载中 -->
      <LoadingPage v-if="loading && prizes.length === 0" />

      <!-- 空状态 -->
      <view v-else-if="!loading && prizes.length === 0" class="empty-state">
        <text class="bi bi-gift empty-icon"></text>
        <text class="empty-text">{{ t('game.noPrizes') }}</text>
      </view>

      <!-- 奖品列表 -->
      <view v-else class="prizes-list">
        <view
          v-for="prize in prizes"
          :key="prize.id"
          class="prize-item"
        >
          <view class="prize-icon" :style="{ background: getPrizeColor(prize.prize_type) }">
            <text :class="getPrizeIcon(prize.prize_type)"></text>
          </view>

          <view class="prize-info">
            <text class="prize-name">{{ getPrizeDisplayName(prize) }}</text>
            <text class="prize-time">{{ formatTime(prize.created_at) }}</text>
          </view>

          <view class="prize-status">
            <text
              class="status-tag"
              :class="prize.status"
            >
              {{ getStatusText(prize.status) }}
            </text>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more" @click="loadMore">
          <text v-if="loadingMore">{{ t('common.loading') }}</text>
          <text v-else>{{ t('common.loadMore') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="prizes.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getUserGameLogs, type GameLog } from '@/api/game'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const appStore = useAppStore()

const loading = ref(true)
const loadingMore = ref(false)
const prizes = ref<GameLog[]>([])
const page = ref(1)
const pageSize = 20
const hasMore = ref(false)

onMounted(() => {
  loadPrizes()
})

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('game.prizeHistory') })
})

async function loadPrizes() {
  loading.value = true
  try {
    const res = await getUserGameLogs({ page: 1, pageSize })
    if (res.code === 0) {
      prizes.value = res.data.list || []
      hasMore.value = prizes.value.length >= pageSize
      page.value = 1
    }
  } catch (error) {
    console.error('Failed to load prizes:', error)
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return

  loadingMore.value = true
  try {
    const res = await getUserGameLogs({ page: page.value + 1, pageSize })
    if (res.code === 0) {
      const newList = res.data.list || []
      prizes.value = [...prizes.value, ...newList]
      hasMore.value = newList.length >= pageSize
      page.value++
    }
  } catch (error) {
    console.error('Failed to load more prizes:', error)
  } finally {
    loadingMore.value = false
  }
}

function getPrizeIcon(type: string): string {
  const icons: Record<string, string> = {
    points: 'bi bi-coin',
    cash: 'bi bi-cash',
    coupon: 'bi bi-ticket-perforated',
    chance: 'bi bi-arrow-repeat',
    goods: 'bi bi-gift',
  }
  return icons[type] || 'bi bi-gift'
}

function getPrizeColor(type: string): string {
  const colors: Record<string, string> = {
    points: '#FFD700',
    cash: '#10B981',
    coupon: '#3B82F6',
    chance: '#A855F7',
    goods: '#F59E0B',
  }
  return colors[type] || '#FFD700'
}

function getStatusText(status: string): string {
  const statusMap: Record<string, string> = {
    pending: t('game.pending'),
    claimed: t('game.claimed'),
    expired: t('game.expired'),
  }
  return statusMap[status] || status
}

function formatTime(time: string): string {
  if (!time) return ''
  const date = new Date(time)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`
}

// 获取奖品显示名称（现金券需要货币转换）
function getPrizeDisplayName(prize: GameLog): string {
  if (prize.prize_type === 'cash') {
    // 现金券：将 USD 金额转换为用户当前货币
    const formattedAmount = appStore.formatPrice(prize.prize_value, 'USD')
    return `${formattedAmount} ${t('game.cashCoupon')}`
  }
  // 其他奖品：直接显示后端返回的翻译名称
  return prize.prize_name
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
$warning: #F59E0B;
$gold: #FFD700;
$radius-lg: 24rpx;

.page {
  min-height: 100vh;
  background: $background;
}

.content {
  padding: 24rpx;
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

.prizes-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.prize-item {
  display: flex;
  align-items: center;
  gap: 24rpx;
  background: $surface;
  padding: 28rpx;
  border-radius: $radius-lg;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
}

.prize-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  text {
    font-size: 36rpx;
    color: #fff;
  }
}

.prize-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;

  .prize-name {
    font-size: 28rpx;
    font-weight: 500;
    color: $text-primary;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .prize-time {
    font-size: 24rpx;
    color: $text-muted;
  }
}

.prize-status {
  flex-shrink: 0;
}

.status-tag {
  font-size: 22rpx;
  padding: 6rpx 16rpx;
  border-radius: 16rpx;

  &.pending {
    background: rgba($warning, 0.1);
    color: $warning;
  }

  &.claimed {
    background: rgba($success, 0.1);
    color: $success;
  }

  &.expired {
    background: rgba($text-muted, 0.1);
    color: $text-muted;
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
