<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('cart.title')">
      <template #right>
        <view v-if="cartStore.items.length > 0" class="nav-action-text" @click="toggleEditMode">
          <text>{{ isEditMode ? t('action.done') : t('action.edit') }}</text>
        </view>
      </template>
    </NavBar>

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 需要登录 -->
    <view v-if="!loading && !userStore.isLoggedIn" class="empty-state">
      <text class="bi bi-cart3 empty-icon"></text>
      <text class="empty-title">{{ t('cart.loginRequired') }}</text>
      <button class="btn-primary" @click="goLogin">{{ t('auth.login') }}</button>
    </view>

    <!-- 购物车为空 -->
    <view v-else-if="!loading && cartStore.items.length === 0" class="empty-state">
      <text class="bi bi-cart3 empty-icon"></text>
      <text class="empty-title">{{ t('cart.empty') }}</text>
      <text class="empty-desc">{{ t('cart.emptyDesc') }}</text>
      <button class="btn-primary" @click="goShopping">{{ t('cart.continueShopping') }}</button>
    </view>

    <!-- 购物车列表 -->
    <template v-if="!loading && userStore.isLoggedIn && cartStore.items.length > 0">
      <scroll-view class="cart-content" scroll-y>
        <!-- 购物车项目 -->
        <view
          v-for="item in cartStore.items"
          :key="item.id"
          class="cart-item"
          @click="goToDetail(item.goods.id)"
        >
          <!-- 选择框 -->
          <view class="item-select" @click.stop="toggleSelect(item)">
            <view class="checkbox" :class="{ checked: item.selected }">
              <text v-if="item.selected" class="bi bi-check"></text>
            </view>
          </view>

          <!-- 商品图片 -->
          <view class="item-image-wrapper">
            <image
              class="item-image"
              :src="item.goods.images?.[0] || '/static/placeholder.png'"
              mode="aspectFit"
            />
            <!-- 已售罄遮罩 -->
            <view v-if="item.goods.stock === 0" class="sold-out-overlay">
              <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
            </view>
          </view>

          <!-- 商品信息 -->
          <view class="item-info">
            <text class="item-title">{{ item.goods.title }}</text>
            <view class="item-tags">
              <view v-if="item.goods.freeShipping" class="tag shipping-tag">
                {{ t('cart.freeShipping') }}
              </view>
              <view class="tag condition-tag">
                {{ getConditionText(item.goods.condition) }}
              </view>
            </view>
            <!-- 活动标签 -->
            <view v-if="item.goods.promotion" class="promo-tag">
              <text class="bi bi-lightning-fill"></text>
              <text>{{ item.goods.promotion.discountPercent }}% OFF</text>
            </view>

            <view class="item-bottom">
              <!-- 活动价格优先显示 -->
              <view class="price-group">
                <text class="item-price" :class="{ 'promo-price': item.goods.promotion }">
                  {{ formatPrice(item.goods.promotion?.promotionPrice ?? item.goods.price, item.goods.currency) }}
                </text>
                <text v-if="item.goods.promotion" class="item-original-price">
                  {{ formatPrice(item.goods.price, item.goods.currency) }}
                </text>
              </view>
              <!-- 数量控制 -->
              <view class="quantity-control" @click.stop>
                <view class="qty-btn" @click="decreaseQty(item)">
                  <text class="bi bi-dash"></text>
                </view>
                <text class="qty-value">{{ item.quantity }}</text>
                <view class="qty-btn" @click="increaseQty(item)">
                  <text class="bi bi-plus"></text>
                </view>
              </view>
            </view>
          </view>

          <!-- 删除按钮（编辑模式） -->
          <view v-if="isEditMode" class="item-delete" @click.stop="removeItem(item)">
            <text class="bi bi-archive"></text>
          </view>
        </view>

        <!-- 底部占位 -->
        <view class="bottom-placeholder"></view>
      </scroll-view>

      <!-- 底部结算栏 -->
      <view class="checkout-bar">
        <!-- 上部：全选和价格 -->
        <view class="checkout-top">
          <view class="checkout-left">
            <!-- 全选 -->
            <view class="select-all" @click="toggleSelectAll">
              <view class="checkbox" :class="{ checked: cartStore.isAllSelected }">
                <text v-if="cartStore.isAllSelected" class="bi bi-check"></text>
              </view>
              <text class="select-all-text">{{ t('cart.selectAll') }}</text>
            </view>
          </view>

          <view class="checkout-right">
            <view class="checkout-info">
              <text class="checkout-label">{{ t('cart.total') }}</text>
              <text class="checkout-price">{{ formatConvertedPrice(cartStore.grandTotal) }}</text>
            </view>
          </view>
        </view>

        <!-- 下部：结账按钮单独一行 -->
        <view class="checkout-bottom">
          <button
            class="btn-checkout"
            :disabled="cartStore.selectedCount === 0"
            @click="goCheckout"
          >
            {{ t('cart.checkout') }} ({{ cartStore.selectedCount }})
          </button>
        </view>
      </view>
    </template>

    <!-- 删除确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      :title="t('cart.delete')"
      :content="t('cart.deleteConfirm')"
      icon="bi-cart-x"
      icon-type="warning"
      @update:visible="showDeleteDialog = $event"
      @confirm="confirmDelete"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store/modules/user'
