<template>
  <view class="page">
    <!-- 加载中 -->
    <view v-if="loading" class="loading-container">
      <view class="spinner"></view>
      <text class="loading-text">{{ t('common.loading') }}</text>
    </view>

    <!-- 错误状态 -->
    <view v-else-if="error" class="error-container">
      <text class="bi bi-exclamation-circle error-icon"></text>
      <text class="error-title">{{ t('share.linkExpired') }}</text>
      <text class="error-desc">{{ t('share.linkExpiredDesc') }}</text>
      <button class="primary-btn" @click="goHome">{{ t('common.goHome') }}</button>
    </view>

    <!-- 成功状态 - 分享内容预览 -->
    <view v-else class="content-container">
      <!-- 顶部 Logo -->
      <view class="logo-section">
        <image class="logo" src="/static/logo.png" mode="aspectFit" />
        <text class="app-name">TURNSY</text>
      </view>

      <!-- 邀请信息 -->
      <view class="invite-card">
        <text class="invite-title">{{ t('share.inviteTitle') }}</text>
        <text class="invite-desc">{{ t('share.inviteDesc') }}</text>

        <!-- 奖励展示 -->
        <view class="rewards-section">
          <text class="rewards-title">{{ t('share.yourRewards') }}</text>
          <view class="reward-items">
            <view v-for="(reward, index) in rewards" :key="index" class="reward-item">
              <text class="bi" :class="getRewardIcon(reward.reward_type)"></text>
              <text class="reward-value">{{ formatRewardValue(reward) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 操作按钮 -->
      <view class="action-section">
        <button class="primary-btn" @click="handleJoin">
          {{ isLoggedIn ? t('share.claimRewards') : t('share.joinNow') }}
        </button>
        <text class="login-hint" v-if="!isLoggedIn" @click="goLogin">
          {{ t('share.alreadyHaveAccount') }}
        </text>
      </view>

      <!-- 底部提示 -->
      <view class="footer-note">
        <text class="note-text">{{ t('share.termsNote') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { recordShareClick, getRewardConfig, registerWithInviteCode, type RewardConfig } from '@/api/share'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const toast = useToast()
const userStore = useUserStore()

// 状态
const loading = ref(true)
const error = ref(false)
const shareCode = ref('')
const shareType = ref('')
const targetId = ref<number | null>(null)
const rewards = ref<RewardConfig[]>([])

// 是否已登录
const isLoggedIn = computed(() => userStore.isLoggedIn)

// 页面加载时获取参数
onLoad((options) => {
  const code = options?.code || ''
  if (code) {
    shareCode.value = code
    handleShareLink(code)
  } else {
    error.value = true
    loading.value = false
  }
})

// 处理分享链接
async function handleShareLink(code: string) {
  try {
    // 1. 记录分享点击
    const clickRes = await recordShareClick(code)
    shareType.value = clickRes.data.type
    targetId.value = clickRes.data.target_id

    // 2. 获取奖励配置
    const configRes = await getRewardConfig()
    // 显示被邀请人可获得的奖励
    rewards.value = [
      ...configRes.data.register.invitee,
      ...configRes.data.first_order.invitee,
    ]

    // 3. 将邀请码存储到本地（注册时使用）
    uni.setStorageSync('invite_code', code)

    loading.value = false
  } catch (e: any) {
    console.error('Handle share link error:', e)
    error.value = true
    loading.value = false
  }
}

// 获取奖励图标
function getRewardIcon(type: string): string {
  switch (type) {
    case 'points':
      return 'bi-coin'
    case 'chances':
      return 'bi-gift'
    case 'coupon':
      return 'bi-ticket-perforated'
    default:
      return 'bi-star-fill'
  }
}

// 格式化奖励值
function formatRewardValue(reward: RewardConfig): string {
  switch (reward.reward_type) {
    case 'points':
      return `${reward.reward_value} ${t('game.points')}`
    case 'chances':
      return `${reward.reward_value} ${t('game.spins')}`
    case 'coupon':
      return `${reward.reward_value}% ${t('coupon.discount')}`
    default:
      return String(reward.reward_value)
  }
}

// 点击加入/领取奖励
async function handleJoin() {
  if (isLoggedIn.value) {
    // 已登录用户，尝试绑定邀请关系
    try {
      await registerWithInviteCode(shareCode.value)
      toast.success(t('share.rewardsClaimed'))

      // 跳转到目标页面
      redirectToTarget()
    } catch (e: any) {
      // 可能已经被邀请过了
      if (e.message?.includes('Already invited')) {
        toast.info(t('share.alreadyInvited'))
      } else {
        toast.error(e.message || t('common.error'))
      }
      // 仍然跳转到目标页面
      redirectToTarget()
    }
  } else {
    // 未登录用户，跳转到注册页面
    uni.navigateTo({
      url: '/pages/auth/register?invite_code=' + shareCode.value
    })
  }
}

// 跳转到目标页面
function redirectToTarget() {
  if (shareType.value === 'goods' && targetId.value) {
    uni.redirectTo({
      url: '/pages/goods/detail?id=' + targetId.value
    })
  } else if (shareType.value === 'game') {
    uni.redirectTo({
      url: '/pages/game/index'
    })
  } else {
    uni.switchTab({
      url: '/pages/index/index'
    })
  }
}

// 跳转到登录页
function goLogin() {
  uni.navigateTo({
    url: '/pages/auth/login'
  })
}

// 跳转到首页
function goHome() {
  uni.switchTab({
    url: '/pages/index/index'
  })
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 24px;
  box-sizing: border-box;
}

// 加载状态
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255, 255, 255, 0.2);
  border-top-color: #FFD700;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-text {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

// 错误状态
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 16px;
}

.error-icon {
  font-size: 64px;
  color: #FF6B6B;
}

.error-title {
  font-size: 20px;
  font-weight: 600;
  color: #fff;
}

.error-desc {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  max-width: 280px;
}

// 内容区域
.content-container {
  width: 100%;
  max-width: 360px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

// Logo 区域
.logo-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 32px;
}

.logo {
  width: 80px;
  height: 80px;
  margin-bottom: 12px;
}

.app-name {
  font-size: 24px;
  font-weight: 700;
  color: #FFD700;
  letter-spacing: 2px;
}

// 邀请卡片
.invite-card {
  width: 100%;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 28px 24px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.invite-title {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  display: block;
  margin-bottom: 8px;
}

.invite-desc {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  display: block;
  margin-bottom: 24px;
}

// 奖励区域
.rewards-section {
  background: rgba(255, 215, 0, 0.1);
  border-radius: 12px;
  padding: 16px;
}

.rewards-title {
  font-size: 14px;
  color: #FFD700;
  font-weight: 600;
  display: block;
  margin-bottom: 12px;
}

.reward-items {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

.reward-item {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.1);
  padding: 8px 14px;
  border-radius: 20px;

  .bi {
    font-size: 16px;
    color: #FFD700;
  }
}

.reward-value {
  font-size: 13px;
  color: #fff;
  font-weight: 500;
}

// 操作区域
.action-section {
  width: 100%;
  margin-top: 32px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.primary-btn {
  width: 100%;
  height: 52px;
  line-height: 52px;
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%) !important;
  color: #1a1a2e !important;
  border: none;
  border-radius: 26px;
  font-size: 17px;
  font-weight: 700;

  &::after {
    border: none;
  }
}

.login-hint {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  text-decoration: underline;
}

// 底部提示
.footer-note {
  margin-top: 24px;
  padding: 0 20px;
}

.note-text {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.4);
  text-align: center;
}
</style>
