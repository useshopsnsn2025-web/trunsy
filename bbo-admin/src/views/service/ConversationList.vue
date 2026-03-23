<template>
  <div class="conversation-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card unread">
          <div class="stat-value">{{ unreadStats.total }}</div>
          <div class="stat-label">未读消息</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card conversations">
          <div class="stat-value">{{ unreadStats.conversations }}</div>
          <div class="stat-label">未读会话</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card presale">
          <div class="stat-value">{{ presaleCount }}</div>
          <div class="stat-label">售前咨询</div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="stat-card aftersale">
          <div class="stat-value">{{ aftersaleCount }}</div>
          <div class="stat-label">售后服务</div>
        </el-card>
      </el-col>
    </el-row>

    <div class="main-content">
      <!-- 会话列表 -->
      <el-card class="conversation-sidebar">
        <template #header>
          <div class="sidebar-header">
            <span>会话列表</span>
            <el-radio-group v-model="searchForm.scene" size="small" @change="handleSearch">
              <el-radio-button :value="0">全部</el-radio-button>
              <el-radio-button :value="1">售前</el-radio-button>
              <el-radio-button :value="2">售后</el-radio-button>
            </el-radio-group>
          </div>
        </template>

        <div class="filter-row">
          <el-radio-group v-model="searchForm.status" size="small" @change="handleSearch">
            <el-radio-button value="">全部</el-radio-button>
            <el-radio-button value="unread">未读</el-radio-button>
          </el-radio-group>
          <el-radio-group v-model="searchForm.assign" size="small" @change="handleSearch" style="margin-left: 10px;">
            <el-radio-button value="">默认</el-radio-button>
            <el-radio-button value="mine">我的</el-radio-button>
            <el-radio-button value="unassigned">待接入</el-radio-button>
          </el-radio-group>
        </div>

        <div class="conversation-items" v-loading="loading">
          <div
            v-for="conv in conversations"
            :key="conv.id"
            class="conversation-item"
            :class="{ active: currentConversation?.id === conv.id }"
            @click="selectConversation(conv)"
          >
            <el-avatar :src="conv.userAvatar || undefined" :size="40">
              <span class="avatar-initial">{{ getAvatarInitial(conv.userName) }}</span>
            </el-avatar>
            <div class="conv-info">
              <div class="conv-header">
                <span class="user-name">{{ conv.userName }}</span>
                <span class="time">{{ formatTime(conv.lastMessageTime) }}</span>
              </div>
              <div class="conv-preview">
                <el-tag size="small" :type="conv.scene === 1 ? 'success' : 'warning'" class="scene-tag">
                  {{ conv.sceneText }}
                </el-tag>
                <el-tag v-if="!conv.isAssigned" size="small" type="danger" class="assign-tag">待接入</el-tag>
                <el-tag v-else-if="conv.serviceInfo" size="small" type="info" class="assign-tag">{{ conv.serviceInfo.name }}</el-tag>
                <span class="message">{{ conv.lastMessage || '暂无消息' }}</span>
              </div>
            </div>
            <el-badge v-if="conv.unreadCount > 0" :value="conv.unreadCount" class="unread-badge" />
          </div>

          <el-empty v-if="conversations.length === 0" description="暂无会话" />
        </div>

        <div class="pagination-wrapper">
          <el-pagination
            v-model:current-page="pagination.page"
            :page-size="pagination.pageSize"
            :total="pagination.total"
            layout="prev, pager, next"
            size="small"
            @current-change="loadConversations"
          />
        </div>
      </el-card>

      <!-- 聊天区域 -->
      <el-card class="chat-area">
        <template v-if="currentConversation">
          <!-- 聊天头部 -->
          <div class="chat-header">
            <div class="user-info">
              <el-avatar :src="currentConversation.userAvatar || undefined" :size="36">
                <span class="avatar-initial">{{ getAvatarInitial(currentConversation.userName) }}</span>
              </el-avatar>
              <div class="info">
                <div class="name">{{ currentConversation.userName }}</div>
                <div class="scene">
                  <el-tag size="small" :type="currentConversation.scene === 1 ? 'success' : 'warning'">
                    {{ currentConversation.sceneText }}
                  </el-tag>
                </div>
              </div>
            </div>
            <div class="actions">
              <el-button
                v-if="!currentConversation.isAssigned"
                type="primary"
                size="small"
                @click="claimConversationHandler"
              >
                接入会话
              </el-button>
              <el-dropdown v-if="currentConversation.isAssigned" trigger="click" @command="transferTo">
                <el-button size="small">
                  转接 <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item
                      v-for="service in onlineServices"
                      :key="service.id"
                      :command="service.id"
                    >
                      {{ service.name }} ({{ service.currentSessions }}/{{ service.maxSessions }})
                    </el-dropdown-item>
                    <el-dropdown-item v-if="onlineServices.length === 0" disabled>
                      暂无在线客服
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <el-button size="small" @click="closeConversationHandler" :disabled="currentConversation.isClosed">
                关闭会话
              </el-button>
            </div>
          </div>

          <!-- 商品信息 -->
          <div v-if="currentConversation.goodsInfo" class="goods-card">
            <el-image
              :src="currentConversation.goodsInfo.image"
              :preview-src-list="[currentConversation.goodsInfo.image]"
              fit="cover"
              class="goods-image"
            />
            <div class="goods-info">
              <div class="title">{{ currentConversation.goodsInfo.title }}</div>
              <div class="price">¥{{ currentConversation.goodsInfo.price }}</div>
            </div>
          </div>

          <!-- 消息列表 -->
          <div ref="messageListRef" class="message-list" @scroll="handleScroll">
            <div v-if="loadingMore" class="loading-more">
              加载中...
            </div>

            <div
              v-for="msg in messages"
              :key="msg.id"
              class="message-item"
              :class="{ 'is-self': msg.senderType === 'service' }"
            >
              <el-avatar
                v-if="msg.senderType !== 'service'"
                :src="currentConversation.userAvatar || undefined"
                :size="32"
              >
                <span class="avatar-initial avatar-initial-sm">{{ getAvatarInitial(currentConversation.userName) }}</span>
              </el-avatar>
              <div class="message-content">
                <!-- 文本消息 -->
                <div v-if="msg.type === 1" class="message-bubble">
                  {{ msg.content }}
                </div>
                <!-- 图片消息 -->
                <el-image
                  v-else-if="msg.type === 2"
                  :src="msg.content"
                  :preview-src-list="[msg.content]"
                  fit="cover"
                  class="message-image"
                />
                <!-- 商品卡片 -->
                <div v-else-if="msg.type === 3" class="message-goods">
                  <el-image :src="msg.extra?.image" fit="cover" class="goods-thumb" />
                  <div class="goods-detail">
                    <div class="title">{{ msg.extra?.title }}</div>
                    <div class="price">¥{{ msg.extra?.price }}</div>
                  </div>
                </div>
                <!-- 系统消息 -->
                <div v-else-if="msg.type === 5" class="message-system">
                  {{ msg.content }}
                </div>
                <div class="message-time">{{ formatMessageTime(msg.createdAt) }}</div>
              </div>
              <el-avatar
                v-if="msg.senderType === 'service'"
                :src="currentConversation?.serviceInfo?.avatar || ''"
                :size="32"
              >
                <el-icon><Service /></el-icon>
              </el-avatar>
            </div>
          </div>

          <!-- 输入区域 -->
          <div class="input-area">
            <div class="toolbar">
              <el-popover placement="top" :width="300" trigger="click">
                <template #reference>
                  <el-button text>
                    <el-icon><ChatDotRound /></el-icon>
                    快捷回复
                    <el-tag v-if="currentUserLanguage" size="small" type="info" style="margin-left: 4px;">
                      {{ getLanguageLabel(currentUserLanguage) }}
                    </el-tag>
                  </el-button>
                </template>
                <div class="quick-replies">
                  <div class="quick-reply-hint" v-if="currentUserLanguage">
                    <el-icon><InfoFilled /></el-icon>
                    <span>用户语言: {{ getLanguageLabel(currentUserLanguage) }}</span>
                  </div>
                  <div
                    v-for="reply in quickReplies"
                    :key="reply.id"
                    class="quick-reply-item"
                    @click="insertQuickReply(reply.content)"
                  >
                    {{ reply.title }}
                  </div>
                  <el-empty v-if="quickReplies.length === 0" description="暂无快捷回复" :image-size="60" />
                </div>
              </el-popover>
              <el-upload
                action="/admin/attachments/upload"
                :headers="uploadHeaders"
                :data="{ type: 'chat' }"
                :show-file-list="false"
                :on-success="handleUploadSuccess"
                :on-error="handleUploadError"
                :before-upload="beforeUpload"
                accept="image/*"
              >
                <el-button text>
                  <el-icon><Picture /></el-icon>
                  发送图片
                </el-button>
              </el-upload>
            </div>
            <div class="input-row">
              <el-input
                v-model="inputText"
                type="textarea"
                :rows="3"
                placeholder="输入消息内容..."
                resize="none"
                @keydown.enter.ctrl="handleSend"
              />
            </div>
            <div class="send-row">
              <span class="hint">Ctrl + Enter 发送</span>
              <el-button type="primary" @click="handleSend" :disabled="!inputText.trim()">
                发送
              </el-button>
            </div>
          </div>
        </template>

        <template v-else>
          <el-empty description="请选择一个会话" />
        </template>
      </el-card>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ChatDotRound, ArrowDown, InfoFilled, Picture, Service } from '@element-plus/icons-vue'
