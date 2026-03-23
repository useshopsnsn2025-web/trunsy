<template>
  <view v-if="visible" class="scratch-modal-overlay" @click="handleOverlayClick" :catch-move="true" @touchmove.stop.prevent="preventMove">
    <view class="scratch-modal-content" :class="{ visible: contentVisible }" @click.stop>
      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="scratch-header">
        <text class="scratch-title">{{ t('game.scratchCard') }}</text>
        <view class="chances-badge">
          <text class="bi bi-ticket-perforated"></text>
          <text class="chances-text">{{ chances }}</text>
        </view>
      </view>

      <!-- 刮刮卡区域 -->
      <view class="scratch-card-container">
        <view
          class="scratch-card"
          :class="{ revealed: isRevealed }"
          @touchstart.stop="handleTouchStart"
          @touchmove.stop="handleTouchMove"
          @touchend.stop="handleTouchEnd"
        >
          <!-- 底层 - 奖品 -->
          <view class="prize-layer" :style="{ background: getPrizeBackground() }">
            <view v-if="winPrize" class="prize-content">
              <text class="bi prize-icon" :class="getPrizeIcon()"></text>
              <text class="prize-name">{{ getPrizeDisplayName() }}</text>
              <text class="prize-value">{{ getPrizeDescription() }}</text>
            </view>
            <view v-else class="prize-loading">
              <text class="bi bi-hourglass-split spinning"></text>
            </view>
          </view>

          <!-- 刮涂层 - 使用多个小方块实现 -->
          <view v-if="!isRevealed" class="scratch-cover">
            <!-- 20x14 = 280 个小方块，每个 14x14.3 像素 -->
            <view
              v-for="(cell, index) in cells"
              :key="index"
              class="cover-cell"
              :class="{ scratched: cell.scratched }"
            ></view>
            <!-- 提示文字 -->
            <view v-if="!isScratching" class="scratch-hint">
              <text class="bi bi-hand-index hint-icon"></text>
              <text class="hint-text">{{ t('game.scratchHint') }}</text>
            </view>
          </view>

          <!-- 无次数提示 -->
          <view v-if="chances <= 0 && !isRevealed" class="no-chances-layer">
            <text class="bi bi-lock"></text>
            <text>{{ t('game.noChances') }}</text>
          </view>
        </view>

        <!-- 刮开进度 -->
        <view v-if="isScratching && !isRevealed" class="scratch-progress">
          <view class="progress-bar">
            <view class="progress-fill" :style="{ width: scratchProgress + '%' }"></view>
          </view>
          <text class="progress-text">{{ Math.round(scratchProgress) }}%</text>
        </view>
      </view>

      <!-- 提示信息 -->
      <view class="scratch-tip">
        <text v-if="chances > 0 && !isRevealed">{{ t('game.scratchTip') }}</text>
        <text v-else-if="isRevealed" class="revealed-tip">{{ getPrizeTip() }}</text>
        <text v-else class="no-chances">{{ t('game.getMoreChances') }}</text>
      </view>

      <!-- 底部操作 -->
      <view class="scratch-footer">
        <!-- 再来一张按钮 -->
        <view
          v-if="isRevealed && chances > 0"
          class="play-again-btn"
          @click="handlePlayAgain"
        >
          <text class="bi bi-arrow-repeat"></text>
          <text>{{ t('game.playAgain') }}</text>
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
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { playScratch, type Prize, type PlayResult } from '@/api/game'
import { toast } from '@/composables/useToast'
import { useAppStore } from '@/store/modules/app'

const props = withDefaults(defineProps<{
  visible: boolean
  chances: number
}>(), {
  chances: 0
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'update:chances', value: number): void
  (e: 'scratch-complete', result: PlayResult): void
  (e: 'view-records'): void
  (e: 'view-rules'): void
}>()

const { t } = useI18n()
const appStore = useAppStore()
const contentVisible = ref(false)
const isScratching = ref(false)
const isRevealed = ref(false)
const winPrize = ref<Prize | null>(null)
const scratchProgress = ref(0)

// 网格配置
const COLS = 20
const ROWS = 14
const TOTAL_CELLS = COLS * ROWS
const CELL_WIDTH = 280 / COLS // 14px
const CELL_HEIGHT = 200 / ROWS // ~14.3px
const REVEAL_THRESHOLD = 35 // 刮开 35% 就显示结果

