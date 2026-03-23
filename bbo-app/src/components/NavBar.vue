<!--
  ============================================================
  NavBar - 公共导航栏组件
  ============================================================

  一个统一的顶部导航栏组件，支持标题左对齐/居中、返回按钮、
  右侧操作按钮等功能，自动适配状态栏高度。

  ============================================================
  使用方法 (Usage)
  ============================================================

  1. 基础用法 - 只有标题和返回按钮：

     <NavBar title="页面标题" />

  2. 标题居中：

     <NavBar title="页面标题" title-align="center" />

  3. 使用 i18n 国际化标题：

     <NavBar :title="t('history.title')" />

  4. 添加右侧操作按钮：

     <NavBar title="页面标题">
       <template #right>
         <view class="nav-action" @click="handleAction">
           <text class="bi bi-trash"></text>
         </view>
       </template>
     </NavBar>

  5. 多个右侧按钮：

     <NavBar title="页面标题">
       <template #right>
         <view class="nav-action" @click="handleSearch">
           <text class="bi bi-search"></text>
         </view>
         <view class="nav-action" @click="handleMore">
           <text class="bi bi-three-dots"></text>
         </view>
       </template>
     </NavBar>

  6. 隐藏返回按钮（用于首页等场景）：

     <NavBar title="首页" :show-back="false" />

  7. 自定义返回逻辑：

     <NavBar title="页面标题" @back="handleCustomBack" />

  8. 透明背景（用于沉浸式页面）：

     <NavBar title="页面标题" transparent />

  9. 自定义背景色：

     <NavBar title="页面标题" background="#FF6B35" color="#FFFFFF" />

  10. 无底部边框：

      <NavBar title="页面标题" :border="false" />

  ============================================================
  Props 属性说明
  ============================================================

  | 属性名      | 类型    | 默认值    | 说明                           |
  |------------|---------|----------|--------------------------------|
  | title      | String  | ''       | 导航栏标题                      |
  | titleAlign | String  | 'left'   | 标题对齐方式: 'left' / 'center' |
  | showBack   | Boolean | true     | 是否显示返回按钮                |
  | background | String  | '#FFFFFF'| 导航栏背景色                    |
  | color      | String  | '#191919'| 标题和图标颜色                  |
  | transparent| Boolean | false    | 是否透明背景                    |
  | border     | Boolean | true     | 是否显示底部边框                |

  ============================================================
  Events 事件说明
  ============================================================

  | 事件名 | 说明                                    |
  |--------|----------------------------------------|
  | back   | 点击返回按钮时触发，可阻止默认返回行为    |

  ============================================================
  Slots 插槽说明
  ============================================================

  | 插槽名 | 说明                     |
  |--------|-------------------------|
  | right  | 右侧操作区域插槽          |
  | left   | 左侧区域插槽（替换返回按钮）|

  ============================================================
-->

<template>
  <view class="nav-bar-wrapper">
    <!-- 顶部导航栏 -->
    <view
      class="nav-bar"
      :class="{ 'nav-bar--transparent': transparent, 'nav-bar--no-border': !border }"
      :style="navBarStyle"
    >
      <view class="nav-bar__content">
        <!-- 左侧区域 -->
        <view class="nav-bar__left">
          <slot name="left">
            <view v-if="showBack" class="nav-bar__back" @click="handleBack">
              <text class="bi bi-arrow-left" :style="{ color: color }"></text>
            </view>
          </slot>
        </view>

        <!-- 标题 -->
        <text
          class="nav-bar__title"
          :class="{ 'nav-bar__title--center': titleAlign === 'center' }"
          :style="{ color: color }"
        >
          {{ title }}
        </text>

        <!-- 右侧区域 -->
        <view class="nav-bar__right">
          <slot name="right"></slot>
        </view>
      </view>
    </view>

    <!-- 导航栏占位 -->
    <view class="nav-bar__placeholder" :style="{ height: placeholderHeight + 'px' }"></view>

    <!-- 全局 Toast -->
    <view v-if="toastVisible" class="toast-container" :class="[`toast-${toastPosition}`]">
      <view class="toast-content" :class="[`toast-${toastType}`]">
        <view class="toast-icon">
          <text :class="toastIconClass"></text>
        </view>
        <text class="toast-message">{{ toastMessage }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * NavBar 公共导航栏组件
 *
 * 特性：
 * - 自动适配手机状态栏高度
 * - 支持标题左对齐/居中
 * - 支持自定义右侧操作按钮
 * - 支持透明背景模式
 * - 支持自定义颜色
 * - 集成全局 Toast 提示
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'

// ==========================================
// Toast 功能
// ==========================================
const toastVisible = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error' | 'warning' | 'info'>('success')
const toastPosition = ref<'top' | 'center' | 'bottom'>('center')
let toastTimer: ReturnType<typeof setTimeout> | null = null

const toastIconClass = computed(() => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    error: 'bi bi-x-circle-fill',
    warning: 'bi bi-exclamation-circle-fill',
    info: 'bi bi-info-circle-fill',
  }
  return icons[toastType.value]
})

function showToast(options: {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
  position?: 'top' | 'center' | 'bottom'
}) {
  if (toastTimer) {
    clearTimeout(toastTimer)
    toastTimer = null
  }

  toastMessage.value = options.message
  toastType.value = options.type || 'success'
  toastPosition.value = options.position || 'center'
  toastVisible.value = true

  const duration = options.duration ?? 2000
  if (duration > 0) {
    toastTimer = setTimeout(() => {
      toastVisible.value = false
    }, duration)
  }
}