import type { UploadRawFile } from 'element-plus'
import {
  getConversationList,
  getUnreadStats,
  getConversationMessages,
  sendMessage,
  pollMessages as pollMessagesApi,
  markAsRead as markAsReadApi,
  closeConversation as closeConversationApi,
  claimConversation as claimConversationApi,
  transferConversation as transferConversationApi,
  type Conversation,
  type Message
} from '@/api/conversation'
import { getQuickReplyList } from '@/api/quickReply'
import { getOnlineServices } from '@/api/customerService'
import { playMessageSound, playSendSound, initAudio } from '@/utils/audio'

// 状态
const loading = ref(false)
const loadingMore = ref(false)
const conversations = ref<Conversation[]>([])
const currentConversation = ref<Conversation | null>(null)
const messages = ref<Message[]>([])
const inputText = ref('')
const quickReplies = ref<any[]>([])
const messageListRef = ref<HTMLElement | null>(null)

// 上传请求头
const uploadHeaders = {
  Authorization: `Bearer ${localStorage.getItem('admin_token') || ''}`
}

// 统计数据
const unreadStats = ref({ total: 0, conversations: 0 })
const presaleCount = ref(0)
const aftersaleCount = ref(0)

// 在线客服列表（用于转接）
const onlineServices = ref<any[]>([])

