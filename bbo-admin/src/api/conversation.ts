import request from './request'

export interface ServiceInfo {
  id: number
  name: string
  avatar: string
}

export interface Conversation {
  id: number
  type: number
  scene: number
  sceneText: string
  userId: number
  userName: string
  userAvatar: string
  lastMessage: string
  lastMessageTime: string
  unreadCount: number
  goodsId: number | null
  goodsInfo: {
    id: number
    title: string
    price: number
    image: string
  } | null
  orderId: number | null
  isClosed: boolean
  isAssigned: boolean
  serviceInfo: ServiceInfo | null
  createdAt: string
}

export interface Message {
  id: number
  conversationId: number
  senderId: number
  senderType: string
  content: string
  type: number
  extra: Record<string, any> | null
  isRead: boolean
  createdAt: string
}

export interface UnreadStats {
  total: number
  conversations: number
}

export interface ConversationListParams {
  page?: number
  pageSize?: number
  scene?: number
  status?: string
  assign?: 'mine' | 'unassigned' | 'all'
}

export interface MessagesResponse {
  list: Message[]
  conversation: Conversation
}

export interface PollResponse {
  hasNew: boolean
  messages: Message[]
}

// 获取会话列表
export function getConversationList(params: ConversationListParams) {
  return request.get<{ list: Conversation[]; total: number }>('/conversations', { params })
}

// 获取未读统计
export function getUnreadStats() {
  return request.get<UnreadStats>('/conversations/unread')
}

// 获取会话消息
export function getConversationMessages(id: number, params?: { lastId?: number; pageSize?: number }) {
  return request.get<MessagesResponse>(`/conversations/${id}`, { params })
}

// 发送消息
export function sendMessage(conversationId: number, data: { content: string; type: number }) {
  return request.post<Message>(`/conversations/${conversationId}/send`, data)
}

// 轮询新消息
export function pollMessages(conversationId: number, lastMsgId: number) {
  return request.get<PollResponse>(`/conversations/${conversationId}/poll`, {
    params: { lastMsgId }
  })
}

// 标记已读
export function markAsRead(conversationId: number) {
  return request.post(`/conversations/${conversationId}/read`)
}

// 关闭会话
export function closeConversation(conversationId: number) {
  return request.post(`/conversations/${conversationId}/close`)
}

// 接入会话
export function claimConversation(conversationId: number) {
  return request.post<{ conversation: Conversation }>(`/conversations/${conversationId}/claim`)
}

// 转接会话
export function transferConversation(conversationId: number, serviceId: number) {
  return request.post<{ conversation: Conversation }>(`/conversations/${conversationId}/transfer`, { serviceId })
}
