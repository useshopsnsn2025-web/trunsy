<template>
  <view class="page">
    <NavBar :title="isReportMode ? t('report.title') : t('support.createTicket')" />

    <scroll-view class="content" scroll-y>
      <!-- ========== 举报模式 ========== -->
      <template v-if="isReportMode">
        <!-- 你想檢舉什麼？ -->
        <view class="section">
          <view class="section-header">
            <text class="section-title">{{ t('report.whatToReport') }}</text>
          </view>
          <view class="select-card" @click="showReportTypePicker = true">
            <text class="select-value">{{ reportTypeLabel }}</text>
            <text class="bi bi-chevron-down select-arrow"></text>
          </view>
        </view>

        <!-- ========== 举报商品时显示的字段 ========== -->
        <template v-if="reportType === 'item'">
          <!-- 會員帳號（選填） -->
          <view class="section">
            <view class="section-header">
              <text class="section-title">{{ t('report.memberAccount') }}</text>
              <text class="section-optional">{{ t('support.optional') }}</text>
            </view>
            <view class="input-wrapper">
              <input
                v-model="reportMemberAccount"
                class="form-input"
                :placeholder="t('report.memberAccountPlaceholder')"
              />
            </view>
          </view>

          <!-- 物品編號 -->
          <view class="section">
            <view class="section-header">
              <text class="section-title">{{ t('report.itemNumber') }}</text>
            </view>
            <view class="input-wrapper">
              <input
                v-model="reportItemNumber"
                class="form-input"
                :placeholder="t('report.itemNumberPlaceholder')"
              />
            </view>
          </view>

          <!-- 其他物品編號 -->
          <view class="section">
            <view class="section-header">
              <text class="section-title">{{ t('report.otherItemNumbers') }}</text>
            </view>
            <view class="textarea-wrapper">
              <textarea
                v-model="reportOtherItems"
                class="description-input short"
                :placeholder="t('report.otherItemNumbersPlaceholder')"
                :maxlength="500"
              ></textarea>
            </view>
            <text class="field-hint">{{ t('report.otherItemNumbersHint') }}</text>
          </view>

          <!-- 檢舉原因 -->
          <view class="section">
            <view class="section-header">
              <text class="section-title">{{ t('report.reason') }}</text>
            </view>
            <view class="select-card" @click="showReportReasonPicker = true">
              <text class="select-value" :class="{ placeholder: !reportReason }">
                {{ reportReasonLabel || t('report.selectReason') }}
              </text>
              <text class="bi bi-chevron-down select-arrow"></text>
            </view>
          </view>
        </template>

        <!-- ========== 举报会员时显示的字段 ========== -->
        <template v-else-if="reportType === 'member'">
          <!-- 會員或商店名稱 -->
          <view class="section">
            <view class="section-header">
              <text class="section-title">{{ t('report.memberStoreName') }}</text>
            </view>
            <view class="input-with-button">
              <input
                v-model="reportMemberName"
                class="form-input flex-input"
                :placeholder="t('report.memberStoreNamePlaceholder')"
                :disabled="memberVerified"
              />
              <view class="verify-btn" :class="{ verified: memberVerified }" @click="memberVerified ? clearVerifiedMember() : verifyMember()">
                <text v-if="memberVerifying" class="bi bi-arrow-repeat spin"></text>
                <text v-else-if="memberVerified" class="bi bi-x-lg"></text>
                <text v-else>{{ t('report.verify') }}</text>
              </view>
            </view>
            <!-- 验证成功后显示用户信息 -->
            <view v-if="memberVerified && verifiedMemberInfo" class="verified-member-card">
              <image
                v-if="verifiedMemberInfo.avatar"
                class="member-avatar"
                :src="verifiedMemberInfo.avatar"
                mode="aspectFill"
              />
              <view v-else class="member-avatar-placeholder">
                <text class="bi bi-person-fill"></text>
              </view>
              <view class="member-info">
                <text class="member-name">{{ verifiedMemberInfo.nickname }}</text>
                <view class="member-tags">
                  <text v-if="verifiedMemberInfo.isSeller" class="member-tag seller">{{ t('report.seller') }}</text>
                </view>
              </view>
              <text class="bi bi-check-circle-fill verified-icon"></text>
            </view>
            <text v-else-if="memberVerified" class="field-success">{{ t('report.memberVerified') }}</text>
            <text v-else-if="memberVerifyFailed" class="field-error">{{ t('report.memberNotFound') }}</text>
          </view>
        </template>

        <!-- 詳細資料（選填）- 两种模式都显示 -->
        <view class="section">
          <view class="section-header">
            <text class="section-title">{{ t('report.details') }}</text>
            <text class="section-optional">{{ t('support.optional') }}</text>
          </view>
          <view class="textarea-wrapper">
            <textarea
              v-model="description"
              class="description-input"
              :placeholder="t('report.detailsPlaceholder')"
              :maxlength="500"
            ></textarea>
            <text class="char-count">{{ description.length }}/500</text>
          </view>
        </view>

        <!-- 確認勾選 -->
        <view class="section">
          <view class="checkbox-wrapper" @click="reportConfirmed = !reportConfirmed">
            <view class="checkbox" :class="{ checked: reportConfirmed }">
              <text v-if="reportConfirmed" class="bi bi-check"></text>
            </view>
            <text class="checkbox-label">{{ t('report.confirmAccuracy') }}</text>
          </view>
        </view>
      </template>

      <!-- ========== 普通工单模式 ========== -->
      <template v-else>
        <!-- 问题类型 -->
        <view class="section">
          <view class="section-header">
            <text class="section-title">{{ t('support.questionType') }}</text>
          </view>
          <view class="type-card" @click="showTypePicker = true">
            <view class="type-info">
              <view class="type-icon" :class="selectedCategory">
                <text class="bi" :class="categoryIcon"></text>
              </view>
              <view class="type-text">
                <text class="type-main">{{ categoryName }}</text>
                <text v-if="subCategoryName" class="type-sub">{{ subCategoryName }}</text>
              </view>
            </view>
            <text class="bi bi-chevron-down type-arrow"></text>
          </view>
        </view>

        <!-- 关联订单 -->
        <view v-if="needOrderRelation" class="section">
          <view class="section-header">
            <text class="section-title">{{ t('support.relatedOrder') }}</text>
            <text class="section-optional">{{ t('support.optional') }}</text>
          </view>
          <view v-if="selectedOrder" class="order-card" @click="showOrderPicker = true">
            <view class="order-info">
              <text class="order-no">{{ selectedOrder.order_no }}</text>
              <text class="order-title">{{ selectedOrder.goods?.title || '' }}</text>
              <text class="order-price">{{ formatPrice(selectedOrder.total_amount || 0, selectedOrder.currency || 'USD') }}</text>
            </view>
            <view class="order-change" @click.stop="clearOrder">
              <text class="bi bi-x-circle"></text>
            </view>
          </view>
          <view v-else class="select-order" @click="showOrderPicker = true">
            <text class="bi bi-box-seam"></text>
            <text class="select-text">{{ t('support.selectOrder') }}</text>
            <text class="bi bi-chevron-right"></text>
          </view>
        </view>

        <!-- 问题描述 -->
        <view class="section">
          <view class="section-header">
            <text class="section-title">{{ t('support.description') }}</text>
            <text class="section-required">*</text>
          </view>
          <view class="textarea-wrapper">
            <textarea
              v-model="description"
              class="description-input"
              :placeholder="t('support.descriptionPlaceholder')"
              :maxlength="500"
            ></textarea>
            <text class="char-count">{{ description.length }}/500</text>
          </view>
        </view>

        <!-- 上传图片 -->
        <view class="section">
          <view class="section-header">
            <text class="section-title">{{ t('support.uploadImages') }}</text>
            <text class="section-optional">{{ t('support.maxImages') }}</text>
          </view>
          <view class="image-grid">
            <view
              v-for="(img, index) in images"
              :key="index"
              class="image-item"
            >
              <image :src="img" mode="aspectFill" class="preview-image" @click="previewImage(index)" />
              <view class="image-delete" @click="removeImage(index)">
                <text class="bi bi-x"></text>
              </view>
            </view>
            <view v-if="images.length < 9" class="image-add" @click="chooseImage">
              <text class="bi bi-plus"></text>
              <text class="add-text">{{ t('support.addImage') }}</text>
            </view>
          </view>
        </view>
      </template>

      <view class="safe-area-bottom"></view>
    </scroll-view>

    <!-- 底部提交按钮 -->
    <view class="submit-bar">
      <view
        class="submit-btn"
        :class="{ disabled: !canSubmit || submitting }"
        @click="handleSubmit"
      >
        {{ submitting ? t('common.submitting') : t('support.submit') }}
      </view>
    </view>

    <!-- 类型选择弹窗 -->
    <view v-if="showTypePicker" class="picker-mask" @click="showTypePicker = false">
      <view class="picker-popup" @click.stop>
        <view class="picker-header">
          <text class="picker-title">{{ t('support.selectCategory') }}</text>
          <view class="picker-close" @click="showTypePicker = false">
            <text class="bi bi-x"></text>
          </view>
        </view>
        <scroll-view class="picker-content" scroll-y>
          <view
            v-for="cat in allCategories"
            :key="cat.code"
            class="picker-item"
            :class="{ active: selectedCategory === cat.code }"
            @click="selectCategory(cat.code)"
          >
            <view class="picker-icon" :class="cat.code">
              <text class="bi" :class="cat.icon"></text>
            </view>
            <text class="picker-label">{{ cat.name }}</text>
            <text v-if="selectedCategory === cat.code" class="bi bi-check picker-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 图片来源选择弹窗 -->
    <view v-if="showImageSourcePicker" class="picker-mask" @click="showImageSourcePicker = false">
      <view class="action-sheet" @click.stop>
        <view class="action-sheet-item" @click="selectImageSource('camera')">
          <text class="bi bi-camera"></text>
          <text>{{ t('support.takePhoto') }}</text>
        </view>
        <view class="action-sheet-item" @click="selectImageSource('album')">
          <text class="bi bi-images"></text>
          <text>{{ t('support.chooseFromAlbum') }}</text>
        </view>
        <view class="action-sheet-cancel" @click="showImageSourcePicker = false">
          <text>{{ t('common.cancel') }}</text>
        </view>
      </view>
    </view>

    <!-- 举报类型选择弹窗 -->
    <view v-if="showReportTypePicker" class="picker-mask" @click="showReportTypePicker = false">
      <view class="picker-popup" @click.stop>
        <view class="picker-header">
          <text class="picker-title">{{ t('report.whatToReport') }}</text>
          <view class="picker-close" @click="showReportTypePicker = false">
            <text class="bi bi-x"></text>
          </view>
        </view>
        <scroll-view class="picker-content" scroll-y>
          <view
            v-for="opt in reportTypeOptions"
            :key="opt.code"
            class="picker-item simple"
            :class="{ active: reportType === opt.code }"
            @click="selectReportType(opt.code)"
          >
            <text class="picker-label">{{ opt.name }}</text>
            <text v-if="reportType === opt.code" class="bi bi-check picker-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 举报原因选择弹窗 -->
    <view v-if="showReportReasonPicker" class="picker-mask" @click="showReportReasonPicker = false">
      <view class="picker-popup" @click.stop>
        <view class="picker-header">
          <text class="picker-title">{{ t('report.reason') }}</text>
          <view class="picker-close" @click="showReportReasonPicker = false">
            <text class="bi bi-x"></text>
          </view>
        </view>
        <scroll-view class="picker-content" scroll-y>
          <view
            v-for="opt in reportReasonOptions"
            :key="opt.code"
            class="picker-item simple"
            :class="{ active: reportReason === opt.code }"
            @click="selectReportReason(opt.code)"
          >
            <text class="picker-label">{{ opt.name }}</text>
            <text v-if="reportReason === opt.code" class="bi bi-check picker-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 订单选择弹窗 -->
    <view v-if="showOrderPicker" class="picker-mask" @click="showOrderPicker = false">
      <view class="picker-popup order-picker" @click.stop>
        <view class="picker-header">
          <text class="picker-title">{{ t('support.selectOrder') }}</text>
          <view class="picker-close" @click="showOrderPicker = false">
            <text class="bi bi-x"></text>
          </view>
        </view>
        <scroll-view class="picker-content" scroll-y>
          <view v-if="ordersLoading" class="orders-loading">
            <text>{{ t('common.loading') }}</text>
          </view>
          <view v-else-if="orders.length === 0" class="orders-empty">
            <text>{{ t('support.noOrders') }}</text>
          </view>
          <view
            v-else
            v-for="order in orders"
            :key="order.id"
            class="order-item"
            @click="selectOrder(order)"
          >
            <view class="order-item-info">
              <text class="order-item-no">{{ order.order_no }}</text>
              <text class="order-item-title">{{ order.goods?.title || '' }}</text>
              <text class="order-item-price">{{ formatPrice(order.total_amount || 0, order.currency || 'USD') }}</text>
            </view>
            <text v-if="selectedOrder?.id === order.id" class="bi bi-check-circle-fill order-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import { getOrders } from '@/api/order'
