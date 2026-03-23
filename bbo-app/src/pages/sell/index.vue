<template>
  <view class="page">
    <!-- 加载页 -->
    <LoadingPage v-model="loading" />

    <!-- 自定义导航栏 -->
    <NavBar :title="t('sell.title')" :show-back="false">
      <template #right>
        <view class="nav-action" @click="goCart">
          <text class="bi bi-cart2"></text>
        </view>
      </template>
    </NavBar>

    <!-- 页面内容 -->
    <scroll-view
      class="content"
      scroll-y
      :show-scrollbar="false"
      :enhanced="true"
      :bounces="false"
    >
      <!-- 顶部 Banner 图片 -->
      <view class="hero-banner">
        <image
          class="hero-image"
          src="/static/sell/sell.png"
          mode="widthFix"
          lazy-load
        />
        <view class="hero-overlay">
          <text class="hero-title">{{ t('sell.heroTitle') }}</text>
          <text class="hero-subtitle">{{ t('sell.heroSubtitle') }}</text>
        </view>
      </view>

      <!-- 草稿区域 - 横向滚动 -->
      <view v-if="drafts.length > 0" class="drafts-section">
        <view class="section-header">
          <text class="section-label">{{ t('sell.draftTitle') }}</text>
          <text class="section-more" @click="goMyListings">{{ t('common.more') }}</text>
        </view>
        <scroll-view class="drafts-scroll" scroll-x :show-scrollbar="false" :enhanced="true" :bounces="false">
          <view class="drafts-list">
            <view
              v-for="item in drafts"
              :key="item.id"
              class="draft-card"
              @click="continueDraft(item)"
            >
              <view class="draft-image-wrapper">
                <image
                  v-if="item.images && item.images.length > 0"
                  class="draft-image"
                  :src="item.images[0]"
                  mode="aspectFill"
                />
                <view v-else class="draft-placeholder">
                  <text class="bi bi-image"></text>
                </view>
              </view>
              <view class="draft-info">
                <text class="draft-title">{{ item.title || t('sell.draftNoTitle') }}</text>
                <text class="draft-meta">{{ t('sell.step') }} {{ item.current_step || 1 }}/4</text>
              </view>
            </view>
          </view>
        </scroll-view>
      </view>

      <!-- 主要操作按钮 -->
      <view class="main-action" @click="goPublish">
        <view class="action-icon">
          <text class="bi bi-plus-lg"></text>
        </view>
        <view class="action-text">
          <text class="action-title">{{ t('sell.publishButton') }}</text>
          <text class="action-desc">{{ t('sell.publishDesc') }}</text>
        </view>
        <text class="bi bi-chevron-right action-arrow"></text>
      </view>

      <!-- 卖家说明 -->
      <view class="seller-info">
        <!-- 快速刊登 -->
        <view class="info-item">
          <text class="info-title">{{ t('sell.infoFastTitle') }}</text>
          <text class="info-desc">{{ t('sell.infoFastDesc') }}</text>
          <text class="info-link" @click="goToFees">{{ t('sell.infoFastLink') }}</text>
        </view>

        <!-- 安全收款 -->
        <view class="info-item">
          <text class="info-title">{{ t('sell.infoSecureTitle') }}</text>
          <text class="info-desc">{{ t('sell.infoSecureDesc') }}</text>
        </view>

        <!-- 轻松运送 -->
        <view class="info-item">
          <text class="info-title">{{ t('sell.infoShippingTitle') }}</text>
          <text class="info-desc">{{ t('sell.infoShippingDesc') }}</text>
        </view>
      </view>

      <!-- 促销信息 -->
      <view class="promo-banner">
        <text class="promo-text">{{ t('sell.promoTitle') }}</text>
        <text class="promo-sub">{{ t('sell.promoDesc') }}</text>
      </view>

      <!-- 常见问题 -->
      <view v-if="faqList.length > 0" class="faq-section">
        <text class="faq-title">{{ t('sell.faqTitle') }}</text>
        <view class="faq-list">
          <view
            v-for="(faq, index) in faqList"
            :key="faq.id"
            class="faq-item"
            @click="toggleFaq(index)"
          >
            <view class="faq-header">
              <text class="faq-question">{{ faq.question }}</text>
              <text class="bi faq-icon" :class="expandedFaq === index ? 'bi-chevron-up' : 'bi-chevron-down'"></text>
            </view>
            <view v-if="expandedFaq === index" class="faq-answer">
              <text>{{ faq.answer }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部留白 -->
      <view class="bottom-spacer"></view>
    </scroll-view>

    <!-- 自定义 TabBar -->
    <CustomTabBar :current="2" />
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getDraft, type GoodsDraft } from '@/api/goods'
import { get } from '@/utils/request'
import { API_PATHS } from '@/config/api'
import { useUserStore } from '@/store/modules/user'
import LoadingPage from '@/components/LoadingPage.vue'
import CustomTabBar from '@/components/CustomTabBar.vue'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const userStore = useUserStore()

