<template>
  <view v-if="visible" class="region-picker-mask" @click="close">
    <view class="region-picker" @click.stop>
      <view class="picker-header">
        <text class="picker-title">{{ t('user.selectRegion') }}</text>
        <text class="picker-close" @click="close">×</text>
      </view>
      <scroll-view class="picker-content" scroll-y>
        <view v-if="loading" class="loading-container">
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>
        <view
          v-else
          v-for="region in regions"
          :key="region.code"
          class="region-item"
          :class="{ active: region.code === currentRegion }"
          @click="selectRegion(region)"
        >
          <image class="region-flag" :src="getFlagUrl(region.flag)" mode="aspectFit" />
          <text class="region-name">{{ region.name }}</text>
          <text v-if="region.code === currentRegion" class="region-check">✓</text>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore, type CountryOption } from '@/store/modules/app'

const { t } = useI18n()
const appStore = useAppStore()

const props = defineProps<{
  visible: boolean
  currentRegion: string
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'select', region: { code: string; locale: string }): void
}>()

const loading = ref(false)

// 使用 API 获取的国家列表
const regions = computed(() => {
  return appStore.availableCountries
})

// 当打开选择器时加载数据
watch(() => props.visible, async (newVisible) => {
  if (newVisible && appStore.availableCountries.length === 0) {
    loading.value = true
    await appStore.fetchAvailableCountries()
    loading.value = false
  }
})

// 使用 flagcdn.com 的国旗图片
function getFlagUrl(flag: string): string {
  if (!flag) return ''
  // 转换特殊代码
  const flagCode = flag === 'UK' ? 'gb' : flag.toLowerCase()
  return `https://flagcdn.com/w40/${flagCode}.png`
}

function close() {
  emit('update:visible', false)
}

function selectRegion(region: CountryOption) {
  emit('select', {
    code: region.code,
    locale: region.locale || 'en-US',
  })
  close()
}
</script>

<style lang="scss" scoped>
.region-picker-mask {
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

.region-picker {
  width: 100%;
  max-width: 500px;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  // 底部预留 TabBar 高度 + 安全区域
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

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 0;
}

.loading-text {
  color: #999;
  font-size: 14px;
}

.region-item {
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

.region-flag {
  width: 32px;
  height: 22px;
  margin-right: 14px;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.region-name {
  flex: 1;
  font-size: 16px;
  color: #333;
}

.region-check {
  font-size: 18px;
  color: #FF6B35;
  font-weight: bold;
}
</style>