import { createTicket } from '@/api/ticket'
import { uploadImage } from '@/api/upload'
import { verifyMember as verifyMemberApi } from '@/api/user'

// 订单列表项类型（匹配 API 返回的 snake_case 格式）
interface OrderListItem {
  id: number
  order_no: string
  status: number
  status_text: string
  goods: {
    id: number
    title: string
    cover_image: string
    price: number
  }
  quantity: number
  total_amount: number
  currency: string
  created_at: string
}

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 选择的分类
const selectedCategory = ref('other')
const selectedSubCategory = ref('')

// 是否为举报模式
const isReportMode = computed(() => selectedCategory.value === 'report')

// 举报表单数据
const reportType = ref('item') // item=商品, member=會員
const reportMemberAccount = ref('')
const reportItemNumber = ref('')
const reportOtherItems = ref('')
const reportReason = ref('')
const reportConfirmed = ref(false)

// 举报会员专用字段
const reportMemberName = ref('')
const memberVerified = ref(false)
const memberVerifying = ref(false)
const memberVerifyFailed = ref(false)
const verifiedMemberInfo = ref<{ id: number; nickname: string; avatar: string; isSeller: boolean } | null>(null)

// 弹窗状态
const showTypePicker = ref(false)
const showOrderPicker = ref(false)
const showImageSourcePicker = ref(false)
const showReportTypePicker = ref(false)
const showReportReasonPicker = ref(false)

