<template>
  <view class="game-page">
    <!-- 页面配置：弹窗打开时禁用页面滚动 -->
    <page-meta :page-style="pageStyle" />

    <!-- 导航栏 -->
    <NavBar :title="t('game.gameCenter')" />

    <!-- 加载中 -->
    <LoadingPage v-model="loading" />

    <!-- 页面内容 -->
    <scroll-view v-if="!loading" :scroll-y="!isAnyModalOpen" class="page-content">
      <!-- 中奖播报 -->
      <view class="marquee-section">
        <WinnerMarquee ref="marqueeRef" />
      </view>

      <!-- 用户积分 -->
      <view class="points-card">
        <view class="points-info">
          <text class="points-label">{{ t('game.myPoints') }}</text>
          <text class="points-value">{{ userPoints }}</text>
        </view>
        <view class="points-action" @click="goToPointsHistory">
          <text>{{ t('game.viewHistory') }}</text>
          <text class="bi bi-chevron-right"></text>
        </view>
      </view>

      <!-- 每日登录奖励 -->
      <view class="daily-login-card">
        <view class="daily-login-info">
          <view class="daily-login-icon">
            <text class="bi bi-gift-fill"></text>
          </view>
          <view class="daily-login-text">
            <text class="daily-login-title">{{ t('game.dailyLoginReward') }}</text>
            <text class="daily-login-desc">{{ t('game.dailyLoginDescAll') }}</text>
          </view>
        </view>
        <view
          class="daily-login-btn"
          :class="{ claimed: dailyLoginStatus.claimed }"
          @click="handleClaimDailyLogin"
        >
          <text v-if="dailyLoginStatus.claimed">{{ t('game.claimed') }}</text>
          <text v-else>+{{ dailyLoginStatus.total_chances }} {{ t('game.chances') }}</text>
        </view>
      </view>

      <!-- 游戏列表 -->
      <view class="games-section">
        <view class="section-header">
          <text class="section-title">{{ t('game.luckyGames') }}</text>
        </view>

        <view class="games-grid">
          <!-- 幸运转盘 -->
          <view
            v-for="game in games"
            :key="game.code"
            class="game-card"
            :class="{ disabled: game.status !== 1 }"
            @click="handleGameClick(game)"
          >
            <view class="game-icon" :style="{ background: getGameGradient(game.code) }">
              <text class="bi" :class="getGameIcon(game.code)"></text>
            </view>
            <view class="game-info">
              <text class="game-name">{{ game.name }}</text>
              <text class="game-desc">{{ game.description || getDefaultDesc(game.code) }}</text>
            </view>
            <view v-if="getGameChances(game.code) > 0" class="game-badge">
              <text>{{ getGameChances(game.code) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 签到区域 -->
      <view class="checkin-section">
        <view class="section-header">
          <text class="section-title">{{ t('game.dailyCheckin') }}</text>
          <view class="checkin-status">
            <text class="bi bi-calendar-check"></text>
            <text>{{ t('game.continuousDays').replace('[DAYS]', String(checkinStatus.continuous_days)) }}</text>
          </view>
        </view>

        <view class="checkin-card">
          <view class="checkin-calendar">
            <view
              v-for="(day, index) in checkinCalendar"
              :key="index"
              class="calendar-day"
              :class="{
                checked: day.checked,
                today: isToday(day.date),
                future: isFuture(day.date)
              }"
            >
              <text class="day-label">{{ getDayLabel(day.date, index) }}</text>
              <view class="day-icon">
                <text v-if="day.checked" class="bi bi-check-circle-fill"></text>
                <text v-else-if="day.extra_reward" class="bi bi-gift-fill"></text>
                <text v-else>{{ day.reward_points }}</text>
              </view>
              <text v-if="day.extra_reward" class="extra-reward">{{ getExtraRewardLabel(day.extra_reward) }}</text>
            </view>
          </view>

          <view
            class="checkin-btn"
            :class="{ disabled: checkinStatus.checked_today }"
            @click="handleCheckin"
          >
            <text v-if="checkinStatus.checked_today">{{ t('game.checkedIn') }}</text>
            <text v-else>{{ t('game.checkinNow') }} +{{ checkinStatus.today_reward?.points || 0 }}</text>
          </view>
        </view>
      </view>

      <!-- 我的奖品 -->
      <view class="prizes-section">
        <view class="section-header">
          <text class="section-title">{{ t('game.myPrizes') }}</text>
          <view class="view-all" @click="goToPrizeHistory">
            <text>{{ t('common.viewAll') }}</text>
            <text class="bi bi-chevron-right"></text>
          </view>
        </view>

        <view v-if="recentPrizes.length > 0" class="prizes-list">
          <view
            v-for="prize in recentPrizes"
            :key="prize.id"
            class="prize-item"
          >
            <view class="prize-icon" :style="{ background: getPrizeColor(prize.prize_type) }">
              <text class="bi" :class="getPrizeIcon(prize.prize_type)"></text>
            </view>
            <view class="prize-info">
              <text class="prize-name">{{ getPrizeDisplayName(prize) }}</text>
              <text class="prize-time">{{ formatTime(prize.created_at) }}</text>
            </view>
            <view class="prize-status" :class="prize.status">
              <text>{{ getPrizeStatusText(prize.status) }}</text>
            </view>
          </view>
        </view>
        <view v-else class="empty-prizes">
          <text class="bi bi-gift"></text>
          <text>{{ t('game.noPrizes') }}</text>
        </view>
      </view>
    </scroll-view>

    <!-- 幸运转盘弹窗 -->
    <LuckyWheelModal
      v-model:visible="showWheelModal"
      v-model:chances="wheelChances"
      :prizes="wheelPrizes"
      @spin-complete="handleSpinComplete"
      @view-records="goToPrizeHistory"
      @view-rules="showRulesDialog"
    />

    <!-- 砸金蛋弹窗 -->
    <GoldenEggModal
      v-model:visible="showEggModal"
      v-model:chances="eggChances"
      @smash-complete="handleSmashComplete"
      @view-records="goToPrizeHistory"
      @view-rules="showEggRulesDialog"
    />

    <!-- 刮刮卡弹窗 -->
    <ScratchCardModal
      v-model:visible="showScratchModal"
      v-model:chances="scratchChances"
      @scratch-complete="handleScratchComplete"
      @view-records="goToPrizeHistory"
      @view-rules="showScratchRulesDialog"
    />

    <!-- 分享弹窗 -->
    <ShareModal
      v-model:visible="showShareModal"
      type="game"
      :show-stats="true"
      @shared="handleShared"
    />

    <!-- 规则弹窗 -->
    <GameRulesModal
      v-model:visible="showRulesModal"
      :title="t('game.rules')"
      :content="rulesContent"
      :game-type="rulesGameType"
    />

    <!-- 分享浮窗按钮 -->
    <view class="share-float-btn" @click="showShareModal = true">
      <text class="bi bi-share-fill"></text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { toast } from '@/composables/useToast'
import { useAppStore } from '@/store/modules/app'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'
import WinnerMarquee from '@/components/WinnerMarquee.vue'
import LuckyWheelModal from '@/components/LuckyWheelModal.vue'
import GoldenEggModal from '@/components/GoldenEggModal.vue'
import ScratchCardModal from '@/components/ScratchCardModal.vue'
import ShareModal from '@/components/ShareModal.vue'
import GameRulesModal from '@/components/GameRulesModal.vue'
import {
  getGameList,
  getGamePrizes,
  getUserGameChances,
  getUserPoints,
  getUserGameLogs,
  getCheckinStatus,
  doCheckin,
  getDailyLoginStatus,
  claimDailyLoginReward,
  type Game,
  type Prize,
  type UserGameChances,
  type GameLog,
  type CheckinStatus,
  type PlayResult,
  type DailyLoginStatus
} from '@/api/game'
// 日期辅助函数（替代 dayjs）
function parseDate(dateStr: string): Date {
  return new Date(dateStr)
}

function formatDateStr(date: Date, format: string): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')

  if (format === 'MM-DD HH:mm') {
    return `${month}-${day} ${hours}:${minutes}`
  }
  if (format === 'MM/DD') {
    return `${month}/${day}`
  }
  return `${month}-${day}`
}