// 当前会话用户的语言
const currentUserLanguage = computed(() => {
  return (currentConversation.value as any)?.userLanguage || ''
})

// 获取语言标签
function getLanguageLabel(lang: string): string {
  const labels: Record<string, string> = {
    'zh-CN': '简体中文',
    'zh-TW': '繁体中文',
    'en-US': 'English',
    'ja-JP': '日本語',
  }
  return labels[lang] || lang
}

// 搜索和分页
const searchForm = ref({
  scene: 0,
  status: '',
  assign: '',
})
const pagination = ref({
  page: 1,
  pageSize: 20,
  total: 0,
})

// 轮询定时器
let pollTimer: number | null = null
let unreadTimer: number | null = null

// 加载会话列表
async function loadConversations() {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.page,
      pageSize: pagination.value.pageSize,
    }
    if (searchForm.value.scene > 0) {
      params.scene = searchForm.value.scene
    }
    if (searchForm.value.status) {
      params.status = searchForm.value.status
    }
    if (searchForm.value.assign) {
      params.assign = searchForm.value.assign
    }

    const res = await getConversationList(params) as any
    if (res.code === 0) {
      conversations.value = res.data.list
      pagination.value.total = res.data.total

      // 统计
      presaleCount.value = conversations.value.filter(c => c.scene === 1).length
      aftersaleCount.value = conversations.value.filter(c => c.scene === 2).length
    }
  } catch (e) {
    console.error('Failed to load conversations:', e)
  } finally {
    loading.value = false
  }
}

// 加载未读统计
async function loadUnreadStats() {
  try {
    const res = await getUnreadStats() as any
    if (res.code === 0) {
      unreadStats.value = res.data
    }
  } catch (e) {
    console.error('Failed to load unread stats:', e)
  }
}

// 选择会话
async function selectConversation(conv: Conversation) {
  currentConversation.value = conv
  messages.value = []
  await loadMessages()
  await markAsRead()
  startPolling()

  // 根据用户语言重新加载快捷回复
  // loadMessages() 会更新 currentConversation，所以在这之后获取 userLocale
  // 确保客服发送的快捷回复是用户能看懂的语言
  const userLocale = (currentConversation.value as any)?.userLocale
  console.log('User locale for quick replies:', userLocale, 'User language:', (currentConversation.value as any)?.userLanguage)
  if (userLocale) {
    loadQuickReplies(userLocale)
  }
}