// FAQ 数据类型
interface FaqItem {
  id: number
  question: string
  answer: string
}

// 状态
const loading = ref(true)
const drafts = ref<GoodsDraft[]>([])
const expandedFaq = ref<number | null>(null)
const faqList = ref<FaqItem[]>([])

// 初始化
onMounted(async () => {
  // 并行加载数据
  const promises: Promise<void>[] = [loadFaqs()]
  if (userStore.isLoggedIn) {
    promises.push(loadDrafts())
  }
  await Promise.all(promises)
  loading.value = false
})

// 加载 FAQ 列表
async function loadFaqs() {
  try {
    const res = await get<FaqItem[]>(API_PATHS.system.sellFaqs)
    if (res.code === 0 && res.data) {
      faqList.value = res.data
    }
  } catch (e) {
    console.error('Load FAQs failed:', e)
  }
}

// 加载草稿列表
async function loadDrafts() {
  try {
    const res = await getDraft()
    if (res.code === 0 && res.data) {
      // API 当前返回单个草稿，包装为数组
      drafts.value = [res.data]
    }
  } catch (e) {
    console.error('Load drafts failed:', e)
  }
}

// 切换 FAQ
function toggleFaq(index: number) {
  expandedFaq.value = expandedFaq.value === index ? null : index
}

// 继续编辑草稿
function continueDraft(item: GoodsDraft) {
  uni.navigateTo({
    url: `/pages/publish/index?from=draft&id=${item.id}`
  })
}

// 前往购物车
function goCart() {
  uni.navigateTo({
    url: '/pages/cart/index'
  })
}

// 前往我的商品列表
function goMyListings() {
  uni.navigateTo({
    url: '/pages/user/listings/index'
  })
}

// 前往发布页面
function goPublish() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({
      url: '/pages/auth/login'
    })
    return
  }

  uni.navigateTo({
    url: '/pages/publish/index'
  })
}

function goToFees() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=fee-policy' })
}

onShow(() => {
  // #ifdef APP-PLUS
  uni.hideTabBar({ animation: false })
  // #endif
})
</script>

<style lang="scss" scoped>
// ==========================================
// 简洁高级设计 - 黑白灰配色
// ==========================================

$color-black: #000000;
$color-dark: #1a1a1a;
$color-gray-900: #262626;
$color-gray-700: #404040;
$color-gray-500: #737373;
$color-gray-400: #a3a3a3;
$color-gray-300: #d4d4d4;
$color-gray-200: #e5e5e5;
$color-gray-100: #f5f5f5;
$color-white: #ffffff;

$font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Helvetica Neue', sans-serif;

$tabbar-height: 70px;

// ==========================================
// 页面结构
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-white;
  font-family: $font-family;
}

// 内容区域
.content {
  flex: 1;
  padding-bottom: calc($tabbar-height + 20px);
  // 优化滚动性能
  -webkit-overflow-scrolling: touch;
  will-change: scroll-position;
}

// ==========================================
// Hero Banner 区域
// ==========================================

.hero-banner {
  position: relative;
  margin: 0 0 24px;
  // border-radius: 16px;
  overflow: hidden;
}

.hero-image {
  width: 100%;
  display: block;
}

.hero-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 24px 20px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
}

