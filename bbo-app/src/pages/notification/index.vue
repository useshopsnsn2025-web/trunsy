<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <NavBar :title="t('notification.title')" />

    <!-- 分类 Tab -->
    <view class="category-tabs">
      <scroll-view scroll-x class="tabs-scroll">
        <view class="tabs-wrapper">
          <view
            v-for="cat in CATEGORY_LIST"
            :key="cat.key"
            class="tab-item"
            :class="{ active: currentCategory === cat.key }"
            @click="handleCategoryChange(cat.key)"
          >
            <text class="tab-label">{{ t(`notification.category.${cat.key}`) }}</text>
            <view v-if="getCategoryUnread(cat.key) > 0" class="tab-badge">
              {{ getCategoryUnread(cat.key) > 99 ? '99+' : getCategoryUnread(cat.key) }}
            </view>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 通知列表 -->
    <view class="notification-content">
      <!-- 操作栏 -->
      <view v-if="notifications.length > 0" class="action-bar">
        <view class="unread-count">
          <text v-if="unreadCount > 0">{{ formatUnreadCountText(unreadCount) }}</text>
          <text v-else>{{ t('notification.allRead') }}</text>
        </view>
        <view v-if="unreadCount > 0" class="mark-all-btn" @click="handleMarkAllRead">
          <text class="bi bi-check2-all"></text>
          <text>{{ t('notification.markAllRead') }}</text>
        </view>
      </view>

      <!-- 切换分类加载中 -->
      <view v-if="switching" class="switching-loading">
        <text class="bi bi-arrow-repeat spinning"></text>
      </view>

      <!-- 通知列表 -->
      <view v-else-if="notifications.length > 0" class="notification-list">
        <view
          v-for="item in notifications"
          :key="item.id"
          class="notification-item"
          :class="{ unread: !item.is_read, expanded: expandedIds.has(item.id) }"
          @click="handleToggleExpand(item)"
        >
          <!-- 类型图标 -->
          <view class="notification-icon" :class="getTypeClass(item.type)">
            <text :class="'bi ' + getTypeIcon(item.type)"></text>
          </view>
          <!-- 内容 -->
          <view class="notification-body">
            <view class="notification-header">
              <text class="notification-title">{{ item.title }}</text>
              <text v-if="!item.is_read" class="unread-dot"></text>
            </view>
            <text class="notification-text" :class="{ 'expanded': expandedIds.has(item.id) }">{{ item.content }}</text>
            <view class="notification-footer">
              <text class="notification-time">{{ formatTime(item.created_at) }}</text>
              <!-- 查看订单按钮 -->
              <view
                v-if="expandedIds.has(item.id) && (item.data?.order_id || item.data?.order_no)"
                class="view-order-btn"
                @click.stop="handleViewOrder(item)"
              >
                <text>{{ t('notification.viewOrder') }}</text>
                <text class="bi bi-chevron-right"></text>
              </view>
            </view>
          </view>
          <!-- 展开/收起图标 -->
          <view class="expand-btn">
            <text :class="'bi ' + (expandedIds.has(item.id) ? 'bi-chevron-up' : 'bi-chevron-down')"></text>
          </view>
          <!-- 删除按钮 -->
          <view class="delete-btn" @click.stop="handleDelete(item.id)">
            <text class="bi bi-trash"></text>
          </view>
        </view>
      </view>

      <!-- 空状态 -->
      <view v-else-if="!loading && !switching" class="empty-state">
        <text class="bi bi-bell-slash empty-icon"></text>
        <text class="empty-text">{{ t('notification.empty') }}</text>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore && notifications.length > 0" class="load-more" @click="loadMore">
        <text v-if="loadingMore">{{ t('common.loading') }}</text>
        <text v-else>{{ t('common.loadMore') }}</text>
      </view>
    </view>

    <!-- 标记全部已读确认弹窗 -->
    <ConfirmDialog
      :visible="showMarkAllReadDialog"
      :title="t('notification.markAllReadTitle')"
      :content="t('notification.markAllReadConfirm')"
      icon="bi-check2-all"
      icon-type="info"
      @update:visible="showMarkAllReadDialog = $event"
      @confirm="confirmMarkAllRead"
    />

    <!-- 删除通知确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      :title="t('notification.deleteTitle')"
      :content="t('notification.deleteConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteDialog = $event"
      @confirm="confirmDelete"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow, onReachBottom } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import {
  getNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  deleteNotification,
  getNotificationStats,
  NOTIFICATION_ICONS,
  CATEGORY_LIST,
  type Notification,
  type NotificationCategory,
  type NotificationStats,
} from '@/api/notification'

