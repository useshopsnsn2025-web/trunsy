<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <view class="navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="navbar-content">
        <view class="navbar-brand">
          <image
            v-if="appLogo"
            class="navbar-logo"
            :src="appLogo"
            mode="widthFix"
          />
          <!-- <text class="navbar-title">{{ appName }}</text> -->
        </view>
        <view class="navbar-actions">
          <text class="bi bi-search action-icon" @click="goSearch"></text>
          <text class="bi bi-cart3 action-icon" @click="goCart"></text>
        </view>
      </view>
    </view>

    <!-- 自定义下拉刷新提示 -->
    <view
      v-if="showRefreshTip"
      class="refresh-tip"
      :style="{ top: navbarHeight + 'px' }"
    >
      <view class="refresh-tip-content" :class="{ 'is-refreshing': isRefreshing }">
        <view class="refresh-icon-wrapper">
          <view v-if="isRefreshing" class="refresh-spinner">
            <view class="spinner-dot"></view>
            <view class="spinner-dot"></view>
            <view class="spinner-dot"></view>
          </view>
          <text v-else class="bi bi-arrow-down refresh-arrow"></text>
        </view>
        <text class="refresh-text">{{ isRefreshing ? t('common.loading') : 'Pull to refresh' }}</text>
      </view>
    </view>

    <!-- 页面内容 -->
    <scroll-view
      class="content"
      scroll-y
      :style="{ paddingTop: navbarHeight + 'px' }"
      @scroll="onScroll"
      @scrolltolower="loadMore"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
    >
      <!-- 顶部轮播Banner -->
      <BannerSwiper
        :banners="homeBanners.home_top"
        @change="onBannerChange"
      />

      <!-- 热门分类 -->
      <view class="hot-categories-section">
        <scroll-view scroll-x class="hot-categories-scroll" :show-scrollbar="false" enhanced>
          <view class="hot-categories-list">
            <view
              v-for="(cat, index) in hotCategories"
              :key="cat.id"
              class="hot-category-card"
              :class="'hot-category-card-' + (index % 4)"
              @click="goCategoryBrands(cat.id, cat.name)"
            >
              <view class="hot-category-info">
                <text class="hot-category-name">{{ cat.name }}</text>
                <text class="hot-category-count">{{ (cat.goods_count || 0) }} {{ t('home.approvedListings') }}</text>
              </view>
              <image
                class="hot-category-image"
                :src="cat.icon || cat.image || '/static/category-default.png'"
                mode="aspectFit"
              />
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 精选品牌 -->
      <view class="section brands-section">
        <text class="section-title">{{ t('home.featuredBrands') }}</text>
        <scroll-view scroll-x class="brands-scroll" :show-scrollbar="false">
          <view class="brands-list">
            <view
              v-for="brand in featuredBrands"
              :key="brand.id"
              class="brand-item"
              @click="goBrandGoods(brand.name)"
            >
              <view class="brand-logo">
                <image
                  v-if="brand.logo"
                  :src="brand.logo"
                  mode="aspectFit"
                  class="brand-logo-image"
                />
                <text v-else class="brand-logo-text">{{ brand.icon }}</text>
              </view>
              <text class="brand-name">{{ brand.name }}</text>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 热门商品 -->
      <view v-if="hotGoods.length > 0" class="section hot-goods-section">
        <view class="section-header">
          <text class="section-title">{{ t('home.hotProducts') }}</text>
        </view>
        <scroll-view scroll-x class="featured-scroll" :show-scrollbar="false">
          <view class="featured-list">
            <view
              v-for="item in hotGoods"
              :key="item.id"
              class="featured-card"
              @click="goGoodsDetail(item.id)"
            >
              <view class="featured-image-wrapper">
                <image
                  class="featured-image"
                  :src="item.images[0]"
                  mode="aspectFit"
                  lazy-load
                />
                <view v-if="item.stock === 0" class="sold-out-overlay">
                  <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
                </view>
                <view v-if="item.promotion && item.stock > 0" class="featured-discount-tag">
                  <text>{{ item.promotion.discountPercent }}%</text>
                  <text class="off-text">OFF</text>
                </view>
                <view class="hot-badge">
                  <text class="bi bi-fire"></text>
                </view>
              </view>
              <view class="featured-info">
                <text class="featured-title">{{ item.title }}</text>
                <view class="featured-price-row">
                  <text class="featured-price" :class="{ 'promo-price': item.promotion }">
                    {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                  </text>
                  <text v-if="item.promotion" class="featured-original-price">
                    {{ formatPrice(item.price, item.currency) }}
                  </text>
                </view>
              </view>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 中部横幅Banner -->
      <BannerList :banners="homeBanners.home_middle" size="medium" />

      <!-- 限时活动 -->
      <view v-if="seckillPromotion" class="section promo-section">
        <!-- 背景装饰 -->
        <view class="promo-bg-decor">
          <view class="promo-bg-circle bg-circle-1"></view>
          <view class="promo-bg-circle bg-circle-2"></view>
          <view class="promo-bg-circle bg-circle-3"></view>
        </view>

        <view class="section-header">
          <view class="promo-title-wrapper">
            <view class="promo-icon-badge">
              <text class="bi bi-lightning-fill promo-icon"></text>
              <view class="icon-pulse"></view>
            </view>
            <view class="promo-title-text">
              <text class="section-title">{{ t('promotion.typeSeckill') }}</text>
              <text class="promo-subtitle">Limited Time Only</text>
            </view>
          </view>

          <view class="countdown-box" v-if="promoCountdown">
            <text class="countdown-label">{{ t('promotion.endsIn') }}</text>
            <view class="countdown-digits">
              <view class="digit-box">
                <text class="digit">{{ String(promoCountdown.hours).padStart(2, '0') }}</text>
              </view>
              <text class="digit-sep">:</text>
              <view class="digit-box">
                <text class="digit">{{ String(promoCountdown.minutes).padStart(2, '0') }}</text>
              </view>
              <text class="digit-sep blink">:</text>
              <view class="digit-box">
                <text class="digit">{{ String(promoCountdown.seconds).padStart(2, '0') }}</text>
              </view>
            </view>
          </view>
        </view>

        <view class="promo-view-all" @click="goPromotions">
          <text>{{ t('promotion.viewAll') }}</text>
          <text class="bi bi-arrow-right"></text>
        </view>

        <!-- 活动商品横向滚动 -->
        <scroll-view scroll-x class="promo-scroll" :show-scrollbar="false">
          <view class="promo-goods-list">
            <view
              v-for="item in seckillPromotion.goods"
              :key="item.id"
              class="promo-goods-card"
              @click="goGoodsDetail(item.goods.id)"
            >
              <view class="promo-goods-image-wrapper">
                <image
                  class="promo-goods-image"
                  :src="item.goods.images?.[0] || '/static/placeholder.png'"
                  mode="aspectFit"
                  lazy-load
                />
                <!-- 已售罄遮罩 -->
                <view v-if="item.goods.stock === 0" class="sold-out-overlay">
                  <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
                </view>
                <view v-else-if="item.discount" class="discount-tag">
                  <text>{{ Math.round(item.discount * 100) }}%</text>
                  <text class="off-text">{{ t('promotion.off') }}</text>
                </view>
              </view>
              <view class="promo-goods-info">
                <text class="promo-goods-title">{{ item.goods.title }}</text>
                <view class="promo-price-row">
                  <text class="promo-price">{{ formatPrice(item.promotionPrice, item.goods.currency) }}</text>
                  <text class="original-price">{{ formatPrice(item.goods.price, item.goods.currency) }}</text>
                </view>
              </view>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 活动入口卡片 -->
      <view v-if="homePromotions.length > 0" class="section promo-entries-section">
        <view class="section-header">
          <text class="section-title">{{ t('promotion.title') }}</text>
          <view class="more-link" @click="goPromotions">
            <text>{{ t('promotion.viewAll') }}</text>
            <text class="bi bi-chevron-right arrow"></text>
          </view>
        </view>
        <view class="promo-cards">
          <view
            v-for="(promo, index) in homePromotions.slice(0, 2)"
            :key="promo.id"
            class="promo-card"
            :class="'promo-type-' + promo.type"
            @click="goPromotionDetail(promo.id)"
          >
            <!-- 背景装饰 -->
            <view class="promo-card-bg">
              <view class="promo-bg-circle circle-1"></view>
              <view class="promo-bg-circle circle-2"></view>
            </view>
            <!-- 内容 -->
            <view class="promo-card-content">
              <view class="promo-card-header">
                <view class="promo-icon-wrapper">
                  <text class="bi" :class="getPromoTypeIcon(promo.type)"></text>
                </view>
                <view class="promo-badge" v-if="index === 0">HOT</view>
              </view>
              <view class="promo-card-body">
                <text class="promo-card-title">{{ promo.name }}</text>
                <text class="promo-card-desc">{{ formatGoodsCountText(promo.goodsCount || 0) }}</text>
              </view>
              <view class="promo-card-footer">
                <text class="promo-shop-now">{{ t('promotion.shopNow') }}</text>
                <view class="promo-arrow">
                  <text class="bi bi-arrow-right"></text>
                </view>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 推荐商品 -->
      <view v-if="recommendGoods.length > 0" class="section recommend-goods-section">
        <view class="section-header">
          <text class="section-title">{{ t('home.recommendProducts') }}</text>
        </view>
        <scroll-view scroll-x class="featured-scroll" :show-scrollbar="false">
          <view class="featured-list">
            <view
              v-for="item in recommendGoods"
              :key="item.id"
              class="featured-card"
              @click="goGoodsDetail(item.id)"
            >
              <view class="featured-image-wrapper">
                <image
                  class="featured-image"
                  :src="item.images[0]"
                  mode="aspectFit"
                  lazy-load
                />
                <view v-if="item.stock === 0" class="sold-out-overlay">
                  <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
                </view>
                <view v-if="item.promotion && item.stock > 0" class="featured-discount-tag">
                  <text>{{ item.promotion.discountPercent }}%</text>
                  <text class="off-text">OFF</text>
                </view>
              </view>
              <view class="featured-info">
                <text class="featured-title">{{ item.title }}</text>
                <view class="featured-price-row">
                  <text class="featured-price" :class="{ 'promo-price': item.promotion }">
                    {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                  </text>
                  <text v-if="item.promotion" class="featured-original-price">
                    {{ formatPrice(item.price, item.currency) }}
                  </text>
                </view>
              </view>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 更多科技产品 -->
      <view class="section tech-deals-section">
        <text class="section-title">{{ t('home.moreTechDeals') }}</text>
        <view class="tech-deals-grid">
          <view
            v-for="cat in techCategories"
            :key="cat.id"
            class="tech-deal-item"
            @click="goCategoryBrands(cat.id, cat.name)"
          >
            <image
              class="tech-deal-image"
              :src="cat.icon || cat.image || '/static/category-default.png'"
              mode="aspectFit"
            />
            <text class="tech-deal-name">{{ cat.name }}</text>
          </view>
        </view>
      </view>

      <!-- 精选商品 -->
      <view class="section featured-section">
        <text class="section-title">{{ t('home.featuredProducts') }}</text>

        <!-- 空状态 -->
        <view v-if="!loading && goods.length === 0" class="empty-state">
          <image class="empty-image" src="/static/empty.png" mode="aspectFit" />
          <text class="empty-text">{{ t('goods.noGoods') }}</text>
          <button class="publish-btn" @click="goPublish">{{ t('goods.publishFirst') }}</button>
        </view>

        <!-- 商品横向滚动 -->
        <scroll-view v-else scroll-x class="featured-scroll">
          <view class="featured-list">
            <view
              v-for="item in goods"
              :key="item.id"
              class="featured-card"
              @click="goGoodsDetail(item.id)"
            >
              <view class="featured-image-wrapper">
                <image
                  class="featured-image"
                  :src="item.images[0]"
                  mode="aspectFit"
                  lazy-load
                />
                <!-- 已售罄遮罩 -->
                <view v-if="item.stock === 0" class="sold-out-overlay">
                  <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
                </view>
                <!-- 活动折扣标签 -->
                <view v-if="item.promotion && item.stock > 0" class="featured-discount-tag">
                  <text>{{ item.promotion.discountPercent }}%</text>
                  <text class="off-text">OFF</text>
                </view>
              </view>
              <view class="featured-info">
                <text class="featured-title">{{ item.title }}</text>
                <view class="featured-price-row">
                  <text class="featured-price" :class="{ 'promo-price': item.promotion }">
                    {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                  </text>
                  <text v-if="item.promotion" class="featured-original-price">
                    {{ formatPrice(item.price, item.currency) }}
                  </text>
                </view>
              </view>
            </view>
          </view>
        </scroll-view>

        <!-- 加载状态 -->
        <view v-if="loading" class="loading">
          <text>{{ t('common.loading') }}</text>
        </view>
      </view>

      <!-- 底部Banner -->
      <BannerList :banners="homeBanners.home_bottom" size="small" />

      <!-- 全部商品 -->
      <view class="section all-goods-section">
        <view class="section-header">
          <text class="section-title">{{ t('home.allProducts') }}</text>
          <view class="more-link" @click="goAllGoods">
            <text>{{ t('common.more') }}</text>
            <text class="bi bi-chevron-right arrow"></text>
          </view>
        </view>

        <!-- 商品瀑布流 -->
        <view class="goods-grid">
          <view
            v-for="item in allGoods"
            :key="item.id"
            class="goods-card"
            @click="goGoodsDetail(item.id)"
          >
            <view class="goods-image-wrapper">
              <image
                class="goods-image"
                :src="item.images[0]"
                mode="aspectFit"
                lazy-load
              />
              <!-- 已售罄遮罩 -->
              <view v-if="item.stock === 0" class="sold-out-overlay">
                <text class="sold-out-text">{{ t('goods.status.soldOut') }}</text>
              </view>
              <!-- 活动折扣标签 -->
              <view v-if="item.promotion && item.stock > 0" class="goods-discount-tag">
                <text>{{ item.promotion.discountPercent }}%</text>
                <text class="off-text">OFF</text>
              </view>
              <!-- 收藏按钮 -->
              <view class="goods-like-btn" @click.stop="handleToggleLike(item)">
                <text class="bi" :class="item.isLiked ? 'bi-heart-fill liked' : 'bi-heart'"></text>
              </view>
            </view>
            <view class="goods-info">
              <text class="goods-title">{{ item.title }}</text>
              <view class="goods-meta">
                <!-- 活动价格优先显示 -->
                <text class="goods-price" :class="{ 'promo-price': item.promotion }">
                  {{ formatPrice(item.promotion?.promotionPrice ?? item.price, item.currency) }}
                </text>
                <!-- 有活动时显示原价 -->
                <text v-if="item.promotion" class="goods-original-price">
                  {{ formatPrice(item.price, item.currency) }}
                </text>
                <!-- 无活动但有原价时显示原价 -->
                <text v-else-if="item.originalPrice" class="goods-original-price">
                  {{ formatPrice(item.originalPrice, item.currency) }}
                </text>
              </view>
              <view class="goods-footer" v-if="item.seller">
                <view class="seller-info">
                  <image
                    class="seller-avatar"
                    :src="item.seller.avatar || '/static/default-avatar.png'"
                    mode="aspectFit"
                  />
                  <text class="seller-name">{{ item.seller.nickname }}</text>
                </view>
              </view>
            </view>
          </view>
        </view>

        <view v-if="noMore && allGoods.length > 0" class="no-more">
          <text>{{ t('common.noMore') }}</text>
        </view>

        <!-- 底部 TabBar 占位 -->
        <view class="tabbar-placeholder"></view>
      </view>
    </scroll-view>

    <!-- 游戏入口浮窗 -->
    <GameFloatButton />

    <!-- 自定义底部导航栏 -->
    <CustomTabBar :current="0" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/modules/app'
import { getGoodsList, getHotGoods, getRecommendGoods, getCategories, getHotCategories, getBrands, toggleLike, type Goods, type Category, type Brand } from '@/api/goods'
import { getHomePromotions, getRemainTime, PROMOTION_TYPE, type HomePromotion, type Promotion } from '@/api/promotion'
import { get } from '@/utils/request'
import { useI18n } from 'vue-i18n'
import { localeReadyPromise } from '@/locale'
import CustomTabBar from '@/components/CustomTabBar.vue'
import BannerSwiper from '@/components/BannerSwiper.vue'
import BannerList from '@/components/BannerList.vue'
import GameFloatButton from '@/components/GameFloatButton.vue'

const { t } = useI18n()
const appStore = useAppStore()

// 状态
const categories = ref<Category[]>([])
const hotCategoriesList = ref<Category[]>([]) // 热门分类列表
const goods = ref<Goods[]>([]) // 精选商品
const hotGoods = ref<Goods[]>([]) // 热门商品
const recommendGoods = ref<Goods[]>([]) // 推荐商品
const allGoods = ref<Goods[]>([]) // 全部商品
const loading = ref(false)
const noMore = ref(false)
const page = ref(1)
const pageSize = 10

// 下拉刷新状态
const isRefreshing = ref(false)
const scrollTop = ref(0)
const showRefreshTip = ref(false)
const touchStartY = ref(0)
const isTouching = ref(false)

// 记录上次的语言，用于检测语言变化
let lastLocale = appStore.locale

// 计算属性
const statusBarHeight = computed(() => appStore.statusBarHeight)
const navbarHeight = computed(() => statusBarHeight.value + 80)
const appName = computed(() => appStore.appName)
const appLogo = computed(() => appStore.appLogo)

// 热门分类（从API获取）
const hotCategories = computed(() => {
  return hotCategoriesList.value
})

// 科技产品分类（显示所有）
const techCategories = computed(() => {
  return categories.value
})

// 精选品牌
const featuredBrands = ref<Brand[]>([])

// 活动相关
const seckillPromotion = ref<HomePromotion | null>(null)
const discountPromotion = ref<HomePromotion | null>(null)
const homePromotions = ref<Promotion[]>([])
const promoCountdown = ref<{ hours: number; minutes: number; seconds: number } | null>(null)
let countdownTimer: ReturnType<typeof setInterval> | null = null

// Banner 相关
interface BannerData {
  id: number
  title: string
  subtitle: string
  content: string
  buttonText: string
  image: string
  displayType: number
  bgColor: string | null
  bgGradient: string | null
  textColor: string
  buttonStyle: number
  buttonColor: string | null
  productImages: string[]
  linkType: number
  linkValue: string
}
interface HomeBanners {
  home_top: BannerData[]
  home_middle: BannerData[]
  home_bottom: BannerData[]
}
const homeBanners = ref<HomeBanners>({
  home_top: [],
  home_middle: [],
  home_bottom: []
})
const currentBannerIndex = ref(0)

// 格式化价格（使用汇率转换）
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

// 格式化商品数量文本（手动替换占位符，解决 UniApp APP 端 vue-i18n 插值不生效的问题）
function formatGoodsCountText(count: number): string {
  const template = t('promotion.goodsCount')
  return template.replace('[COUNT]', String(count))
}

// 加载Banner
async function loadBanners() {
  try {
    const res = await get<HomeBanners>('/system/banners')
    if (res.code === 0 && res.data) {
      homeBanners.value = res.data
    }
  } catch (e) {
    console.error('Failed to load banners:', e)
  }
}

// 加载分类
async function loadCategories() {
  try {
    const res = await getCategories()
    categories.value = res.data
  } catch (e) {
    console.error('Failed to load categories:', e)
  }
}

// 加载热门分类
async function loadHotCategories() {
  try {
    const res = await getHotCategories()
    hotCategoriesList.value = res.data
  } catch (e) {
    console.error('Failed to load hot categories:', e)
  }
}

// 加载品牌
async function loadBrands() {
  try {
    const res = await getBrands()
    featuredBrands.value = res.data
  } catch (e) {
    console.error('Failed to load brands:', e)
  }
}

// 加载首页活动
async function loadHomePromotions() {
  try {
    const res = await getHomePromotions()
    const data = res.data

    // 秒杀活动
    seckillPromotion.value = data?.seckill || null

    // 限时折扣活动
    discountPromotion.value = data?.discount || null

    // 其他活动
    homePromotions.value = data?.promotions || []

    // 启动倒计时（优先秒杀，其次折扣）
    const activePromo = seckillPromotion.value || discountPromotion.value
    if (activePromo) {
      startCountdown(activePromo.endTime)
    }
  } catch (e) {
    console.error('Failed to load promotions:', e)
  }
}

// 启动倒计时
function startCountdown(endTime: string) {
  // 清除旧计时器
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }

  const updateCountdown = () => {
    const remain = getRemainTime(endTime)
    if (remain.total <= 0) {
      promoCountdown.value = null
      if (countdownTimer) {
        clearInterval(countdownTimer)
        countdownTimer = null
      }
      return
    }
    promoCountdown.value = {
      hours: remain.days * 24 + remain.hours,
      minutes: remain.minutes,
      seconds: remain.seconds
    }
  }

  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
}

// 停止倒计时
function stopCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

// 加载精选商品
async function loadFeaturedGoods() {
  try {
    const res = await getGoodsList({
      page: 1,
      pageSize: 10,
      sort: 'popular',
    })
    goods.value = res.data.list
  } catch (e) {
    console.error('Failed to load featured goods:', e)
  }
}

// 加载热门商品
async function loadHotGoodsList() {
  try {
    const res = await getHotGoods(10)
    hotGoods.value = res.data.list
  } catch (e) {
    console.error('Failed to load hot goods:', e)
  }
}

// 加载推荐商品
async function loadRecommendGoodsList() {
  try {
    const res = await getRecommendGoods(10)
    recommendGoods.value = res.data.list
  } catch (e) {
    console.error('Failed to load recommend goods:', e)
  }
}

// 加载全部商品
async function loadAllGoods(isRefresh = false) {
  if (loading.value) return

  if (isRefresh) {
    page.value = 1
    noMore.value = false
  }

  loading.value = true

  try {
    const res = await getGoodsList({
      page: page.value,
      pageSize,
      sort: 'popular',
    })

    if (isRefresh) {
      allGoods.value = res.data.list
    } else {
      allGoods.value = [...allGoods.value, ...res.data.list]
    }

    if (res.data.list.length < pageSize) {
      noMore.value = true
    }
  } catch (e) {
    console.error('Failed to load goods:', e)
  } finally {
    loading.value = false
  }
}

// 加载更多
function loadMore() {
  if (noMore.value || loading.value) return
  page.value++
  loadAllGoods()
}

// 滚动事件
function onScroll(e: any) {
  scrollTop.value = e.detail.scrollTop
}

// 是否在顶部（允许一定容差）
const isAtTop = computed(() => scrollTop.value < 10)

// 触摸事件 - 自定义下拉刷新
// 记录触摸开始时是否在有效区域（导航栏下方一定范围内）
const startedInRefreshZone = ref(false)

function onTouchStart(e: any) {
  if (isRefreshing.value) return

  const touchY = e.touches[0].clientY
  // 刷新触发区域：导航栏下方 200px 以内，且 scroll-view 在顶部
  const refreshZoneBottom = navbarHeight.value + 200

  if (isAtTop.value && touchY < refreshZoneBottom) {
    touchStartY.value = touchY
    isTouching.value = true
    startedInRefreshZone.value = true
  } else {
    startedInRefreshZone.value = false
    isTouching.value = false
  }
}

function onTouchMove(e: any) {
  // 必须是从刷新区域开始的触摸才处理
  if (!isTouching.value || !startedInRefreshZone.value || isRefreshing.value) return

  const currentY = e.touches[0].clientY
  const diff = currentY - touchStartY.value

  // 向下拉动超过阈值时显示刷新提示
  if (diff > 50) {
    showRefreshTip.value = true
  } else {
    showRefreshTip.value = false
  }
}

function onTouchEnd() {
  if (!isTouching.value || !startedInRefreshZone.value) {
    isTouching.value = false
    startedInRefreshZone.value = false
    return
  }

  isTouching.value = false
  startedInRefreshZone.value = false

  // 如果显示了刷新提示，执行刷新
  if (showRefreshTip.value) {
    doRefresh()
  } else {
    showRefreshTip.value = false
  }
}

// 执行刷新
async function doRefresh() {
  if (isRefreshing.value) return
  isRefreshing.value = true

  try {
    await Promise.all([
      loadBanners(),
      loadCategories(),
      loadHotCategories(),
      loadBrands(),
      loadHomePromotions(),
      loadFeaturedGoods(),
      loadHotGoodsList(),
      loadRecommendGoodsList(),
      loadAllGoods(true)
    ])
  } finally {
    isRefreshing.value = false
    showRefreshTip.value = false
  }
}

// 导航方法
function goSearch() {
  uni.navigateTo({ url: '/pages/search/index' })
}

function goCart() {
  uni.navigateTo({ url: '/pages/cart/index' })
}

function goCategoryBrands(categoryId: number, categoryName: string) {
  uni.navigateTo({
    url: `/pages/category/brands?id=${categoryId}&title=${encodeURIComponent(categoryName)}`
  })
}

function goBrandGoods(brandName: string) {
  // 品牌名称转为小写作为 value（与属性选项中的 option_value 对应）
  const brandValue = brandName.toLowerCase().replace(/\s+/g, '_')
  uni.navigateTo({
    url: `/pages/brand/models?value=${encodeURIComponent(brandValue)}&name=${encodeURIComponent(brandName)}`
  })
}

function goGoodsDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

// 收藏/取消收藏商品
async function handleToggleLike(item: Goods) {
  try {
    const res = await toggleLike(item.id)
    if (res.code === 0) {
      // 更新商品的收藏状态
      item.isLiked = res.data.isLiked
      // uni.showToast({
      //   title: res.data.isLiked ? t('goods.liked') : t('goods.unliked'),
      //   icon: 'none'
      // })
    }
  } catch (e) {
    console.error('Failed to toggle like:', e)
  }
}

function goPublish() {
  uni.switchTab({ url: '/pages/publish/index' })
}

function goAllGoods() {
  uni.switchTab({ url: '/pages/category/index' })
}

// 活动相关导航
function goPromotions() {
  uni.navigateTo({ url: '/pages/promotion/index' })
}

function goPromotionDetail(id: number) {
  uni.navigateTo({ url: `/pages/promotion/detail?id=${id}` })
}

// 获取活动类型图标
function getPromoTypeIcon(type: number): string {
  const icons: Record<number, string> = {
    [PROMOTION_TYPE.DISCOUNT]: 'bi-percent',
    [PROMOTION_TYPE.REDUCTION]: 'bi-gift',
    [PROMOTION_TYPE.SECKILL]: 'bi-lightning',
    [PROMOTION_TYPE.GROUP]: 'bi-people',
  }
  return icons[type] || 'bi-tag'
}

// Banner轮播切换
function onBannerChange(index: number) {
  currentBannerIndex.value = index
}

// 生命周期
onMounted(async () => {
  appStore.initSystemInfo()
  // 等待语言初始化完成后再加载数据，避免首次启动显示英语
  await localeReadyPromise
  loadBanners()
  loadCategories()
  loadHotCategories()
  loadBrands()
  loadHomePromotions()
  loadFeaturedGoods()
  loadHotGoodsList()
  loadRecommendGoodsList()
  loadAllGoods()
})

onUnmounted(() => {
  stopCountdown()
})

onShow(() => {
  // #ifdef APP-PLUS
  // 隐藏原生 TabBar
  uni.hideTabBar({ animation: false })
  // #endif

  // 检测语言变化，重新加载数据
  if (lastLocale !== appStore.locale) {
    lastLocale = appStore.locale
    // 重新加载需要翻译的数据
    loadCategories()
    loadHotCategories()
    loadBrands()
    loadHomePromotions()
    loadFeaturedGoods()
    loadHotGoodsList()
    loadRecommendGoodsList()
    loadAllGoods()
  }
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #FFF;
}

// 自定义下拉刷新提示
.refresh-tip {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 99;
  display: flex;
  justify-content: center;
  padding: 16px 0;
  pointer-events: none;
}

.refresh-tip-content {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8F5A 100%);
  border-radius: 24px;
  box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
  transition: all 0.3s ease;

  &.is-refreshing {
    padding: 12px 24px;
  }
}

.refresh-icon-wrapper {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.refresh-arrow {
  font-size: 16px;
  color: #fff;
  animation: bounce 1s ease infinite;
}

.refresh-spinner {
  display: flex;
  align-items: center;
  gap: 4px;
}

.spinner-dot {
  width: 6px;
  height: 6px;
  background-color: #fff;
  border-radius: 50%;
  animation: dotPulse 1.4s ease-in-out infinite;

  &:nth-child(1) { animation-delay: 0s; }
  &:nth-child(2) { animation-delay: 0.2s; }
  &:nth-child(3) { animation-delay: 0.4s; }
}

.refresh-text {
  font-size: 13px;
  font-weight: 500;
  color: #fff;
  letter-spacing: 0.3px;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(3px); }
}

@keyframes dotPulse {
  0%, 80%, 100% {
    transform: scale(0.6);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

// 导航栏
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background-color: #ffffff;

  // 简约弧线装饰 - 带柔和阴影
  &::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: -5%;
    width: 110%;
    height: 10px;
    background-color: #ffffff;
    border-radius: 0 0 50% 50% / 0 0 100% 100%;
    box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.06);
    pointer-events: none;
  }
}

.navbar-content {
  height: 60px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px 16px 0 16px;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 8px;
}

.navbar-logo {
  
  width: 120px;
  border-radius: 4px;
}

.navbar-title {
  font-size: 28px;
  font-weight: 600;
  color: #FFF;
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.action-icon {
  font-size: 20px;
  color: #000000;
  font-weight: 700;
  padding: 8px;
  margin: -8px;
  border-radius: 50%;
  transition: transform 0.15s ease-out, background-color 0.15s ease-out;
  cursor: pointer;

  &:active {
    transform: scale(0.9);
    background-color: rgba(0, 0, 0, 0.05);
  }
}

.content {
  min-height: 100vh;
}

// 热门分类
.hot-categories-section {
  padding: 20rpx 0 12rpx;
  background-color: #fff;
}

.hot-categories-scroll {
  width: 100%;
}

.hot-categories-list {
  display: inline-flex;
  gap: 16rpx;
  padding: 0 32rpx 16rpx;
}

.hot-category-card {
  position: relative;
  width: 280rpx;
  height: 160rpx;
  border-radius: 20rpx;
  overflow: hidden;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx;

  &:active {
    opacity: 0.85;
  }
}

.hot-category-card-0 {
  background: linear-gradient(135deg, #FFF2EC 0%, #FFE0D0 100%);
}

.hot-category-card-1 {
  background: linear-gradient(135deg, #EBF4FF 0%, #D0E4FF 100%);
}

.hot-category-card-2 {
  background: linear-gradient(135deg, #ECFDF3 0%, #D0F5E0 100%);
}

.hot-category-card-3 {
  background: linear-gradient(135deg, #F5F0FF 0%, #E4D9FF 100%);
}

.hot-category-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.hot-category-name {
  font-size: 28rpx;
  font-weight: 700;
  color: #1C1917;
  line-height: 1.3;
  word-break: break-word;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hot-category-count {
  font-size: 22rpx;
  color: #78716C;
  line-height: 1.3;
  word-break: break-word;
}

.hot-category-image {
  width: 120rpx;
  height: 120rpx;
  flex-shrink: 0;
  margin-left: 12rpx;
}

// 通用区块
.section {
  padding: 16px;
  background-color: #fff;
  margin-top: 8px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  display: block;
  margin-bottom: 12px;
}

.more-link {
  display: flex;
  align-items: center;
  font-size: 12px;
  color: #999;
}

.arrow {
  margin-left: 4px;
}

// 精选品牌
.brands-scroll {
  white-space: nowrap;

  ::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
  }
}

.brands-list {
  display: inline-flex;
  gap: 16px;
  padding-bottom: 8px;
}

.brand-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
  width: 72px;
  padding: 12px 10px 10px;
  background-color: #fff;
  border-radius: 20px;
  border: 1px solid #e8e8e8;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease-out, box-shadow 0.2s ease-out, border-color 0.2s ease-out;
  cursor: pointer;

  &:active {
    transform: scale(0.95);
    border-color: #FF6B35;
    box-shadow: 0 2px 12px rgba(255, 107, 53, 0.15);
  }
}

.brand-logo {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.brand-logo-image {
  width: 36px;
  height: 36px;
}

.brand-logo-text {
  font-size: 22px;
  font-weight: 600;
  color: #333;
}

.brand-name {
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 68px;
  color: #333;
  text-align: center;
  font-weight: 600;
}

// 更多科技产品
.tech-deals-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}

.tech-deal-item {
  width: 48%;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 8px;
  background: linear-gradient(to top, #f0f0f0 0%, #fff 40%);
  border-radius: 12px;
  margin-bottom: 12px;
  box-sizing: border-box;
  border: 1px solid rgba(0, 0, 0, 0.06);
  transition: transform 0.15s ease-out, background-color 0.15s ease-out;
  cursor: pointer;

  &:active {
    transform: scale(0.97);
    background: linear-gradient(to top, #e8e8e8 0%, #f5f5f5 40%);
  }
}



.tech-deal-image {
  width: 80px;
  height: 80px;
  margin-bottom: 8px;
}

.tech-deal-name {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  text-align: center;
}

// 热门商品 & 推荐商品
.hot-goods-section,
.recommend-goods-section {
  .section-header {
    margin-bottom: 0;
  }
  .section-title {
    margin-bottom: 12px;
  }
}

.hot-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, #FF6B35 0%, #FF4500 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  box-shadow: 0 2px 6px rgba(255, 69, 0, 0.3);
}

// 精选商品
.featured-scroll {
  white-space: nowrap;
}

.featured-list {
  display: inline-flex;
  gap: 12px;
  padding-bottom: 8px;
}

.featured-card {
  width: 140px;
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
  transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
  cursor: pointer;

  &:active {
    transform: scale(0.97);
    box-shadow: 0 0 0 rgba(0, 0, 0, 0);
  }
}

.featured-image {
  width: 140px;
  height: 140px;
  background-color: #ffffff;
}

.featured-info {
  padding: 8px;
}

.featured-title {
  font-size: 12px;
  color: #333;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  line-height: 1.4;
  height: 2.8em;
  white-space: normal;
}

.featured-image-wrapper {
  position: relative;
  width: 100%;
  height: 140px;
  // 圆角
  border-radius: 8px;
  // 阴影
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.featured-discount-tag {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 4px 8px;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  color: #fff;

  .off-text {
    font-size: 9px;
  }
}

.featured-price-row {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 6px;
  margin-top: 4px;
}

.featured-price {
  font-size: 14px;
  font-weight: 600;
  color: #000;

  &.promo-price {
    color: #000;
  }
}

.featured-original-price {
  font-size: 11px;
  color: #9ca3af;
  text-decoration: line-through;
}

// 全部商品
.all-goods-section {
  .section-title {
    margin-bottom: 0;
  }
}

.goods-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.goods-card {
  width: calc(50% - 4px);
  background-color: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, background-color 0.15s ease-out;
  cursor: pointer;

  &:active {
    transform: scale(0.97);
    background-color: #FAFAFA;
    box-shadow: 0 0 0 rgba(0, 0, 0, 0);
  }
}

.goods-image-wrapper {
  position: relative;
  width: 100%;
  // 使用 padding-bottom 实现正方形容器（1:1 比例）
  padding-bottom: 100%;
  overflow: hidden;
  // background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  border-radius: 8px;
  
}

.goods-image {
  position: absolute;
  top: 0px;
  left: 0px;
  right: 0px;
  bottom: 0px;
  width: 100%;
  height: 100%;
  object-fit: contain;
  background-color: #ffffff;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.sold-out-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.sold-out-text {
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.goods-discount-tag {
  position: absolute;
  top: 8px;
  left: 8px;
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 4px 8px;
  background: linear-gradient(135deg, #EF4444 0%, #F97316 100%);
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  color: #fff;

  .off-text {
    font-size: 9px;
  }
}

// 收藏按钮 - 图片右上角
.goods-like-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, background-color 0.2s ease;

  .bi {
    font-size: 16px;
    color: #999;
    transition: color 0.2s ease;
  }

  .liked {
    color: #FF6B35;
  }

  &:active {
    transform: scale(1.15);
    background-color: #fff;
  }
}

.goods-info {
  padding: 8px;
}

.goods-title {
  font-size: 14px;
  color: #333;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  line-height: 1.4;
  height: 2.8em;
}

.goods-meta {
  display: flex;
  align-items: baseline;
  margin-top: 8px;
}

.goods-price {
  font-size: 16px;
  font-weight: 600;
  color: #000;

  &.promo-price {
    color: #000;
  }
}

.goods-original-price {
  font-size: 12px;
  color: #999;
  text-decoration: line-through;
  margin-left: 6px;
}

.goods-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 8px;
}

.seller-info {
  display: flex;
  align-items: center;
}

.seller-avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  margin-right: 4px;
}

.seller-name {
  font-size: 12px;
  color: #999;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 0;
}

.empty-image {
  width: 160px;
  height: 160px;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 14px;
  color: #999;
  margin-bottom: 24px;
}

.publish-btn {
  background-color: #2e7d32;
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 10px 32px;
  font-size: 14px;
}

.loading, .no-more {
  text-align: center;
  padding: 16px 16px 20px 16px;
  font-size: 14px;
  color: #999;
}

.tabbar-placeholder {
  height: calc(120rpx + env(safe-area-inset-bottom));
}

// 限时活动区块 - 升级版设计
.promo-section {
  position: relative;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  border-radius: 20px;
  margin: 8px 12px;
  padding: 20px 16px 16px;
  overflow: hidden;

  // 背景装饰
  .promo-bg-decor {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }

  .promo-bg-circle {
    position: absolute;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.3) 0%, rgba(255, 107, 107, 0) 70%);

    &.bg-circle-1 {
      width: 200px;
      height: 200px;
      top: -80px;
      right: -60px;
      animation: floatCircle 8s ease-in-out infinite;
    }

    &.bg-circle-2 {
      width: 120px;
      height: 120px;
      bottom: -40px;
      left: -30px;
      background: linear-gradient(135deg, rgba(255, 193, 7, 0.25) 0%, rgba(255, 193, 7, 0) 70%);
      animation: floatCircle 6s ease-in-out infinite reverse;
    }

    &.bg-circle-3 {
      width: 80px;
      height: 80px;
      top: 50%;
      left: 30%;
      background: linear-gradient(135deg, rgba(233, 30, 99, 0.2) 0%, rgba(233, 30, 99, 0) 70%);
      animation: floatCircle 10s ease-in-out infinite;
    }
  }

  .section-header {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
  }

  .promo-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .promo-icon-badge {
    position: relative;
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
  }

  .promo-icon {
    font-size: 22px;
    color: #fff;
    animation: flashIcon 1.5s ease-in-out infinite;
  }

  .icon-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 18px;
    border: 2px solid rgba(255, 107, 107, 0.5);
    animation: pulseRing 2s ease-out infinite;
  }

  .promo-title-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .section-title {
    margin-bottom: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.5px;
  }

  .promo-subtitle {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  // 倒计时盒子
  .countdown-box {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
  }

  .countdown-label {
    font-size: 10px;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .countdown-digits {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .digit-box {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.05) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 6px 8px;
    min-width: 32px;
    text-align: center;
  }

  .digit {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  }

  .digit-sep {
    font-size: 16px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.5);

    &.blink {
      animation: blinkSep 1s step-end infinite;
    }
  }

  // 查看全部按钮
  .promo-view-all {
    position: relative;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, rgba(255, 107, 107, 0.2) 0%, rgba(255, 107, 107, 0.1) 100%);
    border: 1px solid rgba(255, 107, 107, 0.3);
    border-radius: 20px;
    margin-bottom: 16px;
    transition: all 0.3s ease;

    text {
      font-size: 12px;
      font-weight: 600;
      color: #ff6b6b;
    }

    .bi {
      font-size: 12px;
      color: #ff6b6b;
      transition: transform 0.3s ease;
    }

    &:active {
      background: linear-gradient(135deg, rgba(255, 107, 107, 0.3) 0%, rgba(255, 107, 107, 0.2) 100%);

      .bi {
        transform: translateX(4px);
      }
    }
  }
}

// 动画关键帧
@keyframes flashIcon {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

@keyframes pulseRing {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.3);
    opacity: 0;
  }
}

@keyframes floatCircle {
  0%, 100% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(10px, -10px);
  }
}

@keyframes blinkSep {
  0%, 50% {
    opacity: 1;
  }
  51%, 100% {
    opacity: 0.3;
  }
}

.promo-scroll {
  position: relative;
  z-index: 2;
  white-space: nowrap;

  ::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
  }
}