import { useCartStore } from '@/store/modules/cart'
import type { CartItem } from '@/api/cart'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import { formatCurrencyAmount } from '@/utils/currency'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()
const cartStore = useCartStore()
const toast = useToast()

const loading = ref(false)
const isEditMode = ref(false)
const showDeleteDialog = ref(false)
const deleteTargetItem = ref<CartItem | null>(null)
const userCurrency = computed(() => appStore.currency || 'USD')


// 格式化价格（带汇率转换）
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 格式化已转换的金额（不再转换，只格式化）
const formatConvertedPrice = (amount: number) => {
  return formatCurrencyAmount(amount, userCurrency.value, appStore.locale)
}

// 获取商品状态文本
const getConditionText = (condition: number) => {
  const conditions: Record<number, string> = {
    1: t('goods.conditionNew'),
    2: t('goods.conditionLikeNew'),
    3: t('goods.conditionGood'),
    4: t('goods.conditionFair'),
    5: t('goods.conditionPoor'),
  }
  return conditions[condition] || ''
}

// 返回
function goBack() {
  uni.navigateBack({ delta: 1 })
}

// 去登录
function goLogin() {
  uni.navigateTo({ url: '/pages/auth/login' })
}

// 继续购物
function goShopping() {
  uni.switchTab({ url: '/pages/index/index' })
}

