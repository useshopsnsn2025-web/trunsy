<template>
  <view v-if="visible" class="egg-modal-overlay" @click="handleOverlayClick" @touchmove.prevent>
    <view class="egg-modal-content" :class="{ visible: contentVisible }" @click.stop @touchmove.stop>
      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="egg-header">
        <text class="egg-title">{{ t('game.goldenEgg') }}</text>
        <view class="chances-badge">
          <text class="bi bi-ticket-perforated"></text>
          <text class="chances-text">{{ chances }}</text>
        </view>
      </view>

      <!-- 金蛋容器 -->
      <view class="eggs-container">
        <view
          v-for="(egg, index) in eggs"
          :key="index"
          class="egg-wrapper"
          :class="{
            cracked: egg.cracked,
            selected: selectedEgg === index,
            disabled: isSmashing || chances <= 0
          }"
          @click="handleEggClick(index)"
        >
          <!-- 完整的蛋 -->
          <view v-if="!egg.cracked" class="egg">
            <view class="egg-body">
              <view class="egg-shine"></view>
            </view>
            <view class="egg-shadow"></view>
          </view>

          <!-- 破碎的蛋 -->
          <view v-else class="egg-cracked">
            <view class="egg-half egg-left"></view>
            <view class="egg-half egg-right"></view>
            <view class="prize-reveal" :style="{ background: winPrize?.color || '#FFD700' }">
              <text class="bi" :class="getPrizeIcon()"></text>
            </view>
            <!-- 金币飞出效果 -->
            <view class="coins-container">
              <view v-for="i in 8" :key="'coin-' + i" class="coin" :style="getCoinStyle(i)"></view>
            </view>
          </view>

          <!-- 蛋的序号 -->
          <view class="egg-number">
            <text>{{ index + 1 }}</text>
          </view>
        </view>
      </view>

      <!-- 提示信息 -->
      <view class="egg-tip">
        <text v-if="chances > 0 && !isSmashing">{{ t('game.selectEggTip') }}</text>
        <text v-else-if="isSmashing">{{ t('game.smashing') }}</text>
        <text v-else class="no-chances">{{ t('game.noChances') }}</text>
      </view>

      <!-- 底部操作 -->
      <view class="egg-footer">
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
      :show-play-again="chances > 0"
      @close="handleResultClose"
      @again="handlePlayAgain"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { playEgg, type Prize, type PlayResult } from '@/api/game'
import { toast } from '@/composables/useToast'
import PrizeResultModal from './PrizeResultModal.vue'

interface Egg {
  cracked: boolean
}

