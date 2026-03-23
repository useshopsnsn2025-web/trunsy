<template>
  <view class="page">
    <NavBar :title="t('about.title')" />

    <scroll-view class="content" scroll-y>
      <!-- Hero 区域 - 渐变背景 + 玻璃卡片 -->
      <view class="hero-section">
        <view class="hero-bg">
          <view class="bg-gradient"></view>
          <view class="bg-pattern"></view>
        </view>

        <view class="hero-content">
          <view class="logo-container">
            <image v-if="siteLogo" :src="siteLogo" class="logo-image" mode="aspectFit" />
            <view class="logo-glow"></view>
          </view>
          <text class="hero-title">{{ t('about.heroTitle') }}</text>
          <text class="hero-subtitle">{{ t('about.heroSubtitle') }}</text>
        </view>

        <!-- 数据统计 - 玻璃卡片 -->
        <view class="stats-card">
          <view class="stat-item">
            <text class="stat-number">50M+</text>
            <text class="stat-label">{{ t('about.statUsers') }}</text>
          </view>
          <view class="stat-divider"></view>
          <view class="stat-item">
            <text class="stat-number">190+</text>
            <text class="stat-label">{{ t('about.statCountries') }}</text>
          </view>
          <view class="stat-divider"></view>
          <view class="stat-item">
            <text class="stat-number">100M+</text>
            <text class="stat-label">{{ t('about.statTransactions') }}</text>
          </view>
        </view>
      </view>

      <!-- 使命愿景 - 双卡片布局 -->
      <view class="mission-section">
        <view class="mission-card">
          <view class="card-icon mission-icon">
            <text class="bi bi-bullseye"></text>
          </view>
          <view class="card-content">
            <text class="card-title">{{ t('about.missionTitle') }}</text>
            <text class="card-text">{{ t('about.missionText') }}</text>
          </view>
        </view>

        <view class="mission-card">
          <view class="card-icon vision-icon">
            <text class="bi bi-eye"></text>
          </view>
          <view class="card-content">
            <text class="card-title">{{ t('about.visionTitle') }}</text>
            <text class="card-text">{{ t('about.visionText') }}</text>
          </view>
        </view>
      </view>

      <!-- 核心优势 - 2x3 网格 -->
      <view class="features-section">
        <text class="section-title">{{ t('about.whyChooseUs') }}</text>

        <view class="feature-grid">
          <view class="feature-card featured">
            <view class="feature-icon-wrap authentic">
              <text class="bi bi-patch-check-fill"></text>
            </view>
            <text class="feature-title">{{ t('about.featureAuthenticTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureAuthenticDesc') }}</text>
          </view>

          <view class="feature-card featured">
            <view class="feature-icon-wrap buyer">
              <text class="bi bi-shield-fill-check"></text>
            </view>
            <text class="feature-title">{{ t('about.featureBuyerTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureBuyerDesc') }}</text>
          </view>

          <view class="feature-card">
            <view class="feature-icon-wrap">
              <text class="bi bi-lock-fill"></text>
            </view>
            <text class="feature-title">{{ t('about.featureSecureTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureSecureDesc') }}</text>
          </view>

          <view class="feature-card">
            <view class="feature-icon-wrap">
              <text class="bi bi-globe2"></text>
            </view>
            <text class="feature-title">{{ t('about.featureGlobalTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureGlobalDesc') }}</text>
          </view>

          <view class="feature-card">
            <view class="feature-icon-wrap">
              <text class="bi bi-lightning-charge-fill"></text>
            </view>
            <text class="feature-title">{{ t('about.featureFastTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureFastDesc') }}</text>
          </view>

          <view class="feature-card">
            <view class="feature-icon-wrap">
              <text class="bi bi-headset"></text>
            </view>
            <text class="feature-title">{{ t('about.featureSupportTitle') }}</text>
            <text class="feature-desc">{{ t('about.featureSupportDesc') }}</text>
          </view>
        </view>
      </view>

      <!-- 信任背书 - 认证徽章 -->
      <view class="trust-section">
        <text class="section-title">{{ t('about.trustedBy') }}</text>

        <view class="trust-badges">
          <view class="trust-badge">
            <view class="badge-icon">
              <text class="bi bi-patch-check-fill"></text>
            </view>
            <text class="badge-text">{{ t('about.badgeVerified') }}</text>
          </view>
          <view class="trust-badge">
            <view class="badge-icon ssl">
              <text class="bi bi-shield-lock-fill"></text>
            </view>
            <text class="badge-text">{{ t('about.badgeSSL') }}</text>
          </view>
          <view class="trust-badge">
            <view class="badge-icon pci">
              <text class="bi bi-credit-card-2-front-fill"></text>
            </view>
            <text class="badge-text">{{ t('about.badgePCI') }}</text>
          </view>
        </view>

        <view class="certifications">
          <view class="cert-item">
            <text class="bi bi-award-fill cert-icon"></text>
            <text class="cert-text">ISO 27001</text>
          </view>
          <view class="cert-item">
            <text class="bi bi-shield-fill-check cert-icon"></text>
            <text class="cert-text">GDPR</text>
          </view>
          <view class="cert-item">
            <text class="bi bi-check-circle-fill cert-icon"></text>
            <text class="cert-text">SOC 2</text>
          </view>
        </view>
      </view>

      <!-- 联系我们 -->
      <view class="contact-section">
        <text class="section-title">{{ t('about.contactTitle') }}</text>
        <text class="section-subtitle">{{ t('about.contactDesc') }}</text>

        <view class="contact-list">
          <view class="contact-item" @click="copyEmail">
            <view class="contact-icon-wrap">
              <text class="bi bi-envelope-fill"></text>
            </view>
            <view class="contact-info">
              <text class="contact-label">{{ t('about.email') }}</text>
              <text class="contact-value">{{ siteEmail || '-' }}</text>
            </view>
            <view class="contact-action">
              <text class="bi bi-copy"></text>
            </view>
          </view>

          <view class="contact-item" @click="goTickets">
            <view class="contact-icon-wrap help">
              <text class="bi bi-chat-dots-fill"></text>
            </view>
            <view class="contact-info">
              <text class="contact-label">{{ t('about.helpCenter') }}</text>
              <text class="contact-value">{{ t('about.helpCenterDesc') }}</text>
            </view>
            <view class="contact-action">
              <text class="bi bi-chevron-right"></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 版本信息 -->
      <view class="footer-section">
        <text class="version-text">{{ t('about.version') }}: {{ siteVersion }}</text>
        <text class="copyright-text">{{ copyrightText }}</text>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'
