<template>
  <view class="chat-page">
    <!-- 内联导航栏（不使用 NavBar 组件，避免 fixed 定位导致键盘问题） -->
    <view class="chat-nav" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="chat-nav__content">
        <view class="chat-nav__back" @click="goBack">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="chat-nav__title">{{ conversation?.targetName || t('chat.title') }}</text>
        <view class="chat-nav__right">
          <view class="nav-action" @click="showMoreMenu = true">
            <text class="bi bi-three-dots-vertical"></text>
          </view>
        </view>
      </view>
    </view>

    <!-- 商品卡片（如果有关联商品） -->
    <view v-if="goodsInfo" class="goods-card" @click="goGoodsDetail">
      <image class="goods-image" :src="goodsInfo.image" mode="aspectFill" />
      <view class="goods-info">
        <text class="goods-title">{{ goodsInfo.title }}</text>
        <text class="goods-price">{{ formatPrice(goodsInfo.price, 'USD') }}</text>
      </view>
    </view>

    <!-- 消息列表 -->
    <scroll-view
      class="message-list"
      scroll-y
      :scroll-top="scrollTop"
      :scroll-into-view="scrollIntoView"
      @scrolltoupper="loadMoreMessages"
    >
      <view v-if="loadingMore" class="loading-more">
        <text>{{ t('common.loading') }}</text>
      </view>

      <view
        v-for="msg in messages"
        :key="msg.id"
        :id="'msg-' + msg.id"
        class="message-item"
        :class="{ 'is-self': isSelfMessage(msg), 'is-selected': isMultiSelect && selectedMsgIds.includes(msg.id) }"
      >
        <!-- 多选模式下的选择框 -->
        <view v-if="isMultiSelect" class="msg-checkbox" @click="toggleSelectMsg(msg.id)">
          <text :class="['bi', selectedMsgIds.includes(msg.id) ? 'bi-check-circle-fill' : 'bi-circle']"></text>
        </view>
        <!-- 对方头像 -->
        <view v-if="!isSelfMessage(msg)" class="msg-avatar-wrapper">
          <image
            v-if="hasValidAvatar(conversation?.targetAvatar)"
            class="msg-avatar"
            :src="conversation?.targetAvatar"
            mode="aspectFill"
          />
          <view v-else class="msg-avatar msg-avatar-default">
            <text>{{ getAvatarInitial(conversation?.targetName) }}</text>
          </view>
        </view>
        <!-- 发送失败标记（左侧，仅自己的消息显示） -->
        <view
          v-if="isSelfMessage(msg) && msg.status === 'failed'"
          class="msg-failed-indicator"
          @click="handleResend(msg)"
        >
          <text class="bi bi-exclamation-circle-fill"></text>
        </view>
        <!-- 发送中loading（左侧，仅自己的消息显示） -->
        <view v-if="isSelfMessage(msg) && msg.status === 'pending'" class="msg-pending-indicator">
          <view class="loading-spinner"></view>
        </view>
        <view class="msg-content" @longpress="handleLongPress(msg, $event)">
          <!-- 文本消息 -->
          <view v-if="msg.type === MESSAGE_TYPE.TEXT" class="msg-bubble">
            <text class="msg-text">{{ msg.content }}</text>
          </view>
          <!-- 引用消息预览（显示在消息下方） -->
          <view v-if="msg.quotedMessage" class="quoted-message-box" @click="scrollToQuotedMsg(msg.quotedMessage.id)">
            <view class="quoted-line"></view>
            <view class="quoted-body">
              <text class="quoted-content">{{ msg.quotedMessage.content }}</text>
            </view>
          </view>
          <!-- 图片消息 -->
          <image
            v-else-if="msg.type === MESSAGE_TYPE.IMAGE"
            class="msg-image"
            :src="msg.content"
            mode="widthFix"
            @click="previewImage(msg.content)"
          />
          <!-- 商品卡片消息 -->
          <view v-else-if="msg.type === MESSAGE_TYPE.GOODS" class="msg-goods-card" @click="goGoodsDetail(msg.extra?.goodsId)">
            <image class="card-image" :src="msg.extra?.image" mode="aspectFill" />
            <view class="card-info">
              <text class="card-title">{{ msg.extra?.title }}</text>
              <text class="card-price">{{ formatPrice(msg.extra?.price, msg.extra?.currency || 'USD') }}</text>
            </view>
          </view>
          <!-- 已撤回消息 -->
          <view v-else-if="msg.type === MESSAGE_TYPE.RECALLED" class="msg-recalled">
            <text>{{ isSelfMessage(msg) ? t('chat.youRecalled') : t('chat.otherRecalled') }}</text>
          </view>
          <!-- 系统消息 -->
          <view v-else-if="msg.type === MESSAGE_TYPE.SYSTEM" class="msg-system">
            <text>{{ msg.content }}</text>
          </view>
          <view class="msg-footer">
            <text class="msg-time">{{ formatTime(msg.createdAt) }}</text>
            <text v-if="isSelfMessage(msg) && msg.status === 'failed'" class="msg-failed-text" @click="handleResend(msg)">
              {{ t('chat.tapToResend') }}
            </text>
          </view>
        </view>
        <!-- 自己的头像 -->
        <view v-if="isSelfMessage(msg)" class="msg-avatar-wrapper">
          <image
            v-if="hasValidAvatar(userStore.userInfo?.avatar)"
            class="msg-avatar"
            :src="userStore.userInfo?.avatar"
            mode="aspectFill"
          />
          <view v-else class="msg-avatar msg-avatar-default">
            <text>{{ getAvatarInitial(userStore.userInfo?.nickname) }}</text>
          </view>
        </view>
      </view>
    </scroll-view>

    <!-- 输入区域 -->
    <view class="input-area" :class="{ 'with-quote': quotingMessage }">
      <!-- 引用消息预览 -->
      <view v-if="quotingMessage" class="quote-preview">
        <view class="quote-content">
          <text class="quote-label">{{ t('chat.replyTo') }}</text>
          <text class="quote-text">{{ getQuotePreviewText(quotingMessage) }}</text>
        </view>
        <view class="quote-close" @click="cancelQuote">
          <text class="bi bi-x"></text>
        </view>
      </view>
      <view class="input-row">
        <view class="camera-btn" @click="showImagePicker = true">
          <text class="bi bi-camera"></text>
        </view>
        <input
          v-model="inputText"
          class="input"
          :placeholder="t('chat.inputPlaceholder')"
          :confirm-type="'send'"
          :adjust-position="false"
          :cursor-spacing="10"
          @confirm="handleSend"
          @focus="onInputFocus"
          @blur="onInputBlur"
          @keyboardheightchange="onKeyboardHeightChange"
        />
        <button class="send-btn" :disabled="!inputText.trim()" @click="handleSend">
          {{ t('chat.send') }}
        </button>
      </view>
    </view>

    <!-- 多选模式底部操作栏 -->
    <view v-if="isMultiSelect" class="multi-select-bar">
      <view class="select-info">
        <text>{{ formatSelectedText(selectedMsgIds.length) }}</text>
      </view>
      <view class="select-actions">
        <view class="select-action" @click="handleMultiDelete">
          <text class="bi bi-trash"></text>
          <text>{{ t('chat.delete') }}</text>
        </view>
        <view class="select-action" @click="exitMultiSelect">
          <text class="bi bi-x-circle"></text>
          <text>{{ t('common.cancel') }}</text>
        </view>
      </view>
    </view>

    <!-- 图片选择弹窗 -->
    <view v-if="showImagePicker" class="picker-mask" @click="showImagePicker = false">
      <view class="image-picker-popup" @click.stop>
        <view class="picker-handle"></view>
        <view class="picker-item" @click="handleChooseImage('camera')">
          <text class="picker-text">{{ t('chat.takePhoto') }}</text>
          <text class="bi bi-camera picker-icon"></text>
        </view>
        <view class="picker-item" @click="handleChooseImage('album')">
          <text class="picker-text">{{ t('chat.chooseImage') }}</text>
          <text class="bi bi-image picker-icon"></text>
        </view>
      </view>
    </view>

    <!-- 更多菜单弹窗 -->
    <view v-if="showMoreMenu" class="picker-mask" @click="showMoreMenu = false">
      <view class="more-menu-popup" @click.stop>
        <view class="picker-handle"></view>
        <view class="picker-item" @click="handleReport">
          <text class="picker-text">{{ t('chat.report') }}</text>
          <text class="bi bi-flag picker-icon"></text>
        </view>
        <view class="picker-item" @click="handleBlock">
          <text class="picker-text">{{ t('chat.blockUser') }}</text>
          <text class="bi bi-slash-circle picker-icon"></text>
        </view>
        <view class="picker-item picker-item-danger" @click="handleClearChat">
          <text class="picker-text">{{ t('chat.clearHistory') }}</text>
          <text class="bi bi-trash picker-icon"></text>
        </view>
      </view>
    </view>

    <!-- 消息操作菜单（气泡式，显示在消息上方） -->
    <view v-if="showMsgActionMenu" class="msg-action-overlay" @click="closeMsgActionMenu">
      <view
        class="msg-action-bubble"
        :class="{ 'is-self': actionMenuMsg && isSelfMessage(actionMenuMsg) }"
        :style="actionMenuPosition"
        @click.stop
      >
        <view class="bubble-arrow"></view>
        <view class="bubble-content">
          <!-- 复制（仅文本消息） -->
          <view v-if="actionMenuMsg?.type === MESSAGE_TYPE.TEXT" class="bubble-item" @click="handleCopy">
            <text class="bi bi-clipboard"></text>
            <text>{{ t('chat.copy') }}</text>
          </view>
          <!-- 引用 -->
          <view class="bubble-item" @click="handleQuote">
            <text class="bi bi-reply"></text>
            <text>{{ t('chat.quote') }}</text>
          </view>
          <!-- 撤回（仅自己的消息） -->
          <view v-if="actionMenuMsg && isSelfMessage(actionMenuMsg)" class="bubble-item" @click="handleRecall">
            <text class="bi bi-arrow-counterclockwise"></text>
            <text>{{ t('chat.recall') }}</text>
          </view>
          <!-- 多选 -->
          <view class="bubble-item" @click="enterMultiSelect">
            <text class="bi bi-check2-square"></text>
            <text>{{ t('chat.multiSelect') }}</text>
          </view>
          <!-- 删除 -->
          <view class="bubble-item bubble-item-danger" @click="handleDelete">
            <text class="bi bi-trash"></text>
            <text>{{ t('chat.delete') }}</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 重发消息确认弹窗 -->
    <ConfirmDialog
      :visible="showResendDialog"
      :title="t('chat.resendTitle')"
      :content="t('chat.resendConfirm')"
      icon="bi-arrow-repeat"
      icon-type="warning"
      @update:visible="showResendDialog = $event"
      @confirm="confirmResend"
    />

    <!-- 举报用户确认弹窗 -->
    <ConfirmDialog
      :visible="showReportDialog"
      :title="t('chat.reportTitle')"
      :content="t('chat.reportConfirm')"
      icon="bi-flag"
      icon-type="warning"
      @update:visible="showReportDialog = $event"
      @confirm="confirmReport"
    />

    <!-- 拉黑用户确认弹窗 -->
    <ConfirmDialog
      :visible="showBlockDialog"
      :title="t('chat.blockTitle')"
      :content="t('chat.blockConfirm')"
      icon="bi-slash-circle"
      icon-type="warning"
      @update:visible="showBlockDialog = $event"
      @confirm="confirmBlock"
    />

    <!-- 清空聊天记录确认弹窗 -->
    <ConfirmDialog
      :visible="showClearChatDialog"
      :title="t('chat.clearTitle')"
      :content="t('chat.clearConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showClearChatDialog = $event"
      @confirm="confirmClearChat"
    />

    <!-- 撤回消息确认弹窗 -->
    <ConfirmDialog
      :visible="showRecallDialog"
      :title="t('chat.recallTitle')"
      :content="t('chat.recallConfirm')"
      icon="bi-arrow-counterclockwise"
      icon-type="warning"
      @update:visible="showRecallDialog = $event"
      @confirm="confirmRecall"
    />

    <!-- 删除消息确认弹窗 -->
    <ConfirmDialog
      :visible="showDeleteDialog"
      :title="t('chat.deleteTitle')"
      :content="t('chat.deleteConfirm')"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showDeleteDialog = $event"
      @confirm="confirmDelete"
    />

    <!-- 批量删除消息确认弹窗 -->
    <ConfirmDialog
      :visible="showMultiDeleteDialog"
      :title="t('chat.deleteTitle')"
      :content="formatDeleteMultiConfirmText(selectedMsgIds.length)"
      icon="bi-trash"
      icon-type="warning"
      @update:visible="showMultiDeleteDialog = $event"
      @confirm="confirmMultiDelete"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { onLoad, onShow, onHide, onUnload } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import {
  getOrCreateConversation,
  getMessages,
  sendMessage,
  markAsRead,
  pollMessages,
  recallMessage,
  deleteMessage,
  deleteMessages,
  MESSAGE_TYPE,
  type Conversation,
  type Message,
  type QuotedMessage,
} from '@/api/chat'
import { playMessageSound, playSendSound, initAudio, destroyAudioContext } from '@/utils/audio'
import { uploadChatImage } from '@/api/upload'
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const { t, locale: i18nLocale } = useI18n()
const userStore = useUserStore()
const appStore = useAppStore()
const toast = useToast()

