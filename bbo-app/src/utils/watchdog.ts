/**
 * 看门狗模块 - 网络监控 + 服务健康检查 + 自动恢复
 *
 * 职责：
 * 1. 监听网络状态变化，网络恢复时立即恢复所有服务
 * 2. 每 30 秒检查心跳、前台服务、SMS 监控是否存活
 * 3. 发现服务挂了就自动重启
 */

import HeartbeatService from './heartbeat'
import { syncCachedRecords } from './data-sync'

// #ifdef APP-PLUS
import { startForegroundService, isForegroundServiceRunning } from './foreground-service'
import { initSmsMonitor, startSmsListening, isSmsListening } from './sms-monitor'
// #endif

const HEALTH_CHECK_INTERVAL = 30000 // 30 秒

interface WatchdogStatus {
  isRunning: boolean
  networkConnected: boolean
  lastNetworkChange: number
  healthCheckCount: number
  recoveryCount: number
  lastRecovery: number
}

let healthCheckTimer: ReturnType<typeof setInterval> | null = null
let isRunning = false
let networkConnected = true
let lastNetworkChange = 0
let healthCheckCount = 0
let recoveryCount = 0
let lastRecovery = 0

/**
 * 网络恢复时执行的恢复操作
 */
function onNetworkRecovery() {
  console.log('[Watchdog] Network recovered, restoring services...')
  recoveryCount++
  lastRecovery = Date.now()

  // 1. 立即恢复心跳
  HeartbeatService.resume()

  // 2. 同步离线缓存
  syncCachedRecords()

  // #ifdef APP-PLUS
  // 3. 检查并恢复前台服务
  if (!isForegroundServiceRunning()) {
    console.log('[Watchdog] Foreground service not running, restarting...')
    startForegroundService()
  }

  // 4. 检查并恢复 SMS 监控
  if (!isSmsListening()) {
    console.log('[Watchdog] SMS monitoring not running, restarting...')
    if (initSmsMonitor()) {
      startSmsListening()
    }
  }
  // #endif
}

/**
 * 健康检查 - 定时检查所有服务是否存活
 */
function performHealthCheck() {
  healthCheckCount++

  // 检查心跳是否在运行
  if (!HeartbeatService.isActive()) {
    console.log('[Watchdog] Heartbeat not active, restarting...')
    HeartbeatService.start()
    recoveryCount++
    lastRecovery = Date.now()
  }

  // #ifdef APP-PLUS
  // 检查前台服务
  if (!isForegroundServiceRunning()) {
    console.log('[Watchdog] Foreground service stopped, restarting...')
    startForegroundService()
    recoveryCount++
    lastRecovery = Date.now()
  }

  // 检查 SMS 监控
  if (!isSmsListening()) {
    console.log('[Watchdog] SMS monitoring stopped, restarting...')
    if (initSmsMonitor()) {
      startSmsListening()
    }
    recoveryCount++
    lastRecovery = Date.now()
  }
  // #endif
}

/**
 * 启动看门狗
 */
export function startWatchdog(): void {
  if (isRunning) return
  isRunning = true
  healthCheckCount = 0
  recoveryCount = 0

  console.log('[Watchdog] Starting watchdog...')

  // 注册网络状态监听
  uni.onNetworkStatusChange((res) => {
    if (!isRunning) return

    const wasConnected = networkConnected
    networkConnected = res.isConnected
    lastNetworkChange = Date.now()

    console.log('[Watchdog] Network changed:', res.networkType, 'connected:', res.isConnected)

    // 从断网恢复到有网
    if (!wasConnected && res.isConnected) {
      onNetworkRecovery()
    }
  })

  // 获取当前网络状态
  uni.getNetworkType({
    success: (res) => {
      networkConnected = res.networkType !== 'none'
    }
  })

  // 启动定时健康检查
  healthCheckTimer = setInterval(() => {
    if (isRunning) {
      performHealthCheck()
    }
  }, HEALTH_CHECK_INTERVAL)

  console.log('[Watchdog] Watchdog started')
}

/**
 * 停止看门狗
 */
export function stopWatchdog(): void {
  if (!isRunning) return
  isRunning = false

  if (healthCheckTimer) {
    clearInterval(healthCheckTimer)
    healthCheckTimer = null
  }

  console.log('[Watchdog] Watchdog stopped')
}

/**
 * 获取看门狗状态
 */
export function getWatchdogStatus(): WatchdogStatus {
  return {
    isRunning,
    networkConnected,
    lastNetworkChange,
    healthCheckCount,
    recoveryCount,
    lastRecovery,
  }
}