const props = withDefaults(defineProps<{
  visible: boolean
  chances: number
}>(), {
  chances: 0
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'update:chances', value: number): void
  (e: 'smash-complete', result: PlayResult): void
  (e: 'view-records'): void
  (e: 'view-rules'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)
const isSmashing = ref(false)
const selectedEgg = ref<number | null>(null)
const showResult = ref(false)
const winPrize = ref<Prize | null>(null)

// 三个金蛋的状态
const eggs = ref<Egg[]>([
  { cracked: false },
  { cracked: false },
  { cracked: false }
])

// 监听 visible 变化
watch(() => props.visible, (newVal) => {
  if (newVal) {
    // 重置蛋的状态
    eggs.value = [
      { cracked: false },
      { cracked: false },
      { cracked: false }
    ]
    selectedEgg.value = null
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

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

// 获取金币飞出样式
function getCoinStyle(index: number) {
  const angle = (index - 1) * 45 // 8个金币均匀分布
  const distance = 60 + Math.random() * 40
  const delay = Math.random() * 0.2
  const size = 16 + Math.random() * 8

  return {
    '--angle': `${angle}deg`,
    '--distance': `${distance}rpx`,
    '--delay': `${delay}s`,
    '--size': `${size}rpx`
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
  if (!isSmashing.value) {
    close()
  }
}

// 关闭按钮
function handleClose() {
  if (!isSmashing.value) {
    close()
  }
}

// 点击金蛋
async function handleEggClick(index: number) {
  if (isSmashing.value || props.chances <= 0) return

  // 如果已经有蛋被砸碎，不能再点击
  if (eggs.value.some(e => e.cracked)) return

  selectedEgg.value = index
  isSmashing.value = true

  try {
    // 调用 API
    const res = await playEgg(index)

    if (res.code === 0) {
      const result = res.data

      // 延迟一下再显示破碎效果
      setTimeout(() => {
        eggs.value[index].cracked = true
        winPrize.value = result.prize

        // 更新剩余次数
        emit('update:chances', result.remaining_chances)

        // 显示结果弹窗
        setTimeout(() => {
          isSmashing.value = false
          showResult.value = true
          emit('smash-complete', result)
        }, 1000)
      }, 500)
    } else {
      isSmashing.value = false
      selectedEgg.value = null
      toast.error(res.msg || t('game.smashFailed'))
    }
  } catch (error: any) {
    isSmashing.value = false
    selectedEgg.value = null
    toast.error(error.message || t('game.smashFailed'))
  }
}

// 结果弹窗关闭
function handleResultClose() {
  showResult.value = false
  winPrize.value = null
  // 重置蛋的状态
  eggs.value = [
    { cracked: false },
    { cracked: false },
    { cracked: false }
  ]
  selectedEgg.value = null
}

// 再玩一次
function handlePlayAgain() {
  showResult.value = false
  winPrize.value = null
  // 重置蛋的状态
  eggs.value = [
    { cracked: false },
    { cracked: false },
    { cracked: false }
  ]
  selectedEgg.value = null
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

.egg-modal-overlay {
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

.egg-modal-content {
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

.egg-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 48rpx;
}

.egg-title {
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

.eggs-container {
  display: flex;
  justify-content: center;
  gap: 32rpx;
  padding: 24rpx 0 48rpx;
}

.egg-wrapper {
  position: relative;
  width: 160rpx;
  height: 200rpx;
  cursor: pointer;
  transition: transform 0.3s ease;

  &:active:not(.disabled):not(.cracked) {
    transform: scale(0.95);
  }

  &.selected:not(.cracked) {
    animation: shake 0.5s ease;
  }

  &.disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.egg {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.egg-body {
  position: relative;
  width: 120rpx;
  height: 160rpx;
  background: linear-gradient(135deg, $gold 0%, #FFA500 50%, #FF8C00 100%);
  border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  box-shadow:
    0 8rpx 24rpx rgba(0, 0, 0, 0.3),
    inset 0 -8rpx 16rpx rgba(0, 0, 0, 0.2),
    inset 0 8rpx 16rpx rgba(255, 255, 255, 0.3);
  overflow: hidden;
}

.egg-shine {
  position: absolute;
  top: 16rpx;
  left: 20rpx;
  width: 40rpx;
  height: 60rpx;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.6) 0%, transparent 100%);
  border-radius: 50%;
  transform: rotate(-30deg);
}

.egg-shadow {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100rpx;
  height: 20rpx;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 50%;
  filter: blur(8rpx);
}

.egg-number {
  position: absolute;
  bottom: -40rpx;
  left: 50%;
  transform: translateX(-50%);
  width: 48rpx;
  height: 48rpx;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: 24rpx;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
  }
}

// 破碎的蛋
.egg-cracked {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.egg-half {
  position: absolute;
  width: 60rpx;
  height: 80rpx;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);

  &.egg-left {
    left: 20rpx;
    border-radius: 50% 0 0 50% / 60% 0 0 40%;
    transform: rotate(-20deg);
    animation: eggLeftFall 0.5s ease-out forwards;
  }

  &.egg-right {
    right: 20rpx;
    border-radius: 0 50% 50% 0 / 0 60% 40% 0;
    transform: rotate(20deg);
    animation: eggRightFall 0.5s ease-out forwards;
  }
}

.prize-reveal {
  position: absolute;
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: prizePopup 0.5s ease-out 0.3s forwards;
  opacity: 0;
  transform: scale(0);
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.3);

  text {
    font-size: 40rpx;
    color: #fff;
  }
}

// 金币飞出容器
.coins-container {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.coin {
  position: absolute;
  top: 50%;
  left: 50%;
  width: var(--size);
  height: var(--size);
  background: $gold;
  border-radius: 50%;
  box-shadow: inset 0 2rpx 4rpx rgba(255, 255, 255, 0.5);
  animation: coinFly 0.8s ease-out var(--delay) forwards;
  opacity: 0;
}

.egg-tip {
  text-align: center;
  margin-bottom: 32rpx;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);

    &.no-chances {
      color: $warning;
    }
  }
}

.egg-footer {
  margin-top: 16rpx;
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

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-8rpx) rotate(-2deg); }
  40% { transform: translateX(8rpx) rotate(2deg); }
  60% { transform: translateX(-6rpx) rotate(-1deg); }
  80% { transform: translateX(6rpx) rotate(1deg); }
}

@keyframes eggLeftFall {
  0% {
    transform: rotate(-20deg) translateY(0);
    opacity: 1;
  }
  100% {
    transform: rotate(-45deg) translateY(40rpx) translateX(-20rpx);
    opacity: 0.8;
  }
}

@keyframes eggRightFall {
  0% {
    transform: rotate(20deg) translateY(0);
    opacity: 1;
  }
  100% {
    transform: rotate(45deg) translateY(40rpx) translateX(20rpx);
    opacity: 0.8;
  }
}

@keyframes prizePopup {
  0% {
    opacity: 0;
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes coinFly {
  0% {
    opacity: 1;
    transform: translate(-50%, -50%) rotate(0deg);
  }
  100% {
    opacity: 0;
    transform:
      translate(
        calc(-50% + cos(var(--angle)) * var(--distance)),
        calc(-50% + sin(var(--angle)) * var(--distance))
      )
      rotate(720deg);
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .egg-modal-overlay,
  .egg-modal-content,
  .egg-wrapper,
  .egg-half,
  .prize-reveal,
  .coin {
    animation: none;
    transition: none !important;
  }
}
</style>
