<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('credit.applyTitle')" />

    <!-- 顶部进度 -->
    <view class="progress-header">
      <view class="progress-bar">
        <view class="progress-fill" :style="{ width: `${((currentStep + 1) / 3) * 100}%` }"></view>
      </view>
      <text class="progress-text">{{ `${t('credit.stepPrefix')}${currentStep + 1}${t('credit.stepMiddle')}3${t('credit.stepSuffix')}` }}</text>
    </view>

    <!-- Step 1: 选择信用卡 -->
    <view v-show="currentStep === 0" class="form-container">
      <view class="step-header">
        <text class="step-title">{{ t('credit.selectCardTitle') }}</text>
        <text class="step-subtitle">{{ t('credit.selectCardSubtitle') }}</text>
      </view>

      <!-- 加载中 -->
      <view v-if="loadingCards" class="loading-container">
        <text class="bi bi-arrow-repeat spinning"></text>
        <text class="loading-text">{{ t('common.loading') }}</text>
      </view>

      <!-- 无卡片提示 -->
      <view v-else-if="!hasCards" class="no-card-container">
        <text class="bi bi-credit-card-2-back no-card-icon"></text>
        <text class="no-card-title">{{ t('credit.noSavedCard') }}</text>
        <text class="no-card-desc">{{ t('credit.noSavedCardDesc') }}</text>
        <view class="add-card-btn" @click="goAddCard">
          <text class="bi bi-plus-lg"></text>
          <text>{{ t('credit.goAddCard') }}</text>
        </view>
      </view>

      <!-- 卡片列表 -->
      <view v-else class="card-list">
        <view
          v-for="card in userCards"
          :key="card.id"
          class="card-item"
          :class="{ selected: selectedCardId === card.id }"
          @click="selectCard(card)"
        >
          <view class="card-brand-icon" :class="getCardBrandColorClass(card.cardBrand)">
            <text class="bi" :class="getCardBrandIconClass(card.cardBrand)"></text>
          </view>
          <view class="card-info">
            <text class="card-number">**** **** **** {{ card.lastFour }}</text>
            <text class="card-holder">{{ card.cardholderName || t('credit.cardHolder') }}</text>
          </view>
          <view class="card-check" v-if="selectedCardId === card.id">
            <text class="bi bi-check-circle-fill"></text>
          </view>
          <view class="card-radio" v-else>
            <view class="radio-circle"></view>
          </view>
        </view>

        <!-- 添加新卡片入口 -->
        <view class="add-card-link" @click="goAddCard">
          <text class="bi bi-plus-circle"></text>
          <text>{{ t('credit.addNewCard') }}</text>
        </view>
      </view>

      <!-- 选中卡片后显示账单地址预览 -->
      <view v-if="selectedCard?.billingAddress" class="billing-preview">
        <view class="preview-header">
          <text class="bi bi-geo-alt"></text>
          <text class="preview-title">{{ t('credit.billingAddressPreview') }}</text>
        </view>
        <view class="preview-content">
          <view class="preview-row">
            <text class="preview-label">{{ t('credit.legalName') }}</text>
            <text class="preview-value">{{ selectedCard.billingAddress.name }}</text>
          </view>
          <view class="preview-row">
            <text class="preview-label">{{ t('credit.phoneNumber') }}</text>
            <text class="preview-value">{{ selectedCard.billingAddress.phone }}</text>
          </view>
          <view class="preview-row">
            <text class="preview-label">{{ t('credit.address') }}</text>
            <text class="preview-value">{{ selectedCard.billingAddress.fullAddress }}</text>
          </view>
        </view>
      </view>

      <!-- 信息提示 -->
      <view class="info-card" v-if="hasCards">
        <text class="bi bi-info-circle info-icon"></text>
        <view class="info-content">
          <text class="info-text">{{ t('credit.cardSelectInfo') }}</text>
        </view>
      </view>
    </view>

    <!-- Step 2: 补充信息 -->
    <view v-show="currentStep === 1" class="form-container">
      <view class="step-header">
        <text class="step-title">{{ t('credit.additionalInfoTitle') }}</text>
        <text class="step-subtitle">{{ t('credit.additionalInfoSubtitle') }}</text>
      </view>

      <view class="form-card">
        <!-- 邮箱（用户手动输入，预填账户邮箱） -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.emailAddress') }}</text>
          <input
            v-model="form.email"
            type="text"
            class="form-input"
            :placeholder="t('credit.emailPlaceholder')"
            :adjust-position="false"
          />
        </view>

        <!-- 出生日期（用户手动输入） -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.dateOfBirth') }}</text>
          <view class="form-input picker-input" @click="openDatePicker">
            <text :class="{ placeholder: !form.date_of_birth }">
              {{ formatDisplayDate(form.date_of_birth) || t('credit.selectDate') }}
            </text>
            <text class="bi bi-calendar3"></text>
          </view>
        </view>

        <!-- 从卡片获取的信息（只读显示） -->
        <view class="readonly-info" v-if="selectedCard">
          <view class="readonly-header">
            <text class="bi bi-credit-card-2-front"></text>
            <text class="readonly-title">{{ t('credit.infoFromCard') }}</text>
          </view>
          <view class="readonly-content">
            <view class="readonly-row">
              <text class="readonly-label">{{ t('credit.legalName') }}</text>
              <text class="readonly-value">{{ form.full_name }}</text>
            </view>
            <view class="readonly-row">
              <text class="readonly-label">{{ t('credit.phoneNumber') }}</text>
              <text class="readonly-value">{{ form.phone }}</text>
            </view>
            <view class="readonly-row">
              <text class="readonly-label">{{ t('credit.countryOfResidence') }}</text>
              <text class="readonly-value">{{ form.country }}</text>
            </view>
            <view class="readonly-row">
              <text class="readonly-label">{{ t('credit.address') }}</text>
              <text class="readonly-value">{{ form.address }}, {{ form.city }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 预估额度提示 -->
      <view class="estimate-card">
        <text class="bi bi-info-circle estimate-icon"></text>
        <view class="estimate-content">
          <text class="estimate-title">{{ t('credit.estimateTitle') }}</text>
          <text class="estimate-desc">{{ t('credit.estimateDesc') }}</text>
        </view>
      </view>
    </view>

    <!-- Step 3: 身份验证 -->
    <view v-show="currentStep === 2" class="form-container">
      <view class="step-header">
        <text class="step-title">{{ t('credit.verifyIdentityTitle') }}</text>
        <text class="step-subtitle">{{ t('credit.verifyIdentitySubtitle') }}</text>
      </view>

      <view class="form-card">
        <!-- 证件类型 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.idType') }}</text>
          <view class="id-type-options">
            <view
              v-for="item in idTypes"
              :key="item.value"
              class="id-type-option"
              :class="{ active: form.id_type === item.value }"
              @click="form.id_type = item.value"
            >
              <text class="bi" :class="item.icon"></text>
              <text class="option-label">{{ item.label }}</text>
            </view>
          </view>
        </view>

        <!-- 证件号码 -->
        <view class="form-group">
          <text class="form-label">{{ idNumberLabel }}</text>
          <input
            v-model="form.id_number"
            type="text"
            class="form-input"
            :placeholder="idNumberPlaceholder"
            :adjust-position="false"
          />
        </view>

        <!-- 证件照上传 -->
        <view class="form-group">
          <text class="form-label">{{ form.id_type === 'passport' ? t('credit.passportPhoto') : t('credit.idFrontImage') }}</text>
          <view class="upload-area" @click="chooseIdFront">
            <image v-if="form.id_front_image" class="upload-preview" :src="form.id_front_image" mode="aspectFill" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-camera"></text>
              <text class="upload-text">{{ form.id_type === 'passport' ? t('credit.uploadPassport') : t('credit.uploadIdFront') }}</text>
            </view>
            <view v-if="uploadingFront" class="upload-loading">
              <text class="bi bi-arrow-repeat spinning"></text>
            </view>
          </view>
        </view>

        <!-- 证件背面（护照不需要） -->
        <view v-if="form.id_type !== 'passport'" class="form-group">
          <text class="form-label">{{ t('credit.idBackImage') }}</text>
          <view class="upload-area" @click="chooseIdBack">
            <image v-if="form.id_back_image" class="upload-preview" :src="form.id_back_image" mode="aspectFill" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-camera"></text>
              <text class="upload-text">{{ t('credit.uploadIdBack') }}</text>
            </view>
            <view v-if="uploadingBack" class="upload-loading">
              <text class="bi bi-arrow-repeat spinning"></text>
            </view>
          </view>
        </view>

        <!-- 信用卡账单（可选） -->
        <view class="form-group statement-section">
          <view class="statement-header">
            <view class="statement-title-row">
              <text class="form-label">{{ t('credit.cardStatement') }}</text>
              <text class="optional-badge">{{ t('credit.optional') }}</text>
            </view>
            <text class="statement-desc">{{ formatCardStatementDesc(selectedCard?.lastFour || '****') }}</text>
          </view>

          <!-- 隐私保护提示 -->
          <view class="privacy-tip">
            <text class="bi bi-shield-check"></text>
            <view class="privacy-tip-content">
              <text class="privacy-tip-title">{{ t('credit.privacyProtection') }}</text>
              <text class="privacy-tip-text">{{ t('credit.privacyProtectionDesc') }}</text>
            </view>
          </view>

          <!-- 账单要求说明 -->
          <view class="statement-requirements">
            <text class="requirements-title">{{ t('credit.statementRequirements') }}</text>
            <view class="requirements-list">
              <view class="requirement-item">
                <text class="bi bi-check2"></text>
                <text>{{ t('credit.requirementName') }}</text>
              </view>
              <view class="requirement-item">
                <text class="bi bi-check2"></text>
                <text>{{ t('credit.requirementPeriod') }}</text>
              </view>
              <view class="requirement-item">
                <text class="bi bi-check2"></text>
                <text>{{ t('credit.requirementAmount') }}</text>
              </view>
            </view>
          </view>

          <!-- 账单图片上传（最多3张） -->
          <view class="statement-uploads">
            <view
              v-for="(img, index) in 3"
              :key="index"
              class="statement-upload-item"
              @click="chooseStatement(index)"
            >
              <image
                v-if="form.statement_images[index]"
                class="upload-preview"
                :src="form.statement_images[index]"
                mode="aspectFill"
              />
              <view v-else class="upload-placeholder small">
                <text class="bi bi-file-earmark-image"></text>
                <text class="upload-month">{{ t('credit.month' + (index + 1)) }}</text>
              </view>
              <view v-if="uploadingStatement[index]" class="upload-loading">
                <text class="bi bi-arrow-repeat spinning"></text>
              </view>
              <!-- 删除按钮 -->
              <view
                v-if="form.statement_images[index]"
                class="delete-btn"
                @click.stop="removeStatement(index)"
              >
                <text class="bi bi-x"></text>
              </view>
            </view>
          </view>

          <text class="statement-note">{{ t('credit.statementNote') }}</text>
        </view>

        <!-- 条款同意 -->
        <view class="terms-section">
          <view class="terms-checkbox" :class="{ checked: agreeTerms }" @click="agreeTerms = !agreeTerms">
            <text v-if="agreeTerms" class="bi bi-check"></text>
          </view>
          <view class="terms-text">
            <text>{{ t('credit.agreeToThe') }} </text>
            <text class="terms-link" @click.stop="openTerms">{{ t('credit.termsOfService') }}</text>
            <text> {{ t('credit.and') }} </text>
            <text class="terms-link" @click.stop="openPrivacy">{{ t('credit.privacyPolicy') }}</text>
          </view>
        </view>
      </view>

      <!-- 安全提示 -->
      <view class="security-notice">
        <text class="bi bi-shield-lock"></text>
        <text class="notice-text">{{ t('credit.securityNotice') }}</text>
      </view>
    </view>

    <!-- 底部按钮 -->
    <view class="bottom-bar">
      <view v-if="currentStep > 0" class="btn-back" @click="prevStep">
        <text class="bi bi-arrow-left"></text>
        <text>{{ t('credit.back') }}</text>
      </view>
      <view
        v-if="currentStep < 2"
        class="btn-continue"
        :class="{ disabled: !canContinue }"
        @click="nextStep"
      >
        <text>{{ t('credit.continue') }}</text>
        <text class="bi bi-arrow-right"></text>
      </view>
      <view
        v-else
        class="btn-submit"
        :class="{ disabled: !canSubmit }"
        @click="submitApplication"
      >
        <text v-if="submitting">{{ t('credit.submitting') }}</text>
        <text v-else>{{ t('credit.submitApplication') }}</text>
      </view>
    </view>

    <!-- 日期选择器 -->
    <view v-if="showDatePicker" class="picker-mask" @click="showDatePicker = false">
      <view class="picker-container" @click.stop>
        <view class="picker-header">
          <text class="picker-cancel" @click="showDatePicker = false">{{ t('common.cancel') }}</text>
          <text class="picker-title">{{ t('credit.dateOfBirth') }}</text>
          <text class="picker-confirm" @click="confirmDate">{{ t('common.confirm') }}</text>
        </view>
        <view class="date-picker-body">
          <!-- 年份列 -->
          <scroll-view scroll-y class="date-column" :scroll-into-view="'year-' + tempYear">
            <view
              v-for="year in yearList"
              :key="year"
              :id="'year-' + year"
              class="date-item"
              :class="{ active: tempYear === year }"
              @click="tempYear = year"
            >
              <text>{{ year }}</text>
            </view>
          </scroll-view>
          <!-- 月份列 -->
          <scroll-view scroll-y class="date-column" :scroll-into-view="'month-' + tempMonth">
            <view
              v-for="month in monthList"
              :key="month"
              :id="'month-' + month"
              class="date-item"
              :class="{ active: tempMonth === month }"
              @click="tempMonth = month"
            >
              <text>{{ monthNames[month - 1] }}</text>
            </view>
          </scroll-view>
          <!-- 日期列 -->
          <scroll-view scroll-y class="date-column" :scroll-into-view="'day-' + tempDay">
            <view
              v-for="day in dayList"
              :key="day"
              :id="'day-' + day"
              class="date-item"
              :class="{ active: tempDay === day }"
              @click="tempDay = day"
            >
              <text>{{ day }}</text>
            </view>
          </scroll-view>
        </view>
      </view>
    </view>

    <!-- 申请成功弹窗 -->
    <ConfirmDialog
      :visible="showSuccessDialog"
      :title="t('credit.applicationSubmitted')"
      :content="t('credit.applicationSubmittedDesc')"
      icon="bi-check-circle"
      icon-type="success"
      :show-cancel="false"
      :confirm-text="t('common.ok')"
      @update:visible="showSuccessDialog = $event"
      @confirm="handleSuccessConfirm"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { applyCreditLimit, type CreditApplyParams } from '@/api/credit'
import { getUserCards, type UserCard } from '@/api/userCard'
import { uploadCreditImage } from '@/api/upload'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store/modules/user'
import { useToast } from '@/composables/useToast'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()
const toast = useToast()

// 格式化账单描述文本（手动替换插值）
function formatCardStatementDesc(lastFour: string): string {
  const template = t('credit.cardStatementDescWithCard')
  return template.replace('[LASTFOUR]', lastFour)
}

const currentStep = ref(0)
const submitting = ref(false)
const agreeTerms = ref(false)
const showSuccessDialog = ref(false)

// 卡片相关状态
const userCards = ref<UserCard[]>([])
const selectedCard = ref<UserCard | null>(null)
const selectedCardId = ref<number | null>(null)
const loadingCards = ref(false)
const hasCards = computed(() => userCards.value.length > 0)

// 日期选择器状态
const showDatePicker = ref(false)
const tempYear = ref(1990)
const tempMonth = ref(1)
const tempDay = ref(1)

// 图片上传状态
const uploadingFront = ref(false)
const uploadingBack = ref(false)

// 证件类型
const idTypes = computed(() => [
  { value: 'passport', label: t('credit.passport'), icon: 'bi-journal-text' },
  { value: 'driver_license', label: t('credit.driverLicense'), icon: 'bi-car-front' },
  { value: 'national_id', label: t('credit.nationalId'), icon: 'bi-person-badge' },
])

// 根据证件类型动态显示证件号码标签
const idNumberLabel = computed(() => {
  switch (form.id_type) {
    case 'passport':
      return t('credit.passportNumber')
    case 'driver_license':
      return t('credit.driverLicenseNumber')
    default:
      return t('credit.idNumber')
  }
})

// 根据证件类型动态显示证件号码占位符
const idNumberPlaceholder = computed(() => {
  switch (form.id_type) {
    case 'passport':
      return t('credit.passportNumberPlaceholder')
    case 'driver_license':
      return t('credit.driverLicenseNumberPlaceholder')
    default:
      return t('credit.idNumberPlaceholder')
  }
})

// 计算最大出生年份（18岁以上）
const maxBirthYear = computed(() => {
  return new Date().getFullYear() - 18
})

// 年份列表（1920 到 maxBirthYear）
const yearList = computed(() => {
  const years = []
  for (let y = maxBirthYear.value; y >= 1920; y--) {
    years.push(y)
  }
  return years
})

// 月份列表
const monthList = computed(() => {
  return Array.from({ length: 12 }, (_, i) => i + 1)
})

// 日期列表（根据年月计算）
const dayList = computed(() => {
  const daysInMonth = new Date(tempYear.value, tempMonth.value, 0).getDate()
  return Array.from({ length: daysInMonth }, (_, i) => i + 1)
})

// 获取月份名称（多语言）
const monthNames = computed(() => {
  const locale = appStore.locale
  const names: Record<string, string[]> = {
    'en-US': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'zh-TW': ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
    'ja-JP': ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
  }
  return names[locale] || names['en-US']
})

// 表单数据
const form = reactive({
  // 关联卡片
  user_card_id: null as number | null,
  // 从卡片自动填充的信息
  full_name: '',
  phone: '',
  country: '',
  country_code: '',
  city: '',
  address: '',
  postal_code: '',
  card_holder_name: '',
  // 用户手动输入的信息
  email: '',
  date_of_birth: '',
  // 身份验证信息
  id_type: 'passport',
  id_number: '',
  id_front_image: '',      // 用于显示预览的完整URL
  id_front_image_path: '', // 用于提交的相对路径
  id_back_image: '',       // 用于显示预览的完整URL
  id_back_image_path: '',  // 用于提交的相对路径
  statement_images: ['', '', ''] as string[],      // 账单图片预览URL（最多3张）
  statement_images_paths: ['', '', ''] as string[], // 账单图片提交路径
})

// 账单图片上传状态（每张图片独立状态）
const uploadingStatement = ref<boolean[]>([false, false, false])

// Step 1 验证：是否选择了卡片
const canContinueStep1 = computed(() => {
  return selectedCardId.value !== null && selectedCard.value !== null
})

// Step 2 验证：邮箱和出生日期
const canContinueStep2 = computed(() => {
  return form.email.trim() !== '' &&
    validateEmail(form.email) &&
    form.date_of_birth !== ''
})

// 是否可以继续下一步
const canContinue = computed(() => {
  if (currentStep.value === 0) return canContinueStep1.value
  if (currentStep.value === 1) return canContinueStep2.value
  return true
})

// 是否可以提交
const canSubmit = computed(() => {
  // Step 1 验证
  if (!canContinueStep1.value) return false

  // Step 2 验证
  if (!canContinueStep2.value) return false

  // Step 3 验证：身份证件
  const hasRequiredImages = form.id_type === 'passport'
    ? form.id_front_image_path !== ''
    : form.id_front_image_path !== '' && form.id_back_image_path !== ''

  return agreeTerms.value &&
    form.id_number.trim() !== '' &&
    hasRequiredImages &&
    !submitting.value
})

// 邮箱验证函数
function validateEmail(email: string): boolean {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

// 加载用户卡片列表
async function loadUserCards() {
  loadingCards.value = true
  try {
    const res = await getUserCards()
    if (res.code === 0) {
      // 只显示活跃状态的卡片
      userCards.value = (res.data || []).filter((c: UserCard) => c.status === 'active')
    }
  } catch (e) {
    console.error('Failed to load cards:', e)
  } finally {
    loadingCards.value = false
  }
}

// 选择卡片
function selectCard(card: UserCard) {
  selectedCard.value = card
  selectedCardId.value = card.id
  form.user_card_id = card.id

  // 自动填充账单地址信息
  if (card.billingAddress) {
    form.full_name = card.billingAddress.name || ''
    form.phone = card.billingAddress.phone || ''
    form.country = card.billingAddress.country || ''
    form.city = card.billingAddress.city || ''
    form.address = card.billingAddress.street || ''
    form.postal_code = card.billingAddress.postalCode || ''
  }

  // 设置持卡人姓名
  if (card.cardholderName) {
    form.card_holder_name = card.cardholderName
  }
}

// 获取卡片品牌图标类名
function getCardBrandIconClass(brand: string): string {
  const iconMap: Record<string, string> = {
    visa: 'bi-credit-card-2-front',
    mastercard: 'bi-credit-card',
    amex: 'bi-credit-card-2-back',
    discover: 'bi-credit-card-fill',
    jcb: 'bi-credit-card',
  }
  return iconMap[brand?.toLowerCase()] || 'bi-credit-card-2-front'
}

// 获取卡片品牌对应的颜色类名
function getCardBrandColorClass(brand: string): string {
  const brandLower = brand?.toLowerCase() || ''
  const colorMap: Record<string, string> = {
    visa: 'brand-visa',
    mastercard: 'brand-mastercard',
    amex: 'brand-amex',
    discover: 'brand-discover',
    jcb: 'brand-jcb',
  }
  return colorMap[brandLower] || 'brand-default'
}

// 跳转添加卡片页面
function goAddCard() {
  uni.navigateTo({ url: '/pages/payment/add-card' })
}

// 打开日期选择器
function openDatePicker() {
  // 如果已有值，解析并设置
  if (form.date_of_birth) {
    const parts = form.date_of_birth.split('-')
    tempYear.value = parseInt(parts[0])
    tempMonth.value = parseInt(parts[1])
    tempDay.value = parseInt(parts[2])
  } else {
    // 默认1990年1月1日
    tempYear.value = 1990
    tempMonth.value = 1
    tempDay.value = 1
  }
  showDatePicker.value = true
}

// 确认日期选择
function confirmDate() {
  const month = String(tempMonth.value).padStart(2, '0')
  const day = String(tempDay.value).padStart(2, '0')
  form.date_of_birth = `${tempYear.value}-${month}-${day}`
  showDatePicker.value = false
}

// 格式化显示日期（多语言）
function formatDisplayDate(dateStr: string): string {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  const year = parts[0]
  const month = parseInt(parts[1])
  const day = parseInt(parts[2])

  const locale = appStore.locale
  if (locale === 'en-US') {
    return `${monthNames.value[month - 1]} ${day}, ${year}`
  } else {
    // 中文和日文格式：YYYY年M月D日
    return `${year}${t('credit.yearUnit')}${month}${t('credit.monthUnit')}${day}${t('credit.dayUnit')}`
  }
}

// 选择证件正面照片
function chooseIdFront() {
  if (uploadingFront.value) return
  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      const tempFilePath = res.tempFilePaths[0]
      uploadingFront.value = true
      try {
        const result = await uploadCreditImage(tempFilePath)
        if (result.code === 0 && result.data) {
          form.id_front_image = result.data.url       // 用于预览
          form.id_front_image_path = result.data.path // 用于提交
        } else {
          toast.error(t('common.uploadFailed'))
        }
      } catch (e) {
        toast.error(t('common.uploadFailed'))
      } finally {
        uploadingFront.value = false
      }
    },
  })
}