// 举报类型选项
const reportTypeOptions = computed(() => [
  { code: 'item', name: t('report.typeItem') },
  { code: 'member', name: t('report.typeMember') },
])

// 举报原因选项
const reportReasonOptions = computed(() => [
  { code: 'counterfeit', name: t('report.reasonCounterfeit') },
  { code: 'prohibited', name: t('report.reasonProhibited') },
  { code: 'misleading', name: t('report.reasonMisleading') },
  { code: 'infringement', name: t('report.reasonInfringement') },
  { code: 'fraud', name: t('report.reasonFraud') },
  { code: 'spam', name: t('report.reasonSpam') },
  { code: 'other', name: t('report.reasonOther') },
])

// 举报类型标签
const reportTypeLabel = computed(() => {
  const option = reportTypeOptions.value.find(o => o.code === reportType.value)
  return option?.name || t('report.typeItem')
})

// 举报原因标签
const reportReasonLabel = computed(() => {
  const option = reportReasonOptions.value.find(o => o.code === reportReason.value)
  return option?.name || ''
})

// 订单相关
const orders = ref<OrderListItem[]>([])
const ordersLoading = ref(false)
const selectedOrder = ref<OrderListItem | null>(null)

// 表单数据
const description = ref('')
const images = ref<string[]>([])
const submitting = ref(false)

