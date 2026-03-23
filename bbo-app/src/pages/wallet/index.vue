<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('wallet.title')" />

    <LoadingPage v-if="pageLoading" />

    <view v-else class="page-content">
      <!-- 钱包卡片 -->
      <view class="wallet-hero">
        <view class="hero-content">
          <text class="hero-label">{{ t('wallet.availableBalance') }}</text>
          <view class="hero-amount">
            <text class="amount-value">{{ formatPrice(walletInfo?.balance || 0) }}</text>
          </view>

          <!-- 资金明细 -->
          <view class="balance-details">
            <view class="balance-item">
              <text class="bi bi-lock"></text>
              <text class="balance-label">{{ t('wallet.frozen') }}</text>
              <text class="balance-value">{{ formatPrice(walletInfo?.frozen || 0) }}</text>
            </view>
            <view class="balance-divider"></view>
            <view class="balance-item">
              <text class="bi bi-hourglass-split"></text>
              <text class="balance-label">{{ t('wallet.processing') }}</text>
              <text class="balance-value">{{ formatPrice(walletInfo?.processing || 0) }}</text>
            </view>
          </view>

          <!-- 累计数据 -->
          <view class="stats-row">
            <view class="stat-item">
              <text class="stat-label">{{ t('wallet.totalIncome') }}</text>
              <text class="stat-value">{{ formatPrice(walletInfo?.totalIncome || 0) }}</text>
            </view>
            <view class="stat-item">
              <text class="stat-label">{{ t('wallet.totalWithdraw') }}</text>
              <text class="stat-value">{{ formatPrice(walletInfo?.totalWithdraw || 0) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 快捷操作 -->
      <view class="quick-actions">
        <view class="action-card" @click="goWithdraw">
          <text class="bi bi-cash-stack action-icon"></text>
          <text class="action-label">{{ t('wallet.withdraw') }}</text>
        </view>
        <view class="action-card" @click="goTransactions">
          <text class="bi bi-list-ul action-icon"></text>
          <text class="action-label">{{ t('wallet.transactions') }}</text>
        </view>
        <view class="action-card" @click="goBankCards">
          <text class="bi bi-credit-card action-icon"></text>
          <text class="action-label">{{ t('wallet.bankCards') }}</text>
        </view>
      </view>

      <!-- 最近交易 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('wallet.recentTransactions') }}</text>
          <view class="section-more" @click="goTransactions">
            <text>{{ t('common.viewAll') }}</text>
            <text class="bi bi-chevron-right"></text>
          </view>
        </view>

        <view v-if="recentTransactions.length === 0" class="empty-state">
          <text class="bi bi-inbox empty-icon"></text>
          <text class="empty-text">{{ t('wallet.noTransactions') }}</text>
        </view>

        <view v-else class="transaction-list">
          <view
            v-for="item in recentTransactions"
            :key="item.id"
            class="transaction-item"
          >
            <view class="transaction-icon" :class="getTransactionIconClass(item.type)">
              <text :class="getTransactionIcon(item.type)"></text>
            </view>
            <view class="transaction-info">
              <text class="transaction-title">{{ getTransactionTitle(item.title) }}</text>
              <text class="transaction-time">{{ formatTime(item.createdAt) }}</text>
            </view>
            <view class="transaction-amount" :class="{ positive: item.type === 1 || item.type === 4 }">
              <text>{{ (item.type === 1 || item.type === 4) ? '+' : '-' }}{{ formatPrice(Math.abs(item.amount)) }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 提现记录 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('wallet.withdrawalRecords') }}</text>
          <view class="section-more" @click="goWithdrawals">
            <text>{{ t('common.viewAll') }}</text>
            <text class="bi bi-chevron-right"></text>
          </view>
        </view>

        <view v-if="recentWithdrawals.length === 0" class="empty-state">
          <text class="bi bi-inbox empty-icon"></text>
          <text class="empty-text">{{ t('wallet.noWithdrawals') }}</text>
        </view>

        <view v-else class="withdrawal-list">
          <view
            v-for="item in recentWithdrawals"
            :key="item.id"
            class="withdrawal-item"
          >
            <view class="withdrawal-info">
              <text class="withdrawal-no">#{{ item.withdrawalNo }}</text>
              <text class="withdrawal-time">{{ formatTime(item.createdAt) }}</text>
            </view>
            <view class="withdrawal-right">
              <text class="withdrawal-amount">{{ formatPrice(item.actualAmount) }}</text>
              <view class="withdrawal-status" :class="getStatusClass(item.status)">
                <text>{{ getStatusText(item.status) }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部安全提示 -->
      <view class="safety-tips">
        <text class="bi bi-shield-check"></text>
        <text class="tips-text">{{ t('wallet.safetyTips') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { getWalletInfo, getTransactions, getWithdrawals, type WalletInfo, type Transaction, type Withdrawal, WITHDRAWAL_STATUS } from '@/api/wallet'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

const { t, locale } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

const pageLoading = ref(true)
const walletInfo = ref<WalletInfo | null>(null)
const recentTransactions = ref<Transaction[]>([])
const recentWithdrawals = ref<Withdrawal[]>([])

// 格式化价格
function formatPrice(amount: number): string {
  return appStore.formatPrice(amount, walletInfo.value?.currency || 'USD')
}

// 格式化时间
function formatTime(dateStr: string): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffTime = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 0) {
    return date.toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' })
  } else if (diffDays === 1) {
    return t('common.yesterday')
  } else if (diffDays < 7) {
    return `${diffDays} ${t('common.daysAgo')}`
  } else {
    return date.toLocaleDateString(locale.value, { month: 'short', day: 'numeric' })
  }
}

// 获取交易图标
function getTransactionIcon(type: number): string {
  switch (type) {
    case 1: return 'bi bi-arrow-down-left' // 收入
    case 2: return 'bi bi-arrow-up-right'  // 支出
    case 3: return 'bi bi-lock'            // 冻结
    case 4: return 'bi bi-unlock'          // 解冻
    default: return 'bi bi-circle'
  }
}

// 交易标题英文到翻译键的映射
const TRANSACTION_TITLE_MAP: Record<string, string> = {
  'Sale Income': 'wallet.transactionSaleIncome',
  'Refund Deduction': 'wallet.transactionRefundDeduction',
  'Withdrawal': 'wallet.transactionWithdrawal',
  'Frozen': 'wallet.transactionFrozen',
  'Unfrozen': 'wallet.transactionUnfrozen',
  'Purchase': 'wallet.transactionPurchase',
  'Refund': 'wallet.transactionRefund',
  'Commission': 'wallet.transactionCommission',
  'Order Payment': 'wallet.transactionOrderPayment',
  'Withdrawal Cancelled': 'wallet.transactionWithdrawalCancelled',
  'Withdrawal Completed': 'wallet.transactionWithdrawalCompleted',
  'Withdrawal Frozen': 'wallet.transactionWithdrawalFrozen',
  'Withdrawal Rejected': 'wallet.transactionWithdrawalRejected',
  'Game Prize': 'wallet.transactionGamePrize',
  'Egg Prize': 'wallet.transactionEggPrize',
  'Treasure Box': 'wallet.transactionTreasureBox',
}

// 带动态编号的交易标题匹配模式
const TRANSACTION_TITLE_PATTERNS: Array<{ pattern: RegExp; key: string; placeholder: string }> = [
  { pattern: /^Order #(.+) completed$/, key: 'wallet.transactionOrderCompleted', placeholder: '[ORDERNO]' },
  { pattern: /^Withdrawal application #(.+)$/, key: 'wallet.transactionWithdrawalApplication', placeholder: '[WITHDRAWALNO]' },
]

// 获取翻译后的交易标题
function getTransactionTitle(title: string): string {
  // 先尝试精确匹配
  const translationKey = TRANSACTION_TITLE_MAP[title]
  if (translationKey) {
    const translated = t(translationKey)
    return translated === translationKey ? title : translated
  }

  // 再尝试模式匹配（带动态编号的标题）
  for (const { pattern, key, placeholder } of TRANSACTION_TITLE_PATTERNS) {
    const match = title.match(pattern)
    if (match) {
      const translated = t(key)
      if (translated !== key) {
        return translated.replace(placeholder, match[1])
      }
      return title
    }
  }

  return title
}

// 获取交易图标样式
function getTransactionIconClass(type: number): string {
  switch (type) {
    case 1: return 'income'
    case 2: return 'expense'
    case 3: return 'frozen'
    case 4: return 'unfrozen'
    default: return ''
  }
}

// 获取提现状态样式
function getStatusClass(status: number): string {
  switch (status) {
    case WITHDRAWAL_STATUS.PENDING: return 'pending'
    case WITHDRAWAL_STATUS.PROCESSING: return 'processing'
    case WITHDRAWAL_STATUS.COMPLETED: return 'completed'
    case WITHDRAWAL_STATUS.REJECTED: return 'rejected'
    default: return ''
  }
}

// 获取提现状态文本
function getStatusText(status: number): string {
  switch (status) {
    case WITHDRAWAL_STATUS.PENDING: return t('wallet.statusPending')
    case WITHDRAWAL_STATUS.PROCESSING: return t('wallet.statusProcessing')
    case WITHDRAWAL_STATUS.COMPLETED: return t('wallet.statusCompleted')
    case WITHDRAWAL_STATUS.REJECTED: return t('wallet.statusRejected')
    default: return ''
  }
}

// 加载钱包信息
async function loadWalletInfo() {
  try {
    const res = await getWalletInfo()
    walletInfo.value = res.data
  } catch (e) {
    console.error('Failed to load wallet info:', e)
  }
}

// 加载最近交易
async function loadRecentTransactions() {
  try {
    const res = await getTransactions({ page: 1, pageSize: 5 })
    recentTransactions.value = res.data?.list || []
  } catch (e) {
    console.error('Failed to load transactions:', e)
  }
}

// 加载最近提现
async function loadRecentWithdrawals() {
  try {
    const res = await getWithdrawals({ page: 1, pageSize: 3 })
    recentWithdrawals.value = res.data?.list || []
  } catch (e) {
    console.error('Failed to load withdrawals:', e)
  }
}

// 导航函数
function goWithdraw() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }
  uni.navigateTo({ url: '/pages/wallet/withdraw' })
}

function goTransactions() {
  uni.navigateTo({ url: '/pages/wallet/transactions' })
}

function goWithdrawals() {
  uni.navigateTo({ url: '/pages/wallet/withdrawals' })
}

function goBankCards() {
  uni.navigateTo({ url: '/pages/wallet/withdrawalMethods' })
}

// 加载数据
async function loadData() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }

  pageLoading.value = true

  await Promise.all([
    loadWalletInfo(),
    loadRecentTransactions(),
    loadRecentWithdrawals(),
  ])

  pageLoading.value = false
}