// 加载消息
async function loadMessages(lastId?: number) {
  if (!currentConversation.value) return

  try {
    const params: any = { pageSize: 50 }
    if (lastId) {
      params.lastId = lastId
      loadingMore.value = true
    }

    const res = await getConversationMessages(currentConversation.value.id, params) as any
    if (res.code === 0) {
      const newMessages = res.data.list.reverse()
      if (lastId) {
        messages.value = [...newMessages, ...messages.value]
      } else {
        messages.value = newMessages
        // 更新会话信息
        if (res.data.conversation) {
          currentConversation.value = res.data.conversation
        }
        // 滚动到底部
        nextTick(() => scrollToBottom())
      }
    }
  } catch (e) {
    console.error('Failed to load messages:', e)
  } finally {
    loadingMore.value = false
  }
}

// 发送消息
async function handleSend() {
  const content = inputText.value.trim()
  if (!content || !currentConversation.value) return

  inputText.value = ''

  try {
    const res = await sendMessage(currentConversation.value.id, {
      content,
      type: 1,
    }) as any
    if (res.code === 0) {
      messages.value.push(res.data)
      nextTick(() => scrollToBottom())
      // 播放发送成功提示音
      playSendSound()
    }
  } catch (e) {
    console.error('Failed to send message:', e)
    ElMessage.error('发送失败')
    inputText.value = content
  }
}

// 轮询新消息
async function pollMessages() {
  if (!currentConversation.value || messages.value.length === 0) return

  try {
    const lastMsgId = messages.value[messages.value.length - 1].id
    const res = await pollMessagesApi(currentConversation.value.id, lastMsgId) as any

    if (res.code === 0 && res.data.hasNew) {
      const existingIds = new Set(messages.value.map(m => m.id))
      const newMessages = res.data.messages.filter((m: Message) => !existingIds.has(m.id))

      if (newMessages.length > 0) {
        // 检查是否有用户发来的新消息
        const hasUserMessages = newMessages.some((m: Message) => m.senderType === 'user')

        messages.value.push(...newMessages)
        nextTick(() => scrollToBottom())

        // 播放提示音
        if (hasUserMessages) {
          playNotificationSound()
        }

        // 刷新未读统计
        loadUnreadStats()
      }
    }
  } catch (e) {
    console.error('Failed to poll messages:', e)
  }
}

// 标记已读
async function markAsRead() {
  if (!currentConversation.value) return

  try {
    await markAsReadApi(currentConversation.value.id)
    // 更新本地未读数
    if (currentConversation.value) {
      currentConversation.value.unreadCount = 0
    }
    // 刷新列表
    loadUnreadStats()
  } catch (e) {
    console.error('Failed to mark as read:', e)
  }
}

// 关闭会话
async function closeConversationHandler() {
  if (!currentConversation.value) return

  try {
    await ElMessageBox.confirm('确定要关闭此会话吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    const res = await closeConversationApi(currentConversation.value.id) as any
    if (res.code === 0) {
      ElMessage.success('会话已关闭')
      currentConversation.value.isClosed = true
      loadConversations()
    }
  } catch (e: any) {
    if (e !== 'cancel') {
      console.error('Failed to close conversation:', e)
      ElMessage.error('关闭失败')
    }
  }
}

// 接入会话
async function claimConversationHandler() {
  if (!currentConversation.value) return

  try {
    const res = await claimConversationApi(currentConversation.value.id) as any
    if (res.code === 0) {
      ElMessage.success('会话接入成功')
      currentConversation.value = res.data.conversation
      loadConversations()
    } else {
      ElMessage.error(res.message || '接入失败')
    }
  } catch (e: any) {
    console.error('Failed to claim conversation:', e)
    ElMessage.error('接入失败')
  }
}

// 加载在线客服列表
async function loadOnlineServices() {
  try {
    const res = await getOnlineServices() as any
    if (res.code === 0) {
      onlineServices.value = (res.data || []).map((s: any) => ({
        id: s.id,
        name: s.name,
        currentSessions: s.current_sessions,
        maxSessions: s.max_sessions,
      }))
    }
  } catch (e) {
    console.error('Failed to load online services:', e)
  }
}

// 转接会话
async function transferTo(serviceId: number) {
  if (!currentConversation.value) return

  try {
    await ElMessageBox.confirm('确定要将此会话转接给该客服吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    const res = await transferConversationApi(currentConversation.value.id, serviceId) as any
    if (res.code === 0) {
      ElMessage.success('会话转接成功')
      currentConversation.value = res.data.conversation
      loadConversations()
      loadOnlineServices()
    } else {
      ElMessage.error(res.message || '转接失败')
    }
  } catch (e: any) {
    if (e !== 'cancel') {
      console.error('Failed to transfer conversation:', e)
      ElMessage.error('转接失败')
    }
  }
}

// 加载快捷回复（根据用户语言）
async function loadQuickReplies(locale?: string) {
  try {
    const params: Record<string, any> = { is_enabled: 1 }
    // 如果指定了用户语言，传递给后端获取对应翻译
    if (locale) {
      params.locale = locale
    }
    const res = await getQuickReplyList(params) as any
    if (res.code === 0) {
      quickReplies.value = res.data.list || res.data
    }
  } catch (e) {
    console.error('Failed to load quick replies:', e)
  }
}

// 插入快捷回复
function insertQuickReply(content: string) {
  inputText.value = content
}

// 上传前验证
function beforeUpload(file: UploadRawFile) {
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上传图片文件')
    return false
  }
  const isLt5M = file.size / 1024 / 1024 < 5
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB')
    return false
  }
  if (!currentConversation.value) {
    ElMessage.error('请先选择会话')
    return false
  }
  return true
}