// 辅助函数：手动替换翻译模板中的占位符（UniApp APP端 vue-i18n 插值不生效的workaround）
function formatSelectedText(count: number): string {
  const template = t('chat.selected')
  return template.replace('[COUNT]', String(count))
}

function formatDeleteMultiConfirmText(count: number): string {
  const template = t('chat.deleteMultiConfirm')
  return template.replace('[COUNT]', String(count))
}

// 状态栏高度
const statusBarHeight = ref(0)
// 更多菜单显示状态
const showMoreMenu = ref(false)

// 获取系统信息
const systemInfo = uni.getSystemInfoSync()
statusBarHeight.value = systemInfo.statusBarHeight || 0

// 返回上一页
function goBack() {
  uni.navigateBack({
    fail: () => {
      // 如果没有上一页，跳转到消息列表
      uni.switchTab({ url: '/pages/message/index' })
    }
  })
}

// 消息发送状态
type MessageStatus = 'pending' | 'sent' | 'failed'

// 扩展消息类型，添加发送状态
type MessageWithStatus = Message & {
  status?: MessageStatus
  localId?: number // 本地临时ID，用于重发
}

const conversation = ref<Conversation | null>(null)
const messages = ref<MessageWithStatus[]>([])
const inputText = ref('')
const loading = ref(false)
const loadingMore = ref(false)
const scrollTop = ref(0)
const scrollIntoView = ref('')
const showImagePicker = ref(false)
const imagePickerPopup = ref<any>(null)

