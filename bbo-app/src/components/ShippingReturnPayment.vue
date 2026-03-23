<template>
  <view class="shipping-return-section">
    <view class="section-title">{{ t('goods.delivery.popupTitle') }}</view>

    <!-- 运送方式 -->
    <view class="info-block">
      <view class="info-header">
        <!-- <text class="bi bi-truck info-icon"></text> -->
        <text class="info-title">{{ t('goods.delivery.shipping') }}</text>
      </view>
      <view class="info-content">
        <view class="info-row">
          <text class="info-label">{{ t('goods.delivery.shippingOption') }}</text>
          <text class="info-value">{{ shippingMethod }}</text>
        </view>
        <view class="info-row">
          <text class="info-label">{{ t('goods.delivery.estimatedDelivery') }}</text>
          <text class="info-value">{{ deliveryEstimate }}</text>
        </view>
        <view class="info-row">
          <text class="info-label">{{ t('goods.delivery.shippingCost') }}</text>
          <text class="info-value" :class="{ free: goods.freeShipping }">
            {{ goods.freeShipping ? t('goods.freeShipping') : formatPrice(goods.shippingFee, goods.currency) }}
          </text>
        </view>
      </view>
    </view>

    <!-- 退货政策 -->
    <view class="info-block">
      <view class="info-header">
        <!-- <text class="bi bi-arrow-return-left info-icon"></text> -->
        <text class="info-title">{{ t('goods.delivery.returns') }}</text>
      </view>
      <view class="info-content">
        <view class="info-row">
          <text class="info-label">{{ t('goods.delivery.returnPolicy') }}</text>
          <text class="info-value">{{ t('goods.delivery.returnDays') }}</text>
        </view>
        <view class="info-row">
          <text class="info-label">{{ t('goods.delivery.returnShipping') }}</text>
          <text class="info-value return-shipping-info">{{ t('goods.delivery.returnShippingInfo') }}</text>
        </view>
      </view>
    </view>

    <!-- 付款方式 -->
    <view class="info-block">
      <view class="info-header">
        <!-- <text class="bi bi-credit-card info-icon"></text> -->
        <text class="info-title">{{ t('goods.delivery.payments') }}</text>
      </view>
      <view class="payment-methods">
        <view class="payment-icon-wrapper">
          <image src="/static/payment/visa.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/mastercard.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/amex.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/discover.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/diners.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/jbc.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/unionpay.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/stripe.png" class="payment-icon" mode="aspectFit" />
        </view>
        <view class="payment-icon-wrapper">
          <image src="/static/payment/paypal.png" class="payment-icon" mode="aspectFit" />
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'

interface Goods {
  id: number
  price: number
  currency: string
  freeShipping: boolean
  shippingFee: number
  expressDelivery?: string
}

const props = defineProps<{
  goods: Goods
}>()

const { t } = useI18n()
const appStore = useAppStore()

function formatPrice(amount: number, currency: string): string {
  return appStore.formatPrice(amount, currency)
}

// 运送方式：使用后端返回的翻译值，降级到 i18n 翻译
const shippingMethod = computed(() => {
  return props.goods.expressDelivery || t('checkout.standardShipping')
})

// 预计送达时间（可根据商品或配置动态计算）
const deliveryEstimate = computed(() => {
  const today = new Date()
  const startDate = new Date(today)
  startDate.setDate(today.getDate() + 5)
  const endDate = new Date(today)
  endDate.setDate(today.getDate() + 10)

  const formatDate = (date: Date) => {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }

  return `${formatDate(startDate)} - ${formatDate(endDate)}`
})
</script>

<style lang="scss" scoped>
// 设计系统变量 - 珊瑚橙主题
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;
$color-border-light: #F5F5F4;
$color-success: #059669;

$font-weight-medium: 500;
$font-weight-semibold: 600;

$spacing-sm: 8px;
$spacing-base: 16px;
$spacing-lg: 20px;

.shipping-return-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.section-title {
  font-size: 18px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: $spacing-base;
}

.info-block {
  margin-bottom: $spacing-lg;
  padding-bottom: $spacing-lg;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }
}

.info-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.info-icon {
  font-size: 18px;
  color: $color-accent;
  margin-right: 8px;
}

.info-title {
  font-size: 15px;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.info-content {
  padding-left: 26px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;

  &:last-child {
    margin-bottom: 0;
  }
}

.info-label {
  font-size: 14px;
  color: $color-muted;
  flex-shrink: 0;
}

.info-value {
  font-size: 14px;
  color: $color-primary;
  text-align: right;
  max-width: 60%;

  &.free {
    color: $color-success;
    font-weight: $font-weight-medium;
  }

  &.return-shipping-info {
    max-width: 65%;
    line-height: 1.4;
  }
}

.payment-methods {
  display: flex;
  gap: 12px;
  padding-left: 26px;
  flex-wrap: wrap;
}

.payment-icon-wrapper {
  width: 48px;
  height: 30px;
  background-color: $color-background;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.payment-icon {
  width: 40px;
  height: 24px;
}
</style>
