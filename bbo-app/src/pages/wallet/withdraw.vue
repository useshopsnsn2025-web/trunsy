<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('wallet.withdrawTitle')" />

    <LoadingPage v-if="pageLoading" />

    <template v-else>
      <scroll-view class="scroll-content" scroll-y :show-scrollbar="false" :enhanced="true" :bounces="false">
        <!-- 可用余额 -->
        <view class="balance-card">
          <text class="balance-label">{{ t('wallet.availableBalance') }}</text>
          <text class="balance-value">{{ formatPrice(walletInfo?.balance || 0) }}</text>
        </view>

        <!-- 提现表单 -->
        <view class="form-section">
          <!-- 提现金额 -->
          <view class="form-item">
            <text class="form-label">{{ t('wallet.withdrawAmount') }}</text>
            <view class="amount-input-wrap">
              <text class="currency-symbol">{{ currencySymbol }}</text>
              <input
                v-model="amount"
                type="digit"
                class="amount-input"
                :placeholder="t('wallet.enterAmount')"
                placeholder-style="font-size: 14px; font-weight: 400; color: #999;"
                :adjust-position="false"
                @input="onAmountChange"
              />
              <view class="withdraw-all" @click="withdrawAll">
                <text>{{ t('wallet.withdrawAll') }}</text>
              </view>
            </view>
            <view class="amount-tips">
              <text>{{ t('wallet.minAmount') }}: {{ formatPrice(config?.minAmount || 0) }}</text>
              <text>{{ t('wallet.maxAmount') }}: {{ formatPrice(config?.maxAmount || 0) }}</text>
            </view>
          </view>

          <!-- 选择银行账户 -->
          <view class="form-item">
            <text class="form-label">{{ t('wallet.selectAccount') }}</text>
            <view v-if="accounts.length === 0" class="add-card-btn" @click="goAddAccount">
              <text class="bi bi-plus-circle"></text>
              <text>{{ t('wallet.addAccountFirst') }}</text>
            </view>
            <view v-else class="card-list">
              <view
                v-for="account in accounts"
                :key="account.id"
                class="card-item"
                :class="{ selected: selectedAccountId === account.id }"
                @click="selectAccount(account.id)"
              >
                <view class="card-icon">
                  <image
                    v-if="account.bank_logo"
                    :src="account.bank_logo"
                    class="bank-logo"
                    mode="aspectFit"
                  />
                  <text v-else class="bi bi-bank"></text>
                </view>
                <view class="card-info">
                  <text class="card-brand">{{ account.bank_name }}</text>
                  <text class="card-number">{{ account.organization_id }} - {{ account.login_user_id }}</text>
                </view>
                <view v-if="selectedAccountId === account.id" class="card-check">
                  <text class="bi bi-check-circle-fill"></text>
                </view>
              </view>
            </view>
          </view>

          <!-- 费用明细 -->
          <view v-if="amount && parseFloat(amount) > 0" class="fee-detail">
            <view class="fee-row">
              <text class="fee-label">{{ t('wallet.withdrawAmount') }}</text>
              <text class="fee-value">{{ formatDisplayAmount(parseFloat(amount) || 0) }}</text>
            </view>
            <view class="fee-row">
              <text class="fee-label">{{ t('wallet.serviceFee') }}</text>
              <text class="fee-value">-{{ formatDisplayAmount(calculatedFee) }}</text>
            </view>
            <view class="fee-divider"></view>
            <view class="fee-row total">
              <text class="fee-label">{{ t('wallet.actualReceive') }}</text>
              <text class="fee-value">{{ formatDisplayAmount(actualAmount) }}</text>
            </view>
          </view>

          <!-- 今日限额 -->
          <view class="limit-info">
            <text class="bi bi-info-circle"></text>
            <text>{{ t('wallet.remainingLimit') }}: {{ formatPrice(config?.remainingLimit || 0) }}</text>
          </view>
        </view>

        <!-- 底部留白 -->
        <view class="bottom-spacer"></view>
      </scroll-view>

      <!-- 提交按钮 -->
      <view class="submit-section">
        <view
          class="submit-btn"
          :class="{ disabled: !canSubmit }"
          @click="handleSubmit"
        >
          <text>{{ t('wallet.submitWithdraw') }}</text>
        </view>
        <text class="submit-tip">{{ t('wallet.withdrawTip') }}</text>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { getWalletInfo, getWithdrawConfig, applyWithdraw, type WalletInfo, type WithdrawConfig } from '@/api/wallet'
import { getCurrencySymbol, convertAmount, formatCurrencyAmount } from '@/utils/currency'
import { getLinkedAccounts, type LinkedAccount } from '@/api/ocbc'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