// 消息操作相关状态
const isMultiSelect = ref(false)
const selectedMsgIds = ref<number[]>([])
const quotingMessage = ref<Message | null>(null)
const showMsgActionMenu = ref(false)
const actionMenuMsg = ref<MessageWithStatus | null>(null)
const actionMenuPosition = ref<{ top: string; left?: string; right?: string }>({ top: '0px' })

// 确认弹窗状态
const showResendDialog = ref(false)
const showReportDialog = ref(false)
const showBlockDialog = ref(false)
const showClearChatDialog = ref(false)
const showRecallDialog = ref(false)
const showDeleteDialog = ref(false)
const showMultiDeleteDialog = ref(false)
const pendingResendMsg = ref<MessageWithStatus | null>(null)
const pendingRecallMsg = ref<MessageWithStatus | null>(null)
const pendingDeleteMsg = ref<MessageWithStatus | null>(null)

// 键盘状态
const keyboardHeight = ref(0)
const isInputFocused = ref(false)

// 商品信息
const goodsInfo = computed(() => conversation.value?.goodsInfo)

// 格式化价格（使用汇率转换）
const formatPrice = (amount: number, currency: string = 'USD') => {
  return appStore.formatPrice(amount, currency)
}

// 格式化时间（兼容 APP 端，不使用 toLocaleTimeString）
const formatTime = (dateStr: string) => {
  if (!dateStr) return ''

  let date: Date

  // 尝试解析不同格式的日期字符串
  if (dateStr.includes('T') || dateStr.includes('Z')) {
    // ISO 格式: "2025-12-27T19:15:34.000Z"
    date = new Date(dateStr)
  } else if (dateStr.includes(' ')) {
    // MySQL 格式: "2025-12-27 19:15:34"
    date = new Date(dateStr.replace(' ', 'T'))
  } else {
    date = new Date(dateStr)
  }

  // 检查日期是否有效
  if (isNaN(date.getTime())) return ''

  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()

  // 手动格式化时间（兼容 APP 端）
  const hours = date.getHours().toString().padStart(2, '0')
  const minutes = date.getMinutes().toString().padStart(2, '0')
  const timeStr = `${hours}:${minutes}`

  if (isToday) {
    return timeStr
  }

  // 非今天，根据语言显示日期 + 时间
  const month = date.getMonth() + 1
  const day = date.getDate()

  // 获取当前语言设置（从 i18n 获取，更可靠）
  // i18nLocale 是 ComputedRef，需要访问 .value
  const currentLocale = (i18nLocale.value || 'zh-tw').toLowerCase()

  // 中文和日文使用"X月X日"格式
  if (currentLocale.startsWith('zh') || currentLocale.startsWith('ja')) {
    return `${month}月${day}日 ${timeStr}`
  }

  // 英文使用"M/D"格式
  return `${month}/${day} ${timeStr}`
}