function isSameDay(date1: Date, date2: Date): boolean {
  return date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate()
}

function isAfterDay(date1: Date, date2: Date): boolean {
  const d1 = new Date(date1.getFullYear(), date1.getMonth(), date1.getDate())
  const d2 = new Date(date2.getFullYear(), date2.getMonth(), date2.getDate())
  return d1.getTime() > d2.getTime()
}

const { t } = useI18n()
const appStore = useAppStore()

// 状态
const loading = ref(true)
const games = ref<Game[]>([])
const userPoints = ref(0)
const gameChances = ref<UserGameChances[]>([])
const recentPrizes = ref<GameLog[]>([])
const checkinStatus = ref<CheckinStatus>({
  checked_today: false,
  continuous_days: 0,
  today_reward: { points: 5 },
  calendar: []
})

// 转盘相关
const showWheelModal = ref(false)
const wheelPrizes = ref<Prize[]>([])
const wheelChances = ref(0)

// 砸金蛋相关
const showEggModal = ref(false)
const eggChances = ref(0)

// 刮刮卡相关
const showScratchModal = ref(false)
const scratchChances = ref(0)

// 每日登录奖励
const dailyLoginStatus = ref<DailyLoginStatus>({
  claimed: false,
  rewards: [],
  total_chances: 3
})

