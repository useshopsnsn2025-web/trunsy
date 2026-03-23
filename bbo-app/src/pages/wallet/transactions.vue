<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('wallet.transactionsTitle')" />

    <!-- 筛选栏 -->
    <view class="filter-bar">
      <scroll-view scroll-x class="filter-scroll">
        <view class="filter-tabs">
          <view
            class="filter-tab"
            :class="{ active: currentType === null }"
            @click="setType(null)"
          >
            <text>{{ t('wallet.typeAll') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentType === 1 }"
            @click="setType(1)"
          >
            <text>{{ t('wallet.typeIncome') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentType === 2 }"
            @click="setType(2)"
          >
            <text>{{ t('wallet.typeExpense') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentType === 3 }"
            @click="setType(3)"
          >
            <text>{{ t('wallet.typeFreeze') }}</text>
          </view>
          <view
            class="filter-tab"
            :class="{ active: currentType === 4 }"
            @click="setType(4)"
          >
            <text>{{ t('wallet.typeUnfreeze') }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <LoadingPage v-if="pageLoading" />

    <template v-else>
      <!-- 空状态 -->
      <view v-if="transactions.length === 0" class="empty-state">
        <text class="bi bi-inbox empty-icon"></text>
        <text class="empty-text">{{ t('wallet.noTransactions') }}</text>
      </view>

      <!-- 交易列表 -->
      <scroll-view
        v-else
        scroll-y
        class="list-scroll"
        @scrolltolower="loadMore"
      >
        <view class="transaction-list">
          <view
            v-for="item in transactions"
            :key="item.id"
            class="transaction-item"
          >
            <view class="transaction-icon" :class="getTransactionIconClass(item.type)">
              <text :class="getTransactionIcon(item.type)"></text>
            </view>
            <view class="transaction-info">
              <text class="transaction-title">{{ getTransactionTitle(item.title) }}</text>
              <text v-if="item.description" class="transaction-desc">{{ translateDescription(item.description) }}</text>
              <text class="transaction-time">{{ formatTime(item.createdAt) }}</text>
            </view>
            <view class="transaction-right">
              <view class="transaction-amount" :class="{ positive: item.type === 1 || item.type === 4 }">
                <text>{{ (item.type === 1 || item.type === 4) ? '+' : '-' }}{{ formatPrice(Math.abs(item.amount)) }}</text>
              </view>
              <text class="transaction-balance">{{ t('wallet.balance') }}: {{ formatPrice(item.balance) }}</text>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more">
          <text v-if="loadingMore">{{ t('common.loading') }}</text>
          <text v-else>{{ t('common.loadMore') }}</text>
        </view>

        <!-- 没有更多 -->
        <view v-else-if="transactions.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>
      </scroll-view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getTransactions, type Transaction, TRANSACTION_TYPES } from '@/api/wallet'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

const { t, locale } = useI18n()
const appStore = useAppStore()

const pageLoading = ref(true)
const loadingMore = ref(false)
const transactions = ref<Transaction[]>([])
const currentType = ref<number | null>(null)
const page = ref(1)
const pageSize = 20
const hasMore = ref(true)

// 格式化价格
function formatPrice(value: number): string {
  return appStore.formatPrice(value, 'USD')
}

// 格式化时间
function formatTime(dateStr: string): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleString(locale.value, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
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

// 翻译交易描述中的英文关键词和货币金额
function translateDescription(desc: string): string {
  if (!desc) return desc

  // 退款退货: "Refund for return #RTxxx"
  const refundMatch = desc.match(/^Refund for return #(.+)$/)
  if (refundMatch) {
    return t('wallet.descRefundForReturn').replace('[RETURNNO]', refundMatch[1])
  }

  // 提现被拒: "Withdrawal application #xxx rejected: reason" 或已翻译的版本
  const rejectedMatch = desc.match(/^Withdrawal (?:application )?#(\S+)\s+rejected:\s*(.*)$/i)
  if (rejectedMatch) {
    return t('wallet.descWithdrawalRejected').replace('[WITHDRAWALNO]', rejectedMatch[1]).replace('[REASON]', rejectedMatch[2])
  }

  // 提现申请: "Withdrawal application #xxx"
  const withdrawalMatch = desc.match(/^Withdrawal application #(\S+)$/)
  if (withdrawalMatch) {
    return t('wallet.descWithdrawalApplication').replace('[WITHDRAWALNO]', withdrawalMatch[1])
  }

  // 订单完成: "Order #xxx completed"
  const orderMatch = desc.match(/^Order #(.+) completed/)
  if (orderMatch) {
    return t('wallet.descOrderCompleted').replace('[ORDERNO]', orderMatch[1])
  }

  // 通用关键词替换 + 货币转换
  return desc
    .replace(/\$(\d+(?:\.\d+)?)/g, (_match, amount) => formatPrice(parseFloat(amount)))
    .replace(/\bcash coupon\b/gi, t('wallet.descCashCoupon'))
    .replace(/\bCash Voucher\b/g, t('wallet.descCashVoucher'))
    .replace(/\bPoints\b/g, t('wallet.descPoints'))
    .replace(/\bGame Log\b/g, t('wallet.descGameLog'))
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

// 设置类型筛选
function setType(type: number | null) {
  currentType.value = type
  page.value = 1
  transactions.value = []
  hasMore.value = true
  loadTransactions()
}

// 加载交易记录
async function loadTransactions() {
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
    if (currentType.value !== null) {
      params.type = currentType.value
    }

    const res = await getTransactions(params)
    const list = res.data?.list || []

    if (page.value === 1) {
      transactions.value = list
    } else {
      transactions.value = [...transactions.value, ...list]
    }

    hasMore.value = list.length >= pageSize
  } catch (e) {
    console.error('Failed to load transactions:', e)
  } finally {
    pageLoading.value = false
    loadingMore.value = false
  }
}

// 加载更多
function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  loadTransactions()
}

onShow(() => {
  uni.setNavigationBarTitle({ title: t('wallet.transactionsTitle') })
  page.value = 1
  transactions.value = []
  hasMore.value = true
  loadTransactions()
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
// 交易列表
// ==================
.transaction-list {
  padding: 16px;
}

.transaction-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #fff;
  border-radius: 12px;
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.transaction-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 20px;
  }

  &.income {
    background: rgba(5, 150, 105, 0.1);
    .bi { color: $color-success; }
  }

  &.expense {
    background: rgba(220, 38, 38, 0.1);
    .bi { color: $color-error; }
  }

  &.frozen {
    background: rgba(217, 119, 6, 0.1);
    .bi { color: $color-warning; }
  }

  &.unfrozen {
    background: rgba(5, 150, 105, 0.1);
    .bi { color: $color-success; }
  }
}

.transaction-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.transaction-title {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
}

.transaction-desc {
  font-size: 13px;
  color: $color-muted;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.transaction-time {
  font-size: 12px;
  color: $color-muted;
}

.transaction-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.transaction-amount {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;

  &.positive {
    color: $color-success;
  }
}

.transaction-balance {
  font-size: 12px;
  color: $color-muted;
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
