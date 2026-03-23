<template>
  <view class="search-bar" :class="{ 'search-bar--focus': isFocused }">
    <view class="search-bar__inner" @click="handleClick">
      <text class="bi bi-search search-bar__icon"></text>
      <input
        v-if="editable"
        class="search-bar__input"
        type="text"
        :value="modelValue"
        :placeholder="placeholder"
        :focus="focus"
        confirm-type="search"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @confirm="handleConfirm"
      />
      <text v-else class="search-bar__placeholder">{{ placeholder }}</text>
      <view
        v-if="editable && modelValue && showClear"
        class="search-bar__clear"
        @click.stop="handleClear"
      >
        <text class="bi bi-x-circle-fill"></text>
      </view>
    </view>
    <text
      v-if="showCancel && (isFocused || modelValue)"
      class="search-bar__cancel"
      @click="handleCancel"
    >
      {{ cancelText }}
    </text>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Props {
  modelValue?: string
  placeholder?: string
  editable?: boolean
  focus?: boolean
  showCancel?: boolean
  showClear?: boolean
  cancelText?: string
  backgroundColor?: string
  borderRadius?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: '',
  editable: true,
  focus: false,
  showCancel: true,
  showClear: true,
  cancelText: '',
  backgroundColor: '#f5f5f5',
  borderRadius: '20px',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'click'): void
  (e: 'focus'): void
  (e: 'blur'): void
  (e: 'confirm', value: string): void
  (e: 'clear'): void
  (e: 'cancel'): void
}>()

const isFocused = ref(false)

// 计算取消按钮文字
const cancelText = props.cancelText || t('common.cancel')

function handleClick() {
  if (!props.editable) {
    emit('click')
  }
}

function handleInput(e: any) {
  emit('update:modelValue', e.detail.value)
}

function handleFocus() {
  isFocused.value = true
  emit('focus')
}

function handleBlur() {
  isFocused.value = false
  emit('blur')
}

function handleConfirm() {
  emit('confirm', props.modelValue)
}

function handleClear() {
  emit('update:modelValue', '')
  emit('clear')
}

function handleCancel() {
  emit('update:modelValue', '')
  isFocused.value = false
  emit('cancel')
}
</script>

<style lang="scss" scoped>
.search-bar {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  gap: 12px;

  &__inner {
    flex: 1;
    height: 40px;
    background-color: #f5f5f5;
    border-radius: 20px;
    display: flex;
    align-items: center;
    padding: 0 14px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
  }

  &--focus &__inner {
    background-color: #fff;
    border-color: #FF6B35;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.15);
  }

  &__icon {
    font-size: 18px;
    color: #999;
    margin-right: 10px;
    flex-shrink: 0;
  }

  &--focus &__icon {
    color: #FF6B35;
  }

  &__input {
    flex: 1;
    height: 100%;
    font-size: 14px;
    color: #333;
    background: transparent;
  }

  &__placeholder {
    flex: 1;
    font-size: 14px;
    color: #999;
  }

  &__clear {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 8px;
    flex-shrink: 0;

    .bi {
      font-size: 16px;
      color: #ccc;
    }
  }

  &__cancel {
    font-size: 14px;
    color: #FF6B35;
    font-weight: 500;
    flex-shrink: 0;
  }
}
</style>