// 分享相关
const showShareModal = ref(false)

// 规则弹窗相关
const showRulesModal = ref(false)
const rulesGameType = ref<'wheel' | 'egg' | 'scratch'>('wheel')
const rulesContent = ref('')

// 计算是否有任何弹窗打开（用于禁用页面滚动）
const isAnyModalOpen = computed(() => {
  return showWheelModal.value || showEggModal.value || showScratchModal.value || showShareModal.value || showRulesModal.value
})

// 页面样式（控制滚动）
const pageStyle = computed(() => {
  return isAnyModalOpen.value ? 'overflow: hidden;' : 'overflow: auto;'
})

const marqueeRef = ref()

// 签到日历（取前 7 天）
const checkinCalendar = computed(() => {
  const calendar = checkinStatus.value.calendar || []
  return calendar.slice(0, 7)
})

// 获取游戏次数
function getGameChances(gameCode: string): number {
  const chance = gameChances.value.find(c => c.game_code === gameCode)
  return chance?.chances || 0
}

// 获取游戏图标
function getGameIcon(code: string): string {
  const icons: Record<string, string> = {
    wheel: 'bi-bullseye',
    egg: 'bi-egg-fill',
    scratch: 'bi-credit-card-fill',
    checkin: 'bi-calendar-check-fill'
  }
  return icons[code] || 'bi-controller'
}

// 获取游戏渐变色
function getGameGradient(code: string): string {
  const gradients: Record<string, string> = {
    wheel: 'linear-gradient(135deg, #FFD700 0%, #FFA500 100%)',
    egg: 'linear-gradient(135deg, #FF6B35 0%, #FF4444 100%)',
    scratch: 'linear-gradient(135deg, #A855F7 0%, #7C3AED 100%)',
    checkin: 'linear-gradient(135deg, #10B981 0%, #059669 100%)'
  }
  return gradients[code] || 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)'
}

// 获取默认描述
function getDefaultDesc(code: string): string {
  const descs: Record<string, string> = {
    wheel: t('game.wheelDesc'),
    egg: t('game.eggDesc'),
    scratch: t('game.scratchDesc'),
    checkin: t('game.checkinDesc')
  }
  return descs[code] || ''
}

