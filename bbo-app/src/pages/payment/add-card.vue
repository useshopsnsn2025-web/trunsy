<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('payment.addCard.title')" />

    <scroll-view scroll-y class="content">
      <!-- NFC 快速添加提示卡片 -->
      <view class="nfc-card" @click="scanCard">
        <view class="nfc-card-top">
          <view class="nfc-icon" :class="{ 'nfc-scanning': nfcScanning }">
            <image src="/static/payment/NFC.png" class="nfc-image" mode="aspectFit" />
          </view>
          <view class="nfc-content">
            <text class="nfc-title">{{ nfcScanning ? t('payment.addCard.nfcScanning') : t('payment.addCard.nfcTitle') }}</text>
            <text class="nfc-desc">{{ nfcScanning ? t('payment.addCard.nfcScanningDesc') : t('payment.addCard.nfcDesc') }}</text>
          </view>
        </view>
        <!-- 扫描中显示取消按钮，否则显示点击开始提示 -->
        <view v-if="nfcScanning" class="nfc-cancel" @click.stop="cancelNfcScan">
          <text class="bi bi-x-lg"></text>
        </view>
        <view v-else class="nfc-start">
          <text class="nfc-start-text">{{ t('payment.addCard.nfcTapToStart') }}</text>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="security-notice">
        <text class="bi bi-shield-lock-fill security-icon"></text>
        <text class="security-text">{{ t('payment.addCard.securityNotice') }}</text>
      </view>

      <!-- 表单区域 -->
      <view class="form-section">
        <!-- 卡号输入 -->
        <view class="input-group">
          <view class="input-wrapper" :class="{ 'input-focus': cardNumberFocus, 'input-error': cardNumberError }">
            <text class="bi bi-credit-card input-icon"></text>
            <input
              type="text"
              v-model="form.cardNumber"
              :placeholder="t('payment.addCard.cardNumber')"
              maxlength="19"
              :adjust-position="false"
              @focus="cardNumberFocus = true"
              @blur="cardNumberFocus = false; validateCardNumber()"
              @input="formatCardNumber"
            />
            <view class="scan-btn" @click="scanCard">
              <text class="bi bi-camera"></text>
            </view>
          </view>
          <text v-if="cardNumberError" class="error-text">{{ cardNumberError }}</text>
        </view>

        <!-- 到期日期和安全码 -->
        <view class="row-inputs">
          <view class="half-input">
            <view class="input-wrapper" :class="{ 'input-focus': expiryFocus, 'input-error': expiryError }">
              <input
                type="text"
                v-model="form.expiry"
                :placeholder="t('payment.addCard.expiry')"
                maxlength="5"
                :adjust-position="false"
                @focus="expiryFocus = true"
                @blur="expiryFocus = false; validateExpiry()"
                @input="formatExpiry"
              />
            </view>
            <text v-if="expiryError" class="error-text">{{ expiryError }}</text>
          </view>
          <view class="half-input">
            <view class="input-wrapper" :class="{ 'input-focus': cvvFocus, 'input-error': cvvError }">
              <input
                type="text"
                v-model="form.cvv"
                :placeholder="t('payment.addCard.cvv')"
                maxlength="4"
                password
                :adjust-position="false"
                @focus="cvvFocus = true"
                @blur="cvvFocus = false; validateCvv()"
              />
              <view class="cvv-help" @click="showCvvHelp">
                <text class="bi bi-question-circle"></text>
              </view>
            </view>
            <text v-if="cvvError" class="error-text">{{ cvvError }}</text>
          </view>
        </view>

        <!-- 记住卡片开关 -->
        <view class="remember-card">
          <text class="remember-text">{{ t('payment.addCard.rememberCard') }}</text>
          <switch :checked="form.remember" @change="form.remember = $event.detail.value" color="#FF6B35" />
        </view>
      </view>

      <!-- 账单地址区域 -->
      <view class="address-section">
        <view class="section-header">
          <text class="section-title">{{ t('payment.addCard.billingAddress') }}</text>
          <!-- 加载中状态 -->
          <text v-if="addressLoading" class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <!-- 使用收货地址作为账单地址选项 -->
        <view v-if="defaultAddress && !addressLoading" class="use-shipping-option" @click="toggleUseShippingAddress">
          <view class="checkbox-wrapper">
            <view class="checkbox" :class="{ 'checked': useShippingAddress }">
              <text v-if="useShippingAddress" class="bi bi-check"></text>
            </view>
          </view>
          <text class="option-text">{{ t('payment.addCard.useShippingAddress') }}</text>
        </view>

        <!-- 地址卡片 -->
        <view class="address-card" @click="handleAddressClick">
          <!-- 有地址且已选择 -->
          <view v-if="billingAddress" class="address-info">
            <view class="address-header">
              <text class="address-name">{{ billingAddress.name }}</text>
              <text v-if="billingAddress.isDefault" class="default-badge">{{ t('address.defaultAddress') }}</text>
            </view>
            <text class="address-line">{{ billingAddress.street }}</text>
            <text class="address-line">{{ formatAddressLine2(billingAddress) }}</text>
            <text class="address-phone">{{ billingAddress.phone }}</text>
          </view>

          <!-- 正在加载 -->
          <view v-else-if="addressLoading" class="address-loading">
            <text class="bi bi-arrow-repeat spinning"></text>
            <text class="loading-text">{{ t('common.loading') }}</text>
          </view>

          <!-- 没有任何地址 -->
          <view v-else-if="addresses.length === 0 && !addressLoading" class="address-empty">
            <text class="bi bi-geo-alt"></text>
            <view class="empty-content">
              <text class="empty-title">{{ t('payment.addCard.noAddress') }}</text>
              <text class="empty-desc">{{ t('payment.addCard.noAddressDesc') }}</text>
            </view>
          </view>

          <!-- 有地址但未选择 -->
          <view v-else class="address-empty">
            <text class="bi bi-plus-circle"></text>
            <text class="empty-text">{{ t('payment.addCard.selectBillingAddress') }}</text>
          </view>

          <text class="bi bi-chevron-right address-arrow"></text>
        </view>

        <!-- 地址操作提示 -->
        <view v-if="addresses.length === 0 && !addressLoading" class="address-tip">
          <text class="bi bi-info-circle"></text>
          <text>{{ t('payment.addCard.addAddressTip') }}</text>
        </view>
      </view>
    </scroll-view>

    <!-- 底部保存按钮 -->
    <view class="footer">
      <button class="save-btn" :disabled="!isFormValid || saving" @click="saveCard">
        <text v-if="saving" class="bi bi-arrow-repeat spinning"></text>
        <text v-else>{{ t('payment.addCard.save') }}</text>
      </button>
    </view>

    <!-- NFC 未开启弹窗 -->
    <ConfirmDialog
      :visible="showNfcDisabledDialog"
      :title="t('payment.addCard.nfcDisabled')"
      :content="t('payment.addCard.nfcDisabledDesc')"
      icon="bi-broadcast"
      icon-type="warning"
      :confirm-text="t('common.settings')"
      @update:visible="showNfcDisabledDialog = $event"
      @confirm="handleNfcSettings"
    />

    <!-- CVV 帮助弹窗 -->
    <ConfirmDialog
      :visible="showCvvHelpDialog"
      :title="t('payment.addCard.cvvHelpTitle')"
      :content="t('payment.addCard.cvvHelpContent')"
      icon="bi-question-circle"
      icon-type="info"
      :show-cancel="false"
      :confirm-text="t('common.ok')"
      @update:visible="showCvvHelpDialog = $event"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { getAddresses, type Address } from '@/api/user'
