<template>
  <view class="page">
    <!-- 页面初始加载 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部白色背景层 -->
    <view class="header-bg" :style="{ height: (navBarHeight + 46) + 'px' }"></view>

    <!-- 顶部导航栏 -->
    <NavBar :title="t('sellerReturn.title')" />

    <!-- 状态Tab（吸顶） -->
    <view class="status-tabs" :style="{ top: navBarHeight + 'px' }">
      <scroll-view scroll-x class="tabs-scroll">
        <view class="tabs-wrapper">
          <view
            v-for="tab in statusTabs"
            :key="tab.value"
            class="tab-item"
            :class="{ active: currentStatus === tab.value }"
            @click="switchTab(tab.value)"
          >
            <text class="tab-label">{{ tab.label }}</text>
            <text v-if="tab.badge && tab.badge > 0" class="tab-badge">{{ tab.badge > 99 ? '99+' : tab.badge }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- Tab 占位 -->
    <view class="tabs-placeholder"></view>

    <!-- 切换分类加载中 -->
    <view v-if="switching" class="switching-loading">
      <text class="bi bi-arrow-repeat spinning"></text>
    </view>

    <!-- 退货列表 -->
    <scroll-view
      v-else
      class="return-list"
      scroll-y
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <!-- 退货卡片 -->
      <view v-for="item in returns" :key="item.id" class="return-card" @click="goDetail(item)">
        <!-- 退货头部 -->
        <view class="return-header">
          <text class="return-no">{{ t('sellerReturn.returnNo') }}: {{ item.return_no }}</text>
          <text class="return-status" :class="getStatusClass(item.status)">{{ getStatusText(item.status) }}</text>
        </view>

        <!-- 商品信息 -->
        <view class="return-goods">
          <image :src="item.goods_snapshot?.cover_image" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <text class="goods-title">{{ item.goods_snapshot?.title }}</text>
            <view class="return-type">
              <text class="type-tag" :class="{ 'refund-only': item.type === 1 }">
                {{ item.type === 1 ? t('sellerReturn.typeRefundOnly') : t('sellerReturn.typeReturnRefund') }}
              </text>
            </view>
            <view class="return-amount">
              <text class="amount-label">{{ t('sellerReturn.refundAmount') }}: </text>
              <text class="amount-value">{{ formatPrice(item.refund_amount, item.currency) }}</text>
            </view>
          </view>
        </view>

        <!-- 买家信息 -->
        <view class="buyer-info">
          <image v-if="item.buyer?.avatar" :src="item.buyer.avatar" class="buyer-avatar" />
          <text class="bi bi-person-circle buyer-avatar-placeholder" v-else></text>
          <text class="buyer-name">{{ item.buyer?.nickname || t('sellerReturn.unknownBuyer') }}</text>
        </view>

        <!-- 退货底部 -->
        <view class="return-footer">
          <text class="return-time">{{ formatTime(item.created_at) }}</text>
          <view class="return-actions">
            <!-- 待处理 - 同意/拒绝按钮 -->
            <template v-if="item.status === 0">
              <button class="action-btn" @click.stop="handleReject(item)">
                {{ t('sellerReturn.reject') }}
              </button>
              <button class="action-btn primary" @click.stop="handleApprove(item)">
                {{ t('sellerReturn.approve') }}
              </button>
            </template>
            <!-- 退货中 - 确认收货 -->
            <template v-else-if="item.status === 4">
              <button class="action-btn primary" @click.stop="handleReceive(item)">
                {{ t('sellerReturn.confirmReceive') }}
              </button>
            </template>
            <!-- 已同意/已收货 - 退款 -->
            <template v-else-if="item.status === 1 || (item.status === 4 && item.received_at)">
              <button class="action-btn primary" @click.stop="handleRefund(item)">
                {{ t('sellerReturn.refund') }}
              </button>
            </template>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && returns.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('sellerReturn.noReturns') }}</text>
      </view>

      <!-- 加载更多 -->
      <view v-if="returns.length > 0" class="load-more">
        <text v-if="loadingMore" class="load-text">{{ t('common.loading') }}</text>
        <text v-else-if="noMore" class="load-text">{{ t('common.noMore') }}</text>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-bottom"></view>
    </scroll-view>

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
import { ref, computed, onMounted } from 'vue'
import { onShow, onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import {
  getSellerReturnList,
  getSellerReturnStatistics,
  approveReturn,
  rejectReturn,
  receiveReturn,
  refundReturn,
  type SellerReturnInfo,
  type SellerReturnStatistics
} from '@/api/return'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 导航栏高度
const statusBarHeight = ref(20)
const NAV_BAR_HEIGHT = 48
const navBarHeight = computed(() => statusBarHeight.value + NAV_BAR_HEIGHT)

// 状态
const loading = ref(true)
const switching = ref(false)
const refreshing = ref(false)
const loadingMore = ref(false)
const noMore = ref(false)

// 数据
const returns = ref<SellerReturnInfo[]>([])
const statistics = ref<SellerReturnStatistics>({
  pending: 0,
  approved: 0,
  in_return: 0,
  completed: 0,
  need_action: 0
})
const currentStatus = ref<string>('all')
const page = ref(1)
const pageSize = 10

// 弹窗状态
const showApproveDialog = ref(false)
const showRejectDialog = ref(false)
const showReceiveDialog = ref(false)
const showRefundDialog = ref(false)
const currentItem = ref<SellerReturnInfo | null>(null)
const rejectForm = ref({ reason: '' })

// 状态 Tab 配置
const statusTabs = computed(() => [
  { value: 'all', label: t('sellerReturn.statusAll'), badge: 0 },
  { value: 'pending', label: t('sellerReturn.statusPending'), badge: statistics.value.pending },
  { value: 'approved', label: t('sellerReturn.statusApproved'), badge: statistics.value.approved },
  { value: 'inReturn', label: t('sellerReturn.statusInReturn'), badge: statistics.value.in_return },
  { value: 'completed', label: t('sellerReturn.statusCompleted'), badge: 0 },
])

// 退款弹窗内容
const refundDialogContent = computed(() => {
  if (!currentItem.value) return ''
  return t('sellerReturn.refundContent').replace('[AMOUNT]', formatPrice(currentItem.value.refund_amount, currentItem.value.currency))
})

// 状态映射
function getStatusParam(status: string): number | undefined {
  const statusMap: Record<string, number | undefined> = {
    all: undefined,
    pending: 0,
    approved: 1,
    inReturn: 4,
    completed: 5,
  }
  return statusMap[status]
}

// 格式化价格
function formatPrice(amount: number, currency: string) {
  return appStore.formatPrice(amount, currency)
}

// 格式化时间
function formatTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

// 获取状态样式类
function getStatusClass(status: number) {
  const classMap: Record<number, string> = {
    0: 'status-pending',
    1: 'status-approved',
    2: 'status-rejected',
    3: 'status-cancelled',
    4: 'status-in-return',
    5: 'status-completed',
  }
  return classMap[status] || ''
}

// 获取状态文本
function getStatusText(status: number) {
  const textMap: Record<number, string> = {
    0: t('sellerReturn.statusPending'),
    1: t('sellerReturn.statusApproved'),
    2: t('sellerReturn.statusRejected'),
    3: t('sellerReturn.statusCancelled'),
    4: t('sellerReturn.statusInReturn'),
    5: t('sellerReturn.statusCompleted'),
  }
  return textMap[status] || ''
}

// 加载统计数据
async function loadStatistics() {
  try {
    const res = await getSellerReturnStatistics()
    if (res.code === 0 && res.data) {
      statistics.value = res.data
    }
  } catch (e) {
    console.error('Failed to load statistics:', e)
  }
}

// 加载退货列表
async function loadReturns(reset = false) {
  if (reset) {
    page.value = 1
    noMore.value = false
  }

  try {
    const statusParam = getStatusParam(currentStatus.value)
    const res = await getSellerReturnList({
      page: page.value,
      pageSize,
      status: statusParam,
    })

    const list = res.data?.list || []

    if (reset) {
      returns.value = list
    } else {
      returns.value = [...returns.value, ...list]
    }

    if (list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Failed to load seller returns:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
    switching.value = false
    refreshing.value = false
    loadingMore.value = false
  }
}

// 切换 Tab
function switchTab(status: string) {
  if (currentStatus.value === status) return
  currentStatus.value = status
  switching.value = true
  loadReturns(true)
}

// 下拉刷新
function onRefresh() {
  refreshing.value = true
  loadStatistics()
  loadReturns(true)
}

// 加载更多
function loadMore() {
  if (loadingMore.value || noMore.value) return
  loadingMore.value = true
  page.value++
  loadReturns()
}

// 跳转详情
function goDetail(item: SellerReturnInfo) {
  uni.navigateTo({ url: `/pages/seller/returns/detail?id=${item.id}` })
}

// 同意退货
function handleApprove(item: SellerReturnInfo) {
  currentItem.value = item
  showApproveDialog.value = true
}

async function confirmApprove() {
  if (!currentItem.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await approveReturn(currentItem.value.id)
    if (res.code === 0) {
      toast.success(t('sellerReturn.approveSuccess'))
      loadStatistics()
      loadReturns(true)
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
    currentItem.value = null
  }
}

// 拒绝退货
function handleReject(item: SellerReturnInfo) {
  currentItem.value = item
  rejectForm.value = { reason: '' }
  showRejectDialog.value = true
}

function closeRejectDialog() {
  showRejectDialog.value = false
  currentItem.value = null
}

async function confirmReject() {
  if (!currentItem.value || !rejectForm.value.reason.trim()) return

  try {
    uni.showLoading({ title: '' })
    const res = await rejectReturn(currentItem.value.id, { reason: rejectForm.value.reason })
    if (res.code === 0) {
      toast.success(t('sellerReturn.rejectSuccess'))
      closeRejectDialog()
      loadStatistics()
      loadReturns(true)
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
function handleReceive(item: SellerReturnInfo) {
  currentItem.value = item
  showReceiveDialog.value = true
}

async function confirmReceive() {
  if (!currentItem.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await receiveReturn(currentItem.value.id)
    if (res.code === 0) {
      toast.success(t('sellerReturn.receiveSuccess'))
      loadStatistics()
      loadReturns(true)
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
    currentItem.value = null
  }
}

// 执行退款
function handleRefund(item: SellerReturnInfo) {
  currentItem.value = item
  showRefundDialog.value = true
}

async function confirmRefund() {
  if (!currentItem.value) return

  try {
    uni.showLoading({ title: '' })
    const res = await refundReturn(currentItem.value.id)
    if (res.code === 0) {
      toast.success(t('sellerReturn.refundSuccess'))
      loadStatistics()
      loadReturns(true)
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
    currentItem.value = null
  }
}

// 获取状态栏高度
onMounted(() => {
  uni.getSystemInfo({
    success: (res) => {
      statusBarHeight.value = res.statusBarHeight || 20
    },
  })
})

// 页面加载
onLoad((options: any) => {
  if (options?.status) {
    currentStatus.value = options.status
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('sellerReturn.title') })
  loadStatistics()
  loadReturns(true)
})
</script>

<style lang="scss" scoped>
// 设计变量
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

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;

$radius-sm: 8px;
$radius-md: 12px;

.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: $color-background;
}

.header-bg {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: $color-surface;
  z-index: 1;
}

.status-tabs {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 99;
  background: $color-surface;
  padding: 0 24rpx;
  border-bottom: 1rpx solid $color-border;
}

.tabs-placeholder {
  height: 92rpx;
}

.tabs-scroll {
  white-space: nowrap;

  ::-webkit-scrollbar {
    display: none;
  }
}

.tabs-wrapper {
  display: inline-flex;
  padding: 20rpx 0;
  gap: 24rpx;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  padding: 12rpx 24rpx;
  border-radius: 32rpx;
  background: $color-background;
  font-size: 26rpx;
  color: $color-text-secondary;
  transition: all 0.2s;
  gap: 8rpx;

  &.active {
    background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
    color: #fff;

    .tab-badge {
      background: rgba(255, 255, 255, 0.3);
      color: #fff;
    }
  }

  &:active {
    opacity: 0.8;
  }
}

.tab-label {
  white-space: nowrap;
}

.tab-badge {
  min-width: 32rpx;
  height: 32rpx;
  padding: 0 8rpx;
  border-radius: 16rpx;
  background: $color-danger;
  color: #fff;
  font-size: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.switching-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 80rpx 0;

  .bi {
    font-size: 48rpx;
    color: $color-primary;
  }

  .spinning {
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.return-list {
  flex: 1;
  padding: $spacing-md;
  width: auto;

  ::-webkit-scrollbar {
    display: none;
  }
}

.return-card {
  background-color: $color-surface;
  border-radius: $radius-md;
  margin-bottom: $spacing-md;
  overflow: hidden;
}

.return-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-base;
  border-bottom: 1px solid $color-border;
}

.return-no {
  font-size: 12px;
  color: $color-text-muted;
}

.return-status {
  font-size: 13px;
  font-weight: 500;

  &.status-pending {
    color: $color-warning;
  }

  &.status-approved {
    color: $color-primary;
  }

  &.status-rejected,
  &.status-cancelled {
    color: $color-text-muted;
  }

  &.status-in-return {
    color: $color-info;
  }

  &.status-completed {
    color: $color-success;
  }
}

.return-goods {
  display: flex;
  padding: $spacing-base;
  gap: $spacing-md;
}

.goods-image {
  width: 80px;
  height: 80px;
  border-radius: $radius-sm;
  background-color: $color-background;
  flex-shrink: 0;
}

.goods-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
}

.goods-title {
  font-size: 14px;
  color: $color-text-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.return-type {
  margin-top: $spacing-xs;
}

.type-tag {
  display: inline-block;
  padding: 4rpx 12rpx;
  font-size: 20rpx;
  border-radius: 4rpx;
  background: rgba($color-primary, 0.1);
  color: $color-primary;

  &.refund-only {
    background: rgba($color-warning, 0.1);
    color: $color-warning;
  }
}

.return-amount {
  display: flex;
  align-items: center;
  margin-top: $spacing-xs;
}

.amount-label {
  font-size: 12px;
  color: $color-text-muted;
}

.amount-value {
  font-size: 14px;
  font-weight: 600;
  color: $color-primary;
}

.buyer-info {
  display: flex;
  align-items: center;
  padding: $spacing-sm $spacing-base;
  background-color: $color-background;
  margin: 0 $spacing-base;
  border-radius: $radius-sm;
  gap: $spacing-sm;
}

.buyer-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.buyer-avatar-placeholder {
  font-size: 24px;
  color: $color-text-muted;
}

.buyer-name {
  font-size: 12px;
  color: $color-text-primary;
}

.return-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-base;
  border-top: 1px solid $color-border;
}

.return-time {
  font-size: 12px;
  color: $color-text-muted;
}

.return-actions {
  display: flex;
  gap: $spacing-sm;
}

.action-btn {
  margin: 0;
  padding: $spacing-sm $spacing-md;
  font-size: 13px;
  line-height: 1.5;
  border-radius: $radius-sm;
  background-color: $color-surface;
  border: 1px solid $color-border;
  color: $color-text-secondary;
  transition: transform 150ms ease-out, opacity 150ms ease-out;

  &::after {
    display: none;
  }

  &:active {
    transform: scale(0.97);
    opacity: 0.9;
  }

  &.primary {
    background-color: $color-primary;
    border-color: $color-primary;
    color: #fff;
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px $spacing-base;
}

.empty-icon {
  font-size: 60px;
  color: $color-text-muted;
  margin-bottom: $spacing-lg;
}

.empty-text {
  font-size: 15px;
  color: $color-text-muted;
}

.load-more {
  text-align: center;
  padding: $spacing-base;
}

.load-text {
  font-size: 13px;
  color: $color-text-muted;
}

.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}

// 弹窗样式
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
  background-color: $color-surface;
  border-radius: $radius-md;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: $spacing-base;
  border-bottom: 1px solid $color-border;
}

.modal-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text-primary;
}

.modal-close {
  font-size: 24px;
  color: $color-text-muted;
  cursor: pointer;
}

.modal-body {
  padding: $spacing-base;
}

.form-item {
  margin-bottom: $spacing-base;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  display: block;
  font-size: 13px;
  color: $color-text-secondary;
  margin-bottom: $spacing-sm;

  .required {
    color: $color-danger;
  }
}

.form-textarea {
  width: 100%;
  min-height: 120px;
  padding: $spacing-md;
  font-size: 14px;
  border: 1px solid $color-border;
  border-radius: $radius-sm;
  background-color: $color-background;
  box-sizing: border-box;

  &::placeholder {
    color: $color-text-muted;
  }
}

.modal-footer {
  display: flex;
  gap: $spacing-md;
  padding: $spacing-base;
  border-top: 1px solid $color-border;
}

.modal-btn {
  flex: 1;
  height: 44px;
  font-size: 15px;
  border-radius: $radius-sm;
  border: none;
  transition: transform 150ms ease-out, opacity 150ms ease-out;

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
