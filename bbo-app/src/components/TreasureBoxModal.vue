<template>
  <view v-if="visible" class="treasure-box-overlay" @click="handleClose" @touchmove.prevent>
    <view class="treasure-box-modal" @click.stop @touchmove.stop>
      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="modal-header">
        <text class="header-title">{{ t('game.treasureBox') }}</text>
        <text class="header-subtitle">{{ t('game.treasureBoxDesc') }}</text>
      </view>

      <!-- 宝箱展示区 -->
      <view class="box-display">
        <!-- 未开启状态 -->
        <view v-if="!isOpening && !showResult" class="box-container" @click="openBox">
          <view
            class="treasure-box"
            :class="[boxType, { shake: canOpen }]"
            :style="{ '--box-color': boxColor }"
          >
            <view class="box-lid">
              <view class="lid-front"></view>
              <view class="lid-top"></view>
            </view>
            <view class="box-body">
              <view class="box-front"></view>
              <view class="box-glow"></view>
            </view>
          </view>
          <text class="box-name">{{ boxName }}</text>
          <text v-if="canOpen" class="tap-hint">{{ t('game.tapToOpen') }}</text>
        </view>

        <!-- 开启动画 -->
        <view v-if="isOpening" class="opening-animation">
          <view class="treasure-box opening" :style="{ '--box-color': boxColor }">
            <view class="box-lid open">
              <view class="lid-front"></view>
              <view class="lid-top"></view>
            </view>
            <view class="box-body">
              <view class="box-front"></view>
              <view class="box-glow active"></view>
            </view>
          </view>
          <view class="sparkles">
            <view v-for="i in 12" :key="i" class="sparkle" :style="{ '--delay': i * 0.1 + 's' }"></view>
          </view>
        </view>

        <!-- 结果展示 -->
        <view v-if="showResult && prize" class="result-display">
          <view class="prize-icon" :style="{ background: prize.color || '#FFD700' }">
            <text class="bi" :class="getPrizeIcon(prize.type)"></text>
          </view>
          <text class="prize-name">{{ prize.name }}</text>
          <text class="prize-value">{{ formatPrizeValue(prize) }}</text>
          <view class="result-actions">
            <view class="action-btn primary" @click="handleClose">
              <text>{{ t('common.gotIt') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 我的宝箱数量 -->
      <view v-if="boxCounts" class="my-boxes">
        <text class="section-title">{{ t('game.myTreasureBoxes') }}</text>
        <view class="box-counts">
          <view class="count-item silver" @click="selectBox('silver_box')">
            <view class="count-icon"></view>
            <text class="count-num">{{ boxCounts.silver_box }}</text>
            <text class="count-label">{{ t('game.silverBox') }}</text>
          </view>
          <view class="count-item gold" @click="selectBox('gold_box')">
            <view class="count-icon"></view>
            <text class="count-num">{{ boxCounts.gold_box }}</text>
            <text class="count-label">{{ t('game.goldBox') }}</text>
          </view>
          <view class="count-item diamond" @click="selectBox('diamond_box')">
            <view class="count-icon"></view>
            <text class="count-num">{{ boxCounts.diamond_box }}</text>
            <text class="count-label">{{ t('game.diamondBox') }}</text>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// Props
const props = withDefaults(
  defineProps<{
    visible: boolean
    boxCode?: string
    boxCounts?: {
      silver_box: number
      gold_box: number
      diamond_box: number
    }
    pendingBoxes?: Array<{
      id: number
      box_code: string
    }>
  }>(),
  {
    boxCode: 'silver_box'
  }
)

// Emits
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'open', boxId: number): void
  (e: 'opened', result: any): void
}>()

// 状态
const isOpening = ref(false)
const showResult = ref(false)
const prize = ref<any>(null)
const currentBoxCode = ref(props.boxCode)

// 计算属性
const boxType = computed(() => {
  if (currentBoxCode.value.includes('diamond')) return 'diamond'
  if (currentBoxCode.value.includes('gold')) return 'gold'
  return 'silver'
})

const boxColor = computed(() => {
  const colors: Record<string, string> = {
    silver: '#C0C0C0',
    gold: '#FFD700',
    diamond: '#B9F2FF'
  }
  return colors[boxType.value] || '#C0C0C0'
})

const boxName = computed(() => {
  const names: Record<string, string> = {
    silver_box: t('game.silverBox'),
    gold_box: t('game.goldBox'),
    diamond_box: t('game.diamondBox')
  }
  return names[currentBoxCode.value] || currentBoxCode.value
})

const canOpen = computed(() => {
  if (!props.boxCounts) return false
  return (props.boxCounts[currentBoxCode.value as keyof typeof props.boxCounts] || 0) > 0
})

const currentPendingBox = computed(() => {
  if (!props.pendingBoxes) return null
  return props.pendingBoxes.find(b => b.box_code === currentBoxCode.value)
})

// 监听显示状态重置
watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      isOpening.value = false
      showResult.value = false
      prize.value = null
      currentBoxCode.value = props.boxCode
    }
  }
)

// 选择宝箱
function selectBox(code: string) {
  if (isOpening.value || showResult.value) return
  currentBoxCode.value = code
}

// 开启宝箱
function openBox() {
  if (!canOpen.value || isOpening.value) return

  const pendingBox = currentPendingBox.value
  if (!pendingBox) {
    uni.showToast({
      title: t('game.noBoxAvailable'),
      icon: 'none'
    })
    return
  }

  // 发出开启事件
  emit('open', pendingBox.id)
}

// 处理开启结果
function handleOpenResult(result: any) {
  isOpening.value = true

  // 开启动画
  setTimeout(() => {
    isOpening.value = false
    showResult.value = true
    prize.value = result.prize

    emit('opened', result)
  }, 1500)
}

