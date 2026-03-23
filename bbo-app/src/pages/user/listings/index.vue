<template>
  <view class="page">
    <!-- 加载页 -->
    <LoadingPage v-model="loading" />

    <!-- 自定义导航栏 -->
    <NavBar :title="t('myListings.title')">
      <template #right>
        <view class="nav-action" @click="goPublish">
          <text class="bi bi-plus-lg"></text>
        </view>
      </template>
    </NavBar>

    <!-- 状态 Tab（吸顶） -->
    <view class="status-tabs" :style="{ top: navBarHeight + 'px' }">
      <scroll-view scroll-x class="tabs-scroll">
        <view class="tabs-wrapper">
          <view
            v-for="tab in tabs"
            :key="tab.value"
            class="tab-item"
            :class="{ active: currentTab === tab.value }"
            @click="switchTab(tab.value)"
          >
            <text class="tab-label">{{ tab.label }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- Tab 占位 -->
    <view class="tabs-placeholder"></view>

    <!-- 切换分类加载中 -->
    <view v-if="switching" class="switching-loading">
      <text class="bi bi-arrow-repeat spinning"></text>
    </view>

    <!-- 商品列表 -->
    <scroll-view
      v-else
      class="content"
      scroll-y
      :style="{ height: contentHeight }"
      @scrolltolower="loadMore"
    >
      <!-- 草稿 Tab -->
      <template v-if="currentTab === 'draft'">
        <!-- 草稿空状态 -->
        <view v-if="!draft" class="empty-state">
          <text class="bi bi-pencil-square empty-icon"></text>
          <text class="empty-text">{{ t('myListings.noDraft') }}</text>
          <view class="empty-action" @click="goPublish">
            <text>{{ t('myListings.startSelling') }}</text>
          </view>
        </view>

        <!-- 草稿卡片 -->
        <view v-else class="goods-list">
          <view class="goods-card" @click="editDraft">
            <view class="goods-image-wrap">
              <image
                v-if="draft.images && draft.images.length > 0"
                class="goods-image"
                :src="draft.images[0]"
                mode="aspectFit"
              />
              <view v-else class="goods-image placeholder">
                <text class="bi bi-image"></text>
              </view>
              <view class="status-badge draft">
                <text>{{ t('myListings.status.draft') }}</text>
              </view>
            </view>
            <view class="goods-info">
              <text class="goods-title">{{ draft.title || t('myListings.untitled') }}</text>
              <view class="goods-meta">
                <text v-if="draft.price" class="goods-price">{{ formatPrice(draft.price, 'SGD') }}</text>
                <text v-else class="goods-price-empty">{{ t('myListings.noPrice') }}</text>
                <text class="goods-step">{{ t('myListings.step') }} {{ draft.current_step }}/4</text>
              </view>
              <view class="goods-actions">
                <view class="action-btn" @click.stop="editDraft">
                  <text>{{ t('myListings.continueDraft') }}</text>
                </view>
                <view class="action-btn danger" @click.stop="removeDraft">
                  <text>{{ t('common.delete') }}</text>
                </view>
              </view>
            </view>
          </view>
        </view>
      </template>

      <!-- 其他 Tab -->
      <template v-else>
        <!-- 空状态 -->
        <view v-if="!loading && list.length === 0" class="empty-state">
          <text class="bi bi-box-seam empty-icon"></text>
          <text class="empty-text">{{ t('myListings.empty') }}</text>
          <view class="empty-action" @click="goPublish">
            <text>{{ t('myListings.startSelling') }}</text>
          </view>
        </view>

        <!-- 商品卡片 -->
        <view v-else class="goods-list">
          <view
            v-for="item in list"
            :key="item.id"
            class="goods-card"
            @click="goDetail(item.id)"
          >
            <view class="goods-image-wrap">
              <image
                class="goods-image"
                :src="item.images[0]"
                mode="aspectFit"
              />
              <view v-if="item.statusText !== 'active'" class="status-badge" :class="item.statusText">
                <text>{{ t('myListings.status.' + item.statusText) }}</text>
              </view>
            </view>
            <view class="goods-info">
              <text class="goods-title">{{ item.title }}</text>
              <view class="goods-meta">
                <text class="goods-price">{{ formatPrice(item.price, item.currency) }}</text>
                <text class="goods-views">{{ item.views }} {{ t('myListings.views') }}</text>
              </view>
              <view class="goods-actions">
                <view class="action-btn" @click.stop="editGoods(item)">
                  <text>{{ t('common.edit') }}</text>
                </view>
                <view v-if="item.statusText === 'active'" class="action-btn" @click.stop="offShelf(item)">
                  <text>{{ t('myListings.offShelf') }}</text>
                </view>
                <view v-if="item.statusText === 'off'" class="action-btn" @click.stop="onShelf(item)">
                  <text>{{ t('myListings.onShelf') }}</text>
                </view>
                <view class="action-btn danger" @click.stop="deleteGoods(item)">
                  <text>{{ t('common.delete') }}</text>
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="list.length > 0" class="load-more">
          <text v-if="isLoadingMore">{{ t('common.loading') }}</text>
          <text v-else-if="!hasMore">{{ t('common.noMore') }}</text>
        </view>
      </template>

      <!-- 底部安全区 -->
      <view class="safe-bottom"></view>
    </scroll-view>

    <!-- 下架商品弹窗 -->
    <ConfirmDialog
      :visible="showOffShelfDialog"
      :title="t('myListings.offShelfConfirm')"
      :content="t('myListings.offShelfDesc')"
      icon="bi-arrow-down-circle"
      icon-type="warning"
      @update:visible="showOffShelfDialog = $event"
      @confirm="handleOffShelf"
    />

    <!-- 上架商品弹窗 -->
    <ConfirmDialog
      :visible="showOnShelfDialog"
      :title="t('myListings.onShelfConfirm')"
      :content="t('myListings.onShelfDesc')"
      icon="bi-arrow-up-circle"
      icon-type="warning"
      @update:visible="showOnShelfDialog = $event"
      @confirm="handleOnShelf"
    />

    <!-- 删除商品弹窗 -->
    <ConfirmDialog
      :visible="showDeleteGoodsDialog"
      :title="t('myListings.deleteConfirm')"
      :content="t('myListings.deleteDesc')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteGoodsDialog = $event"
      @confirm="handleDeleteGoods"
    />

    <!-- 删除草稿弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDraftDialog"
      :title="t('myListings.deleteConfirm')"
      :content="t('myListings.deleteDraftDesc')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteDraftDialog = $event"
      @confirm="handleDeleteDraft"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import { getMyGoods, getMyGoodsStats, deleteGoods as deleteGoodsApi, getDraft, deleteDraft, offShelfGoods, onShelfGoods, type Goods, type MyGoodsStats, type GoodsDraft } from '@/api/goods'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 导航栏高度（状态栏 + 48px）
const statusBarHeight = ref(0) // H5 端为 0，APP 端由 getSystemInfo 动态获取
const NAV_BAR_HEIGHT = 48
const navBarHeight = computed(() => statusBarHeight.value + NAV_BAR_HEIGHT)

// 内容区高度（动态计算）
// Tab 栏高度 92rpx，根据设备屏幕宽度动态转换
const windowWidth = ref(375)
const tabBarHeight = computed(() => Math.ceil(92 * windowWidth.value / 750))
const contentHeight = computed(() => `calc(100vh - ${navBarHeight.value + tabBarHeight.value}px)`)

// 接收页面参数
const initialTab = ref<string>('all')

// 状态
const loading = ref(true)
const switching = ref(false) // 切换分类加载
const list = ref<(Goods & { statusText?: string })[]>([])
const draft = ref<GoodsDraft | null>(null) // 草稿
const stats = ref<MyGoodsStats>({
  total: 0,
  active: 0,
  pending: 0,
  sold: 0,
  off: 0,
  draft: 0,
})
const currentTab = ref<string>('all')
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const isLoadingMore = ref(false)

// 弹窗状态
const showOffShelfDialog = ref(false)
const showOnShelfDialog = ref(false)
const showDeleteGoodsDialog = ref(false)
const showDeleteDraftDialog = ref(false)
const targetItem = ref<Goods | null>(null)

// Tab 列表
const tabs = computed(() => [
  { label: t('myListings.tabs.all'), value: 'all' },
  { label: t('myListings.tabs.active'), value: 'active' },
  { label: t('myListings.tabs.sold'), value: 'sold' },
  { label: t('myListings.tabs.draft'), value: 'draft' },
])

// 格式化价格
function formatPrice(price: number, currency: string) {
  return appStore.formatPrice(price, currency)
}

// 切换 Tab
function switchTab(tab: string) {
  if (currentTab.value === tab) return
  currentTab.value = tab
  page.value = 1
  list.value = []
  hasMore.value = true
  switching.value = true
  loadList()
}

// 加载统计数据
async function loadStats() {
  try {
    const res = await getMyGoodsStats()
    if (res.code === 0 && res.data) {
      stats.value = res.data
    }
  } catch (e) {
    console.error('Load stats failed:', e)
  }
}

// 加载商品列表
async function loadList() {
  try {
    // 草稿 Tab 特殊处理
    if (currentTab.value === 'draft') {
      const res = await getDraft()
      if (res.code === 0) {
        draft.value = res.data
      }
      hasMore.value = false
      return
    }

    // 其他 Tab 加载商品列表
    const res = await getMyGoods({
      page: page.value,
      pageSize,
      status: currentTab.value as any,
    })
    if (res.code === 0 && res.data) {
      if (page.value === 1) {
        list.value = res.data.list
      } else {
        list.value = [...list.value, ...res.data.list]
      }
      hasMore.value = res.data.list.length >= pageSize
    }
  } catch (e) {
    console.error('Load list failed:', e)
  } finally {
    loading.value = false
    switching.value = false
    isLoadingMore.value = false
  }
}

// 加载更多
function loadMore() {
  if (!hasMore.value || isLoadingMore.value) return
  isLoadingMore.value = true
  page.value++
  loadList()
}

// 跳转详情
function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/goods/detail?id=${id}` })
}

// 编辑商品
function editGoods(item: Goods) {
  // 如果商品是上架状态，需要先下架才能编辑
  if (item.statusText === 'active') {
    toast.warning(t('myListings.editRequireOffShelf'))
    return
  }
  uni.navigateTo({ url: `/pages/publish/index?id=${item.id}&mode=edit` })
}

// 下架商品
function offShelf(item: Goods) {
  targetItem.value = item
  showOffShelfDialog.value = true
}

// 下架商品回调
async function handleOffShelf() {
  const item = targetItem.value
  if (!item) return
  try {
    const result = await offShelfGoods(item.id)
    if (result.code === 0) {
      toast.success(t('myListings.offShelfSuccess'))
      // 更新列表中的状态
      item.statusText = 'off'
      // 刷新统计
      loadStats()
    }
  } catch (e) {
    toast.error(t('common.failed'))
  }
}

// 上架商品
function onShelf(item: Goods) {
  targetItem.value = item
  showOnShelfDialog.value = true
}

// 上架商品回调
async function handleOnShelf() {
  const item = targetItem.value
  if (!item) return
  try {
    const result = await onShelfGoods(item.id)
    if (result.code === 0) {
      toast.success(t('myListings.onShelfSuccess'))
      // 更新列表中的状态
      item.statusText = 'active'
      // 刷新统计
      loadStats()
    }
  } catch (e) {
    toast.error(t('common.failed'))
  }
}

// 删除商品
function deleteGoods(item: Goods) {
  targetItem.value = item
  showDeleteGoodsDialog.value = true
}

// 删除商品回调
async function handleDeleteGoods() {
  const item = targetItem.value
  if (!item) return
  try {
    const result = await deleteGoodsApi(item.id)
    if (result.code === 0) {
      toast.success(t('common.success'))
      // 从列表中移除
      list.value = list.value.filter(g => g.id !== item.id)
      // 刷新统计
      loadStats()
    }
  } catch (e) {
    toast.error(t('common.failed'))
  }
}

// 发布商品
function goPublish() {
  uni.navigateTo({ url: '/pages/publish/index' })
}

// 编辑草稿
function editDraft() {
  uni.navigateTo({ url: '/pages/publish/index?from=draft' })
}

// 删除草稿
function removeDraft() {
  showDeleteDraftDialog.value = true
}

// 删除草稿回调
async function handleDeleteDraft() {
  try {
    const result = await deleteDraft()
    if (result.code === 0) {
      toast.success(t('common.success'))
      draft.value = null
      loadStats()
    }
  } catch (e) {
    toast.error(t('common.failed'))
  }
}

// 页面加载 - 处理 URL 参数
onLoad((options: any) => {
  if (options?.tab && ['all', 'active', 'sold', 'draft'].includes(options.tab)) {
    initialTab.value = options.tab
    currentTab.value = options.tab
  }
})

// 初始化
onMounted(async () => {
  // 获取系统信息
  uni.getSystemInfo({
    success: (res) => {
      // H5 端没有状态栏，statusBarHeight 为 0 是正确的
      statusBarHeight.value = res.statusBarHeight || 0
      windowWidth.value = res.windowWidth || 375
    },
  })
  await Promise.all([loadStats(), loadList()])
})

// 页面显示时刷新
onShow(() => {
  if (!loading.value) {
    loadStats()
    page.value = 1
    loadList()
  }
})
</script>

<style lang="scss" scoped>
// 颜色变量
$color-primary: #FF6B35;
$color-text: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-success: #228B22;
$color-warning: #F5A623;
$color-danger: #FF6B35;

.page {
  min-height: 100vh;
  background-color: $color-background;
}

// 状态 Tab（吸顶）
.status-tabs {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 99;
  background: $color-surface;
  padding: 0 24rpx;
  border-bottom: 1rpx solid $color-border;
}

// Tab 占位（与 status-tabs 实际高度一致：padding 40rpx + tab高度 约50rpx + border 1rpx）
.tabs-placeholder {
  height: 92rpx;
}

.tabs-scroll {
  white-space: nowrap;

  ::-webkit-scrollbar {
    display: none;
  }
}

.tabs-wrapper {
  display: inline-flex;
  padding: 20rpx 0;
  gap: 24rpx;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  padding: 12rpx 24rpx;
  border-radius: 32rpx;
  background: $color-background;
  font-size: 26rpx;
  color: $color-text-secondary;
  transition: all 0.2s;
  position: relative;

  &.active {
    background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
    color: #fff;
  }

  &:active {
    opacity: 0.8;
  }
}

.tab-label {
  white-space: nowrap;
}

// 切换分类加载中
.switching-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 80rpx 0;

  .bi {
    font-size: 48rpx;
    color: $color-primary;
  }

  .spinning {
    animation: spin 1s linear infinite;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

// 内容区
.content {
  // 高度通过 :style 动态设置

  ::-webkit-scrollbar {
    display: none;
  }
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 32px;
}

.empty-icon {
  font-size: 64px;
  color: $color-border;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 15px;
  color: $color-text-muted;
  margin-bottom: 24px;
}

.empty-action {
  padding: 12px 32px;
  background-color: $color-primary;
  border-radius: 24px;
  cursor: pointer;

  text {
    font-size: 15px;
    font-weight: 600;
    color: #fff;
  }
}

// 商品列表
.goods-list {
  padding: 12px;
}

.goods-card {
  display: flex;
  gap: 12px;
  padding: 12px;
  background-color: $color-surface;
  border-radius: 12px;
  margin-bottom: 12px;
  cursor: pointer;
}

.goods-image-wrap {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  box-sizing: border-box;
}

.goods-image {
  width: calc(100% - 12px);
  height: calc(100% - 12px);
  object-fit: contain;
  background-color: #fff;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);

  &.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: $color-background;

    .bi {
      font-size: 32px;
      color: $color-text-muted;
    }
  }
}

.status-badge {
  position: absolute;
  top: 6px;
  left: 6px;
  padding: 2px 8px;
  border-radius: 4px;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;

  text {
    font-size: 11px;
    color: #fff;
  }

  &.pending {
    background-color: $color-warning;
  }

  &.sold {
    background-color: $color-success;
  }

  &.off {
    background-color: $color-text-muted;
  }

  &.draft {
    background-color: $color-primary;
  }
}

.goods-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.goods-title {
  font-size: 14px;
  font-weight: 500;
  color: $color-text;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 8px;
}

.goods-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.goods-price {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
}

.goods-price-empty {
  font-size: 14px;
  color: $color-text-muted;
}

.goods-views {
  font-size: 12px;
  color: $color-text-muted;
}

.goods-step {
  font-size: 12px;
  color: $color-text-muted;
}

.goods-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 4px;
  background-color: $color-background;
  border-radius: 6px;
  cursor: pointer;

  text {
    font-size: 12px;
    color: $color-text-secondary;
    white-space: nowrap;
  }

  &.danger {
    text {
      color: $color-danger;
    }
  }

  &:active {
    opacity: 0.7;
  }
}

// 加载更多
.load-more {
  padding: 20px;
  text-align: center;

  text {
    font-size: 13px;
    color: $color-text-muted;
  }
}

// 底部安全区
.safe-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 20px);
}
</style>
