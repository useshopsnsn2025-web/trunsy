<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 空状态 -->
    <view v-if="!loading && tickets.length === 0" class="empty-state">
      <text class="bi bi-ticket empty-icon"></text>
      <text class="empty-text">{{ t('ticket.noTickets') }}</text>
    </view>

    <!-- 工单列表 -->
    <view v-if="!loading && tickets.length > 0" class="ticket-list">
      <view
        v-for="ticket in tickets"
        :key="ticket.id"
        class="ticket-item"
        @click="goDetail(ticket.id)"
      >
        <view class="ticket-header">
          <text class="ticket-no">{{ ticket.ticketNo }}</text>
          <view class="ticket-status" :class="getStatusClass(ticket.status)">
            {{ ticket.statusText }}
          </view>
        </view>
        <text class="ticket-subject">{{ ticket.subject }}</text>
        <view class="ticket-footer">
          <text class="ticket-category">{{ ticket.categoryText }}</text>
          <text class="ticket-time">{{ formatTime(ticket.createdAt) }}</text>
        </view>
      </view>

      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more" @click="loadMore">
        <text v-if="loading">{{ t('common.loading') }}</text>
        <text v-else>{{ t('common.loadMore') }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store/modules/app'
import { getTickets, TICKET_STATUS, type Ticket } from '@/api/ticket'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()
const appStore = useAppStore()

const tickets = ref<Ticket[]>([])
const loading = ref(false)
const page = ref(1)
const pageSize = 20
const total = ref(0)
const hasMore = ref(false)

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

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/ticket/detail?id=${id}` })
}

async function loadTickets(isRefresh = false) {
  if (loading.value) return

  if (isRefresh) {
    page.value = 1
    tickets.value = []
  }

  loading.value = true
  try {
    const res = await getTickets({ page: page.value, pageSize })
    if (res.code === 0) {
      if (isRefresh) {
        tickets.value = res.data.list || []
      } else {
        tickets.value = [...tickets.value, ...(res.data.list || [])]
      }
      total.value = res.data.total
      hasMore.value = tickets.value.length < total.value
    }
  } catch (e) {
    console.error('Failed to load tickets:', e)
  } finally {
    loading.value = false
  }
}

function loadMore() {
  if (loading.value || !hasMore.value) return
  page.value++
  loadTickets()
}

onLoad(() => {
  uni.setNavigationBarTitle({ title: t('ticket.myTickets') })
})

onShow(() => {
  loadTickets(true)
})
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
  color: #999;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 14px;
}

.ticket-list {
  padding: 16px;
}

.ticket-item {
  background-color: #fff;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
}

.ticket-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.ticket-no {
  font-size: 12px;
  color: #999;
}

.ticket-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;

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

.ticket-subject {
  font-size: 15px;
  font-weight: 500;
  color: #333;
  display: block;
  margin-bottom: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ticket-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ticket-category {
  font-size: 12px;
  color: #666;
  background-color: #f5f5f5;
  padding: 2px 8px;
  border-radius: 4px;
}

.ticket-time {
  font-size: 12px;
  color: #999;
}

.load-more {
  text-align: center;
  padding: 16px;
  color: #999;
  font-size: 14px;
}
</style>