// 获取奖品图标
function getPrizeIcon(type: string): string {
  const icons: Record<string, string> = {
    points: 'bi-coin',
    cash: 'bi-cash',
    coupon: 'bi-ticket-perforated',
    goods: 'bi-box-seam',
    chance: 'bi-arrow-repeat'
  }
  return icons[type] || 'bi-gift'
}

// 获取奖品颜色
function getPrizeColor(type: string): string {
  const colors: Record<string, string> = {
    points: '#FFD700',
    cash: '#10B981',
    coupon: '#FF6B35',
    goods: '#A855F7',
    chance: '#3B82F6'
  }
  return colors[type] || '#94A3B8'
}

// 获取签到额外奖励显示文本
function getExtraRewardLabel(extra: string): string {
  const map: Record<string, string> = {
    '5% coupon': t('game.reward5Coupon'),
    'silver_box': t('game.rewardSilverBox'),
    'gold_box': t('game.rewardGoldBox'),
    'diamond_box': t('game.rewardDiamondBox'),
  }
  return map[extra] || extra
}

// 获取奖品状态文本
function getPrizeStatusText(status: string): string {
  const texts: Record<string, string> = {
    pending: t('game.statusPending'),
    claimed: t('game.statusClaimed'),
    expired: t('game.statusExpired')
  }
  return texts[status] || status
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

// 格式化时间
function formatTime(time: string): string {
  return formatDateStr(parseDate(time), 'MM-DD HH:mm')
}

// 是否是今天
function isToday(date: string): boolean {
  return isSameDay(parseDate(date), new Date())
}

// 是否是未来
function isFuture(date: string): boolean {
  return isAfterDay(parseDate(date), new Date())
}

// 获取日期标签
function getDayLabel(date: string, index: number): string {
  if (index === 0) return t('game.today')
  return formatDateStr(parseDate(date), 'MM/DD')
}

// 加载数据
async function loadData() {
  loading.value = true

  try {
    // 并行请求
    const [gamesRes, pointsRes, chancesRes, logsRes, checkinRes, dailyLoginRes] = await Promise.all([
      getGameList(),
      getUserPoints(),
      getUserGameChances(),
      getUserGameLogs({ pageSize: 5 }),
      getCheckinStatus(),
      getDailyLoginStatus()
    ])

    if (gamesRes.code === 0) {
      games.value = gamesRes.data || []
    }

    if (pointsRes.code === 0) {
      userPoints.value = pointsRes.data?.balance || 0
    }

    if (chancesRes.code === 0) {
      gameChances.value = chancesRes.data || []
      // 更新转盘次数
      const wheelChance = gameChances.value.find(c => c.game_code === 'wheel')
      wheelChances.value = wheelChance?.chances || 0
      // 更新砸金蛋次数
      const eggChance = gameChances.value.find(c => c.game_code === 'egg')
      eggChances.value = eggChance?.chances || 0
      // 更新刮刮卡次数
      const scratchChance = gameChances.value.find(c => c.game_code === 'scratch')
      scratchChances.value = scratchChance?.chances || 0
    }

    if (logsRes.code === 0) {
      recentPrizes.value = logsRes.data?.list || []
    }

    if (checkinRes.code === 0) {
      checkinStatus.value = checkinRes.data
    }

    if (dailyLoginRes.code === 0) {
      dailyLoginStatus.value = dailyLoginRes.data
    }
  } catch (error) {
    console.error('Failed to load game data:', error)
  } finally {
    loading.value = false
  }
}

// 加载转盘奖品
async function loadWheelPrizes() {
  try {
    const res = await getGamePrizes('wheel')
    if (res.code === 0) {
      wheelPrizes.value = res.data || []
    }
  } catch (error) {
    console.error('Failed to load wheel prizes:', error)
  }
}

// 点击游戏
async function handleGameClick(game: Game) {
  if (game.status !== 1) {
    toast.info(t('game.gameNotAvailable'))
    return
  }

  if (game.code === 'wheel') {
    // 加载奖品
    if (wheelPrizes.value.length === 0) {
      uni.showLoading({ title: '' })
      await loadWheelPrizes()
      uni.hideLoading()
    }
    showWheelModal.value = true
  } else if (game.code === 'egg') {
    showEggModal.value = true
  } else if (game.code === 'scratch') {
    showScratchModal.value = true
  }
}

// 签到
async function handleCheckin() {
  if (checkinStatus.value.checked_today) return

  try {
    uni.showLoading({ title: '' })
    const res = await doCheckin()
    uni.hideLoading()

    if (res.code === 0) {
      toast.success(t('game.checkinSuccess').replace('[POINTS]', String(res.data.reward_points)))

      // 更新状态
      checkinStatus.value.checked_today = true
      checkinStatus.value.continuous_days = res.data.continuous_days
      userPoints.value += res.data.reward_points

      // 刷新签到日历
      const checkinRes = await getCheckinStatus()
      if (checkinRes.code === 0) {
        checkinStatus.value = checkinRes.data
      }
    }
  } catch (error: any) {
    uni.hideLoading()
    toast.error(error.message || t('game.checkinFailed'))
  }
}

// 领取每日登录奖励
async function handleClaimDailyLogin() {
  if (dailyLoginStatus.value.claimed) return

  try {
    uni.showLoading({ title: '' })
    const res = await claimDailyLoginReward()
    uni.hideLoading()

    if (res.code === 0) {
      toast.success(t('game.dailyLoginSuccessAll').replace('[CHANCES]', String(res.data.chances_added)))

      // 更新状态
      dailyLoginStatus.value.claimed = true

      // 更新各游戏次数
      if (res.data.chances.wheel !== undefined) {
        wheelChances.value = res.data.chances.wheel
      }
      if (res.data.chances.egg !== undefined) {
        eggChances.value = res.data.chances.egg
      }
      if (res.data.chances.scratch !== undefined) {
        scratchChances.value = res.data.chances.scratch
      }

      // 更新游戏次数列表
      const chancesRes = await getUserGameChances()
      if (chancesRes.code === 0) {
        gameChances.value = chancesRes.data || []
      }
    }
  } catch (error: any) {
    uni.hideLoading()
    toast.error(error.message || t('game.dailyLoginFailed'))
  }
}

// 转盘完成
function handleSpinComplete(result: PlayResult) {
  // 刷新积分
  if (result.prize_type === 'points') {
    userPoints.value += result.prize_value
  }

  // 刷新跑马灯
  marqueeRef.value?.refresh()
}

// 砸金蛋完成
function handleSmashComplete(result: PlayResult) {
  // 刷新积分
  if (result.prize_type === 'points') {
    userPoints.value += result.prize_value
  }

  // 刷新跑马灯
  marqueeRef.value?.refresh()
}

// 刮刮卡完成
function handleScratchComplete(result: PlayResult) {
  // 刷新积分
  if (result.prize_type === 'points') {
    userPoints.value += result.prize_value
  }

  // 刷新跑马灯
  marqueeRef.value?.refresh()
}

// 显示刮刮卡规则弹窗
function showScratchRulesDialog() {
  rulesGameType.value = 'scratch'
  rulesContent.value = t('game.scratchRulesContent')
  showRulesModal.value = true
}

// 分享完成
function handleShared(channel: string) {
  toast.success(t('share.copySuccess'))
  // 刷新游戏次数
  getUserGameChances().then(res => {
    if (res.code === 0) {
      gameChances.value = res.data || []
      const wheelChance = gameChances.value.find(c => c.game_code === 'wheel')
      wheelChances.value = wheelChance?.chances || 0
    }
  })
}

// 跳转积分历史
function goToPointsHistory() {
  uni.navigateTo({ url: '/pages/game/points' })
}

// 跳转奖品历史
function goToPrizeHistory() {
  uni.navigateTo({ url: '/pages/game/prizes' })
}

// 显示转盘规则弹窗
function showRulesDialog() {
  rulesGameType.value = 'wheel'
  rulesContent.value = t('game.wheelRulesContent')
  showRulesModal.value = true
}

// 显示砸金蛋规则弹窗
function showEggRulesDialog() {
  rulesGameType.value = 'egg'
  rulesContent.value = t('game.eggRulesContent')
  showRulesModal.value = true
}

// 页面加载
onMounted(() => {
  loadData()
})

// 页面显示时刷新
onShow(() => {
  if (!loading.value) {
    // 仅刷新次数和积分
    getUserGameChances().then(res => {
      if (res.code === 0) {
        gameChances.value = res.data || []
        const wheelChance = gameChances.value.find(c => c.game_code === 'wheel')
        wheelChances.value = wheelChance?.chances || 0
        const eggChance = gameChances.value.find(c => c.game_code === 'egg')
        eggChances.value = eggChance?.chances || 0
        const scratchChance = gameChances.value.find(c => c.game_code === 'scratch')
        scratchChances.value = scratchChance?.chances || 0
      }
    })

    getUserPoints().then(res => {
      if (res.code === 0) {
        userPoints.value = res.data?.balance || 0
      }
    })
  }
})
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
$success: #10B981;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.game-page {
  min-height: 100vh;
  background: $background;
  display: flex;
  flex-direction: column;
}

