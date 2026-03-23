<template>
  <view v-if="visible" class="share-modal-overlay" @click="handleClose">
    <view class="share-modal" @click.stop>
      <!-- 头部 -->
      <view class="modal-header">
        <text class="header-title">{{ t('share.title') }}</text>
        <view class="close-btn" @click="handleClose">
          <text class="close-icon">×</text>
        </view>
      </view>

      <!-- 奖励提示 -->
      <view class="reward-tips">
        <view class="reward-icon"><text class="bi bi-gift-fill"></text></view>
        <view class="reward-text">
          <text class="reward-title">{{ t('share.rewardTitle') }}</text>
          <text class="reward-desc">{{ t('share.rewardDesc') }}</text>
        </view>
      </view>

      <!-- 分享渠道 -->
      <view class="share-channels">
        <view
          v-for="channel in channels"
          :key="channel.key"
          class="channel-item"
          @click="handleShare(channel.key)"
        >
          <view class="channel-icon" :style="{ backgroundColor: channel.bgColor }">
            <text :class="['bi', channel.biIcon, 'channel-bi-icon']"></text>
          </view>
          <text class="channel-name">{{ channel.name }}</text>
        </view>
      </view>

      <!-- 分享链接 -->
      <view v-if="shareUrl" class="share-link-section">
        <text class="link-label">{{ t('share.linkLabel') }}</text>
        <view class="link-box">
          <text class="link-text">{{ shareUrl }}</text>
          <view class="copy-btn" @click="copyLink">
            <text class="copy-text">{{ t('share.copy') }}</text>
          </view>
        </view>
      </view>

      <!-- 分享码 -->
      <view v-if="shareCode" class="share-code-section">
        <text class="code-label">{{ t('share.codeLabel') }}</text>
        <view class="code-box">
          <text class="code-text">{{ shareCode }}</text>
        </view>
      </view>

      <!-- 统计数据 -->
      <view v-if="showStats && stats" class="share-stats">
        <view class="stat-item">
          <text class="stat-value">{{ stats.share.total_clicks }}</text>
          <text class="stat-label">{{ t('share.clicks') }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ stats.share.total_registers }}</text>
          <text class="stat-label">{{ t('share.registers') }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ stats.invite.completed_orders }}</text>
          <text class="stat-label">{{ t('share.orders') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  generateShareLink,
  getShareStats,
  getShareTemplates,
  type ShareType,
  type ShareChannel,
  type ShareStats
} from '@/api/share'

const { t, locale } = useI18n()

// Props
const props = withDefaults(
  defineProps<{
    visible: boolean
    type?: ShareType
    targetId?: number
    showStats?: boolean
  }>(),
  {
    type: 'game',
    showStats: false
  }
)

// Emits
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'shared', channel: ShareChannel): void
}>()

// 状态
const shareUrl = ref('')
const shareCode = ref('')
const stats = ref<ShareStats | null>(null)
const templates = ref<Record<string, Record<string, string>>>({})
const loading = ref(false)

// 分享渠道配置
const channels = computed(() => [
  {
    key: 'whatsapp' as ShareChannel,
    name: 'WhatsApp',
    bgColor: '#25D366',
    biIcon: 'bi-whatsapp'
  },
  {
    key: 'facebook' as ShareChannel,
    name: 'Facebook',
    bgColor: '#1877F2',
    biIcon: 'bi-facebook'
  },
  {
    key: 'line' as ShareChannel,
    name: 'LINE',
    bgColor: '#00C300',
    biIcon: 'bi-line'
  },
  {
    key: 'twitter' as ShareChannel,
    name: 'X',
    bgColor: '#000000',
    biIcon: 'bi-twitter-x'
  },
  {
    key: 'copy' as ShareChannel,
    name: t('share.copyLink'),
    bgColor: '#6B7280',
    biIcon: 'bi-link-45deg'
  }
])

// 监听显示状态
watch(
  () => props.visible,
  async (newVal) => {
    if (newVal) {
      await initShare()
    }
  }
)

// 初始化分享
async function initShare() {
  loading.value = true
  try {
    // 生成分享链接
    const linkRes = await generateShareLink({
      type: props.type,
      target_id: props.targetId
    })
    if (linkRes.code === 0) {
      shareUrl.value = linkRes.data.share_url
      shareCode.value = linkRes.data.share_code
    }

    // 获取统计数据
    if (props.showStats) {
      const statsRes = await getShareStats()
      if (statsRes.code === 0) {
        stats.value = statsRes.data
      }
    }

    // 获取分享模板
    const templatesRes = await getShareTemplates()
    if (templatesRes.code === 0) {
      templates.value = templatesRes.data
    }
  } catch (error) {
    console.error('Init share failed:', error)
  } finally {
    loading.value = false
  }
}

