<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="isEdit ? t('address.editAddress') : t('address.addAddress')">
      <template #right>
        <view class="nav-action-text" :class="{ active: isFormValid }" @click="handleSave">
          <text>{{ t('action.save') }}</text>
        </view>
      </template>
    </NavBar>

    <!-- 表单内容 -->
    <scroll-view scroll-y class="form-container">
      <!-- 姓名 -->
      <view class="form-item">
        <view class="form-label">{{ t('address.name') }}</view>
        <input
          class="form-input"
          type="text"
          v-model="form.name"
          :placeholder="t('address.namePlaceholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 国家/地区 -->
      <view class="form-item form-item-select" @click="showCountryPicker = true">
        <view class="form-label">{{ t('address.country') }}</view>
        <view class="form-select-row">
          <view class="form-value-with-flag" :class="{ placeholder: !form.country }">
            <image v-if="form.countryCode" class="country-flag" :src="getFlagUrl(form.countryCode)" mode="aspectFit" />
            <text>{{ form.country || t('address.countryPlaceholder') }}</text>
          </view>
          <text class="bi bi-chevron-right"></text>
        </view>
      </view>

      <!-- 街道地址 -->
      <view class="form-item">
        <view class="form-label">{{ t('address.street') }}</view>
        <input
          class="form-input"
          type="text"
          v-model="form.street"
          :placeholder="t('address.streetPlaceholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 街道地址2 (可选) -->
      <view class="form-item">
        <view class="form-label">{{ t('address.street2') }}{{ t('address.optional') }}</view>
        <input
          class="form-input"
          type="text"
          v-model="form.street2"
          :placeholder="t('address.street2Placeholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 城市 -->
      <view class="form-item">
        <view class="form-label">{{ t('address.city') }}</view>
        <input
          class="form-input"
          type="text"
          v-model="form.city"
          :placeholder="t('address.cityPlaceholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 州/省/地区 -->
      <view class="form-item form-item-select" @click="openStatePicker">
        <view class="form-label">{{ t('address.state') }}</view>
        <view class="form-select-row">
          <text class="form-value" :class="{ placeholder: !form.state }">
            {{ form.state || t('address.statePlaceholder') }}
          </text>
          <text class="bi bi-chevron-right"></text>
        </view>
      </view>

      <!-- 邮政编码 -->
      <view class="form-item">
        <view class="form-label">{{ t('address.postalCode') }}</view>
        <input
          class="form-input"
          type="text"
          v-model="form.postalCode"
          :placeholder="t('address.postalCodePlaceholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 电话 -->
      <view class="form-item">
        <view class="form-label">{{ t('address.phone') }}</view>
        <input
          class="form-input"
          type="tel"
          v-model="form.phone"
          :placeholder="t('address.phonePlaceholder')"
          :adjust-position="false"
        />
      </view>

      <!-- 设为默认地址 -->
      <view class="checkbox-item" @click="form.isDefault = !form.isDefault">
        <view class="checkbox" :class="{ checked: form.isDefault }">
          <text v-if="form.isDefault" class="bi bi-check"></text>
        </view>
        <text class="checkbox-label">{{ t('address.setDefault') }}</text>
      </view>

      <!-- 删除按钮（仅编辑模式显示） -->
      <view v-if="isEdit" class="delete-section">
        <button class="delete-btn" @click="handleDelete">
          <text class="bi bi-archive"></text>
          <text>{{ t('address.deleteAddress') }}</text>
        </button>
      </view>
    </scroll-view>

    <!-- 国家选择器 -->
    <view v-if="showCountryPicker" class="picker-mask" @click="showCountryPicker = false">
      <view class="picker-container" @click.stop>
        <view class="picker-header">
          <text class="picker-cancel" @click="showCountryPicker = false">{{ t('common.cancel') }}</text>
          <text class="picker-title">{{ t('address.selectCountry') }}</text>
          <text class="picker-confirm" @click="confirmCountry">{{ t('common.confirm') }}</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view
            v-for="country in countries"
            :key="country.code"
            class="picker-item"
            :class="{ active: tempCountry === country.name }"
            @click="tempCountry = country.name; tempCountryCode = country.code"
          >
            <view class="country-option">
              <image class="country-flag" :src="getFlagUrl(country.code)" mode="aspectFit" />
              <text>{{ country.name }}</text>
            </view>
            <text v-if="tempCountry === country.name" class="bi bi-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 州/省选择器 -->
    <view v-if="showStatePicker" class="picker-mask" @click="showStatePicker = false">
      <view class="picker-container" @click.stop>
        <view class="picker-header">
          <text class="picker-cancel" @click="showStatePicker = false">{{ t('common.cancel') }}</text>
          <text class="picker-title">{{ t('address.selectState') }}</text>
          <text class="picker-confirm" @click="confirmState">{{ t('common.confirm') }}</text>
        </view>
        <scroll-view scroll-y class="picker-list">
          <view
            v-for="state in currentStates"
            :key="state"
            class="picker-item"
            :class="{ active: tempState === state }"
            @click="tempState = state"
          >
            <text>{{ state }}</text>
            <text v-if="tempState === state" class="bi bi-check"></text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 删除确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      :title="t('address.deleteAddress')"
      :content="t('address.deleteConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteDialog = $event"
      @confirm="confirmDelete"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getAddresses, addAddress, updateAddress, deleteAddress, type Address } from '@/api/user'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import countriesData from '@/utils/countries.json'