const { t } = useI18n()
const toast = useToast()

// 辅助函数：手动替换占位符（UniApp APP 端 vue-i18n 插值不生效）
function formatUnreadCountText(count: number): string {
  const template = t('notification.unreadCount')
  return template.replace('[COUNT]', String(count))
}

function formatMinutesAgoText(count: number): string {
  const template = t('notification.minutesAgo')
  return template.replace('[COUNT]', String(count))
}

const loading = ref(true) // 页面初始加载
const loadingMore = ref(false) // 加载更多
const switching = ref(false) // 切换分类加载
const notifications = ref<Notification[]>([])
const unreadCount = ref(0)
const page = ref(1)
const pageSize = 20
const total = ref(0)
const expandedIds = ref<Set<number>>(new Set())
const currentCategory = ref<NotificationCategory>('all')
const categoryStats = ref<Record<string, number>>({})
const showMarkAllReadDialog = ref(false)
const showDeleteDialog = ref(false)
const deleteTargetId = ref<number | null>(null)

// 获取分类未读数
function getCategoryUnread(category: NotificationCategory): number {
  if (category === 'all') {
    return unreadCount.value
  }
  return categoryStats.value[category] || 0
}

// 切换分类
function handleCategoryChange(category: NotificationCategory) {
  if (currentCategory.value === category) return
  currentCategory.value = category
  switching.value = true
  loadNotifications(true)
}

const hasMore = computed(() => notifications.value.length < total.value)

// 获取类型图标
function getTypeIcon(type: string): string {
  return NOTIFICATION_ICONS[type] || 'bi-bell'
}

// 获取类型样式类
function getTypeClass(type: string): string {
  const classMap: Record<string, string> = {
    order_created: 'type-order',
    payment_success: 'type-payment',
    order_shipped: 'type-shipping',
    order_delivered: 'type-delivered',
    order_cancelled: 'type-cancelled',
    refund_success: 'type-refund',
    preauth_voided: 'type-preauth',
  }
  return classMap[type] || 'type-default'
}

