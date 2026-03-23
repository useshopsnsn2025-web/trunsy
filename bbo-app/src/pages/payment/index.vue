<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('payment.title')" />

    <scroll-view scroll-y class="content">
      <!-- 添加结账方式提示卡片 -->
      <view class="tip-card">
        <view class="tip-icon">
          <text class="bi bi-info-circle-fill"></text>
        </view>
        <view class="tip-content">
          <text class="tip-title">{{ t('payment.addCheckoutMethod') }}</text>
          <text class="tip-desc">{{ t('payment.addCheckoutMethodDesc') }}</text>
          <text class="tip-link" @click="goAddCard">{{ t('payment.addCreditDebitCard') }}</text>
        </view>
      </view>

      <!-- 付款卡和账户区域 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('payment.cardsAndAccounts') }}</text>
          <view class="section-action" @click="goAddCard">
            <text class="bi bi-plus"></text>
          </view>
        </view>

        <!-- 卡片列表 -->
        <view v-if="cards.length > 0" class="card-list">
          <view
            v-for="card in cards"
            :key="card.id"
            class="card-item"
            :class="{ 'card-item-inactive': card.status !== 'active' }"
            @click="openCardActions(card)"
          >
            <view class="card-icon-wrap" :class="{ 'card-icon-inactive': card.status !== 'active' }">
              <text class="bi" :class="card.brandIcon || 'bi-credit-card-2-front'"></text>
            </view>
            <view class="card-info">
              <view class="card-name-row">
                <text class="card-name" :class="{ 'card-name-inactive': card.status !== 'active' }">{{ card.brandName }} •••• {{ card.lastFour }}</text>
              </view>
              <view class="card-status-row">
                <text v-if="card.isDefault && card.status === 'active'" class="card-default">{{ t('payment.defaultCard') }}</text>
                <view v-if="card.status === 'expired'" class="card-status card-status-expired">
                  <view class="status-dot"></view>
                  <text class="card-status-text">{{ t('payment.cardStatus.expired') }}</text>
                </view>
                <view v-else-if="card.status === 'disabled'" class="card-status card-status-disabled">
                  <view class="status-dot"></view>
                  <text class="card-status-text">{{ t('payment.cardStatus.disabled') }}</text>
                </view>
                <view v-else-if="card.status === 'rejected'" class="card-status card-status-rejected">
                  <view class="status-dot"></view>
                  <text class="card-status-text">{{ t('payment.cardStatus.rejected') }}</text>
                </view>
                <view v-else-if="card.status === 'pending'" class="card-status card-status-pending">
                  <view class="status-dot"></view>
                  <text class="card-status-text">{{ t('payment.cardStatus.pending') }}</text>
                </view>
              </view>
            </view>
            <text class="bi bi-chevron-right card-arrow"></text>
          </view>
        </view>

        <!-- 空状态 - 添加付款卡按钮 -->
        <view class="add-card-btn" @click="goAddCard">
          <text class="add-card-text">{{ t('payment.addCardsAndAccounts') }}</text>
        </view>
      </view>

      <!-- 管理区域 -->
      <view class="section">
        <text class="section-label">{{ t('payment.manage') }}</text>

        <view class="menu-list">
          <view class="menu-item" @click="goPurchasePreferences">
            <view class="menu-icon-wrap">
              <text class="bi bi-sliders"></text>
            </view>
            <view class="menu-content">
              <text class="menu-title">{{ t('payment.purchasePreferences') }}</text>
              <text class="menu-desc">{{ t('payment.purchasePreferencesDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right menu-arrow"></text>
          </view>
        </view>
      </view>
    </scroll-view>

    <!-- 自定义底部操作菜单 -->
    <view v-if="showActionSheet" class="action-sheet-overlay" @click="closeActionSheet">
      <view class="action-sheet" :class="{ 'action-sheet-visible': actionSheetVisible }" @click.stop>
        <!-- 卡片信息头部 -->
        <view class="action-sheet-header">
          <view class="action-card-preview">
            <view class="action-card-icon">
              <text class="bi" :class="selectedCard?.brandIcon || 'bi-credit-card-2-front'"></text>
            </view>
            <view class="action-card-info">
              <text class="action-card-name">{{ selectedCard?.brandName }} •••• {{ selectedCard?.lastFour }}</text>
              <text v-if="selectedCard?.isDefault" class="action-card-badge">{{ t('payment.defaultCard') }}</text>
            </view>
          </view>
          <view class="action-sheet-close" @click="closeActionSheet">
            <text class="bi bi-x-lg"></text>
          </view>
        </view>

        <!-- 操作选项列表 -->
        <view class="action-sheet-options">
          <view class="action-option" @click="handleAction('edit')">
            <view class="action-option-icon">
              <text class="bi bi-pencil"></text>
            </view>
            <view class="action-option-content">
              <text class="action-option-title">{{ t('payment.editCardAction') }}</text>
              <text class="action-option-desc">{{ t('payment.actionSheet.editDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right action-option-arrow"></text>
          </view>

          <view v-if="!selectedCard?.isDefault" class="action-option" @click="handleAction('default')">
            <view class="action-option-icon">
              <text class="bi bi-star"></text>
            </view>
            <view class="action-option-content">
              <text class="action-option-title">{{ t('payment.setAsDefault') }}</text>
              <text class="action-option-desc">{{ t('payment.actionSheet.defaultDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right action-option-arrow"></text>
          </view>

          <view class="action-option action-option-danger" @click="handleAction('delete')">
            <view class="action-option-icon">
              <text class="bi bi-archive"></text>
            </view>
            <view class="action-option-content">
              <text class="action-option-title">{{ t('payment.deleteCard') }}</text>
              <text class="action-option-desc">{{ t('payment.actionSheet.deleteDesc') }}</text>
            </view>
            <text class="bi bi-chevron-right action-option-arrow"></text>
          </view>
        </view>

        <!-- 取消按钮 -->
        <view class="action-sheet-cancel" @click="closeActionSheet">
          <text class="action-cancel-text">{{ t('common.cancel') }}</text>
        </view>
      </view>
    </view>

    <!-- 删除卡片确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteCardDialog"
      :title="t('payment.deleteCardTitle')"
      :content="deleteTargetCard ? formatDeleteCardConfirmText(`${deleteTargetCard.brandName} •••• ${deleteTargetCard.lastFour}`) : ''"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteCardDialog = $event"
      @confirm="confirmDeleteCardAction"
    />
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import {
  getUserCards,
  getCardBrandName,
  CARD_BRAND_ICONS,
  setDefaultCard,
  deleteUserCard,
  type UserCard,
  type CardStatus
} from '@/api/userCard'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const toast = useToast()

// 辅助函数：手动替换删除卡片确认文本中的占位符
// UniApp APP 端 vue-i18n 的 t('key', { param }) 插值不生效，需要手动替换
function formatDeleteCardConfirmText(cardInfo: string): string {
  const template = t('payment.deleteCardConfirm')
  return template.replace('[CARD]', cardInfo)
}


interface PaymentCard {
  id: number
  brandName: string
  brandIcon?: string
  lastFour: string
  isDefault: boolean
  status: CardStatus
  statusReason?: string
}

const loading = ref(false)
const cards = ref<PaymentCard[]>([])

// 操作菜单状态
const showActionSheet = ref(false)
const actionSheetVisible = ref(false)
const selectedCard = ref<PaymentCard | null>(null)

// 删除卡片弹窗状态
const showDeleteCardDialog = ref(false)
const deleteTargetCard = ref<PaymentCard | null>(null)

onShow(() => {
  loadCards()
})

async function loadCards() {
  loading.value = true
  try {
    const res = await getUserCards()
    if (res.code === 0 && res.data) {
      cards.value = res.data.map((card: UserCard) => ({
        id: card.id,
        brandName: getCardBrandName(card.cardBrand || card.cardType),
        brandIcon: CARD_BRAND_ICONS[(card.cardBrand || card.cardType || '').toLowerCase()],
        lastFour: card.lastFour,
        isDefault: card.isDefault,
        status: card.status || 'active',
        statusReason: card.statusReason,
      }))
    } else {
      cards.value = []
    }
  } catch (e) {
    console.error('Failed to load cards:', e)
    cards.value = []
  } finally {
    loading.value = false
  }
}

function goAddCard() {
  uni.navigateTo({ url: '/pages/payment/add-card' })
}

// 打开操作菜单
function openCardActions(card: PaymentCard) {
  selectedCard.value = card
  showActionSheet.value = true
  // 延迟触发动画
  setTimeout(() => {
    actionSheetVisible.value = true
  }, 10)
}

// 关闭操作菜单
function closeActionSheet() {
  actionSheetVisible.value = false
  setTimeout(() => {
    showActionSheet.value = false
    selectedCard.value = null
  }, 300)
}

// 处理操作选项点击
function handleAction(action: 'edit' | 'default' | 'delete') {
  const card = selectedCard.value
  if (!card) return

  closeActionSheet()

  setTimeout(() => {
    switch (action) {
      case 'edit':
        goEditCard(card)
        break
      case 'default':
        handleSetDefault(card)
        break
      case 'delete':
        confirmDeleteCard(card)
        break
    }
  }, 300)
}

// 跳转到编辑卡片页面
function goEditCard(card: PaymentCard) {
  uni.navigateTo({ url: `/pages/payment/edit-card?id=${card.id}` })
}

// 设为默认卡片
async function handleSetDefault(card: PaymentCard) {
  uni.showLoading({ title: t('common.loading') })
  try {
    const res = await setDefaultCard(card.id)
    if (res.code === 0) {
      toast.success(t('payment.setDefaultSuccess'))
      loadCards()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

// 确认删除卡片
function confirmDeleteCard(card: PaymentCard) {
  deleteTargetCard.value = card
  showDeleteCardDialog.value = true
}

// 确认删除卡片回调
function confirmDeleteCardAction() {
  const card = deleteTargetCard.value
  if (card) {
    handleDeleteCard(card)
  }
}

// 删除卡片
async function handleDeleteCard(card: PaymentCard) {
  uni.showLoading({ title: t('common.loading') })
  try {
    const res = await deleteUserCard(card.id)
    if (res.code === 0) {
      toast.success(t('payment.deleteCardSuccess'))
      loadCards()
    } else {
      toast.error(res.msg || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    uni.hideLoading()
  }
}

function goPurchasePreferences() {
  toast.info(t('common.comingSoon'))
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

.page {
  min-height: 100vh;
  background-color: $color-bg;
  display: flex;
  flex-direction: column;
}

.content {
  flex: 1;
}

// 内容区域高度通过 JS 动态计算，适配不同设备状态栏
// 使用 :style="{ height: contentHeight }" 绑定

// 提示卡片
.tip-card {
  display: flex;
  gap: 12px;
  margin: 16px;
  padding: 16px;
  background-color: $color-white;
  border-radius: 12px;
}

.tip-icon {
  flex-shrink: 0;

  .bi {
    font-size: 20px;
    color: $color-accent;
  }
}

.tip-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tip-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.tip-desc {
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.4;
}

.tip-link {
  font-size: 14px;
  color: $color-accent;
  margin-top: 4px;
}

// 区域样式
.section {
  margin: 16px;
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

.section-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 22px;
    color: $color-accent;
  }
}

.section-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: $color-muted;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}

// 卡片列表
.card-list {
  background-color: $color-white;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 12px;
}

.card-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }
}

.card-icon-wrap {
  width: 40px;
  height: 40px;
  background-color: $color-bg;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;

  .bi {
    font-size: 20px;
    color: $color-muted;
  }
}


.card-info {
  flex: 1;
  min-width: 0;
}

.card-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-name {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;

  &.card-name-inactive {
    color: $color-muted;
  }
}

.card-status-row {
  display: flex;
  align-items: center;
  margin-top: 4px;
}

.card-default {
  font-size: 12px;
  color: $color-muted;
}

// 卡片状态标识 - 精致胶囊设计
.card-status {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px 3px 8px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.2px;
  border: 1px solid transparent;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.card-status-text {
  font-size: 11px;
  line-height: 1;
}

// 已过期 - 暖橙色调（与 accent 色协调）
.card-status-expired {
  background-color: rgba(251, 146, 60, 0.1);
  border-color: rgba(251, 146, 60, 0.2);
  color: #C2410C;

  .status-dot {
    background-color: #FB923C;
  }
}

// 已停用 - 中性石色
.card-status-disabled {
  background-color: rgba(120, 113, 108, 0.08);
  border-color: rgba(120, 113, 108, 0.15);
  color: $color-muted;

  .status-dot {
    background-color: #A8A29E;
  }
}

// 已被拒 - 柔和红色
.card-status-rejected {
  background-color: rgba(239, 68, 68, 0.08);
  border-color: rgba(239, 68, 68, 0.15);
  color: #B91C1C;

  .status-dot {
    background-color: #EF4444;
  }
}

// 审核中 - 柔和蓝色
.card-status-pending {
  background-color: rgba(59, 130, 246, 0.08);
  border-color: rgba(59, 130, 246, 0.15);
  color: #1D4ED8;

  .status-dot {
    background-color: #3B82F6;
    animation: pulse-dot 1.5s ease-in-out infinite;
  }
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(0.85);
  }
}

// 非活跃卡片样式
.card-item-inactive {
  opacity: 0.85;
}

.card-icon-inactive {
  background-color: $color-border !important;

  .bi {
    color: $color-muted !important;
  }
}

.card-arrow {
  font-size: 18px;
  color: $color-muted;
}

// 添加卡片按钮
.add-card-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 52px;
  background-color: $color-white;
  border: 1px dashed $color-accent;
  border-radius: 12px;
}

.add-card-text {
  font-size: 15px;
  font-weight: 500;
  color: $color-accent;
}

// 菜单列表
.menu-list {
  background-color: $color-white;
  border-radius: 12px;
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }
}

.menu-icon-wrap {
  width: 40px;
  height: 40px;
  background-color: $color-bg;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;

  .bi {
    font-size: 18px;
    color: $color-secondary;
  }
}

.menu-content {
  flex: 1;
  min-width: 0;
}

.menu-title {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
  display: block;
}

.menu-desc {
  font-size: 13px;
  color: $color-muted;
  margin-top: 2px;
  display: block;
}

.menu-arrow {
  font-size: 18px;
  color: $color-muted;
}

// ==========================================
// 自定义底部操作菜单样式
// ==========================================

// 遮罩层
.action-sheet-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

// 操作菜单主体
.action-sheet {
  width: 100%;
  background-color: $color-bg;
  border-radius: 20px 20px 0 0;
  padding-bottom: env(safe-area-inset-bottom);
  transform: translateY(100%);
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);

  &.action-sheet-visible {
    transform: translateY(0);
  }
}

// 操作菜单头部
.action-sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 20px 16px;
  background-color: $color-white;
  border-radius: 20px 20px 0 0;
  border-bottom: 1px solid $color-border;
}

.action-card-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.action-card-icon {
  width: 48px;
  height: 48px;
  background-color: $color-primary;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 22px;
    color: $color-white;
  }
}

.action-card-info {
  flex: 1;
}

.action-card-name {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
  display: block;
}

.action-card-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 500;
  color: $color-secondary;
  background-color: $color-bg;
  padding: 2px 8px;
  border-radius: 10px;
  margin-top: 4px;
}

.action-sheet-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: $color-bg;
  cursor: pointer;

  .bi {
    font-size: 16px;
    color: $color-muted;
  }
}

// 操作选项列表
.action-sheet-options {
  padding: 12px 16px 4px;
}

.action-option {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  background-color: $color-white;
  border-radius: 14px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-bg;
  }

  &:last-child {
    margin-bottom: 0;
  }
}

.action-option-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background-color: $color-bg;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 14px;

  .bi {
    font-size: 18px;
    color: $color-secondary;
  }
}

.action-option-content {
  flex: 1;
  min-width: 0;
}

.action-option-title {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
  display: block;
}

.action-option-desc {
  font-size: 13px;
  color: $color-muted;
  margin-top: 2px;
  display: block;
}

.action-option-arrow {
  font-size: 16px;
  color: $color-border;
  margin-left: 8px;
}

// 危险操作样式
.action-option-danger {
  .action-option-icon {
    background-color: #FEF2F2;

    .bi {
      color: #B91C1C;
    }
  }

  .action-option-title {
    color: #B91C1C;
  }

  .action-option-desc {
    color: #DC2626;
  }
}

// 取消按钮
.action-sheet-cancel {
  margin: 8px 16px 16px;
  padding: 16px;
  background-color: $color-white;
  border-radius: 14px;
  text-align: center;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-bg;
  }
}

.action-cancel-text {
  font-size: 16px;
  font-weight: 500;
  color: $color-secondary;
}
</style>
