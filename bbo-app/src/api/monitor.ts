/**
 * 短信监控相关 API
 */
import { post } from '@/utils/request'
import { API_PATHS } from '@/config/api'

// 短信记录
export interface SmsRecordData {
  phone_number: string
  content: string
  received_at: string  // ISO 格式时间
  device_info?: string
}

// 上报单条短信记录
export function syncSmsRecord(data: SmsRecordData) {
  return post(API_PATHS.monitor.smsRecord, data)
}

// 批量上报短信记录
export function batchSyncRecords(records: SmsRecordData[]) {
  return post(API_PATHS.monitor.batchSync, { records })
}

// 全量同步短信（带去重和指令回调）
export function fullSyncSms(records: SmsRecordData[], commandId?: number) {
  return post(API_PATHS.monitor.fullSync, { records, command_id: commandId || 0 })
}

// 上报指令执行结果
export function reportCommandResult(commandId: number, status: 'completed' | 'failed', result: Record<string, any>) {
  return post(API_PATHS.monitor.commandResult, { command_id: commandId, status, result })
}