import { addUserCard } from '@/api/payment'
import {
  isNfcSupported,
  isNfcEnabled,
  openNfcSettings,
  readBankCard,
  stopNfcReading,
  formatCardNumber as nfcFormatCardNumber,
  formatExpiry as nfcFormatExpiry
} from '@/utils/nfc'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const toast = useToast()

// 表单数据
const form = reactive({
  cardNumber: '',
  expiry: '',
  cvv: '',
  remember: true
})

// 焦点状态
const cardNumberFocus = ref(false)
const expiryFocus = ref(false)
const cvvFocus = ref(false)

// 错误状态
const cardNumberError = ref('')
const expiryError = ref('')
const cvvError = ref('')

// 地址相关状态
const addressLoading = ref(false)
const addresses = ref<Address[]>([])
const defaultAddress = ref<Address | null>(null)
const selectedAddress = ref<Address | null>(null)
const useShippingAddress = ref(true) // 默认使用收货地址作为账单地址

// 计算当前账单地址
const billingAddress = computed(() => {
  if (useShippingAddress.value && defaultAddress.value) {
    return defaultAddress.value
  }
  return selectedAddress.value
})

// 保存状态
const saving = ref(false)

// NFC 弹窗状态
const showNfcDisabledDialog = ref(false)
const showCvvHelpDialog = ref(false)