onShow(() => {
  uni.setNavigationBarTitle({ title: t('wallet.title') })
  loadData()
})
</script>

<style lang="scss" scoped>
// ==================
// 简洁现代设计系统
// Clean Modern Design System
// ==================

// 配色
$color-dark: #1E2028;
$color-text: #2D3142;
$color-muted: #6B7280;
$color-accent: #6366F1;  // 现代紫蓝色
$color-success: #10B981;
$color-warning: #F59E0B;
$color-error: #EF4444;

.page {
  min-height: 100vh;
  background: #F5F5F7;
}

.page-content {
  padding-bottom: 32px;
}

// ==================
// 钱包卡片 - 纯净深色
// ==================
.wallet-hero {
  margin: 16px;
  border-radius: 20px;
  overflow: hidden;
  background: linear-gradient(135deg, #1E2028 0%, #2D3142 100%);
}

.hero-content {
  padding: 24px;
  color: #fff;
}

.hero-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
  display: block;
  margin-bottom: 8px;
}

.hero-amount {
  margin-bottom: 20px;
}

.amount-value {
  font-size: 36px;
  font-weight: 600;
  color: #fff;
}

// 资金明细
.balance-details {
  display: flex;
  align-items: center;
  padding: 16px 0;
  margin-bottom: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.balance-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;

  .bi {
    font-size: 16px;
    color: $color-accent;
  }
}