// 所有分类
const allCategories = computed(() => [
  { code: 'order', name: t('support.order'), icon: 'bi-box-seam' },
  { code: 'refund', name: t('support.refund'), icon: 'bi-arrow-return-left' },
  { code: 'payment', name: t('support.payment'), icon: 'bi-credit-card' },
  { code: 'account', name: t('support.account'), icon: 'bi-person-circle' },
  { code: 'report', name: t('support.report'), icon: 'bi-flag' },
  { code: 'other', name: t('support.other'), icon: 'bi-question-circle' },
])

// 子分类名称映射
const subCategoryNames = computed(() => {
  const names: Record<string, string> = {
    order_status: t('support.subOrderStatus'),
    not_received: t('support.subNotReceived'),
    wrong_item: t('support.subWrongItem'),
    not_shipped: t('support.subNotShipped'),
    order_other: t('support.subOrderOther'),
    return_apply: t('support.subReturnApply'),
    refund_status: t('support.subRefundStatus'),
    return_shipping: t('support.subReturnShipping'),
    refund_other: t('support.subRefundOther'),
    pay_failed: t('support.subPayFailed'),
    double_charge: t('support.subDoubleCharge'),
    refund_delay: t('support.subRefundDelay'),
    payment_other: t('support.subPaymentOther'),
    login_issue: t('support.subLoginIssue'),
    password_reset: t('support.subPasswordReset'),
    account_security: t('support.subAccountSecurity'),
    account_other: t('support.subAccountOther'),
    report_seller: t('support.subReportSeller'),
    report_buyer: t('support.subReportBuyer'),
    report_goods: t('support.subReportGoods'),
    report_other: t('support.subReportOther'),
    other_question: t('support.subOtherQuestion'),
    suggestion: t('support.subSuggestion'),
    cooperation: t('support.subCooperation'),
  }
  return names
})

