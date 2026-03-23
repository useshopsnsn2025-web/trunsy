<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('wallet.withdrawalsTitle')" />

    <!-- 筛选栏 -->
    <view class="filter-bar">
      <scroll-view scroll-x class="filter-scroll">
        <view class="filter-tabs">
          <view
            class="filter-tab"
            :class="{ active: currentStatus === null }"
            @click="setStatus(null)"
          >
            <text>{{ t('wallet.typeAll') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentStatus === 0 }"
            @click="setStatus(0)"
          >
            <text>{{ t('wallet.statusPending') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentStatus === 1 }"
            @click="setStatus(1)"
          >
            <text>{{ t('wallet.statusProcessing') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentStatus === 2 }"
            @click="setStatus(2)"
          >
            <text>{{ t('wallet.statusCompleted') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentStatus === 3 }"
            @click="setStatus(3)"
          >
            <text>{{ t('wallet.statusRejected') }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <LoadingPage v-if="pageLoading" />

    <template v-else>
      <!-- 空状态 -->
      <view v-if="withdrawals.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('wallet.noWithdrawals') }}</text>
      </view>

      <!-- 提现列表 -->
      <scroll-view
        v-else
        scroll-y
        class="list-scroll"
        @scrolltolower="loadMore"
      >
        <view class="withdrawal-list">
          <view
            v-for="item in withdrawals"
            :key="item.id"
            class="withdrawal-card"
          >
            <view class="card-header">
              <text class="withdrawal-no">#{{ item.withdrawalNo }}</text>
              <view class="withdrawal-status" :class="getStatusClass(item.status)">
                <text>{{ getStatusText(item.status) }}</text>
              </view>
            </view>

            <view class="card-body">
              <view class="amount-row">
                <text class="amount-label">{{ t('wallet.withdrawAmount') }}</text>
                <text class="amount-value">{{ formatPrice(item.amount) }}</text>
              </view>
              <view class="amount-row">
                <text class="amount-label">{{ t('wallet.serviceFee') }}</text>
                <text class="amount-value">-{{ formatPrice(item.fee) }}</text>
              </view>
              <view class="amount-row highlight">
                <text class="amount-label">{{ t('wallet.actualReceive') }}</text>
                <text class="amount-value">{{ formatPrice(item.actualAmount) }}</text>
              </view>
            </view>

            <view class="card-footer">
              <view class="bank-info">
                <text class="bi bi-credit-card"></text>
                <text>{{ item.bankName }} {{ item.accountNo }}</text>
              </view>
              <text class="time">{{ formatTime(item.createdAt) }}</text>
            </view>

            <!-- 拒绝原因 -->
            <view v-if="item.status === 3 && item.rejectReason" class="reject-reason">
              <view class="reject-icon-wrap">
                <text class="bi bi-x-circle-fill"></text>
              </view>
              <text class="reject-text">{{ item.rejectReason }}</text>
            </view>

            <!-- 取消按钮 -->
            <view v-if="item.status === 0" class="card-actions">
              <view class="cancel-btn" @click="handleCancel(item)">
                <text>{{ t('wallet.cancelWithdraw') }}</text>
              </view>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more">
          <text v-if="loadingMore">{{ t('common.loading') }}</text>
          <text v-else>{{ t('common.loadMore') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="withdrawals.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>
      </scroll-view>
    </template>

    <!-- 取消提现确认弹窗 -->
    <ConfirmDialog
      :visible="showCancelDialog"
      :title="t('wallet.cancelWithdrawConfirm')"
      :content="t('wallet.cancelWithdrawDesc')"
      icon="bi-x-circle"
      icon-type="warning"
      @update:visible="showCancelDialog = $event"
      @confirm="confirmCancel"
    />
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getWithdrawals, cancelWithdraw, type Withdrawal, WITHDRAWAL_STATUS } from '@/api/wallet'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const pageLoading = ref(true)
const loadingMore = ref(false)
const withdrawals = ref<Withdrawal[]>([])
const currentStatus = ref<number | null>(null)
const page = ref(1)
const pageSize = 20
const hasMore = ref(true)
const showCancelDialog = ref(false)
const cancelTargetItem = ref<Withdrawal | null>(null)

// 格式化价格
function formatPrice(value: number): string {
  return appStore.formatPrice(value, 'USD')
}

// 格式化时间
function formatTime(dateStr: string): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 获取状态样式
function getStatusClass(status: number): string {
  switch (status) {
    case WITHDRAWAL_STATUS.PENDING: return 'pending'
    case WITHDRAWAL_STATUS.PROCESSING: return 'processing'
    case WITHDRAWAL_STATUS.COMPLETED: return 'completed'
    case WITHDRAWAL_STATUS.REJECTED: return 'rejected'
    default: return ''
  }
}

// 获取状态文本
function getStatusText(status: number): string {
  switch (status) {
    case WITHDRAWAL_STATUS.PENDING: return t('wallet.statusPending')
    case WITHDRAWAL_STATUS.PROCESSING: return t('wallet.statusProcessing')
    case WITHDRAWAL_STATUS.COMPLETED: return t('wallet.statusCompleted')
    case WITHDRAWAL_STATUS.REJECTED: return t('wallet.statusRejected')
    default: return ''
  }
}

// 设置状态筛选
function setStatus(status: number | null) {
  currentStatus.value = status
  page.value = 1
  withdrawals.value = []
  hasMore.value = true
  loadWithdrawals()
}

// 加载提现记录
async function loadWithdrawals() {
  if (page.value === 1) {
    pageLoading.value = true
  } else {
    loadingMore.value = true
  }

  try {
    const params: any = {
      page: page.value,
      pageSize
    }
    if (currentStatus.value !== null) {
      params.status = currentStatus.value
    }

    const res = await getWithdrawals(params)
    const list = res.data?.list || []

    if (page.value === 1) {
      withdrawals.value = list
    } else {
      withdrawals.value = [...withdrawals.value, ...list]
    }

    hasMore.value = list.length >= pageSize
  } catch (e) {
    console.error('Failed to load withdrawals:', e)
  } finally {
    pageLoading.value = false
    loadingMore.value = false
  }
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadWithdrawals()
}

// 取消提现
function handleCancel(item: Withdrawal) {
  cancelTargetItem.value = item
  showCancelDialog.value = true
}

// 确认取消提现
async function confirmCancel() {
  const item = cancelTargetItem.value
  if (!item) return

  uni.showLoading({ title: '' })
  try {
    await cancelWithdraw(item.id)
    toast.success(t('wallet.cancelSuccess'))
    // 刷新列表
    page.value = 1
    withdrawals.value = []
    loadWithdrawals()
  } catch (e: any) {
    toast.error(e.message || t('wallet.cancelFailed'))
  } finally {
    uni.hideLoading()
  }
}

onShow(() => {
  uni.setNavigationBarTitle({ title: t('wallet.withdrawalsTitle') })
  page.value = 1
  withdrawals.value = []
  hasMore.value = true
  loadWithdrawals()
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-success: #059669;
$color-warning: #D97706;
$color-error: #DC2626;
$color-border: #E7E5E4;
$color-bg: #FAFAF9;

.page {
  min-height: 100vh;
  background-color: $color-bg;
  display: flex;
  flex-direction: column;
}

// ==================
// 筛选栏
// ==================
.filter-bar {
  background: #fff;
  border-bottom: 1px solid $color-border;
}

.filter-scroll {
  white-space: nowrap;
}

.filter-tabs {
  display: inline-flex;
  padding: 12px 16px;
  gap: 8px;
}

.filter-tab {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  background: $color-bg;
  border-radius: 20px;
  transition: all 0.2s;

  text {
    font-size: 13px;
    color: $color-secondary;
    white-space: nowrap;
  }

  &.active {
    background: $color-primary;

    text {
      color: #fff;
    }
  }
}

// ==================
// 列表滚动区域
// ==================
.list-scroll {
  flex: 1;
  height: 0;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 80px 32px;
}

.empty-icon {
  font-size: 56px;
  color: $color-border;
}

.empty-text {
  font-size: 15px;
  color: $color-muted;
}

// ==================
// 提现卡片
// ==================
.withdrawal-list {
  padding: 16px;
}

.withdrawal-card {
  background: #fff;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.withdrawal-no {
  font-size: 14px;
  font-weight: 600;
  color: $color-primary;
}

.withdrawal-status {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;

  &.pending {
    background: rgba(217, 119, 6, 0.1);
    color: $color-warning;
  }

  &.processing {
    background: rgba(59, 130, 246, 0.1);
    color: #3B82F6;
  }

  &.completed {
    background: rgba(5, 150, 105, 0.1);
    color: $color-success;
  }

  &.rejected {
    background: rgba(220, 38, 38, 0.1);
    color: $color-error;
  }
}

.card-body {
  padding: 12px 0;
  border-top: 1px solid $color-border;
  border-bottom: 1px solid $color-border;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;

  &.highlight {
    .amount-label, .amount-value {
      font-weight: 600;
      color: $color-primary;
    }
  }
}

.amount-label {
  font-size: 14px;
  color: $color-muted;
}

.amount-value {
  font-size: 14px;
  color: $color-secondary;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 12px;
}

.bank-info {
  display: flex;
  align-items: center;
  gap: 6px;

  .bi {
    font-size: 14px;
    color: $color-muted;
  }

  text {
    font-size: 13px;
    color: $color-muted;
  }
}

.time {
  font-size: 12px;
  color: $color-muted;
}

// 拒绝原因
.reject-reason {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  background: #FEF2F2;
  border-left: 3px solid #EF4444;
  border-radius: 0 8px 8px 0;
  margin-top: 12px;
}

.reject-icon-wrap {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FEE2E2;
  border-radius: 50%;
  flex-shrink: 0;

  .bi {
    font-size: 14px;
    color: #EF4444;
  }
}

.reject-text {
  flex: 1;
  font-size: 13px;
  color: #991B1B;
  line-height: 1.4;
}

// 操作按钮
.card-actions {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid $color-border;
}

.cancel-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
  background: $color-bg;
  border-radius: 8px;

  text {
    font-size: 14px;
    color: $color-secondary;
  }
}

// 加载更多
.load-more, .no-more {
  padding: 20px;
  text-align: center;

  text {
    font-size: 13px;
    color: $color-muted;
  }
}
</style>