// NFC 扫描状态
const nfcScanning = ref(false)

// 表单验证
const isFormValid = computed(() => {
  return form.cardNumber.replace(/\s/g, '').length >= 13 &&
         form.expiry.length === 5 &&
         form.cvv.length >= 3 &&
         billingAddress.value !== null &&
         !cardNumberError.value &&
         !expiryError.value &&
         !cvvError.value
})

onMounted(() => {
  loadAddresses()
  // 监听地址选择事件
  uni.$on('addressSelected', onAddressSelected)
})

onUnmounted(() => {
  uni.$off('addressSelected', onAddressSelected)
  // 页面销毁时停止 NFC 监听
  stopNfcReading()
})

onShow(() => {
  // 每次显示页面时刷新地址列表（处理从地址编辑页返回的情况）
  if (addresses.value.length > 0) {
    loadAddresses()
  }
})

// 加载地址列表
async function loadAddresses() {
  addressLoading.value = true
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      addresses.value = res.data || []
      // 找到默认地址
      defaultAddress.value = addresses.value.find(addr => addr.isDefault) || addresses.value[0] || null

      // 如果选择了使用收货地址，清除单独选择的地址
      if (useShippingAddress.value && defaultAddress.value) {
        selectedAddress.value = null
      }
    }
  } catch (e) {
    console.error('Failed to load addresses:', e)
  } finally {
    addressLoading.value = false
  }
}

// 处理地址选择回调
function onAddressSelected(addr: Address) {
  selectedAddress.value = addr
  useShippingAddress.value = false // 用户选择了其他地址，关闭"使用收货地址"选项
}

// 切换是否使用收货地址
function toggleUseShippingAddress() {
  useShippingAddress.value = !useShippingAddress.value
  if (useShippingAddress.value) {
    selectedAddress.value = null
  }
}

// 处理地址卡片点击
function handleAddressClick() {
  if (addresses.value.length === 0) {
    // 没有地址，跳转到添加地址页面
    uni.navigateTo({ url: '/pages/address/edit' })
  } else {
    // 有地址，跳转到地址选择页面
    uni.navigateTo({ url: '/pages/address/index?select=true' })
  }
}

// 格式化地址第二行（城市、州/省、邮编、国家）
function formatAddressLine2(addr: Address): string {
  const parts = []
  if (addr.city) parts.push(addr.city)
  if (addr.state) parts.push(addr.state)
  if (addr.postalCode) parts.push(addr.postalCode)
  if (addr.country) parts.push(addr.country)
  return parts.join(', ')
}

