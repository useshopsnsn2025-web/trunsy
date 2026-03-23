// 分享裂变相关 API
import { get, post } from '@/utils/request'

// ==================== 类型定义 ====================

// 分享类型
export type ShareType = 'game' | 'goods' | 'activity'

// 分享渠道
export type ShareChannel = 'whatsapp' | 'facebook' | 'line' | 'twitter' | 'copy'

// 分享链接
export interface ShareLink {
  id: number
  code: string
  type: ShareType
  target_id: number | null
  channel: ShareChannel | null
  click_count: number
  register_count: number
  order_count: number
  expires_at: string
  created_at: string
}

// 分享统计
export interface ShareStats {
  share: {
    total_links: number
    total_clicks: number
    total_registers: number
    total_orders: number
  }
  invite: {
    total_invites: number
    completed_orders: number
    pending_orders: number
  }
}

// 邀请记录
export interface InviteRecord {
  id: number
  invitee_id: number
  invitee_nickname: string
  invitee_avatar: string
  status: number // 1-已注册 2-已首单
  register_reward_issued: boolean
  order_reward_issued: boolean
  first_order_at: string | null
  created_at: string
}

// 奖励配置
export interface RewardConfig {
  reward_type: 'points' | 'chances' | 'coupon'
  reward_value: number
  game_code?: string
}

export interface RewardConfigResponse {
  register: {
    inviter: RewardConfig[]
    invitee: RewardConfig[]
  }
  first_order: {
    inviter: RewardConfig[]
    invitee: RewardConfig[]
  }
  share_click: {
    inviter: RewardConfig[]
  }
}

// 分享话术模板
export interface ShareTemplates {
  [type: string]: {
    [locale: string]: string
  }
}

// ==================== API 方法 ====================

/**
 * 生成分享链接
 */
export function generateShareLink(params: {
  type?: ShareType
  target_id?: number
  channel?: ShareChannel
}) {
  return post<{
    share_link: ShareLink
    share_url: string
    share_code: string
  }>('/share/generate', params)
}

/**
 * 记录分享点击（访客调用）
 */
export function recordShareClick(code: string) {
  return post<{
    inviter_id: number
    type: ShareType
    target_id: number | null
  }>('/share/click', { code })
}

/**
 * 使用邀请码注册
 */
export function registerWithInviteCode(code: string) {
  return post<{
    message: string
    inviter_id: number
  }>('/share/register', { code })
}

/**
 * 获取分享统计
 */
export function getShareStats() {
  return get<ShareStats>('/share/stats')
}

/**
 * 获取我的分享链接列表
 */
export function getMyShareLinks(params?: { page?: number; page_size?: number }) {
  return get<{
    total: number
    list: ShareLink[]
    page: number
    page_size: number
  }>('/share/my-links', params)
}

/**
 * 获取我的邀请列表
 */
export function getMyInvites(params?: { page?: number; page_size?: number }) {
  return get<{
    total: number
    list: InviteRecord[]
    page: number
    page_size: number
  }>('/share/my-invites', params)
}

/**
 * 获取奖励配置
 */
export function getRewardConfig() {
  return get<RewardConfigResponse>('/share/reward-config')
}

/**
 * 获取分享话术模板
 */
export function getShareTemplates() {
  return get<ShareTemplates>('/share/templates')
}