.promo-goods-list {
  display: inline-flex;
  gap: 12px;
  padding-bottom: 8px;
}

.promo-goods-card {
  width: 140px;
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
  transition: transform 0.2s ease;

  &:active {
    transform: scale(0.98);
  }
}

.promo-goods-image-wrapper {
  position: relative;
  width: 140px;
  height: 140px;
  overflow: hidden;
  // background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  // padding: 8px;
  box-sizing: border-box;
}

.promo-goods-image {
  width: calc(100% - 16px);
  height: calc(100% - 16px);
  object-fit: contain;
  background-color: #fff;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.discount-tag {
  position: absolute;
  top: 8px;
  left: 8px;
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
  color: #fff;
  padding: 4px 8px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: baseline;
  gap: 2px;
  box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);

  .off-text {
    font-size: 9px;
    font-weight: 500;
  }
}

.promo-goods-info {
  padding: 10px;
  background: linear-gradient(180deg, #fff 0%, #fafafa 100%);
}

.promo-goods-title {
  font-size: 13px;
  font-weight: 500;
  color: #1a1a2e;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 1;
  overflow: hidden;
  white-space: normal;
}

.promo-price-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-top: 6px;
}

.promo-price {
  font-size: 16px;
  font-weight: 700;
  color: #ff6b6b;
}

