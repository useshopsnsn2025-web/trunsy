<!--
  ============================================================
  LoadingPage - 启动页/加载页组件 (eBay Style)
  ============================================================

  参考 eBay App 设计的极简启动页：
  - 纯净背景，无干扰元素
  - Logo 居中，带优雅呼吸动画
  - 简洁三点波浪加载指示器
  - 专业、信赖、高级感

  ============================================================
  使用方法 (Usage)
  ============================================================

  推荐用法 - v-model（自带最小显示时间 + 淡出动画）：

     <LoadingPage v-model="loading" />

  自定义配置：

     <LoadingPage
       v-model="loading"
       theme="light"
       :min-duration="1000"
       :show-text="true"
       text="加载中"
     />

  ============================================================
  Props 说明
  ============================================================

  | Prop         | Type              | Default    | Description              |
  |--------------|-------------------|------------|--------------------------|
  | v-model      | boolean           | true       | 控制显示/隐藏            |
  | theme        | 'light' | 'dark'  | 'light'    | 主题模式                 |
  | minDuration  | number            | 1000       | 最小显示时间(ms)         |
  | showText     | boolean           | false      | 是否显示加载文字         |
  | text         | string            | ''         | 自定义加载文字           |
  | showProgress | boolean           | false      | 是否显示进度条           |
  | progress     | number            | 0          | 进度值 0-100             |

  ============================================================
-->

<template>
  <view v-show="internalVisible" class="loading-page" :class="[`theme-${theme}`, { 'is-leaving': isLeaving }]">
    <!-- eBay Style: 极简居中布局 -->
    <view class="splash-content">
      <!-- Logo 区域 - 核心视觉焦点 -->
      <view class="logo-wrapper">
        <image
          src="../static/logo.png"
          class="logo"
          mode="aspectFit"
        />
      </view>

      <!-- 加载指示器 - 简洁波浪点 -->
      <view class="loader-wrapper">
        <view class="wave-dots">
          <view class="wave-dot"></view>
          <view class="wave-dot"></view>
          <view class="wave-dot"></view>
        </view>

        <!-- 进度条 (可选) -->
        <view v-if="showProgress" class="progress-track">
          <view class="progress-bar" :style="{ width: `${progress}%` }"></view>
        </view>
      </view>

      <!-- 加载文字 (可选) -->
      <text v-if="showText" class="loading-text">{{ displayText }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Props {
  /** 控制加载页显示/隐藏 (支持 v-model) */
  modelValue?: boolean
  theme?: 'light' | 'dark'
  /** 最小显示时间(ms)，避免闪屏，0=禁用 */
  minDuration?: number
  showText?: boolean
  text?: string
  showProgress?: boolean
  progress?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: true,
  theme: 'light',
  minDuration: 1000,
  showText: false,
  text: '',
  showProgress: false,
  progress: 0
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// 内部可见性状态
const internalVisible = ref(true)
// 淡出动画状态
const isLeaving = ref(false)
// 记录组件挂载时间
const mountTime = ref(0)

onMounted(() => {
  mountTime.value = Date.now()
})

// 监听外部传入的 modelValue 变化
watch(() => props.modelValue, (newVal) => {
  if (!newVal && internalVisible.value) {
    // 外部想要隐藏，启动淡出流程
    startLeaving()
  } else if (newVal && !internalVisible.value) {
    // 外部想要显示，重置状态
    internalVisible.value = true
    isLeaving.value = false
    mountTime.value = Date.now()
  }
}, { immediate: false })

// 启动淡出流程
function startLeaving() {
  // 计算已经显示的时间
  const elapsedTime = Date.now() - mountTime.value
  const remainingTime = Math.max(0, props.minDuration - elapsedTime)

  // 等待最小显示时间后开始淡出
  setTimeout(() => {
    isLeaving.value = true

    // 淡出动画结束后真正隐藏
    setTimeout(() => {
      internalVisible.value = false
      emit('update:modelValue', false)
    }, 300) // 淡出动画时长
  }, remainingTime)
}

const displayText = computed(() => {
  return props.text || t('common.loading')
})
</script>

<style lang="scss" scoped>
// ============================================================
// eBay Style Splash Screen - 极简专业设计
// ============================================================

// 设计变量
$color-bg-light: #FFFFFF;
$color-bg-dark: #121212;
$color-primary: #3B82F6;     // 信赖蓝
$color-accent: #FF6B35;       // 品牌橙
$color-text-light: #1E293B;
$color-text-dark: #F1F5F9;
$color-muted-light: #64748B;
$color-muted-dark: #94A3B8;

// ============================================================
// 主容器
// ============================================================
.loading-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  opacity: 1;

  // 淡出动画
  &.is-leaving {
    opacity: 0;
    pointer-events: none;
  }

  // ========== 浅色主题 ==========
  &.theme-light {
    background-color: $color-bg-light;

    .loading-text {
      color: $color-muted-light;
    }

    .wave-dot {
      background-color: $color-primary;
    }

    .progress-track {
      background-color: #E2E8F0;
    }

    .progress-bar {
      background: linear-gradient(90deg, $color-primary, lighten($color-primary, 10%));
    }
  }

  // ========== 深色主题 ==========
  &.theme-dark {
    background-color: $color-bg-dark;

    .loading-text {
      color: $color-muted-dark;
    }

    .wave-dot {
      background-color: $color-primary;
    }

    .progress-track {
      background-color: #334155;
    }

    .progress-bar {
      background: linear-gradient(90deg, $color-primary, lighten($color-primary, 15%));
    }

    .logo {
      // 深色模式下 Logo 轻微发光
      filter: drop-shadow(0 0 20px rgba($color-primary, 0.15));
    }
  }
}

// ============================================================
// 内容区域 - 垂直居中布局
// ============================================================
.splash-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

// ============================================================
// Logo 区域
// ============================================================
.logo-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 48px;
}

