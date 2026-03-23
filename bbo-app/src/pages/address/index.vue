<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="t('address.title')">
      <template #right>
        <view class="nav-action" @click="goAdd">
          <text class="bi bi-plus-lg"></text>
        </view>
      </template>
    </NavBar>

    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && addresses.length === 0" class="empty-state">
      <text class="bi bi-geo-alt empty-icon"></text>
      <text class="empty-text">{{ t('address.noAddress') }}</text>
      <button class="add-btn" @click="goAdd">{{ t('address.addAddress') }}</button>
    </view>

    <!-- 地址列表 -->
    <view v-if="!loading && addresses.length > 0" class="address-list">
      <view
        v-for="addr in addresses"
        :key="addr.id"
        class="address-item"
        :class="{ 'is-default': addr.isDefault }"
        @click="selectAddress(addr)"
      >
        <!-- 默认地址标签 -->
        <view v-if="addr.isDefault" class="default-tag">
          <text>{{ t('address.defaultAddress') }}</text>
        </view>

        <!-- 地址信息 -->
        <view class="address-info">
          <view class="name-phone">
            <text class="name">{{ addr.name }}</text>
            <text class="phone">{{ addr.phone }}</text>
          </view>
          <view class="address-detail">
            <text>{{ formatAddress(addr) }}</text>
          </view>
        </view>

        <!-- 右侧箭头 -->
        <view class="address-arrow" @click.stop="goEdit(addr)">
          <text class="bi bi-chevron-right"></text>
        </view>
      </view>
    </view>

    <!-- 底部添加按钮 -->
    <view v-if="addresses.length > 0" class="bottom-bar">
      <button class="add-btn-full" @click="goAdd">
        <text class="bi bi-plus"></text>
        <text>{{ t('address.addNewAddress') }}</text>
      </button>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getAddresses, type Address } from '@/api/user'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()

const loading = ref(false)
const addresses = ref<Address[]>([])

// 是否为选择模式（从其他页面跳转过来选择地址）
const selectMode = ref(false)

onMounted(() => {
  // 检查是否为选择模式
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1]
  selectMode.value = (currentPage as any).options?.select === 'true'
})

onShow(() => {
  loadAddresses()
})

async function loadAddresses() {
  loading.value = true
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      addresses.value = res.data || []
    }
  } catch (e) {
    console.error('Failed to load addresses:', e)
  } finally {
    loading.value = false
  }
}

function formatAddress(addr: Address): string {
  const parts = [addr.street]
  if (addr.city) parts.push(addr.city)
  if (addr.state) parts.push(addr.state)
  if (addr.postalCode) parts.push(addr.postalCode)
  if (addr.country) parts.push(addr.country)
  return parts.join(', ')
}

function goBack() {
  uni.navigateBack()
}

function goAdd() {
  uni.navigateTo({ url: '/pages/address/edit' })
}

function goEdit(addr: Address) {
  uni.navigateTo({ url: `/pages/address/edit?id=${addr.id}` })
}

function selectAddress(addr: Address) {
  if (selectMode.value) {
    // 选择模式，返回选中的地址
    uni.$emit('addressSelected', addr)
    uni.navigateBack()
  } else {
    // 普通模式，进入编辑
    goEdit(addr)
  }
}
</script>

<style lang="scss" scoped>
$primary-color: #FF6B35;
$text-color: #000;
$text-secondary: #333;
$text-muted: #8E8E93;
$border-color: #C6C6C8;
$bg-color: #F2F2F7;

.page {
  // min-height: 100vh;
  // background-color: $bg-color;
  padding-bottom: 80px;
}

// 导航栏
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 20px 16px;
  // background-color: $bg-color;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left-group {
  display: flex;
  align-items: center;
  gap: 4px;

  .bi {
    font-size: 28px;
    color: $text-color;
  }
}

.nav-right {
  display: flex;
  align-items: center;

  .bi {
    font-size: 28px;
    color: $primary-color;
  }
}

.nav-title {
  font-size: 17px;
  font-weight: 600;
  color: $text-color;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 80px 24px;
}

.empty-icon {
  font-size: 64px;
  color: #C7C7CC;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 17px;
  color: $text-muted;
  margin-bottom: 24px;
}

.add-btn {
  background-color: $primary-color;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 12px 32px;
  font-size: 17px;
  font-weight: 500;
}

// 地址列表
.address-list {
  margin: 16px;
  background-color: #fff;
  border-radius: 10px;
  overflow: hidden;
}

.address-item {
  padding: 16px;
  display: flex;
  align-items: center;
  position: relative;
  border-bottom: 1px solid #E5E5EA;

  // 最后一项不需要底部边框
  &:last-child {
    border-bottom: none;
  }

  // 默认地址卡片需要额外上内边距
  &.is-default {
    padding-top: 28px;
  }
}

.default-tag {
  position: absolute;
  top: 0;
  left: 0;
  background-color: $primary-color;
  color: #fff;
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 0;
  font-weight: 500;
  z-index: 1;
}

.address-info {
  flex: 1;
  min-width: 0;
}

.name-phone {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.name {
  font-size: 17px;
  font-weight: 600;
  color: $text-color;
}

.phone {
  font-size: 15px;
  color: $text-muted;
}

.address-detail {
  font-size: 15px;
  color: #666;
  line-height: 1.5;
}

.address-arrow {
  padding: 8px;
  margin-right: -8px;

  .bi {
    font-size: 18px;
    color: #C7C7CC;
  }
}

// 底部添加按钮
.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  background-color: $bg-color;
}

.add-btn-full {
  width: 100%;
  height: 50px;
  background-color: $primary-color;
  color: #fff;
  border: none;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 500;

  .bi {
    font-size: 20px;
  }
}
</style>