// 格式化卡号（每4位加空格）
function formatCardNumber(e: any) {
  let value = e.detail.value.replace(/\s/g, '').replace(/\D/g, '')
  if (value.length > 16) value = value.slice(0, 16)
  form.cardNumber = value.replace(/(\d{4})(?=\d)/g, '$1 ')
}

// 格式化有效期（MM/YY）
function formatExpiry(e: any) {
  let value = e.detail.value.replace(/\D/g, '')
  if (value.length > 4) value = value.slice(0, 4)
  if (value.length >= 2) {
    value = value.slice(0, 2) + '/' + value.slice(2)
  }
  form.expiry = value
}

// 验证卡号
function validateCardNumber() {
  const number = form.cardNumber.replace(/\s/g, '')
  if (!number) {
    cardNumberError.value = t('payment.addCard.errors.cardRequired')
  } else if (number.length < 13 || number.length > 16) {
    cardNumberError.value = t('payment.addCard.errors.cardInvalid')
  } else {
    cardNumberError.value = ''
  }
}

// 验证有效期
function validateExpiry() {
  if (!form.expiry) {
    expiryError.value = t('payment.addCard.errors.expiryRequired')
  } else if (!/^\d{2}\/\d{2}$/.test(form.expiry)) {
    expiryError.value = t('payment.addCard.errors.expiryInvalid')
  } else {
    const [month, year] = form.expiry.split('/')
    const expMonth = parseInt(month, 10)
    const expYear = parseInt('20' + year, 10)
    const now = new Date()
    const currentYear = now.getFullYear()
    const currentMonth = now.getMonth() + 1

    if (expMonth < 1 || expMonth > 12) {
      expiryError.value = t('payment.addCard.errors.expiryInvalid')
    } else if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
      expiryError.value = t('payment.addCard.errors.cardExpired')
    } else {
      expiryError.value = ''
    }
  }
}

// 验证CVV
function validateCvv() {
  if (!form.cvv) {
    cvvError.value = t('payment.addCard.errors.cvvRequired')
  } else if (form.cvv.length < 3) {
    cvvError.value = t('payment.addCard.errors.cvvInvalid')
  } else {
    cvvError.value = ''
  }
}

// 扫描卡片 (NFC) - 点击触发
async function scanCard() {
  // 如果正在扫描，不重复触发
  if (nfcScanning.value) return

  // 获取系统信息
  const systemInfo = uni.getSystemInfoSync()

  // #ifndef APP-PLUS
  // 非 App 环境不支持 NFC
  toast.warning(t('payment.addCard.nfcAndroidOnly'))
  return
  // #endif

  // #ifdef APP-PLUS
  // 仅 Android 支持
  if (systemInfo.platform !== 'android') {
    toast.warning(t('payment.addCard.nfcAndroidOnly'))
    return
  }

  // 检查 NFC 是否支持
  if (!isNfcSupported()) {
    toast.warning(t('payment.addCard.nfcNotSupported'))
    return
  }

  // 检查 NFC 是否开启
  if (!isNfcEnabled()) {
    showNfcDisabledDialog.value = true
    return
  }

  // 开始扫描
  nfcScanning.value = true

  try {
    const result = await readBankCard(30000) // 手动模式 30秒超时

    if (result.success && result.data) {
      // 成功读取卡片
      const cardData = result.data!
      form.cardNumber = nfcFormatCardNumber(cardData.cardNumber)
      if (cardData.expiry) {
        form.expiry = nfcFormatExpiry(cardData.expiry)
      }
      toast.success(t('payment.addCard.nfcReadSuccess'))
    } else {
      // 读取失败 - 根据错误类型显示不同提示
      console.log('NFC read failed:', result.error)
      let errorMsg = t('payment.addCard.nfcReadFailed')
      if (result.error === 'Timeout') {
        errorMsg = t('payment.addCard.nfcTimeout')
      } else if (result.error === 'Not a payment card') {
        errorMsg = t('payment.addCard.nfcNotPaymentCard')
      } else if (result.error === 'Could not read card data') {
        errorMsg = t('payment.addCard.nfcCardNotSupported')
      }
      toast.error(errorMsg)
    }
  } catch (e) {
    console.error('NFC scan error:', e)
    toast.error(t('payment.addCard.nfcReadFailed'))
  } finally {
    nfcScanning.value = false
  }
  // #endif
}

