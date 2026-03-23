<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="brandName" background="#FF6B35" color="#FFFFFF" />

    <!-- 加载中 -->
    <LoadingPage v-model="loading" />

    <!-- 内容 -->
    <scroll-view v-if="!loading" scroll-y class="content-scroll">
      <!-- 产品列表 -->
      <view class="models-list">
        <view
          v-for="model in models"
          :key="model.value"
          class="model-item"
          @click="goGoodsList(model)"
        >
          <view class="model-image-wrapper">
            <image
              v-if="model.image"
              class="model-image"
              :src="model.image"
              mode="aspectFit"
            />
            <view v-else class="model-image-placeholder">
              <text class="bi bi-phone"></text>
            </view>
          </view>
          <view class="model-info">
            <text class="model-name">{{ model.name }}</text>
            <text v-if="model.min_price !== null" class="model-price">
              {{ formatPrice(model.min_price) }}+
            </text>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && models.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('common.noData') }}</text>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getBrandModels, type BrandModelInfo } from '@/api/goods'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const brandValue = ref<string>('')
const brandName = ref<string>('')
const models = ref<BrandModelInfo[]>([])
const loading = ref(true)

// 格式化价格
function formatPrice(price: number): string {
  return appStore.formatPrice(price)
}

// 加载数据
async function loadData() {
  if (!brandValue.value) return

  loading.value = true
  try {
    const res = await getBrandModels(brandValue.value)
    models.value = (res.data.models || []).filter((m: any) => m.min_price !== null && m.min_price !== undefined)
    brandName.value = res.data.brand_name

    // 设置页面标题
    uni.setNavigationBarTitle({ title: brandName.value })
  } catch (e) {
    console.error('Failed to load brand models:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 跳转到型号商品列表
function goGoodsList(model: BrandModelInfo) {
  // 跳转到型号商品列表页，传递分类ID、型号筛选和图片
  let url = model.category_id
    ? `/pages/goods/model-goods?categoryId=${model.category_id}&model=${encodeURIComponent(model.value)}&title=${encodeURIComponent(model.name)}`
    : `/pages/goods/model-goods?model=${encodeURIComponent(model.value)}&title=${encodeURIComponent(model.name)}`

  // 传递图片和最低价
  if (model.image) {
    url += `&image=${encodeURIComponent(model.image)}`
  }
  if (model.min_price !== null) {
    url += `&min_price=${model.min_price}`
  }

  uni.navigateTo({ url })
}

onLoad((options) => {
  brandValue.value = options?.value || ''
  if (options?.name) {
    brandName.value = decodeURIComponent(options.name)
    uni.setNavigationBarTitle({ title: brandName.value })
  }
})

onMounted(() => {
  loadData()
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff;
}

.content-scroll {
  flex: 1;
}

.models-list {
  padding: 20rpx 30rpx;
}

.model-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 30rpx 0;
  border-bottom: 1rpx solid #f0f0f0;
}

.model-item:last-child {
  border-bottom: none;
}

.model-image-wrapper {
  width: 140rpx;
  height: 140rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-right: 30rpx;
}

.model-image {
  width: 120rpx;
  height: 120rpx;
}

.model-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 120rpx;
  height: 120rpx;
  background-color: #f5f5f5;
  border-radius: 16rpx;
  font-size: 60rpx;
  color: #ccc;
}

.model-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.model-name {
  font-size: 30rpx;
  color: #333;
  line-height: 1.4;
  word-break: break-word;
}

.model-price {
  margin-top: 10rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: #FF6B35;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 100rpx 0;
}

.empty-icon {
  font-size: 120rpx;
  color: #ddd;
  margin-bottom: 20rpx;
}

.empty-text {
  font-size: 28rpx;
  color: #999;
}
</style>