// 上传成功
async function handleUploadSuccess(response: any) {
  if (response.code === 0 && response.data?.url) {
    // 发送图片消息
    if (!currentConversation.value) return

    try {
      const res = await sendMessage(currentConversation.value.id, {
        content: response.data.url,
        type: 2, // 图片消息类型
      }) as any
      if (res.code === 0) {
        messages.value.push(res.data)
        nextTick(() => scrollToBottom())
      }
    } catch (e) {
      console.error('Failed to send image:', e)
      ElMessage.error('发送图片失败')
    }
  } else {
    ElMessage.error(response.msg || '上传失败')
  }
}

// 上传失败
function handleUploadError() {
  ElMessage.error('上传失败')
}

// 播放提示音
function playNotificationSound() {
  playMessageSound()
}

// 滚动到底部
function scrollToBottom() {
  if (messageListRef.value) {
    messageListRef.value.scrollTop = messageListRef.value.scrollHeight
  }
}

// 处理滚动加载更多
function handleScroll() {
  if (!messageListRef.value || loadingMore.value) return

  if (messageListRef.value.scrollTop === 0 && messages.value.length > 0) {
    const firstMsgId = messages.value[0].id
    loadMessages(firstMsgId)
  }
}

// 开始轮询
function startPolling() {
  stopPolling()
  pollTimer = window.setInterval(pollMessages, 3000)
}

// 停止轮询
function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

// 搜索
function handleSearch() {
  pagination.value.page = 1
  loadConversations()
}

// 获取头像首字母
function getAvatarInitial(name?: string): string {
  if (!name) return '?'
  return name.charAt(0).toUpperCase()
}