// 单元格状态
interface Cell {
  scratched: boolean
}
const cells = ref<Cell[]>([])

// 触摸状态
let touchStarted = false
let lastTouchX = 0
let lastTouchY = 0
let cardRect: { left: number; top: number; width: number; height: number } | null = null

// 初始化单元格
function initCells() {
  cells.value = Array.from({ length: TOTAL_CELLS }, () => ({ scratched: false }))
}

// 监听 visible 变化
watch(() => props.visible, async (newVal) => {
  if (newVal) {
    resetCard()
    await nextTick()
    contentVisible.value = true
  }
})

// 获取卡片位置（使用 DOM 查询）
async function getCardRect() {
  return new Promise<{ left: number; top: number; width: number; height: number } | null>((resolve) => {
    const query = uni.createSelectorQuery()
    query.select('.scratch-card')
      .boundingClientRect((rect: any) => {
        if (rect) {
          resolve({
            left: rect.left,
            top: rect.top,
            width: rect.width,
            height: rect.height
          })
        } else {
          resolve(null)
        }
      })
      .exec()
  })
}

// 触摸开始
async function handleTouchStart(e: any) {
  if (props.chances <= 0 || isRevealed.value) return

  // 如果是第一次刮，先调用 API 获取结果
  if (!isScratching.value && !winPrize.value) {
    try {
      const res = await playScratch()
      if (res.code === 0) {
        winPrize.value = res.data.prize
        emit('update:chances', res.data.remaining_chances)
      } else {
        toast.error(res.msg || t('game.scratchFailed'))
        return
      }
    } catch (error: any) {
      toast.error(error.message || t('game.scratchFailed'))
      return
    }
  }

  touchStarted = true
  isScratching.value = true

  // 获取卡片位置
  if (!cardRect) {
    cardRect = await getCardRect()
  }

  if (e.touches?.[0] && cardRect) {
    const touch = e.touches[0]
    lastTouchX = touch.clientX
    lastTouchY = touch.clientY
    scratchAt(touch.clientX, touch.clientY)
  }
}

// 触摸移动
function handleTouchMove(e: any) {
  if (!touchStarted || !isScratching.value || isRevealed.value || !cardRect) return

  if (e.touches?.[0]) {
    const touch = e.touches[0]
    const currentX = touch.clientX
    const currentY = touch.clientY

    // 插值：在两点之间刮除
    interpolateScratch(lastTouchX, lastTouchY, currentX, currentY)

    lastTouchX = currentX
    lastTouchY = currentY

    // 检查进度
    checkProgress()
  }
}

// 触摸结束
function handleTouchEnd() {
  touchStarted = false
}

// 在两点之间插值刮除
function interpolateScratch(x1: number, y1: number, x2: number, y2: number) {
  const dist = Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2)
  const step = 5 // 每 5 像素一个点

  if (dist > 0) {
    const steps = Math.max(1, Math.floor(dist / step))
    for (let i = 0; i <= steps; i++) {
      const t = i / steps
      const x = x1 + (x2 - x1) * t
      const y = y1 + (y2 - y1) * t
      scratchAt(x, y)
    }
  }
}

// 在指定位置刮除
function scratchAt(clientX: number, clientY: number) {
  if (!cardRect) return

  // 计算相对于卡片的位置
  const relX = clientX - cardRect.left
  const relY = clientY - cardRect.top

  // 边界检查
  if (relX < 0 || relX > cardRect.width || relY < 0 || relY > cardRect.height) return

  // 转换为网格坐标
  const scaleX = 280 / cardRect.width
  const scaleY = 200 / cardRect.height
  const gridX = relX * scaleX
  const gridY = relY * scaleY

  // 计算要刮除的单元格（刮除半径内的所有单元格）
  const brushRadius = 2 // 刮除半径（单元格数）

  const centerCol = Math.floor(gridX / CELL_WIDTH)
  const centerRow = Math.floor(gridY / CELL_HEIGHT)

  for (let row = centerRow - brushRadius; row <= centerRow + brushRadius; row++) {
    for (let col = centerCol - brushRadius; col <= centerCol + brushRadius; col++) {
      if (row >= 0 && row < ROWS && col >= 0 && col < COLS) {
        const index = row * COLS + col
        if (!cells.value[index].scratched) {
          cells.value[index].scratched = true
        }
      }
    }
  }
}