// 选择证件背面照片
function chooseIdBack() {
  if (uploadingBack.value) return
  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      const tempFilePath = res.tempFilePaths[0]
      uploadingBack.value = true
      try {
        const result = await uploadCreditImage(tempFilePath)
        if (result.code === 0 && result.data) {
          form.id_back_image = result.data.url       // 用于预览
          form.id_back_image_path = result.data.path // 用于提交
        } else {
          toast.error(t('common.uploadFailed'))
        }
      } catch (e) {
        toast.error(t('common.uploadFailed'))
      } finally {
        uploadingBack.value = false
      }
    },
  })
}

// 选择信用卡账单图片
function chooseStatement(index: number) {
  if (uploadingStatement.value[index]) return
  // 如果已有图片，不再选择新图片（需要先删除）
  if (form.statement_images[index]) return

  uni.chooseImage({
    count: 1,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      const tempFilePath = res.tempFilePaths[0]
      uploadingStatement.value[index] = true
      try {
        const result = await uploadCreditImage(tempFilePath)
        if (result.code === 0 && result.data) {
          form.statement_images[index] = result.data.url
          form.statement_images_paths[index] = result.data.path
        } else {
          toast.error(t('common.uploadFailed'))
        }
      } catch (e) {
        toast.error(t('common.uploadFailed'))
      } finally {
        uploadingStatement.value[index] = false
      }
    },
  })
}