import statesData from '@/utils/states.json'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 类型定义
interface Country {
  id: number
  name: string
  iso2: string
  emoji: string
  translations: Record<string, string>
}

interface State {
  id: number
  name: string
  country_code: string
}

const isEdit = ref(false)
const addressId = ref<number | null>(null)
const loading = ref(false)

const form = reactive({
  name: '',
  country: '',
  countryCode: '',
  street: '',
  street2: '',
  city: '',
  state: '',
  postalCode: '',
  phone: '',
  isDefault: false,
})

// 表单是否有效（用于保存按钮状态）
const isFormValid = computed(() => {
  return !!(
    form.name.trim() &&
    form.country &&
    form.street.trim() &&
    form.city.trim() &&
    form.phone.trim()
  )
})

// 选择器状态
const showCountryPicker = ref(false)
const showStatePicker = ref(false)
const showDeleteDialog = ref(false)
const tempCountry = ref('')
const tempCountryCode = ref('')
const tempState = ref('')

// 语言映射：将应用 locale 映射到 JSON 中的翻译 key
function getTranslationKey(): string {
  const locale = appStore.locale
  // 特殊映射（语言代码与 countries.json 翻译键不一致的情况）
  const mapping: Record<string, string> = {
    'zh-TW': 'zh-CN', // 繁体中文使用简体中文翻译（JSON没有zh-TW）
    'zh-CN': 'zh-CN',
  }
  // 自动提取语言部分作为 fallback（如 ko-KR -> ko）
  return mapping[locale] || locale.split('-')[0] || 'en'
}

// 获取国家的本地化名称
function getCountryName(country: Country): string {
  const key = getTranslationKey()
  // 优先使用翻译，如果没有则使用原始名称
  return country.translations?.[key] || country.name
}

// 获取国旗图片URL
function getFlagUrl(code: string): string {
  if (!code) return ''
  const flagCode = code === 'UK' ? 'gb' : code.toLowerCase()
  return `https://flagcdn.com/w40/${flagCode}.png`
}

// 国家列表（按本地化名称排序）
const countries = computed(() => {
  return (countriesData as Country[])
    .map(c => ({
      code: c.iso2,
      name: getCountryName(c),
      originalName: c.name,
    }))
    .sort((a, b) => a.name.localeCompare(b.name))
})

// 当前国家的州/省列表
const currentStates = computed(() => {
  if (!form.countryCode) return []
  return (statesData as State[])
    .filter(s => s.country_code === form.countryCode)
    .map(s => s.name)
    .sort((a, b) => a.localeCompare(b))
})

onLoad((options: any) => {
  if (options?.id) {
    isEdit.value = true
    addressId.value = Number(options.id)
    loadAddress()
  } else {
    // 添加模式：根据用户选择的地区设置默认国家
    initDefaultCountry()
  }
})

// 根据用户的地区设置初始化默认国家
function initDefaultCountry() {
  const userRegion = appStore.region // 如 'US', 'CA', 'TW', 'JP' 等
  if (!userRegion) return

  // 特殊处理：UK 在 countries.json 中是 GB
  const countryCode = userRegion === 'UK' ? 'GB' : userRegion

  // 在国家列表中查找对应的国家
  const country = (countriesData as Country[]).find(c => c.iso2 === countryCode)
  if (country) {
    form.countryCode = country.iso2
    form.country = getCountryName(country)
  }
}

async function loadAddress() {
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      const addr = res.data?.find((a: Address) => a.id === addressId.value)
      if (addr) {
        form.name = addr.name
        form.country = addr.country
        form.countryCode = addr.countryCode || ''
        form.street = addr.street
        form.street2 = ''
        form.city = addr.city || ''
        form.state = addr.state || ''
        form.postalCode = addr.postalCode || ''
        form.phone = addr.phone
        form.isDefault = addr.isDefault
      }
    }
  } catch (e) {
    console.error('Failed to load address:', e)
  }
}

function confirmCountry() {
  form.country = tempCountry.value
  form.countryCode = tempCountryCode.value
  form.state = '' // 清空州/省
  showCountryPicker.value = false
}

function confirmState() {
  form.state = tempState.value
  showStatePicker.value = false
}

function openStatePicker() {
  if (!form.countryCode) {
    toast.warning(t('address.countryRequired'))
    return
  }
  if (currentStates.value.length === 0) {
    return
  }
  showStatePicker.value = true
}

function validateForm(): boolean {
  if (!form.name.trim()) {
    toast.warning(t('address.nameRequired'))
    return false
  }
  if (!form.country) {
    toast.warning(t('address.countryRequired'))
    return false
  }
  if (!form.street.trim()) {
    toast.warning(t('address.streetRequired'))
    return false
  }
  if (!form.city.trim()) {
    toast.warning(t('address.cityRequired'))
    return false
  }
  if (!form.phone.trim()) {
    toast.warning(t('address.phoneRequired'))
    return false
  }
  return true
}

