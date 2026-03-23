<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('ticket.ticketDetail')" />

    <!-- 加载状态 -->
    <view v-if="loading" class="loading-state">
      <text>{{ t('common.loading') }}</text>
    </view>

    <template v-else-if="ticket">
      <!-- 可滚动内容区域 -->
      <scroll-view class="scroll-content" scroll-y>
        <view class="scroll-inner">
          <!-- 工单信息 -->
          <view class="ticket-info">
          <view class="info-header">
            <view class="ticket-status" :class="getStatusClass(ticket.status)">
              {{ ticket.statusText }}
            </view>
            <text class="ticket-no">{{ ticket.ticketNo }}</text>
          </view>
          <text class="ticket-subject">{{ ticket.subject }}</text>
          <!-- 举报工单显示结构化内容 -->
          <view v-if="isReportTicket && parsedReportContent" class="report-content">
            <view v-for="(value, key) in parsedReportContent" :key="key" class="report-item">
              <text class="report-label">{{ getReportFieldLabel(key) }}:</text>
              <text class="report-value">{{ getReportFieldValue(key, value) }}</text>
            </view>
          </view>
          <!-- 普通工单显示原始内容 -->
          <text v-else class="ticket-content">{{ ticket.content }}</text>
          <view v-if="ticket.images && ticket.images.length > 0" class="ticket-images">
            <image
              v-for="(img, idx) in ticket.images"
              :key="idx"
              class="ticket-image"
              :src="img"
              mode="aspectFill"
              @click="previewImage(img, ticket.images)"
            />
          </view>
          <view class="ticket-meta">
            <text class="meta-item">{{ ticket.categoryText }}</text>
            <text class="meta-item">{{ formatTime(ticket.createdAt) }}</text>
          </view>
        </view>

        <!-- 回复列表 -->
        <view class="replies-section">
          <text class="section-title">{{ t('ticket.replies') }}</text>

          <view v-if="!ticket.replies || ticket.replies.length === 0" class="no-replies">
            <text>{{ t('ticket.noReplies') }}</text>
          </view>

          <view v-else class="reply-list">
            <view
              v-for="reply in ticket.replies"
              :key="reply.id"
              class="reply-item"
              :class="{ 'is-user': reply.fromType === REPLY_FROM_TYPE.USER }"
            >
              <view class="reply-header">
                <text class="reply-from">
                  {{ reply.fromType === REPLY_FROM_TYPE.USER ? t('ticket.you') : t('ticket.service') }}
                </text>
                <text class="reply-time">{{ formatTime(reply.createdAt) }}</text>
              </view>
              <text class="reply-content">{{ getReplyContent(reply) }}</text>
              <view v-if="reply.images && reply.images.length > 0" class="reply-images">
                <image
                  v-for="(img, idx) in reply.images"
                  :key="idx"
                  class="reply-image"
                  :src="img"
                  mode="aspectFill"
                  @click="previewImage(img, reply.images)"
                />
              </view>
            </view>
          </view>
        </view>
        </view>
      </scroll-view>

      <!-- 回复输入框 -->
      <view v-if="ticket.status !== TICKET_STATUS.CLOSED" class="reply-input-area">
        <view class="input-row">
          <input
            v-model="replyContent"
            class="reply-input"
            :placeholder="t('ticket.replyPlaceholder')"
            :adjust-position="false"
            :cursor-spacing="10"
          />
          <button class="send-btn" :disabled="!replyContent.trim() || sending" @click="handleReply">
            {{ t('chat.send') }}
          </button>
        </view>
      </view>

      <!-- 已关闭提示 -->
      <view v-else class="closed-tip">
        <text>{{ t('ticket.ticketClosed') }}</text>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'
import { useAppStore } from '@/store/modules/app'
import { useToast } from '@/composables/useToast'
import { getTicketDetail, replyTicket, TICKET_STATUS, REPLY_FROM_TYPE, type Ticket } from '@/api/ticket'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

const ticketId = ref(0)
const ticket = ref<Ticket | null>(null)
const loading = ref(false)
const replyContent = ref('')
const sending = ref(false)

// 是否为举报工单
const isReportTicket = computed(() => {
  return ticket.value?.category === 'report'
})

// 解析举报工单的内容
const parsedReportContent = computed(() => {
  if (!ticket.value || !isReportTicket.value) return null

  const content = ticket.value.content || ''
  const lines = content.split('\n').filter(line => line.trim())
  const result: Record<string, string> = {}

  for (const line of lines) {
    const colonIndex = line.indexOf(':')
    if (colonIndex > 0) {
      const key = line.substring(0, colonIndex).trim()
      const value = line.substring(colonIndex + 1).trim()
      if (key && value) {
        result[key] = value
      }
    }
  }

  return Object.keys(result).length > 0 ? result : null
})

// 获取举报字段标签的翻译
function getReportFieldLabel(key: string): string {
  const labelMap: Record<string, string> = {
    'report_type': t('ticket.reportType'),
    'member_name': t('ticket.memberName'),
    'item_number': t('ticket.itemNumber'),
    'other_items': t('ticket.otherItems'),
    'details': t('ticket.details'),
    'reason': t('ticket.reason'),
  }
  return labelMap[key] || key
}