// 删除信用卡账单图片
function removeStatement(index: number) {
  form.statement_images[index] = ''
  form.statement_images_paths[index] = ''
}

// 验证步骤
function validateStep(step: number): boolean {
  if (step === 0) {
    if (!selectedCardId.value) {
      toast.warning(t('credit.pleaseSelectCard'))
      return false
    }
  }
  if (step === 1) {
    if (!form.email.trim()) {
      toast.warning(t('credit.emailRequired'))
      return false
    }
    if (!validateEmail(form.email)) {
      toast.warning(t('credit.invalidEmail'))
      return false
    }
    if (!form.date_of_birth) {
      toast.warning(t('credit.dobRequired'))
      return false
    }
  }
  return true
}

function nextStep() {
  if (!validateStep(currentStep.value)) return
  currentStep.value++
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

function openTerms() {
  uni.navigateTo({
    url: '/pages/settings/legal-detail?type=terms-of-service'
  })
}

function openPrivacy() {
  uni.navigateTo({
    url: '/pages/settings/legal-detail?type=privacy-policy'
  })
}

async function submitApplication() {
  // 验证所有步骤
  if (!validateStep(0) || !validateStep(1)) {
    return
  }

  if (!canSubmit.value) {
    if (!agreeTerms.value) {
      toast.warning(t('credit.pleaseAgreeTerms'))
    } else if (!form.id_number.trim()) {
      toast.warning(t('credit.idRequired'))
    } else if (!form.id_front_image_path) {
      toast.warning(form.id_type === 'passport' ? t('credit.passportRequired') : t('credit.idFrontRequired'))
    } else if (form.id_type !== 'passport' && !form.id_back_image_path) {
      toast.warning(t('credit.idBackRequired'))
    }
    return
  }

  try {
    submitting.value = true

    // 转换 id_type 值（national_id -> id_card）
    const idTypeMap: Record<string, 'passport' | 'id_card' | 'driver_license'> = {
      passport: 'passport',
      driver_license: 'driver_license',
      national_id: 'id_card',
    }

    // 收集已上传的账单图片路径（过滤空值）
    const statementImages = form.statement_images_paths.filter(path => path !== '')

    const submitData: Partial<CreditApplyParams> = {
      // 关联选中的卡片
      user_card_id: form.user_card_id || undefined,
      // 从卡片自动获取的信息
      full_name: form.full_name,
      phone: form.phone,
      country: form.country,
      city: form.city,
      address: form.address,
      postal_code: form.postal_code,
      // 用户手动输入的信息
      email: form.email,
      birth_date: form.date_of_birth,
      nationality: form.country,
      // 身份验证信息
      id_type: idTypeMap[form.id_type] || 'passport',
      id_number: form.id_number,
      id_front_image: form.id_front_image_path,  // 使用 path 提交
      id_back_image: form.id_type !== 'passport' ? form.id_back_image_path : undefined,  // 使用 path 提交
      statement_images: statementImages.length > 0 ? statementImages : undefined, // 账单图片（可选）
    }

    const res = await applyCreditLimit(submitData as CreditApplyParams)

    if (res.code === 0) {
      showSuccessDialog.value = true
    } else {
      toast.error(res.msg || t('common.error'))
    }
  } catch (e: any) {
    toast.error(e.message || t('common.error'))
  } finally {
    submitting.value = false
  }
}

function handleSuccessConfirm() {
  uni.redirectTo({
    url: '/pages/credit/index'
  })
}

onMounted(async () => {
  await loadUserCards()
  // 预填用户账户邮箱
  if (userStore.userInfo?.email) {
    form.email = userStore.userInfo.email
  }
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.applyCredit') })
  // 从添加卡片页面返回后刷新列表
  if (userCards.value.length === 0 || !selectedCardId.value) {
    loadUserCards()
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-success: #059669;
$color-border: #E7E5E4;
$color-bg: #FAFAF9;
$color-input-bg: #F5F5F4;

.page {
  min-height: 100vh;
  background-color: $color-bg;
  padding-bottom: 20px;
}

// 进度条
.progress-header {
  padding: 20px 24px;
  background: #fff;
}

.progress-bar {
  height: 4px;
  background: $color-border;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-fill {
  height: 100%;
  background: $color-accent;
  border-radius: 2px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 13px;
  color: $color-muted;
}

// 表单容器
.form-container {
  padding: 24px 16px;
}

.step-header {
  margin-bottom: 24px;
}

.step-title {
  font-size: 24px;
  font-weight: 700;
  color: $color-primary;
  display: block;
  margin-bottom: 8px;
}

.step-subtitle {
  font-size: 15px;
  color: $color-muted;
  line-height: 1.5;
}

// 加载状态
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: #fff;
  border-radius: 16px;

  .bi {
    font-size: 32px;
    color: $color-accent;
    margin-bottom: 12px;
  }
}

.loading-text {
  font-size: 14px;
  color: $color-muted;
}

// 无卡片提示
.no-card-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 24px;
  background: #fff;
  border-radius: 16px;
}

.no-card-icon {
  font-size: 56px;
  color: $color-border;
  margin-bottom: 20px;
}

.no-card-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: 8px;
}

.no-card-desc {
  font-size: 14px;
  color: $color-muted;
  margin-bottom: 24px;
  text-align: center;
}

.add-card-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: $color-accent;
  border-radius: 12px;
  color: #fff;
  font-size: 15px;
  font-weight: 600;

  .bi {
    font-size: 18px;
  }
}

// 卡片列表
.card-list {
  background: #fff;
  border-radius: 16px;
  padding: 8px;
  margin-bottom: 16px;
}

.card-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  border-radius: 12px;
  transition: all 0.2s;

  &.selected {
    background: #FFF5F2;
    border: 2px solid $color-accent;
    margin: -2px;
    padding: 16px;
  }
}

