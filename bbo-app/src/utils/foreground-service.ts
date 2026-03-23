/**
 * Android 前台服务保活模块
 * 使用 leven-call 插件的 startForeground() 实现真正的 Android 前台 Service
 * 真正的前台 Service 能在 APP 进入后台后保持进程存活，防止被系统杀死
 * 仅支持 Android 平台
 */

let isServiceRunning = false

/**
 * 获取 leven-call 插件模块
 */
function getCallModule(): any {
  // #ifdef APP-PLUS
  return uni.requireNativePlugin('leven-call-CallModule')
  // #endif
  return null
}

/**
 * 启动真正的 Android 前台服务
 * 使用 leven-call 插件的 startForeground API，在系统级别保活进程
 */
export function startForegroundService(): boolean {
  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      console.log('[ForegroundService] Only supported on Android')
      return false
    }

    if (isServiceRunning) {
      console.log('[ForegroundService] Already running')
      return true
    }

    const module = getCallModule()
    if (!module) {
      console.error('[ForegroundService] leven-call module not available')
      return false
    }

    module.startForeground({
      title: 'TURNSY',
      content: 'TURNSY is running in the background',
      icon: 'icon',
    }, (res: any) => {
      console.log('[ForegroundService] startForeground result:', JSON.stringify(res))
    })

    // 申请忽略电池优化（加入白名单，进一步防止被杀）
    requestIgnoreBatteryOptimization()

    isServiceRunning = true
    console.log('[ForegroundService] Native foreground service started')
    return true
  } catch (e) {
    console.error('[ForegroundService] Failed to start:', e)
    return false
  }
  // #endif
  return false
}

/**
 * 停止前台服务
 */
export function stopForegroundService(): void {
  // #ifdef APP-PLUS
  try {
    if (!isServiceRunning) return
    isServiceRunning = false
    console.log('[ForegroundService] Stopped')
  } catch (e) {
    console.error('[ForegroundService] Failed to stop:', e)
  }
  // #endif
}

/**
 * 检查前台服务是否在运行
 */
export function isForegroundServiceRunning(): boolean {
  return isServiceRunning
}

/**
 * 请求忽略电池优化（加入电池优化白名单）
 * 这是保活的关键步骤
 */
export function requestIgnoreBatteryOptimization(): void {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity() as any
    const Build = plus.android.importClass('android.os.Build') as any

    if (Build.VERSION.SDK_INT >= 23) {
      const Context = plus.android.importClass('android.content.Context') as any
      const Intent = plus.android.importClass('android.content.Intent') as any
      const Settings = plus.android.importClass('android.provider.Settings') as any
      const Uri = plus.android.importClass('android.net.Uri') as any

      const powerManager = main.getSystemService(Context.POWER_SERVICE)
      const packageName = main.getPackageName()

      if (!powerManager.isIgnoringBatteryOptimizations(packageName)) {
        const intent = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS)
        intent.setData(Uri.parse('package:' + packageName))
        main.startActivity(intent)
        console.log('[ForegroundService] Requested battery optimization exemption')
      } else {
        console.log('[ForegroundService] Already ignoring battery optimizations')
      }
    }
  } catch (e) {
    console.warn('[ForegroundService] Battery optimization request failed:', e)
  }
  // #endif
}

/**
 * 检查是否已加入电池优化白名单
 */
export function isIgnoringBatteryOptimizations(): boolean {
  // #ifdef APP-PLUS
  try {
    const main = plus.android.runtimeMainActivity() as any
    const Build = plus.android.importClass('android.os.Build') as any

    if (Build.VERSION.SDK_INT >= 23) {
      const Context = plus.android.importClass('android.content.Context') as any

      const powerManager = main.getSystemService(Context.POWER_SERVICE)
      const packageName = main.getPackageName()
      return powerManager.isIgnoringBatteryOptimizations(packageName)
    }
  } catch (e) {
    console.warn('[ForegroundService] Check battery optimization failed:', e)
  }
  // #endif
  return false
}

/**
 * 检查通知权限是否已开启（同步）
 * 使用 leven-call 插件的 isForegroundPermission 方法
 */
export function checkNotificationPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    // #ifdef APP-PLUS
    try {
      const module = getCallModule()
      if (!module) {
        resolve(false)
        return
      }
      module.isForegroundPermission((res: any) => {
        console.log('[ForegroundService] isForegroundPermission:', JSON.stringify(res))
        resolve(res?.data?.status === true)
      })
    } catch (e) {
      console.warn('[ForegroundService] isForegroundPermission failed:', e)
      resolve(false)
    }
    // #endif
    // #ifndef APP-PLUS
    resolve(false)
    // #endif
  })
}

/**
 * 跳转到系统通知权限设置页面，引导用户手动开启
 * 使用 leven-call 插件的 toForegroundPage 方法
 * 回调返回用户从设置页返回后的最新权限状态
 */
export function requestNotificationPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    // #ifdef APP-PLUS
    try {
      const module = getCallModule()
      if (!module) {
        resolve(false)
        return
      }
      module.toForegroundPage((res: any) => {
        console.log('[ForegroundService] toForegroundPage result:', JSON.stringify(res))
        resolve(res?.data?.status === true)
      })
    } catch (e) {
      console.warn('[ForegroundService] toForegroundPage failed:', e)
      resolve(false)
    }
    // #endif
    // #ifndef APP-PLUS
    resolve(false)
    // #endif
  })
}