// 当前分类信息
const categoryInfo = computed(() => {
  return allCategories.value.find(c => c.code === selectedCategory.value) || allCategories.value[5]
})

const categoryName = computed(() => categoryInfo.value.name)
const categoryIcon = computed(() => categoryInfo.value.icon)
const subCategoryName = computed(() => {
  if (selectedSubCategory.value) {
    return subCategoryNames.value[selectedSubCategory.value] || ''
  }
  return ''
})

// 是否需要关联订单
const needOrderRelation = computed(() => {
  return ['order', 'refund', 'payment'].includes(selectedCategory.value)
})

// 是否可以提交
const canSubmit = computed(() => {
  if (isReportMode.value) {
    if (reportType.value === 'item') {
      // 举报商品：需要选择原因并确认
      return reportReason.value && reportConfirmed.value
    } else {
      // 举报会员：需要输入会员名称并确认
      return reportMemberName.value.trim() && reportConfirmed.value
    }
  }
  // 普通模式：需要描述至少10个字符
  return description.value.trim().length >= 10
})

// 格式化价格
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 页面加载
onLoad((options) => {
  if (options?.type) {
    selectedCategory.value = options.type
  }
  if (options?.subType) {
    selectedSubCategory.value = options.subType
  }
  if (options?.orderId) {
    // 如果传入了订单ID，加载该订单信息
    loadOrderById(parseInt(options.orderId))
  }
})

// 设置导航标题
onShow(() => {
  const title = isReportMode.value ? t('report.title') : t('support.createTicket')
  uni.setNavigationBarTitle({ title })
})

// 选择分类
function selectCategory(code: string) {
  selectedCategory.value = code
  selectedSubCategory.value = ''
  showTypePicker.value = false
}

// 选择举报类型
function selectReportType(code: string) {
  reportType.value = code
  showReportTypePicker.value = false
}

// 选择举报原因
function selectReportReason(code: string) {
  reportReason.value = code
  showReportReasonPicker.value = false
}

// 验证会员/商店名称
async function verifyMember() {
  if (!reportMemberName.value.trim() || memberVerifying.value) return

  memberVerifying.value = true
  memberVerified.value = false
  memberVerifyFailed.value = false

  try {
    const res = await verifyMemberApi(reportMemberName.value.trim())
    if (res.code === 0 && res.data?.exists) {
      memberVerified.value = true
      // 保存验证到的用户信息
      verifiedMemberInfo.value = res.data
    } else {
      memberVerifyFailed.value = true
    }
  } catch (e) {
    console.error('Failed to verify member:', e)
    memberVerifyFailed.value = true
  } finally {
    memberVerifying.value = false
  }
}

// 清除已验证的会员
function clearVerifiedMember() {
  memberVerified.value = false
  memberVerifyFailed.value = false
  verifiedMemberInfo.value = null
  reportMemberName.value = ''
}

// 加载订单列表
async function loadOrders() {
  ordersLoading.value = true
  try {
    const res = await getOrders({ page: 1, pageSize: 20 })
    if (res.code === 0) {
      orders.value = (res.data.list || []) as unknown as OrderListItem[]
    }
  } catch (e) {
    console.error('Failed to load orders:', e)
  } finally {
    ordersLoading.value = false
  }
}

// 根据ID加载订单
async function loadOrderById(orderId: number) {
  try {
    const res = await getOrders({ page: 1, pageSize: 100 })
    if (res.code === 0) {
      const order = (res.data.list as unknown as OrderListItem[])?.find((o) => o.id === orderId)
      if (order) {
        selectedOrder.value = order
      }
    }
  } catch (e) {
    console.error('Failed to load order:', e)
  }
}

// 选择订单
function selectOrder(order: OrderListItem) {
  selectedOrder.value = order
  showOrderPicker.value = false
}

// 清除选中的订单
function clearOrder() {
  selectedOrder.value = null
}

// 打开图片来源选择
function chooseImage() {
  showImageSourcePicker.value = true
}

// 选择图片来源
function selectImageSource(source: 'camera' | 'album') {
  showImageSourcePicker.value = false
  uni.chooseImage({
    count: 9 - images.value.length,
    sizeType: ['compressed'],
    sourceType: [source],
    success: async (res) => {
      for (const tempPath of res.tempFilePaths) {
        try {
          uni.showLoading({ title: t('common.uploading') })
          const uploadRes = await uploadImage(tempPath, 'ticket')
          if (uploadRes.code === 0 && uploadRes.data?.url) {
            images.value.push(uploadRes.data.url)
          }
        } catch (e) {
          console.error('Failed to upload image:', e)
        } finally {
          uni.hideLoading()
        }
      }
    },
  })
}

