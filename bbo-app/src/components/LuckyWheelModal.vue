<template>
  <view v-if="visible" class="wheel-modal-overlay" @click="handleOverlayClick" @touchmove.prevent>
    <view class="wheel-modal-content" :class="{ visible: contentVisible }" @click.stop @touchmove.stop>
      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="wheel-header">
        <text class="wheel-title">{{ t('game.luckyWheel') }}</text>
        <view class="chances-badge">
          <text class="bi bi-ticket-perforated"></text>
          <text class="chances-text">{{ chances }}</text>
        </view>
      </view>

      <!-- 转盘容器 -->
      <view class="wheel-container">
        <!-- 指针 -->
        <view class="wheel-pointer">
          <view class="pointer-triangle"></view>
        </view>

        <!-- 转盘 -->
        <view class="wheel-wrapper">
          <view
            class="wheel"
            :style="{ transform: `rotate(${rotation}deg)`, transition: isSpinning ? spinTransition : 'none', background: wheelBackground }"
          >
            <!-- 扇区分隔线 -->
            <view
              v-for="(prize, index) in prizes"
              :key="'line-' + index"
              class="segment-line"
              :style="{ transform: `rotate(${segmentAngle * index}deg)` }"
            ></view>
            <!-- 奖品文字 -->
            <view
              v-for="(prize, index) in prizes"
              :key="'text-' + index"
              class="prize-label"
              :style="getPrizeLabelStyle(index)"
            >
              <text class="prize-name">{{ getPrizeDisplayName(prize) }}</text>
            </view>
          </view>

          <!-- 中心按钮 -->
          <view class="wheel-center" :class="{ disabled: isSpinning || chances <= 0, spinning: isSpinning }" @click="handleSpin">
            <view v-if="isSpinning" class="spinner-container">
              <view class="spinner-ring"></view>
              <view class="spinner-dot"></view>
            </view>
            <text v-else class="center-text">{{ t('game.spin') }}</text>
          </view>
        </view>
      </view>

      <!-- 底部信息 -->
      <view class="wheel-footer">
        <view v-if="chances <= 0" class="no-chances">
          <text class="bi bi-info-circle"></text>
          <text>{{ t('game.noChances') }}</text>
        </view>
        <view class="footer-actions">
          <view class="action-btn" @click="handleViewRecords">
            <text class="bi bi-list-ul"></text>
            <text>{{ t('game.myPrizes') }}</text>
          </view>
          <view class="action-btn" @click="handleViewRules">
            <text class="bi bi-question-circle"></text>
            <text>{{ t('game.rules') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 中奖结果弹窗 -->
    <PrizeResultModal
      v-model:visible="showResult"
      :prize="winPrize"
      @close="handleResultClose"
      @again="handlePlayAgain"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { playWheel, type Prize, type PlayResult } from '@/api/game'
import { toast } from '@/composables/useToast'
import { useAppStore } from '@/store/modules/app'
import PrizeResultModal from './PrizeResultModal.vue'

const appStore = useAppStore()

const props = withDefaults(defineProps<{
  visible: boolean
  prizes: Prize[]
  chances: number
}>(), {
  prizes: () => [],
  chances: 0
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'update:chances', value: number): void
  (e: 'spin-complete', result: PlayResult): void
  (e: 'view-records'): void
  (e: 'view-rules'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)
const isSpinning = ref(false)
const rotation = ref(0)
const showResult = ref(false)
const winPrize = ref<Prize | null>(null)

// 转盘动画配置
const spinDuration = 5 // 秒
const spinTransition = computed(() => `transform ${spinDuration}s cubic-bezier(0.17, 0.67, 0.12, 0.99)`)

// 每个扇形的角度
const segmentAngle = computed(() => {
  return props.prizes.length > 0 ? 360 / props.prizes.length : 0
})

// 监听 visible 变化
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// 默认颜色
function getDefaultColor(index: number) {
  const colors = ['#FFD700', '#FFA500', '#FF6B35', '#A855F7', '#3B82F6', '#10B981']
  return colors[index % colors.length]
}

// 获取奖品显示名称（支持货币转换）
function getPrizeDisplayName(prize: Prize): string {
  // 对于现金券类型，需要转换为用户货币显示
  if (prize.type === 'cash') {
    const formattedAmount = appStore.formatPrice(prize.value, 'USD')
    return formattedAmount
  }

  // 其他类型直接使用后端返回的名称
  return prize.name
}

// 生成 conic-gradient 背景
const wheelBackground = computed(() => {
  if (props.prizes.length === 0) return '#333'

  const angle = segmentAngle.value
  const segments = props.prizes.map((prize, index) => {
    const color = prize.color || getDefaultColor(index)
    const start = angle * index
    const end = angle * (index + 1)
    return `${color} ${start}deg ${end}deg`
  })

  return `conic-gradient(from -${angle / 2}deg, ${segments.join(', ')})`
})

// 获取奖品标签位置样式
function getPrizeLabelStyle(index: number) {
  const angle = segmentAngle.value
  // 计算标签的角度（扇区中心）
  const labelAngle = angle * index + angle / 2
  // 转换为弧度
  const radian = (labelAngle - 90) * Math.PI / 180
  // 标签距离圆心的距离（半径的 36%）
  const radius = 36 // 百分比
  const x = 50 + radius * Math.cos(radian)
  const y = 50 + radius * Math.sin(radian)

  return {
    left: `${x}%`,
    top: `${y}%`,
    // 文字沿扇形方向旋转，使其从外向内阅读
    transform: `translate(-50%, -50%) rotate(${labelAngle}deg)`
  }
}

// 关闭弹窗
function close() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
  }, 200)
}