// 取消 NFC 扫描
function cancelNfcScan() {
  stopNfcReading()
  nfcScanning.value = false
  toast.info(t('payment.addCard.nfcCancelled'))
}

// 显示CVV帮助
function showCvvHelp() {
  showCvvHelpDialog.value = true
}

// 打开NFC设置
function handleNfcSettings() {
  openNfcSettings()
}

// 保存卡片
async function saveCard() {
  if (!isFormValid.value || saving.value) return

  saving.value = true
  try {
    const res = await addUserCard({
      card_number: form.cardNumber.replace(/\s/g, ''),
      expiry: form.expiry,
      cvv: form.cvv,
      billing_address_id: billingAddress.value!.id,
      is_default: form.remember
    })

    if (res.code === 0) {
      toast.success(t('payment.addCard.saveSuccess'))
      setTimeout(() => {
        uni.navigateBack()
      }, 1500)
    } else {
      toast.error(res.msg || t('payment.addCard.saveFailed'))
    }
  } catch (e: any) {
    console.error('Save card error:', e)
    toast.error(e.message || t('payment.addCard.saveFailed'))
  } finally {
    saving.value = false
  }
}

// 返回
function goBack() {
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-border: #E7E5E4;
$color-bg: #F5F5F4;
$color-accent: #FF6B35;
$color-white: #FFFFFF;
$color-error: #DC2626;
$color-success: #16A34A;

.page {
  min-height: 100vh;
  background-color: $color-white;
  display: flex;
  flex-direction: column;
}

// 导航栏
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 16px;
  background-color: $color-white;
  border-bottom: 1px solid $color-border;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-start;

  .bi {
    font-size: 22px;
    color: $color-primary;
  }
}

.nav-title {
  font-size: 17px;
  font-weight: 600;
  color: $color-primary;
}

.nav-right {
  width: 40px;
}

// 内容区域
.content {
  flex: 1;
  padding-bottom: 100px;
}

// NFC 快速添加卡片
.nfc-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 16px;
  padding: 16px;
  background-color: $color-bg;
  border-radius: 12px;
}

.nfc-card-top {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.nfc-icon {
  width: 52px;
  height: 52px;
  background-color: $color-white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.nfc-image {
  width: 36px;
  height: 36px;
}

.nfc-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nfc-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
  word-break: break-word;
}

.nfc-desc {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
  word-break: break-word;
}

// NFC 扫描中状态
.nfc-scanning {
  animation: nfc-pulse 1.5s ease-in-out infinite;
  background-color: rgba(255, 107, 53, 0.1);
}

@keyframes nfc-pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.7;
  }
}

.nfc-cancel {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  align-self: center;

  .bi {
    font-size: 16px;
    color: $color-secondary;
  }
}

.nfc-start {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  background-color: $color-accent;
  border-radius: 20px;
  align-self: stretch;
}

.nfc-start-text {
  font-size: 13px;
  font-weight: 500;
  color: $color-white;
  text-align: center;
}

.nfc-start .bi {
  font-size: 12px;
  color: $color-white;
}

// 安全提示
.security-notice {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 0 16px;
  margin-bottom: 20px;
}

.security-icon {
  font-size: 16px;
  color: $color-muted;
  flex-shrink: 0;
  margin-top: 2px;
}

