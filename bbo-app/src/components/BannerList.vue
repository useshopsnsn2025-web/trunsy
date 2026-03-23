<template>
  <view v-if="banners.length > 0" class="banner-list-wrapper" :class="[`banner-list-${size}`]">
    <BannerItem
      v-for="banner in banners"
      :key="banner.id"
      :banner="banner"
      :size="size"
      class="banner-list-item"
      @click="onBannerClick"
    />
  </view>
</template>

<script setup lang="ts">
import BannerItem, { type BannerData } from './BannerItem.vue'

withDefaults(defineProps<{
  banners: BannerData[]
  size?: 'large' | 'medium' | 'small'
}>(), {
  size: 'medium'
})

const emit = defineEmits<{
  click: [banner: BannerData]
}>()

function onBannerClick(banner: BannerData) {
  emit('click', banner)
}
</script>

<style lang="scss" scoped>
.banner-list-wrapper {
  margin: 16px 0;
}

.banner-list-item {
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.banner-list-small .banner-list-item {
  margin-bottom: 10px;
}
</style>
