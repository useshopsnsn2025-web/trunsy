<template>
  <view v-if="visible" class="action-sheet-overlay" @click="handleOverlayClick">
    <view class="action-sheet-content" :class="{ visible: contentVisible }" @click.stop>
      <!-- 拖动指示器 -->
      <view class="action-sheet-header">
        <view class="action-sheet-handle"></view>
      </view>

      <!-- 标题 -->
      <view v-if="title" class="action-sheet-title-wrapper">
        <text class="action-sheet-title">{{ title }}</text>
      </view>

      <!-- 选项列表 -->
      <view class="action-sheet-options">
        <view
          v-for="(option, index) in options"
          :key="index"
          class="action-sheet-option"
          :class="{
            active: modelValue === option.value,
            destructive: option.destructive,
            disabled: option.disabled
          }"
          @click="selectOption(option)"
        >
          <text v-if="option.icon" :class="['bi', option.icon, 'option-icon']"></text>
          <text class="option-label">{{ option.label }}</text>
          <text v-if="modelValue === option.value && showCheck" class="bi bi-check-lg option-check"></text>
        </view>
      </view>

      <!-- 取消按钮 -->
      <view v-if="showCancel" class="action-sheet-cancel" @click="handleCancel">
        <text>{{ cancelText }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

export interface ActionSheetOption {
  label: string
  value: string | number
  icon?: string
  destructive?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<{
  visible: boolean
  options: ActionSheetOption[]
  title?: string
  modelValue?: string | number
  showCheck?: boolean
  showCancel?: boolean
  cancelText?: string
  closeOnClickOverlay?: boolean
}>(), {
  showCheck: true,
  showCancel: true,
  closeOnClickOverlay: true
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'update:modelValue', value: string | number): void
  (e: 'select', option: ActionSheetOption): void
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
  }, 300)
}

// 点击遮罩层
function handleOverlayClick() {
  if (props.closeOnClickOverlay) {
    close()
  }
}

// 选择选项
function selectOption(option: ActionSheetOption) {
  if (option.disabled) return

  emit('update:modelValue', option.value)
  emit('select', option)
  close()
}

// 取消
function handleCancel() {
  emit('cancel')
  close()
}

// 获取取消按钮文本
const cancelText = props.cancelText || t('common.cancel')
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
$radius-lg: 24rpx;
$radius-full: 9999rpx;

.action-sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.2s ease;
}

.action-sheet-content {
  width: 100%;
  background: $surface;
  border-radius: 32rpx 32rpx 0 0;
  transform: translateY(100%);
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);

  &.visible {
    transform: translateY(0);
  }
}

.action-sheet-header {
  display: flex;
  justify-content: center;
  padding: 16rpx 0;
}

.action-sheet-handle {
  width: 80rpx;
  height: 8rpx;
  background: $border;
  border-radius: 4rpx;
}

.action-sheet-title-wrapper {
  padding: 8rpx 32rpx 24rpx;
}

.action-sheet-title {
  display: block;
  font-size: 32rpx;
  font-weight: 700;
  color: $text-primary;
}

.action-sheet-options {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  padding: 0 32rpx 24rpx;
}

.action-sheet-option {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 28rpx 24rpx;
  background: $background;
  border-radius: $radius-lg;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
  }

  &.active {
    background: rgba($primary, 0.1);

    .option-label {
      color: $primary;
      font-weight: 600;
    }
  }

  &.destructive {
    .option-label,
    .option-icon {
      color: $destructive;
    }

    &:active {
      background: rgba($destructive, 0.1);
    }
  }

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }
}

.option-icon {
  font-size: 36rpx;
  color: $text-secondary;
}

.option-label {
  flex: 1;
  font-size: 30rpx;
  color: $text-primary;
}

.option-check {
  font-size: 36rpx;
  color: $primary;
}

.action-sheet-cancel {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100rpx;
  margin: 0 32rpx;
  margin-bottom: calc(24rpx + env(safe-area-inset-bottom));
  background: $background;
  border-radius: $radius-lg;
  font-size: 30rpx;
  font-weight: 600;
  color: $text-secondary;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
    background: darken($background, 3%);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .action-sheet-overlay,
  .action-sheet-content {
    animation: none;
    transition: none;
  }
}
</style>
