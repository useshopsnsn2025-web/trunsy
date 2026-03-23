// 游戏相关 API
import { get, post } from '@/utils/request'

// ==================== 类型定义 ====================

// 游戏配置
export interface Game {
  id: number
  code: string // wheel, egg, scratch, checkin
  type: 'lottery' | 'task' | 'checkin' | 'share'
  status: number
  icon: string
  bg_image: string
  config: GameConfig
  sort: number
  start_time: string | null
  end_time: string | null
  name: string // 已翻译的名称
  description: string // 已翻译的描述
  rules: string // 已翻译的规则
}

// 游戏配置
export interface GameConfig {
  slots?: number // 转盘格子数
  spin_duration?: number // 旋转时长（秒）
  eggs?: number // 金蛋数量
  [key: string]: any
}

// 奖品
export interface Prize {
  id: number
  game_id: number
  type: 'points' | 'coupon' | 'cash' | 'goods' | 'chance'
  value: number
  probability: number // 概率（百分比）
  image: string
  color: string
  sort: number
  name: string // 已翻译的名称
  description?: string
}

// 用户游戏次数
export interface UserGameChances {
  game_code: string
  chances: number // 可用次数
  used_today: number // 今日已用
  total_used: number // 累计已用
}

// 抽奖结果
export interface PlayResult {
  prize: Prize
  prize_id: number
  prize_type: string
  prize_value: number
  prize_name: string
  prize_image: string
  prize_color: string
  remaining_chances: number
  log_id: number
}

// 中奖记录
export interface WinRecord {
  id: number
  user_id: number
  user_nickname: string // 脱敏的用户名
  game_code: string
  prize_type: string
  prize_value: number
  prize_name: string
  created_at: string
}

// 用户游戏记录
export interface GameLog {
  id: number
  game_id: number
  game_code: string
  prize_id: number
  prize_type: string
  prize_value: number
  prize_name: string
  status: 'pending' | 'claimed' | 'expired'
  claimed_at: string | null
  expired_at: string | null
  created_at: string
}

// 签到状态
export interface CheckinStatus {
  checked_today: boolean
  continuous_days: number
  today_reward: {
    points: number
    extra_reward?: string
  }
  calendar: CheckinDay[]
}

// 签到日历
export interface CheckinDay {
  date: string
  checked: boolean
  reward_points: number
  extra_reward?: string
}

// ==================== API 方法 ====================

// 获取游戏列表
export function getGameList() {
  return get<Game[]>('/games')
}

// 获取游戏详情
export function getGameDetail(code: string) {
  return get<Game>(`/games/${code}`)
}

// 获取游戏奖品列表
export function getGamePrizes(gameCode: string) {
  return get<Prize[]>(`/games/${gameCode}/prizes`)
}

// 获取用户所有游戏次数
export function getUserGameChances() {
  return get<UserGameChances[]>('/games/chances')
}

// 每日登录奖励项
export interface DailyLoginReward {
  game_code: string
  chances: number
  name: string
}

// 每日登录状态
export interface DailyLoginStatus {
  claimed: boolean
  rewards: DailyLoginReward[]
  total_chances: number
}

// 检查每日登录奖励状态
export function getDailyLoginStatus() {
  return get<DailyLoginStatus>('/games/daily-login/status')
}

// 领取每日登录奖励（一次性领取所有游戏次数）
export function claimDailyLoginReward() {
  return post<{
    chances_added: number
    chances: Record<string, number>
  }>('/games/daily-login/claim')
}

// 获取指定游戏次数
export function getGameChances(gameCode: string) {
  return get<UserGameChances>(`/games/${gameCode}/chances`)
}

// 转盘抽奖
export function playWheel() {
  return post<PlayResult>('/games/wheel/spin')
}

// 砸金蛋
export function playEgg(eggIndex: number) {
  return post<PlayResult>('/games/egg/smash', { egg_index: eggIndex })
}

// 刮刮卡
export function playScratch() {
  return post<PlayResult>('/games/scratch/reveal')
}

// 获取中奖播报（跑马灯）
export function getWinnerBroadcast(limit: number = 20) {
  return get<WinRecord[]>('/games/winners', { limit })
}

// 获取用户游戏记录
export function getUserGameLogs(params?: {
  game_code?: string
  status?: string
  page?: number
  pageSize?: number
}) {
  return get<{
    list: GameLog[]
    total: number
    page: number
    pageSize: number
  }>('/games/logs', params)
}

// 领取奖品
export function claimPrize(logId: number) {
  return post<{
    message: string
    prize_name: string
    prize_type: string
    prize_value: number
  }>(`/games/prizes/${logId}/claim`)
}

// ==================== 签到相关 ====================

// 获取签到状态
export function getCheckinStatus() {
  return get<CheckinStatus>('/checkin/status')
}

// 执行签到
export function doCheckin() {
  return post<{
    success: boolean
    reward_points: number
    extra_reward?: string
    continuous_days: number
  }>('/checkin')
}