// 处理分享
async function handleShare(channel: ShareChannel) {
  if (!shareUrl.value) {
    uni.showToast({
      title: t('share.generating'),
      icon: 'loading'
    })
    return
  }

  // 生成带渠道的分享链接
  try {
    const res = await generateShareLink({
      type: props.type,
      target_id: props.targetId,
      channel
    })
    if (res.code === 0) {
      shareUrl.value = res.data.share_url
      shareCode.value = res.data.share_code
    }
  } catch (error) {
    // 使用已有链接
  }

  // 获取分享文案
  const template = templates.value[props.type]?.[locale.value] || templates.value[props.type]?.['en-us'] || ''
  const shareText = template.replace('[CODE]', shareCode.value)

  // 根据渠道打开分享
  switch (channel) {
    case 'whatsapp':
      openWhatsApp(shareText, shareUrl.value)
      break
    case 'facebook':
      openFacebook(shareUrl.value)
      break
    case 'line':
      openLine(shareText, shareUrl.value)
      break
    case 'twitter':
      openTwitter(shareText, shareUrl.value)
      break
    case 'copy':
      copyLink()
      break
  }

  emit('shared', channel)
}

// 打开 WhatsApp
function openWhatsApp(text: string, url: string) {
  const message = encodeURIComponent(`${text}\n${url}`)
  // #ifdef H5
  window.open(`https://wa.me/?text=${message}`, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(`whatsapp://send?text=${message}`)
  // #endif
}

// 打开 Facebook
function openFacebook(url: string) {
  const shareUrl = encodeURIComponent(url)
  // #ifdef H5
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(`https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`)
  // #endif
}

// 打开 LINE
function openLine(text: string, url: string) {
  const message = encodeURIComponent(`${text}\n${url}`)
  // #ifdef H5
  window.open(`https://line.me/R/msg/text/?${message}`, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(`line://msg/text/${message}`)
  // #endif
}

// 打开 Twitter/X
function openTwitter(text: string, url: string) {
  const shareText = encodeURIComponent(text)
  const shareUrl = encodeURIComponent(url)
  // #ifdef H5
  window.open(`https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(`https://twitter.com/intent/tweet?text=${shareText}&url=${shareUrl}`)
  // #endif
}

// 复制链接
function copyLink() {
  if (!shareUrl.value) return

  // #ifdef H5
  try {
    const textarea = document.createElement('textarea')
    textarea.value = shareUrl.value
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    uni.showToast({ title: t('share.copySuccess'), icon: 'success' })
  } catch (e) {
    console.error('Copy failed:', e)
  }
  // #endif

  // #ifndef H5
  uni.setClipboardData({
    data: shareUrl.value,
    showToast: false,
    success: () => {
      uni.showToast({ title: t('share.copySuccess'), icon: 'success' })
    }
  })
  // #endif
}

// 关闭弹窗
function handleClose() {
  emit('update:visible', false)
}
</script>

<style lang="scss" scoped>
.share-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 9999;
}

.share-modal {
  width: 100%;
  max-width: 500px;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 24rpx 24rpx 0 0;
  padding: 40rpx 32rpx;
  padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32rpx;
}

.header-title {
  font-size: 36rpx;
  font-weight: 600;
  color: #fff;
}

.close-btn {
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
}

.close-icon {
  font-size: 40rpx;
  color: #fff;
  line-height: 1;
}

.reward-tips {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 165, 0, 0.2));
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 32rpx;
}

.reward-icon {
  margin-right: 20rpx;

  text {
    font-size: 44rpx;
    color: #ffd700;
  }
}

.reward-text {
  display: flex;
  flex-direction: column;
}

.reward-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #ffd700;
  margin-bottom: 4rpx;
}

.reward-desc {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
}

.share-channels {
  display: flex;
  justify-content: space-around;
  margin-bottom: 32rpx;
}

.channel-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16rpx;
}

.channel-icon {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12rpx;
}

.channel-bi-icon {
  font-size: 44rpx;
  color: #fff;
}

.channel-name {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
}

.share-link-section {
  margin-bottom: 24rpx;
}

.link-label,
.code-label {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 12rpx;
  display: block;
}

.link-box {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12rpx;
  padding: 16rpx 20rpx;
}

.link-text {
  flex: 1;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.8);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.copy-btn {
  background: linear-gradient(135deg, #ffd700, #ffa500);
  border-radius: 8rpx;
  padding: 12rpx 24rpx;
  margin-left: 16rpx;
}

.copy-text {
  font-size: 24rpx;
  color: #1a1a2e;
  font-weight: 600;
}

.share-code-section {
  margin-bottom: 24rpx;
}

.code-box {
  background: rgba(255, 215, 0, 0.1);
  border: 2rpx dashed rgba(255, 215, 0, 0.5);
  border-radius: 12rpx;
  padding: 20rpx;
  text-align: center;
}

.code-text {
  font-size: 40rpx;
  font-weight: 700;
  color: #ffd700;
  letter-spacing: 8rpx;
}

.share-stats {
  display: flex;
  justify-content: space-around;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12rpx;
  padding: 24rpx;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 36rpx;
  font-weight: 700;
  color: #ffd700;
  margin-bottom: 8rpx;
}

.stat-label {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.6);
}
</style>