async function handleSave() {
  if (!validateForm()) return
  if (loading.value) return

  loading.value = true
  try {
    // 后端使用下划线命名
    const data = {
      name: form.name.trim(),
      country: form.country,
      country_code: form.countryCode,
      street: form.street.trim() + (form.street2 ? ', ' + form.street2.trim() : ''),
      city: form.city.trim(),
      state: form.state,
      postal_code: form.postalCode.trim(),
      phone: form.phone.trim(),
      is_default: form.isDefault,
    }

    if (isEdit.value && addressId.value) {
      await updateAddress(addressId.value, data)
      toast.success(t('address.updateSuccess'))
    } else {
      await addAddress(data)
      toast.success(t('address.addSuccess'))
    }

    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  } catch (e: any) {
    toast.error(e.message || t('common.operationFailed'))
  } finally {
    loading.value = false
  }
}

function handleDelete() {
  showDeleteDialog.value = true
}

async function confirmDelete() {
  if (addressId.value) {
    try {
      await deleteAddress(addressId.value)
      toast.success(t('address.deleteSuccess'))
      setTimeout(() => {
        uni.navigateBack()
      }, 1500)
    } catch (e) {
      toast.error(t('common.operationFailed'))
    }
  }
}

function goBack() {
  uni.navigateBack()
}
</script>

<style lang="scss" scoped>
$primary-color: #FF6B35;
$text-color: #000;
$text-secondary: #333;
$text-muted: #8E8E93;
$border-color: #C6C6C8;
$bg-color: #F2F2F7;
$danger-color: #FF6B35;

.page {
  min-height: 100vh;
  background-color: $bg-color;
}

// 导航栏
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 20px 16px;
  background-color: $bg-color;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left-group {
  display: flex;
  align-items: center;
  gap: 4px;

  .bi {
    font-size: 22px;
    color: $text-color;
  }
}

.nav-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-color;
}

.nav-right {
  display: flex;
  align-items: center;
}

.save-text {
  font-size: 17px;
  color: #C7C7CC;
  font-weight: 400;

  &.active {
    color: $text-color;
  }
}

// 表单容器
.form-container {
  // height: calc(100vh - 84px);
  width: auto;
  padding: 16px;
}

// 表单项 - 带边框的白色盒子
.form-item {
  background-color: #fff;
  border: 1px solid $border-color;
  border-radius: 10px;
  padding: 10px 16px;
  margin-bottom: 12px;
}

.form-item-select {
  cursor: pointer;
}

.form-label {
  font-size: 13px;
  color: $text-muted;
  margin-bottom: 4px;
}

.form-input {
  width: 100%;
  height: 24px;
  border: none;
  padding: 0;
  font-size: 17px;
  color: $text-color;
  box-sizing: border-box;
  background-color: transparent;

  &::placeholder {
    color: #C7C7CC;
  }
}

// 选择器行
.form-select-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 24px;

  .bi {
    font-size: 16px;
    color: #C7C7CC;
  }
}

.form-value {
  font-size: 17px;
  color: $text-color;

  &.placeholder {
    color: #C7C7CC;
  }
}

// 复选框
.checkbox-item {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  padding: 0 4px;
}

.checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid #C7C7CC;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;

  &.checked {
    background-color: $primary-color;
    border-color: $primary-color;

    .bi {
      color: #fff;
      font-size: 14px;
      font-weight: bold;
    }
  }
}

.checkbox-label {
  font-size: 17px;
  color: $text-color;
}

// 删除按钮
.delete-section {
  margin-top: 32px;
}

.delete-btn {
  width: 100%;
  height: 50px;
  background-color: #fff;
  border: 1px solid $border-color;
  border-radius: 10px;
  color: $danger-color;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 17px;

  .bi {
    font-size: 18px;
  }
}

// 选择器
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
  padding: 12px 16px;
  background-color: #fff;
  border-radius: 12px 12px 0 0;
}

.picker-cancel {
  font-size: 17px;
  color: $primary-color;
}

.picker-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-color;
}

.picker-confirm {
  font-size: 17px;
  color: $primary-color;
  font-weight: 600;
}

.picker-list {
  flex: 1;
  max-height: 50vh;
  background-color: #fff;
  margin-top: 8px;
}

.picker-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  font-size: 17px;
  color: $text-color;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);

  &.active {
    color: $primary-color;
  }

  .bi {
    font-size: 20px;
    color: $primary-color;
  }
}

// 国家选项
.country-option {
  display: flex;
  align-items: center;
  gap: 10px;
}

// 国旗图片
.country-flag {
  width: 24px;
  height: 16px;
  border-radius: 2px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

// 带国旗的表单值
.form-value-with-flag {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  color: $text-color;

  &.placeholder {
    color: #C7C7CC;
  }

  .country-flag {
    width: 20px;
    height: 14px;
  }
}
</style>