.page-content {
  flex: 1;
  padding: 24rpx;
  width: auto;
}

.marquee-section {
  margin-bottom: 24rpx;
}

.points-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
  border-radius: $radius-xl;
  padding: 32rpx;
  margin-bottom: 32rpx;

  .points-info {
    display: flex;
    flex-direction: column;
  }

  .points-label {
    font-size: 24rpx;
    color: rgba(#1a1a2e, 0.7);
    margin-bottom: 8rpx;
  }

  .points-value {
    font-size: 56rpx;
    font-weight: 700;
    color: #1a1a2e;
  }

  .points-action {
    display: flex;
    align-items: center;
    gap: 4rpx;
    padding: 16rpx 24rpx;
    background: rgba(#1a1a2e, 0.1);
    border-radius: $radius-lg;

    text {
      font-size: 24rpx;
      color: #1a1a2e;
    }
  }
}

.daily-login-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  background: $surface;
  border-radius: $radius-xl;
  padding: 24rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

  .daily-login-info {
    display: flex;
    align-items: center;
    gap: 16rpx;
    flex: 1;
    min-width: 0; // 允许收缩
  }

  .daily-login-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF6B35 0%, #FF4444 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    text {
      font-size: 32rpx;
      color: #fff;
    }
  }

  .daily-login-text {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0; // 允许文字截断
  }

  .daily-login-title {
    font-size: 28rpx;
    font-weight: 600;
    color: $text-primary;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .daily-login-desc {
    font-size: 22rpx;
    color: $text-muted;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .daily-login-btn {
    padding: 16rpx 24rpx;
    background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
    border-radius: $radius-lg;
    transition: all 0.2s ease;
    flex-shrink: 0;
    white-space: nowrap;

    text {
      font-size: 26rpx;
      font-weight: 600;
      color: #1a1a2e;
    }

    &:active:not(.claimed) {
      transform: scale(0.95);
    }

    &.claimed {
      background: $border;

      text {
        color: $text-muted;
      }
    }
  }
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
}

.section-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-primary;
}

