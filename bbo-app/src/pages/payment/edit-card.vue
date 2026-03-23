<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <view class="navbar">
      <view class="nav-left" @click="goBack">
        <text class="bi bi-arrow-left"></text>
      </view>
      <text class="nav-title">{{ t('payment.editCard.title') }}</text>
      <view class="nav-right"></view>
    </view>

    <scroll-view scroll-y class="content">
      <!-- 卡片信息展示 -->
      <view class="card-display">
        <view class="card-icon-wrap">
          <text class="bi" :class="cardIcon"></text>
        </view>
        <view class="card-info">
          <text class="card-brand">{{ cardBrand }}</text>
          <text class="card-number">⁕⁕⁕⁕ ⁕⁕⁕⁕ ⁕⁕⁕⁕ {{ cardData.lastFour }}</text>
          <text class="card-expiry">{{ t('payment.editCard.expires') }} {{ cardData.expiry }}</text>
        </view>
      </view>

      <!-- 账单地址区域 -->
      <view class="address-section">
        <view class="section-header">
          <text class="section-title">{{ t('payment.addCard.billingAddress') }}</text>
          <text v-if="addressLoading" class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <!-- 地址卡片 -->
        <view class="address-card" @click="handleAddressClick">
          <view v-if="billingAddress" class="address-info">
            <view class="address-header">
              <text class="address-name">{{ billingAddress.name }}</text>
              <text v-if="billingAddress.isDefault" class="default-badge">{{ t('address.defaultAddress') }}</text>
            </view>
            <text class="address-line">{{ billingAddress.street }}</text>
            <text class="address-line">{{ formatAddressLine2(billingAddress) }}</text>
            <text class="address-phone">{{ billingAddress.phone }}</text>
          </view>

          <view v-else-if="addressLoading" class="address-loading">
            <text class="bi bi-arrow-repeat spinning"></text>
            <text class="loading-text">{{ t('common.loading') }}</text>
          </view>

          <view v-else class="address-empty">
            <text class="bi bi-plus-circle"></text>
            <text class="empty-text">{{ t('payment.addCard.selectBillingAddress') }}</text>
          </view>

          <text class="bi bi-chevron-right address-arrow"></text>
        </view>
      </view>

      <!-- 设为默认选项 -->
      <view class="option-section">
        <view class="option-item" @click="toggleDefault">
          <text class="option-text">{{ t('payment.editCard.setAsDefaultCard') }}</text>
          <switch :checked="isDefault" @change="isDefault = $event.detail.value" color="#FF6B35" />
        </view>
      </view>
    </scroll-view>

    <!-- 底部保存按钮 -->
    <view class="footer">
      <button class="save-btn" :disabled="!canSave || saving" @click="saveCard">
        <text v-if="saving" class="bi bi-arrow-repeat spinning"></text>
        <text v-else>{{ t('action.save') }}</text>
      </button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { getAddresses, type Address } from '@/api/user'
import { getUserCardDetail, updateUserCard, getCardBrandName, CARD_BRAND_ICONS } from '@/api/userCard'

const { t } = useI18n()
const toast = useToast()

// 卡片数据
const cardId = ref(0)
const cardData = ref({
  id: 0,
  cardType: '',
  cardBrand: '',
  lastFour: '',
  expiry: '',
  billingAddressId: 0,
  isDefault: false
})

// 地址相关
const addressLoading = ref(false)
const addresses = ref<Address[]>([])
const selectedAddressId = ref(0)
const isDefault = ref(false)

// 计算卡品牌显示名称
const cardBrand = computed(() => {
  return getCardBrandName(cardData.value.cardBrand || cardData.value.cardType)
})

// 计算卡片图标
const cardIcon = computed(() => {
  const brand = (cardData.value.cardBrand || cardData.value.cardType || '').toLowerCase()
  return CARD_BRAND_ICONS[brand] || 'bi-credit-card-2-front'
})

// 计算账单地址
const billingAddress = computed(() => {
  return addresses.value.find(addr => addr.id === selectedAddressId.value) || null
})

// 是否可以保存
const canSave = computed(() => {
  return selectedAddressId.value > 0
})

// 保存状态
const saving = ref(false)

onMounted(() => {
  // 获取卡片ID
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1] as any
  const options = currentPage.$page?.options || currentPage.options || {}
  cardId.value = parseInt(options.id) || 0

  if (cardId.value) {
    loadCardDetail()
  }

  // 监听地址选择事件
  uni.$on('addressSelected', onAddressSelected)
})

