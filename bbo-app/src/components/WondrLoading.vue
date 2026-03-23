<!--
  ============================================================
  WondrLoading - Wondr by BNI 风格加载组件
  ============================================================

  参考 Wondr by BNI App 设计的加载组件：
  - 圆形 spinner 旋转动画
  - #71dbd3 品牌青色
  - 简洁、现代、专业

  ============================================================
  使用方法 (Usage)
  ============================================================

  全屏加载：
     <WondrLoading v-if="loading" />

  带文字：
     <WondrLoading v-if="loading" :show-text="true" text="Loading..." />

  内联加载（非全屏）：
     <WondrLoading v-if="loading" :fullscreen="false" />

  ============================================================
-->

<template>
    <view class="wondr-loading" :class="{ 'is-fullscreen': fullscreen }">
        <view class="loading-content">
            <!-- Wondr 风格圆形 Spinner -->
            <view class="spinner"></view>

            <!-- 加载文字 (可选) -->
            <text v-if="showText" class="loading-text">{{ text }}</text>
        </view>
    </view>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
    name: 'WondrLoading',
    props: {
        /** 是否全屏覆盖 */
        fullscreen: {
            type: Boolean,
            default: true
        },
        /** 是否显示加载文字 */
        showText: {
            type: Boolean,
            default: false
        },
        /** 加载文字内容 */
        text: {
            type: String,
            default: 'Loading...'
        }
    }
})
</script>

<style lang="scss" scoped>
// Wondr 品牌色
$wondr-primary: #71dbd3;
$wondr-bg: #FFFFFF;

.wondr-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: $wondr-bg;

    // 全屏模式
    &.is-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
    }
}

.loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24rpx;
}

// Wondr 风格圆形 Spinner
.spinner {
    width: 48rpx;
    height: 48rpx;
    border: 4rpx solid $wondr-primary;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

// 加载文字
.loading-text {
    font-size: 28rpx;
    color: #6B7280;
    font-weight: 400;
}

// 旋转动画
@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

// 无障碍：减少动画
@media (prefers-reduced-motion: reduce) {
    .spinner {
        animation-duration: 1.5s;
    }
}
</style>