.view-all {
  display: flex;
  align-items: center;
  gap: 4rpx;

  text {
    font-size: 24rpx;
    color: $text-secondary;
  }
}

.games-section {
  margin-bottom: 32rpx;
}

.games-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16rpx;
}

.game-card {
  position: relative;
  background: $surface;
  border-radius: $radius-xl;
  padding: 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
  }

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  .game-icon {
    width: 96rpx;
    height: 96rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    text {
      font-size: 40rpx;
      color: #fff;
    }
  }

  .game-info {
    text-align: center;

    .game-name {
      display: block;
      font-size: 28rpx;
      font-weight: 600;
      color: $text-primary;
      margin-bottom: 4rpx;
    }

    .game-desc {
      display: block;
      font-size: 22rpx;
      color: $text-muted;
    }
  }

  .game-badge {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    min-width: 40rpx;
    height: 40rpx;
    padding: 0 12rpx;
    background: #EF4444;
    border-radius: 20rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    text {
      font-size: 22rpx;
      font-weight: 600;
      color: #fff;
    }
  }
}

.checkin-section {
  margin-bottom: 32rpx;

  .checkin-status {
    display: flex;
    align-items: center;
    gap: 8rpx;

    text {
      font-size: 24rpx;
      color: $success;
    }
  }
}