// 商品详情
function goToDetail(goodsId: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${goodsId}` })
}

// 切换编辑模式
function toggleEditMode() {
  isEditMode.value = !isEditMode.value
}

// 切换选中
async function toggleSelect(item: CartItem) {
  await cartStore.updateItem(item.id, { selected: !item.selected })
}

// 全选/取消全选
async function toggleSelectAll() {
  await cartStore.selectAll(!cartStore.isAllSelected)
}

// 减少数量
async function decreaseQty(item: CartItem) {
  if (item.quantity <= 1) {
    // 数量为1时，询问是否删除
    deleteTargetItem.value = item
    showDeleteDialog.value = true
    return
  }
  await cartStore.updateItem(item.id, { quantity: item.quantity - 1 })
}

// 增加数量
async function increaseQty(item: CartItem) {
  if (item.quantity >= item.goods.stock) {
    toast.warning(t('cart.maxQuantity'))
    return
  }
  await cartStore.updateItem(item.id, { quantity: item.quantity + 1 })
}

// 删除项目
function removeItem(item: CartItem) {
  deleteTargetItem.value = item
  showDeleteDialog.value = true
}

// 确认删除
async function confirmDelete() {
  const item = deleteTargetItem.value
  if (!item) return

  const success = await cartStore.removeItem(item.id)
  if (success) {
    toast.success(t('cart.deleteSuccess'))
  }
}

// 去结算
function goCheckout() {
  if (cartStore.selectedCount === 0) {
    toast.warning(t('cart.selectAll'))
    return
  }
  // TODO: 跳转到订单确认页
  toast.info(t('common.comingSoon'))
}

// 加载购物车
async function loadCart() {
  if (!userStore.isLoggedIn) return

  loading.value = true
  try {
    await cartStore.fetchCart()
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadCart()
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - 与优惠券页面保持一致
// ==========================================

// 色彩系统
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-accent-light: #FFF4F0;
$color-success: #059669;
$color-success-light: #D1FAE5;
$color-warning: #D97706;
$color-warning-light: #FEF3C7;
$color-danger: #DC2626;
$color-danger-light: #FEE2E2;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;
$color-border-light: #F5F5F4;

// 字体系统
$font-family-base: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;

// 字号系统
$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 22px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 圆角
$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-full: 9999px;

// 阴影
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

// ==========================================
// 基础样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  font-family: $font-family-base;
  display: flex;
  flex-direction: column;
  color: $color-primary;
  -webkit-font-smoothing: antialiased;
}

// 导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 48px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 $spacing-base;
  padding-top: 20px;
  background-color: $color-surface;
  border-bottom: 1px solid $color-border-light;
  z-index: 100;
}

.nav-back, .nav-action {
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: $radius-full;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-border-light;
  }
}

.nav-action {
  width: auto;
  padding: 0 $spacing-sm;
}

.icon-back {
  font-size: 28px;
  color: $color-primary;
}

.nav-title {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.edit-text {
  font-size: $font-size-base;
  color: $color-accent;
  font-weight: $font-weight-medium;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  padding: $spacing-xl;
  padding-top: 48px;
}

.empty-icon {
  font-size: 64px;
  color: $color-border;
  margin-bottom: $spacing-lg;
}

.empty-title {
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.empty-desc {
  font-size: $font-size-base;
  color: $color-muted;
  margin-bottom: $spacing-xl;
  text-align: center;
}

.btn-primary {
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  padding: $spacing-md $spacing-xl;
  border-radius: $radius-full;
  border: none;
  margin: 0;
  line-height: 1.5;

  &::after {
    border: none;
  }

  &:active {
    opacity: 0.9;
  }
}

// 购物车内容
// 高度通过 JavaScript 动态计算：windowHeight - (statusBarHeight + NavBar高度) - 底部栏高度
// 使用 :style 绑定设置，不再使用硬编码的 calc()
.cart-content {
  flex: 1;
  width: auto;
  padding: $spacing-md;
}

// 购物车项目
.cart-item {
  display: flex;
  align-items: center;
  background-color: $color-surface;
  border-radius: $radius-lg;
  padding: $spacing-md;
  margin-bottom: $spacing-md;
  box-shadow: $shadow-sm;
}

.item-select {
  margin-right: $spacing-md;
}

.checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid $color-border;
  border-radius: $radius-sm;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all 0.2s ease;

  &.checked {
    background-color: $color-accent;
    border-color: $color-accent;

    .bi-check {
      color: #fff;
      font-size: 14px;
    }
  }
}

.item-image-wrapper {
  position: relative;
  width: 90px;
  height: 90px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  box-sizing: border-box;
}

.item-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background-color: #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.sold-out-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.sold-out-text {
  color: #fff;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.item-info {
  flex: 1;
  margin-left: $spacing-md;
  min-width: 0;
}

.item-title {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.item-tags {
  display: flex;
  gap: $spacing-xs;
  margin-top: $spacing-sm;
}

.tag {
  font-size: $font-size-xs;
  padding: 2px 6px;
  border-radius: $radius-sm;
}

.shipping-tag {
  background-color: $color-success-light;
  color: $color-success;
}

.condition-tag {
  background-color: $color-border-light;
  color: $color-secondary;
}

.promo-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: $radius-full;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  color: #fff;
  margin-top: $spacing-sm;

  .bi {
    font-size: 10px;
  }
}

.item-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: $spacing-sm;
  gap: $spacing-sm;
}

.price-group {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.item-price {
  font-size: $font-size-base;
  font-weight: $font-weight-bold;
  color: $color-primary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;

  &.promo-price {
    color: #EF4444;
  }
}

.item-original-price {
  font-size: $font-size-xs;
  color: $color-muted;
  text-decoration: line-through;
  white-space: nowrap;
}

// 数量控制
.quantity-control {
  display: flex;
  align-items: center;
  background-color: $color-background;
  border-radius: $radius-full;
  padding: 2px;
  flex-shrink: 0;
}

.qty-btn {
  width: 28px;
  height: 28px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: $radius-full;
  color: $color-secondary;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-border;
  }

  .bi {
    font-size: 14px;
  }
}

.qty-value {
  min-width: 32px;
  text-align: center;
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

// 删除按钮
.item-delete {
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: $color-danger;
  margin-left: $spacing-sm;

  .bi {
    font-size: 18px;
  }
}

// 底部占位
.bottom-placeholder {
  height: 20px;
}

// 结算栏
.checkout-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  flex-direction: column;
  padding: $spacing-md $spacing-base;
  padding-bottom: calc($spacing-md + env(safe-area-inset-bottom, 0px));
  background-color: $color-surface;
  border-top: 1px solid $color-border-light;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
}

.checkout-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-md;
}

.checkout-left {
  display: flex;
  align-items: center;
}

.select-all {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.select-all-text {
  font-size: $font-size-base;
  color: $color-secondary;
}

.checkout-right {
  display: flex;
  align-items: center;
}

.checkout-info {
  display: flex;
  align-items: baseline;
  gap: $spacing-xs;
}

.checkout-label {
  font-size: $font-size-sm;
  color: $color-muted;
}

.checkout-price {
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-accent;
}

.checkout-bottom {
  width: 100%;
}

.btn-checkout {
  width: 100%;
  height: 50px;
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  border-radius: $radius-lg;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  // 禁用 uni-app button 默认样式
  margin: 0;
  padding: 0;
  line-height: 50px;

  &::after {
    border: none;
  }

  &:active {
    opacity: 0.9;
    transform: scale(0.99);
  }

  &[disabled] {
    background-color: $color-border;
    color: $color-muted;
  }
}
</style>
