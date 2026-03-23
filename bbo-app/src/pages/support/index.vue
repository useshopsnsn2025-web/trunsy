<template>
  <view class="page">
    <NavBar :title="t('support.title')" />

    <scroll-view class="content" scroll-y>
      <!-- 顶部标题区域 -->
      <view class="header-section">
        <view class="header-content">
          <text class="header-title">{{ t('support.headerTitle') }}</text>
          <text class="header-subtitle">{{ t('support.headerSubtitle') }}</text>
        </view>
      </view>

      <!-- 问题分类 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('support.selectCategory') }}</text>
        </view>
        <view class="category-grid">
          <view
            v-for="category in categories"
            :key="category.code"
            class="category-item"
            @click="goCategory(category.code)"
          >
            <view class="category-icon" :class="category.code">
              <text class="bi" :class="category.icon"></text>
            </view>
            <text class="category-label">{{ category.name }}</text>
          </view>
        </view>
      </view>

      <!-- 热门问题 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('support.hotQuestions') }}</text>
        </view>
        <view class="section-card">
          <view
            v-for="(faq, index) in hotFaqs"
            :key="index"
            class="faq-item"
            @click="toggleFaq(index)"
          >
            <view class="faq-header">
              <text class="faq-question">{{ faq.question }}</text>
              <text
                class="bi faq-arrow"
                :class="expandedFaq === index ? 'bi-chevron-up' : 'bi-chevron-down'"
              ></text>
            </view>
            <view v-if="expandedFaq === index" class="faq-answer">
              <text>{{ faq.answer }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 我的工单 -->
      <view class="section">
        <view class="section-card ticket-card" @click="goTickets">
          <view class="ticket-icon">
            <text class="bi bi-ticket-detailed"></text>
          </view>
          <view class="ticket-content">
            <text class="ticket-title">{{ t('support.myTickets') }}</text>
            <text class="ticket-desc">{{ t('support.myTicketsDesc') }}</text>
          </view>
          <view v-if="ticketCount > 0" class="ticket-badge">{{ ticketCount }}</view>
          <text class="bi bi-chevron-right ticket-arrow"></text>
        </view>
      </view>

      <!-- 帮助中心入口 -->
      <view class="section">
        <view class="section-card help-card" @click="goGuide">
          <view class="help-icon">
            <text class="bi bi-book"></text>
          </view>
          <view class="help-content">
            <text class="help-title">{{ t('support.helpCenter') }}</text>
            <text class="help-desc">{{ t('support.helpCenterDesc') }}</text>
          </view>
          <text class="bi bi-chevron-right help-arrow"></text>
        </view>
      </view>

      <!-- 底部操作按钮 -->
      <view class="section">
        <view class="section-header">
          <text class="section-title">{{ t('support.cantFindAnswer') }}</text>
        </view>
        <view class="action-buttons">
          <view class="action-btn primary" @click="goCreateTicket">
            <text class="bi bi-pencil-square"></text>
            <text class="action-text">{{ t('support.createTicket') }}</text>
          </view>
          <view class="action-btn secondary" @click="goOnlineChat">
            <text class="bi bi-chat-dots"></text>
            <text class="action-text">{{ t('support.onlineChat') }}</text>
          </view>
        </view>
      </view>

      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'
import { getTicketList } from '@/api/ticket'

const { t } = useI18n()

// 展开的 FAQ 索引
const expandedFaq = ref<number | null>(null)

// 工单数量
const ticketCount = ref(0)

// 问题分类
const categories = computed(() => [
  {
    code: 'order',
    name: t('support.order'),
    icon: 'bi-box-seam',
  },
  {
    code: 'refund',
    name: t('support.refund'),
    icon: 'bi-arrow-return-left',
  },
  {
    code: 'payment',
    name: t('support.payment'),
    icon: 'bi-credit-card',
  },
  {
    code: 'account',
    name: t('support.account'),
    icon: 'bi-person-circle',
  },
  {
    code: 'report',
    name: t('support.report'),
    icon: 'bi-flag',
  },
  {
    code: 'other',
    name: t('support.other'),
    icon: 'bi-question-circle',
  },
])

// 热门问题
const hotFaqs = computed(() => [
  {
    question: t('support.faq1Question'),
    answer: t('support.faq1Answer'),
  },
  {
    question: t('support.faq2Question'),
    answer: t('support.faq2Answer'),
  },
  {
    question: t('support.faq3Question'),
    answer: t('support.faq3Answer'),
  },
  {
    question: t('support.faq4Question'),
    answer: t('support.faq4Answer'),
  },
])

// 设置导航标题
onShow(() => {
  uni.setNavigationBarTitle({ title: t('support.title') })
  loadTicketCount()
})

// 加载工单数量
async function loadTicketCount() {
  try {
    const res = await getTicketList({ page: 1, pageSize: 1 })
    if (res.code === 0) {
      ticketCount.value = res.data.total || 0
    }
  } catch (e) {
    // 忽略错误
  }
}

// 切换 FAQ 展开状态
function toggleFaq(index: number) {
  expandedFaq.value = expandedFaq.value === index ? null : index
}

// 跳转分类页
function goCategory(categoryCode: string) {
  uni.navigateTo({ url: `/pages/support/category?type=${categoryCode}` })
}

// 跳转工单列表
function goTickets() {
  uni.navigateTo({ url: '/pages/ticket/index' })
}

// 跳转帮助中心
function goGuide() {
  uni.navigateTo({ url: '/pages/guide/index' })
}

// 跳转创建工单
function goCreateTicket() {
  uni.navigateTo({ url: '/pages/support/create' })
}

// 跳转在线客服
function goOnlineChat() {
  // 跳转到客服聊天页面
  uni.navigateTo({ url: '/pages/chat/index?type=2&targetId=0' })
}
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #FF6B35;
$color-primary-light: #FFF5F2;
$color-text: #191919;
$color-text-secondary: #707070;
$color-text-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;

.page {
  min-height: 100vh;
  background-color: $color-background;
}

.content {
  flex: 1;
  width: auto;
}

// 顶部标题区域
.header-section {
  background: linear-gradient(135deg, $color-primary 0%, darken($color-primary, 10%) 100%);
  padding: 32px 16px;
  margin-bottom: 16px;
}

.header-content {
  text-align: center;
}

.header-title {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #FFFFFF;
  margin-bottom: 8px;
}

.header-subtitle {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
}

// 区块
.section {
  padding: 0 16px;
  margin-bottom: 20px;
}

.section-header {
  padding: 8px 4px 12px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-text;
}

.section-card {
  background-color: $color-surface;
  border-radius: 12px;
  overflow: hidden;
}

// 问题分类网格
.category-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  background-color: $color-surface;
  border-radius: 12px;
  padding: 16px;
}