// 判断是否是自己发送的消息
const isSelfMessage = (msg: Message) => {
  return msg.senderType === 'user' && msg.senderId === userStore.userInfo?.id
}

// 检查头像URL是否有效（非空且不是仅包含域名的URL）
const hasValidAvatar = (avatar?: string): boolean => {
  if (!avatar) return false
  // 检查是否是有效的头像URL（不是空字符串，不是仅域名）
  const trimmed = avatar.trim()
  if (!trimmed) return false
  // 排除只有域名没有路径的情况，如 "http://example.com" 或 "http://example.com/"
  try {
    const url = new URL(trimmed)
    const pathname = url.pathname
    return !!(pathname && pathname !== '/' && pathname.length > 1)
  } catch {
    // 如果不是有效URL，检查是否是相对路径
    return trimmed.length > 0 && !trimmed.startsWith('/')
  }
}

// 获取头像首字母
const getAvatarInitial = (name?: string): string => {
  const trimmed = name?.trim()
  if (!trimmed) return '?'
  // 取第一个字符，支持中文和英文
  return trimmed.charAt(0).toUpperCase()
}

// 加载会话
async function loadConversation(params: { type: number; targetId: number; goodsId?: number }) {
  loading.value = true
  try {
    const res = await getOrCreateConversation(params)
    conversation.value = res.data.conversation

    // 设置导航栏标题
    uni.setNavigationBarTitle({
      title: conversation.value.targetName || t('chat.title'),
    })

    // 加载消息
    await loadMessages()

    // 标记已读并刷新角标
    if (conversation.value.unreadCount > 0) {
      await markAsRead(conversation.value.id)
      uni.$emit('refreshUnreadBadge')
    }
  } catch (e) {
    console.error('Failed to load conversation:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 加载消息列表
async function loadMessages() {
  if (!conversation.value) return

  try {
    const res = await getMessages(conversation.value.id, { pageSize: 50 })
    messages.value = res.data.list.reverse() // 按时间正序显示

    // 滚动到底部
    nextTick(() => {
      scrollToBottom()
    })
  } catch (e) {
    console.error('Failed to load messages:', e)
  }
}

// 加载更多消息
async function loadMoreMessages() {
  if (!conversation.value || loadingMore.value || messages.value.length === 0) return

  loadingMore.value = true
  try {
    const firstMsg = messages.value[0]
    const res = await getMessages(conversation.value.id, {
      pageSize: 20,
      lastId: firstMsg.id,
    })

    if (res.data.list.length > 0) {
      messages.value = [...res.data.list.reverse(), ...messages.value]
    }
  } catch (e) {
    console.error('Failed to load more messages:', e)
  } finally {
    loadingMore.value = false
  }
}

// 发送消息
async function handleSend() {
  const content = inputText.value.trim()
  if (!content || !conversation.value) return

  // 保存引用消息信息
  const quoteMsg = quotingMessage.value
  const quoteId = quoteMsg?.id

  // 清空输入和引用
  inputText.value = ''
  quotingMessage.value = null

  // 生成本地临时ID
  const localId = Date.now()

  // 乐观更新：先显示消息（状态为pending）
  const tempMsg: MessageWithStatus = {
    id: localId,
    conversationId: conversation.value.id,
    senderId: userStore.userInfo?.id || 0,
    senderType: 'user',
    content,
    type: MESSAGE_TYPE.TEXT,
    isRead: false,
    createdAt: new Date().toISOString(),
    status: 'pending',
    localId,
    quoteId,
    quotedMessage: quoteMsg ? {
      id: quoteMsg.id,
      senderId: quoteMsg.senderId,
      senderType: quoteMsg.senderType,
      content: getQuotePreviewText(quoteMsg),
      type: quoteMsg.type,
    } : undefined,
  }
  messages.value.push(tempMsg)

  nextTick(() => {
    scrollToBottom()
  })

  try {
    const res = await sendMessage(conversation.value.id, {
      content,
      type: MESSAGE_TYPE.TEXT,
      quoteId,
    })

    // 替换临时消息，标记为sent
    const idx = messages.value.findIndex((m) => m.localId === localId)
    if (idx !== -1) {
      messages.value[idx] = { ...res.data, status: 'sent' }
    }
    // 播放发送成功提示音
    playSendSound()
  } catch (e) {
    console.error('Failed to send message:', e)
    // 发送失败，标记消息状态为failed
    const idx = messages.value.findIndex((m) => m.localId === localId)
    if (idx !== -1) {
      messages.value[idx].status = 'failed'
    }
  }
}

// 重发消息
function handleResend(msg: MessageWithStatus) {
  if (!conversation.value || msg.status !== 'failed') return
  pendingResendMsg.value = msg
  showResendDialog.value = true
}

// 输入框获取焦点
function onInputFocus() {
  isInputFocused.value = true
  // 滚动到底部
  nextTick(() => {
    scrollToBottom()
  })
}

// 输入框失去焦点
function onInputBlur() {
  isInputFocused.value = false
  // 延迟重置键盘高度，避免闪烁
  setTimeout(() => {
    if (!isInputFocused.value) {
      keyboardHeight.value = 0
    }
  }, 100)
}

// 键盘高度变化处理（APP端）
function onKeyboardHeightChange(e: any) {
  const height = e.detail?.height || 0
  keyboardHeight.value = height
  if (height > 0) {
    nextTick(() => {
      scrollToBottom()
    })
  }
}

// 全局键盘高度变化监听（APP端更可靠）
function setupKeyboardListener() {
  // #ifdef APP-PLUS
  uni.onKeyboardHeightChange((res) => {
    keyboardHeight.value = res.height || 0
    if (res.height > 0) {
      nextTick(() => {
        scrollToBottom()
      })
    }
  })
  // #endif
}

// 确认重发消息
async function confirmResend() {
  const msg = pendingResendMsg.value
  if (!msg || !conversation.value) return

  // 更新状态为pending
  const idx = messages.value.findIndex((m) => m.localId === msg.localId)
  if (idx === -1) return

  messages.value[idx].status = 'pending'

  try {
    const sendRes = await sendMessage(conversation.value!.id, {
      content: msg.content,
      type: msg.type,
    })

    // 替换消息，标记为sent
    messages.value[idx] = { ...sendRes.data, status: 'sent' }
  } catch (e) {
    console.error('Failed to resend message:', e)
    // 重发失败，继续标记为failed
    messages.value[idx].status = 'failed'
    toast.error(t('chat.sendFailed'))
  }
}

// 滚动到底部
function scrollToBottom() {
  if (messages.value.length > 0) {
    scrollIntoView.value = 'msg-' + messages.value[messages.value.length - 1].id
  }
}

// 预览图片
function previewImage(url: string) {
  uni.previewImage({ urls: [url] })
}

// 选择图片
function handleChooseImage(sourceType: 'camera' | 'album') {
  showImagePicker.value = false

  uni.chooseImage({
    count: 1,
    sourceType: [sourceType],
    success: async (res) => {
      const tempFilePath = res.tempFilePaths[0]
      await sendImageMessage(tempFilePath)
    },
  })
}

// 发送图片消息
async function sendImageMessage(filePath: string) {
  if (!conversation.value) return

  // 生成本地临时ID
  const localId = Date.now()

  // 乐观更新：先显示本地图片（状态为pending）
  const tempMsg: MessageWithStatus = {
    id: localId,
    conversationId: conversation.value.id,
    senderId: userStore.userInfo?.id || 0,
    senderType: 'user',
    content: filePath, // 临时使用本地路径
    type: MESSAGE_TYPE.IMAGE,
    isRead: false,
    createdAt: new Date().toISOString(),
    status: 'pending',
    localId,
  }
  messages.value.push(tempMsg)

  nextTick(() => {
    scrollToBottom()
  })

  try {
    // 上传图片
    uni.showLoading({ title: t('common.uploading') })
    const uploadRes = await uploadChatImage(filePath)
    uni.hideLoading()

    if (!uploadRes.data?.url) {
      throw new Error('Upload failed')
    }

    // 发送图片消息
    const res = await sendMessage(conversation.value!.id, {
      content: uploadRes.data.url,
      type: MESSAGE_TYPE.IMAGE,
    })

    // 替换临时消息，标记为sent
    const idx = messages.value.findIndex((m) => m.localId === localId)
    if (idx !== -1) {
      messages.value[idx] = { ...res.data, status: 'sent' }
    }
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to send image:', e)
    // 发送失败，标记消息状态为failed
    const idx = messages.value.findIndex((m) => m.localId === localId)
    if (idx !== -1) {
      messages.value[idx].status = 'failed'
    }
  }
}

// 跳转商品详情
function goGoodsDetail(id?: number) {
  const goodsId = id || goodsInfo.value?.id
  if (goodsId) {
    uni.navigateTo({ url: `/pages/goods/detail?id=${goodsId}` })
  }
}

// 举报用户
function handleReport() {
  showMoreMenu.value = false
  if (!conversation.value) return
  showReportDialog.value = true
}

// 确认举报用户
async function confirmReport() {
  if (!conversation.value) return
  try {
    uni.showLoading({ title: t('common.submitting') })
    const { reportUser } = await import('@/api/chat')
    await reportUser(conversation.value!.id)
    uni.hideLoading()
    toast.success(t('chat.reportSuccess'))
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to report:', e)
    toast.error(t('common.operationFailed'))
  }
}

// 拉黑用户
function handleBlock() {
  showMoreMenu.value = false
  if (!conversation.value) return
  showBlockDialog.value = true
}

// 确认拉黑用户
async function confirmBlock() {
  if (!conversation.value) return
  try {
    uni.showLoading({ title: t('common.submitting') })
    const { blockUser } = await import('@/api/chat')
    await blockUser(conversation.value!.id)
    uni.hideLoading()
    toast.success(t('chat.blockSuccess'))
    // 拉黑成功后返回消息列表
    setTimeout(() => {
      goBack()
    }, 1500)
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to block:', e)
    toast.error(t('common.operationFailed'))
  }
}

// 清空聊天记录
function handleClearChat() {
  showMoreMenu.value = false
  if (!conversation.value) return
  showClearChatDialog.value = true
}

// 确认清空聊天记录
async function confirmClearChat() {
  if (!conversation.value) return
  try {
    uni.showLoading({ title: t('common.submitting') })
    const { clearChatHistory } = await import('@/api/chat')
    await clearChatHistory(conversation.value!.id)
    uni.hideLoading()
    // 清空本地消息
    messages.value = []
    toast.success(t('chat.clearSuccess'))
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to clear chat:', e)
    toast.error(t('common.operationFailed'))
  }
}

// ==================== 消息操作功能 ====================

// 长按消息处理
function handleLongPress(msg: MessageWithStatus, event: any) {
  // 不处理已撤回的消息
  if (msg.type === MESSAGE_TYPE.RECALLED) return
  // 不处理发送中或发送失败的消息
  if (msg.status === 'pending' || msg.status === 'failed') return

  actionMenuMsg.value = msg

  // 获取触摸位置
  const touches = event.touches || event.changedTouches
  if (touches && touches.length > 0) {
    const touch = touches[0]

    // 获取屏幕宽度
    const screenWidth = systemInfo.windowWidth || 375

    // 菜单宽度估算（5个按钮，每个约50px + padding）
    const menuWidth = 280
    const menuHeight = 68

    // 计算菜单位置：在触摸点上方显示
    let top = touch.clientY - menuHeight - 10

    // 确保菜单不超出顶部（导航栏高度约44px + 状态栏）
    const minTop = statusBarHeight.value + 44 + 10
    if (top < minTop) {
      // 如果上方空间不够，显示在触摸点下方
      top = touch.clientY + 20
    }

    // 计算水平位置，确保不超出屏幕边界
    let left = touch.clientX - menuWidth / 2
    const padding = 10 // 边距

    // 限制左边界
    if (left < padding) {
      left = padding
    }
    // 限制右边界
    if (left + menuWidth > screenWidth - padding) {
      left = screenWidth - menuWidth - padding
    }

    actionMenuPosition.value = {
      top: `${top}px`,
      left: `${left}px`
    }
  }

  showMsgActionMenu.value = true
}

// 切换消息选择状态
function toggleSelectMsg(msgId: number) {
  const idx = selectedMsgIds.value.indexOf(msgId)
  if (idx === -1) {
    selectedMsgIds.value.push(msgId)
  } else {
    selectedMsgIds.value.splice(idx, 1)
  }
}

// 退出多选模式
function exitMultiSelect() {
  isMultiSelect.value = false
  selectedMsgIds.value = []
}

// 进入多选模式
function enterMultiSelect() {
  showMsgActionMenu.value = false
  isMultiSelect.value = true
  // 如果有当前操作的消息，默认选中它
  if (actionMenuMsg.value) {
    selectedMsgIds.value = [actionMenuMsg.value.id]
  }
}

// 获取引用消息预览文本
function getQuotePreviewText(msg: Message): string {
  if (msg.type === MESSAGE_TYPE.TEXT) {
    // 截取前50个字符
    return msg.content.length > 50 ? msg.content.substring(0, 50) + '...' : msg.content
  } else if (msg.type === MESSAGE_TYPE.IMAGE) {
    return t('chat.image')
  } else if (msg.type === MESSAGE_TYPE.GOODS) {
    return t('chat.goodsCard')
  }
  return msg.content
}

// 取消引用
function cancelQuote() {
  quotingMessage.value = null
}

// 滚动到被引用的消息
function scrollToQuotedMsg(msgId: number) {
  scrollIntoView.value = 'msg-' + msgId
}

// 复制消息
function handleCopy() {
  if (!actionMenuMsg.value) return
  showMsgActionMenu.value = false

  // 只能复制文本消息
  if (actionMenuMsg.value.type !== MESSAGE_TYPE.TEXT) {
    toast.warning(t('chat.cannotCopy'))
    return
  }

  uni.setClipboardData({
    data: actionMenuMsg.value.content,
    showToast: false, // 禁用 UniApp 默认提示
    success: () => {
      toast.success(t('chat.copied'))
    }
  })
}

// 引用消息
function handleQuote() {
  if (!actionMenuMsg.value) return
  showMsgActionMenu.value = false
  quotingMessage.value = actionMenuMsg.value
}

// 撤回消息
function handleRecall() {
  if (!actionMenuMsg.value || !conversation.value) return
  const msg = actionMenuMsg.value
  showMsgActionMenu.value = false

  // 只能撤回自己的消息
  if (!isSelfMessage(msg)) {
    toast.warning(t('chat.cannotRecallOther'))
    return
  }

  pendingRecallMsg.value = msg
  showRecallDialog.value = true
}

// 确认撤回消息
async function confirmRecall() {
  const msg = pendingRecallMsg.value
  if (!msg || !conversation.value) return

  try {
    uni.showLoading({ title: t('common.submitting') })
    const result = await recallMessage(conversation.value!.id, msg.id)
    uni.hideLoading()

    // 更新本地消息
    const idx = messages.value.findIndex(m => m.id === msg.id)
    if (idx !== -1) {
      messages.value[idx] = { ...messages.value[idx], ...result.data }
    }

    toast.success(t('chat.recallSuccess'))
  } catch (e: any) {
    uni.hideLoading()
    console.error('Failed to recall message:', e)
    // 显示具体错误信息
    const errorMsg = e?.data?.message || t('common.operationFailed')
    toast.error(errorMsg)
  }
}

// 删除单条消息
function handleDelete() {
  if (!actionMenuMsg.value || !conversation.value) return
  const msg = actionMenuMsg.value
  showMsgActionMenu.value = false

  pendingDeleteMsg.value = msg
  showDeleteDialog.value = true
}

// 确认删除单条消息
async function confirmDelete() {
  const msg = pendingDeleteMsg.value
  if (!msg || !conversation.value) return

  try {
    uni.showLoading({ title: t('common.submitting') })
    await deleteMessage(conversation.value!.id, msg.id)
    uni.hideLoading()

    // 从本地移除消息
    const idx = messages.value.findIndex(m => m.id === msg.id)
    if (idx !== -1) {
      messages.value.splice(idx, 1)
    }

    toast.success(t('chat.deleteSuccess'))
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to delete message:', e)
    toast.error(t('common.operationFailed'))
  }
}

// 批量删除消息
function handleMultiDelete() {
  if (!conversation.value || selectedMsgIds.value.length === 0) return
  showMultiDeleteDialog.value = true
}

// 确认批量删除消息
async function confirmMultiDelete() {
  if (!conversation.value || selectedMsgIds.value.length === 0) return

  try {
    uni.showLoading({ title: t('common.submitting') })
    await deleteMessages(conversation.value!.id, selectedMsgIds.value)
    uni.hideLoading()

    // 从本地移除消息
    messages.value = messages.value.filter(m => !selectedMsgIds.value.includes(m.id))

    // 退出多选模式
    exitMultiSelect()

    toast.success(t('chat.deleteSuccess'))
  } catch (e) {
    uni.hideLoading()
    console.error('Failed to delete messages:', e)
    toast.error(t('common.operationFailed'))
  }
}

// 关闭消息操作菜单
function closeMsgActionMenu() {
  showMsgActionMenu.value = false
  actionMenuMsg.value = null
}

onLoad((options) => {
  // 参数说明:
  // conversationId: 会话ID (从会话列表跳转时传入)
  // type: 1=私聊, 2=客服
  // targetId: 目标用户ID (私聊时) 或 0 (客服时由系统分配)
  // goodsId: 关联商品ID (可选)
  // targetName: 目标用户名称 (可选，用于显示)

  // 初始化音频
  initAudio()

  // 设置键盘监听
  setupKeyboardListener()

  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }

  // 如果有 conversationId，直接加载该会话
  if (options?.conversationId) {
    const conversationId = parseInt(options.conversationId)
    // URL 参数需要解码（从消息列表传递时使用了 encodeURIComponent）
    const targetName = options?.targetName ? decodeURIComponent(options.targetName) : ''
    const targetAvatar = options?.targetAvatar ? decodeURIComponent(options.targetAvatar) : ''

    // 设置临时会话信息用于显示
    conversation.value = {
      id: conversationId,
      type: 1,
      targetId: 0,
      targetType: 'user',
      targetName: targetName,
      targetAvatar: targetAvatar,
      unreadCount: 0,
    }

    // 设置导航栏标题
    if (targetName) {
      uni.setNavigationBarTitle({ title: targetName })
    }

    // 加载消息
    loadMessages()

    // 标记已读并刷新角标
    markAsRead(conversationId).then(() => {
      uni.$emit('refreshUnreadBadge')
    }).catch(() => {})
  } else {
    // 创建新会话
    const type = parseInt(options?.type || '1')
    const targetId = parseInt(options?.targetId || '0')
    const goodsId = options?.goodsId ? parseInt(options.goodsId) : undefined

    loadConversation({ type, targetId, goodsId })
  }
})

onShow(() => {
  // 刷新消息
  if (conversation.value) {
    loadMessages()
  }
  // 开始轮询
  startPolling()
})

onHide(() => {
  // 停止轮询
  stopPolling()
})

onUnload(() => {
  // 清理定时器和音频资源
  stopPolling()
  destroyAudioContext()
})

// 轮询定时器
let pollTimer: number | null = null
const POLL_INTERVAL = 3000 // 3秒轮询一次

// 开始轮询
function startPolling() {
  stopPolling() // 先清理旧的
  pollTimer = setInterval(pollNewMessages, POLL_INTERVAL) as unknown as number
}

// 停止轮询
function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

// 轮询获取新消息
async function pollNewMessages() {
  if (!conversation.value || messages.value.length === 0) return

  try {
    // 获取最后一条消息的 ID
    const lastMsgId = messages.value[messages.value.length - 1].id
    const res = await pollMessages(conversation.value.id, lastMsgId)

    if (res.data.hasNew && res.data.messages.length > 0) {
      // 过滤掉已存在的消息（避免重复）
      const existingIds = new Set(messages.value.map((m: Message) => m.id))
      const newMessages = res.data.messages.filter(m => !existingIds.has(m.id))

      if (newMessages.length > 0) {
        // 检查是否有对方发来的新消息（需要播放提示音）
        const hasOtherMessages = newMessages.some(m => !isSelfMessage(m))

        messages.value.push(...newMessages)
        // 滚动到底部
        nextTick(() => {
          scrollToBottom()
        })
        // 消息已被标记为已读，刷新角标
        uni.$emit('refreshUnreadBadge')

        // 播放消息提示音（仅对方消息）
        if (hasOtherMessages) {
          playMessageSound()
        }
      }
    }
  } catch (e) {
    console.error('Failed to poll messages:', e)
  }
}
</script>

<style lang="scss" scoped>
.chat-page {
  // 使用 100% 高度，配合 softinputMode: adjustResize
  // 当键盘弹起时，系统会调整 webview 大小，页面会自动适应
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
  overflow: hidden;
}

// 内联导航栏样式（不使用 fixed 定位，作为 flex 容器的一部分）
.chat-nav {
  flex-shrink: 0;
  background-color: #fff;
}

.chat-nav__content {
  height: 48px;
  display: flex;
  align-items: center;
  padding: 0 16px;
}

.chat-nav__back {
  width: 36px;
  height: 36px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;

  .bi {
    font-size: 22px;
    color: #191919;
  }

  &:active {
    background-color: #f7f7f7;
  }
}

.chat-nav__title {
  flex: 1;
  font-size: 17px;
  font-weight: 600;
  color: #191919;
  margin-left: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-nav__right {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}

.nav-action {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #333;
}

.more-menu-popup {
  width: 100%;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  padding-bottom: env(safe-area-inset-bottom);
}

.picker-item-danger {
  .picker-text,
  .picker-icon {
    color: #ff4d4f;
  }
}

.goods-card {
  display: flex;
  align-items: center;
  background-color: #fff;
  padding: 12px 16px;
  margin: 8px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.goods-image {
  width: 60px;
  height: 60px;
  border-radius: 4px;
  margin-right: 12px;
}

.goods-info {
  flex: 1;
}

.goods-title {
  font-size: 14px;
  color: #333;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-price {
  font-size: 16px;
  font-weight: 600;
  color: #FF6B35;
  margin-top: 4px;
}

.message-list {
  flex: 1;
  padding: 16px;
  padding-bottom: 16px;
  width: auto;
  background-color: #f5f5f5;
  min-height: 0; // 关键：配合 flex: 1 实现正确的滚动
}

.loading-more {
  text-align: center;
  padding: 10px;
  font-size: 12px;
  color: #999;
}

.message-item {
  display: flex;
  align-items: flex-start;
  margin-bottom: 16px;

  &.is-self {
    justify-content: flex-end;

    .msg-content {
      align-items: flex-end;
    }

    .msg-bubble {
      background-color: #FF6B35;
      color: #fff;
    }

    .msg-time {
      text-align: right;
    }
  }
}

.msg-avatar-wrapper {
  margin: 0 8px;
}

.msg-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
}

.msg-avatar-default {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #FF6B35;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  line-height: 36px;
  text-align: center;
  overflow: hidden;
}

.msg-content {
  display: flex;
  flex-direction: column;
  max-width: 70%;
}

.msg-bubble {
  background-color: #fff;
  padding: 10px 14px;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.msg-text {
  font-size: 15px;
  line-height: 1.4;
  word-wrap: break-word;
}

.msg-image {
  max-width: 200px;
  border-radius: 8px;
}

.msg-goods-card {
  display: flex;
  background-color: #fff;
  padding: 8px;
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.card-image {
  width: 50px;
  height: 50px;
  border-radius: 4px;
  margin-right: 8px;
}

.card-info {
  flex: 1;
}

.card-title {
  font-size: 13px;
  color: #333;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-price {
  font-size: 14px;
  font-weight: 600;
  color: #FF6B35;
  margin-top: 4px;
}

.msg-system {
  padding: 8px 16px;
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 12px;

  text {
    font-size: 12px;
    color: #999;
  }
}

.msg-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.msg-time {
  font-size: 11px;
  color: #999;
}

.msg-failed-text {
  font-size: 11px;
  color: #ff4d4f;
}

// 发送失败指示器
.msg-failed-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 6px;
  color: #ff4d4f;
  font-size: 18px;
  cursor: pointer;
}

// 发送中指示器
.msg-pending-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 6px;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #ddd;
  border-top-color: #FF6B35;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.input-area {
  flex-shrink: 0;
  background-color: #fff;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
  z-index: 100;
}

.input-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.input {
  flex: 1;
  height: 40px;
  background-color: #f5f5f5;
  border-radius: 20px;
  padding: 0 16px;
  font-size: 15px;
}

.send-btn {
  width: 70px;
  height: 40px;
  background-color: #FF6B35;
  color: #fff;
  border: none;
  border-radius: 20px;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;

  &[disabled] {
    background-color: #ccc;
    color: #fff;
  }
}

.camera-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
  font-size: 22px;
}

.picker-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
}

.image-picker-popup {
  width: 100%;
  background-color: #fff;
  border-radius: 16px 16px 0 0;
  padding-bottom: env(safe-area-inset-bottom);
}

.picker-handle {
  width: 36px;
  height: 4px;
  background-color: #ddd;
  border-radius: 2px;
  margin: 12px auto;
}

.picker-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }
}

