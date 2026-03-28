<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('tabbar.message')" :show-back="false" />

    <view v-if="!isLoggedIn" class="login-prompt">
      <text class="bi bi-chat-dots prompt-icon"></text>
      <text class="prompt-text">{{ t('message.loginRequired') }}</text>
      <button class="login-btn" @click="goLogin">{{ t('auth.login') }}</button>
    </view>

    <view v-else class="message-container">
      <!-- 加载状态 -->
      <view v-if="loading" class="loading-state">
        <text>{{ t('common.loading') }}...</text>
      </view>

      <!-- 空状态 -->
      <view v-else-if="conversations.length === 0" class="empty-state">
        <text class="bi bi-chat-dots empty-icon"></text>
        <text class="empty-text">{{ t('message.noMessages') }}</text>
      </view>

      <!-- 会话列表 -->
      <scroll-view
        v-else
        class="message-list"
        scroll-y
        :scroll-with-animation="true"
        :enhanced="true"
        :show-scrollbar="false"
      >
        <view
          v-for="conv in conversations"
          :key="conv.id"
          class="swipe-container"
        >
          <!-- 滑动操作按钮（右侧隐藏） -->
          <view class="swipe-actions">
            <view
              class="action-btn action-pin"
              @click.stop="handlePin(conv)"
            >
              <text class="bi" :class="conv.isPinned ? 'bi-pin-fill' : 'bi-pin'"></text>
              <text>{{ conv.isPinned ? t('message.unpin') : t('message.pin') }}</text>
            </view>
            <view
              class="action-btn action-delete"
              @click.stop="handleDelete(conv)"
            >
              <text class="bi bi-trash"></text>
              <text>{{ t('message.deleteConversation') }}</text>
            </view>
          </view>

          <!-- 会话内容（可滑动） -->
          <view
            class="conversation-item"
            :class="{ 'is-pinned': conv.isPinned }"
            :style="{ transform: `translateX(${getSwipeOffset(conv.id)}px)` }"
            @touchstart.passive="onTouchStart($event, conv.id)"
            @touchmove.passive="onTouchMove($event, conv.id)"
            @touchend="onTouchEnd($event, conv.id)"
            @click="goChat(conv)"
          >
            <!-- 置顶标记 -->
            <view v-if="conv.isPinned" class="pin-indicator">
              <text class="bi bi-pin-fill"></text>
            </view>
            <view class="avatar-wrapper">
              <image
                v-if="conv.targetAvatar"
                class="avatar"
                :src="conv.targetAvatar"
                mode="aspectFill"
                @error="onAvatarError($event, conv)"
              />
              <view v-else class="avatar avatar-initial" style="color: #ffffff; font-size: 20px; font-weight: 600;">
                <text>{{ conv.targetName.trim().charAt(0).toUpperCase() }}</text>
              </view>
            </view>
            <view class="conv-content">
              <view class="conv-header">
                <text class="conv-name">{{ conv.targetName }}</text>
                <text class="conv-time">{{ formatTime(conv.lastMessageTime) }}</text>
              </view>
              <view class="conv-preview">
                <text class="preview-text">{{ getPreviewText(conv) }}</text>
                <view v-if="conv.unreadCount > 0" class="unread-badge">
                  {{ conv.unreadCount > 99 ? '99+' : conv.unreadCount }}
                </view>
              </view>
              <!-- 商品信息 -->
              <view v-if="conv.goodsInfo" class="goods-tag">
                <text class="goods-tag-text">{{ conv.goodsInfo.title }}</text>
              </view>
            </view>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 自定义底部导航栏 -->
    <CustomTabBar :current="3" />

    <!-- 删除会话确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      :title="t('message.deleteConversation')"
      :content="t('message.deleteConversationConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteDialog = $event"
      @confirm="confirmDelete"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import {
  getConversations,
  pinConversation,
  deleteConversation,
  type Conversation,
} from '@/api/chat'
import CustomTabBar from '@/components/CustomTabBar.vue'
import NavBar from '@/components/NavBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { navigateToLogin } from '@/utils/request'

const { t } = useI18n()
const toast = useToast()
const userStore = useUserStore()
const appStore = useAppStore()

