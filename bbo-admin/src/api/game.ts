import request from './request'

/**
 * 游戏配置接口
 */
export interface Game {
  id: number
  code: string
  type: 'lottery' | 'task' | 'checkin' | 'share'
  icon: string
  bg_image: string
  config: GameConfig
  sort: number
  status: number
  start_time: string | null
  end_time: string | null
  created_at: string
  updated_at: string
  translations?: Record<string, GameTranslation>
  prize_count?: number
  today_stats?: {
    play_count: number
    issued_value: number
  }
}

export interface GameConfig {
  slots?: number
  spin_duration?: number
  eggs?: number
  [key: string]: any
}

export interface GameTranslation {
  name: string
  description: string
  rules: string
}

/**
 * 奖品配置接口
 */
export interface Prize {
  id: number
  game_id: number
  type: 'points' | 'coupon' | 'cash' | 'goods' | 'chance'
  value: number
  coupon_id: number | null
  goods_id: number | null
  probability: number  // 百分比格式 0-100
  stock: number
  daily_limit: number
  user_daily_limit: number
  image: string
  color: string
  sort: number
  status: number
  created_at: string
  updated_at: string
  translations?: Record<string, PrizeTranslation>
}

export interface PrizeTranslation {
  name: string
  description?: string
}

export interface PrizeForm {
  type: Prize['type']
  value: number
  coupon_id?: number | null
  goods_id?: number | null
  probability: number
  stock?: number
  daily_limit?: number
  user_daily_limit?: number
  image?: string
  color?: string
  sort?: number
  status?: number
  translations?: Record<string, PrizeTranslation>
}

/**
 * 获取游戏列表
 */
export function getGameList() {
  return request.get<Game[]>('/games')
}

/**
 * 获取游戏详情
 */
export function getGameDetail(id: number) {
  return request.get<Game>(`/games/${id}`)
}

/**
 * 更新游戏配置
 */
export function updateGame(id: number, data: Partial<Game>) {
  return request.put(`/games/${id}`, data)
}

/**
 * 切换游戏状态
 */
export function toggleGameStatus(id: number) {
  return request.put(`/games/${id}/status`)
}

/**
 * 获取游戏奖品列表
 */
export function getGamePrizes(gameId: number) {
  return request.get<{
    prizes: Prize[]
    total_probability: number
    config: GameConfig
  }>(`/games/${gameId}/prizes`)
}

/**
 * 添加奖品
 */
export function addPrize(gameId: number, data: PrizeForm) {
  return request.post(`/games/${gameId}/prizes`, data)
}

/**
 * 更新奖品
 */
export function updatePrize(gameId: number, prizeId: number, data: Partial<PrizeForm>) {
  return request.put(`/games/${gameId}/prizes/${prizeId}`, data)
}

/**
 * 删除奖品
 */
export function deletePrize(gameId: number, prizeId: number) {
  return request.delete(`/games/${gameId}/prizes/${prizeId}`)
}

/**
 * 批量更新奖品排序
 */
export function sortPrizes(gameId: number, sorts: Record<number, number>) {
  return request.post(`/games/${gameId}/prizes/sort`, { sorts })
}

/**
 * 验证概率配置
 */
export function validateProbability(gameId: number) {
  return request.get<{
    total: number
    valid: boolean
    message: string
  }>(`/games/${gameId}/validate-probability`)
}

/**
 * 模拟抽奖
 */
export function simulateLottery(gameId: number, times: number = 1000) {
  return request.post<{
    times: number
    results: Array<{
      id: number
      name: string
      type: string
      value: number
      expected_probability: number
      count: number
      actual_probability: number
      deviation: number
    }>
    total_cost: number
    avg_cost: number
  }>(`/games/${gameId}/simulate`, { times })
}

/**
 * 获取游戏统计
 */
export function getGameStats(gameId: number, startDate?: string, endDate?: string) {
  return request.get(`/games/${gameId}/stats`, {
    params: { start_date: startDate, end_date: endDate }
  })
}

/**
 * 奖品类型选项
 */
export const prizeTypeOptions = [
  { label: '积分', value: 'points' },
  { label: '优惠券', value: 'coupon' },
  { label: '现金券', value: 'cash' },
  { label: '实物商品', value: 'goods' },
  { label: '游戏次数', value: 'chance' },
]

/**
 * 预设颜色
 */
export const presetColors = [
  { label: '金色（常见）', value: '#FFD700' },
  { label: '橙色（中等）', value: '#FFA500' },
  { label: '红色（稀有）', value: '#FF4444' },
  { label: '紫色（传说）', value: '#A855F7' },
  { label: '蓝色', value: '#3B82F6' },
  { label: '银色', value: '#C0C0C0' },
  { label: '绿色', value: '#22C55E' },
]

/**
 * 游戏类型标签
 */
export const gameTypeLabels: Record<string, string> = {
  lottery: '抽奖',
  task: '任务',
  checkin: '签到',
  share: '分享',
}

/**
 * 游戏代码标签
 */
export const gameCodeLabels: Record<string, string> = {
  wheel: '幸运转盘',
  egg: '砸金蛋',
  scratch: '刮刮卡',
  checkin: '每日签到',
}