.original-price {
  font-size: 11px;
  color: #999;
  text-decoration: line-through;
}

// 活动入口卡片 - 清爽现代设计
.promo-entries-section {
  position: relative;
  margin: 16px 12px;
  padding: 0;
  overflow: visible;

  .section-header {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .section-title {
    margin-bottom: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
  }

  .more-link {
    color: #666;
    font-size: 13px;

    &:active {
      color: #ff6b35;
    }
  }
}

// 促销卡片网格布局
.promo-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

// 促销卡片 - 简洁卡片风格
.promo-card {
  position: relative;
  border-radius: 16px;
  padding: 16px;
  overflow: hidden;
  min-height: 140px;
  cursor: pointer;
  transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, opacity 0.15s ease-out;
  display: flex;
  flex-direction: column;

  // 默认样式（浅灰背景 + 彩色点缀）
  background: #fff;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);

  &:active {
    transform: scale(0.96);
    opacity: 0.9;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
  }

  // DISCOUNT - 薄荷绿
  &.promo-type-1 {
    background: linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%);
    border-color: #bbf7d0;

    .promo-icon-wrapper {
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }
    .promo-card-title { color: #166534; }
    .promo-card-desc { color: #15803d; }
    .promo-shop-now { color: #22c55e; }
  }

  // REDUCTION - 暖阳橙
  &.promo-type-2 {
    background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%);
    border-color: #fde68a;

    .promo-icon-wrapper {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    .promo-card-title { color: #92400e; }
    .promo-card-desc { color: #a16207; }
    .promo-shop-now { color: #f59e0b; }
  }

  // SECKILL - 樱花粉
  &.promo-type-3 {
    background: linear-gradient(145deg, #fdf2f8 0%, #fce7f3 100%);
    border-color: #fbcfe8;

    .promo-icon-wrapper {
      background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
    }
    .promo-card-title { color: #9d174d; }
    .promo-card-desc { color: #be185d; }
    .promo-shop-now { color: #ec4899; }
  }

  // GROUP - 薰衣草紫
  &.promo-type-4 {
    background: linear-gradient(145deg, #f5f3ff 0%, #ede9fe 100%);
    border-color: #ddd6fe;

    .promo-icon-wrapper {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }
    .promo-card-title { color: #5b21b6; }
    .promo-card-desc { color: #6d28d9; }
    .promo-shop-now { color: #8b5cf6; }
  }
}

// 隐藏背景装饰
.promo-card-bg {
  display: none;
}

.promo-card-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  z-index: 1;
}

.promo-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.promo-icon-wrapper {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ff6b35 0%, #ff8f5a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);

  .bi {
    font-size: 16px;
    color: #fff;
  }
}

.promo-badge {
  padding: 4px 8px;
  background: #ff4757;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 0.5px;
}

.promo-card-body {
  flex: 1;
}

.promo-card-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  display: block;
  margin-bottom: 4px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.promo-card-desc {
  font-size: 11px;
  color: #666;
  display: block;
}

.promo-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 12px;
}

.promo-shop-now {
  font-size: 12px;
  font-weight: 600;
  color: #ff6b35;
}

.promo-arrow {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;

  .bi {
    font-size: 12px;
    color: #999;
  }
}

.promo-card:active .promo-arrow {
  transform: translateX(2px);
}

</style>
