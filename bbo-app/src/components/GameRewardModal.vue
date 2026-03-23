<template>
  <view v-if="visible" class="reward-modal-overlay" @click="handleOverlayClick" @touchmove.prevent>
    <view class="reward-modal-content" :class="{ visible: contentVisible }" @click.stop @touchmove.stop>
      <!-- Confetti animation -->
      <view class="confetti-container">
        <view v-for="i in 20" :key="i" class="confetti" :style="getConfettiStyle(i)"></view>
      </view>

      <!-- Header -->
      <view class="reward-header">
        <view class="reward-icon">
          <text class="bi bi-gift-fill"></text>
        </view>
        <text class="reward-title">{{ t('game.reward.congratulations') }}</text>
        <text class="reward-subtitle">{{ t('game.reward.orderComplete') }}</text>
      </view>

      <!-- Rewards list -->
      <view class="rewards-list">
        <!-- Wheel chances -->
        <view v-if="wheelChances > 0" class="reward-item">
          <view class="reward-item-icon wheel">
            <text class="bi bi-bullseye"></text>
          </view>
          <view class="reward-item-info">
            <text class="reward-item-name">{{ t('game.luckyWheel') }}</text>
            <text class="reward-item-value">+{{ wheelChances }} {{ t('game.chances') }}</text>
          </view>
        </view>

        <!-- Egg reward -->
        <view v-if="eggCode" class="reward-item">
          <view class="reward-item-icon egg" :style="{ background: getEggColor(eggCode) }">
            <text class="egg-emoji">{{ getEggEmoji(eggCode) }}</text>
          </view>
          <view class="reward-item-info">
            <text class="reward-item-name">{{ t(`game.eggTier.${getEggName(eggCode)}`) }}</text>
            <text class="reward-item-value">+1 {{ t('game.egg') }}</text>
          </view>
        </view>
      </view>

      <!-- Action buttons -->
      <view class="reward-actions">
        <button class="btn-primary" @click="handlePlayNow">
          <text class="bi bi-play-fill"></text>
          <text>{{ t('game.reward.playNow') }}</text>
        </button>
        <view class="btn-link" @click="handleLater">
          <text>{{ t('game.reward.laterRemind') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const props = withDefaults(defineProps<{
  visible: boolean
  wheelChances?: number
  eggCode?: string | null
}>(), {
  wheelChances: 0,
  eggCode: null
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'play-now'): void
  (e: 'later'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)

// Watch visible changes
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// Get confetti style for animation
function getConfettiStyle(index: number) {
  const colors = ['#FFD700', '#FF6B35', '#10B981', '#3B82F6', '#A855F7', '#EC4899']
  const color = colors[index % colors.length]
  const left = Math.random() * 100
  const delay = Math.random() * 2
  const duration = 2 + Math.random() * 2
  const size = 8 + Math.random() * 8

  return {
    '--confetti-color': color,
    '--confetti-left': `${left}%`,
    '--confetti-delay': `${delay}s`,
    '--confetti-duration': `${duration}s`,
    '--confetti-size': `${size}rpx`
  }
}

// Get egg emoji
function getEggEmoji(code: string): string {
  const emojis: Record<string, string> = {
    bronze_egg: '🥚',
    silver_egg: '🥈',
    gold_egg: '🥇',
    diamond_egg: '💎'
  }
  return emojis[code] || '🥚'
}

// Get egg color
function getEggColor(code: string): string {
  const colors: Record<string, string> = {
    bronze_egg: '#CD7F32',
    silver_egg: '#C0C0C0',
    gold_egg: '#FFD700',
    diamond_egg: '#B9F2FF'
  }
  return colors[code] || '#CD7F32'
}

// Get egg name key for translation
function getEggName(code: string): string {
  const names: Record<string, string> = {
    bronze_egg: 'bronzeEgg',
    silver_egg: 'silverEgg',
    gold_egg: 'goldEgg',
    diamond_egg: 'diamondEgg'
  }
  return names[code] || 'bronzeEgg'
}

// Close modal
function close() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
  }, 200)
}

// Handle overlay click
function handleOverlayClick() {
  close()
}

// Play now
function handlePlayNow() {
  close()
  emit('play-now')
}

// Later
function handleLater() {
  close()
  emit('later')
}
</script>

<style lang="scss" scoped>
$primary: #FF6B35;
$gold: #FFD700;
$success: #10B981;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.reward-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  animation: fadeIn 0.3s ease;
}

.reward-modal-content {
  position: relative;
  width: 100%;
  max-width: 600rpx;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  padding: 48rpx 32rpx;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.32, 0.72, 0, 1);
  overflow: hidden;

  &.visible {
    transform: scale(1);
    opacity: 1;
  }
}

// Confetti animation
.confetti-container {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.confetti {
  position: absolute;
  top: -20rpx;
  left: var(--confetti-left);
  width: var(--confetti-size);
  height: var(--confetti-size);
  background: var(--confetti-color);
  animation: confettiFall var(--confetti-duration) ease-out var(--confetti-delay) infinite;
  opacity: 0;

  &:nth-child(odd) {
    border-radius: 50%;
  }
}

.reward-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 40rpx;
}

.reward-icon {
  width: 120rpx;
  height: 120rpx;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24rpx;
  box-shadow: 0 8rpx 32rpx rgba($gold, 0.4);
  animation: iconPulse 1.5s ease-in-out infinite;

  .bi {
    font-size: 56rpx;
    color: #fff;
  }
}

.reward-title {
  font-size: 44rpx;
  font-weight: 700;
  color: $gold;
  margin-bottom: 12rpx;
  text-shadow: 0 2rpx 8rpx rgba($gold, 0.3);
}

.reward-subtitle {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.7);
}

.rewards-list {
  background: rgba(255, 255, 255, 0.05);
  border-radius: $radius-lg;
  padding: 24rpx;
  margin-bottom: 32rpx;
}

.reward-item {
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 16rpx 0;

  &:not(:last-child) {
    border-bottom: 1rpx solid rgba(255, 255, 255, 0.1);
  }
}

.reward-item-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  &.wheel {
    background: linear-gradient(135deg, $primary 0%, #FF8C00 100%);

    .bi {
      font-size: 40rpx;
      color: #fff;
    }
  }

  &.egg {
    .egg-emoji {
      font-size: 40rpx;
    }
  }
}

.reward-item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.reward-item-name {
  font-size: 28rpx;
  font-weight: 600;
  color: #fff;
}

.reward-item-value {
  font-size: 32rpx;
  font-weight: 700;
  color: $gold;
}

.reward-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20rpx;
}

.btn-primary {
  width: 100%;
  height: 96rpx;
  background: linear-gradient(135deg, $primary 0%, #FF8C00 100%) !important;
  border: none !important;
  border-radius: 48rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  box-shadow: 0 8rpx 24rpx rgba($primary, 0.4);

  &::after {
    border: none;
  }

  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #fff !important;
  }

  .bi {
    font-size: 36rpx;
  }
}

.btn-link {
  padding: 16rpx 32rpx;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.6);
  }

  &:active {
    opacity: 0.7;
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes confettiFall {
  0% {
    transform: translateY(0) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(1000rpx) rotate(720deg);
    opacity: 0;
  }
}

@keyframes iconPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

// Reduce motion
@media (prefers-reduced-motion: reduce) {
  .confetti,
  .reward-icon,
  .reward-modal-content {
    animation: none;
    transition: none !important;
  }
}
</style>
