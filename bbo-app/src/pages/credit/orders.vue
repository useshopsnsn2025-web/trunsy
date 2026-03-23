<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('credit.ordersTitle')" />

    <!-- 状态筛选 -->
    <view class="filter-tabs">
      <view v-for="tab in tabs" :key="tab.value" class="tab" :class="{ active: currentStatus === tab.value }" @click="currentStatus = tab.value">
        <text>{{ tab.label }}</text>
      </view>
    </view>

    <!-- 订单列表 -->
    <view v-if="loading" class="loading-state">
      <text>{{ t('common.loading') }}</text>
    </view>

    <view v-else-if="orders.length === 0" class="empty-state">
      <text class="empty-icon">📋</text>
      <text class="empty-text">{{ t('credit.noOrders') }}</text>
    </view>

    <view v-else class="order-list">
      <view v-for="order in orders" :key="order.id" class="order-card" @click="goDetail(order.id)">
        <view class="order-header">
          <text class="order-no">{{ order.installment_no }}</text>
          <text class="order-status" :class="'status-' + order.status">{{ order.status_text }}</text>
        </view>

        <view class="order-amount">
          <view class="amount-item">
            <text class="label">{{ t('credit.totalAmount') }}</text>
            <text class="value">{{ formatPrice(order.total_amount, order.currency) }}</text>
          </view>
          <view class="amount-item">
            <text class="label">{{ t('credit.periodAmount') }}</text>
            <text class="value">{{ formatPrice(order.period_amount, order.currency) }}/{{ t('credit.period') }}</text>
          </view>
        </view>

        <view class="order-progress">
          <view class="progress-bar">
            <view class="progress-fill" :style="{ width: (order.paid_periods / order.periods * 100) + '%' }"></view>
          </view>
          <text class="progress-text">{{ order.paid_periods }}/{{ order.periods }} {{ t('credit.periodsPaid') }}</text>
        </view>

        <view v-if="order.next_due_date && order.status === 1" class="order-due">
          <text class="due-label">{{ t('credit.nextDueDate') }}:</text>
          <text class="due-date" :class="{ overdue: order.overdue_days > 0 }">{{ order.next_due_date }}</text>
          <text v-if="order.overdue_days > 0" class="overdue-days">{{ formatOverdueDaysText(order.overdue_days) }}</text>
        </view>

        <view class="order-time">
          <text>{{ t('credit.createdAt') }}: {{ order.created_at }}</text>
        </view>
      </view>
    </view>

    <!-- 加载更多 -->
    <view v-if="!loading && hasMore" class="load-more" @click="loadMore">
      <text>{{ t('common.loadMore') }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getInstallmentOrders, type InstallmentOrder } from '@/api/credit'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const appStore = useAppStore()

const loading = ref(true)
const orders = ref<InstallmentOrder[]>([])
const currentStatus = ref<number | null>(null)
const page = ref(1)
const pageSize = 10
const hasMore = ref(false)

const tabs = computed(() => [
  { value: null, label: t('common.all') },
  { value: 1, label: t('credit.statusActive') },
  { value: 3, label: t('credit.statusOverdue') },
  { value: 2, label: t('credit.statusCompleted') },
])

// 格式化价格（使用汇率转换）
function formatPrice(amount: number, currency: string = 'USD'): string {
  return appStore.formatPrice(amount, currency)
}

// 格式化逾期天数文本（手动替换插值，解决 UniApp vue-i18n 插值不生效问题）
function formatOverdueDaysText(days: number): string {
  const template = t('credit.overdueDays')
  return template.replace('[DAYS]', String(days))
}

async function loadOrders(refresh = false) {
  if (refresh) {
    page.value = 1
    orders.value = []
  }

  try {
    loading.value = true
    const params: any = { page: page.value, pageSize }
    if (currentStatus.value !== null) {
      params.status = currentStatus.value
    }

    const res = await getInstallmentOrders(params)
    const list = res.data?.list || []

    if (refresh) {
      orders.value = list
    } else {
      orders.value.push(...list)
    }

    hasMore.value = list.length >= pageSize
  } catch (e) {
    console.error('Failed to load orders:', e)
  } finally {
    loading.value = false
  }
}

function loadMore() {
  if (hasMore.value && !loading.value) {
    page.value++
    loadOrders()
  }
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/credit/order-detail?id=${id}` })
}

watch(currentStatus, () => {
  loadOrders(true)
})

onShow(() => {
  // 设置导航标题
  uni.setNavigationBarTitle({ title: t('page.installmentOrders') })
  loadOrders(true)
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.filter-tabs {
  display: flex;
  background: #fff;
  padding: 12px 16px;
  gap: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.tab {
  padding: 8px 16px;
  font-size: 14px;
  color: #666;
  border-radius: 20px;
  background: #f5f5f5;

  &.active {
    background: #FF6B35;
    color: #fff;
  }
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 14px;
  color: #999;
}

.order-list {
  padding: 16px;
}

.order-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.order-no {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.order-status {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 4px;
  background: #f5f5f5;
  color: #666;

  &.status-1 {
    background: #E8F5E9;
    color: #4CAF50;
  }
  &.status-2 {
    background: #E3F2FD;
    color: #2196F3;
  }
  &.status-3 {
    background: #FFEBEE;
    color: #F44336;
  }
  &.status-4 {
    background: #FFF3E0;
    color: #FF9800;
  }
}

.order-amount {
  display: flex;
  gap: 24px;
  margin-bottom: 12px;
}

.amount-item {
  .label {
    font-size: 12px;
    color: #999;
    display: block;
    margin-bottom: 4px;
  }
  .value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
  }
}

.order-progress {
  margin-bottom: 12px;
}

.progress-bar {
  height: 6px;
  background: #f0f0f0;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 6px;
}

.progress-fill {
  height: 100%;
  background: #FF6B35;
  border-radius: 3px;
  transition: width 0.3s;
}

.progress-text {
  font-size: 12px;
  color: #666;
}

.order-due {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-bottom: 12px;
}

.due-label {
  font-size: 12px;
  color: #999;
}

.due-date {
  font-size: 13px;
  color: #333;

  &.overdue {
    color: #F44336;
  }
}

.overdue-days {
  font-size: 12px;
  color: #F44336;
  font-weight: 500;
}

.order-time {
  font-size: 12px;
  color: #999;
}

.load-more {
  text-align: center;
  padding: 20px;
  color: #666;
  font-size: 14px;
}
</style>
