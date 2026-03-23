<template>
  <view v-if="visible" class="result-modal-overlay" @click.stop @touchmove.prevent>
    <view class="result-modal-content" :class="{ visible: contentVisible }" @touchmove.stop>
      <!-- 礼花动画背景 -->
      <view class="confetti-container">
        <view v-for="i in 20" :key="i" class="confetti" :style="getConfettiStyle(i)"></view>
      </view>

      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 奖品展示 -->
      <view class="prize-display">
        <view class="prize-glow"></view>
        <view class="prize-icon" :style="{ background: prize?.color || '#FFD700' }">
          <image v-if="prize?.image" :src="prize.image" class="prize-image" mode="aspectFit" />
          <text v-else class="bi" :class="getPrizeIcon()"></text>
        </view>
      </view>

      <!-- 恭喜文字 -->
      <view class="result-title">
        <text>{{ t('game.congratulations') }}</text>
      </view>

      <!-- 奖品名称（现金券不显示，因为下面已有换算金额） -->
      <view v-if="prize?.type !== 'cash'" class="prize-name">
        <text>{{ prize?.name || '' }}</text>
      </view>

      <!-- 奖品价值 -->
      <view class="prize-value">
        <text v-if="prize?.type === 'points'">+{{ prize.value }} {{ t('game.points') }}</text>
        <text v-else-if="prize?.type === 'cash'">{{ formatCashPrize(prize.value) }}</text>
        <text v-else-if="prize?.type === 'coupon'">{{ prize.value }}% {{ t('game.discountCoupon') }}</text>
        <text v-else-if="prize?.type === 'chance'">+{{ prize.value }} {{ t('game.extraChances') }}</text>
        <text v-else-if="prize?.type === 'goods'">{{ t('game.physicalPrize') }}</text>
      </view>

      <!-- 提示信息 -->
      <view class="result-tip">
        <text class="bi bi-info-circle"></text>
        <text>{{ getPrizeTip() }}</text>
      </view>

      <!-- 按钮组 -->
      <view class="result-actions">
        <view class="action-btn secondary" @click="handleClose">
          <text>{{ t('common.close') }}</text>
        </view>
        <view v-if="showPlayAgain" class="action-btn primary" @click="handlePlayAgain">
          <text>{{ t('game.playAgain') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Prize } from '@/api/game'
import { useAppStore } from '@/store/modules/app'

const appStore = useAppStore()

const props = withDefaults(defineProps<{
  visible: boolean
  prize: Prize | null
  showPlayAgain?: boolean
}>(), {
  showPlayAgain: true
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'close'): void
  (e: 'again'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)

// 监听 visible 变化
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// 获取奖品图标
function getPrizeIcon() {
  if (!props.prize) return 'bi-gift'

  const icons: Record<string, string> = {
    points: 'bi-coin',
    cash: 'bi-cash',
    coupon: 'bi-ticket-perforated',
    goods: 'bi-box-seam',
    chance: 'bi-arrow-repeat'
  }

  return icons[props.prize.type] || 'bi-gift'
}

// 格式化现金奖品显示（支持货币转换）
function formatCashPrize(value: number): string {
  const formattedAmount = appStore.formatPrice(value, 'USD')
  return `${formattedAmount} ${t('game.cashCoupon')}`
}

// 获取底部提示信息（根据奖品类型显示不同提示）
function getPrizeTip(): string {
  // 现金券使用特殊提示
  if (props.prize?.type === 'cash') {
    return t('game.prizeCashTip')
  }
  // 其他奖品使用通用提示
  return t('game.prizeTip')
}

// 获取礼花样式
function getConfettiStyle(index: number) {
  const colors = ['#FFD700', '#FF6B35', '#A855F7', '#3B82F6', '#10B981', '#F59E0B']
  const color = colors[index % colors.length]
  const left = Math.random() * 100
  const delay = Math.random() * 1
  const duration = 2 + Math.random() * 1
  const size = 8 + Math.random() * 8

  return {
    left: `${left}%`,
    background: color,
    width: `${size}rpx`,
    height: `${size}rpx`,
    animationDelay: `${delay}s`,
    animationDuration: `${duration}s`
  }
}

// 关闭弹窗
function close() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
    emit('close')
  }, 200)
}