// 检查刮开进度
function checkProgress() {
  const scratchedCount = cells.value.filter(c => c.scratched).length
  scratchProgress.value = (scratchedCount / TOTAL_CELLS) * 100

  if (scratchProgress.value >= REVEAL_THRESHOLD && !isRevealed.value) {
    revealPrize()
  }
}

// 显示奖品
function revealPrize() {
  isRevealed.value = true
  isScratching.value = false
  touchStarted = false

  // 通知父组件刮卡完成
  if (winPrize.value) {
    emit('scratch-complete', {
      prize: winPrize.value,
      prize_id: winPrize.value.id,
      prize_type: winPrize.value.type,
      prize_value: winPrize.value.value,
      prize_name: winPrize.value.name,
      prize_image: winPrize.value.image,
      prize_color: winPrize.value.color,
      remaining_chances: props.chances,
      log_id: 0
    })
  }
}

// 重置卡片
function resetCard() {
  isScratching.value = false
  isRevealed.value = false
  winPrize.value = null
  scratchProgress.value = 0
  touchStarted = false
  cardRect = null
  initCells()
}

// 获取奖品图标
function getPrizeIcon(): string {
  if (!winPrize.value) return 'bi-gift'

  const icons: Record<string, string> = {
    points: 'bi-coin',
    cash: 'bi-cash',
    coupon: 'bi-ticket-perforated',
    goods: 'bi-box-seam',
    chance: 'bi-arrow-repeat'
  }

  return icons[winPrize.value.type] || 'bi-gift'
}

// 获取奖品背景
function getPrizeBackground(): string {
  if (!winPrize.value) return 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'

  const backgrounds: Record<string, string> = {
    points: 'linear-gradient(135deg, #FFD700 0%, #FFA500 100%)',
    cash: 'linear-gradient(135deg, #10B981 0%, #059669 100%)',
    coupon: 'linear-gradient(135deg, #FF6B35 0%, #FF4444 100%)',
    goods: 'linear-gradient(135deg, #A855F7 0%, #7C3AED 100%)',
    chance: 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)'
  }

  return backgrounds[winPrize.value.type] || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
}

// 获取奖品显示名称（大字，主要信息）
function getPrizeDisplayName(): string {
  if (!winPrize.value) return ''

  const type = winPrize.value.type
  const value = winPrize.value.value

  switch (type) {
    case 'points':
      return `${value} ${t('game.points')}`
    case 'cash':
      // 使用货币转换显示对应汇率的金额 + 现金券
      const formattedAmount = appStore.formatPrice(value, 'USD')
      return `${formattedAmount} ${t('game.cashCoupon')}`
    case 'coupon':
      return `${value}% ${t('game.discountCoupon')}`
    case 'chance':
      return `${value} ${t('game.chances')}`
    default:
      return winPrize.value.name || String(value)
  }
}

// 获取奖品描述（小字，补充信息）
function getPrizeDescription(): string {
  if (!winPrize.value) return ''

  const type = winPrize.value.type

  switch (type) {
    case 'points':
      return t('game.prizePointsDesc')
    case 'cash':
      return t('game.prizeCashDesc')
    case 'coupon':
      return t('game.prizeCouponDesc')
    case 'chance':
      return t('game.prizeChanceDesc')
    default:
      return ''
  }
}

// 获取底部提示信息（根据奖品类型显示不同提示）
function getPrizeTip(): string {
  if (!winPrize.value) return t('game.prizeTip')

  // 现金券使用特殊提示
  if (winPrize.value.type === 'cash') {
    return t('game.prizeCashTip')
  }

  // 其他奖品使用通用提示
  return t('game.prizeTip')
}

// 阻止背景滚动
function preventMove(e: any) {
  // 阻止事件冒泡和默认行为
  if (e.preventDefault) {
    e.preventDefault()
  }
  if (e.stopPropagation) {
    e.stopPropagation()
  }
  return false
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
  if (!isScratching.value) {
    close()
  }
}

