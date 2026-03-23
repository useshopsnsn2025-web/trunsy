<template>
  <view v-if="visible" class="share-popup-overlay" @click="close">
    <view class="share-popup-content" @click.stop>
      <view class="share-popup-header">
        <view class="share-popup-handle"></view>
      </view>
      <view class="share-popup-body">
        <text class="share-popup-title">{{ t('share.title') }}</text>

        <!-- 商品预览（可选） -->
        <view v-if="previewImage || previewTitle" class="share-preview">
          <image v-if="previewImage" class="share-preview-image" :src="previewImage" mode="aspectFill" />
          <view class="share-preview-info">
            <text v-if="previewTitle" class="share-preview-title">{{ previewTitle }}</text>
            <text v-if="previewPrice" class="share-preview-price">{{ previewPrice }}</text>
          </view>
        </view>

        <!-- 分享选项 -->
        <view class="share-options">
          <view class="share-option" @click="copyLink">
            <view class="share-option-icon share-icon-copy">
              <text class="bi bi-link-45deg"></text>
            </view>
            <text class="share-option-label">{{ t('share.copyLink') }}</text>
          </view>
          <view class="share-option" @click="shareToFacebook">
            <view class="share-option-icon share-icon-facebook">
              <text class="bi bi-facebook"></text>
            </view>
            <text class="share-option-label">Facebook</text>
          </view>
          <view class="share-option" @click="shareToTwitter">
            <view class="share-option-icon share-icon-twitter">
              <text class="bi bi-twitter-x"></text>
            </view>
            <text class="share-option-label">X</text>
          </view>
          <view class="share-option" @click="shareToWhatsApp">
            <view class="share-option-icon share-icon-whatsapp">
              <text class="bi bi-whatsapp"></text>
            </view>
            <text class="share-option-label">WhatsApp</text>
          </view>
          <view class="share-option" @click="shareToTelegram">
            <view class="share-option-icon share-icon-telegram">
              <text class="bi bi-telegram"></text>
            </view>
            <text class="share-option-label">Telegram</text>
          </view>
          <view class="share-option" @click="shareToLine">
            <view class="share-option-icon share-icon-line">
              <text class="bi bi-line"></text>
            </view>
            <text class="share-option-label">LINE</text>
          </view>
          <view class="share-option" @click="shareByEmail">
            <view class="share-option-icon share-icon-email">
              <text class="bi bi-envelope"></text>
            </view>
            <text class="share-option-label">{{ t('share.email') }}</text>
          </view>
        </view>

        <!-- 取消按钮 -->
        <view class="share-cancel" @click="close">
          <text>{{ t('common.cancel') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const toast = useToast()

// Props
const props = defineProps<{
  visible: boolean
  shareUrl: string
  shareTitle?: string
  shareText?: string
  previewImage?: string
  previewTitle?: string
  previewPrice?: string
}>()

// Emits
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'close'): void
}>()

// 关闭弹窗
function close() {
  emit('update:visible', false)
  emit('close')
}

// 打开外部链接（兼容 H5 和 APP）
function openExternalUrl(url: string) {
  // #ifdef H5
  window.open(url, '_blank')
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(url)
  // #endif
}

// 复制链接
function copyLink() {
  const message = t('share.linkCopied')
  uni.setClipboardData({
    data: props.shareUrl,
    showToast: false,
    success: () => {
      close()
      // 使用自定义 toast
      setTimeout(() => {
        toast.success(message)
      }, 150)
    },
    fail: () => {
      close()
    }
  })
}

// 分享到 Facebook
function shareToFacebook() {
  const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.shareUrl)}`
  openExternalUrl(url)
  close()
}

// 分享到 Twitter/X
function shareToTwitter() {
  const text = props.shareText || props.shareTitle || ''
  const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(props.shareUrl)}`
  openExternalUrl(url)
  close()
}