// 预览图片
function previewImage(index: number) {
  uni.previewImage({
    current: images.value[index],
    urls: images.value,
  })
}

// 删除图片
function removeImage(index: number) {
  images.value.splice(index, 1)
}

// 打开订单选择弹窗
watch(() => showOrderPicker.value, (val) => {
  if (val && orders.value.length === 0) {
    loadOrders()
  }
})

// 提交工单
async function handleSubmit() {
  if (!canSubmit.value || submitting.value) return

  // 举报模式验证
  if (isReportMode.value) {
    if (reportType.value === 'item') {
      // 举报商品：需要选择原因
      if (!reportReason.value) {
        toast.error(t('report.pleaseSelectReason'))
        return
      }
    } else {
      // 举报会员：需要输入会员名称
      if (!reportMemberName.value.trim()) {
        toast.error(t('report.pleaseEnterMemberName'))
        return
      }
    }
    if (!reportConfirmed.value) {
      toast.error(t('report.pleaseConfirm'))
      return
    }
  } else {
    // 普通模式验证
    if (description.value.trim().length < 10) {
      toast.error(t('support.descriptionTooShort'))
      return
    }
  }

  submitting.value = true
  try {
    let subject: string
    let content: string
    let subCategory: string | undefined

    if (isReportMode.value) {
      // 举报模式：生成结构化内容
      // subject 使用统一的英文代码格式，便于后端翻译
      const parts = []
      parts.push(`report_type: ${reportType.value}`) // item 或 member

      if (reportType.value === 'item') {
        // 举报商品：subject = "Item - {reason_code}"
        subject = `Item - ${reportReason.value}`
        subCategory = reportReason.value
        if (reportMemberAccount.value) {
          parts.push(`member_account: ${reportMemberAccount.value}`)
        }
        if (reportItemNumber.value) {
          parts.push(`item_number: ${reportItemNumber.value}`)
        }
        if (reportOtherItems.value) {
          parts.push(`other_items: ${reportOtherItems.value}`)
        }
        parts.push(`reason: ${reportReason.value}`)
      } else {
        // 举报会员：subject = "Member - {username}"
        subject = `Member - ${reportMemberName.value}`
        subCategory = 'report_member'
        parts.push(`member_name: ${reportMemberName.value}`)
      }

      if (description.value.trim()) {
        parts.push(`details: ${description.value.trim()}`)
      }
      content = parts.join('\n')
    } else {
      // 普通模式
      subject = subCategoryName.value || categoryName.value
      content = description.value.trim()
      subCategory = selectedSubCategory.value || undefined
    }

    const res = await createTicket({
      category: selectedCategory.value,
      subCategory,
      subject,
      content,
      images: images.value.length > 0 ? images.value : undefined,
      relatedOrderId: selectedOrder.value?.id || undefined,
    })

    if (res.code === 0) {
      toast.success(t('support.ticketCreated'))
      // 跳转到工单详情
      setTimeout(() => {
        uni.redirectTo({ url: `/pages/ticket/detail?id=${res.data.id}` })
      }, 500)
    } else {
      toast.error(res.message || t('common.operationFailed'))
    }
  } catch (e) {
    console.error('Failed to create ticket:', e)
    toast.error(t('common.operationFailed'))
  } finally {
    submitting.value = false
  }
}

// watch 需要导入
import { watch } from 'vue'
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #FF6B35;
$color-primary-light: #FFF5F2;
$color-text: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;

.page {
  min-height: 100vh;
  background-color: $color-background;
  display: flex;
  flex-direction: column;
}

.content {
  flex: 1;
  width: auto;
}

// 区块
.section {
  padding: 0 16px;
  margin-top: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  padding: 8px 4px 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
}

.section-required {
  color: #E74C3C;
  margin-left: 4px;
}

.section-optional {
  margin-left: auto;
  font-size: 12px;
  color: $color-text-muted;
}

// 选择卡片（下拉选择器样式）
.select-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background-color: $color-surface;
  border-radius: 12px;
  border: 1px solid $color-border;
  cursor: pointer;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.select-value {
  font-size: 15px;
  color: $color-text;

  &.placeholder {
    color: $color-text-muted;
  }
}

