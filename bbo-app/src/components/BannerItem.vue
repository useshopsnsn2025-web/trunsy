<template>
  <!-- 背景色型Banner -->
  <view
    v-if="banner.displayType === 2"
    class="banner-item banner-color-type"
    :class="[sizeClass]"
    :style="bgStyle"
    @click="handleClick"
  >
    <view class="banner-color-content">
      <view class="banner-text-area">
        <text v-if="banner.title" class="banner-title" :style="{ color: banner.textColor }">{{ banner.title }}</text>
        <text v-if="banner.subtitle" class="banner-subtitle" :style="{ color: banner.textColor }">{{ banner.subtitle }}</text>
        <text v-if="banner.content" class="banner-desc" :style="{ color: banner.textColor }">{{ banner.content }}</text>
        <view v-if="banner.buttonText" class="banner-button" :class="'btn-style-' + banner.buttonStyle" :style="buttonStyle">
          <text>{{ banner.buttonText }}</text>
        </view>
      </view>
      <view v-if="banner.productImages && banner.productImages.length > 0" class="banner-products">
        <image
          v-for="(img, idx) in banner.productImages.slice(0, 3)"
          :key="idx"
          :src="img"
          class="banner-product-img"
          mode="aspectFill"
        />
      </view>
    </view>
  </view>

  <!-- 图片型Banner -->
  <view v-else class="banner-item banner-image-type" :class="[sizeClass]" @click="handleClick">
    <image class="banner-image" :src="banner.image" mode="aspectFill" />
    <view v-if="banner.title || banner.subtitle" class="banner-overlay">
      <text v-if="banner.title" class="banner-title">{{ banner.title }}</text>
      <text v-if="banner.subtitle" class="banner-subtitle">{{ banner.subtitle }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Banner数据类型
export interface BannerData {
  id: number
  title: string
  subtitle: string
  content: string
  buttonText: string
  image: string
  displayType: number // 1=图片型, 2=背景色型
  bgColor: string | null
  bgGradient: string | null
  textColor: string
  buttonStyle: number // 1=实心黑, 2=实心主题色, 3=边框, 4=自定义颜色
  buttonColor: string | null // 自定义按钮背景色
  productImages: string[]
  linkType: number
  linkValue: string
}

const props = withDefaults(defineProps<{
  banner: BannerData
  size?: 'large' | 'medium' | 'small' // 大(轮播)、中(横幅)、小(底部)
}>(), {
  size: 'medium'
})

const emit = defineEmits<{
  click: [banner: BannerData]
}>()

// 尺寸class
const sizeClass = computed(() => `banner-${props.size}`)

// 背景样式
const bgStyle = computed(() => {
  if (props.banner.bgGradient) {
    return { background: props.banner.bgGradient }
  }
  return { background: props.banner.bgColor || '#f5f5f5' }
})

// 按钮样式（自定义颜色）
const buttonStyle = computed(() => {
  if (props.banner.buttonStyle === 4 && props.banner.buttonColor) {
    return { background: props.banner.buttonColor, color: '#fff' }
  }
  return {}
})

// 点击处理
function handleClick() {
  if (!props.banner.linkType || props.banner.linkType === 0) {
    emit('click', props.banner)
    return
  }

  switch (props.banner.linkType) {
    case 1: // 商品详情
      if (props.banner.linkValue) {
        uni.navigateTo({ url: `/pages/goods/detail?id=${props.banner.linkValue}` })
      }
      break
    case 2: // 分类页
      if (props.banner.linkValue) {
        uni.navigateTo({ url: `/pages/category/brands?id=${props.banner.linkValue}` })
      } else {
        uni.switchTab({ url: '/pages/category/index' })
      }
      break
    case 3: // 活动详情
      if (props.banner.linkValue) {
        uni.navigateTo({ url: `/pages/promotion/detail?id=${props.banner.linkValue}` })
      }
      break
    case 4: // 外部链接
      if (props.banner.linkValue) {
        // #ifdef H5
        window.open(props.banner.linkValue, '_blank')
        // #endif
        // #ifndef H5
        uni.navigateTo({ url: `/pages/webview/index?url=${encodeURIComponent(props.banner.linkValue)}` })
        // #endif
      }
      break
    case 5: // 品牌页
      if (props.banner.linkValue) {
        uni.navigateTo({ url: `/pages/brand/models?id=${props.banner.linkValue}` })
      }
      break
    case 6: // 搜索结果
      if (props.banner.linkValue) {
        uni.navigateTo({ url: `/pages/search/index?keyword=${encodeURIComponent(props.banner.linkValue)}` })
      }
      break
    case 7: // 优惠券中心
      uni.navigateTo({ url: '/pages/coupon/index' })
      break
    case 8: // 购物车
      uni.switchTab({ url: '/pages/cart/index' })
      break
    case 9: // 钱包
      uni.navigateTo({ url: '/pages/wallet/index' })
      break
    case 10: // 收藏夹
      uni.navigateTo({ url: '/pages/favorites/index' })
      break
    case 11: // 订单列表
      uni.navigateTo({ url: '/pages/order/list' })
      break
    case 12: // 发布商品
      uni.navigateTo({ url: '/pages/publish/index' })
      break
    case 13: // 信用购
      uni.navigateTo({ url: '/pages/credit/index' })
      break
    case 14: // 卖家中心
      uni.navigateTo({ url: '/pages/sell/index' })
      break
    case 15: // 自定义页面
      if (props.banner.linkValue) {
        uni.navigateTo({ url: props.banner.linkValue })
      }
      break
  }

  emit('click', props.banner)
}
</script>

<style lang="scss" scoped>
.banner-item {
  position: relative;
  // width: 100%;
  overflow: hidden;
}

// ========== 尺寸变体 ==========

// 大尺寸 - 用于轮播
.banner-large {
  height: 160px;

  .banner-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .banner-subtitle {
    font-size: 14px;
    margin-bottom: 16px;
  }
  .banner-desc {
    font-size: 12px;
    margin-bottom: 12px;
  }
  .banner-button {
    padding: 8px 20px;
    font-size: 13px;
  }
  .banner-product-img {
    width: 50px;
    height: 50px;
  }
}

// 中尺寸 - 用于中部横幅
.banner-medium {
  min-height: 100px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

  .banner-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .banner-subtitle {
    font-size: 14px;
    margin-bottom: 8px;
  }
  .banner-desc {
    font-size: 12px;
    margin-bottom: 10px;
  }
  .banner-button {
    padding: 8px 20px;
    font-size: 13px;
  }
  .banner-product-img {
    width: 45px;
    height: 45px;
  }
}

// 小尺寸 - 用于底部
.banner-small {
  min-height: 80px;
  // border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

  .banner-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 6px;
  }
  .banner-subtitle {
    font-size: 14px;
    margin-bottom: 12px;
  }
  .banner-desc {
    font-size: 12px;
    margin-bottom: 10px;
  }
  .banner-button {
    padding: 8px 20px;
    font-size: 13px;

  }
  .banner-product-img {
    width: 40px;
    height: 40px;
  }
}

