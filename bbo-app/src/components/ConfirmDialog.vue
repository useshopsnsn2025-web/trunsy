<template>
  <view v-if="visible" class="confirm-dialog-overlay" @click="handleOverlayClick">
    <view class="confirm-dialog-content" :class="{ visible: contentVisible }" @click.stop>
      <!-- 图标 -->
      <view v-if="icon" class="confirm-dialog-icon" :class="iconType">
        <text :class="icon"></text>
      </view>

      <!-- 标题 -->
      <view v-if="title" class="confirm-dialog-title">
        <text>{{ title }}</text>
      </view>

      <!-- 内容 -->
      <view v-if="content" class="confirm-dialog-message">
        <text>{{ content }}</text>
      </view>

      <!-- 按钮组 -->
      <view class="confirm-dialog-actions">
        <view
          v-if="showCancel"
          class="confirm-dialog-btn cancel"
          @click="handleCancel"
        >
          <text>{{ cancelText || t('common.cancel') }}</text>
        </view>
        <view
          class="confirm-dialog-btn confirm"
          :class="{ destructive: confirmDestructive }"
          @click="handleConfirm"
        >
          <text>{{ confirmText || t('common.confirm') }}</text>
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
  title?: string
  content?: string
  icon?: string
  iconType?: 'warning' | 'danger' | 'success' | 'info'
  confirmText?: string
  cancelText?: string
  showCancel?: boolean
  confirmDestructive?: boolean
  closeOnClickOverlay?: boolean
}>(), {
  showCancel: true,
  confirmDestructive: false,
  closeOnClickOverlay: false,
  iconType: 'warning'
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)

// 监听 visible 变化，控制动画
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// 关闭弹窗
function close() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
  }, 200)
}

// 点击遮罩层
function handleOverlayClick() {
  if (props.closeOnClickOverlay) {
    handleCancel()
  }
}

// 确认
function handleConfirm() {
  emit('confirm')
  close()
}

// 取消
function handleCancel() {
  emit('cancel')
  close()
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
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.confirm-dialog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  animation: fadeIn 0.2s ease;
}

.confirm-dialog-content {
  width: 100%;
  max-width: 560rpx;
  background: $surface;
  border-radius: $radius-xl;
  padding: 48rpx 40rpx 40rpx;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.2s cubic-bezier(0.32, 0.72, 0, 1);

  &.visible {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-dialog-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 96rpx;
  height: 96rpx;
  margin: 0 auto 24rpx;
  border-radius: 50%;

  text {
    font-size: 48rpx;
  }

  &.warning {
    background: rgba($primary, 0.1);
    text { color: $primary; }
  }

  &.danger {
    background: rgba($destructive, 0.1);
    text { color: $destructive; }
  }

  &.success {
    background: rgba($success, 0.1);
    text { color: $success; }
  }

  &.info {
    background: rgba($info, 0.1);
    text { color: $info; }
  }
}

.confirm-dialog-title {
  text-align: center;
  margin-bottom: 16rpx;

  text {
    font-size: 34rpx;
    font-weight: 700;
    color: $text-primary;
    line-height: 1.4;
  }
}

.confirm-dialog-message {
  text-align: center;
  margin-bottom: 40rpx;

  text {
    font-size: 28rpx;
    color: $text-secondary;
    line-height: 1.6;
  }
}

.confirm-dialog-actions {
  display: flex;
  gap: 16rpx;
}

.confirm-dialog-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 88rpx;
  border-radius: $radius-lg;
  font-size: 30rpx;
  font-weight: 600;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
  }

  &.cancel {
    background: $background;
    color: $text-secondary;

    &:active {
      background: darken($background, 3%);
    }
  }

  &.confirm {
    background: $primary;
    color: #fff;

    &:active {
      background: darken($primary, 5%);
    }

    &.destructive {
      background: $destructive;

      &:active {
        background: darken($destructive, 5%);
      }
    }
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .confirm-dialog-overlay,
  .confirm-dialog-content {
    animation: none;
    transition: none;
  }
}
</style>