import { useToast } from '@/composables/useToast'
import { useAppStore } from '@/store/modules/app'

const { t } = useI18n()
const toast = useToast()
const appStore = useAppStore()

// 从 appStore 获取配置
const siteName = computed(() => appStore.appName || 'TURNSY')
const siteLogo = computed(() => appStore.appLogo || '')
const siteVersion = computed(() => appStore.appVersion || '1.0.0')
const siteEmail = computed(() => appStore.contactEmail || '')

// 动态生成版权文本
const copyrightText = computed(() => {
  const year = new Date().getFullYear()
  return `Copyright ${year} ${siteName.value} Inc. All rights reserved.`
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('about.title') })
  // 确保配置已加载
  if (!appStore.appName) {
    appStore.fetchSystemConfig()
  }
})

function copyEmail() {
  if (!siteEmail.value) return
  uni.setClipboardData({
    data: siteEmail.value,
    showToast: false,
    success: () => {
      toast.success(t('common.copied'))
    }
  })
}

function goTickets() {
  uni.navigateTo({ url: '/pages/guide/index' })
}
</script>

<style lang="scss" scoped>
// ===== 设计系统 =====
// 信任蓝色系 - 专业、可靠
$color-primary: #3B82F6;
$color-primary-dark: #2563EB;
$color-primary-light: #60A5FA;
$color-primary-bg: rgba(59, 130, 246, 0.08);

// CTA 橙色 - 活力、行动
$color-cta: #F97316;
$color-cta-light: #FB923C;

// 中性色
$color-text: #1E293B;
$color-text-secondary: #475569;
$color-text-muted: #64748B;
$color-background: #F8FAFC;
$color-surface: #FFFFFF;
$color-border: #E2E8F0;

// 功能色
$color-success: #10B981;
$color-success-light: #34D399;

// 玻璃效果
$glass-bg: rgba(255, 255, 255, 0.85);
$glass-border: rgba(255, 255, 255, 0.6);
$glass-shadow: 0 8px 32px rgba(31, 38, 135, 0.12);

// ===== 页面基础 =====
.page {
  min-height: 100vh;
  background-color: $color-background;
}

.content {
  flex: 1;
}

// ===== Hero 区域 =====
.hero-section {
  position: relative;
  padding: 40px 20px 60px;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 0;
}

.bg-gradient {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 40%, #1E40AF 100%);
}

.bg-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image:
    radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.25) 0%, transparent 50%),
    radial-gradient(circle at 80% 70%, rgba(16, 185, 129, 0.15) 0%, transparent 40%),
    radial-gradient(circle at 50% 50%, rgba(249, 115, 22, 0.1) 0%, transparent 60%);
}

.hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.logo-container {
  position: relative;
  margin-bottom: 24px;
}

.logo-image {
  width: 88px;
  height: 88px;
  border-radius: 20px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.logo-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.4) 0%, transparent 70%);
  z-index: -1;
}

