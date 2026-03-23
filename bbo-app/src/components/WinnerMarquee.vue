<template>
  <view v-if="winners.length > 0" class="marquee-container">
    <view class="marquee-track" :style="{ animationDuration: `${duration}s` }">
      <view
        v-for="(winner, index) in displayList"
        :key="`${winner.id}-${index}`"
        class="marquee-item"
      >
        <text class="winner-emoji">🎉</text>
        <text class="winner-text">
          {{ winner.user_nickname }} {{ t('game.justWon') }}
          <text class="prize-highlight">{{ getPrizeDisplayName(winner) }}</text>
        </text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { getWinnerBroadcast, type WinRecord } from '@/api/game'
import { useAppStore } from '@/store/modules/app'

const props = withDefaults(defineProps<{
  autoFetch?: boolean
  fetchInterval?: number // 刷新间隔（毫秒）
}>(), {
  autoFetch: true,
  fetchInterval: 60000 // 默认 1 分钟刷新一次
})

const { t } = useI18n()
const appStore = useAppStore()
const winners = ref<WinRecord[]>([])
const duration = ref(30) // 动画时长（秒）

// 获取奖品显示名称（现金券需要转换为用户货币）
function getPrizeDisplayName(winner: WinRecord): string {
  // 对于现金券类型，显示换算后的金额
  if (winner.prize_type === 'cash') {
    const formattedAmount = appStore.formatPrice(winner.prize_value, 'USD')
    return `${formattedAmount} ${t('game.cashCoupon')}`
  }
  // 其他类型直接使用后端返回的翻译名称
  return winner.prize_name
}

let fetchTimer: ReturnType<typeof setInterval> | null = null

// 复制一份用于无缝滚动
const displayList = computed(() => {
  if (winners.value.length < 3) {
    // 少于 3 条时复制多份
    const repeated = []
    for (let i = 0; i < 3; i++) {
      repeated.push(...winners.value)
    }
    return repeated
  }
  // 复制一份实现无缝滚动
  return [...winners.value, ...winners.value]
})

// 获取中奖播报
async function fetchWinners() {
  try {
    const res = await getWinnerBroadcast(20)
    if (res.code === 0 && res.data) {
      winners.value = res.data
      // 根据数量调整动画时长
      duration.value = Math.max(20, res.data.length * 3)
    }
  } catch (error) {
    console.error('Failed to fetch winners:', error)
  }
}

// 组件挂载
onMounted(() => {
  if (props.autoFetch) {
    fetchWinners()

    // 定时刷新
    fetchTimer = setInterval(fetchWinners, props.fetchInterval)
  }
})

// 组件卸载
onUnmounted(() => {
  if (fetchTimer) {
    clearInterval(fetchTimer)
    fetchTimer = null
  }
})

// 暴露方法供父组件调用
defineExpose({
  refresh: fetchWinners,
  setWinners: (data: WinRecord[]) => {
    winners.value = data
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$gold: #FFD700;
$text-primary: #1E293B;

.marquee-container {
  width: 100%;
  height: 64rpx;
  background: linear-gradient(90deg, rgba($gold, 0.15) 0%, rgba($gold, 0.05) 50%, rgba($gold, 0.15) 100%);
  border-radius: 32rpx;
  overflow: hidden;
  position: relative;

  &::before,
  &::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 48rpx;
    z-index: 1;
    pointer-events: none;
  }

  &::before {
    left: 0;
    background: linear-gradient(90deg, rgba($gold, 0.15) 0%, transparent 100%);
  }

  &::after {
    right: 0;
    background: linear-gradient(90deg, transparent 0%, rgba($gold, 0.15) 100%);
  }
}

.marquee-track {
  display: flex;
  animation: marquee linear infinite;
  white-space: nowrap;
}

.marquee-item {
  display: flex;
  align-items: center;
  padding: 0 48rpx;
  height: 64rpx;
  flex-shrink: 0;

  .winner-emoji {
    margin-right: 8rpx;
    font-size: 24rpx;
  }

  .winner-text {
    font-size: 24rpx;
    color: $text-primary;
  }

  .prize-highlight {
    font-weight: 600;
    color: $gold;
    margin-left: 4rpx;
  }
}

@keyframes marquee {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .marquee-track {
    animation: none;
  }
}
</style>
