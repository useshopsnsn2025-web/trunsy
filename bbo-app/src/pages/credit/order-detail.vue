<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('credit.orderDetailTitle')" />

    <view v-if="loading" class="loading-state">
      <text>{{ t('common.loading') }}</text>
    </view>

    <template v-else-if="order">
      <!-- 状态卡片 -->
      <view class="status-card" :class="'status-' + order.status">
        <text class="status-text">{{ order.status_text }}</text>
        <text v-if="order.overdue_days > 0" class="overdue-info">{{ formatOverdueDaysText(order.overdue_days) }}</text>
      </view>

      <!-- 金额信息 -->
      <view class="info-card">
        <view class="card-title">{{ t('credit.amountInfo') }}</view>
        <view class="info-grid">
          <view class="info-item">
            <text class="label">{{ t('credit.totalAmount') }}</text>
            <text class="value">{{ formatPrice(order.total_amount, order.currency) }}</text>
          </view>
          <view class="info-item">
            <text class="label">{{ t('credit.downPayment') }}</text>
            <text class="value">{{ formatPrice(order.down_payment, order.currency) }}</text>
          </view>
          <view class="info-item">
            <text class="label">{{ t('credit.financedAmount') }}</text>
            <text class="value">{{ formatPrice(order.financed_amount, order.currency) }}</text>
          </view>
          <view class="info-item">
            <text class="label">{{ t('credit.totalInterest') }}</text>
            <text class="value">{{ formatPrice(order.total_interest, order.currency) }}</text>
          </view>
          <view class="info-item">
            <text class="label">{{ t('credit.totalFee') }}</text>
            <text class="value">{{ formatPrice(order.total_fee, order.currency) }}</text>
          </view>
          <view class="info-item">
            <text class="label">{{ t('credit.periodAmount') }}</text>
            <text class="value highlight">{{ formatPrice(order.period_amount, order.currency) }}/{{ t('credit.period') }}</text>
          </view>
        </view>
      </view>

      <!-- 进度信息 -->
      <view class="info-card">
        <view class="card-title">{{ t('credit.progressInfo') }}</view>
        <view class="progress-section">
          <view class="progress-bar-large">
            <view class="progress-fill" :style="{ width: (order.paid_periods / order.periods * 100) + '%' }"></view>
          </view>
          <view class="progress-info">
            <text class="progress-text">{{ order.paid_periods }}/{{ order.periods }} {{ t('credit.periodsPaid') }}</text>
            <text v-if="order.next_due_date && order.status === 1" class="next-due">{{ t('credit.nextDueDate') }}: {{ order.next_due_date }}</text>
          </view>
        </view>
      </view>

      <!-- 还款计划 -->
      <view class="info-card">
        <view class="card-title">{{ t('credit.repaymentSchedule') }}</view>
        <view class="schedule-list">
          <view v-for="schedule in order.schedules" :key="schedule.period" class="schedule-item" :class="'status-' + schedule.status">
            <view class="schedule-left">
              <text class="schedule-period">{{ formatPeriodNumText(schedule.period) }}</text>
              <text class="schedule-date">{{ schedule.due_date }}</text>
            </view>
            <view class="schedule-right">
              <text class="schedule-amount">{{ formatPrice(schedule.amount, order.currency) }}</text>
              <text class="schedule-status">{{ schedule.status_text || getScheduleStatus(schedule) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 订单信息 -->
      <view class="info-card">
        <view class="card-title">{{ t('credit.orderInfo') }}</view>
        <view class="info-list">
          <view class="info-row">
            <text class="label">{{ t('credit.installmentNo') }}</text>
            <text class="value">{{ order.installment_no }}</text>
          </view>
          <view class="info-row">
            <text class="label">{{ t('credit.currency') }}</text>
            <text class="value">{{ order.currency }}</text>
          </view>
          <view class="info-row">
            <text class="label">{{ t('credit.autoDeduct') }}</text>
            <text class="value">{{ order.auto_deduct ? t('common.yes') : t('common.no') }}</text>
          </view>
          <view class="info-row">
            <text class="label">{{ t('credit.createdAt') }}</text>
            <text class="value">{{ order.created_at }}</text>
          </view>
          <view v-if="order.completed_at" class="info-row">
            <text class="label">{{ t('credit.completedAt') }}</text>
            <text class="value">{{ order.completed_at }}</text>
          </view>
        </view>
      </view>
    </template>

    <view v-else class="empty-state">
      <text>{{ t('credit.orderNotFound') }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getInstallmentOrderDetail, type InstallmentOrderDetail, type InstallmentSchedule } from '@/api/credit'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const appStore = useAppStore()

// 页面显示时加载数据
onShow(() => {
})

const loading = ref(true)
const order = ref<InstallmentOrderDetail | null>(null)

const props = defineProps<{
  id?: string
}>()

// 格式化价格（使用汇率转换）
function formatPrice(amount: number, currency: string = 'USD'): string {
  return appStore.formatPrice(amount, currency)
}

// 格式化逾期天数文本（手动替换插值，解决 UniApp vue-i18n 插值不生效问题）
function formatOverdueDaysText(days: number): string {
  const template = t('credit.overdueDays')
  return template.replace('[DAYS]', String(days))
}

// 格式化期数文本（手动替换插值）
function formatPeriodNumText(num: number): string {
  const template = t('credit.periodNum')
  return template.replace('[NUM]', String(num))
}

function getScheduleStatus(schedule: InstallmentSchedule): string {
  if (schedule.paid_at) {
    return t('credit.paid')
  }
  const today = new Date().toISOString().split('T')[0]
  if (schedule.due_date < today) {
    return t('credit.overdue')
  }
  return t('credit.pending')
}

async function loadOrder() {
  const id = props.id || (uni as any)?.getCurrentPages?.()?.pop?.()?.options?.id

  if (!id) {
    loading.value = false
    return
  }

  try {
    loading.value = true
    const res = await getInstallmentOrderDetail(Number(id))
    order.value = res.data
  } catch (e) {
    console.error('Failed to load order:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadOrder()
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding-bottom: 40px;
}

.loading-state, .empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: #999;
}

.status-card {
  padding: 24px;
  text-align: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;

  &.status-1 {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  }
  &.status-2 {
    background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
  }
  &.status-3 {
    background: linear-gradient(135deg, #F44336 0%, #E91E63 100%);
  }
}

.status-text {
  font-size: 20px;
  font-weight: 600;
  display: block;
}

.overdue-info {
  font-size: 14px;
  margin-top: 8px;
  opacity: 0.9;
}

.info-card {
  margin: 16px;
  background: #fff;
  border-radius: 12px;
  padding: 16px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.info-item {
  .label {
    font-size: 12px;
    color: #999;
    display: block;
    margin-bottom: 4px;
  }
  .value {
    font-size: 15px;
    color: #333;
    font-weight: 500;

    &.highlight {
      color: #FF6B35;
    }
  }
}

.progress-section {
  padding: 12px 0;
}

.progress-bar-large {
  height: 10px;
  background: #f0f0f0;
  border-radius: 5px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #FF6B35 0%, #FF8E53 100%);
  border-radius: 5px;
  transition: width 0.3s;
}

.progress-info {
  display: flex;
  justify-content: space-between;
}

.progress-text {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.next-due {
  font-size: 12px;
  color: #999;
}

.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.schedule-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9f9f9;
  border-radius: 8px;
  border-left: 3px solid #e0e0e0;

  &.status-1 {
    border-left-color: #4CAF50;
    background: #F1F8E9;
  }
  &.status-2 {
    border-left-color: #F44336;
    background: #FFEBEE;
  }
}

.schedule-left {
  display: flex;
  flex-direction: column;
}

.schedule-period {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.schedule-date {
  font-size: 12px;
  color: #999;
  margin-top: 2px;
}

.schedule-right {
  text-align: right;
}

.schedule-amount {
  font-size: 15px;
  color: #333;
  font-weight: 600;
  display: block;
}

.schedule-status {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .label {
    font-size: 14px;
    color: #999;
  }
  .value {
    font-size: 14px;
    color: #333;
  }
}
</style>