// 格式化时间
function formatTime(timeStr: string): string {
  if (!timeStr) return ''
  const date = new Date(timeStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  // 一分钟内
  if (diff < 60 * 1000) {
    return t('notification.justNow')
  }
  // 一小时内
  if (diff < 60 * 60 * 1000) {
    const minutes = Math.floor(diff / (60 * 1000))
    return formatMinutesAgoText(minutes)
  }
  // 今天内
  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  // 昨天
  const yesterday = new Date(now)
  yesterday.setDate(yesterday.getDate() - 1)
  if (date.toDateString() === yesterday.toDateString()) {
    return t('notification.yesterday') + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  // 更早
  return date.toLocaleDateString()
}

// 加载通知列表
async function loadNotifications(isRefresh = false) {
  if (isRefresh) {
    page.value = 1
    notifications.value = []
  }

  try {
    const params: Record<string, any> = {
      page: page.value,
      pageSize,
    }
    // 如果不是全部，则按分类筛选
    if (currentCategory.value !== 'all') {
      params.category = currentCategory.value
    }
    const res = await getNotifications(params)
    if (res.code === 0) {
      if (isRefresh) {
        notifications.value = res.data.list
      } else {
        notifications.value.push(...res.data.list)
      }
      total.value = res.data.total
    }
  } catch (e) {
    console.error('Failed to load notifications:', e)
  } finally {
    loading.value = false
    loadingMore.value = false
    switching.value = false
  }
}

// 加载未读数量和分类统计
async function loadUnreadCount() {
  try {
    const res = await getNotificationStats()
    if (res.code === 0) {
      unreadCount.value = res.data.unread_count
      categoryStats.value = res.data.category_stats || {}
    }
  } catch (e) {
    console.error('Failed to load notification stats:', e)
  }
}

// 加载更多
async function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  page.value++
  await loadNotifications()
}

// 切换展开/收起
async function handleToggleExpand(item: Notification) {
  // 标记已读
  if (!item.is_read) {
    try {
      await markNotificationRead(item.id)
      item.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (e) {
      console.error('Failed to mark as read:', e)
    }
  }

  // 切换展开状态
  if (expandedIds.value.has(item.id)) {
    expandedIds.value.delete(item.id)
  } else {
    expandedIds.value.add(item.id)
  }
  // 触发响应式更新
  expandedIds.value = new Set(expandedIds.value)
}

// 查看订单
function handleViewOrder(item: Notification) {
  if (item.data?.order_id || item.data?.order_no) {
    const orderParam = item.data.order_id ? `id=${item.data.order_id}` : `orderNo=${item.data.order_no}`
    uni.navigateTo({
      url: `/pages/order/detail?${orderParam}`,
    })
  }
}

// 标记全部已读
function handleMarkAllRead() {
  showMarkAllReadDialog.value = true
}

async function confirmMarkAllRead() {
  try {
    uni.showLoading({ title: '' })
    const result = await markAllNotificationsRead()
    if (result.code === 0) {
      // 更新本地状态
      notifications.value.forEach((item) => {
        item.is_read = true
      })
      unreadCount.value = 0
      toast.success(t('common.success'))
    }
  } catch (e) {
    console.error('Failed to mark all as read:', e)
    toast.error(t('common.error'))
  } finally {
    uni.hideLoading()
  }
}

// 删除通知
function handleDelete(id: number) {
  deleteTargetId.value = id
  showDeleteDialog.value = true
}

async function confirmDelete() {
  const id = deleteTargetId.value
  if (!id) return

  try {
    uni.showLoading({ title: '' })
    const result = await deleteNotification(id)
    if (result.code === 0) {
      // 从列表中移除
      const index = notifications.value.findIndex((item) => item.id === id)
      if (index !== -1) {
        const item = notifications.value[index]
        if (!item.is_read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
        total.value--
      }
      toast.success(t('common.success'))
    }
  } catch (e) {
    console.error('Failed to delete notification:', e)
    toast.error(t('common.error'))
  } finally {
    uni.hideLoading()
  }
}

onShow(() => {
  uni.setNavigationBarTitle({ title: t('notification.title') })
  loading.value = true
  loadNotifications(true)
  loadUnreadCount()
})

onReachBottom(() => {
  if (hasMore.value && !loadingMore.value) {
    loadMore()
  }
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
}

/* 分类 Tab */
.category-tabs {
  background: #fff;
  padding: 0 24rpx;
  border-bottom: 1rpx solid #eee;
}

.tabs-scroll {
  white-space: nowrap;
}

.tabs-wrapper {
  display: inline-flex;
  padding: 20rpx 0;
  gap: 32rpx;
}

.tab-item {
  display: inline-flex;
  align-items: center;
  padding: 12rpx 24rpx;
  border-radius: 32rpx;
  background: #f5f5f5;
  font-size: 26rpx;
  color: #666;
  transition: all 0.2s;
  position: relative;

  &.active {
    background: linear-gradient(135deg, #FF6B35 0%, #ff8f5a 100%);
    color: #fff;
  }

  &:active {
    opacity: 0.8;
  }
}

.tab-label {
  white-space: nowrap;
}

.tab-badge {
  position: absolute;
  top: -8rpx;
  right: -8rpx;
  min-width: 32rpx;
  height: 32rpx;
  line-height: 32rpx;
  padding: 0 8rpx;
  font-size: 20rpx;
  color: #fff;
  background: #ff4d4f;
  border-radius: 16rpx;
  text-align: center;
}

.notification-content {
  padding: 24rpx;
}

/* 切换分类加载中 */
.switching-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 80rpx 0;

  .bi {
    font-size: 48rpx;
    color: #FF6B35;
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

/* 操作栏 */
.action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 24rpx;
  background: #fff;
  border-radius: 12rpx;
  margin-bottom: 24rpx;
}

.unread-count {
  font-size: 26rpx;
  color: #666;
}

.mark-all-btn {
  display: flex;
  align-items: center;
  font-size: 26rpx;
  color: #FF6B35;

  .bi {
    margin-right: 8rpx;
  }

  &:active {
    opacity: 0.7;
  }
}

/* 通知列表 */
.notification-list {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}

.notification-item {
  display: flex;
  padding: 28rpx 24rpx;
  border-bottom: 1rpx solid #f5f5f5;
  position: relative;

  &:last-child {
    border-bottom: none;
  }

  &.unread {
    background: #fff8f5;
  }

  &:active {
    background: #f9f9f9;
  }
}

/* 类型图标 */
.notification-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
  flex-shrink: 0;

  .bi {
    font-size: 36rpx;
    color: #fff;
  }

  &.type-order {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  &.type-payment {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  }

  &.type-shipping {
    background: linear-gradient(135deg, #FF6B35 0%, #ff8f5a 100%);
  }

  &.type-delivered {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
  }

  &.type-cancelled {
    background: linear-gradient(135deg, #8c8c8c 0%, #bfbfbf 100%);
  }

  &.type-refund {
    background: linear-gradient(135deg, #faad14 0%, #ffc53d 100%);
  }

  &.type-preauth {
    background: linear-gradient(135deg, #1890ff 0%, #69c0ff 100%);
  }

  &.type-default {
    background: linear-gradient(135deg, #999 0%, #ccc 100%);
  }
}

/* 通知内容 */
.notification-body {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: center;
  margin-bottom: 8rpx;
}

.notification-title {
  font-size: 30rpx;
  font-weight: 500;
  color: #333;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.unread-dot {
  width: 16rpx;
  height: 16rpx;
  border-radius: 50%;
  background: #FF6B35;
  margin-left: 12rpx;
  flex-shrink: 0;
}

.notification-text {
  font-size: 26rpx;
  color: #666;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 8rpx;
  word-break: break-all;

  &.expanded {
    -webkit-line-clamp: unset;
    display: block;
  }
}

.notification-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12rpx;
}

.notification-time {
  font-size: 24rpx;
  color: #999;
}

.view-order-btn {
  display: flex;
  align-items: center;
  font-size: 24rpx;
  color: #FF6B35;
  padding: 8rpx 16rpx;
  background: #fff5f0;
  border-radius: 24rpx;

  .bi {
    margin-left: 4rpx;
    font-size: 20rpx;
  }

  &:active {
    opacity: 0.7;
  }
}

/* 展开/收起按钮 */
.expand-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48rpx;
  height: 48rpx;
  margin-left: 8rpx;
  flex-shrink: 0;

  .bi {
    font-size: 28rpx;
    color: #999;
  }
}

/* 删除按钮 */
.delete-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60rpx;
  height: 60rpx;
  margin-left: 16rpx;
  flex-shrink: 0;

  .bi {
    font-size: 32rpx;
    color: #999;
  }

  &:active {
    .bi {
      color: #ff4d4f;
    }
  }
}

/* 空状态 */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 120rpx 0;
}

.empty-icon {
  font-size: 120rpx;
  color: #ddd;
  margin-bottom: 24rpx;
}

.empty-text {
  font-size: 28rpx;
  color: #999;
}

/* 加载更多 */
.load-more {
  display: flex;
  justify-content: center;
  padding: 32rpx;
  font-size: 26rpx;
  color: #999;

  &:active {
    color: #FF6B35;
  }
}
</style>