// 格式化时间
function formatTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr.replace(' ', 'T'))
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()

  if (isToday) {
    return date.toLocaleTimeString('zh-CN', { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}

function formatMessageTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr.replace(' ', 'T'))
  return date.toLocaleString('zh-CN', {
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// 会话列表刷新定时器
let conversationTimer: number | null = null

// 生命周期
onMounted(() => {
  loadConversations()
  loadUnreadStats()
  loadQuickReplies()
  loadOnlineServices()

  // 初始化音频（用户进入页面即视为交互）
  initAudio()

  // 定时刷新未读统计
  unreadTimer = window.setInterval(loadUnreadStats, 10000)

  // 定时刷新会话列表（每10秒刷新一次）
  conversationTimer = window.setInterval(loadConversations, 10000)
})

onUnmounted(() => {
  stopPolling()
  if (unreadTimer) {
    clearInterval(unreadTimer)
  }
  if (conversationTimer) {
    clearInterval(conversationTimer)
  }
})
</script>

<style scoped>
.conversation-list {
  padding: 20px;
}

.stat-cards {
  margin-bottom: 20px;
}

.stat-card {
  text-align: center;
  padding: 20px;
}

.stat-card .stat-value {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 8px;
}

.stat-card .stat-label {
  color: #909399;
  font-size: 14px;
}

.stat-card.unread .stat-value { color: #f56c6c; }
.stat-card.conversations .stat-value { color: #e6a23c; }
.stat-card.presale .stat-value { color: #67c23a; }
.stat-card.aftersale .stat-value { color: #409eff; }

.main-content {
  display: flex;
  gap: 20px;
  height: calc(100vh - 280px);
  min-height: 500px;
}

.conversation-sidebar {
  width: 360px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
}

.conversation-sidebar :deep(.el-card__body) {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 0;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-row {
  padding: 10px 15px;
  border-bottom: 1px solid #ebeef5;
}

.conversation-items {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 15px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
}

.conversation-item:hover {
  background-color: #f5f7fa;
}

.conversation-item.active {
  background-color: #ecf5ff;
}

.conv-info {
  flex: 1;
  min-width: 0;
}

.conv-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.conv-header .user-name {
  font-weight: 500;
  font-size: 14px;
}

.conv-header .time {
  font-size: 12px;
  color: #909399;
}

.conv-preview {
  display: flex;
  align-items: center;
  gap: 6px;
}

.conv-preview .scene-tag {
  flex-shrink: 0;
}

.conv-preview .assign-tag {
  flex-shrink: 0;
}

.conv-preview .message {
  font-size: 13px;
  color: #909399;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.unread-badge {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
}

.pagination-wrapper {
  padding: 10px;
  border-top: 1px solid #ebeef5;
  display: flex;
  justify-content: center;
}

.chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-area :deep(.el-card__body) {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 0;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #ebeef5;
}

.chat-header .user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-header .user-info .info .name {
  font-weight: 500;
  font-size: 15px;
}

.chat-header .user-info .info .scene {
  margin-top: 4px;
}

.goods-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background-color: #fafafa;
  border-bottom: 1px solid #ebeef5;
}

.goods-card .goods-image {
  width: 60px;
  height: 60px;
  border-radius: 4px;
}

.goods-card .goods-info .title {
  font-size: 14px;
  margin-bottom: 4px;
}

.goods-card .goods-info .price {
  font-size: 16px;
  font-weight: 600;
  color: #f56c6c;
}

.message-list {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background-color: #f5f5f5;
}

.loading-more {
  text-align: center;
  padding: 10px;
  color: #909399;
}

.message-item {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.message-item.is-self {
  flex-direction: row-reverse;
}

.message-item.is-self .message-content {
  align-items: flex-end;
}

.message-item.is-self .message-content .message-bubble {
  background-color: #409eff;
  color: #fff;
}

.message-content {
  display: flex;
  flex-direction: column;
  max-width: 60%;
}

.message-bubble {
  background-color: #fff;
  padding: 10px 14px;
  border-radius: 8px;
  word-wrap: break-word;
  line-height: 1.5;
}

.message-image {
  max-width: 200px;
  border-radius: 8px;
}

.message-goods {
  display: flex;
  gap: 8px;
  background-color: #fff;
  padding: 8px;
  border-radius: 8px;
}

.message-goods .goods-thumb {
  width: 50px;
  height: 50px;
  border-radius: 4px;
}

.message-goods .goods-detail .title {
  font-size: 13px;
  margin-bottom: 4px;
}

.message-goods .goods-detail .price {
  font-size: 14px;
  font-weight: 600;
  color: #f56c6c;
}

.message-system {
  background-color: rgba(0, 0, 0, 0.05);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  color: #909399;
}

.message-time {
  font-size: 11px;
  color: #909399;
  margin-top: 4px;
}

.input-area {
  border-top: 1px solid #ebeef5;
  background-color: #fff;
}

.input-area .toolbar {
  padding: 8px 16px;
  border-bottom: 1px solid #f0f0f0;
}

.input-area .input-row {
  padding: 12px 16px 0;
}

.input-area .send-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 16px 12px;
}

.input-area .send-row .hint {
  font-size: 12px;
  color: #909399;
}

.quick-replies {
  max-height: 200px;
  overflow-y: auto;
}

.quick-reply-hint {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  margin-bottom: 8px;
  font-size: 12px;
  color: #909399;
  background-color: #f4f4f5;
  border-radius: 4px;
}

.quick-reply-item {
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 4px;
}

.quick-reply-item:hover {
  background-color: #f5f7fa;
}

/* 默认头像首字母样式 */
.avatar-initial {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
}

.avatar-initial-sm {
  font-size: 14px;
}

:deep(.el-avatar) {
  background: #FF6B35;
}

:deep(.el-avatar[src]) {
  background: transparent;
}
</style>