.checkin-card {
  background: $surface;
  border-radius: $radius-xl;
  padding: 24rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.checkin-calendar {
  display: flex;
  gap: 8rpx;
  margin-bottom: 24rpx;
  overflow-x: auto;

  &::-webkit-scrollbar {
    display: none;
  }
}

.calendar-day {
  flex: 1;
  min-width: 80rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
  padding: 16rpx 8rpx;
  border-radius: $radius-lg;
  background: $background;
  transition: all 0.2s ease;

  &.checked {
    background: rgba($success, 0.1);

    .day-icon text {
      color: $success;
    }
  }

  &.today {
    border: 2rpx solid $primary;
  }

  &.future {
    opacity: 0.5;
  }

  .day-label {
    font-size: 20rpx;
    color: $text-muted;
  }

  .day-icon {
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    text {
      font-size: 24rpx;
      color: $text-secondary;
    }
  }

  .extra-reward {
    font-size: 18rpx;
    color: $gold;
    font-weight: 600;
  }
}

.checkin-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 88rpx;
  background: linear-gradient(135deg, $success 0%, #059669 100%);
  border-radius: $radius-lg;
  transition: all 0.2s ease;

  text {
    font-size: 30rpx;
    font-weight: 600;
    color: #fff;
  }

  &:active:not(.disabled) {
    transform: scale(0.98);
  }

  &.disabled {
    background: $border;

    text {
      color: $text-muted;
    }
  }
}

.prizes-section {
  margin-bottom: 48rpx;
}

.prizes-list {
  background: $surface;
  border-radius: $radius-xl;
  overflow: hidden;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.prize-item {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx;
  border-bottom: 1rpx solid $border;

  &:last-child {
    border-bottom: none;
  }

  .prize-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    text {
      font-size: 32rpx;
      color: #fff;
    }
  }

  .prize-info {
    flex: 1;

    .prize-name {
      display: block;
      font-size: 28rpx;
      font-weight: 500;
      color: $text-primary;
      margin-bottom: 4rpx;
    }

    .prize-time {
      display: block;
      font-size: 22rpx;
      color: $text-muted;
    }
  }

  .prize-status {
    padding: 8rpx 16rpx;
    border-radius: 16rpx;
    font-size: 22rpx;

    &.pending {
      background: rgba($primary, 0.1);
      color: $primary;
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
}

.empty-prizes {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64rpx;
  background: $surface;
  border-radius: $radius-xl;

  text {
    font-size: 28rpx;
    color: $text-muted;

    &:first-child {
      font-size: 64rpx;
      margin-bottom: 16rpx;
    }
  }
}

.share-float-btn {
  position: fixed;
  right: 32rpx;
  bottom: 200rpx;
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #FF6B35 0%, #FF4444 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 24rpx rgba(255, 107, 53, 0.4);
  z-index: 100;
  animation: pulse 2s infinite;

  text {
    font-size: 40rpx;
    color: #fff;
  }

  &:active {
    transform: scale(0.95);
  }
}

@keyframes pulse {
  0% {
    box-shadow: 0 8rpx 24rpx rgba(255, 107, 53, 0.4);
  }
  50% {
    box-shadow: 0 8rpx 32rpx rgba(255, 107, 53, 0.6);
  }
  100% {
    box-shadow: 0 8rpx 24rpx rgba(255, 107, 53, 0.4);
  }
}
</style>