.hero-title {
  font-size: 26px;
  font-weight: 700;
  color: #FFFFFF;
  line-height: 1.3;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.hero-subtitle {
  font-size: 15px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  max-width: 320px;
}

// ===== 统计卡片 - 玻璃效果 =====
.stats-card {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin: 32px 0 -40px;
  padding: 24px 16px;
  background: $glass-bg;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  border: 1px solid $glass-border;
  box-shadow: $glass-shadow;
}

.stat-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-number {
  font-size: 28px;
  font-weight: 800;
  color: $color-primary;
  letter-spacing: -1px;
}

.stat-label {
  font-size: 12px;
  color: $color-text-secondary;
  margin-top: 4px;
}

.stat-divider {
  width: 1px;
  height: 48px;
  background: linear-gradient(to bottom, transparent, $color-border, transparent);
}

// ===== 使命愿景 =====
.mission-section {
  padding: 56px 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.mission-card {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: $color-surface;
  border-radius: 16px;
  border: 1px solid $color-border;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 22px;
    color: #FFFFFF;
  }

  &.mission-icon {
    background: linear-gradient(135deg, $color-primary 0%, $color-primary-dark 100%);
  }

  &.vision-icon {
    background: linear-gradient(135deg, $color-success 0%, #059669 100%);
  }
}

.card-content {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
  display: block;
  margin-bottom: 8px;
}

.card-text {
  font-size: 14px;
  color: $color-text-secondary;
  line-height: 1.6;
}

// ===== 核心优势 =====
.features-section {
  padding: 24px 20px;
}

.section-title {
  font-size: 18px;
  font-weight: 700;
  color: $color-text;
  text-align: center;
  display: block;
  margin-bottom: 20px;
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.feature-card {
  padding: 20px 16px;
  background: $color-surface;
  border-radius: 16px;
  border: 1px solid $color-border;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;

  &.featured {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(16, 185, 129, 0.06) 100%);
    border-color: rgba(59, 130, 246, 0.2);
  }

  &:active {
    transform: scale(0.98);
  }
}

.feature-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: $color-primary-bg;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;

  .bi {
    font-size: 22px;
    color: $color-primary;
  }

  &.authentic {
    background: linear-gradient(135deg, $color-success 0%, #059669 100%);
    .bi { color: #FFFFFF; }
  }

  &.buyer {
    background: linear-gradient(135deg, $color-primary 0%, $color-primary-dark 100%);
    .bi { color: #FFFFFF; }
  }
}

.feature-title {
  font-size: 14px;
  font-weight: 600;
  color: $color-text;
  margin-bottom: 6px;
  line-height: 1.3;
}

.feature-desc {
  font-size: 12px;
  color: $color-text-muted;
  line-height: 1.5;
}

// ===== 信任背书 =====
.trust-section {
  padding: 24px 20px;
  margin: 0 20px;
  background: linear-gradient(135deg, #F0F9FF 0%, #F0FDF4 100%);
  border-radius: 20px;
  border: 1px solid rgba(59, 130, 246, 0.1);
}

.trust-badges {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
}

.trust-badge {
  flex: 1;
  max-width: 100px;
  padding: 16px 8px;
  background: $color-surface;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.badge-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: $color-primary-bg;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }

  &.ssl .bi { color: $color-success; }
  &.pci .bi { color: $color-cta; }
  &.ssl { background: rgba(16, 185, 129, 0.1); }
  &.pci { background: rgba(249, 115, 22, 0.1); }
}

.badge-text {
  font-size: 11px;
  color: $color-text-secondary;
  text-align: center;
  font-weight: 500;
}

.certifications {
  display: flex;
  justify-content: center;
  gap: 20px;
  padding-top: 16px;
  border-top: 1px solid rgba(59, 130, 246, 0.1);
}

.cert-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.cert-icon {
  font-size: 16px;
  color: $color-success;
}

.cert-text {
  font-size: 13px;
  font-weight: 600;
  color: $color-text;
}

// ===== 联系我们 =====
.contact-section {
  padding: 32px 20px;
}

.section-subtitle {
  font-size: 14px;
  color: $color-text-secondary;
  text-align: center;
  display: block;
  margin-top: -12px;
  margin-bottom: 20px;
}

.contact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: $color-surface;
  border-radius: 14px;
  border: 1px solid $color-border;
  cursor: pointer;
  transition: background-color 0.2s ease-out, border-color 0.2s ease-out;

  &:active {
    background: $color-background;
    border-color: $color-primary-light;
  }
}

.contact-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: $color-primary-bg;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }

  &.help {
    background: rgba(16, 185, 129, 0.1);
    .bi { color: $color-success; }
  }
}

.contact-info {
  flex: 1;
  min-width: 0;
}

.contact-label {
  font-size: 14px;
  font-weight: 600;
  color: $color-text;
  display: block;
  margin-bottom: 2px;
}

.contact-value {
  font-size: 13px;
  color: $color-text-secondary;
}

.contact-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 16px;
    color: $color-text-muted;
  }
}

// ===== 底部信息 =====
.footer-section {
  padding: 24px 20px;
  text-align: center;
}

.version-text {
  font-size: 13px;
  color: $color-text-muted;
  display: block;
  margin-bottom: 8px;
}

.copyright-text {
  font-size: 12px;
  color: $color-text-muted;
}

// ===== 安全区域 =====
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}

// ===== 无障碍 =====
@media (prefers-reduced-motion: reduce) {
  .feature-card,
  .contact-item {
    transition: none;
  }
}
</style>
