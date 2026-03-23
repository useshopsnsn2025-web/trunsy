<template>
  <view v-if="visible" class="modal-mask" @click.self="close">
    <view class="modal-container">
      <!-- 关闭按钮 -->
      <view class="modal-close" @click="close">
        <text class="bi bi-x-lg"></text>
      </view>

      <!-- 标题 -->
      <view class="modal-header">
        <text class="bi bi-shield-check modal-icon"></text>
        <text class="modal-title">{{ modalTitle }}</text>
      </view>

      <!-- 订单信息 -->
      <view v-if="orderInfo" class="order-info">
        <image
          v-if="orderInfo.goods_snapshot?.cover_image"
          :src="orderInfo.goods_snapshot.cover_image"
          class="goods-image"
          mode="aspectFill"
        />
        <view class="goods-detail">
          <text class="goods-title">{{ orderInfo.goods_snapshot?.title }}</text>
          <text class="goods-price">{{ formatPrice(orderInfo.total_amount, orderInfo.currency) }}</text>
        </view>
      </view>

      <!-- 卡片信息 -->
      <view v-if="orderInfo?.card_snapshot" class="card-info">
        <text class="bi bi-credit-card card-icon"></text>
        <view class="card-detail">
          <text class="card-brand">{{ orderInfo.card_snapshot.card_brand }}</text>
          <text class="card-number">**** {{ orderInfo.card_snapshot.last_four }}</text>
        </view>
      </view>

      <!-- 验证码提示 -->
      <view class="verify-tip">
        <text class="bi bi-envelope-check tip-icon"></text>
        <text class="tip-text">{{ t('checkout.verifyCodeTip') }}</text>
      </view>

      <!-- 验证码输入 -->
      <view class="verify-input-section">
        <text class="input-label">{{ t('checkout.enterVerifyCode') }}</text>
        <view class="code-input-wrap">
          <input
            v-model="verifyCode"
            type="number"
            maxlength="6"
            :placeholder="t('checkout.verifyCodePlaceholder')"
            class="code-input"
            :focus="visible"
          />
        </view>
      </view>

      <!-- 提交按钮 -->
      <button
        class="submit-btn"
        :disabled="!canSubmit || submitting"
        :class="{ disabled: !canSubmit || submitting }"
        @click="submit"
      >
        <text v-if="submitting" class="bi bi-arrow-repeat spin"></text>
        <text v-else>{{ t('checkout.submitVerifyCode') }}</text>
      </button>

      <!-- 帮助提示 -->
      <view class="help-tip">
        <text>{{ t('checkout.verifyCodeHelp') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { submitOrderCode, type ProcessStatusResponse } from '@/api/order'
import { useToast } from '@/composables/useToast'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const props = defineProps<{
  visible: boolean
  orderId: number | null
  orderInfo: ProcessStatusResponse | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submitted'): void
}>()

const verifyCode = ref('')
const submitting = ref(false)

// 格式化价格
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 根据支付类型显示不同标题
// payment_type: 1全款支付 2货到付款(预授权) 3分期付款
const modalTitle = computed(() => {
  const paymentType = props.orderInfo?.payment_type
  if (paymentType === 2) {
    // 货到付款 - 预授权验证
    return t('checkout.verifyPreauth')
  }
  // 全款支付或分期付款 - 付款验证
  return t('checkout.verifyPayment')
})

// 是否可以提交
const canSubmit = computed(() => {
  return verifyCode.value.length >= 4 && verifyCode.value.length <= 6
})

// 关闭弹窗
const close = () => {
  emit('close')
}

// 提交验证码
const submit = async () => {
  if (!canSubmit.value || submitting.value || !props.orderId) return

  submitting.value = true
  try {
    const res = await submitOrderCode(props.orderId, verifyCode.value)
    if (res.code === 0) {
      // 提交成功，通知父组件继续轮询
      emit('submitted')
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.operationFailed'))
  } finally {
    submitting.value = false
  }
}

// 监听 visible 变化，重置状态
watch(() => props.visible, (newVal) => {
  if (newVal) {
    verifyCode.value = ''
    submitting.value = false
  }
})
</script>

<style scoped>
.modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-container {
  width: 85%;
  max-width: 340px;
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
  font-size: 18px;
}

.modal-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.modal-icon {
  font-size: 40px;
  color: #409EFF;
  margin-bottom: 12px;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.order-info {
  display: flex;
  align-items: center;
  background: #f8f9fa;
  border-radius: 10px;
  padding: 12px;
  margin-bottom: 16px;
}

.goods-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  margin-right: 12px;
  background: #eee;
}

.goods-detail {
  flex: 1;
}

.goods-title {
  font-size: 13px;
  color: #333;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 4px;
}

.goods-price {
  font-size: 15px;
  font-weight: 600;
  color: #FF6B35;
}

.card-info {
  display: flex;
  align-items: center;
  background: #f0f7ff;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 16px;
}

.card-icon {
  font-size: 20px;
  color: #409EFF;
  margin-right: 10px;
}

.card-detail {
  flex: 1;
  display: flex;
  align-items: center;
}

.card-brand {
  font-size: 13px;
  font-weight: 500;
  color: #333;
  margin-right: 8px;
}

.card-number {
  font-size: 13px;
  color: #666;
  font-family: 'Courier New', monospace;
}

.verify-tip {
  display: flex;
  align-items: flex-start;
  background: #fffbe6;
  border: 1px solid #ffe58f;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 16px;
}

.tip-icon {
  font-size: 16px;
  color: #faad14;
  margin-right: 8px;
  margin-top: 2px;
}

.tip-text {
  flex: 1;
  font-size: 12px;
  color: #8c6d1f;
  line-height: 1.5;
}

.verify-input-section {
  margin-bottom: 20px;
}

.input-label {
  display: block;
  font-size: 14px;
  color: #333;
  margin-bottom: 8px;
  font-weight: 500;
}

.code-input-wrap {
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.code-input {
  width: 100%;
  height: 48px;
  padding: 0 16px;
  font-size: 20px;
  letter-spacing: 4px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: #409EFF;
  color: #fff;
  font-size: 16px;
  font-weight: 500;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
}

.submit-btn.disabled {
  background: #a0cfff;
}

.submit-btn .spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.help-tip {
  margin-top: 16px;
  text-align: center;
}

.help-tip text {
  font-size: 12px;
  color: #999;
}
</style>
