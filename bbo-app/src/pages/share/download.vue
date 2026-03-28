<template>
  <view class="download-page">
    <!-- 顶部导航 -->
    <view class="nav-bar">
      <image class="nav-logo" src="/static/logo.png" mode="aspectFit" />
      <text class="nav-brand">TURNSY</text>
      <view class="nav-download-btn" @click="handleDownload">
        <text class="bi bi-download"></text>
        <text class="nav-btn-text">{{ t('share.download.navBtn') }}</text>
      </view>
    </view>

    <!-- Hero 区域 -->
    <view class="hero-section">
      <view class="hero-bg-decoration">
        <view class="hero-circle hero-circle-1"></view>
        <view class="hero-circle hero-circle-2"></view>
        <view class="hero-circle hero-circle-3"></view>
      </view>
      <view class="hero-content">
        <view class="hero-badge">
          <text class="bi bi-star-fill hero-badge-icon"></text>
          <text class="hero-badge-text">{{ t('share.download.badge') }}</text>
        </view>
        <text class="hero-title">{{ t('share.download.heroTitle1') }}</text>
        <text class="hero-title">{{ t('share.download.heroTitle2') }}</text>
        <text class="hero-subtitle">{{ t('share.download.heroSubtitle') }}</text>
        <view class="hero-actions">
          <view class="download-btn-primary" @click="handleDownload">
            <text class="bi bi-android2 download-btn-icon"></text>
            <view class="download-btn-info">
              <text class="download-btn-label">{{ t('share.download.androidDownload') }}</text>
              <text class="download-btn-version">{{ t('share.download.version') }}</text>
            </view>
          </view>
        </view>
        <view class="hero-trust-badges">
          <view class="trust-badge-item">
            <text class="bi bi-shield-check trust-badge-icon"></text>
            <text class="trust-badge-text">{{ t('share.download.trustSafety') }}</text>
          </view>
          <view class="trust-badge-item">
            <text class="bi bi-lock-fill trust-badge-icon"></text>
            <text class="trust-badge-text">{{ t('share.download.trustPrivacy') }}</text>
          </view>
          <view class="trust-badge-item">
            <text class="bi bi-arrow-repeat trust-badge-icon"></text>
            <text class="trust-badge-text">{{ t('share.download.trustFreeUpdate') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 数据统计 -->
    <view class="stats-section">
      <view class="stats-grid">
        <view class="stat-item">
          <text class="stat-number">{{ t('share.download.statUsersValue') }}</text>
          <text class="stat-label">{{ t('share.download.statUsers') }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-number">{{ t('share.download.statCountriesValue') }}</text>
          <text class="stat-label">{{ t('share.download.statCountries') }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-number">{{ t('share.download.statOrdersValue') }}</text>
          <text class="stat-label">{{ t('share.download.statOrders') }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-number">{{ t('share.download.statRatingValue') }}</text>
          <text class="stat-label">{{ t('share.download.statRating') }}</text>
        </view>
      </view>
    </view>

    <!-- 核心功能 -->
    <view class="features-section">
      <view class="section-header">
        <text class="section-tag">{{ t('share.download.whyChoose') }}</text>
        <text class="section-title">{{ t('share.download.featuresSectionTitle') }}</text>
      </view>
      <view class="features-grid">
        <view class="features-row" v-for="rowIndex in Math.ceil(features.length / 2)" :key="rowIndex">
          <view class="feature-card" v-for="(feature, index) in features.slice((rowIndex - 1) * 2, rowIndex * 2)" :key="index">
            <view class="feature-icon-wrapper" :style="{ background: feature.bgColor }">
              <text class="bi feature-icon" :class="feature.icon" :style="{ color: feature.color }"></text>
            </view>
            <text class="feature-title">{{ feature.title }}</text>
            <text class="feature-desc">{{ feature.desc }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 应用截图 -->
    <view class="screenshots-section">
      <view class="section-header">
        <text class="section-tag">{{ t('share.download.previewTag') }}</text>
        <text class="section-title">{{ t('share.download.previewTitle') }}</text>
      </view>
      <scroll-view scroll-x class="screenshots-scroll" :show-scrollbar="false">
        <view class="screenshots-list">
          <view v-for="(screen, index) in screenshots" :key="index" class="screenshot-card">
            <view class="screenshot-mockup">
              <view class="screenshot-notch"></view>
              <image
                v-if="previewImages[index]"
                class="screenshot-image"
                :src="previewImages[index]"
                mode="aspectFill"
              />
              <view v-else class="screenshot-content" :style="{ background: screen.bg }">
                <text class="bi screenshot-icon" :class="screen.icon"></text>
                <text class="screenshot-label">{{ screen.label }}</text>
              </view>
            </view>
            <text class="screenshot-title">{{ screen.title }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 用户评价 -->
    <view class="reviews-section">
      <view class="section-header">
        <text class="section-tag">{{ t('share.download.reviewsTag') }}</text>
        <text class="section-title">{{ t('share.download.reviewsTitle') }}</text>
      </view>

      <!-- 评分汇总 -->
      <view class="rating-summary">
        <view class="rating-score">
          <text class="rating-number">4.8</text>
          <view class="rating-stars">
            <text class="bi bi-star-fill star-icon" v-for="i in 5" :key="i"></text>
          </view>
          <text class="rating-count">{{ t('share.download.reviewsBasedOn') }}</text>
        </view>
        <view class="rating-bars">
          <view class="rating-bar-row" v-for="(bar, index) in ratingBars" :key="index">
            <text class="rating-bar-label">{{ 5 - index }}{{ t('share.download.starLabel') }}</text>
            <view class="rating-bar-track">
              <view class="rating-bar-fill" :style="{ width: bar.percent + '%' }"></view>
            </view>
            <text class="rating-bar-percent">{{ bar.percent }}%</text>
          </view>
        </view>
      </view>

      <!-- 评价列表 -->
      <view class="reviews-list">
        <view class="review-card" v-for="(review, index) in reviews" :key="index">
          <view class="review-header">
            <view class="review-avatar" :style="{ background: review.avatarBg }">
              <text class="review-avatar-text">{{ review.avatarText }}</text>
            </view>
            <view class="review-user-info">
              <text class="review-username">{{ review.username }}</text>
              <view class="review-meta">
                <view class="review-stars">
                  <text class="bi bi-star-fill review-star" v-for="i in review.rating" :key="i"></text>
                </view>
                <text class="review-date">{{ review.date }}</text>
              </view>
            </view>
          </view>
          <text class="review-content">{{ review.content }}</text>
          <view class="review-footer" v-if="review.tag">
            <view class="review-tag">
              <text class="bi" :class="review.tagIcon"></text>
              <text class="review-tag-text">{{ review.tag }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 安全保障 -->
    <view class="security-section">
      <view class="section-header">
        <text class="section-tag">{{ t('share.download.securityTag') }}</text>
        <text class="section-title">{{ t('share.download.securityTitle') }}</text>
      </view>
      <view class="security-grid">
        <view class="security-item" v-for="(item, index) in securityFeatures" :key="index">
          <text class="bi security-icon" :class="item.icon"></text>
          <view class="security-info">
            <text class="security-title">{{ item.title }}</text>
            <text class="security-desc">{{ item.desc }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 应用信息 -->
    <view class="app-info-section">
      <text class="app-info-title">{{ t('share.download.appInfoTitle') }}</text>
      <view class="app-info-grid">
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoVersion') }}</text>
          <text class="app-info-value">2.6.0</text>
        </view>
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoSize') }}</text>
          <text class="app-info-value">68 MB</text>
        </view>
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoSystem') }}</text>
          <text class="app-info-value">Android 8.0+</text>
        </view>
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoDate') }}</text>
          <text class="app-info-value">2026-03-18</text>
        </view>
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoLanguage') }}</text>
          <text class="app-info-value">{{ t('share.download.appInfoLanguageValue') }}</text>
        </view>
        <view class="app-info-item">
          <text class="app-info-label">{{ t('share.download.appInfoDeveloper') }}</text>
          <text class="app-info-value">{{ t('share.download.appInfoDeveloperValue') }}</text>
        </view>
      </view>
    </view>

    <!-- 底部 CTA -->
    <view class="bottom-cta-section">
      <view class="bottom-cta-content">
        <text class="bottom-cta-title">{{ t('share.download.ctaTitle') }}</text>
        <text class="bottom-cta-desc">{{ t('share.download.ctaDesc') }}</text>
        <view class="bottom-cta-btn" @click="handleDownload">
          <text class="bi bi-android2 bottom-cta-icon"></text>
          <text class="bottom-cta-btn-text">{{ t('share.download.ctaBtn') }}</text>
        </view>
      </view>
    </view>

    <!-- 页脚 -->
    <view class="footer">
      <image class="footer-logo" src="/static/logo.png" mode="aspectFit" />
      <text class="footer-brand">TURNSY</text>
      <text class="footer-copyright">{{ t('share.download.copyright') }}</text>
      <view class="footer-links">
        <text class="footer-link">{{ t('share.download.privacy') }}</text>
        <text class="footer-divider">|</text>
        <text class="footer-link">{{ t('share.download.terms') }}</text>
        <text class="footer-divider">|</text>
        <text class="footer-link">{{ t('share.download.contact') }}</text>
      </view>
    </view>

    <!-- 底部固定下载栏 -->
    <view class="fixed-download-bar" :class="{ 'show': showFixedBar }">
      <view class="fixed-bar-info">
        <image class="fixed-bar-logo" src="/static/logo.png" mode="aspectFit" />
        <view class="fixed-bar-text">
          <text class="fixed-bar-name">TURNSY</text>
          <text class="fixed-bar-desc">{{ t('share.download.fixedBarDesc') }}</text>
        </view>
      </view>
      <view class="fixed-bar-btn" @click="handleDownload">
        <text class="fixed-bar-btn-text">{{ t('share.download.fixedBarBtn') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { get } from '@/utils/request'

const { t } = useI18n()
const appStore = useAppStore()

// 应用预览图（从后端获取）
const previewImages = ref<string[]>([])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('page.download') })
})

// 加载配置和预览图
const configLoaded = ref(false)
onMounted(async () => {
  try {
    // 确保系统配置已获取（包含 apkDownloadUrl）
    if (!appStore.apkDownloadUrl) {
      await appStore.fetchSystemConfig()
    }
    configLoaded.value = true
  } catch (e) {
    configLoaded.value = true
  }
  try {
    const res = await get<string[]>('/system/app-previews')
    if (res.code === 0 && res.data) {
      previewImages.value = res.data
    }
  } catch (e) {
    // 加载失败使用默认图标占位
  }
})

// 是否显示底部固定栏
const showFixedBar = ref(false)

// 监听滚动
const handleScroll = () => {
  // #ifdef H5
  showFixedBar.value = window.scrollY > 600
  // #endif
}

onMounted(() => {
  // #ifdef H5
  window.addEventListener('scroll', handleScroll)
  // #endif

  // #ifndef H5
  showFixedBar.value = false
  // #endif
})

onUnmounted(() => {
  // #ifdef H5
  window.removeEventListener('scroll', handleScroll)
  // #endif
})

// 核心功能
const features = computed(() => [
  {
    icon: 'bi-globe2',
    title: t('share.download.featureGlobalTitle'),
    desc: t('share.download.featureGlobalDesc'),
    color: '#FF6B35',
    bgColor: 'rgba(255, 107, 53, 0.1)'
  },
  {
    icon: 'bi-shield-lock-fill',
    title: t('share.download.featureSecurityTitle'),
    desc: t('share.download.featureSecurityDesc'),
    color: '#10B981',
    bgColor: 'rgba(16, 185, 129, 0.1)'
  },
  {
    icon: 'bi-lightning-charge-fill',
    title: t('share.download.featureSpeedTitle'),
    desc: t('share.download.featureSpeedDesc'),
    color: '#6366F1',
    bgColor: 'rgba(99, 102, 241, 0.1)'
  },
  {
    icon: 'bi-chat-dots-fill',
    title: t('share.download.featureChatTitle'),
    desc: t('share.download.featureChatDesc'),
    color: '#F59E0B',
    bgColor: 'rgba(245, 158, 11, 0.1)'
  },
  {
    icon: 'bi-credit-card-2-front-fill',
    title: t('share.download.featurePaymentTitle'),
    desc: t('share.download.featurePaymentDesc'),
    color: '#EC4899',
    bgColor: 'rgba(236, 72, 153, 0.1)'
  },
  {
    icon: 'bi-trophy-fill',
    title: t('share.download.featureRewardTitle'),
    desc: t('share.download.featureRewardDesc'),
    color: '#8B5CF6',
    bgColor: 'rgba(139, 92, 246, 0.1)'
  }
])

// 应用截图
const screenshots = computed(() => [
  { icon: 'bi-house-fill', label: t('share.download.screenHome'), title: t('share.download.screenHomeTitle'), bg: 'linear-gradient(135deg, #FF6B35, #FF8F65)' },
  { icon: 'bi-grid-fill', label: t('share.download.screenCategory'), title: t('share.download.screenCategoryTitle'), bg: 'linear-gradient(135deg, #6366F1, #818CF8)' },
  { icon: 'bi-bag-fill', label: t('share.download.screenDetail'), title: t('share.download.screenDetailTitle'), bg: 'linear-gradient(135deg, #10B981, #34D399)' },
  { icon: 'bi-chat-fill', label: t('share.download.screenChat'), title: t('share.download.screenChatTitle'), bg: 'linear-gradient(135deg, #F59E0B, #FBBF24)' },
  { icon: 'bi-person-fill', label: t('share.download.screenProfile'), title: t('share.download.screenProfileTitle'), bg: 'linear-gradient(135deg, #EC4899, #F472B6)' }
])

// 评分分布
const ratingBars = ref([
  { percent: 72 },
  { percent: 18 },
  { percent: 6 },
  { percent: 3 },
  { percent: 1 }
])

// 用户评价
const reviews = computed(() => [
  {
    username: 'Chen W.',
    avatarText: 'C',
    avatarBg: '#FF6B35',
    rating: 5,
    date: '2026-03-15',
    content: t('share.download.review1'),
    tag: t('share.download.reviewTagVerified'),
    tagIcon: 'bi-patch-check-fill'
  },
  {
    username: 'Tanaka Y.',
    avatarText: 'T',
    avatarBg: '#6366F1',
    rating: 5,
    date: '2026-03-12',
    content: t('share.download.review2'),
    tag: t('share.download.reviewTagCrossBorder'),
    tagIcon: 'bi-globe2'
  },
  {
    username: 'David L.',
    avatarText: 'D',
    avatarBg: '#10B981',
    rating: 5,
    date: '2026-03-10',
    content: t('share.download.review3'),
    tag: t('share.download.reviewTagSeller'),
    tagIcon: 'bi-shop'
  },
  {
    username: 'Sarah K.',
    avatarText: 'S',
    avatarBg: '#EC4899',
    rating: 4,
    date: '2026-03-08',
    content: t('share.download.review4'),
    tag: null,
    tagIcon: ''
  },
  {
    username: 'Mike H.',
    avatarText: 'M',
    avatarBg: '#F59E0B',
    rating: 5,
    date: '2026-03-05',
    content: t('share.download.review5'),
    tag: t('share.download.reviewTagLoyal'),
    tagIcon: 'bi-heart-fill'
  },
  {
    username: 'Lisa W.',
    avatarText: 'L',
    avatarBg: '#8B5CF6',
    rating: 5,
    date: '2026-03-02',
    content: t('share.download.review6'),
    tag: t('share.download.reviewTagCrossBuyer'),
    tagIcon: 'bi-currency-exchange'
  }
])

// 安全特性
const securityFeatures = computed(() => [
  {
    icon: 'bi-shield-fill-check',
    title: t('share.download.securityEscrowTitle'),
    desc: t('share.download.securityEscrowDesc')
  },
  {
    icon: 'bi-fingerprint',
    title: t('share.download.securityVerifyTitle'),
    desc: t('share.download.securityVerifyDesc')
  },
  {
    icon: 'bi-lock-fill',
    title: t('share.download.securityEncryptTitle'),
    desc: t('share.download.securityEncryptDesc')
  },
  {
    icon: 'bi-headset',
    title: t('share.download.securitySupportTitle'),
    desc: t('share.download.securitySupportDesc')
  }
])

// 处理下载
async function handleDownload() {
  // 如果配置还没加载完，先等待
  if (!configLoaded.value) {
    uni.showLoading({ title: '' })
    try {
      await appStore.fetchSystemConfig()
      configLoaded.value = true
    } catch (e) {
      // ignore
    }
    uni.hideLoading()
  }

  const downloadUrl = appStore.apkDownloadUrl
  if (!downloadUrl) {
    // 再尝试一次获取
    try {
      await appStore.fetchSystemConfig()
    } catch (e) {
      // ignore
    }
    const retryUrl = appStore.apkDownloadUrl
    if (!retryUrl) {
      uni.showToast({ title: t('share.download.notAvailable') || 'Download not available', icon: 'none' })
      return
    }
    // #ifdef H5
    window.location.href = retryUrl
    // #endif
    return
  }

  // #ifdef H5
  window.location.href = downloadUrl
  // #endif

  // #ifndef H5
  uni.showToast({
    title: t('share.download.downloading'),
    icon: 'none',
    duration: 2000
  })
  // #endif
}
</script>

<style lang="scss" scoped>
.download-page {
  min-height: 100vh;
  background: #FAFAF9;
}

/* 导航栏 */
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  padding: 0 32rpx;
  height: calc(88rpx + var(--status-bar-height, 44rpx));
  padding-top: var(--status-bar-height, 44rpx);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20rpx);
  border-bottom: 1rpx solid rgba(0, 0, 0, 0.06);
}

.nav-logo {
  width: 56rpx;
  height: 56rpx;
  border-radius: 12rpx;
}

.nav-brand {
  font-size: 32rpx;
  font-weight: 700;
  color: #1C1917;
  margin-left: 16rpx;
  letter-spacing: 1rpx;
}

.nav-download-btn {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 14rpx 28rpx;
  background: #FF6B35;
  border-radius: 40rpx;
  color: #fff;
  font-size: 24rpx;
}

.nav-btn-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #fff;
}

/* Hero 区域 */
.hero-section {
  position: relative;
  padding: calc(88rpx + var(--status-bar-height, 44rpx) + 60rpx) 40rpx 80rpx;
  background: linear-gradient(160deg, #1C1917 0%, #292524 60%, #44403C 100%);
  overflow: hidden;
}

.hero-bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.hero-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.08;
}

.hero-circle-1 {
  width: 500rpx;
  height: 500rpx;
  background: #FF6B35;
  top: -100rpx;
  right: -150rpx;
}

.hero-circle-2 {
  width: 300rpx;
  height: 300rpx;
  background: #FF6B35;
  bottom: 50rpx;
  left: -80rpx;
}

.hero-circle-3 {
  width: 200rpx;
  height: 200rpx;
  background: #fff;
  top: 40%;
  right: 20%;
}

.hero-content {
  position: relative;
  z-index: 2;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  padding: 10rpx 24rpx;
  background: rgba(255, 107, 53, 0.15);
  border: 1rpx solid rgba(255, 107, 53, 0.3);
  border-radius: 40rpx;
  margin-bottom: 40rpx;
}

.hero-badge-icon {
  font-size: 22rpx;
  color: #FF6B35;
}

.hero-badge-text {
  font-size: 24rpx;
  color: #FF6B35;
  font-weight: 500;
}

.hero-title {
  display: block;
  font-size: 56rpx;
  font-weight: 800;
  color: #FFFFFF;
  line-height: 1.3;
  letter-spacing: 2rpx;
}

.hero-subtitle {
  display: block;
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.65);
  line-height: 1.7;
  margin-top: 30rpx;
  margin-bottom: 50rpx;
}

.hero-actions {
  margin-bottom: 50rpx;
}

.download-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 20rpx;
  padding: 24rpx 48rpx;
  background: #FF6B35;
  border-radius: 16rpx;
  box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.35);
}

.download-btn-icon {
  font-size: 44rpx;
  color: #fff;
}

.download-btn-info {
  display: flex;
  flex-direction: column;
}

.download-btn-label {
  font-size: 30rpx;
  font-weight: 700;
  color: #fff;
}

.download-btn-version {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.75);
  margin-top: 4rpx;
}

