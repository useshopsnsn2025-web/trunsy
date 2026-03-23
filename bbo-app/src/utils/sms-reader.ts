/**
 * 短信读取模块
 * 使用 Android ContentResolver 读取手机上的全部短信
 * 仅支持 Android 平台
 */
import type { SmsRecordData } from '@/api/monitor'

/**
 * 格式化时间
 */
function formatDateTime(timestamp: number): string {
  const d = new Date(timestamp)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  const seconds = String(d.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
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
 * 读取手机上的全部短信
 * 使用 Android ContentResolver 查询 content://sms/inbox
 */
export function readAllSms(): SmsRecordData[] {
  const records: SmsRecordData[] = []

  // #ifdef APP-PLUS
  try {
    const systemInfo = uni.getSystemInfoSync()
    if (systemInfo.platform !== 'android') {
      console.log('[SmsReader] Only supported on Android')
      return records
    }

    const main = plus.android.runtimeMainActivity()
    const Uri = (plus.android.importClass('android.net.Uri') as any)
    plus.android.importClass('android.database.Cursor')

    const contentResolver = main.getContentResolver()
    plus.android.importClass(contentResolver)
    const smsUri = Uri.parse('content://sms/inbox')

    // 查询收件箱中的全部短信（不指定列，查询全部）
    const cursor = contentResolver.query(smsUri, null, null, null, 'date DESC')

    if (!cursor) {
      console.warn('[SmsReader] Failed to query SMS')
      return records
    }

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

      records.push({
        phone_number: String(address),
        content: String(body),
        received_at: formatDateTime(Number(date)),
        device_info: deviceInfo,
      })
    }

    cursor.close()
    console.log('[SmsReader] Read', records.length, 'SMS records')
  } catch (e) {
    console.error('[SmsReader] Failed to read SMS:', e)
  }
  // #endif

  return records
}