onUnmounted(() => {
  uni.$off('addressSelected', onAddressSelected)
})

onShow(() => {
  // 每次显示页面时刷新地址列表
  if (addresses.value.length > 0) {
    loadAddresses()
  }
})

// 加载卡片详情
async function loadCardDetail() {
  uni.showLoading({ title: t('common.loading') })
  try {
    const res = await getUserCardDetail(cardId.value)
    if (res.code === 0 && res.data) {
      // 兼容处理：如果返回的是数组，取第一个元素
      const card = Array.isArray(res.data) ? res.data[0] : res.data
      if (!card) {
        toast.error(t('common.loadFailed'))
        setTimeout(() => uni.navigateBack(), 1500)
        return
      }
      cardData.value = {
        id: card.id,
        cardType: card.cardType,
        cardBrand: card.cardBrand,
        lastFour: card.lastFour,
        expiry: card.expiry,
        billingAddressId: card.billingAddressId || 0,
        isDefault: card.isDefault
      }
      selectedAddressId.value = card.billingAddressId || 0
      isDefault.value = card.isDefault
      // 加载地址列表
      await loadAddresses()
    } else {
      toast.error(res.msg || t('common.loadFailed'))
      setTimeout(() => uni.navigateBack(), 1500)
    }
  } catch (e: any) {
    toast.error(e?.message || t('common.loadFailed'))
  } finally {
    uni.hideLoading()
  }
}

// 加载地址列表
async function loadAddresses() {
  addressLoading.value = true
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      addresses.value = res.data || []
    }
  } catch (e) {
    console.error('Failed to load addresses:', e)
  } finally {
    addressLoading.value = false
  }
}

// 处理地址选择回调
function onAddressSelected(addr: Address) {
  selectedAddressId.value = addr.id
}

// 处理地址卡片点击
function handleAddressClick() {
  if (addresses.value.length === 0) {
    uni.navigateTo({ url: '/pages/address/edit' })
  } else {
    uni.navigateTo({ url: '/pages/address/index?select=true' })
  }
}

// 切换默认状态
function toggleDefault() {
  isDefault.value = !isDefault.value
}

// 格式化地址第二行
function formatAddressLine2(addr: Address): string {
  const parts = []
  if (addr.city) parts.push(addr.city)
  if (addr.state) parts.push(addr.state)
  if (addr.postalCode) parts.push(addr.postalCode)
  if (addr.country) parts.push(addr.country)
  return parts.join(', ')
}

// 保存卡片
async function saveCard() {
  if (!canSave.value || saving.value) return

  saving.value = true
  try {
    const res = await updateUserCard(cardId.value, {
      billingAddressId: selectedAddressId.value,
      isDefault: isDefault.value
    })

    if (res.code === 0) {
      toast.success(t('payment.editCard.saveSuccess'))
      setTimeout(() => uni.navigateBack(), 1500)
    } else {
      toast.error(res.msg || t('payment.editCard.saveFailed'))
    }
  } catch (e: any) {
    console.error('Save card error:', e)
    toast.error(e.message || t('payment.editCard.saveFailed'))
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
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-border: #E7E5E4;
$color-bg: #F5F5F4;
$color-accent: #FF6B35;
$color-white: #FFFFFF;

.page {
  min-height: 100vh;
  background-color: $color-bg;
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

// 卡片信息展示
.card-display {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 16px;
  padding: 20px;
  background-color: $color-white;
  border-radius: 12px;
}

.card-icon-wrap {
  width: 56px;
  height: 56px;
  background-color: $color-bg;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 28px;
    color: $color-secondary;
  }
}

.card-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-brand {
  font-size: 17px;
  font-weight: 600;
  color: $color-primary;
}

.card-number {
  font-size: 15px;
  font-weight: 700;
  color: $color-secondary;
  font-family: monospace;
  letter-spacing: 1px;
}

.card-expiry {
  font-size: 13px;
  color: $color-muted;
}

// 地址区域
.address-section {
  padding: 0 16px 16px;
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

// 地址卡片
.address-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background-color: $color-white;
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

.empty-text {
  font-size: 15px;
  color: $color-accent;
}

.address-arrow {
  font-size: 18px;
  color: $color-muted;
  margin-left: 12px;
}

// 选项区域
.option-section {
  margin: 16px;
  background-color: $color-white;
  border-radius: 12px;
  overflow: hidden;
}

.option-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
}

.option-text {
  font-size: 15px;
  color: $color-primary;
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
