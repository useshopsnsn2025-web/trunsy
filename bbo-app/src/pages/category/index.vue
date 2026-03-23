<template>
  <view class="page">
    <!-- 搜索框 -->
    <view class="search-wrapper" :style="{ paddingTop: statusBarHeight + 'px' }">
      <SearchBar
        :editable="false"
        :placeholder="t('search.placeholder')"
        :show-cancel="false"
        @click="goSearch"
      />
    </view>

    <!-- 分类列表 -->
    <scroll-view scroll-y class="category-scroll" :style="{ height: scrollHeight }">
      <view class="category-grid">
        <view
          v-for="(cat, index) in categories"
          :key="cat.id"
          class="category-item"
          :class="{ 'is-pressing': pressingId === cat.id }"
          @click="goCategoryBrands(cat)"
          @touchstart="pressingId = cat.id"
          @touchend="pressingId = null"
          @touchcancel="pressingId = null"
        >
          <image
            class="category-image"
            :src="cat.icon || cat.image || '/static/category-default.png'"
            mode="aspectFit"
          />
          <text class="category-name">{{ cat.name }}</text>
        </view>
      </view>
    </scroll-view>

    <!-- 自定义底部导航栏 -->
    <CustomTabBar :current="1" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getCategories, type Category } from '@/api/goods'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import SearchBar from '@/components/SearchBar.vue'
import CustomTabBar from '@/components/CustomTabBar.vue'

const { t } = useI18n()
const appStore = useAppStore()

const categories = ref<Category[]>([])
// 点击状态
const pressingId = ref<number | null>(null)
// 记录上次的语言，用于检测语言变化
let lastLocale = appStore.locale

// 状态栏高度
const statusBarHeight = computed(() => appStore.statusBarHeight)

// 计算滚动区域高度（减去状态栏、搜索框、tabbar）
const scrollHeight = computed(() => {
  const searchHeight = 60 // 搜索框高度
  const tabbarHeight = 50 // tabbar高度
  return `calc(100vh - ${statusBarHeight.value + searchHeight + tabbarHeight}px)`
})

async function loadCategories() {
  try {
    const res = await getCategories()
    categories.value = res.data
  } catch (e) {
    console.error('Failed to load categories:', e)
  }
}

function goSearch() {
  uni.navigateTo({ url: '/pages/search/index' })
}

function goCategoryBrands(cat: Category) {
  uni.navigateTo({
    url: `/pages/category/brands?id=${cat.id}&title=${encodeURIComponent(cat.name)}`
  })
}

onMounted(() => {
  appStore.initSystemInfo()
  loadCategories()
})

onShow(() => {
  // #ifdef APP-PLUS
  uni.hideTabBar({ animation: false })
  // #endif

  // 检测语言变化，重新加载分类数据
  if (lastLocale !== appStore.locale) {
    lastLocale = appStore.locale
    loadCategories()
  }
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff;
}

.search-wrapper {
  background-color: #fff;
}

.category-scroll {
  flex: 1;
  background-color: #fff;
}

.category-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 0 16px 16px;
}

.category-item {
  width: 48%;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 8px;
  border-radius: 12px;
  margin-bottom: 12px;
  box-sizing: border-box;
  background: linear-gradient(to top, #f0f0f0 0%, #fff 40%);
  border: 1px solid rgba(0, 0, 0, 0.06);
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition: transform 0.15s ease, box-shadow 0.15s ease;

  &.is-pressing {
    transform: scale(0.96);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
}

.category-image {
  width: 80px;
  height: 80px;
  margin-bottom: 8px;
}

.category-name {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  text-align: center;
}
</style>
