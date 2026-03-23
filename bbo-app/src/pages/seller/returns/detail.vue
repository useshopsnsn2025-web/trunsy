<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('sellerReturn.detailTitle')" />

    <view v-if="returnInfo" class="content">
      <!-- 状态卡片 -->
      <view class="status-card" :class="getStatusClass()">
        <view class="status-icon">
          <text :class="getStatusIcon()"></text>
        </view>
        <view class="status-info">
          <text class="status-text">{{ getStatusText() }}</text>
          <text class="status-desc">{{ getStatusDesc() }}</text>
        </view>
      </view>

      <!-- 买家信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-person"></text>
          <text class="section-title">{{ t('sellerReturn.buyerInfo') }}</text>
        </view>
        <view class="buyer-detail">
          <image v-if="returnInfo.buyer?.avatar" :src="returnInfo.buyer.avatar" class="buyer-avatar" />
          <text class="bi bi-person-circle buyer-avatar-placeholder" v-else></text>
          <text class="buyer-name">{{ returnInfo.buyer?.nickname || t('sellerReturn.unknownBuyer') }}</text>
        </view>
      </view>

      <!-- 商品信息 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-box-seam"></text>
          <text class="section-title">{{ t('sellerReturn.orderInfo') }}</text>
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
          <text class="section-title">{{ t('sellerReturn.returnInfo') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.returnNo') }}</text>
            <text class="info-value">{{ returnInfo.return_no }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.type') }}</text>
            <text class="info-value">
              <text class="type-tag" :class="{ 'refund-only': returnInfo.type === 1 }">
                {{ returnInfo.type === 1 ? t('sellerReturn.typeRefundOnly') : t('sellerReturn.typeReturnRefund') }}
              </text>
            </text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.reason') }}</text>
            <text class="info-value">{{ getReasonText(returnInfo.reason) }}</text>
          </view>
          <view v-if="returnInfo.reason_detail" class="info-row detail-row">
            <text class="info-label">{{ t('sellerReturn.reasonDetail') }}</text>
            <text class="info-value">{{ returnInfo.reason_detail }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.refundAmount') }}</text>
            <text class="info-value highlight">{{ formatPrice(returnInfo.refund_amount, returnInfo.currency) }}</text>
          </view>
        </view>

        <!-- 图片证据 -->
        <view v-if="returnInfo.images && returnInfo.images.length > 0" class="evidence-images">
          <view class="evidence-label">{{ t('sellerReturn.evidenceImages') }}</view>
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

      <!-- 卖家回复（如果有） -->
      <view v-if="returnInfo.seller_reply" class="section-card">
        <view class="section-header">
          <text class="bi bi-chat-left-text"></text>
          <text class="section-title">{{ t('sellerReturn.yourReply') }}</text>
        </view>
        <view class="seller-reply">
          <text class="reply-content">{{ returnInfo.seller_reply }}</text>
          <text class="reply-time">{{ returnInfo.seller_replied_at }}</text>
        </view>
      </view>

      <!-- 拒绝原因（如果有） -->
      <view v-if="returnInfo.status === 2 && returnInfo.reject_reason" class="section-card reject-card">
        <view class="section-header">
          <text class="bi bi-exclamation-circle"></text>
          <text class="section-title">{{ t('sellerReturn.rejectReason') }}</text>
        </view>
        <text class="reject-reason">{{ returnInfo.reject_reason }}</text>
      </view>

      <!-- 退货物流信息（如果有） -->
      <view v-if="returnInfo.return_tracking_no" class="section-card">
        <view class="section-header">
          <text class="bi bi-truck"></text>
          <text class="section-title">{{ t('sellerReturn.returnShipping') }}</text>
        </view>
        <view class="info-list">
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.carrier') }}</text>
            <text class="info-value">{{ returnInfo.return_carrier || '-' }}</text>
          </view>
          <view class="info-row">
            <text class="info-label">{{ t('sellerReturn.trackingNumber') }}</text>
            <text class="info-value">{{ returnInfo.return_tracking_no }}</text>
          </view>
          <view v-if="returnInfo.shipped_at" class="info-row">
            <text class="info-label">{{ t('sellerReturn.shippedAt') }}</text>
            <text class="info-value">{{ returnInfo.shipped_at }}</text>
          </view>
          <view v-if="returnInfo.received_at" class="info-row">
            <text class="info-label">{{ t('sellerReturn.receivedAt') }}</text>
            <text class="info-value">{{ returnInfo.received_at }}</text>
          </view>
        </view>
      </view>

      <!-- 时间线 -->
      <view class="section-card">
        <view class="section-header">
          <text class="bi bi-clock-history"></text>
          <text class="section-title">{{ t('sellerReturn.timeline') }}</text>
        </view>
        <view class="timeline">
          <view class="timeline-item active">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('sellerReturn.timelineSubmitted') }}</text>
              <text class="timeline-time">{{ returnInfo.created_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.seller_replied_at" class="timeline-item active">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('sellerReturn.timelineResponded') }}</text>
              <text class="timeline-time">{{ returnInfo.seller_replied_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.shipped_at" class="timeline-item active">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('sellerReturn.timelineBuyerShipped') }}</text>
              <text class="timeline-time">{{ returnInfo.shipped_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.received_at" class="timeline-item active">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('sellerReturn.timelineReceived') }}</text>
              <text class="timeline-time">{{ returnInfo.received_at }}</text>
            </view>
          </view>
          <view v-if="returnInfo.refunded_at" class="timeline-item active">
            <view class="timeline-dot"></view>
            <view class="timeline-content">
              <text class="timeline-title">{{ t('sellerReturn.timelineRefunded') }}</text>
              <text class="timeline-time">{{ returnInfo.refunded_at }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部操作按钮 -->
    <view v-if="returnInfo" class="bottom-actions">
      <!-- 待处理 -->
      <template v-if="returnInfo.status === 0">
        <button class="action-btn secondary" @click="handleReject">
          {{ t('sellerReturn.reject') }}
        </button>
        <button class="action-btn primary" @click="handleApprove">
          {{ t('sellerReturn.approve') }}
        </button>
      </template>
      <!-- 退货中 -->
      <template v-else-if="returnInfo.status === 4 && !returnInfo.received_at">
        <button class="action-btn primary" @click="handleReceive">
          {{ t('sellerReturn.confirmReceive') }}
        </button>
      </template>
      <!-- 已同意（仅退款）或已收货 - 退款 -->
      <template v-else-if="(returnInfo.status === 1 && returnInfo.type === 1) || returnInfo.received_at">
        <button class="action-btn primary" @click="handleRefund">
          {{ t('sellerReturn.refund') }}
        </button>
      </template>
    </view>

    <!-- 同意确认弹窗 -->
    <ConfirmDialog
      :visible="showApproveDialog"
      :title="t('sellerReturn.approveTitle')"
      :content="t('sellerReturn.approveContent')"
      icon="bi-check-circle"
      icon-type="success"
      @update:visible="showApproveDialog = $event"
      @confirm="confirmApprove"
    />

    <!-- 拒绝弹窗 -->
    <view v-if="showRejectDialog" class="modal-mask" @click="closeRejectDialog">
      <view class="modal-content" @click.stop>
        <view class="modal-header">
          <text class="modal-title">{{ t('sellerReturn.rejectTitle') }}</text>
          <text class="bi bi-x modal-close" @click="closeRejectDialog"></text>
        </view>
        <view class="modal-body">
          <view class="form-item">
            <text class="form-label">{{ t('sellerReturn.rejectReason') }} <text class="required">*</text></text>
            <textarea
              v-model="rejectForm.reason"
              class="form-textarea"
              :placeholder="t('sellerReturn.rejectReasonPlaceholder')"
              :maxlength="500"
            />
          </view>
        </view>
        <view class="modal-footer">
          <button class="modal-btn cancel" @click="closeRejectDialog">{{ t('common.cancel') }}</button>
          <button class="modal-btn confirm" :disabled="!rejectForm.reason.trim()" @click="confirmReject">{{ t('common.confirm') }}</button>
        </view>
      </view>
    </view>

    <!-- 确认收货弹窗 -->
    <ConfirmDialog
      :visible="showReceiveDialog"
      :title="t('sellerReturn.receiveTitle')"
      :content="t('sellerReturn.receiveContent')"
      icon="bi-box-seam"
      icon-type="info"
      @update:visible="showReceiveDialog = $event"
      @confirm="confirmReceive"
    />

    <!-- 退款确认弹窗 -->
    <ConfirmDialog
      :visible="showRefundDialog"
      :title="t('sellerReturn.refundTitle')"
      :content="refundDialogContent"
      icon="bi-currency-exchange"
      icon-type="warning"
      @update:visible="showRefundDialog = $event"
      @confirm="confirmRefund"
    />
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
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import {
  getSellerReturnDetail,
  approveReturn,
  rejectReturn,
  receiveReturn,
  refundReturn,
  type SellerReturnInfo
} from '@/api/return'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const loading = ref(true)
const returnId = ref<number | null>(null)
const returnInfo = ref<SellerReturnInfo | null>(null)

// 弹窗状态
const showApproveDialog = ref(false)
const showRejectDialog = ref(false)
const showReceiveDialog = ref(false)
const showRefundDialog = ref(false)
const rejectForm = ref({ reason: '' })

// 退款弹窗内容
const refundDialogContent = computed(() => {
  if (!returnInfo.value) return ''
  const template = t('sellerReturn.refundContent')
  return template.replace('[AMOUNT]', formatPrice(returnInfo.value.refund_amount, returnInfo.value.currency))
})

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

// 获取状态文本
function getStatusText() {
  if (!returnInfo.value) return ''
  const textMap: Record<number, string> = {
    0: t('sellerReturn.statusPending'),
    1: t('sellerReturn.statusApproved'),
    2: t('sellerReturn.statusRejected'),
    3: t('sellerReturn.statusCancelled'),
    4: t('sellerReturn.statusInReturn'),
    5: t('sellerReturn.statusCompleted')
  }
  return textMap[returnInfo.value.status] || ''
}

// 获取状态描述
function getStatusDesc() {
  if (!returnInfo.value) return ''
  const descMap: Record<number, string> = {
    0: t('sellerReturn.statusDescPending'),
    1: t('sellerReturn.statusDescApproved'),
    2: t('sellerReturn.statusDescRejected'),
    3: t('sellerReturn.statusDescCancelled'),
    4: t('sellerReturn.statusDescInReturn'),
    5: t('sellerReturn.statusDescCompleted')
  }
  return descMap[returnInfo.value.status] || ''
}

// 获取退货原因文本
function getReasonText(reason: string) {
  const reasonMap: Record<string, string> = {
    'not_as_described': t('return.reasons.not_as_described'),
    'wrong_item': t('return.reasons.wrong_item'),
    'damaged': t('return.reasons.damaged'),
    'missing_parts': t('return.reasons.missing_parts'),
    'quality_issue': t('return.reasons.quality_issue'),
    'not_needed': t('return.reasons.not_needed'),
    'better_price': t('return.reasons.better_price'),
    'other': t('return.reasons.other'),
  }
  return reasonMap[reason] || reason
}

// 加载退货详情
async function loadReturnInfo() {
  if (!returnId.value) {
    loading.value = false
    return
  }

  try {
    const res = await getSellerReturnDetail(returnId.value)
    if (res.code === 0) {
      returnInfo.value = res.data
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

// 同意退货
function handleApprove() {
  showApproveDialog.value = true
}

async function confirmApprove() {
  if (!returnId.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await approveReturn(returnId.value)
    if (res.code === 0) {
      toast.success(t('sellerReturn.approveSuccess'))
      loadReturnInfo()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

// 拒绝退货
function handleReject() {
  rejectForm.value = { reason: '' }
  showRejectDialog.value = true
}

function closeRejectDialog() {
  showRejectDialog.value = false
}

async function confirmReject() {
  if (!returnId.value || !rejectForm.value.reason.trim()) return

  try {
    uni.showLoading({ title: '' })
    const res = await rejectReturn(returnId.value, { reason: rejectForm.value.reason })
    if (res.code === 0) {
      toast.success(t('sellerReturn.rejectSuccess'))
      closeRejectDialog()
      loadReturnInfo()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

// 确认收货
function handleReceive() {
  showReceiveDialog.value = true
}

async function confirmReceive() {
  if (!returnId.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await receiveReturn(returnId.value)
    if (res.code === 0) {
      toast.success(t('sellerReturn.receiveSuccess'))
      loadReturnInfo()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

// 执行退款
function handleRefund() {
  showRefundDialog.value = true
}

async function confirmRefund() {
  if (!returnId.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await refundReturn(returnId.value)
    if (res.code === 0) {
      toast.success(t('sellerReturn.refundSuccess'))
      loadReturnInfo()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

onLoad((options) => {
  if (options?.id) {
    returnId.value = parseInt(options.id)
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('sellerReturn.detailTitle') })
  if (returnId.value) {
    loadReturnInfo()
  } else {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
$color-primary: #FF6B35;
$color-text-primary: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #228B22;
$color-warning: #F5A623;
$color-danger: #E74C3C;
$color-info: #3498db;

.page {
  min-height: 100vh;
  background-color: $color-background;
  padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.content {
  padding: 24rpx;
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
    background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
  }

  &.status-rejected,
  &.status-cancelled {
    background: linear-gradient(135deg, #8c8c8c 0%, #bfbfbf 100%);
  }

  &.status-completed {
    background: linear-gradient(135deg, $color-success 0%, #73d13d 100%);
  }

  &.status-in-return {
    background: linear-gradient(135deg, $color-info 0%, #40a9ff 100%);
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

/* 通用卡片 */
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
    color: $color-primary;
    margin-right: 12rpx;
  }
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
}

/* 买家信息 */
.buyer-detail {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.buyer-avatar {
  width: 64rpx;
  height: 64rpx;
  border-radius: 50%;
}

.buyer-avatar-placeholder {
  font-size: 64rpx;
  color: $color-text-muted;
}

.buyer-name {
  font-size: 28rpx;
  color: $color-text-primary;
}

/* 商品信息 */
.goods-item {
  display: flex;
}

.goods-image {
  width: 120rpx;
  height: 120rpx;
  border-radius: 12rpx;
  background: $color-background;
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
  color: $color-text-primary;
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
  color: $color-text-muted;
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
  color: $color-text-muted;
  flex-shrink: 0;
}

.info-value {
  font-size: 26rpx;
  color: $color-text-primary;
  text-align: right;

  &.highlight {
    font-size: 32rpx;
    font-weight: 600;
    color: $color-primary;
  }
}

.type-tag {
  display: inline-block;
  padding: 4rpx 12rpx;
  font-size: 22rpx;
  border-radius: 4rpx;
  background: rgba($color-primary, 0.1);
  color: $color-primary;

  &.refund-only {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
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
  color: $color-text-muted;
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
  background: $color-background;
  border-radius: 12rpx;
  padding: 20rpx;
}

.reply-content {
  font-size: 28rpx;
  color: $color-text-primary;
  line-height: 1.6;
}

.reply-time {
  display: block;
  font-size: 24rpx;
  color: $color-text-muted;
  margin-top: 12rpx;
}

/* 拒绝原因 */
.reject-card {
  background: #fff2f0;
}

.reject-reason {
  font-size: 28rpx;
  color: $color-danger;
  line-height: 1.6;
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
    background: $color-primary;
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
    background: $color-primary;
  }
}

.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.timeline-title {
  font-size: 28rpx;
  color: $color-text-primary;
}

.timeline-time {
  font-size: 24rpx;
  color: $color-text-muted;
}

/* 底部按钮 */
.bottom-actions {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 20rpx;
  padding: 20rpx 24rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.05);
}

.action-btn {
  flex: 1;
  height: 88rpx;
  font-size: 32rpx;
  border-radius: 44rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 150ms ease-out, opacity 150ms ease-out;

  &::after {
    border: none;
  }

  &:active {
    transform: scale(0.97);
    opacity: 0.9;
  }

  &.primary {
    background: $color-primary !important;
    color: #fff !important;
  }

  &.secondary {
    background: #fff !important;
    color: $color-text-secondary !important;
    border: 2rpx solid $color-border !important;
  }
}

/* 弹窗样式 */
.modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.modal-content {
  width: 85%;
  max-width: 400px;
  background-color: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 32rpx;
  border-bottom: 1px solid $color-border;
}

.modal-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $color-text-primary;
}

.modal-close {
  font-size: 48rpx;
  color: $color-text-muted;
}

.modal-body {
  padding: 32rpx;
}

.form-item {
  margin-bottom: 24rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  font-size: 26rpx;
  color: $color-text-secondary;
  margin-bottom: 16rpx;

  .required {
    color: $color-danger;
  }
}

.form-textarea {
  width: 100%;
  min-height: 200rpx;
  padding: 20rpx;
  font-size: 28rpx;
  border: 1px solid $color-border;
  border-radius: 12rpx;
  background-color: $color-background;
  box-sizing: border-box;

  &::placeholder {
    color: $color-text-muted;
  }
}

.modal-footer {
  display: flex;
  gap: 20rpx;
  padding: 32rpx;
  border-top: 1px solid $color-border;
}

.modal-btn {
  flex: 1;
  height: 88rpx;
  font-size: 30rpx;
  border-radius: 44rpx;
  border: none;
  transition: transform 150ms ease-out, opacity 150ms ease-out;
  display: flex;
  align-items: center;
  justify-content: center;

  &::after {
    display: none;
  }

  &:active {
    transform: scale(0.97);
    opacity: 0.9;
  }

  &.cancel {
    background-color: $color-background;
    color: $color-text-secondary;
  }

  &.confirm {
    background-color: $color-primary;
    color: #fff;

    &[disabled] {
      opacity: 0.5;
    }
  }
}
</style>