const pageLoading = ref(true)
const walletInfo = ref<WalletInfo | null>(null)
const config = ref<WithdrawConfig | null>(null)
const accounts = ref<LinkedAccount[]>([])
const amount = ref('')
const selectedAccountId = ref<number | null>(null)
const submitting = ref(false)

// 钱包原始货币（后端使用 USD）
const walletCurrency = computed(() => walletInfo.value?.currency || 'USD')

// 用户显示货币
const displayCurrency = computed(() => appStore.currency)

// 获取货币符号（使用用户选择的货币）
const currencySymbol = computed(() => {
  return getCurrencySymbol(displayCurrency.value)
})

// 将原始货币金额转换为用户货币显示
function toDisplayAmount(amount: number): number {
  if (walletCurrency.value === displayCurrency.value) return amount
  return convertAmount(amount, walletCurrency.value, displayCurrency.value, appStore.exchangeRates)
}

// 将用户输入的金额转换回原始货币（用于提交）
function toWalletAmount(amount: number): number {
  if (walletCurrency.value === displayCurrency.value) return amount
  return convertAmount(amount, displayCurrency.value, walletCurrency.value, appStore.exchangeRates)
}

// 格式化价格（从钱包原始货币转换为用户货币显示）
function formatPrice(value: number): string {
  return appStore.formatPrice(value, walletCurrency.value)
}

// 格式化用户货币金额（已经是用户货币，只需添加符号和千分位）
function formatDisplayAmount(value: number): string {
  return formatCurrencyAmount(value, displayCurrency.value, appStore.locale)
}

// 转换后的余额（用户货币）
const displayBalance = computed(() => toDisplayAmount(walletInfo.value?.balance || 0))

// 转换后的最低金额（用户货币）
const displayMinAmount = computed(() => toDisplayAmount(config.value?.minAmount || 0))

// 转换后的最高金额（用户货币）
const displayMaxAmount = computed(() => toDisplayAmount(config.value?.maxAmount || 0))

// 转换后的剩余限额（用户货币）
const displayRemainingLimit = computed(() => toDisplayAmount(config.value?.remainingLimit || 0))

// 计算手续费（基于用户输入的金额，费率计算）
const calculatedFee = computed(() => {
  const amt = parseFloat(amount.value) || 0
  if (!config.value || amt <= 0) return 0
  // 手续费按比例计算（用户货币）
  return amt * config.value.feeRate + toDisplayAmount(config.value.fixedFee)
})

// 实际到账（用户货币）
const actualAmount = computed(() => {
  const amt = parseFloat(amount.value) || 0
  return Math.max(0, amt - calculatedFee.value)
})

// 是否可提交
const canSubmit = computed(() => {
  const amt = parseFloat(amount.value) || 0
  if (amt <= 0) return false
  if (!selectedAccountId.value) return false
  if (!config.value) return false
  if (amt < displayMinAmount.value) return false
  if (amt > displayMaxAmount.value) return false
  if (amt > displayBalance.value) return false
  if (amt > displayRemainingLimit.value) return false
  if (actualAmount.value <= 0) return false
  return true
})

// 金额变化
function onAmountChange() {
  // 限制输入
  if (amount.value) {
    const num = parseFloat(amount.value)
    if (num < 0) {
      amount.value = '0'
    }
  }
}

// 全部提现（使用用户货币金额）
function withdrawAll() {
  if (walletInfo.value) {
    const maxWithdraw = Math.min(
      displayBalance.value,
      displayMaxAmount.value || displayBalance.value,
      displayRemainingLimit.value || displayBalance.value
    )
    // 取整显示
    amount.value = Math.floor(maxWithdraw).toString()
  }
}

// 选择银行账户
function selectAccount(accountId: number) {
  selectedAccountId.value = accountId
}