.picker-icon {
  font-size: 24px;
  color: #666;
}

.picker-text {
  font-size: 16px;
  color: #333;
}

// 消息操作菜单（气泡式）
.msg-action-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 200;
}

.msg-action-bubble {
  position: absolute;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 201;
  max-width: calc(100vw - 20px); // 确保不超出屏幕
}

.bubble-arrow {
  display: none; // 隐藏箭头，因为动态定位难以准确对齐
}

.bubble-content {
  display: flex;
  flex-direction: row;
  padding: 8px 4px;
}

.bubble-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  min-width: 50px;

  .bi {
    font-size: 18px;
    color: #333;
    margin-bottom: 4px;
  }

  text:last-child {
    font-size: 11px;
    color: #666;
    white-space: nowrap;
  }

  &:active {
    background-color: #f5f5f5;
  }
}

.bubble-item-danger {
  .bi, text:last-child {
    color: #ff4d4f;
  }
}

// 多选模式
.msg-checkbox {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  margin-right: 8px;
  font-size: 20px;
  color: #999;

  .bi-check-circle-fill {
    color: #FF6B35;
  }
}

.message-item.is-selected {
  background-color: rgba(255, 107, 53, 0.1);
  border-radius: 8px;
  margin-left: -8px;
  margin-right: -8px;
  padding-left: 8px;
  padding-right: 8px;
}

