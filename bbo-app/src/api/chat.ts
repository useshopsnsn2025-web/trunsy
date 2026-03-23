import { get, post, del } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 会话类型
export const CONVERSATION_TYPE = {
  PRIVATE: 1,   // 私聊（用户与用户）
  SERVICE: 2,   // 客服（用户与平台客服）
} as const

// 消息类型
export const MESSAGE_TYPE = {
  TEXT: 1,      // 文本
  IMAGE: 2,     // 图片
  GOODS: 3,     // 商品卡片
  ORDER: 4,     // 订单卡片
  SYSTEM: 5,    // 系统消息
  RECALLED: 6,  // 已撤回
} as const

// 会话信息
export interface Conversation {
  id: number
  type: number
  targetId: number
  targetType: string
  targetName: string
  targetAvatar?: string
  lastMessage?: string
  lastMessageTime?: string
  unreadCount: number
  goodsId?: number
  goodsInfo?: {
    id: number
    title: string
    price: number
    image: string
  }
  isPinned?: boolean
}

// 引用消息摘要
export interface QuotedMessage {
  id: number
  senderId: number
  senderType: string
  content: string
  type: number
}

// 消息
export interface Message {
  id: number
  conversationId: number
  senderId: number
  senderType: string
  content: string
  type: number
  extra?: Record<string, any>
  isRead: boolean
  createdAt: string
  quoteId?: number
  quotedMessage?: QuotedMessage
}

// 获取会话列表
export function getConversations() {
  return get<Conversation[]>(API_PATHS.chat.conversations)
}

// 获取或创建会话（联系卖家时调用）
export interface CreateConversationParams {
  type: number          // 会话类型：1=私聊, 2=客服
  targetId: number      // 目标ID（用户ID或客服ID，客服时传0由系统分配）
  goodsId?: number      // 商品ID（可选，用于关联商品）
}

export function getOrCreateConversation(params: CreateConversationParams) {
  return post<{ conversation: Conversation }>(API_PATHS.chat.conversations, params)
}

// 获取会话消息列表
export interface GetMessagesParams {
  page?: number
  pageSize?: number
  lastId?: number   // 最后一条消息ID，用于加载更多
}

export function getMessages(conversationId: number, params?: GetMessagesParams) {
  return get<{ list: Message[]; total: number }>(
    API_PATHS.chat.messages(String(conversationId)),
    params
  )
}

// 发送消息
export interface SendMessageParams {
  content: string
  type?: number       // 消息类型，默认为文本
  extra?: Record<string, any>  // 额外信息（如商品卡片数据）
  quoteId?: number    // 引用的消息ID
}

export function sendMessage(conversationId: number, params: SendMessageParams) {
  return post<Message>(
    API_PATHS.chat.send(String(conversationId)),
    params
  )
}

// 标记消息已读
export function markAsRead(conversationId: number) {
  return post(`/conversations/${conversationId}/read`)
}

// 轮询获取新消息
export function pollMessages(conversationId: number, lastMsgId: number) {
  return get<{ messages: Message[]; hasNew: boolean }>(
    `/conversations/${conversationId}/poll`,
    { lastMsgId }
  )
}

// 获取未读消息总数
export function getUnreadCount() {
  return get<{ total: number }>('/conversations/unread')
}

// 获取客服在线状态
export function getServiceStatus() {
  return get<{ online: boolean; waitCount: number }>('/service/status')
}

// 举报用户（创建工单）
export function reportUser(conversationId: number, reason?: string) {
  return post(`/conversations/${conversationId}/report`, { reason })
}

// 拉黑用户
export function blockUser(conversationId: number) {
  return post(`/conversations/${conversationId}/block`)
}

// 清空聊天记录
export function clearChatHistory(conversationId: number) {
  return post(`/conversations/${conversationId}/clear`)
}

// 撤回消息
export function recallMessage(conversationId: number, msgId: number) {
  return post<Message>(`/conversations/${conversationId}/messages/${msgId}/recall`)
}

// 删除消息
export function deleteMessage(conversationId: number, msgId: number) {
  return del(`/conversations/${conversationId}/messages/${msgId}`)
}

// 批量删除消息
export function deleteMessages(conversationId: number, msgIds: number[]) {
  return post(`/conversations/${conversationId}/messages/delete`, { msgIds })
}

// 置顶/取消置顶会话
export function pinConversation(conversationId: number) {
  return post<{ isPinned: boolean }>(`/conversations/${conversationId}/pin`)
}

// 删除会话
export function deleteConversation(conversationId: number) {
  return del(`/conversations/${conversationId}`)
}