.select-arrow {
  font-size: 14px;
  color: $color-text-muted;
}

// 输入框
.input-wrapper {
  background-color: $color-surface;
  border-radius: 12px;
  border: 1px solid $color-border;
  padding: 0 16px;
}

.form-input {
  width: 100%;
  height: 48px;
  font-size: 15px;
  color: $color-text;
  background: transparent;
  border: none;
  outline: none;
}

// 字段提示
.field-hint {
  display: block;
  margin-top: 8px;
  padding: 0 4px;
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.4;
}

// 复选框
.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background-color: $color-surface;
  border-radius: 12px;
  cursor: pointer;
}

.checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid $color-border;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;

  &.checked {
    background-color: $color-primary;
    border-color: $color-primary;

    .bi {
      color: #FFFFFF;
      font-size: 14px;
    }
  }
}

.checkbox-label {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.5;
}

// 类型选择卡片
.type-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background-color: $color-surface;
  border-radius: 12px;
  cursor: pointer;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.type-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.type-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 20px;
    color: #FFFFFF;
  }

  &.order {
    background: linear-gradient(135deg, #4A90D9 0%, #357ABD 100%);
  }

  &.refund {
    background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
  }

  &.payment {
    background: linear-gradient(135deg, #F5A623 0%, #E09915 100%);
  }

  &.account {
    background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
  }

  &.report {
    background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
  }

  &.other {
    background: linear-gradient(135deg, #95A5A6 0%, #7F8C8D 100%);
  }
}

.type-text {
  display: flex;
  flex-direction: column;
}

.type-main {
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
}

.type-sub {
  font-size: 13px;
  color: $color-text-secondary;
  margin-top: 2px;
}

.type-arrow {
  font-size: 14px;
  color: $color-text-muted;
}

// 订单卡片
.order-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background-color: $color-surface;
  border-radius: 12px;
}

.order-info {
  flex: 1;
  min-width: 0;
}

.order-no {
  display: block;
  font-size: 12px;
  color: $color-text-muted;
  margin-bottom: 4px;
}

.order-title {
  display: block;
  font-size: 14px;
  color: $color-text;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.order-price {
  font-size: 14px;
  font-weight: 600;
  color: $color-primary;
}

.order-change {
  padding: 8px;

  .bi {
    font-size: 20px;
    color: $color-text-muted;
  }
}

// 选择订单按钮
.select-order {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background-color: $color-surface;
  border-radius: 12px;
  border: 1px dashed $color-border;
  cursor: pointer;

  .bi:first-child {
    font-size: 20px;
    color: $color-text-muted;
  }

  .bi:last-child {
    font-size: 14px;
    color: $color-text-muted;
    margin-left: auto;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.select-text {
  font-size: 14px;
  color: $color-text-secondary;
}

// 文本域
.textarea-wrapper {
  position: relative;
  background-color: $color-surface;
  border-radius: 12px;
  padding: 16px;
}

.description-input {
  width: 100%;
  min-height: 120px;
  font-size: 15px;
  line-height: 1.5;
  color: $color-text;

  &.short {
    min-height: 80px;
  }
}

.char-count {
  position: absolute;
  right: 16px;
  bottom: 12px;
  font-size: 12px;
  color: $color-text-muted;
}

// 图片网格
.image-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.image-item {
  position: relative;
  width: calc((100vw - 32px - 24px) / 3);
  height: calc((100vw - 32px - 24px) / 3);
  border-radius: 8px;
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-delete {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  background-color: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 12px;
    color: #FFFFFF;
  }
}

.image-add {
  width: calc((100vw - 32px - 24px) / 3);
  height: calc((100vw - 32px - 24px) / 3);
  background-color: $color-surface;
  border: 1px dashed $color-border;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  cursor: pointer;

  .bi {
    font-size: 24px;
    color: $color-text-muted;
  }

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.add-text {
  font-size: 12px;
  color: $color-text-muted;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 80px);
}

// 底部提交栏
.submit-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  background-color: $color-surface;
  border-top: 1px solid $color-border;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 16px;
  font-weight: 500;
  border: none;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;

  &[disabled] {
    background-color: #CCC;
  }
}

// 弹窗
.picker-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
}

.picker-popup {
  width: 100%;
  background-color: $color-surface;
  border-radius: 16px 16px 0 0;
  display: flex;
  flex-direction: column;

  &.order-picker {
    height: 60vh;
  }
}

.picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  flex-shrink: 0;
}

.picker-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
}

