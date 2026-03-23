<template>
  <view
    v-if="visible"
    class="game-float-btn"
    :style="{ bottom: `${bottom}rpx`, right: `${right}rpx` }"
    @click="handleClick"
  >
    <!-- 主按钮 -->
    <view class="btn-main" :class="{ 'has-chances': hasChances }">
      <view class="btn-icon">
        <text class="bi bi-gift"></text>
      </view>
    </view>

    <!-- 次数徽章 -->
    <view v-if="totalChances > 0" class="btn-badge">
      <text>{{ totalChances > 99 ? '99+' : totalChances }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { getUserGameChances, type UserGameChances } from '@/api/game'
import { useUserStore } from '@/store/modules/user'

const props = withDefaults(defineProps<{
  bottom?: number
  right?: number
  autoFetch?: boolean
  fetchInterval?: number
}>(), {
  bottom: 200,
  right: 32,
  autoFetch: true,
  fetchInterval: 60000 // 1 分钟
})

const emit = defineEmits<{
  (e: 'click'): void
}>()

const userStore = useUserStore()

const visible = ref(true)
const chances = ref<UserGameChances[]>([])

let fetchTimer: ReturnType<typeof setInterval> | null = null

// 总次数
const totalChances = computed(() => {
  return chances.value.reduce((sum, c) => sum + c.chances, 0)
})

// 是否有次数
const hasChances = computed(() => totalChances.value > 0)

// 获取次数
async function fetchChances() {
  // 未登录时不请求
  if (!userStore.isLoggedIn) {
    chances.value = []
    return
  }

  try {
    const res = await getUserGameChances()
    if (res.code === 0) {
      chances.value = res.data || []
    }
  } catch (error) {
    console.error('Failed to fetch game chances:', error)
  }
}

// 点击按钮
function handleClick() {
  emit('click')
  uni.navigateTo({ url: '/pages/game/index' })
}

// 显示/隐藏
function show() {
  visible.value = true
}

function hide() {
  visible.value = false
}

// 刷新次数
function refresh() {
  fetchChances()
}

onMounted(() => {
  if (props.autoFetch) {
    fetchChances()
    fetchTimer = setInterval(fetchChances, props.fetchInterval)
  }
})

onUnmounted(() => {
  if (fetchTimer) {
    clearInterval(fetchTimer)
    fetchTimer = null
  }
})

// 暴露方法
defineExpose({
  show,
  hide,
  refresh,
  setChances: (data: UserGameChances[]) => {
    chances.value = data
  }
})
</script>

<style lang="scss" scoped>
$primary: #FF6B35;
$primary-dark: #E85A2A;
$badge-red: #EF4444;

.game-float-btn {
  position: fixed;
  z-index: 100;
  width: 96rpx;
  height: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.btn-main {
  position: relative;
  width: 96rpx;
  height: 96rpx;
  border-radius: 50%;
  background: $primary;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4rpx 16rpx rgba($primary, 0.35);
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &.has-chances {
    animation: gentle-bounce 3s ease-in-out infinite;
  }

  &:active {
    transform: scale(0.92);
    box-shadow: 0 2rpx 8rpx rgba($primary, 0.25);
  }
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: 40rpx;
    color: #fff;
  }
}

.btn-badge {
  position: absolute;
  top: -4rpx;
  right: -4rpx;
  min-width: 36rpx;
  height: 36rpx;
  padding: 0 8rpx;
  background: $badge-red;
  border-radius: 18rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3rpx solid #fff;
  box-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.15);

  text {
    font-size: 20rpx;
    font-weight: 600;
    color: #fff;
    line-height: 1;
  }
}

@keyframes gentle-bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6rpx);
  }
}

// 响应减弱动效
@media (prefers-reduced-motion: reduce) {
  .btn-main.has-chances {
    animation: none;
  }
}
</style>
