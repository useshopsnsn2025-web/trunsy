<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('withdrawal.select_method')" />

    <view class="page-content">
      <!-- 加载状态 -->
      <LoadingPage v-if="loading" />

      <!-- 空状态 -->
      <view v-else-if="!methods.length" class="empty-state">
        <view class="empty-icon">💳</view>
        <view class="empty-text">{{ t('withdrawal.no_methods_available') }}</view>
      </view>

      <!-- 列表 -->
      <view v-else class="methods-list">
      <view
        v-for="method in methods"
        :key="method.id"
        class="method-item"
        @tap="handleMethodSelect(method)"
      >
        <view class="method-content">
          <!-- Logo -->
          <image
            v-if="method.logo"
            :src="method.logo"
            class="method-logo"
            mode="aspectFit"
            @error="handleImageError(method)"
          />
          <view v-else class="method-logo-placeholder">
            <text class="placeholder-text">{{ method.code.substring(0, 3).toUpperCase() }}</text>
          </view>

          <!-- 名称 -->
          <!-- <view class="method-name">{{ method.name }}</view> -->

          <!-- 右箭头图标 -->
          <text class="arrow-icon">›</text>
        </view>
      </view>
    </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getWithdrawalMethods, type WithdrawalMethod } from '@/api/wallet'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const methods = ref<WithdrawalMethod[]>([])

// 获取提现方式列表
const fetchMethods = async () => {
  try {
    loading.value = true
    const res = await getWithdrawalMethods(appStore.region)
    if (res.code === 0 && res.data) {
      methods.value = res.data
    } else {
      toast.error(res.msg || t('common.loadFailed'))
    }
  } catch (error) {
    console.error('Failed to fetch withdrawal methods:', error)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 图片加载失败处理
const handleImageError = (method: WithdrawalMethod) => {
  console.error('Failed to load logo for:', method.code, method.logo)
  // 将 logo 设置为空，显示占位符
  method.logo = ''
}

// 选择提现方式
const handleMethodSelect = (method: WithdrawalMethod) => {
  if (!method.routePath) {
    toast.warning(t('withdrawal.method_not_available'))
    return
  }

  // 跳转到对应的绑定页面，传递 code 参数，如果有 logo 也传递
  let url = `${method.routePath}?code=${method.code}`
  if (method.logo) {
    url += `&logo=${encodeURIComponent(method.logo)}`
  }

  console.log('Navigate to:', url, 'Logo:', method.logo)

  uni.navigateTo({ url })
}

onShow(() => {
  fetchMethods()
})
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  background-color: #F5F5F5;
}

.page-content {
  padding: 24rpx;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 200rpx 40rpx;
  text-align: center;

  .empty-icon {
    font-size: 120rpx;
    margin-bottom: 24rpx;
    opacity: 0.5;
  }

  .empty-text {
    font-size: 28rpx;
    color: #999999;
  }
}

.methods-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.method-item {
  background-color: #FFFFFF;
  border-radius: 16rpx;
  overflow: hidden;
  transition: all 0.2s ease;
  cursor: pointer;

  &:active {
    transform: scale(0.98);
    opacity: 0.9;
  }
}

.method-content {
  display: flex;
  align-items: center;
  padding: 32rpx 28rpx;
  gap: 24rpx;
  justify-content: space-between;
}

.method-logo {
  width: 160rpx;
  height: 72rpx;
  flex-shrink: 0;
  object-fit: contain;
}

.method-logo-placeholder {
  width: 160rpx;
  height: 72rpx;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8rpx;
}

.placeholder-text {
  font-size: 22rpx;
  font-weight: 600;
  color: #FFFFFF;
  letter-spacing: 1rpx;
}

.method-name {
  flex: 1;
  font-size: 32rpx;
  font-weight: 500;
  color: #333333;
}

.arrow-icon {
  font-size: 48rpx;
  color: #CCCCCC;
  font-weight: 300;
  line-height: 1;
  flex-shrink: 0;
}

// 悬停效果（H5）
@media (hover: hover) {
  .method-item:hover {
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
    transform: translateY(-2rpx);
  }
}
</style>
