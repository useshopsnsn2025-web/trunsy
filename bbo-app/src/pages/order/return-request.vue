<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('return.pageTitle')" />

    <view v-if="order" class="content">
      <!-- 商品信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-box-seam"></text>
          <text class="section-title">{{ t('return.orderInfo') }}</text>
        </view>
        <view class="goods-item">
          <image :src="order.goods_snapshot?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <text class="goods-title">{{ order.goods_snapshot?.title }}</text>
            <view class="goods-price-row">
              <text class="goods-price">{{ formatPrice(order.total_amount, order.currency) }}</text>
              <text class="order-no">{{ order.order_no }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 退货类型 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-arrow-left-right"></text>
          <text class="section-title">{{ t('return.type') }}</text>
        </view>
        <view class="type-options">
          <view
            class="type-option"
            :class="{ active: returnType === 1 }"
            @click="returnType = 1"
          >
            <text class="bi" :class="returnType === 1 ? 'bi-check-circle-fill' : 'bi-circle'"></text>
            <text class="type-label">{{ t('return.typeRefundOnly') }}</text>
          </view>
          <view
            class="type-option"
            :class="{ active: returnType === 2 }"
            @click="returnType = 2"
          >
            <text class="bi" :class="returnType === 2 ? 'bi-check-circle-fill' : 'bi-circle'"></text>
            <text class="type-label">{{ t('return.typeReturnRefund') }}</text>
          </view>
        </view>
      </view>

      <!-- 退货原因 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-question-circle"></text>
          <text class="section-title">{{ t('return.reason') }} <text class="required">*</text></text>
        </view>
        <view class="reason-options">
          <view
            v-for="reason in reasonOptions"
            :key="reason.code"
            class="reason-option"
            :class="{ active: selectedReason === reason.code }"
            @click="selectedReason = reason.code"
          >
            <text class="reason-label">{{ reason.label }}</text>
            <text v-if="selectedReason === reason.code" class="bi bi-check"></text>
          </view>
        </view>
      </view>

      <!-- 详细说明 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-chat-left-text"></text>
          <text class="section-title">{{ t('return.reasonDetail') }}</text>
        </view>
        <textarea
          v-model="reasonDetail"
          class="detail-textarea"
          :placeholder="t('return.reasonDetailPlaceholder')"
          :maxlength="500"
        />
        <view class="char-count">{{ reasonDetail.length }}/500</view>
      </view>

      <!-- 上传图片 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-image"></text>
          <text class="section-title">{{ t('return.uploadImages') }}</text>
        </view>
        <view class="upload-hint">{{ t('return.uploadImagesHint') }}</view>
        <view class="image-list">
          <view v-for="(img, index) in images" :key="index" class="image-item">
            <image :src="img" mode="aspectFill" class="preview-image" />
            <view class="remove-btn" @click="removeImage(index)">
              <text class="bi bi-x"></text>
            </view>
          </view>
          <view v-if="images.length < 9" class="upload-btn" @click="chooseImage">
            <text class="bi bi-plus"></text>
            <text class="upload-text">{{ images.length }}/9</text>
          </view>
        </view>
      </view>

      <!-- 退款金额 -->
      <view class="section-card refund-amount-card">
        <view class="refund-row">
          <text class="refund-label">{{ t('return.refundAmount') }}</text>
          <text class="refund-value">{{ formatPrice(order.total_amount, order.currency) }}</text>
        </view>
      </view>

      <!-- 温馨提示 -->
      <view class="tips-card">
        <view class="tips-header">
          <text class="bi bi-lightbulb"></text>
          <text class="tips-title">{{ t('return.tips') }}</text>
        </view>
        <view class="tips-list">
          <view class="tip-item">
            <text class="tip-dot">•</text>
            <text class="tip-text">{{ t('return.tip1') }}</text>
          </view>
          <view class="tip-item">
            <text class="tip-dot">•</text>
            <text class="tip-text">{{ t('return.tip2') }}</text>
          </view>
          <view class="tip-item">
            <text class="tip-dot">•</text>
            <text class="tip-text">{{ t('return.tip3') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部提交按钮 -->
    <view class="bottom-actions">
      <button class="btn-primary" :disabled="!canSubmit || submitting" @click="submitRequest">
        <text v-if="submitting" class="bi bi-arrow-repeat spinning"></text>
        {{ t('return.submit') }}
      </button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import { getOrderDetail } from '@/api/order'
import { createReturn } from '@/api/return'
import { uploadImage } from '@/api/upload'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const submitting = ref(false)
const orderId = ref<number | null>(null)
const order = ref<any>(null)

// 表单数据
const returnType = ref(2) // 1=仅退款 2=退货退款
const selectedReason = ref('')
const reasonDetail = ref('')
const images = ref<string[]>([])

// 退货原因选项
const reasonOptions = computed(() => [
  { code: 'not_as_described', label: t('return.reasons.not_as_described') },
  { code: 'wrong_item', label: t('return.reasons.wrong_item') },
  { code: 'damaged', label: t('return.reasons.damaged') },
  { code: 'missing_parts', label: t('return.reasons.missing_parts') },
  { code: 'quality_issue', label: t('return.reasons.quality_issue') },
  { code: 'not_needed', label: t('return.reasons.not_needed') },
  { code: 'better_price', label: t('return.reasons.better_price') },
  { code: 'other', label: t('return.reasons.other') },
])

// 是否可以提交
const canSubmit = computed(() => {
  return selectedReason.value !== ''
})

// 格式化价格
function formatPrice(amount: number | undefined, currency?: string) {
  if (amount === undefined || amount === null) return ''
  return appStore.formatPrice(amount, currency || 'USD')
}

// 加载订单信息
async function loadOrderInfo() {
  if (!orderId.value) {
    loading.value = false
    return
  }

  try {
    const res = await getOrderDetail(orderId.value)
    if (res.code === 0) {
      order.value = res.data
    }
  } catch (e) {
    console.error('Failed to load order:', e)
  } finally {
    loading.value = false
  }
}

// 选择图片
function chooseImage() {
  uni.chooseImage({
    count: 9 - images.value.length,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      for (const tempPath of res.tempFilePaths) {
        try {
          uni.showLoading({ title: '' })
          const uploadRes = await uploadImage(tempPath)
          if (uploadRes.code === 0 && uploadRes.data?.url) {
            images.value.push(uploadRes.data.url)
          }
        } catch (e) {
          console.error('Failed to upload image:', e)
        } finally {
          uni.hideLoading()
        }
      }
    }
  })
}

// 移除图片
function removeImage(index: number) {
  images.value.splice(index, 1)
}

// 提交退货申请
async function submitRequest() {
  if (!canSubmit.value || submitting.value) return

  submitting.value = true
  try {
    uni.showLoading({ title: '' })
    const res = await createReturn({
      order_id: orderId.value!,
      type: returnType.value,
      reason: selectedReason.value,
      reason_detail: reasonDetail.value,
      images: images.value
    })

    if (res.code === 0) {
      toast.success(t('return.submitSuccess'))
      // 跳转到退货详情页
      setTimeout(() => {
        uni.redirectTo({
          url: `/pages/order/return-detail?id=${res.data.id}`
        })
      }, 1000)
    } else {
      toast.error(res.msg || t('common.error'))
    }
  } catch (e) {
    console.error('Failed to submit return request:', e)
    toast.error(t('common.error'))
  } finally {
    submitting.value = false
    uni.hideLoading()
  }
}

onLoad((options) => {
  if (options?.orderId) {
    orderId.value = parseInt(options.orderId)
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('return.pageTitle') })
  if (orderId.value) {
    loadOrderInfo()
  } else {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.content {
  padding: 24rpx;
}

/* 通用卡片样式 */
.section-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 24rpx;

  .bi {
    font-size: 32rpx;
    color: #FF6B35;
    margin-right: 12rpx;
  }
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #333;

  .required {
    color: #ff4d4f;
  }
}

/* 商品信息 */
.goods-item {
  display: flex;
}

.goods-image {
  width: 120rpx;
  height: 120rpx;
  border-radius: 12rpx;
  background: #f5f5f5;
  margin-right: 20rpx;
  flex-shrink: 0;
}

.goods-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.goods-title {
  font-size: 28rpx;
  color: #333;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.goods-price {
  font-size: 28rpx;
  font-weight: 600;
  color: #FF6B35;
}

.order-no {
  font-size: 24rpx;
  color: #999;
}

/* 退货类型 */
.type-options {
  display: flex;
  gap: 20rpx;
}

.type-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx;
  border: 2rpx solid #e5e5e5;
  border-radius: 12rpx;
  transition: all 0.2s;

  &.active {
    border-color: #FF6B35;
    background: rgba(255, 107, 53, 0.05);
  }

  .bi {
    font-size: 32rpx;
    margin-right: 12rpx;
    color: #ccc;
  }

  &.active .bi {
    color: #FF6B35;
  }
}

.type-label {
  font-size: 28rpx;
  color: #333;
}

/* 退货原因 */
.reason-options {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.reason-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  border: 2rpx solid transparent;
  transition: all 0.2s;

  &.active {
    background: rgba(255, 107, 53, 0.05);
    border-color: #FF6B35;
  }

  .bi {
    font-size: 32rpx;
    color: #FF6B35;
  }
}

.reason-label {
  font-size: 28rpx;
  color: #333;
}

/* 详细说明 */
.detail-textarea {
  width: 100%;
  height: 200rpx;
  padding: 20rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  font-size: 28rpx;
  color: #333;
  line-height: 1.6;
  box-sizing: border-box;
}

.char-count {
  text-align: right;
  font-size: 24rpx;
  color: #999;
  margin-top: 12rpx;
}

/* 上传图片 */
.upload-hint {
  font-size: 24rpx;
  color: #999;
  margin-bottom: 20rpx;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.image-item {
  position: relative;
  width: 160rpx;
  height: 160rpx;
}

.preview-image {
  width: 100%;
  height: 100%;
  border-radius: 12rpx;
}

.remove-btn {
  position: absolute;
  top: -12rpx;
  right: -12rpx;
  width: 40rpx;
  height: 40rpx;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 24rpx;
    color: #fff;
  }
}

.upload-btn {
  width: 160rpx;
  height: 160rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f9f9f9;
  border: 2rpx dashed #ddd;
  border-radius: 12rpx;

  .bi {
    font-size: 48rpx;
    color: #ccc;
  }
}

.upload-text {
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

/* 退款金额 */
.refund-amount-card {
  background: linear-gradient(135deg, #fff5f0 0%, #fff 100%);
}

.refund-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.refund-label {
  font-size: 28rpx;
  color: #666;
}

.refund-value {
  font-size: 36rpx;
  font-weight: 600;
  color: #FF6B35;
}

/* 温馨提示 */
.tips-card {
  background: #fffbe6;
  border-radius: 16rpx;
  padding: 24rpx;
  margin-bottom: 24rpx;
}

.tips-header {
  display: flex;
  align-items: center;
  margin-bottom: 16rpx;

  .bi {
    font-size: 28rpx;
    color: #faad14;
    margin-right: 8rpx;
  }
}

.tips-title {
  font-size: 28rpx;
  font-weight: 500;
  color: #333;
}

.tips-list {
  padding-left: 36rpx;
}

.tip-item {
  display: flex;
  margin-bottom: 8rpx;
}

.tip-dot {
  color: #faad14;
  margin-right: 8rpx;
}

.tip-text {
  font-size: 26rpx;
  color: #666;
  line-height: 1.5;
}

/* 底部按钮 */
.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20rpx 24rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.btn-primary {
  width: 100%;
  height: 88rpx;
  background: #FF6B35 !important;
  color: #fff !important;
  font-size: 32rpx;
  font-weight: 500;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;

  &::after {
    border: none;
  }

  &[disabled] {
    opacity: 0.5;
  }
}

.spinning {
  animation: spin 1s linear infinite;
  margin-right: 8rpx;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