// 分享到 WhatsApp
function shareToWhatsApp() {
  const text = props.shareText || props.shareTitle || ''
  const fullText = text ? `${text} ${props.shareUrl}` : props.shareUrl
  const url = `https://wa.me/?text=${encodeURIComponent(fullText)}`
  openExternalUrl(url)
  close()
}

// 分享到 Telegram
function shareToTelegram() {
  const text = props.shareText || props.shareTitle || ''
  const url = `https://t.me/share/url?url=${encodeURIComponent(props.shareUrl)}&text=${encodeURIComponent(text)}`
  openExternalUrl(url)
  close()
}

// 分享到 LINE
function shareToLine() {
  const text = props.shareText || props.shareTitle || ''
  const url = `https://social-plugins.line.me/lineit/share?url=${encodeURIComponent(props.shareUrl)}&text=${encodeURIComponent(text)}`
  openExternalUrl(url)
  close()
}

// 通过邮件分享
function shareByEmail() {
  const subject = props.shareTitle || ''
  const body = `${t('share.checkThisOut')}\n\n${props.shareText || props.shareTitle || ''}\n\n${props.shareUrl}`
  const url = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
  // #ifdef H5
  window.location.href = url
  // #endif
  // #ifdef APP-PLUS
  plus.runtime.openURL(url)
  // #endif
  close()
}
</script>

<style lang="scss" scoped>
// 设计变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;

$font-family-display: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Helvetica Neue', sans-serif;
$font-size-xs: 11px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-xl: 22px;
$font-weight-medium: 500;
$font-weight-bold: 700;
$line-height-tight: 1.2;

$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-xl: 20px;
$radius-full: 9999px;

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

.share-popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.25s ease;
  backdrop-filter: blur(4px);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.share-popup-content {
  width: 100%;
  max-height: 80vh;
  background-color: $color-surface;
  border-radius: $radius-xl $radius-xl 0 0;
  animation: slideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.share-popup-header {
  display: flex;
  justify-content: center;
  padding: $spacing-md 0;
}

.share-popup-handle {
  width: 40px;
  height: 4px;
  background-color: $color-border;
  border-radius: $radius-full;
}

.share-popup-body {
  padding: $spacing-base $spacing-lg 40px;
}

.share-popup-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  text-align: center;
  margin-bottom: $spacing-lg;
}

.share-preview {
  display: flex;
  align-items: center;
  gap: $spacing-md;
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;
  margin-bottom: $spacing-xl;
}

.share-preview-image {
  width: 64px;
  height: 64px;
  border-radius: $radius-sm;
  flex-shrink: 0;
}

.share-preview-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.share-preview-title {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: $line-height-tight;
}

.share-preview-price {
  font-size: $font-size-md;
  font-weight: $font-weight-bold;
  color: $color-accent;
}

.share-options {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  gap: $spacing-lg;
  margin-bottom: $spacing-xl;
}

.share-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: $spacing-sm;
  width: 72px;
  cursor: pointer;
  transition: transform 0.2s ease;

  &:active {
    transform: scale(0.95);
  }
}

.share-option-icon {
  width: 52px;
  height: 52px;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #fff;

  .bi {
    line-height: 1;
  }
}

.share-icon-copy {
  background-color: $color-secondary;
}

.share-icon-facebook {
  background-color: #1877F2;
}

.share-icon-twitter {
  background-color: #000000;
}

.share-icon-whatsapp {
  background-color: #25D366;
}

.share-icon-telegram {
  background-color: #0088CC;
}

.share-icon-line {
  background-color: #00B900;
}

.share-icon-email {
  background-color: $color-muted;
}

.share-option-label {
  font-size: $font-size-xs;
  color: $color-secondary;
  text-align: center;
  white-space: nowrap;
}

.share-cancel {
  width: 100%;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-background;
  border-radius: $radius-lg;
  font-size: $font-size-md;
  font-weight: $font-weight-medium;
  color: $color-secondary;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-border;
  }
}

// 减少动画（无障碍）
@media (prefers-reduced-motion: reduce) {
  .share-popup-overlay,
  .share-popup-content {
    animation: none;
  }
}
</style>
