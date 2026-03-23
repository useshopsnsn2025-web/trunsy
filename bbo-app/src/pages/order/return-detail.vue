<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('return.pageTitle')" />

    <view v-if="returnInfo" class="content">
      <!-- 状态卡片 -->
      <view class="status-card" :class="getStatusClass()">
        <view class="status-icon">
          <text :class="getStatusIcon()"></text>
        </view>
        <view class="status-info">
          <text class="status-text">{{ returnInfo.status_text }}</text>
          <text class="status-desc">{{ getStatusDesc() }}</text>
        </view>
      </view>

      <!-- 商品信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-box-seam"></text>
          <text class="section-title">{{ t('return.orderInfo') }}</text>
        </view>
        <view class="goods-item">
          <image :src="returnInfo.goods_snapshot?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <text class="goods-title">{{ returnInfo.goods_snapshot?.title }}</text>
            <view class="goods-meta">
              <text class="order-no">{{ returnInfo.order_no }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 退货信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-file-text"></text>
          <text class="section-title">{{ t('return.title') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('return.type') }}</text>
            <text class="info-value">{{ returnInfo.type_text }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('return.reason') }}</text>
            <text class="info-value">{{ returnInfo.reason_text }}</text>
          </view>
          <view v-if="returnInfo.reason_detail" class="info-row detail-row">
            <text class="info-label">{{ t('return.reasonDetail') }}</text>
            <text class="info-value">{{ returnInfo.reason_detail }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('return.refundAmount') }}</text>
            <text class="info-value highlight">{{ formatPrice(returnInfo.refund_amount, returnInfo.currency) }}</text>
          </view>
        </view>

        <!-- 图片证据 -->
        <view v-if="returnInfo.images && returnInfo.images.length > 0" class="evidence-images">
          <view class="evidence-label">{{ t('return.uploadImages') }}</view>
          <view class="image-list">
            <image
              v-for="(img, index) in returnInfo.images"
              :key="index"
              :src="img"
              mode="aspectFill"
              class="evidence-image"
              @click="previewImage(img)"
            />
          </view>
        </view>
      </view>

      <!-- 卖家回复 -->
      <view v-if="returnInfo.seller_reply" class="section-card">
        <view class="section-header">
          <text class="bi bi-chat-left-text"></text>
          <text class="section-title">{{ t('return.sellerReply') }}</text>
        </view>
        <view class="seller-reply">
          <text class="reply-content">{{ returnInfo.seller_reply }}</text>
          <text class="reply-time">{{ returnInfo.seller_replied_at }}</text>
        </view>
      </view>

      <!-- 拒绝原因 -->
      <view v-if="returnInfo.status === 2 && returnInfo.reject_reason" class="section-card reject-card">
        <view class="section-header">
          <text class="bi bi-exclamation-circle"></text>
          <text class="section-title">{{ t('return.rejectReason') }}</text>
        </view>
        <text class="reject-reason">{{ returnInfo.reject_reason }}</text>
      </view>

      <!-- 退货物流（已批准状态，退货退款类型） -->
      <view v-if="Number(returnInfo.status) === 1 && Number(returnInfo.type) === 2" class="section-card shipping-card">
        <view class="section-header">
          <text class="bi bi-truck"></text>
          <text class="section-title">{{ t('return.shipReturn') }}</text>
        </view>
        <view class="shipping-form">
          <view class="form-item">
            <text class="form-label">{{ t('return.carrier') }}</text>
            <input v-model="shipCarrier" class="form-input" :placeholder="t('return.carrierPlaceholder')" />
          </view>
          <view class="form-item">
            <text class="form-label">{{ t('return.trackingNumber') }}</text>
            <input v-model="shipTrackingNo" class="form-input" :placeholder="t('return.trackingPlaceholder')" />
          </view>
          <button class="ship-btn" :disabled="!shipTrackingNo" @click="submitShipping">
            {{ t('return.submitShipping') }}
          </button>
        </view>
      </view>

      <!-- 退货物流信息（已发货） -->
      <view v-if="returnInfo.return_tracking_no" class="section-card">
        <view class="section-header">
          <text class="bi bi-truck"></text>
          <text class="section-title">{{ t('return.returnShipping') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('return.carrier') }}</text>
            <text class="info-value">{{ returnInfo.return_carrier || '-' }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('return.trackingNumber') }}</text>
            <text class="info-value">{{ returnInfo.return_tracking_no }}</text>
          </view>
          <view v-if="returnInfo.shipped_at" class="info-row">
            <text class="info-label">{{ t('return.shippedAt') }}</text>
            <text class="info-value">{{ returnInfo.shipped_at }}</text>
          </view>
        </view>
      </view>

      <!-- 时间线 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-clock-history"></text>
          <text class="section-title">{{ t('return.timeline') }}</text>
        </view>
        <view class="timeline">
          <view class="timeline-item" :class="{ active: true }">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('return.timelineSubmitted') }}</text>
              <text class="timeline-time">{{ returnInfo.created_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.seller_replied_at" class="timeline-item" :class="{ active: true }">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('return.timelineSellerResponded') }}</text>
              <text class="timeline-time">{{ returnInfo.seller_replied_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.shipped_at" class="timeline-item" :class="{ active: true }">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('return.timelineShipped') }}</text>
              <text class="timeline-time">{{ returnInfo.shipped_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.received_at" class="timeline-item" :class="{ active: true }">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('return.timelineReceived') }}</text>
              <text class="timeline-time">{{ returnInfo.received_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.refunded_at" class="timeline-item" :class="{ active: true }">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('return.timelineRefunded') }}</text>
              <text class="timeline-time">{{ returnInfo.refunded_at }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部操作按钮 -->
    <view v-if="returnInfo && returnInfo.status === 0" class="bottom-actions">
      <button class="btn-secondary" @click="handleCancel">
        {{ t('return.cancelRequest') }}
      </button>
    </view>

    <!-- 取消确认弹窗 -->
    <ConfirmDialog
      :visible="showCancelDialog"
      :title="t('return.cancelDialogTitle')"
      :content="t('return.cancelDialogContent')"
      icon="bi-x-circle"
      icon-type="warning"
      @update:visible="showCancelDialog = $event"
      @confirm="confirmCancel"
    />
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { getReturnDetail, getReturnByOrder, cancelReturn, shipReturn, type ReturnInfo } from '@/api/return'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const returnId = ref<number | null>(null)
const orderId = ref<number | null>(null)
const returnInfo = ref<ReturnInfo | null>(null)

// 弹窗状态
const showCancelDialog = ref(false)

// 发货表单
const shipCarrier = ref('')
const shipTrackingNo = ref('')

// 格式化价格
function formatPrice(amount: number | undefined, currency?: string) {
  if (amount === undefined || amount === null) return ''
  return appStore.formatPrice(amount, currency || 'USD')
}

// 获取状态样式类
function getStatusClass() {
  if (!returnInfo.value) return ''
  const statusMap: Record<number, string> = {
    0: 'status-pending',
    1: 'status-approved',
    2: 'status-rejected',
    3: 'status-cancelled',
    4: 'status-in-return',
    5: 'status-completed'
  }
  return statusMap[returnInfo.value.status] || ''
}

// 获取状态图标
function getStatusIcon() {
  if (!returnInfo.value) return 'bi bi-clock'
  const iconMap: Record<number, string> = {
    0: 'bi bi-clock',
    1: 'bi bi-check-circle',
    2: 'bi bi-x-circle',
    3: 'bi bi-slash-circle',
    4: 'bi bi-truck',
    5: 'bi bi-check2-circle'
  }
  return iconMap[returnInfo.value.status] || 'bi bi-clock'
}

// 获取状态描述
function getStatusDesc() {
  if (!returnInfo.value) return ''
  const descMap: Record<number, string> = {
    0: t('return.statusDescPending'),
    1: t('return.statusDescApproved'),
    2: t('return.statusDescRejected'),
    3: t('return.statusDescCancelled'),
    4: t('return.statusDescInReturn'),
    5: t('return.statusDescCompleted')
  }
  return descMap[returnInfo.value.status] || ''
}

// 加载退货详情
async function loadReturnInfo() {
  try {
    let res
    if (returnId.value) {
      // 通过退货ID加载
      res = await getReturnDetail(returnId.value)
    } else if (orderId.value) {
      // 通过订单ID加载
      res = await getReturnByOrder(orderId.value)
    } else {
      loading.value = false
      return
    }

    if (res.code === 0) {
      returnInfo.value = res.data
      returnId.value = res.data.id
    }
  } catch (e) {
    console.error('Failed to load return info:', e)
  } finally {
    loading.value = false
  }
}

// 预览图片
function previewImage(url: string) {
  uni.previewImage({
    current: url,
    urls: returnInfo.value?.images || [url]
  })
}

// 取消退货申请
function handleCancel() {
  showCancelDialog.value = true
}

async function confirmCancel() {
  if (!returnId.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await cancelReturn(returnId.value)
    if (res.code === 0) {
      toast.success(t('return.cancelSuccess'))
      loadReturnInfo()
    } else {
      toast.error(res.msg || 'Failed to cancel')
    }
  } catch (e) {
    toast.error('Failed to cancel')
  } finally {
    uni.hideLoading()
  }
}

// 提交物流信息
async function submitShipping() {
  if (!returnId.value || !shipTrackingNo.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await shipReturn(returnId.value, {
      tracking_no: shipTrackingNo.value,
      carrier: shipCarrier.value
    })
    if (res.code === 0) {
      toast.success(t('return.shippingSubmitted'))
      loadReturnInfo()
    } else {
      toast.error(res.msg || 'Failed to submit')
    }
  } catch (e) {
    toast.error('Failed to submit')
  } finally {
    uni.hideLoading()
  }
}

onLoad((options) => {
  if (options?.id) {
    returnId.value = parseInt(options.id)
  }
  if (options?.orderId) {
    orderId.value = parseInt(options.orderId)
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('return.pageTitle') })
  if (returnId.value || orderId.value) {
    loadReturnInfo()
  } else {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
.page {
  height: 100vh;
  background-color: #f5f5f5;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
}

.content {
  padding: 24rpx;
  flex: 1;
  overflow-y: auto;
}

/* 状态卡片 */
.status-card {
  display: flex;
  align-items: center;
  padding: 32rpx;
  background: linear-gradient(135deg, #faad14 0%, #ffc53d 100%);
  border-radius: 16rpx;
  margin-bottom: 24rpx;

  &.status-approved {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
  }

  &.status-rejected,
  &.status-cancelled {
    background: linear-gradient(135deg, #8c8c8c 0%, #bfbfbf 100%);
  }

  &.status-completed {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
  }

  &.status-in-return {
    background: linear-gradient(135deg, #1890ff 0%, #40a9ff 100%);
  }
}

.status-icon {
  width: 80rpx;
  height: 80rpx;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;

  .bi {
    font-size: 40rpx;
    color: #fff;
  }
}

.status-info {
  flex: 1;
}

.status-text {
  display: block;
  font-size: 36rpx;
  font-weight: 600;
  color: #fff;
  margin-bottom: 8rpx;
}

.status-desc {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.9);
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
  padding-bottom: 20rpx;
  border-bottom: 1rpx solid #f0f0f0;

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

.goods-meta {
  margin-top: 12rpx;
}

.order-no {
  font-size: 24rpx;
  color: #999;
}

/* 信息列表 */
.info-list {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  &.detail-row {
    flex-direction: column;
    gap: 8rpx;
  }
}

.info-label {
  font-size: 26rpx;
  color: #666;
  flex-shrink: 0;
}

.info-value {
  font-size: 26rpx;
  color: #333;
  text-align: right;

  &.highlight {
    font-size: 32rpx;
    font-weight: 600;
    color: #FF6B35;
  }
}

/* 图片证据 */
.evidence-images {
  margin-top: 24rpx;
  padding-top: 24rpx;
  border-top: 1rpx solid #f0f0f0;
}

.evidence-label {
  font-size: 26rpx;
  color: #666;
  margin-bottom: 16rpx;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.evidence-image {
  width: 120rpx;
  height: 120rpx;
  border-radius: 8rpx;
}

/* 卖家回复 */
.seller-reply {
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 20rpx;
}

.reply-content {
  font-size: 28rpx;
  color: #333;
  line-height: 1.6;
}

.reply-time {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 12rpx;
}

/* 拒绝原因 */
.reject-card {
  background: #fff2f0;
}

.reject-reason {
  font-size: 28rpx;
  color: #ff4d4f;
  line-height: 1.6;
}

/* 发货表单 */
.shipping-form {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.form-item {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.form-label {
  font-size: 26rpx;
  color: #666;
}

.form-input {
  height: 80rpx;
  padding: 0 20rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  font-size: 28rpx;
}

.ship-btn {
  height: 80rpx;
  background: #FF6B35 !important;
  color: #fff !important;
  font-size: 28rpx;
  border-radius: 40rpx;
  margin-top: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 150ms ease-out, opacity 150ms ease-out;

  &:active {
    transform: scale(0.97);
    opacity: 0.9;
  }

  &::after {
    border: none;
  }

  &[disabled] {
    opacity: 0.5;
    pointer-events: none;
  }
}

/* 时间线 */
.timeline {
  position: relative;
  padding-left: 40rpx;
}

.timeline-item {
  position: relative;
  padding-bottom: 32rpx;

  &:last-child {
    padding-bottom: 0;
  }

  &::before {
    content: '';
    position: absolute;
    left: -32rpx;
    top: 16rpx;
    bottom: -16rpx;
    width: 2rpx;
    background: #e5e5e5;
  }

  &:last-child::before {
    display: none;
  }

  &.active::before {
    background: #FF6B35;
  }
}

.timeline-dot {
  position: absolute;
  left: -40rpx;
  top: 8rpx;
  width: 16rpx;
  height: 16rpx;
  background: #e5e5e5;
  border-radius: 50%;

  .active & {
    background: #FF6B35;
  }
}

.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.timeline-title {
  font-size: 28rpx;
  color: #333;
}

.timeline-time {
  font-size: 24rpx;
  color: #999;
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

.btn-secondary {
  width: 100%;
  height: 88rpx;
  background: #fff !important;
  color: #666 !important;
  font-size: 32rpx;
  border: 2rpx solid #ddd !important;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 150ms ease-out, background-color 150ms ease-out;

  &:active {
    transform: scale(0.97);
    background: #f5f5f5 !important;
  }

  &::after {
    border: none;
  }
}
</style>
