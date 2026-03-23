<template>
  <view class="page">
    <NavBar :title="t('charity.title')" />

    <!-- 搜索框 -->
    <view class="search-box">
      <view class="search-input">
        <text class="bi bi-search search-icon"></text>
        <input
          type="text"
          v-model="searchKeyword"
          :placeholder="t('charity.searchPlaceholder')"
          @confirm="handleSearch"
        />
      </view>
    </view>

    <!-- 内容区域 -->
    <scroll-view class="content" scroll-y>
      <!-- 介绍区块 -->
      <view class="intro-section">
        <text class="intro-title">{{ t('charity.whatIsTitle') }}</text>
        <text class="intro-desc">{{ t('charity.whatIsDesc') }}</text>
      </view>

      <!-- 功能列表 -->
      <view class="feature-list">
        <!-- 结账捐款 -->
        <view class="feature-item">
          <view class="feature-icon ribbon-icon">
            <text class="bi bi-heart"></text>
          </view>
          <view class="feature-text">
            <text class="feature-title">{{ t('charity.checkoutTitle') }}</text>
            <text class="feature-desc">{{ t('charity.checkoutDesc') }}</text>
          </view>
        </view>

        <!-- 成交价捐款 -->
        <view class="feature-item">
          <view class="feature-icon percent-icon">
            <text class="percent-text">%</text>
          </view>
          <view class="feature-text">
            <text class="feature-title">{{ t('charity.percentTitle') }}</text>
            <text class="feature-desc">{{ t('charity.percentDesc') }}</text>
          </view>
        </view>

        <!-- 购买捐助 -->
        <view class="feature-item">
          <view class="feature-icon cart-icon">
            <text class="bi bi-cart3"></text>
          </view>
          <view class="feature-text">
            <text class="feature-title">{{ t('charity.purchaseTitle') }}</text>
            <text class="feature-desc">{{ t('charity.purchaseDesc') }}</text>
          </view>
        </view>
      </view>

      <!-- 品牌标识 -->
      <view class="brand-section">
        <view class="brand-logo">
          <text class="brand-name">TURNSY</text>
          <text class="brand-sub">FOR CHARITY</text>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()

const searchKeyword = ref('')

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('charity.title') })
})

// 搜索慈善机构
function handleSearch() {
  if (!searchKeyword.value.trim()) return
  // TODO: 实现搜索功能
  console.log('Search:', searchKeyword.value)
}
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

.search-box {
  padding: 16rpx 32rpx;
  background-color: #fff;
  box-sizing: border-box;
}

.search-input {
  display: flex;
  align-items: center;
  height: 80rpx;
  background-color: #f5f5f5;
  border-radius: 8rpx;
  padding: 0 24rpx;
  box-sizing: border-box;

  .search-icon {
    font-size: 32rpx;
    color: #999;
    margin-right: 16rpx;
    flex-shrink: 0;
  }

  input {
    flex: 1;
    font-size: 28rpx;
    color: #333;
    min-width: 0;
  }
}

.content {
  flex: 1;
  padding: 32rpx;
  box-sizing: border-box;
  overflow-x: hidden;
}

.intro-section {
  margin-bottom: 48rpx;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.intro-title {
  display: block;
  font-size: 36rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 24rpx;
  word-wrap: break-word;
}

.intro-desc {
  display: block;
  font-size: 28rpx;
  color: #666;
  line-height: 1.6;
  word-wrap: break-word;
}

.feature-list {
  margin-bottom: 64rpx;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  padding: 32rpx 0;
}

.feature-icon {
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 32rpx;
  flex-shrink: 0;

  .bi {
    font-size: 64rpx;
    color: #999;
  }
}

.ribbon-icon {
  .bi {
    font-size: 72rpx;
  }
}

.percent-icon {
  .percent-text {
    font-size: 72rpx;
    font-weight: 300;
    color: #999;
  }
}

.cart-icon {
  .bi {
    font-size: 72rpx;
  }
}

.feature-text {
  flex: 1;
  padding-top: 16rpx;
  min-width: 0;
  overflow: hidden;
}

.feature-title {
  display: block;
  font-size: 28rpx;
  font-weight: 500;
  color: #333;
  margin-bottom: 8rpx;
  word-wrap: break-word;
}

.feature-desc {
  display: block;
  font-size: 26rpx;
  color: #999;
  word-wrap: break-word;
}

.brand-section {
  display: flex;
  justify-content: flex-end;
  padding: 48rpx 0;
}

.brand-logo {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.brand-name {
  font-size: 48rpx;
  font-weight: 700;
  color: #333;
  letter-spacing: 2rpx;
}

.brand-sub {
  display: flex;
  align-items: center;
  font-size: 20rpx;
  color: #666;
  letter-spacing: 1rpx;

  &::after {
    content: '';
    display: inline-block;
    width: 24rpx;
    height: 24rpx;
    margin-left: 8rpx;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E");
    background-size: contain;
  }
}
</style>
