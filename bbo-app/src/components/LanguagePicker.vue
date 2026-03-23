<template>
  <view v-if="visible" class="language-picker-mask" @click="close">
    <view class="language-picker" @click.stop>
      <view class="picker-header">
        <text class="picker-title">{{ t('user.selectLanguage') }}</text>
        <text class="picker-close" @click="close">×</text>
      </view>
      <scroll-view class="picker-content" scroll-y>
        <view
          v-for="lang in supportedLanguages"
          :key="lang.code"
          class="language-item"
          :class="{ active: lang.code === currentLocale }"
          @click="selectLanguage(lang)"
        >
          <image
            v-if="lang.flag"
            class="language-flag-img"
            :src="getFlagUrl(lang.flag)"
            mode="aspectFit"
            @error="handleFlagError"
          />
          <text v-else class="language-flag">🌐</text>
          <view class="language-info">
            <text class="language-name">{{ lang.nativeName }}</text>
            <text class="language-name-sub">{{ lang.name }}</text>
          </view>
          <text v-if="lang.code === currentLocale" class="language-check">✓</text>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore, type LanguageOption } from '@/store/modules/app'

const { t } = useI18n()
const appStore = useAppStore()

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'select', locale: string): void
}>()

// 使用 store 中过滤后的语言列表（只显示有 UI 翻译的语言）
const supportedLanguages = computed(() => appStore.supportedLanguages)

// 当前语言
const currentLocale = computed(() => appStore.locale)

function close() {
  emit('update:visible', false)
}

function selectLanguage(lang: LanguageOption) {
  appStore.setLocale(lang.code)
  emit('select', lang.code)
  close()
}

// 获取国旗图片 URL
function getFlagUrl(code: string) {
  if (!code) return ''
  return `https://flagcdn.com/w40/${code.toLowerCase()}.png`
}

// 处理图片加载失败
function handleFlagError(e: any) {
  // 隐藏加载失败的图片
  if (e.target) {
    e.target.style.display = 'none'
  }
}
</script>

<style lang="scss" scoped>
.language-picker-mask {
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

.language-picker {
  width: 100%;
  max-width: 500px;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  padding-bottom: calc(66px + env(safe-area-inset-bottom));
}

.picker-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
}

.picker-title {
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.picker-close {
  font-size: 24px;
  color: #999;
  padding: 0 8px;
}

.picker-content {
  flex: 1;
  overflow-y: auto;
}

.language-item {
  display: flex;
  align-items: center;
  padding: 14px 20px;
  border-bottom: 1px solid #f5f5f5;

  &:active {
    background-color: #f9f9f9;
  }

  &.active {
    background-color: #fff5f0;
  }
}

.language-flag {
  font-size: 24px;
  margin-right: 14px;
}

.language-flag-img {
  width: 32px;
  height: 24px;
  margin-right: 14px;
  border-radius: 2px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.language-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.language-name {
  font-size: 16px;
  color: #333;
}

.language-name-sub {
  font-size: 13px;
  color: #999;
}

.language-check {
  font-size: 18px;
  color: #FF6B35;
  font-weight: bold;
}
</style>