// 获取举报字段值的翻译
function getReportFieldValue(key: string, value: string): string {
  // 举报类型翻译
  if (key === 'report_type') {
    const typeMap: Record<string, string> = {
      'member': t('ticket.reportTypeMember'),
      'item': t('ticket.reportTypeItem'),
    }
    return typeMap[value] || value
  }

  // 举报原因翻译
  if (key === 'reason' || key === 'details') {
    const reasonMap: Record<string, string> = {
      'counterfeit': t('ticket.reasonCounterfeit'),
      'prohibited': t('ticket.reasonProhibited'),
      'misleading': t('ticket.reasonMisleading'),
      'infringement': t('ticket.reasonInfringement'),
      'fraud': t('ticket.reasonFraud'),
      'spam': t('ticket.reasonSpam'),
      'deception': t('ticket.reasonFraud'),
      'other': t('ticket.reasonOther'),
    }
    return reasonMap[value] || value
  }

  return value
}

// 获取回复内容（系统消息需要翻译）
function getReplyContent(reply: { content: string; fromType: number }): string {
  // 系统消息翻译映射
  const systemMessageMap: Record<string, string> = {
    '工单已关闭': t('ticket.ticketClosed'),
    '工单已解决': t('ticket.ticketResolved'),
    'Ticket closed': t('ticket.ticketClosed'),
    'Ticket resolved': t('ticket.ticketResolved'),
  }

  // 如果是系统消息类型（fromType === 3），尝试翻译
  if (reply.fromType === REPLY_FROM_TYPE.SYSTEM) {
    return systemMessageMap[reply.content] || reply.content
  }

  return reply.content
}

function formatTime(dateStr: string) {
  if (!dateStr) return ''
  return appStore.formatRelativeTime(dateStr)
}

function getStatusClass(status: number) {
  switch (status) {
    case TICKET_STATUS.PENDING:
      return 'status-pending'
    case TICKET_STATUS.PROCESSING:
      return 'status-processing'
    case TICKET_STATUS.REPLIED:
      return 'status-replied'
    case TICKET_STATUS.RESOLVED:
      return 'status-resolved'
    case TICKET_STATUS.CLOSED:
      return 'status-closed'
    default:
      return ''
  }
}

function previewImage(current: string, urls: string[]) {
  uni.previewImage({ current, urls })
}

async function loadTicket() {
  if (!ticketId.value) return

  loading.value = true
  try {
    const res = await getTicketDetail(ticketId.value)
    if (res.code === 0) {
      ticket.value = res.data
    }
  } catch (e) {
    console.error('Failed to load ticket:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

async function handleReply() {
  if (!replyContent.value.trim() || sending.value || !ticket.value) return

  sending.value = true
  try {
    const res = await replyTicket(ticketId.value, { content: replyContent.value.trim() })
    if (res.code === 0) {
      // 添加到回复列表
      if (!ticket.value.replies) {
        ticket.value.replies = []
      }
      ticket.value.replies.push(res.data)
      replyContent.value = ''
      toast.success(t('ticket.replySent'))
    }
  } catch (e) {
    console.error('Failed to send reply:', e)
    toast.error(t('common.operationFailed'))
  } finally {
    sending.value = false
  }
}

onLoad((options) => {
  if (options?.id) {
    ticketId.value = parseInt(options.id)
    loadTicket()
  }
})
</script>

<style lang="scss" scoped>
.page {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
  overflow: hidden;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 60vh;
  color: #999;
}

.scroll-content {
  flex: 1;
  min-height: 0;
}

.scroll-inner {
  display: flex;
  flex-direction: column;
  min-height: 100%;
}

.ticket-info {
  background-color: #fff;
  padding: 16px;
  margin-bottom: 12px;
}

.info-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.ticket-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
  margin-right: 8px;

  &.status-pending {
    background-color: #fff7e6;
    color: #fa8c16;
  }

  &.status-processing {
    background-color: #e6f7ff;
    color: #1890ff;
  }

  &.status-replied {
    background-color: #f6ffed;
    color: #52c41a;
  }

  &.status-resolved {
    background-color: #f9f9f9;
    color: #666;
  }

  &.status-closed {
    background-color: #f5f5f5;
    color: #999;
  }
}

.ticket-no {
  font-size: 12px;
  color: #999;
}

.ticket-subject {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  display: block;
  margin-bottom: 8px;
}

.ticket-content {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
  display: block;
}

.report-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.report-item {
  display: flex;
  flex-direction: row;
  gap: 8px;
}

.report-label {
  font-size: 14px;
  color: #999;
  flex-shrink: 0;
}

.report-value {
  font-size: 14px;
  color: #333;
  flex: 1;
}

.ticket-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.ticket-image {
  width: 80px;
  height: 80px;
  border-radius: 8px;
}

.ticket-meta {
  display: flex;
  gap: 12px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f5f5f5;
}

.meta-item {
  font-size: 12px;
  color: #999;
}

.replies-section {
  flex: 1;
  background-color: #fff;
  padding: 16px;
}

.section-title {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  display: block;
  margin-bottom: 16px;
}

.no-replies {
  text-align: center;
  padding: 32px 0;
  color: #999;
  font-size: 14px;
  
}

.reply-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.reply-item {
  background-color: #f5f5f5;
  border-radius: 12px;
  padding: 12px;

  &.is-user {
    background-color: #fff7e6;
  }
}

.reply-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.reply-from {
  font-size: 13px;
  font-weight: 500;
  color: #333;
}

.reply-time {
  font-size: 12px;
  color: #999;
}

.reply-content {
  font-size: 14px;
  color: #666;
  line-height: 1.5;
}

.reply-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.reply-image {
  width: 60px;
  height: 60px;
  border-radius: 6px;
}

.reply-input-area {
  flex-shrink: 0;
  background-color: #fff;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  border-top: 1px solid #eee;
}

.input-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reply-input {
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
  }
}

.closed-tip {
  flex-shrink: 0;
  background-color: #f5f5f5;
  padding: 16px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  text-align: center;
  color: #999;
  font-size: 14px;
}
</style>