.balance-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
}

.balance-value {
  font-size: 15px;
  font-weight: 500;
  color: #fff;
}

.balance-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
}

// 累计数据
.stats-row {
  display: flex;
  justify-content: space-between;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
}

.stat-value {
  font-size: 14px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
}

// ==================
// 快捷操作
// ==================
.quick-actions {
  display: flex;
  gap: 12px;
  padding: 0 16px;
  margin-bottom: 20px;
}

.action-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 18px 12px;
  cursor: pointer;
  background: #fff;
  border-radius: 14px;
  transition: background 0.2s ease;

  &:active {
    background: #F9FAFB;
  }
}

.action-icon {
  font-size: 22px;
  color: $color-accent;
}

.action-label {
  font-size: 12px;
  color: $color-muted;
  font-weight: 500;
}

// ==================
// 区块样式
// ==================
.section {
  margin: 0 16px 12px;
  padding: 18px;
  background: #fff;
  border-radius: 14px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-text;
}

.section-more {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: $color-accent;
  cursor: pointer;

  .bi {
    font-size: 12px;
  }

  &:active {
    opacity: 0.7;
  }
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 28px 0;
}

.empty-icon {
  font-size: 36px;
  color: #D1D5DB;
}

.empty-text {
  font-size: 13px;
  color: #9CA3AF;
}