// 获取签到日历
export function getCheckinCalendar(month?: string) {
  return get<CheckinDay[]>('/checkin/calendar', { month })
}

// ==================== 积分相关 ====================

// 获取用户积分
export function getUserPoints() {
  return get<{
    balance: number
    total_earned: number
    total_spent: number
  }>('/points/balance')
}

// 获取积分记录
export function getPointLogs(params?: {
  type?: 'earn' | 'spend'
  page?: number
  pageSize?: number
}) {
  return get<{
    list: Array<{
      id: number
      type: 'earn' | 'spend'
      amount: number
      balance_after: number
      source: string
      description: string
      created_at: string
    }>
    total: number
    page: number
    pageSize: number
  }>('/points/logs', params)
}

// 积分兑换
export function exchangePoints(params: {
  type: 'coupon' | 'chance' // 兑换类型
  target_id?: number // 目标ID（如优惠券ID）
  amount?: number // 积分数量
}) {
  return post<{ message: string }>('/points/exchange', params)
}

// ==================== 宝箱相关 ====================

// 宝箱配置
export interface TreasureBox {
  id: number
  code: string
  type: string
  icon: string
  bg_color: string
  name: string
  description: string
  prizes: TreasureBoxPrize[]
}

// 宝箱奖品
export interface TreasureBoxPrize {
  id: number
  box_id: number
  type: 'points' | 'cash' | 'coupon' | 'chance'
  value: number
  probability: number
  image: string
  color: string
  name: string
  description?: string
}

// 用户宝箱
export interface UserTreasureBox {
  id: number
  box_code: string
  box_name: string
  box_type: string
  bg_color: string
  source: string
  status: 'pending' | 'opened' | 'expired'
  expired_at: string
  created_at: string
}

// 宝箱数量
export interface BoxCounts {
  silver_box: number
  gold_box: number
  diamond_box: number
}

// 获取宝箱列表
export function getTreasureBoxList() {
  return get<TreasureBox[]>('/treasure-boxes')
}

// 获取宝箱详情
export function getTreasureBoxDetail(code: string) {
  return get<TreasureBox>(`/treasure-boxes/${code}`)
}

// 获取我的宝箱
export function getMyTreasureBoxes() {
  return get<{
    boxes: UserTreasureBox[]
    counts: BoxCounts
  }>('/treasure-boxes/my')
}

// 开启宝箱
export function openTreasureBox(id: number) {
  return post<{
    prize: TreasureBoxPrize
    prize_type: string
    prize_value: number
    remaining_boxes: BoxCounts
  }>(`/treasure-boxes/${id}/open`)
}

// 获取宝箱历史
export function getTreasureBoxHistory(params?: {
  page?: number
  page_size?: number
}) {
  return get<{
    list: Array<{
      id: number
      box_code: string
      box_name: string
      source: string
      status: string
      prize: TreasureBoxPrize | null
      opened_at: string | null
      created_at: string
    }>
    total: number
    page: number
    page_size: number
  }>('/treasure-boxes/history', params)
}

// ==================== 蛋分级相关 ====================

// 蛋分级配置
export interface EggTier {
  id: number
  code: string // bronze_egg, silver_egg, gold_egg, diamond_egg
  type: string
  icon: string
  bg_color: string
  min_order_amount: number
  name: string
  description: string
  prizes: EggTierPrize[]
}

// 蛋分级奖品
export interface EggTierPrize {
  id: number
  egg_id: number
  type: 'points' | 'cash' | 'coupon' | 'chance'
  value: number
  probability: number
  image: string
  color: string
  name: string
  description?: string
}

// 蛋分级砸蛋结果
export interface EggTierSmashResult {
  egg_tier: EggTier
  prize: EggTierPrize
  remaining_chances: number
}

// 获取蛋分级列表
export function getEggTierList() {
  return get<EggTier[]>('/egg-tiers')
}

// 获取蛋分级详情
export function getEggTierDetail(code: string) {
  return get<EggTier>(`/egg-tiers/${code}`)
}

// 根据订单金额获取可获得的蛋类型
export function getEggForOrder(amount: number) {
  return get<EggTier | null>('/egg-tiers/for-order', { amount })
}

// 砸蛋（使用蛋分级奖池）
export function smashEggTier(eggCode: string) {
  return post<EggTierSmashResult>('/egg-tiers/smash', { egg_code: eggCode })
}

// ==================== 订单游戏奖励 ====================

// 订单奖励信息
export interface OrderRewardInfo {
  wheel_chances: number
  egg_granted: boolean
  egg_code: string | null
  egg_name: string | null
}

// 订单奖励预览
export interface RewardPreview {
  wheel_chances: number
  egg_tier: {
    code: string
    type: string
    name: string
  } | null
}

// 获取订单的游戏奖励信息
export function getOrderReward(orderNo: string) {
  return get<OrderRewardInfo>('/games/order-reward', { order_no: orderNo })
}

// 获取订单金额的奖励预览
export function getRewardPreview(amount: number) {
  return get<RewardPreview>('/games/reward-preview', { amount })
}