.card-brand-icon {
  width: 48px;
  height: 32px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  background: #F5F5F4;

  .bi {
    font-size: 18px;
    color: #78716C;
  }

  // Visa - 深蓝色
  &.brand-visa {
    background: #1A1F71;
    .bi { color: #fff; }
  }

  // Mastercard - 红橙渐变
  &.brand-mastercard {
    background: linear-gradient(135deg, #EB001B 0%, #F79E1B 100%);
    .bi { color: #fff; }
  }

  // Amex - 蓝色
  &.brand-amex {
    background: #006FCF;
    .bi { color: #fff; }
  }

  // Discover - 橙色
  &.brand-discover {
    background: #FF6000;
    .bi { color: #fff; }
  }

  // JCB - 蓝绿红渐变
  &.brand-jcb {
    background: linear-gradient(135deg, #0E4C96 0%, #BC1E3A 50%, #E30138 100%);
    .bi { color: #fff; }
  }

  // 默认
  &.brand-default {
    background: #E7E5E4;
    .bi { color: #78716C; }
  }
}


.card-info {
  flex: 1;
}

.card-number {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 4px;
  letter-spacing: 0.5px;
}

.card-holder {
  font-size: 13px;
  color: $color-muted;
}

.card-check {
  .bi {
    font-size: 24px;
    color: $color-accent;
  }
}

.card-radio {
  .radio-circle {
    width: 22px;
    height: 22px;
    border: 2px solid $color-border;
    border-radius: 50%;
  }
}

.add-card-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  margin-top: 8px;
  border-top: 1px solid $color-border;
  color: $color-accent;
  font-size: 14px;
  font-weight: 500;

  .bi {
    font-size: 18px;
  }
}

// 账单地址预览
.billing-preview {
  background: #fff;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;

  .bi {
    font-size: 18px;
    color: $color-accent;
  }
}

.preview-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.preview-label {
  font-size: 13px;
  color: $color-muted;
  flex-shrink: 0;
}

.preview-value {
  font-size: 14px;
  color: $color-primary;
  text-align: right;
  flex: 1;
  margin-left: 16px;
}

// 信息提示卡片
.info-card {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #EFF6FF;
  border-radius: 12px;
}

.info-icon {
  font-size: 18px;
  color: #3B82F6;
  flex-shrink: 0;
}

.info-text {
  font-size: 13px;
  color: #1E40AF;
  line-height: 1.5;
}

// 表单卡片
.form-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 16px;
}

.form-group {
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
  margin-bottom: 10px;
}

.form-input {
  width: 100%;
  height: 52px;
  padding: 0 16px;
  background: $color-input-bg;
  border: 1px solid transparent;
  border-radius: 12px;
  font-size: 15px;
  color: $color-primary;
  box-sizing: border-box;
  transition: border-color 0.2s, background-color 0.2s;

  &:focus {
    background: #fff;
    border-color: $color-accent;
  }
}

.picker-input {
  display: flex;
  align-items: center;
  justify-content: space-between;

  .placeholder {
    color: $color-muted;
  }

  .bi {
    font-size: 16px;
    color: $color-muted;
  }
}

// 只读信息区域
.readonly-info {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid $color-border;
}

.readonly-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;

  .bi {
    font-size: 18px;
    color: $color-accent;
  }
}

.readonly-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.readonly-content {
  background: $color-input-bg;
  border-radius: 12px;
  padding: 16px;
}

.readonly-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 10px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);

  &:last-child {
    border-bottom: none;
  }
}

.readonly-label {
  font-size: 13px;
  color: $color-muted;
  flex-shrink: 0;
}

.readonly-value {
  font-size: 14px;
  color: $color-primary;
  text-align: right;
  flex: 1;
  margin-left: 16px;
}

// 证件类型选项
.id-type-options {
  display: flex;
  gap: 12px;
}

.id-type-option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 12px;
  background: $color-input-bg;
  border: 2px solid transparent;
  border-radius: 12px;
  transition: all 0.2s;

  &.active {
    background: #FFF5F2;
    border-color: $color-accent;
  }

  .bi {
    font-size: 24px;
    color: $color-muted;
  }

  &.active .bi {
    color: $color-accent;
  }
}

.option-label {
  font-size: 12px;
  color: $color-secondary;
  text-align: center;

  .id-type-option.active & {
    color: $color-accent;
    font-weight: 500;
  }
}

// 条款同意
.terms-section {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid $color-border;
}

.terms-checkbox {
  width: 24px;
  height: 24px;
  border: 2px solid $color-border;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;

  &.checked {
    background: $color-accent;
    border-color: $color-accent;

    .bi {
      color: #fff;
      font-size: 14px;
    }
  }
}

.terms-text {
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.5;
}

.terms-link {
  color: $color-accent;
  font-weight: 500;
}

// 预估额度卡片
.estimate-card {
  display: flex;
  gap: 14px;
  padding: 18px;
  background: #EFF6FF;
  border-radius: 12px;
}

.estimate-icon {
  font-size: 20px;
  color: #3B82F6;
  flex-shrink: 0;
}

.estimate-content {
  flex: 1;
}

.estimate-title {
  font-size: 14px;
  font-weight: 600;
  color: #1E40AF;
  display: block;
  margin-bottom: 4px;
}

.estimate-desc {
  font-size: 13px;
  color: #3B82F6;
  line-height: 1.4;
}

// 安全提示
.security-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 18px 60px 16px;
  background: #F0FDF4;
  border-radius: 12px;

  .bi {
    font-size: 18px;
    color: $color-success;
  }
}

.notice-text {
  font-size: 13px;
  color: #166534;
}

// 底部按钮
.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
}