// 添加银行账户
function goAddAccount() {
  uni.navigateTo({ url: '/pages/wallet/withdrawalMethods' })
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

// 加载提现配置
async function loadConfig() {
  try {
    const res = await getWithdrawConfig()
    config.value = res.data
  } catch (e) {
    console.error('Failed to load withdraw config:', e)
  }
}

// 加载已关联的银行账户
async function loadAccounts() {
  try {
    const res = await getLinkedAccounts()
    accounts.value = res.data.accounts || []
    // 默认选择第一个账户
    if (accounts.value.length > 0 && !selectedAccountId.value) {
      selectedAccountId.value = accounts.value[0].id
    }
  } catch (e) {
    console.error('Failed to load accounts:', e)
  }
}

// 提交提现
async function handleSubmit() {
  if (!canSubmit.value || submitting.value) return

  const displayAmt = parseFloat(amount.value)
  if (!selectedAccountId.value) {
    toast.error(t('wallet.selectAccountTip'))
    return
  }

  // 将用户输入的金额（用户货币）转回钱包原始货币（USD）
  const walletAmt = toWalletAmount(displayAmt)

  submitting.value = true
  uni.showLoading({ title: '' })

  try {
    const res = await applyWithdraw({
      amount: walletAmt,
      accountId: selectedAccountId.value,
      displayCurrency: displayCurrency.value,
      displayAmount: displayAmt
    })

    toast.success(t('wallet.withdrawSubmitted'))

    // 返回钱包页面
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  } catch (e: any) {
    toast.error(e.message || t('wallet.withdrawFailed'))
  } finally {
    submitting.value = false
    uni.hideLoading()
  }
}

// 加载数据
async function loadData() {
  pageLoading.value = true
  await Promise.all([
    loadWalletInfo(),
    loadConfig(),
    loadAccounts()
  ])
  pageLoading.value = false
}

onShow(() => {
  uni.setNavigationBarTitle({ title: t('wallet.withdrawTitle') })
  loadData()
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
}

.scroll-content {
  height: calc(100vh - 44px);
}

.bottom-spacer {
  height: 140px;
}

// ==================
// 余额卡片
// ==================
.balance-card {
  margin: 16px;
  padding: 24px;
  background: linear-gradient(135deg, #1C1917 0%, #44403C 100%);
  border-radius: 16px;
  color: #fff;
}

.balance-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  display: block;
  margin-bottom: 8px;
}

.balance-value {
  font-size: 32px;
  font-weight: 700;
}

// ==================
// 表单区域
// ==================
.form-section {
  margin: 0 16px;
  padding: 20px;
  background: #fff;
  border-radius: 16px;
}

.form-item {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: $color-primary;
  display: block;
  margin-bottom: 12px;
}

// 金额输入
.amount-input-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: $color-bg;
  border-radius: 12px;
  border: 1px solid $color-border;
}

.currency-symbol {
  font-size: 24px;
  font-weight: 600;
  color: $color-primary;
}

.amount-input {
  flex: 1;
  font-size: 24px;
  font-weight: 600;
  color: $color-primary;
  background: transparent;
  border: none;
  outline: none;
}

.withdraw-all {
  padding: 6px 12px;
  background: linear-gradient(135deg, #1C1917 0%, #44403C 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: 12px;
    font-weight: 500;
    color: #FFFF;
  }
}

.amount-tips {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;

  text {
    font-size: 12px;
    color: $color-muted;
  }
}

// 银行卡
.add-card-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  background: $color-bg;
  border: 2px dashed $color-border;
  border-radius: 12px;

  .bi {
    font-size: 20px;
    color: $color-accent;
  }

  text {
    font-size: 14px;
    color: $color-secondary;
  }
}

.card-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.card-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: $color-bg;
  border-radius: 12px;
  border: 2px solid transparent;
  transition: border-color 0.2s;

  &.selected {
    border-color: $color-accent;
    background: rgba(255, 107, 53, 0.05);
  }
}

.card-icon {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;

  .bank-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .bi {
    font-size: 22px;
    color: $color-primary;
  }
}

.card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-brand {
  font-size: 14px;
  font-weight: 500;
  color: $color-primary;
}

.card-number {
  font-size: 13px;
  color: $color-muted;
}

.card-check {
  .bi {
    font-size: 22px;
    color: $color-accent;
  }
}

// 费用明细
.fee-detail {
  padding: 16px;
  background: $color-bg;
  border-radius: 12px;
  margin-bottom: 16px;
}

.fee-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;

  &.total {
    .fee-label, .fee-value {
      font-weight: 600;
      color: $color-primary;
    }
  }
}

.fee-label {
  font-size: 14px;
  color: $color-muted;
}

.fee-value {
  font-size: 14px;
  color: $color-secondary;
}

.fee-divider {
  height: 1px;
  background: $color-border;
  margin: 8px 0;
}

// 限额提示
.limit-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 10px;

  .bi {
    font-size: 16px;
    color: #3B82F6;
  }

  text {
    font-size: 13px;
    color: #1E40AF;
  }
}

// ==================
// 提交按钮
// ==================
.submit-section {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background: #fff;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: $color-accent;
  border-radius: 12px;
  margin-bottom: 8px;

  text {
    font-size: 16px;
    font-weight: 600;
    color: #fff;
  }

  &.disabled {
    background: $color-border;

    text {
      color: $color-muted;
    }
  }
}

.submit-tip {
  display: block;
  text-align: center;
  font-size: 12px;
  color: $color-muted;
}
</style>