.hero-title {
  display: block;
  font-size: 22px;
  font-weight: 700;
  color: $color-white;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.hero-subtitle {
  display: block;
  margin-top: 4px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
  letter-spacing: -0.2px;
}

// ==========================================
// 草稿区域 - 横向滚动
// ==========================================

.drafts-section {
  margin-bottom: 24px;
}

.section-header {
  padding: 0 20px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-label {
  font-size: 13px;
  font-weight: 600;
  color: $color-gray-500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-more {
  font-size: 13px;
  color: #0066cc;
}

.drafts-scroll {
  width: 100%;
  white-space: nowrap;
  -webkit-overflow-scrolling: touch;
}

.drafts-list {
  display: inline-flex;
  gap: 12px;
  padding: 0 20px;
}

.draft-card {
  display: inline-flex;
  flex-direction: column;
  width: 140px;
  background-color: $color-gray-100;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  flex-shrink: 0;

  &:active {
    background-color: $color-gray-200;
  }
}

.draft-image-wrapper {
  width: 100%;
  height: 140px;
  overflow: hidden;
}

.draft-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.draft-placeholder {
  width: 100%;
  height: 100%;
  background-color: $color-gray-200;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 24px;
    color: $color-gray-400;
  }
}

.draft-info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.draft-title {
  font-size: 13px;
  font-weight: 500;
  color: $color-black;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.draft-meta {
  font-size: 11px;
  color: $color-gray-500;
}

// ==========================================
// 主要操作按钮
// ==========================================

.main-action {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 0 20px 32px;
  padding: 20px;
  background-color: $color-black;
  border-radius: 16px;
  cursor: pointer;

  &:active {
    background-color: $color-dark;
  }
}

.action-icon {
  width: 48px;
  height: 48px;
  background-color: $color-white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 22px;
    color: $color-black;
  }
}

.action-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.action-title {
  font-size: 17px;
  font-weight: 600;
  color: $color-white;
  letter-spacing: -0.3px;
}

.action-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

.action-arrow {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.4);
  flex-shrink: 0;
}

// ==========================================
// 卖家说明
// ==========================================

.seller-info {
  padding: 0 20px;
}

.info-item {
  padding: 20px 0;
  border-bottom: 1px solid $color-gray-200;

  &:last-child {
    border-bottom: none;
  }
}

.info-title {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: $color-black;
  letter-spacing: -0.3px;
  margin-bottom: 8px;
}

.info-desc {
  display: block;
  font-size: 14px;
  color: $color-gray-500;
  line-height: 1.6;
}

.info-link {
  display: inline-block;
  margin-top: 8px;
  font-size: 14px;
  color: #0066cc;
  text-decoration: underline;
}

// ==========================================
// 促销信息
// ==========================================

.promo-banner {
  margin: 24px 20px 0;
  padding: 20px 24px;
  background-color: $color-gray-100;
  border-radius: 12px;
  text-align: center;
}

.promo-text {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: $color-black;
  letter-spacing: -0.3px;
}

.promo-sub {
  display: block;
  margin-top: 4px;
  font-size: 13px;
  color: $color-gray-500;
}

// ==========================================
// 常见问题
// ==========================================

.faq-section {
  padding: 24px 20px 0;
}

.faq-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: $color-gray-500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
}

.faq-list {
  background-color: $color-gray-100;
  border-radius: 12px;
  overflow: hidden;
}

.faq-item {
  cursor: pointer;

  &:not(:last-child) {
    border-bottom: 1px solid $color-gray-200;
  }
}

.faq-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  gap: 12px;
}

.faq-question {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  color: $color-black;
  letter-spacing: -0.2px;
}

.faq-icon {
  font-size: 14px;
  color: $color-gray-400;
  flex-shrink: 0;
}

.faq-answer {
  padding: 0 16px 16px;

  text {
    font-size: 14px;
    color: $color-gray-500;
    line-height: 1.5;
  }
}

// ==========================================
// 底部留白
// ==========================================

.bottom-spacer {
  height: 24px;
}

// ==========================================
// 减少动画
// ==========================================

@media (prefers-reduced-motion: reduce) {
  .main-action,
  .draft-card {
    transition: none;
  }
}
</style>