.hero-trust-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx 40rpx;
}

.trust-badge-item {
  display: flex;
  align-items: center;
  gap: 8rpx;
}

.trust-badge-icon {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.5);
  flex-shrink: 0;
}

.trust-badge-text {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.5);
}

/* 统计数据 */
.stats-section {
  margin: -40rpx 30rpx 0;
  position: relative;
  z-index: 10;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  padding: 0;
  background: #FFFFFF;
  border-radius: 20rpx;
  box-shadow: 0 8rpx 40rpx rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32rpx 16rpx;
  border-bottom: 1rpx solid #F5F5F0;
  border-right: 1rpx solid #F5F5F0;
}

.stat-item:nth-child(2n) {
  border-right: none;
}

.stat-item:nth-child(n+3) {
  border-bottom: none;
}

.stat-number {
  font-size: 36rpx;
  font-weight: 800;
  color: #1C1917;
  white-space: nowrap;
}

.stat-label {
  font-size: 22rpx;
  color: #78716C;
  margin-top: 6rpx;
  text-align: center;
  word-break: break-word;
}

.stat-divider {
  display: none;
}

/* 通用 Section Header */
.section-header {
  text-align: center;
  margin-bottom: 50rpx;
}

.section-tag {
  display: block;
  font-size: 24rpx;
  color: #FF6B35;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 2rpx;
  margin-bottom: 12rpx;
}