// 关闭按钮
function handleClose() {
  close()
}

// 再抽一次
function handlePlayAgain() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
    emit('again')
  }, 200)
}
</script>

<style lang="scss" scoped>
// 设计系统变量
$primary: #FF6B35;
$surface: #FFFFFF;
$background: #F8FAFC;
$text-primary: #1E293B;
$text-secondary: #64748B;
$text-muted: #94A3B8;
$border: #E2E8F0;
$gold: #FFD700;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.result-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  animation: fadeIn 0.3s ease;
}

.result-modal-content {
  position: relative;
  width: 100%;
  max-width: 560rpx;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  padding: 64rpx 40rpx 48rpx;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.32, 0.72, 0, 1);
  overflow: hidden;

  &.visible {
    transform: scale(1);
    opacity: 1;
  }
}

.confetti-container {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.confetti {
  position: absolute;
  top: -20rpx;
  border-radius: 4rpx;
  animation: confettiFall 2s ease-out forwards;
}

.close-btn {
  position: absolute;
  top: 24rpx;
  right: 24rpx;
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  z-index: 10;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);
  }

  &:active {
    background: rgba(255, 255, 255, 0.2);
  }
}

.prize-display {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 32rpx;
}

.prize-glow {
  position: absolute;
  width: 200rpx;
  height: 200rpx;
  background: radial-gradient(circle, rgba($gold, 0.3) 0%, transparent 70%);
  animation: pulse 2s ease-in-out infinite;
}

.prize-icon {
  position: relative;
  width: 160rpx;
  height: 160rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 8rpx 32rpx rgba(0, 0, 0, 0.3),
    inset 0 2rpx 4rpx rgba(255, 255, 255, 0.3);
  animation: bounceIn 0.5s ease;

  .prize-image {
    width: 80rpx;
    height: 80rpx;
  }

  text {
    font-size: 64rpx;
    color: #fff;
  }
}

.result-title {
  text-align: center;
  margin-bottom: 16rpx;

  text {
    font-size: 40rpx;
    font-weight: 700;
    color: $gold;
    text-shadow: 0 2rpx 8rpx rgba($gold, 0.3);
  }
}

.prize-name {
  text-align: center;
  margin-bottom: 8rpx;

  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #fff;
  }
}

.prize-value {
  text-align: center;
  margin-bottom: 24rpx;

  text {
    font-size: 48rpx;
    font-weight: 700;
    color: $gold;
  }
}

.result-tip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  margin-bottom: 32rpx;
  padding: 16rpx 24rpx;
  background: rgba(255, 255, 255, 0.1);
  border-radius: $radius-lg;

  text {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
  }
}

.result-actions {
  display: flex;
  gap: 16rpx;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 88rpx;
  border-radius: $radius-lg;
  font-size: 30rpx;
  font-weight: 600;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
  }

  &.secondary {
    background: rgba(255, 255, 255, 0.1);

    text {
      color: rgba(255, 255, 255, 0.8);
    }

    &:active {
      background: rgba(255, 255, 255, 0.2);
    }
  }

  &.primary {
    background: linear-gradient(135deg, $gold 0%, #FFA500 100%);

    text {
      color: #1a1a2e;
    }

    &:active {
      background: linear-gradient(135deg, darken($gold, 5%) 0%, darken(#FFA500, 5%) 100%);
    }
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.2); opacity: 0.8; }
}

@keyframes confettiFall {
  0% {
    transform: translateY(0) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(800rpx) rotate(720deg);
    opacity: 0;
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .result-modal-overlay,
  .result-modal-content,
  .prize-icon,
  .prize-glow,
  .confetti {
    animation: none;
    transition: none;
  }
}
</style>