.logo {
  width: 180px;
  height: 72px;
  animation: logoBreath 2.5s ease-in-out infinite;
}

// ============================================================
// 加载指示器
// ============================================================
.loader-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  min-height: 40px;
}

// 波浪点动画 - eBay 风格
.wave-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 20px;
}

.wave-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  animation: waveMotion 1.2s ease-in-out infinite;

  &:nth-child(1) {
    animation-delay: 0s;
  }

  &:nth-child(2) {
    animation-delay: 0.15s;
  }

  &:nth-child(3) {
    animation-delay: 0.3s;
  }
}

// ============================================================
// 进度条 (可选)
// ============================================================
.progress-track {
  width: 100px;
  height: 2px;
  border-radius: 1px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  border-radius: 1px;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

// ============================================================
// 加载文字 (可选)
// ============================================================
.loading-text {
  margin-top: 20px;
  font-size: 13px;
  font-weight: 400;
  letter-spacing: 0.3px;
  opacity: 0.8;
}

// ============================================================
// 动画定义
// ============================================================

// Logo 呼吸动画 - 优雅微妙
@keyframes logoBreath {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.015);
    opacity: 0.95;
  }
}

// 波浪点动画 - 流畅上下波动
@keyframes waveMotion {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  50% {
    transform: translateY(-8px);
    opacity: 1;
  }
}

// ============================================================
// 响应式适配
// ============================================================
@media (min-width: 768px) {
  .logo {
    width: 220px;
    height: 88px;
  }

  .wave-dot {
    width: 8px;
    height: 8px;
  }

  .progress-track {
    width: 140px;
    height: 3px;
  }

  .loading-text {
    font-size: 14px;
  }
}

// ============================================================
// 无障碍：减少动画
// ============================================================
@media (prefers-reduced-motion: reduce) {
  .logo {
    animation: none;
  }

  .wave-dot {
    animation: simplePulse 1.5s ease-in-out infinite;

    &:nth-child(2) {
      animation-delay: 0.5s;
    }

    &:nth-child(3) {
      animation-delay: 1s;
    }
  }

  .loading-page {
    transition: opacity 0.2s ease;
  }
}

@keyframes simplePulse {
  0%, 100% {
    opacity: 0.4;
  }
  50% {
    opacity: 1;
  }
}
</style>
