import request from './request'

export interface EmailTemplateImages {
  header_image: string
  logo: string
  banner: string
  footer_image: string
  category_image_1: string
  category_image_2: string
  category_image_3: string
  category_image_4: string
  protection_image: string
  live_image: string
  app_qr_code: string
  sell_image: string
}

export interface EmailTemplate {
  id: number
  type: string
  name: string
  subject: string
  content: string
  variables: string[] | null
  images: EmailTemplateImages | null
  is_active: boolean
  created_at: string
  updated_at: string
  translation_count?: number
  translations?: Record<string, EmailTemplateTranslation>
  available_locales?: LocaleInfo[]
}

export interface EmailTemplateTranslation {
  id?: number
  template_id?: number
  locale?: string
  subject: string
  content: string
}

export interface LocaleInfo {
  code: string
  name: string
  native_name?: string
}

export interface PreviewData {
  subject: string
  content: string
}

/**
 * 获取邮件模板列表
 */
export function getEmailTemplateList() {
  return request.get<any, { data: EmailTemplate[] }>('/email-templates')
}

/**
 * 获取单个模板详情
 */
export function getEmailTemplateDetail(id: number) {
  return request.get<any, { data: EmailTemplate }>(`/email-templates/${id}`)
}

/**
 * 更新模板基本信息
 */
export function updateEmailTemplate(id: number, data: Partial<EmailTemplate>) {
  return request.put<any, { data: EmailTemplate }>(`/email-templates/${id}`, data)
}

/**
 * 获取模板翻译
 */
export function getTemplateTranslation(id: number, locale: string) {
  return request.get<any, { data: EmailTemplateTranslation }>(`/email-templates/${id}/translations/${locale}`)
}

/**
 * 保存模板翻译
 */
export function saveTemplateTranslation(id: number, locale: string, data: { subject: string; content: string }) {
  return request.post<any, { data: EmailTemplateTranslation }>(`/email-templates/${id}/translations/${locale}`, data)
}

/**
 * 删除模板翻译
 */
export function deleteTemplateTranslation(id: number, locale: string) {
  return request.delete<any, any>(`/email-templates/${id}/translations/${locale}`)
}

/**
 * 预览模板
 */
export function previewEmailTemplate(id: number, locale: string) {
  return request.get<any, { data: PreviewData }>(`/email-templates/${id}/preview`, {
    params: { locale }
  })
}

/**
 * 发送测试邮件
 */
export function sendTestEmail(id: number, data: { email: string; locale: string }) {
  return request.post<any, any>(`/email-templates/${id}/send-test`, data)
}

/**
 * 获取可用模板类型
 */
export function getEmailTemplateTypes() {
  return request.get<any, { data: string[] }>('/email-templates/types')
}

/**
 * 获取模板图片配置
 */
export function getTemplateImages(id: number) {
  return request.get<any, { data: { template_id: number; type: string; images: EmailTemplateImages; available_keys: string[] } }>(`/email-templates/${id}/images`)
}

/**
 * 更新模板图片配置
 */
export function updateTemplateImages(id: number, images: Partial<EmailTemplateImages>) {
  return request.put<any, { data: { images: EmailTemplateImages } }>(`/email-templates/${id}/images`, { images })
}
