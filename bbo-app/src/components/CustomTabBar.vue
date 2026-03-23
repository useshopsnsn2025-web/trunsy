<template>
  <view class="custom-tabbar" :style="{ paddingBottom: safeAreaBottom + 'px' }">
    <view class="tabbar-content">
      <view
        v-for="(item, index) in tabList"
        :key="index"
        class="tabbar-item"
        :class="{
          active: currentIndex === index,
          'is-tapping': tappingIndex === index
        }"
        @click="switchTab(item, index)"
        @touchstart="onTouchStart(index)"
        @touchend="onTouchEnd"
        @touchcancel="onTouchEnd"
      >
        <view class="icon-wrapper">
          <text class="bi" :class="currentIndex === index ? item.activeIcon : item.icon"></text>
          <!-- 消息红点 -->
          <view v-if="item.showBadge && unreadCount > 0" class="badge">
            <text class="badge-text">{{ unreadCount > 99 ? '99+' : unreadCount }}</text>
          </view>
        </view>
        <text class="tabbar-text">{{ t(item.textKey) }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface TabItem {
  pagePath: string
  textKey: string
  icon: string
  activeIcon: string
  showBadge?: boolean
}

const props = defineProps<{
  current: number
}>()

const emit = defineEmits<{
  (e: 'change', index: number): void
}>()

// 安全区域底部高度
const safeAreaBottom = ref(0)

// 未读消息数量
const unreadCount = ref(0)

// 当前选中索引
const currentIndex = computed(() => props.current)

// 触摸状态（用于点击动效）
const tappingIndex = ref<number | null>(null)

// 触摸开始
function onTouchStart(index: number) {
  tappingIndex.value = index
}

// 触摸结束
function onTouchEnd() {
  tappingIndex.value = null
}

// TabBar 配置
const tabList: TabItem[] = [
  {
    pagePath: '/pages/index/index',
    textKey: 'tabbar.home',
    icon: 'bi-house',
    activeIcon: 'bi-house-check-fill',
  },
  {
    pagePath: '/pages/category/index',
    textKey: 'tabbar.category',
    icon: 'bi-grid',
    activeIcon: 'bi-grid-fill',
  },
  {
    pagePath: '/pages/sell/index',
    textKey: 'tabbar.sell',
    icon: 'bi-tag',
    activeIcon: 'bi-tag-fill',
  },
  {
    pagePath: '/pages/message/index',
    textKey: 'tabbar.message',
    icon: 'bi-chat-dots',
    activeIcon: 'bi-chat-dots-fill',
    showBadge: true,
  },
  {
    pagePath: '/pages/profile/index',
    textKey: 'tabbar.profile',
    icon: 'bi-person-badge',
    activeIcon: 'bi-person-badge-fill',
  },
]

// 切换标签
function switchTab(item: TabItem, index: number) {
  if (currentIndex.value === index) return

  emit('change', index)

  uni.switchTab({
    url: item.pagePath,
    fail: (err) => {
      console.error('Switch tab failed:', err)
    }
  })
}

// 获取安全区域
onMounted(() => {
  uni.getSystemInfo({
    success: (res) => {
      safeAreaBottom.value = res.safeAreaInsets?.bottom || 0
    }
  })
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - 与优惠券页面保持一致
// ==========================================

// 色彩系统
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-accent-light: #FFF4F0;
$color-background: #FAFAF9;
$color-surface: #fafafa;
$color-border: #E7E5E4;

// 字体系统
$font-family-base: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

// 字号
$font-size-xs: 10px;
$font-size-sm: 11px;

// 字重
$font-weight-medium: 500;
$font-weight-semibold: 600;

// 圆角
$radius-full: 9999px;

// 间距
$spacing-xs: 4px;
$spacing-sm: 6px;

// ==========================================
// TabBar 样式
// ==========================================

.custom-tabbar {
  position: fixed;
  padding: 30rpx 0 0 0;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: $color-surface;
  z-index: 999;
  font-family: $font-family-base;
}

.tabbar-content {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 56px;
  padding: 0 8px;
}

.tabbar-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  height: 100%;
  padding: 6px 0 8px;
  cursor: pointer;
  transition: transform 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              opacity 0.15s ease-out;
  -webkit-tap-highlight-color: transparent;

  // 点击态（通过 is-tapping class 控制）
  &.is-tapping {
    transform: scale(0.92);
    opacity: 0.8;
  }

  // 普通状态
  .icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 28px;
    margin-bottom: 2px;
    border-radius: 14px;
    background-color: transparent;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
                background-color 0.2s ease-out;

    .bi {
      font-size: 22px;
      color: $color-primary;
      transition: color 0.15s ease-out, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
  }

  .tabbar-text {
    font-size: $font-size-sm;
    font-weight: $font-weight-medium;
    color: $color-primary;
    transition: color 0.15s ease-out, transform 0.15s ease-out;
    white-space: nowrap;
  }

  // 点击时图标弹跳效果
  &.is-tapping .icon-wrapper {
    transform: scale(0.85);
  }

  // 选中状态
  &.active {
    .icon-wrapper {
      background-color: $color-accent-light;

      .bi {
        color: $color-accent;
      }
    }

    .tabbar-text {
      color: $color-accent;
      font-weight: $font-weight-semibold;
    }

    // 选中项点击时图标放大
    &.is-tapping .icon-wrapper .bi {
      transform: scale(1.15);
    }
  }

}

// 消息红点
.badge {
  position: absolute;
  top: -4px;
  right: -8px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  background-color: #DC2626;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-text {
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  color: #FFFFFF;
  line-height: 1;
}

// 响应式 - 减少动画
@media (prefers-reduced-motion: reduce) {
  .tabbar-item,
  .center-btn,
  .icon-wrapper .bi,
  .tabbar-text {
    transition: none;
  }
}
</style>
