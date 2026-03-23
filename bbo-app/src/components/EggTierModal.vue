<template>
  <view v-if="visible" class="egg-tier-overlay" @click="handleOverlayClick" @touchmove.prevent>
    <view class="egg-tier-content" :class="{ visible: contentVisible }" @click.stop @touchmove.stop>
      <!-- 关闭按钮 -->
      <view class="close-btn" @click="handleClose">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="header">
        <text class="title">{{ t('game.eggTier.title') }}</text>
        <view class="chances-badge">
          <text class="bi bi-ticket-perforated"></text>
          <text class="chances-text">{{ chances }}</text>
        </view>
      </view>

      <!-- 蛋分级选择 -->
      <view v-if="!selectedTier" class="tier-list">
        <view class="tier-subtitle">{{ t('game.eggTier.selectEgg') }}</view>
        <scroll-view scroll-x class="tier-scroll">
          <view
            v-for="tier in eggTiers"
            :key="tier.code"
            class="tier-card"
            :style="{ '--tier-color': tier.bg_color }"
            @click="handleSelectTier(tier)"
          >
            <view class="tier-icon">
              <text class="egg-emoji">{{ getEggEmoji(tier.code) }}</text>
            </view>
            <view class="tier-info">
              <text class="tier-name">{{ tier.name }}</text>
              <text class="tier-desc">{{ tier.description }}</text>
            </view>
            <view class="tier-min-order">
              <text class="bi bi-cart"></text>
              <text>${{ tier.min_order_amount }}</text>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 砸蛋界面 -->
      <view v-else class="smash-area">
        <!-- 返回选择 -->
        <view class="back-btn" @click="handleBackToSelect">
          <text class="bi bi-arrow-left"></text>
          <text>{{ selectedTier.name }}</text>
        </view>

        <!-- 蛋容器 -->
        <view class="eggs-container">
          <view
            v-for="(egg, index) in eggs"
            :key="index"
            class="egg-wrapper"
            :class="{
              cracked: egg.cracked,
              selected: selectedEggIndex === index,
              disabled: isSmashing || chances <= 0
            }"
            :style="{ '--egg-color': selectedTier.bg_color }"
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
          <text v-if="chances > 0 && !isSmashing">{{ t('game.eggTier.tapToSmash') }}</text>
          <text v-else-if="isSmashing">{{ t('game.smashing') }}</text>
          <text v-else class="no-chances">{{ t('game.noChances') }}</text>
        </view>

        <!-- 奖池预览 -->
        <view class="prize-pool">
          <view class="pool-header">
            <text class="bi bi-gift"></text>
            <text>{{ t('game.eggTier.prizePool') }}</text>
          </view>
          <view class="pool-list">
            <view
              v-for="prize in selectedTier.prizes"
              :key="prize.id"
              class="pool-item"
              :style="{ '--prize-color': prize.color }"
            >
              <view class="prize-dot"></view>
              <text class="prize-name">{{ prize.name }}</text>
              <text class="prize-prob">{{ (prize.probability * 100).toFixed(0) }}%</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部操作 -->
      <view class="footer">
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
import { ref, watch, nextTick, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { getEggTierList, smashEggTier, type EggTier, type EggTierPrize } from '@/api/game'
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
  (e: 'smash-complete', result: any): void
  (e: 'view-records'): void
  (e: 'view-rules'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)
const isSmashing = ref(false)
const selectedEggIndex = ref<number | null>(null)
const showResult = ref(false)
const winPrize = ref<EggTierPrize | null>(null)

// 蛋分级列表
const eggTiers = ref<EggTier[]>([])
const selectedTier = ref<EggTier | null>(null)

// 三个蛋的状态
const eggs = ref<Egg[]>([
  { cracked: false },
  { cracked: false },
  { cracked: false }
])

// 加载蛋分级列表
async function loadEggTiers() {
  try {
    const res = await getEggTierList()
    if (res.code === 0) {
      eggTiers.value = res.data
    }
  } catch (error) {
    console.error('Failed to load egg tiers:', error)
  }
}

// 监听 visible 变化
watch(() => props.visible, async (newVal) => {
  if (newVal) {
    // 重置状态
    selectedTier.value = null
    selectedEggIndex.value = null
    eggs.value = [
      { cracked: false },
      { cracked: false },
      { cracked: false }
    ]
    await loadEggTiers()
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// 获取蛋的表情
function getEggEmoji(code: string): string {
  const emojis: Record<string, string> = {
    bronze_egg: '🥚',
    silver_egg: '🥈',
    gold_egg: '🥇',
    diamond_egg: '💎'
  }
  return emojis[code] || '🥚'
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

// 获取金币飞出样式
function getCoinStyle(index: number) {
  const angle = (index - 1) * 45
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

// 选择蛋分级
function handleSelectTier(tier: EggTier) {
  selectedTier.value = tier
  eggs.value = [
    { cracked: false },
    { cracked: false },
    { cracked: false }
  ]
  selectedEggIndex.value = null
}

// 返回选择
function handleBackToSelect() {
  if (!isSmashing.value) {
    selectedTier.value = null
  }
}

// 点击蛋
async function handleEggClick(index: number) {
  if (isSmashing.value || props.chances <= 0 || !selectedTier.value) return

  // 如果已经有蛋被砸碎，不能再点击
  if (eggs.value.some(e => e.cracked)) return

  selectedEggIndex.value = index
  isSmashing.value = true

  try {
    const res = await smashEggTier(selectedTier.value.code)

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
      selectedEggIndex.value = null
      toast.error(res.msg || t('game.smashFailed'))
    }
  } catch (error: any) {
    isSmashing.value = false
    selectedEggIndex.value = null
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
  selectedEggIndex.value = null
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
  selectedEggIndex.value = null
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

.egg-tier-overlay {
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

.egg-tier-content {
  position: relative;
  width: 100%;
  max-width: 680rpx;
  max-height: 90vh;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  padding: 40rpx 32rpx;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.32, 0.72, 0, 1);
  overflow-y: auto;

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

.header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  margin-bottom: 32rpx;
}

.title {
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

// 蛋分级选择
.tier-list {
  margin-bottom: 24rpx;
}

.tier-subtitle {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
  margin-bottom: 24rpx;
}

.tier-scroll {
  white-space: nowrap;
  padding: 8rpx 0;
}

.tier-card {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
  width: 180rpx;
  padding: 24rpx 16rpx;
  margin-right: 16rpx;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
  border: 2rpx solid var(--tier-color, rgba(255, 255, 255, 0.2));
  border-radius: $radius-lg;
  cursor: pointer;
  transition: all 0.3s ease;

  &:active {
    transform: scale(0.95);
    background: rgba(255, 255, 255, 0.15);
  }
}

.tier-icon {
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--tier-color, rgba(255, 255, 255, 0.1));
  border-radius: 50%;

  .egg-emoji {
    font-size: 40rpx;
  }
}

.tier-info {
  text-align: center;
}

.tier-name {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: #fff;
  margin-bottom: 4rpx;
}

.tier-desc {
  display: block;
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.6);
  white-space: normal;
  word-break: break-word;
  line-height: 1.3;
}

.tier-min-order {
  display: flex;
  align-items: center;
  gap: 4rpx;
  padding: 8rpx 12rpx;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 16rpx;

  text {
    font-size: 20rpx;
    color: rgba(255, 255, 255, 0.8);
  }
}

// 砸蛋区域
.smash-area {
  margin-bottom: 24rpx;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 16rpx;
  background: rgba(255, 255, 255, 0.1);
  border-radius: $radius-lg;
  margin-bottom: 24rpx;
  width: fit-content;

  text {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
  }

  &:active {
    background: rgba(255, 255, 255, 0.2);
  }
}

.eggs-container {
  display: flex;
  justify-content: center;
  gap: 32rpx;
  padding: 24rpx 0 32rpx;
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
  background: linear-gradient(135deg, var(--egg-color, $gold) 0%, color-mix(in srgb, var(--egg-color, $gold) 80%, #000) 100%);
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
  background: linear-gradient(135deg, var(--egg-color, $gold) 0%, color-mix(in srgb, var(--egg-color, $gold) 80%, #000) 100%);

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
  margin-bottom: 24rpx;

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);

    &.no-chances {
      color: $warning;
    }
  }
}

// 奖池预览
.prize-pool {
  background: rgba(255, 255, 255, 0.05);
  border-radius: $radius-lg;
  padding: 16rpx 20rpx;
}

.pool-header {
  display: flex;
  align-items: center;
  gap: 8rpx;
  margin-bottom: 12rpx;

  text {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.8);
  }
}

.pool-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8rpx;
}

.pool-item {
  display: flex;
  align-items: center;
  gap: 6rpx;
  padding: 6rpx 12rpx;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 16rpx;
}

.prize-dot {
  width: 12rpx;
  height: 12rpx;
  background: var(--prize-color, $gold);
  border-radius: 50%;
}

.prize-name {
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.8);
}

.prize-prob {
  font-size: 18rpx;
  color: rgba(255, 255, 255, 0.5);
}

// 底部
.footer {
  margin-top: 24rpx;
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
  .egg-tier-overlay,
  .egg-tier-content,
  .egg-wrapper,
  .egg-half,
  .prize-reveal,
  .coin,
  .tier-card {
    animation: none;
    transition: none !important;
  }
}
</style>