// ========== 图片型样式 ==========

.banner-image-type {
  .banner-image {
    width: 100%;
    height: 100%;
  }

  .banner-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);

    .banner-title {
      display: block;
      color: #fff;
      line-height: 1.3;
    }
    .banner-subtitle {
      display: block;
      color: rgba(255, 255, 255, 0.85);
      opacity: 1;
    }
  }
}

// 中小尺寸图片型特殊处理
.banner-medium.banner-image-type,
.banner-small.banner-image-type {
  .banner-overlay {
    top: 50%;
    bottom: auto;
    transform: translateY(-50%);
    background: none;
    max-width: 60%;

    .banner-title {
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }
    .banner-subtitle {
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }
  }
}

// ========== 背景色型样式 ==========

.banner-color-type {
  display: flex;
  align-items: center;
  padding: 16px;
}

.banner-color-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  height: 100%;
}

.banner-text-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.banner-title {
  display: block;
  line-height: 1.3;
}

.banner-subtitle {
  display: block;
  opacity: 0.9;
}

.banner-desc {
  display: block;
  opacity: 0.8;
  line-height: 1.4;
}

.banner-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
  font-weight: 600;
  width: fit-content;
  padding: 6px 16px;
  font-size: 12px;

  &.btn-style-1 {
    background: #000;
    color: #fff;
  }
  &.btn-style-2 {
    background: #409eff;
    color: #fff;
  }
  &.btn-style-3 {
    background: transparent;
    border: 2px solid currentColor;
  }
}

.banner-products {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 6px;
  margin-left: 12px;
  flex-shrink: 0;
  max-width: 120px;
}

.banner-product-img {
  width: 120px !important;
  height: 120px !important;
  min-width: 50px;
  min-height: 50px;
  // border-radius: 8px;
  // background: #fff;
  // box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

// 小尺寸背景色型特殊布局
.banner-small.banner-color-type {
  padding: 12px 16px;

  .banner-color-content {
    flex-direction: row;
  }
  .banner-products {
    display: none; // 小尺寸不显示产品图
  }
}
</style>