// 获取奖品图标
function getPrizeIcon(type: string): string {
  const icons: Record<string, string> = {
    points: 'bi-coin',
    cash: 'bi-cash',
    coupon: 'bi-ticket-perforated',
    chance: 'bi-arrow-repeat'
  }
  return icons[type] || 'bi-gift'
}

// 格式化奖品值
function formatPrizeValue(prize: any): string {
  if (!prize) return ''
  switch (prize.type) {
    case 'points':
      return `+${prize.value} ${t('game.points')}`
    case 'cash':
      return `+$${prize.value}`
    case 'coupon':
      return `${prize.value}% OFF`
    case 'chance':
      return `+${prize.value} ${t('game.chances')}`
    default:
      return prize.value
  }
}

// 关闭弹窗
function handleClose() {
  if (isOpening.value) return
  emit('update:visible', false)
}

// 暴露方法给父组件
defineExpose({
  handleOpenResult
})
</script>

<style lang="scss" scoped>
.treasure-box-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.treasure-box-modal {
  position: relative;
  width: 90%;
  max-width: 600rpx;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 32rpx;
  padding: 48rpx 32rpx;
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
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;

  text {
    font-size: 32rpx;
    color: rgba(255, 255, 255, 0.6);
  }
}

.modal-header {
  text-align: center;
  margin-bottom: 48rpx;
}

.header-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: #fff;
  margin-bottom: 8rpx;
}

.header-subtitle {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.6);
}

.box-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 400rpx;
}

.box-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
}

.treasure-box {
  position: relative;
  width: 200rpx;
  height: 180rpx;
  perspective: 500px;

  &.shake {
    animation: shake 0.5s ease-in-out infinite;
  }
}

.box-lid {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 60rpx;
  transform-origin: bottom center;
  transition: transform 0.5s ease;

  &.open {
    transform: rotateX(-120deg);
  }
}

.lid-front {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 40rpx;
  background: linear-gradient(180deg, var(--box-color) 0%, color-mix(in srgb, var(--box-color) 70%, #000) 100%);
  border-radius: 8rpx 8rpx 0 0;
}

.lid-top {
  position: absolute;
  top: 0;
  left: 10%;
  width: 80%;
  height: 20rpx;
  background: linear-gradient(180deg, color-mix(in srgb, var(--box-color) 100%, #fff 30%) 0%, var(--box-color) 100%);
  border-radius: 8rpx 8rpx 0 0;
}

.box-body {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 140rpx;
}

.box-front {
  width: 100%;
  height: 100%;
  background: linear-gradient(180deg, var(--box-color) 0%, color-mix(in srgb, var(--box-color) 60%, #000) 100%);
  border-radius: 0 0 12rpx 12rpx;
}

.box-glow {
  position: absolute;
  top: -20rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 80%;
  height: 60rpx;
  background: radial-gradient(ellipse, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;

  &.active {
    opacity: 1;
    animation: glow 0.5s ease-in-out infinite alternate;
  }
}

.box-name {
  margin-top: 24rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: #fff;
}

.tap-hint {
  margin-top: 16rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.5);
  animation: pulse 1.5s ease-in-out infinite;
}

.opening-animation {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 300rpx;
}

.sparkles {
  position: absolute;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.sparkle {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 16rpx;
  height: 16rpx;
  background: #FFD700;
  border-radius: 50%;
  animation: sparkle 1s ease-out forwards;
  animation-delay: var(--delay);
  opacity: 0;

  @for $i from 1 through 12 {
    &:nth-child(#{$i}) {
      --angle: #{$i * 30}deg;
    }
  }
}

.result-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: fadeIn 0.5s ease;
}

.prize-icon {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24rpx;

  text {
    font-size: 56rpx;
    color: #fff;
  }
}

.prize-name {
  font-size: 32rpx;
  font-weight: 600;
  color: #fff;
  margin-bottom: 8rpx;
}

.prize-value {
  font-size: 48rpx;
  font-weight: 700;
  color: #FFD700;
  margin-bottom: 32rpx;
}

.result-actions {
  width: 100%;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 88rpx;
  border-radius: 44rpx;

  &.primary {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  }

  text {
    font-size: 30rpx;
    font-weight: 600;
    color: #1a1a2e;
  }
}

.my-boxes {
  margin-top: 32rpx;
  padding-top: 32rpx;
  border-top: 1rpx solid rgba(255, 255, 255, 0.1);
}

.section-title {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 16rpx;
  text-align: center;
}

.box-counts {
  display: flex;
  justify-content: space-around;
}

.count-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16rpx;
  border-radius: 16rpx;
  cursor: pointer;

  &.silver .count-icon {
    background: #C0C0C0;
  }

  &.gold .count-icon {
    background: #FFD700;
  }

  &.diamond .count-icon {
    background: #B9F2FF;
  }
}

.count-icon {
  width: 48rpx;
  height: 48rpx;
  border-radius: 8rpx;
  margin-bottom: 8rpx;
}

.count-num {
  font-size: 32rpx;
  font-weight: 700;
  color: #fff;
}

.count-label {
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.5);
}

@keyframes shake {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(-3deg); }
  75% { transform: rotate(3deg); }
}

@keyframes glow {
  0% { opacity: 0.5; }
  100% { opacity: 1; }
}

@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

@keyframes sparkle {
  0% {
    transform: translate(-50%, -50%) scale(0);
    opacity: 1;
  }
  100% {
    transform: translate(
      calc(-50% + cos(var(--angle)) * 150rpx),
      calc(-50% + sin(var(--angle)) * 150rpx)
    ) scale(1);
    opacity: 0;
  }
}

@keyframes fadeIn {
  0% {
    opacity: 0;
    transform: scale(0.8);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