// 点击遮罩
function handleOverlayClick() {
  if (!isSpinning.value) {
    close()
  }
}

// 关闭按钮
function handleClose() {
  if (!isSpinning.value) {
    close()
  }
}

// 开始抽奖
async function handleSpin() {
  if (isSpinning.value || props.chances <= 0) return

  isSpinning.value = true

  try {
    // 调用 API
    const res = await playWheel()

    if (res.code === 0) {
      const result = res.data

      // 找到中奖奖品的索引（后端返回 prize 对象，其中包含 id）
      const prizeId = result.prize?.id
      const prizeIndex = prizeId ? props.prizes.findIndex(p => p.id === prizeId) : -1

      if (prizeIndex !== -1) {
        // 计算需要旋转的角度
        // 转盘至少转 5 圈 + 停在目标位置
        const targetAngle = 360 - (prizeIndex * segmentAngle.value + segmentAngle.value / 2)
        const totalRotation = rotation.value + 360 * 5 + targetAngle - (rotation.value % 360)

        rotation.value = totalRotation

        // 等待动画完成
        setTimeout(() => {
          isSpinning.value = false
          winPrize.value = result.prize // 使用后端返回的完整奖品对象
          showResult.value = true

          // 更新剩余次数
          emit('update:chances', result.remaining_chances)
          emit('spin-complete', result)
        }, spinDuration * 1000 + 200)
      } else {
        // 没有中奖或未找到对应奖品
        isSpinning.value = false
        if (result.prize) {
          winPrize.value = result.prize
          showResult.value = true
          emit('update:chances', result.remaining_chances)
          emit('spin-complete', result)
        }
      }
    } else {
      isSpinning.value = false
      toast.error(res.msg || t('game.spinFailed'))
    }
  } catch (error: any) {
    isSpinning.value = false
    toast.error(error.message || t('game.spinFailed'))
  }
}

// 结果弹窗关闭
function handleResultClose() {
  showResult.value = false
  winPrize.value = null
}

// 再抽一次
function handlePlayAgain() {
  showResult.value = false
  winPrize.value = null

  if (props.chances > 0) {
    setTimeout(() => {
      handleSpin()
    }, 300)
  }
}

// 查看记录
function handleViewRecords() {
  emit('view-records')
}

// 查看规则
function handleViewRules() {
  emit('view-rules')
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
$destructive: #EF4444;
$warning: #F59E0B;
$success: #10B981;
$info: #3B82F6;
$gold: #FFD700;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.wheel-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32rpx;
  animation: fadeIn 0.3s ease;
}

.wheel-modal-content {
  position: relative;
  width: 100%;
  max-width: 680rpx;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  padding: 40rpx 32rpx;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.32, 0.72, 0, 1);

  &.visible {
    transform: scale(1);
    opacity: 1;
  }
}

.close-btn {
  position: absolute;
  top: 24rpx;
  right: 24rpx;
  width: 64rpx;
  height: 64rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  z-index: 10;

  text {
    font-size: 32rpx;
    color: rgba(255, 255, 255, 0.7);
  }

  &:active {
    background: rgba(255, 255, 255, 0.2);
  }
}

.wheel-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 32rpx;
}

