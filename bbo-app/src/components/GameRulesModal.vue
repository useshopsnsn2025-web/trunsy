<template>
  <view v-if="visible" class="rules-modal-overlay" @click="handleClose">
    <view class="rules-modal-content" :class="{ visible: contentVisible }" @click.stop>
      <!-- 顶部装饰 -->
      <view class="modal-header">
        <view class="header-icon" :style="{ background: iconGradient }">
          <text class="bi" :class="iconClass"></text>
        </view>
        <text class="modal-title">{{ title }}</text>
        <view class="close-btn" @click="handleClose">
          <text class="bi bi-x-lg"></text>
        </view>
      </view>

      <!-- 规则内容 -->
      <view class="rules-content">
        <view
          v-for="(rule, index) in rulesList"
          :key="index"
          class="rule-item"
        >
          <view class="rule-number">{{ index + 1 }}</view>
          <text class="rule-text">{{ rule }}</text>
        </view>
      </view>

      <!-- 底部按钮 -->
      <view class="modal-footer">
        <view class="confirm-btn" @click="handleClose">
          <text>{{ t('common.gotIt') }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const props = withDefaults(defineProps<{
  visible: boolean
  title?: string
  content: string
  gameType?: 'wheel' | 'egg' | 'scratch'
}>(), {
  title: '',
  gameType: 'wheel'
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'close'): void
}>()

const { t } = useI18n()
const contentVisible = ref(false)

// 监听 visible 变化，添加动画
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      contentVisible.value = true
    })
  }
})

// 解析规则内容为列表
const rulesList = computed(() => {
  if (!props.content) return []
  // 按换行符分割，并过滤掉序号
  return props.content
    .split('\n')
    .map(line => line.replace(/^\d+\.\s*/, '').trim())
    .filter(line => line.length > 0)
})

// 根据游戏类型获取图标
const iconClass = computed(() => {
  const icons: Record<string, string> = {
    wheel: 'bi-bullseye',
    egg: 'bi-egg-fill',
    scratch: 'bi-credit-card-fill'
  }
  return icons[props.gameType] || 'bi-info-circle'
})

// 根据游戏类型获取渐变色
const iconGradient = computed(() => {
  const gradients: Record<string, string> = {
    wheel: 'linear-gradient(135deg, #FFD700 0%, #FFA500 100%)',
    egg: 'linear-gradient(135deg, #FF6B35 0%, #FF4444 100%)',
    scratch: 'linear-gradient(135deg, #A855F7 0%, #7C3AED 100%)'
  }
  return gradients[props.gameType] || 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)'
})

// 关闭弹窗
function handleClose() {
  contentVisible.value = false
  setTimeout(() => {
    emit('update:visible', false)
    emit('close')
  }, 200)
}
</script>

<style lang="scss" scoped>
$gold: #FFD700;
$surface: #FFFFFF;
$text-primary: #1E293B;
$text-secondary: #64748B;
$radius-lg: 24rpx;
$radius-xl: 32rpx;

.rules-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48rpx;
  animation: fadeIn 0.2s ease;
}

.rules-modal-content {
  position: relative;
  width: 100%;
  max-width: 600rpx;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  border-radius: $radius-xl;
  overflow: hidden;
  transform: scale(0.9);
  opacity: 0;
  transition: all 0.25s cubic-bezier(0.32, 0.72, 0, 1);

  &.visible {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-header {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48rpx 40rpx 32rpx;
  background: linear-gradient(180deg, rgba(255, 215, 0, 0.1) 0%, transparent 100%);
}

.header-icon {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20rpx;
  box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.3);

  text {
    font-size: 48rpx;
    color: #fff;
  }
}

.modal-title {
  font-size: 36rpx;
  font-weight: 700;
  color: $gold;
  text-shadow: 0 2rpx 8rpx rgba($gold, 0.3);
}

.close-btn {
  position: absolute;
  top: 24rpx;
  right: 24rpx;
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);

  text {
    font-size: 28rpx;
    color: rgba(255, 255, 255, 0.7);
  }

  &:active {
    background: rgba(255, 255, 255, 0.2);
  }
}

.rules-content {
  padding: 0 40rpx 32rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.rule-item {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
  padding: 20rpx 24rpx;
  background: rgba(255, 255, 255, 0.05);
  border-radius: $radius-lg;
  border: 1rpx solid rgba(255, 255, 255, 0.08);
}

.rule-number {
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 24rpx;
  font-weight: 700;
  color: #1a1a2e;
}

.rule-text {
  flex: 1;
  font-size: 28rpx;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.85);
}

.modal-footer {
  padding: 16rpx 40rpx 40rpx;
}

.confirm-btn {
  width: 100%;
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, $gold 0%, #FFA500 100%);
  border-radius: $radius-lg;
  transition: all 0.2s ease;

  text {
    font-size: 32rpx;
    font-weight: 600;
    color: #1a1a2e;
  }

  &:active {
    transform: scale(0.98);
    opacity: 0.9;
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .rules-modal-overlay,
  .rules-modal-content {
    animation: none;
    transition: none;
  }
}
</style>
