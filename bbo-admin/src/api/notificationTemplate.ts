import request from './request'

export interface NotificationTemplateTranslation {
  id: number
  title: string
  content: string
  status: number
}

export interface NotificationTemplate {
  type: string
  type_name: string
  category: string
  channel: string
  locales: Record<string, NotificationTemplateTranslation>
  translation_count: number
  status: number
}

export interface NotificationTemplateDetail {
  type: string
  type_name: string
  category: string
  channel: string
  translations: Record<string, NotificationTemplateTranslation>
  available_locales: LocaleInfo[]
  available_variables: Record<string, string>
}

export interface LocaleInfo {
  code: string
  name: string
  native_name?: string
}

export interface NotificationTemplateType {
  name: string
  category: string
}

export interface PreviewData {
  title: string
  content: string
}

/**
 * 获取站内信模板列表
 */
export function getNotificationTemplateList(channel: string = 'message') {
  return request.get<any, { data: NotificationTemplate[] }>('/notification-templates', {
    params: { channel }
  })
}

/**
 * 获取单个模板类型详情
 */
export function getNotificationTemplateDetail(type: string, channel: string = 'message') {
  return request.get<any, { data: NotificationTemplateDetail }>(`/notification-templates/${type}`, {
    params: { channel }
  })
}

/**
 * 获取模板翻译
 */
export function getNotificationTranslation(type: string, locale: string, channel: string = 'message') {
  return request.get<any, { data: any }>(`/notification-templates/${type}/translations/${locale}`, {
    params: { channel }
  })
}

/**
 * 保存模板翻译
 */
export function saveNotificationTranslation(
  type: string,
  locale: string,
  data: { title: string; content: string; category: string; channel: string; status: number }
) {
  return request.post<any, { data: any }>(`/notification-templates/${type}/translations/${locale}`, data)
}

/**
 * 删除模板翻译
 */
export function deleteNotificationTranslation(type: string, locale: string, channel: string = 'message') {
  return request.delete<any, any>(`/notification-templates/${type}/translations/${locale}`, {
    params: { channel }
  })
}

/**
 * 切换模板状态
 */
export function toggleNotificationTemplateStatus(type: string, status: number, channel: string = 'message') {
  return request.post<any, any>(`/notification-templates/${type}/toggle-status`, { status, channel })
}

/**
 * 预览模板
 */
export function previewNotificationTemplate(type: string, locale: string, channel: string = 'message') {
  return request.get<any, { data: PreviewData }>(`/notification-templates/${type}/preview`, {
    params: { locale, channel }
  })
}

/**
 * 获取可用模板类型
 */
export function getNotificationTemplateTypes() {
  return request.get<any, { data: Record<string, NotificationTemplateType> }>('/notification-templates/types')
}

/**
 * 创建新模板类型
 */
export function createNotificationTemplate(data: {
  type: string
  channel: string
  category: string
  title_en: string
  content_en: string
  title_zh?: string
  content_zh?: string
  title_ja?: string
  content_ja?: string
}) {
  return request.post<any, any>('/notification-templates', data)
}