// ==================
// 交易列表
// ==================
.transaction-list {
  display: flex;
  flex-direction: column;
}

.transaction-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #F3F4F6;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &:first-child {
    padding-top: 0;
  }
}

.transaction-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 16px;
  }

  &.income {
    background: #ECFDF5;
    .bi { color: $color-success; }
  }

  &.expense {
    background: #FEF2F2;
    .bi { color: $color-error; }
  }

  &.frozen {
    background: #FFFBEB;
    .bi { color: $color-warning; }
  }

  &.unfrozen {
    background: #ECFDF5;
    .bi { color: $color-success; }
  }
}

.transaction-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.transaction-title {
  font-size: 14px;
  color: $color-text;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.transaction-time {
  font-size: 12px;
  color: $color-muted;
}

.transaction-amount {
  font-size: 14px;
  font-weight: 600;
  color: $color-text;

  &.positive {
    color: $color-success;
  }
}

// ==================
// 提现列表
// ==================
.withdrawal-list {
  display: flex;
  flex-direction: column;
}

.withdrawal-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #F3F4F6;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &:first-child {
    padding-top: 0;
  }
}

.withdrawal-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.withdrawal-no {
  font-size: 14px;
  color: $color-text;
  font-weight: 500;
}

.withdrawal-time {
  font-size: 12px;
  color: $color-muted;
}

.withdrawal-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.withdrawal-amount {
  font-size: 14px;
  font-weight: 600;
  color: $color-text;
}

.withdrawal-status {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;

  &.pending {
    background: #FEF3C7;
    color: #92400E;
  }

  &.processing {
    background: #DBEAFE;
    color: #1E40AF;
  }

  &.completed {
    background: #D1FAE5;
    color: #065F46;
  }

  &.rejected {
    background: #FEE2E2;
    color: #991B1B;
  }
}

// ==================
// 安全提示
// ==================
.safety-tips {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 16px;
  margin: 4px 16px 0;

  .bi {
    font-size: 14px;
    color: $color-success;
  }
}

.tips-text {
  font-size: 12px;
  color: $color-muted;
}
</style>