.btn-back {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0 24px;
  height: 52px;
  background: $color-bg;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  color: $color-secondary;

  .bi {
    font-size: 16px;
  }
}

.btn-continue, .btn-submit {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 52px;
  background: $color-accent;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #fff;

  .bi {
    font-size: 18px;
  }

  &.disabled {
    opacity: 0.5;
  }
}

// 图片上传区域
.upload-area {
  position: relative;
  width: 100%;
  height: 140px;
  background: $color-input-bg;
  border: 2px dashed $color-border;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;

  .bi {
    font-size: 32px;
    color: $color-muted;
  }
}

.upload-text {
  font-size: 14px;
  color: $color-muted;
}

.upload-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 24px;
    color: $color-accent;
  }
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

// 日期选择器
.picker-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.picker-container {
  width: 100%;
  background-color: #F2F2F7;
  border-radius: 12px 12px 0 0;
  max-height: 60vh;
  display: flex;
  flex-direction: column;
}

.picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  background-color: #fff;
  border-radius: 12px 12px 0 0;
  border-bottom: 1px solid $color-border;
}

.picker-cancel {
  font-size: 16px;
  color: $color-muted;
}

.picker-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
}

.picker-confirm {
  font-size: 16px;
  color: $color-accent;
  font-weight: 600;
}