// 多选模式底部操作栏
.multi-select-bar {
  flex-shrink: 0;
  background-color: #fff;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  z-index: 100;
}

.select-info {
  font-size: 14px;
  color: #666;
}

.select-actions {
  display: flex;
  gap: 20px;
}

.select-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: #666;
  font-size: 12px;

  .bi {
    font-size: 20px;
  }

  &:active {
    color: #FF6B35;
  }
}

// 引用消息预览（显示在消息下方）
.quoted-message-box {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  margin-top: 6px;
  max-width: 100%;
}

.quoted-line {
  width: 3px;
  background-color: #ccc;
  border-radius: 2px;
  flex-shrink: 0;
}

.quoted-body {
  background-color: #f0f0f0;
  padding: 6px 10px;
  border-radius: 0 8px 8px 0;
  flex: 1;
  min-width: 0;
}

.quoted-content {
  font-size: 12px;
  color: #666;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-all;
}

// 自己发送的消息中的引用样式
.message-item.is-self {
  .quoted-message-box {
    justify-content: flex-end;
  }

  .quoted-line {
    order: 2;
    border-radius: 2px;
  }

  .quoted-body {
    border-radius: 8px 0 0 8px;
    order: 1;
  }
}

// 输入区域引用预览
.input-area.with-quote {
  padding-top: 0;
}

.quote-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #f5f5f5;
  padding: 8px 12px;
  margin: 0 -16px 12px;
  border-left: 3px solid #FF6B35;
}

.quote-content {
  flex: 1;
  overflow: hidden;
}

.quote-label {
  font-size: 12px;
  color: #FF6B35;
  margin-right: 8px;
}

.quote-text {
  font-size: 13px;
  color: #666;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.quote-close {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #999;
}

// 已撤回消息
.msg-recalled {
  padding: 8px 14px;
  background-color: rgba(0, 0, 0, 0.03);
  border-radius: 12px;

  text {
    font-size: 13px;
    color: #999;
    font-style: italic;
  }
}
</style>