.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px 8px;
  border-radius: 12px;
  cursor: pointer;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.03);
  }
}

.category-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 26px;
    color: #FFFFFF;
  }

  &.order {
    background: linear-gradient(135deg, #4A90D9 0%, #357ABD 100%);
  }

  &.refund {
    background: linear-gradient(135deg, #52C41A 0%, #389E0D 100%);
  }

  &.payment {
    background: linear-gradient(135deg, #F5A623 0%, #E09915 100%);
  }

  &.account {
    background: linear-gradient(135deg, #9B59B6 0%, #8E44AD 100%);
  }

  &.report {
    background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
  }

  &.other {
    background: linear-gradient(135deg, #95A5A6 0%, #7F8C8D 100%);
  }
}

.category-label {
  font-size: 13px;
  color: $color-text-secondary;
  text-align: center;
}

// FAQ
.faq-item {
  padding: 16px;
  border-bottom: 1px solid $color-border;
  cursor: pointer;

  &:last-child {
    border-bottom: none;
  }
}

.faq-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.faq-question {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  line-height: 1.4;
}

.faq-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
  margin-top: 2px;
}

.faq-answer {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed $color-border;

  text {
    font-size: 14px;
    color: $color-text-secondary;
    line-height: 1.6;
  }
}

// 工单卡片
.ticket-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  cursor: pointer;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.ticket-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: $color-primary-light;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }
}

.ticket-content {
  flex: 1;
  min-width: 0;
}

.ticket-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.ticket-desc {
  font-size: 13px;
  color: $color-text-muted;
}

.ticket-badge {
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background-color: $color-primary;
  color: #FFFFFF;
  font-size: 12px;
  font-weight: 600;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.ticket-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// 帮助中心卡片
.help-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  cursor: pointer;
  transition: background-color 0.2s;

  &:active {
    background-color: rgba(0, 0, 0, 0.02);
  }
}

.help-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: #E8F4FD;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 20px;
    color: #4A90D9;
  }
}

.help-content {
  flex: 1;
  min-width: 0;
}

.help-title {
  display: block;
  font-size: 15px;
  font-weight: 500;
  color: $color-text;
  margin-bottom: 2px;
}

.help-desc {
  font-size: 13px;
  color: $color-text-muted;
}

.help-arrow {
  font-size: 14px;
  color: $color-text-muted;
  flex-shrink: 0;
}

// 底部操作按钮
.action-buttons {
  display: flex;
  gap: 12px;
}

.action-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 16px;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s;

  &:active {
    opacity: 0.8;
  }

  .bi {
    font-size: 24px;
  }

  &.primary {
    background-color: $color-primary;
    color: #FFFFFF;
  }

  &.secondary {
    background-color: $color-surface;
    color: $color-text;
    border: 1px solid $color-border;

    .bi {
      color: $color-primary;
    }
  }
}

.action-text {
  font-size: 14px;
  font-weight: 500;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