.date-picker-body {
  display: flex;
  height: 240px;
  background: #fff;
}

.date-column {
  flex: 1;
  height: 100%;
  border-right: 1px solid rgba(0, 0, 0, 0.05);

  &:last-child {
    border-right: none;
  }
}

.date-item {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  font-size: 16px;
  color: $color-secondary;
  transition: all 0.2s;

  &.active {
    color: $color-accent;
    font-weight: 600;
    background: rgba(255, 107, 53, 0.08);
  }
}

// 信用卡账单上传区域
.statement-section {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid $color-border;
}

.statement-header {
  margin-bottom: 16px;
}

.statement-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;

  .form-label {
    margin-bottom: 0;
  }
}

.optional-badge {
  font-size: 11px;
  padding: 2px 8px;
  background: #E0E7FF;
  color: #4338CA;
  border-radius: 4px;
  font-weight: 500;
}

.statement-desc {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

.privacy-tip {
  display: flex;
  gap: 12px;
  padding: 14px 16px;
  background: #ECFDF5;
  border-radius: 10px;
  margin-bottom: 16px;

  .bi {
    font-size: 20px;
    color: $color-success;
    flex-shrink: 0;
  }
}

.privacy-tip-content {
  flex: 1;
}

.privacy-tip-title {
  font-size: 13px;
  font-weight: 600;
  color: #047857;
  display: block;
  margin-bottom: 4px;
}

.privacy-tip-text {
  font-size: 12px;
  color: #059669;
  line-height: 1.4;
}

.statement-requirements {
  background: $color-input-bg;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 16px;
}

.requirements-title {
  font-size: 13px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 10px;
}

.requirements-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.requirement-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: $color-secondary;

  .bi {
    font-size: 14px;
    color: $color-success;
  }
}

.statement-uploads {
  display: flex;
  gap: 12px;
}

.statement-upload-item {
  flex: 1;
  position: relative;
  aspect-ratio: 1;
  background: $color-input-bg;
  border: 2px dashed $color-border;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;

  .upload-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.upload-placeholder.small {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;

  .bi {
    font-size: 24px;
    color: $color-muted;
  }
}

.upload-month {
  font-size: 11px;
  color: $color-muted;
}

.delete-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 14px;
    color: #fff;
  }
}

.statement-note {
  display: block;
  margin-top: 12px;
  font-size: 12px;
  color: $color-muted;
  text-align: center;
}
</style>