.section-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: #1C1917;
}

/* 核心功能 */
.features-section {
  padding: 80rpx 30rpx;
}

.features-grid {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.features-row {
  display: flex;
  gap: 24rpx;
}

.feature-card {
  flex: 1;
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 36rpx 28rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
  border: 1rpx solid rgba(0, 0, 0, 0.04);
}

.feature-icon-wrapper {
  width: 80rpx;
  height: 80rpx;
  border-radius: 18rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24rpx;
}

.feature-icon {
  font-size: 36rpx;
}

.feature-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: #1C1917;
  margin-bottom: 10rpx;
}

.feature-desc {
  display: block;
  font-size: 24rpx;
  color: #78716C;
  line-height: 1.6;
}

/* 应用截图 */
.screenshots-section {
  padding: 80rpx 0;
  background: #F5F5F0;
}

.screenshots-section .section-header {
  padding: 0 30rpx;
}

.screenshots-scroll {
  width: 100%;
}

.screenshots-list {
  display: inline-flex;
  gap: 40rpx;
  padding: 0 30rpx 20rpx;
}

.screenshot-card {
  width: 280rpx;
}

.screenshot-mockup {
  width: 100%;
  height: 480rpx;
  background: #1C1917;
  border-radius: 36rpx;
  padding: 16rpx;
  position: relative;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.screenshot-notch {
  width: 120rpx;
  height: 28rpx;
  background: #1C1917;
  border-radius: 0 0 16rpx 16rpx;
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.screenshot-image {
  width: 100%;
  height: 100%;
  border-radius: 24rpx;
}

.screenshot-content {
  width: 100%;
  height: 100%;
  border-radius: 24rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.screenshot-icon {
  font-size: 64rpx;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 16rpx;
}

.screenshot-label {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 500;
}

.screenshot-title {
  display: block;
  font-size: 26rpx;
  color: #44403C;
  font-weight: 500;
  margin-top: 16rpx;
  white-space: normal;
  text-align: center;
}

/* 用户评价 */
.reviews-section {
  padding: 80rpx 30rpx;
}

.rating-summary {
  display: flex;
  flex-direction: column;
  gap: 32rpx;
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 40rpx;
  margin-bottom: 32rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.rating-score {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.rating-number {
  font-size: 72rpx;
  font-weight: 800;
  color: #1C1917;
  line-height: 1;
}

.rating-stars {
  display: flex;
  gap: 4rpx;
  margin-top: 12rpx;
}

.star-icon {
  font-size: 24rpx;
  color: #F59E0B;
}

.rating-count {
  font-size: 20rpx;
  color: #78716C;
  margin-top: 10rpx;
  white-space: nowrap;
}

.rating-bars {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14rpx;
  justify-content: center;
}

.rating-bar-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
}

.rating-bar-label {
  font-size: 22rpx;
  color: #78716C;
  white-space: nowrap;
  text-align: right;
  flex-shrink: 0;
}

.rating-bar-track {
  flex: 1;
  height: 12rpx;
  background: #F5F5F0;
  border-radius: 6rpx;
  overflow: hidden;
}

.rating-bar-fill {
  height: 100%;
  background: #F59E0B;
  border-radius: 6rpx;
  transition: width 0.6s ease;
}

.rating-bar-percent {
  font-size: 22rpx;
  color: #78716C;
  width: 64rpx;
  text-align: right;
  flex-shrink: 0;
}

/* 评价卡片 */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.review-card {
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 36rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.review-header {
  display: flex;
  align-items: center;
  gap: 20rpx;
  margin-bottom: 20rpx;
}

.review-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.review-avatar-text {
  font-size: 28rpx;
  font-weight: 700;
  color: #FFFFFF;
}

.review-user-info {
  flex: 1;
}

.review-username {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #1C1917;
  margin-bottom: 6rpx;
}

.review-meta {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.review-stars {
  display: flex;
  gap: 4rpx;
}

.review-star {
  font-size: 20rpx;
  color: #F59E0B;
}

.review-date {
  font-size: 22rpx;
  color: #A8A29E;
}

.review-content {
  display: block;
  font-size: 28rpx;
  color: #44403C;
  line-height: 1.7;
}

.review-footer {
  margin-top: 20rpx;
}

.review-tag {
  display: inline-flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 20rpx;
  background: #FFF4F0;
  border-radius: 30rpx;
  color: #FF6B35;
  font-size: 22rpx;
}

.review-tag-text {
  font-size: 22rpx;
  color: #FF6B35;
  font-weight: 500;
}

/* 安全保障 */
.security-section {
  padding: 80rpx 30rpx;
  background: #F5F5F0;
}

.security-grid {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.security-item {
  display: flex;
  align-items: flex-start;
  gap: 24rpx;
  background: #FFFFFF;
  border-radius: 20rpx;
  padding: 36rpx;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.security-icon {
  font-size: 40rpx;
  color: #FF6B35;
  flex-shrink: 0;
  margin-top: 4rpx;
}

.security-info {
  flex: 1;
}

.security-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: #1C1917;
  margin-bottom: 8rpx;
}

.security-desc {
  display: block;
  font-size: 26rpx;
  color: #78716C;
  line-height: 1.6;
}

/* 应用信息 */
.app-info-section {
  padding: 60rpx 30rpx;
}

.app-info-title {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: #1C1917;
  margin-bottom: 30rpx;
}

.app-info-grid {
  background: #FFFFFF;
  border-radius: 20rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}

.app-info-item {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 24rpx;
  padding: 28rpx 32rpx;
  border-bottom: 1rpx solid #F5F5F0;
}

.app-info-item:last-child {
  border-bottom: none;
}

.app-info-label {
  font-size: 28rpx;
  color: #78716C;
  flex-shrink: 0;
}

.app-info-value {
  font-size: 28rpx;
  color: #1C1917;
  font-weight: 500;
  text-align: right;
  word-break: break-word;
}

/* 底部 CTA */
.bottom-cta-section {
  padding: 80rpx 30rpx;
  background: linear-gradient(160deg, #1C1917 0%, #292524 100%);
}

.bottom-cta-content {
  text-align: center;
}

.bottom-cta-title {
  display: block;
  font-size: 42rpx;
  font-weight: 700;
  color: #FFFFFF;
  margin-bottom: 16rpx;
}

.bottom-cta-desc {
  display: block;
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 48rpx;
}

.bottom-cta-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 16rpx;
  padding: 28rpx 64rpx;
  background: #FF6B35;
  border-radius: 50rpx;
  box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.35);
}

.bottom-cta-icon {
  font-size: 36rpx;
  color: #fff;
}

.bottom-cta-btn-text {
  font-size: 30rpx;
  font-weight: 700;
  color: #fff;
}

/* 页脚 */
.footer {
  padding: 60rpx 30rpx calc(60rpx + env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #FAFAF9;
  border-top: 1rpx solid #E7E5E4;
}

.footer-logo {
  width: 60rpx;
  height: 60rpx;
  border-radius: 14rpx;
  margin-bottom: 12rpx;
}

.footer-brand {
  font-size: 28rpx;
  font-weight: 700;
  color: #1C1917;
  letter-spacing: 2rpx;
  margin-bottom: 16rpx;
}

.footer-copyright {
  font-size: 22rpx;
  color: #A8A29E;
  margin-bottom: 20rpx;
}

.footer-links {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.footer-link {
  font-size: 22rpx;
  color: #78716C;
}

.footer-divider {
  font-size: 20rpx;
  color: #D6D3D1;
}

/* 底部固定下载栏 */
.fixed-download-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 32rpx;
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(20rpx);
  border-top: 1rpx solid rgba(0, 0, 0, 0.06);
  transform: translateY(100%);
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.fixed-download-bar.show {
  transform: translateY(0);
}

.fixed-bar-info {
  display: flex;
  align-items: center;
  gap: 16rpx;
}

.fixed-bar-logo {
  width: 64rpx;
  height: 64rpx;
  border-radius: 14rpx;
}

.fixed-bar-text {
  display: flex;
  flex-direction: column;
}

.fixed-bar-name {
  font-size: 28rpx;
  font-weight: 700;
  color: #1C1917;
}

.fixed-bar-desc {
  font-size: 22rpx;
  color: #78716C;
}

.fixed-bar-btn {
  padding: 18rpx 40rpx;
  background: #FF6B35;
  border-radius: 40rpx;
}

.fixed-bar-btn-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #fff;
}

/* H5 适配 */
// #ifdef H5
.download-page {
  padding-bottom: 0;
}
// #endif
</style>