const isLoggedIn = computed(() => userStore.isLoggedIn)
const conversations = ref<Conversation[]>([])
const loading = ref(false)
const showDeleteDialog = ref(false)
const deleteTargetConv = ref<Conversation | null>(null)

// 滑动状态管理
const swipeStates = reactive<Record<number, {
  startX: number
  currentX: number
  offset: number
  swiping: boolean
}>>({})

const ACTION_WIDTH = 160 // 操作按钮总宽度

function getSwipeOffset(convId: number): number {
  return swipeStates[convId]?.offset || 0
}

function onTouchStart(e: TouchEvent, convId: number) {
  // 关闭其他已展开的滑动
  Object.keys(swipeStates).forEach((id) => {
    if (Number(id) !== convId && swipeStates[Number(id)]) {
      swipeStates[Number(id)].offset = 0
    }
  })

  const touch = e.touches[0]
  if (!swipeStates[convId]) {
    swipeStates[convId] = {
      startX: touch.clientX,
      currentX: touch.clientX,
      offset: 0,
      swiping: false,
    }
  } else {
    swipeStates[convId].startX = touch.clientX - swipeStates[convId].offset
    swipeStates[convId].swiping = false
  }
}

function onTouchMove(e: TouchEvent, convId: number) {
  if (!swipeStates[convId]) return

  const touch = e.touches[0]
  const deltaX = touch.clientX - swipeStates[convId].startX

  // 只允许向左滑动
  if (deltaX < 0) {
    swipeStates[convId].swiping = true
    swipeStates[convId].offset = Math.max(deltaX, -ACTION_WIDTH)
  } else {
    swipeStates[convId].offset = Math.min(deltaX, 0)
  }
}

function onTouchEnd(_e: TouchEvent, convId: number) {
  if (!swipeStates[convId]) return

  const offset = swipeStates[convId].offset

  // 如果滑动超过一半，展开；否则收起
  if (offset < -ACTION_WIDTH / 2) {
    swipeStates[convId].offset = -ACTION_WIDTH
  } else {
    swipeStates[convId].offset = 0
  }

  // 重置滑动状态
  setTimeout(() => {
    if (swipeStates[convId]) {
      swipeStates[convId].swiping = false
    }
  }, 300)
}

function closeSwipe(convId: number) {
  if (swipeStates[convId]) {
    swipeStates[convId].offset = 0
  }
}

function formatTime(dateStr?: string) {
  if (!dateStr) return ''
  return appStore.formatRelativeTime(dateStr)
}

// 获取预览文本：当lastMessage含商品标题时，用翻译后的goodsInfo.title替代
function getPreviewText(conv: Conversation) {
  if (!conv.lastMessage) return t('message.noMessages')
  if (conv.goodsInfo?.title && conv.lastMessage.startsWith('[商品]')) {
    return `[${t('message.goods')}] ${conv.goodsInfo.title}`
  }
  return conv.lastMessage
}

function goLogin() {
  navigateToLogin()
}

// 头像加载失败处理 - 清空头像以显示首字母
function onAvatarError(_e: Event, conv: Conversation) {
  conv.targetAvatar = ''
}

function goChat(conv: Conversation) {
  // 如果正在滑动，不跳转
  const state = swipeStates[conv.id]
  if (state?.swiping) {
    return
  }
  // 如果已展开滑动菜单，先关闭
  if (state?.offset && state.offset < -10) {
    closeSwipe(conv.id)
    return
  }

  // 跳转到聊天页面（使用传统方式拼接参数，兼容 APP 端）
  const params: string[] = [
    `conversationId=${conv.id}`,
    `targetName=${encodeURIComponent(conv.targetName)}`,
  ]
  if (conv.targetAvatar) {
    params.push(`targetAvatar=${encodeURIComponent(conv.targetAvatar)}`)
  }
  if (conv.goodsId) {
    params.push(`goodsId=${conv.goodsId}`)
  }
  uni.navigateTo({ url: `/pages/chat/index?${params.join('&')}` })
}

async function handlePin(conv: Conversation) {
  closeSwipe(conv.id)

  try {
    const res = await pinConversation(conv.id)
    if (res.code === 0) {
      conv.isPinned = res.data.isPinned
      // 重新排序会话列表
      await loadConversations()
      toast.success(res.data.isPinned ? t('message.pinSuccess') : t('message.unpinSuccess'))
    }
  } catch (e) {
    console.error('Failed to pin conversation:', e)
    toast.error(t('common.operationFailed'))
  }
}