function hideToast() {
  toastVisible.value = false
  if (toastTimer) {
    clearTimeout(toastTimer)
    toastTimer = null
  }
}

// Props 定义
const props = withDefaults(defineProps<{
  /** 导航栏标题 */
  title?: string
  /** 标题对齐方式: 'left' | 'center' */
  titleAlign?: 'left' | 'center'
  /** 是否显示返回按钮 */
  showBack?: boolean
  /** 导航栏背景色 */
  background?: string
  /** 标题和图标颜色 */
  color?: string
  /** 是否透明背景 */
  transparent?: boolean
  /** 是否显示底部边框 */
  border?: boolean
}>(), {
  title: '',
  titleAlign: 'left',
  showBack: true,
  background: '#FFFFFF',
  color: '#191919',
  transparent: false,
  border: true,
})

// Events 定义
const emit = defineEmits<{
  (e: 'back'): void
}>()

// 状态栏高度
const statusBarHeight = ref(0)

// 导航栏高度（固定48px）
const NAV_BAR_HEIGHT = 48

// 占位高度
const placeholderHeight = computed(() => statusBarHeight.value + NAV_BAR_HEIGHT)

// 导航栏样式
const navBarStyle = computed(() => ({
  paddingTop: statusBarHeight.value + 'px',
  backgroundColor: props.transparent ? 'transparent' : props.background,
}))

// 获取状态栏高度 & 注册 Toast 事件
onMounted(() => {
  uni.getSystemInfo({
    success: (res) => {
      // H5 端没有状态栏，statusBarHeight 为 0 是正确的
      statusBarHeight.value = res.statusBarHeight || 0
    }
  })

  // 注册全局 Toast 事件
  uni.$on('toast:show', showToast)
  uni.$on('toast:hide', hideToast)
})

// 清理事件监听
onUnmounted(() => {
  uni.$off('toast:show', showToast)
  uni.$off('toast:hide', hideToast)
  if (toastTimer) {
    clearTimeout(toastTimer)
  }
})

// 返回处理
function handleBack() {
  emit('back')
  // 默认行为：返回上一页
  uni.navigateBack({
    delta: 1,
    fail: () => {
      // 如果无法返回，则跳转首页
      uni.switchTab({ url: '/pages/index/index' })
    }
  })
}
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量
// ==========================================

// 色彩
$color-border: #E5E5E5;
$color-background: #F7F7F7;

// 字体
$font-size-lg: 17px;
$font-weight-semibold: 600;

// 间距
$spacing-sm: 8px;
$spacing-base: 16px;

// 圆角
$radius-full: 9999px;

// ==========================================
// 导航栏样式
// ==========================================

.nav-bar-wrapper {
  width: 100%;
}

.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  // border-bottom: 1px solid $color-border;

  &--transparent {
    border-bottom: none;
  }

  &--no-border {
    border-bottom: none;
  }
}

.nav-bar__content {
  height: 48px;
  display: flex;
  align-items: center;
  padding: 0 $spacing-base;
}

.nav-bar__left {
  display: flex;
  align-items: center;
}

.nav-bar__back {
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: $radius-full;

  .bi {
    font-size: 22px;
  }

  &:active {
    background-color: $color-background;
  }
}

.nav-bar__title {
  flex: 1;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  margin-left: $spacing-sm;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;

  // 标题居中模式
  &--center {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    margin-left: 0;
    max-width: 60%;
    text-align: center;
  }
}

.nav-bar__right {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-left: auto;
}

.nav-bar__placeholder {
  flex-shrink: 0;
}

// ==========================================
// 右侧操作按钮样式（供外部使用）
// ==========================================

:deep(.nav-action) {
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: $radius-full;
  transition: background-color 0.2s;

  .bi {
    font-size: 20px;
  }

  &:active {
    background-color: $color-background;
  }
}

// 响应式 - 减少动画
@media (prefers-reduced-motion: reduce) {
  .nav-bar__back,
  :deep(.nav-action) {
    transition: none;
  }
}

// ==========================================
// Toast 样式
// ==========================================
$color-primary: #FF6B35;
$color-success: #10B981;
$color-error: #EF4444;
$color-warning: #F59E0B;
$color-info: #3B82F6;

.toast-container {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 10000;
  display: flex;
  justify-content: center;
  pointer-events: none;
  padding: 0 48rpx;

  &.toast-top {
    top: calc(env(safe-area-inset-top) + 120rpx);
  }

  &.toast-center {
    top: 50%;
    transform: translateY(-50%);
  }

  &.toast-bottom {
    bottom: calc(env(safe-area-inset-bottom) + 120rpx);
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx 36rpx;
  border-radius: 48rpx;
  background: rgba(0, 0, 0, 0.85);
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15);
  animation: toastFadeIn 0.25s ease-out;

  &.toast-success {
    .toast-icon {
      color: $color-success;
    }
  }

  &.toast-error {
    .toast-icon {
      color: $color-error;
    }
  }

  &.toast-warning {
    .toast-icon {
      color: $color-warning;
    }
  }

  &.toast-info {
    .toast-icon {
      color: $color-info;
    }
  }
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 40rpx;
  }
}

.toast-message {
  font-size: 28rpx;
  color: #fff;
  font-weight: 500;
  max-width: 480rpx;
  word-break: break-word;
}

@keyframes toastFadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