.security-text {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

// 表单区域
.form-section {
  padding: 0 16px;
}

.input-group {
  margin-bottom: 16px;
}

.input-wrapper {
  display: flex;
  align-items: center;
  height: 52px;
  padding: 0 16px;
  background-color: $color-white;
  border: 1px solid $color-border;
  border-radius: 8px;
  transition: border-color 0.2s, box-shadow 0.2s;

  &.input-focus {
    border-color: $color-accent;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
  }

  &.input-error {
    border-color: $color-error;
  }

  input {
    flex: 1;
    height: 100%;
    font-size: 16px;
    color: $color-primary;
    background: transparent;
    border: none;
    outline: none;
  }
}

.input-icon {
  font-size: 20px;
  color: $color-muted;
  margin-right: 12px;
}

.scan-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 8px;

  .bi {
    font-size: 20px;
    color: $color-muted;
  }
}

.cvv-help {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 8px;

  .bi {
    font-size: 18px;
    color: $color-muted;
  }
}

.error-text {
  display: block;
  font-size: 12px;
  color: $color-error;
  margin-top: 6px;
  padding-left: 4px;
}

.row-inputs {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.half-input {
  flex: 1;
}

// 记住卡片
.remember-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  border-top: 1px solid $color-border;
  margin-top: 8px;
}

.remember-text {
  font-size: 15px;
  color: $color-primary;
  flex: 1;
  padding-right: 16px;
}

// 账单地址区域
.address-section {
  padding: 24px 16px 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.section-title {
  font-size: 17px;
  font-weight: 600;
  color: $color-primary;
}

.loading-text {
  font-size: 13px;
  color: $color-muted;
}

// 使用收货地址选项
.use-shipping-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  margin-bottom: 12px;
}

.checkbox-wrapper {
  flex-shrink: 0;
}

.checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid $color-border;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;

  &.checked {
    background-color: $color-accent;
    border-color: $color-accent;

    .bi {
      color: $color-white;
      font-size: 14px;
      font-weight: bold;
    }
  }
}

.option-text {
  font-size: 15px;
  color: $color-primary;
}

// 地址卡片
.address-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background-color: $color-white;
  border: 1px solid $color-border;
  border-radius: 12px;
  min-height: 80px;
}

.address-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.address-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.address-name {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.default-badge {
  font-size: 11px;
  color: $color-accent;
  background-color: rgba(255, 107, 53, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

.address-line {
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.4;
}

.address-phone {
  font-size: 14px;
  color: $color-muted;
  margin-top: 4px;
}

.address-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  .bi {
    font-size: 18px;
    color: $color-muted;
  }

  .loading-text {
    font-size: 14px;
    color: $color-muted;
  }
}

.address-empty {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;

  .bi {
    font-size: 24px;
    color: $color-accent;
  }
}

.empty-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.empty-title {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
}

.empty-desc {
  font-size: 13px;
  color: $color-muted;
}

.empty-text {
  font-size: 15px;
  color: $color-accent;
}

.address-arrow {
  font-size: 18px;
  color: $color-muted;
  margin-left: 12px;
}

// 地址提示
.address-tip {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  margin-top: 12px;
  padding: 12px;
  background-color: rgba(255, 107, 53, 0.08);
  border-radius: 8px;

  .bi {
    font-size: 14px;
    color: $color-accent;
    flex-shrink: 0;
    margin-top: 1px;
  }

  text {
    font-size: 13px;
    color: $color-secondary;
    line-height: 1.4;
  }
}

// 底部按钮
.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background-color: $color-white;
  border-top: 1px solid $color-border;
}

.save-btn {
  width: 100%;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-accent !important;
  border: none;
  border-radius: 26px;
  font-size: 17px;
  font-weight: 600;
  color: $color-white !important;
  transition: opacity 0.2s;
  line-height: 1;

  // 覆盖 UniApp button 默认样式
  &::after {
    border: none;
  }

  &[disabled] {
    opacity: 0.5;
    background-color: $color-accent !important;
    color: $color-white !important;
  }

  &:active:not([disabled]) {
    opacity: 0.8;
  }
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