function handleDelete(conv: Conversation) {
  closeSwipe(conv.id)
  deleteTargetConv.value = conv
  showDeleteDialog.value = true
}

async function confirmDelete() {
  const conv = deleteTargetConv.value
  if (!conv) return

  try {
    const res = await deleteConversation(conv.id)
    if (res.code === 0) {
      // 从列表中移除
      conversations.value = conversations.value.filter((c) => c.id !== conv.id)
      toast.success(t('message.deleteConversationSuccess'))
    }
  } catch (e) {
    console.error('Failed to delete conversation:', e)
    toast.error(t('common.operationFailed'))
  }
}

async function loadConversations() {
  if (!isLoggedIn.value) return

  loading.value = true
  try {
    const res = await getConversations()
    if (res.code === 0) {
      conversations.value = res.data || []
    }
  } catch (e) {
    console.error('Failed to load conversations:', e)
  } finally {
    loading.value = false
  }
}

onShow(() => {
  // #ifdef APP-PLUS
  uni.hideTabBar({ animation: false })
  // #endif
  loadConversations()
})
</script>

<style lang="scss" scoped>
// TabBar 高度 = padding-top(10px) + content(56px) + 安全区域预留
// 实际高度约 66px，但需要预留安全区域，H5 端一般没有安全区域
$tabbar-height: 66px;
// 导航栏高度
$navbar-height: 44px;

.page {
  position: fixed;
  top: var(--window-top, 0);
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #f5f5f5;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.login-prompt {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-bottom: $tabbar-height;
}

.prompt-icon {
  font-size: 64px;
  margin-bottom: 16px;
  color: #ccc;
}

.prompt-text {
  font-size: 16px;
  color: #666;
  margin-bottom: 24px;
}

.login-btn {
  background-color: #FF6B35;
  color: #fff;
  border: none;
  border-radius: 20px;
  padding: 12px 48px;
  font-size: 16px;
}

.message-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding-bottom: $tabbar-height;
}

.loading-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
}

.message-list {
  flex: 1;
  height: 0; // 关键：配合 flex: 1 实现正确的滚动
  background-color: #fff;
}

.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  color: #ccc;
}

.empty-text {
  font-size: 14px;
  color: #999;
}

// 滑动容器
.swipe-container {
  position: relative;
  overflow: hidden;
}

// 滑动操作按钮
.swipe-actions {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  display: flex;
  align-items: stretch;
}

.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 80px;
  color: #fff;
  font-size: 12px;

  .bi {
    font-size: 20px;
    margin-bottom: 4px;
  }
}

.action-pin {
  background-color: #1890ff;
}

.action-delete {
  background-color: #ff4d4f;
}

// 会话项
.conversation-item {
  position: relative;
  display: flex;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f5f5f5;
  background-color: #fff;
  transition: transform 0.3s ease, background-color 0.15s ease-out;
  will-change: transform;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;

  &.is-pinned {
    background-color: #fafafa;
  }

  // 点击态
  &:active {
    background-color: #f5f5f5;
  }
}

.pin-indicator {
  position: absolute;
  top: 8px;
  left: 8px;
  color: #1890ff;
  font-size: 12px;
}

// 头像容器
.avatar-wrapper {
  width: 48px;
  height: 48px;
  margin-right: 12px;
  flex-shrink: 0;
  border-radius: 50%;
  overflow: hidden;
  background-color: #f0f0f0;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: block;
}

.avatar-initial {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FF6B35;
}

.conv-content {
  flex: 1;
  min-width: 0;
}

.conv-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conv-name {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.conv-time {
  font-size: 12px;
  color: #999;
}

.conv-preview {
  display: flex;
  align-items: center;
}

.preview-text {
  flex: 1;
  font-size: 14px;
  color: #999;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.unread-badge {
  min-width: 18px;
  height: 18px;
  background-color: #FF6B35;
  color: #fff;
  font-size: 10px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  margin-left: 8px;
}

.goods-tag {
  margin-top: 6px;
  padding: 4px 8px;
  background-color: #f5f5f5;
  border-radius: 4px;
  display: inline-flex;
}

.goods-tag-text {
  font-size: 12px;
  color: #666;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
}
</style>
