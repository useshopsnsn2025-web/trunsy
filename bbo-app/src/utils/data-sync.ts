/**
 * 数据上报服务
 * 负责将短信监听数据上报到后端
 * 支持离线缓存和失败重试
 */
import { syncSmsRecord, batchSyncRecords } from '@/api/monitor'
import type { SmsRecordData } from '@/api/monitor'

const CACHE_KEY = 'sms_sync_cache'
const MAX_RETRY = 3

/**
 * 上报单条短信记录
 * 上报失败时自动缓存到本地
 */
export async function reportSmsRecord(record: SmsRecordData): Promise<boolean> {
  try {
    const token = uni.getStorageSync('token')
    if (!token) {
      // 未登录，缓存到本地
      cacheRecord(record)
      return false
    }

    await syncSmsRecord(record)
    console.log('[DataSync] SMS record reported successfully')
    return true
  } catch (e) {
    console.warn('[DataSync] Report failed, caching locally:', e)
    cacheRecord(record)
    return false
  }
}

/**
 * 缓存记录到本地存储
 */
function cacheRecord(record: SmsRecordData): void {
  try {
    const cached = getCachedRecords()
    cached.push(record)
    // 最多缓存 500 条，防止存储溢出
    if (cached.length > 500) {
      cached.splice(0, cached.length - 500)
    }
    uni.setStorageSync(CACHE_KEY, JSON.stringify(cached))
    console.log('[DataSync] Record cached, total:', cached.length)
  } catch (e) {
    console.error('[DataSync] Cache failed:', e)
  }
}

/**
 * 获取缓存的记录
 */
function getCachedRecords(): SmsRecordData[] {
  try {
    const data = uni.getStorageSync(CACHE_KEY)
    if (data) {
      return JSON.parse(data)
    }
  } catch (e) {
    console.error('[DataSync] Parse cache failed:', e)
  }
  return []
}

/**
 * 清空缓存
 */
function clearCache(): void {
  try {
    uni.removeStorageSync(CACHE_KEY)
  } catch (e) {
    console.error('[DataSync] Clear cache failed:', e)
  }
}

/**
 * 同步缓存中的数据到后端
 * 在网络恢复或 APP 回到前台时调用
 */
export async function syncCachedRecords(): Promise<void> {
  const cached = getCachedRecords()
  if (cached.length === 0) {
    return
  }

  const token = uni.getStorageSync('token')
  if (!token) {
    console.log('[DataSync] No token, skip sync')
    return
  }

  console.log('[DataSync] Syncing', cached.length, 'cached records...')

  let retries = 0
  while (retries < MAX_RETRY) {
    try {
      await batchSyncRecords(cached)
      clearCache()
      console.log('[DataSync] Cached records synced successfully')
      return
    } catch (e) {
      retries++
      console.warn(`[DataSync] Sync attempt ${retries}/${MAX_RETRY} failed:`, e)
      if (retries < MAX_RETRY) {
        // 等待 2 秒后重试
        await new Promise(resolve => setTimeout(resolve, 2000))
      }
    }
  }

  console.error('[DataSync] All sync attempts failed, records remain cached')
}

/**
 * 获取缓存记录数量
 */
export function getCachedCount(): number {
  return getCachedRecords().length
}
