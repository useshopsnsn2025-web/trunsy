<template>
  <view v-if="banners.length > 0" class="banner-swiper-wrapper">
    <swiper
      class="banner-swiper"
      :indicator-dots="banners.length > 1"
      indicator-color="rgba(255,255,255,0.5)"
      indicator-active-color="#ffffff"
      :autoplay="autoplay"
      :interval="interval"
      :circular="circular"
      @change="onChange"
    >
      <swiper-item v-for="banner in banners" :key="banner.id">
        <BannerItem :banner="banner" size="large" @click="onBannerClick" />
      </swiper-item>
    </swiper>
  </view>
</template>

<script setup lang="ts">
import BannerItem, { type BannerData } from './BannerItem.vue'

withDefaults(defineProps<{
  banners: BannerData[]
  autoplay?: boolean
  interval?: number
  circular?: boolean
}>(), {
  autoplay: true,
  interval: 4000,
  circular: true
})

const emit = defineEmits<{
  change: [index: number]
  click: [banner: BannerData]
}>()

function onChange(e: any) {
  emit('change', e.detail.current)
}

function onBannerClick(banner: BannerData) {
  emit('click', banner)
}
</script>

<style lang="scss" scoped>
.banner-swiper-wrapper {
  width: 100%;
  
  box-sizing: border-box;
  margin-bottom: 8px;
}

.banner-swiper {
  width: 100%;
  height: 180px;
  overflow: hidden;
}
</style>