// 关闭按钮
function handleClose() {
  if (!isScratching.value) {
    close()
  }
}

// 再来一张
function handlePlayAgain() {
  resetCard()
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
$purple: #A855F7;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.scratch-modal-overlay {
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

.scratch-modal-content {
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

.scratch-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 32rpx;
}

.scratch-title {
  font-size: 40rpx;
  font-weight: 700;
  color: $purple;
  text-shadow: 0 2rpx 8rpx rgba($purple, 0.3);
}

.chances-badge {
  display: flex;
  align-items: center;
  gap: 8rpx;
  background: rgba($purple, 0.2);
  padding: 8rpx 16rpx;
  border-radius: 32rpx;

  text {
    font-size: 24rpx;
    color: $purple;
  }

  .chances-text {
    font-weight: 600;
  }
}

.scratch-card-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 24rpx;
}

.scratch-card {
  position: relative;
  width: 280px;
  height: 200px;
  border-radius: $radius-xl;
  overflow: hidden;
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.3);

  &.revealed {
    .prize-layer {
      animation: prizeReveal 0.5s ease-out forwards;
    }
  }
}

.prize-layer {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.prize-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
  text-align: center;

  .prize-icon {
    font-size: 80rpx;
    color: #fff;
    text-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.2);
  }

  .prize-name {
    font-size: 36rpx;
    font-weight: 700;
    color: #fff;
  }

  .prize-value {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(0, 0, 0, 0.2);
    padding: 8rpx 24rpx;
    border-radius: 24rpx;
  }
}

.prize-loading {
  .spinning {
    font-size: 64rpx;
    color: rgba(255, 255, 255, 0.5);
    animation: spin 1s linear infinite;
  }
}

// 刮涂层
.scratch-cover {
  position: absolute;
  inset: 0;
  display: flex;
  flex-wrap: wrap;
  border-radius: inherit;
  overflow: hidden;
}

.cover-cell {
  width: calc(100% / 20);  // 20 列
  height: calc(100% / 14); // 14 行
  background: linear-gradient(135deg, #D0D0D0 0%, #C0C0C0 50%, #B8B8B8 100%);
  border: 0.5px solid rgba(255, 255, 255, 0.15);
  box-sizing: border-box;
  transition: opacity 0.15s ease-out, transform 0.15s ease-out;

  &.scratched {
    opacity: 0;
    transform: scale(0.5);
  }
}

.scratch-hint {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  background: rgba(0, 0, 0, 0.35);
  pointer-events: none;

  .hint-icon {
    font-size: 64rpx;
    color: #fff;
    animation: bounce 1s ease infinite;
  }

  .hint-text {
    font-size: 28rpx;
    color: #fff;
    font-weight: 500;
  }
}

.no-chances-layer {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  background: rgba(0, 0, 0, 0.7);
  z-index: 5;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);

    &:first-child {
      font-size: 64rpx;
    }
  }
}

.scratch-progress {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-top: 16rpx;
  width: 560rpx;

  .progress-bar {
    flex: 1;
    height: 12rpx;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 6rpx;
    overflow: hidden;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $purple 0%, #EC4899 100%);
    border-radius: 6rpx;
    transition: width 0.1s ease;
  }

  .progress-text {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
    min-width: 60rpx;
    text-align: right;
  }
}

.scratch-tip {
  text-align: center;
  margin-bottom: 24rpx;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);

    &.revealed-tip {
      color: $success;
    }

    &.no-chances {
      color: $warning;
    }
  }
}

.scratch-footer {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.play-again-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  height: 88rpx;
  background: linear-gradient(135deg, $purple 0%, #7C3AED 100%);
  border-radius: $radius-lg;
  margin-bottom: 8rpx;

  text {
    font-size: 30rpx;
    font-weight: 600;
    color: #fff;
  }

  &:active {
    opacity: 0.9;
    transform: scale(0.98);
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

// 动画
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes prizeReveal {
  0% {
    transform: scale(0.8);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10rpx);
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .scratch-modal-overlay,
  .scratch-modal-content,
  .prize-layer,
  .hint-icon,
  .spinning,
  .cover-cell {
    animation: none !important;
    transition: none !important;
  }
}
</style>
