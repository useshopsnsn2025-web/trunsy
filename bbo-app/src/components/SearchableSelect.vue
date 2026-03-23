<template>
  <view v-if="visible" class="searchable-select-mask" @click="close">
    <view class="searchable-select" @click.stop>
      <!-- 头部 -->
      <view class="select-header">
        <text class="select-title">{{ title || t('common.pleaseSelect') }}</text>
        <text class="select-close bi bi-x-lg" @click="close"></text>
      </view>

      <!-- 搜索框（选项超过阈值时显示） -->
      <view v-if="showSearch" class="search-box">
        <text class="bi bi-search search-icon"></text>
        <input
          class="search-input"
          type="text"
          v-model="searchKeyword"
          :placeholder="searchPlaceholder || t('common.search')"
          :focus="autoFocus"
          @input="onSearchInput"
        />
        <text
          v-if="searchKeyword"
          class="bi bi-x-circle-fill clear-icon"
          @click="clearSearch"
        ></text>
      </view>

      <!-- 选项列表 -->
      <scroll-view class="select-content" scroll-y :scroll-into-view="scrollIntoView">
        <view v-if="loading" class="loading-container">
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <view v-else-if="filteredOptions.length === 0" class="empty-container">
          <text class="bi bi-inbox empty-icon"></text>
          <text class="empty-text">{{ t('common.noData') }}</text>
        </view>

        <view v-else class="options-list">
          <view
            v-for="(option, index) in filteredOptions"
            :key="getOptionValue(option)"
            :id="'option-' + index"
            class="option-item"
            :class="{ active: isSelected(option), disabled: option.disabled }"
            @click="selectOption(option)"
          >
            <!-- 图标/图片（可选） -->
            <image
              v-if="option.icon || option.image"
              class="option-icon"
              :src="option.icon || option.image"
              mode="aspectFit"
            />

            <!-- 选项内容 -->
            <view class="option-content">
              <text class="option-label">{{ getOptionLabel(option) }}</text>
              <text v-if="option.description" class="option-desc">{{ option.description }}</text>
            </view>

            <!-- 选中标记 -->
            <text v-if="isSelected(option)" class="bi bi-check-lg check-icon"></text>
          </view>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// 选项类型
export interface SelectOption {
  label?: string
  value?: string | number
  name?: string  // 兼容后端返回的 name 字段
  id?: string | number  // 兼容后端返回的 id 字段
  icon?: string
  image?: string
  description?: string
  disabled?: boolean
  [key: string]: any
}

const props = withDefaults(defineProps<{
  visible: boolean
  title?: string
  options: SelectOption[]
  modelValue?: string | number | null
  labelKey?: string  // 自定义 label 字段名
  valueKey?: string  // 自定义 value 字段名
  searchPlaceholder?: string
  searchThreshold?: number  // 超过多少个选项显示搜索框，默认 10
  loading?: boolean
  autoFocus?: boolean
}>(), {
  searchThreshold: 10,
  autoFocus: true,
  loading: false,
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'update:modelValue', value: string | number | null): void
  (e: 'select', option: SelectOption): void
  (e: 'close'): void
}>()

const searchKeyword = ref('')
const scrollIntoView = ref('')

// 是否显示搜索框
const showSearch = computed(() => {
  return props.options.length > props.searchThreshold
})

// 获取选项的 label
function getOptionLabel(option: SelectOption): string {
  if (props.labelKey && option[props.labelKey]) {
    return option[props.labelKey]
  }
  return option.label || option.name || String(option.value || option.id || '')
}

// 获取选项的 value
function getOptionValue(option: SelectOption): string | number {
  if (props.valueKey && option[props.valueKey] !== undefined) {
    return option[props.valueKey]
  }
  return option.value ?? option.id ?? option.name ?? ''
}

// 过滤后的选项
const filteredOptions = computed(() => {
  if (!searchKeyword.value.trim()) {
    return props.options
  }

  const keyword = searchKeyword.value.toLowerCase().trim()
  return props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase()
    const desc = (option.description || '').toLowerCase()
    return label.includes(keyword) || desc.includes(keyword)
  })
})

// 是否选中
function isSelected(option: SelectOption): boolean {
  return getOptionValue(option) === props.modelValue
}

// 选择选项
function selectOption(option: SelectOption) {
  if (option.disabled) return

  const value = getOptionValue(option)
  emit('update:modelValue', value)
  emit('select', option)
  close()
}

// 关闭
function close() {
  searchKeyword.value = ''
  emit('update:visible', false)
  emit('close')
}

// 清除搜索
function clearSearch() {
  searchKeyword.value = ''
}

// 搜索输入
function onSearchInput() {
  // 可以在这里添加防抖逻辑
}

// 打开时滚动到当前选中项
watch(() => props.visible, (newVisible) => {
  if (newVisible && props.modelValue !== null && props.modelValue !== undefined) {
    nextTick(() => {
      const index = props.options.findIndex(opt => getOptionValue(opt) === props.modelValue)
      if (index > -1) {
        scrollIntoView.value = `option-${index}`
      }
    })
  }
})
</script>

<style lang="scss" scoped>
$primary-color: #FF6B35;

.searchable-select-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 999;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.searchable-select {
  width: 100%;
  max-width: 500px;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  padding-bottom: calc(env(safe-area-inset-bottom) + 16px);
}

.select-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
}

.select-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.select-close {
  font-size: 18px;
  color: #999;
  padding: 4px 8px;
}

.search-box {
  display: flex;
  align-items: center;
  margin: 12px 16px;
  padding: 10px 14px;
  background-color: #f5f5f5;
  border-radius: 10px;
}

.search-icon {
  font-size: 16px;
  color: #999;
  margin-right: 10px;
}

.search-input {
  flex: 1;
  font-size: 15px;
  color: #333;
  background: transparent;
}

.clear-icon {
  font-size: 16px;
  color: #ccc;
  padding: 4px;
}

.select-content {
  flex: 1;
  overflow-y: auto;
}

.loading-container,
.empty-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 60px 0;
}

.loading-text {
  color: #999;
  font-size: 14px;
}

.empty-icon {
  font-size: 48px;
  color: #ddd;
  margin-bottom: 12px;
}

.empty-text {
  color: #999;
  font-size: 14px;
}

.options-list {
  padding: 0 0 8px;
}

.option-item {
  display: flex;
  align-items: center;
  padding: 14px 20px;
  border-bottom: 1px solid #f5f5f5;

  &:active {
    background-color: #f9f9f9;
  }

  &.active {
    background-color: #fff8f5;
  }

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }
}

.option-icon {
  width: 32px;
  height: 32px;
  margin-right: 14px;
  border-radius: 6px;
}

.option-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.option-label {
  font-size: 16px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.option-desc {
  font-size: 13px;
  color: #999;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.check-icon {
  font-size: 18px;
  color: $primary-color;
  margin-left: 12px;
}
</style>