.wheel-title {
  font-size: 40rpx;
  font-weight: 700;
  color: $gold;
  text-shadow: 0 2rpx 8rpx rgba($gold, 0.3);
}

.chances-badge {
  display: flex;
  align-items: center;
  gap: 8rpx;
  background: rgba($gold, 0.2);
  padding: 8rpx 16rpx;
  border-radius: 32rpx;

  text {
    font-size: 24rpx;
    color: $gold;
  }

  .chances-text {
    font-weight: 600;
  }
}

.wheel-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx 0;
}

.wheel-pointer {
  position: absolute;
  top: 8rpx;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;

  .pointer-triangle {
    width: 0;
    height: 0;
    border-left: 20rpx solid transparent;
    border-right: 20rpx solid transparent;
    border-top: 40rpx solid $gold;
    filter: drop-shadow(0 2rpx 4rpx rgba(0, 0, 0, 0.3));
  }
}

.wheel-wrapper {
  position: relative;
  width: 560rpx;
  height: 560rpx;
}

.wheel {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  overflow: hidden;
  box-shadow:
    0 0 0 16rpx rgba($gold, 0.3),
    0 0 0 20rpx rgba($gold, 0.1),
    0 8rpx 32rpx rgba(0, 0, 0, 0.4);
}

// 扇区分隔线
.segment-line {
  position: absolute;
  width: 2rpx;
  height: 50%;
  left: 50%;
  top: 0;
  transform-origin: bottom center;
  background: rgba(255, 255, 255, 0.3);
  margin-left: -1rpx;
}

// 奖品标签
.prize-label {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2;
  pointer-events: none;
  width: 120rpx;
  height: 60rpx;

  .prize-name {
    display: block;
    font-size: 18rpx;
    font-weight: 700;
    color: #fff;
    text-shadow:
      1rpx 1rpx 1rpx rgba(0, 0, 0, 1),
      -1rpx -1rpx 1rpx rgba(0, 0, 0, 1),
      1rpx -1rpx 1rpx rgba(0, 0, 0, 1),
      -1rpx 1rpx 1rpx rgba(0, 0, 0, 1),
      0 0 4rpx rgba(0, 0, 0, 0.8);
    text-align: center;
    white-space: nowrap;
    line-height: 1.2;
  }
}

.wheel-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow:
    0 4rpx 16rpx rgba(0, 0, 0, 0.3),
    inset 0 2rpx 4rpx rgba(255, 255, 255, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;

  &:active:not(.disabled):not(.spinning) {
    transform: translate(-50%, -50%) scale(0.95);
  }

  &.disabled:not(.spinning) {
    background: linear-gradient(135deg, #94A3B8 0%, #64748B 100%);
    cursor: not-allowed;
  }

  &.spinning {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    box-shadow:
      0 4rpx 16rpx rgba(0, 0, 0, 0.3),
      inset 0 0 20rpx rgba($gold, 0.3),
      0 0 30rpx rgba($gold, 0.4);
  }

  .center-text {
    font-size: 28rpx;
    font-weight: 700;
    color: #1a1a2e;
  }

  .spinner-container {
    position: relative;
    width: 60rpx;
    height: 60rpx;
  }

  .spinner-ring {
    position: absolute;
    inset: 0;
    border: 4rpx solid rgba($gold, 0.2);
    border-top-color: $gold;
    border-radius: 50%;
    animation: spinnerRotate 0.8s linear infinite;
  }

  .spinner-dot {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16rpx;
    height: 16rpx;
    background: $gold;
    border-radius: 50%;
    animation: spinnerPulse 1s ease-in-out infinite;
  }
}

@keyframes spinnerRotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes spinnerPulse {
  0%, 100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
  }
  50% {
    transform: translate(-50%, -50%) scale(0.6);
    opacity: 0.6;
  }
}

.wheel-footer {
  margin-top: 32rpx;
}

.no-chances {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  margin-bottom: 16rpx;

  text {
    font-size: 24rpx;
    color: $warning;
  }
}

.footer-actions {
  display: flex;
  justify-content: center;
  gap: 32rpx;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 16rpx 24rpx;
  background: rgba(255, 255, 255, 0.1);
  border-radius: $radius-lg;
  transition: all 0.2s ease;

  text {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
  }

  &:active {
    background: rgba(255, 255, 255, 0.2);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .wheel-modal-overlay,
  .wheel-modal-content,
  .wheel {
    animation: none;
    transition: none !important;
  }
}
</style>