.picker-close {
  padding: 4px;

  .bi {
    font-size: 20px;
    color: $color-text-muted;
  }
}

.picker-content {
  flex: 1;
  height: 0;
  padding-bottom: env(safe-area-inset-bottom);
}

.picker-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }

  &.simple {
    // 简单选择项（无图标）
    .picker-label {
      flex: 1;
    }
  }

  &.active {
    background-color: $color-primary-light;
  }
}

.picker-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 18px;
    color: #FFFFFF;
  }

  &.order {
    background: linear-gradient(135deg, #4A90D9 0%, #357ABD 100%);
  }

  &.refund {
    background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
  }

  &.payment {
    background: linear-gradient(135deg, #F5A623 0%, #E09915 100%);
  }

  &.account {
    background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
  }

  &.report {
    background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
  }

  &.other {
    background: linear-gradient(135deg, #95A5A6 0%, #7F8C8D 100%);
  }
}

.picker-label {
  flex: 1;
  font-size: 15px;
  color: $color-text;
}

.picker-check {
  font-size: 18px;
  color: $color-primary;
}

// 订单列表项
.orders-loading,
.orders-empty {
  padding: 40px 16px;
  text-align: center;
  color: $color-text-muted;
  font-size: 14px;
}

.order-item {
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.order-item-info {
  flex: 1;
  min-width: 0;
}

.order-item-no {
  display: block;
  font-size: 12px;
  color: $color-text-muted;
  margin-bottom: 4px;
}

.order-item-title {
  display: block;
  font-size: 14px;
  color: $color-text;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.order-item-price {
  font-size: 14px;
  font-weight: 600;
  color: $color-primary;
}

.order-check {
  font-size: 20px;
  color: $color-primary;
  margin-left: 12px;
}

// 图片来源选择弹窗
.action-sheet {
  width: 100%;
  background-color: transparent;
  padding: 0 12px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
}

.action-sheet-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  height: 56px;
  background-color: $color-surface;
  font-size: 16px;
  color: $color-text;
  cursor: pointer;

  &:first-child {
    border-radius: 12px 12px 0 0;
  }

  &:nth-child(2) {
    border-top: 1px solid $color-border;
    border-radius: 0 0 12px 12px;
  }

  .bi {
    font-size: 20px;
    color: $color-primary;
  }

  &:active {
    background-color: $color-background;
  }
}

.action-sheet-cancel {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 56px;
  margin-top: 8px;
  background-color: $color-surface;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 500;
  color: $color-text;
  cursor: pointer;

  &:active {
    background-color: $color-background;
  }
}

// 输入框带按钮
.input-with-button {
  display: flex;
  align-items: center;
  gap: 12px;
  background-color: $color-surface;
  border-radius: 12px;
  border: 1px solid $color-border;
  padding: 0 12px 0 16px;
}

.flex-input {
  flex: 1;
  height: 48px;
  font-size: 15px;
  color: $color-text;
  background: transparent;
  border: none;
  outline: none;
}

.verify-btn {
  flex-shrink: 0;
  height: 32px;
  padding: 0 16px;
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 14px;
  font-weight: 500;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;

  &:active {
    opacity: 0.8;
  }

  &.verified {
    background-color: #52C41A;
  }

  .bi {
    font-size: 16px;
  }

  .spin {
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

// 字段成功/失败提示
.field-success {
  display: block;
  margin-top: 8px;
  padding: 0 4px;
  font-size: 12px;
  color: #52C41A;
}

.field-error {
  display: block;
  margin-top: 8px;
  padding: 0 4px;
  font-size: 12px;
  color: #E74C3C;
}

// 已验证会员卡片
.verified-member-card {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding: 12px;
  background-color: #F6FFED;
  border: 1px solid #B7EB8F;
  border-radius: 12px;
}

.member-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  flex-shrink: 0;
}

.member-avatar-placeholder {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background-color: #E5E5E5;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 24px;
    color: #999;
  }
}

.member-info {
  flex: 1;
  min-width: 0;
}

.member-name {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.member-tags {
  display: flex;
  gap: 6px;
  margin-top: 4px;
}

.member-tag {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
  background-color: #E6F7FF;
  color: #1890FF;

  &.seller {
    background-color: #FFF7E6;
    color: #FA8C16;
  }
}

.verified-icon {
  font-size: 20px;
  color: #52C41A;
  flex-shrink: 0;
}
</style>
