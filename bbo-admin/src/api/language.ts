import request from './request'

/**
 * 语言配置接口
 */
export interface Language {
  id: number
  code: string
  name: string
  native_name: string
  flag: string
  sort: number
  is_default: boolean
  is_active: boolean
  created_at?: string
  updated_at?: string
}

/**
 * 创建/更新语言参数
 */
export interface LanguageForm {
  code: string
  name: string
  native_name?: string
  flag?: string
  sort?: number
  is_active?: boolean
}

/**
 * 获取语言列表
 */
export function getLanguageList(params?: { page?: number; pageSize?: number; keyword?: string }) {
  return request.get('/languages', { params })
}

/**
 * 获取语言详情
 */
export function getLanguageDetail(id: number) {
  return request.get(`/languages/${id}`)
}

/**
 * 创建语言
 */
export function createLanguage(data: LanguageForm) {
  return request.post('/languages', data)
}

/**
 * 更新语言
 */
export function updateLanguage(id: number, data: Partial<LanguageForm>) {
  return request.put(`/languages/${id}`, data)
}

/**
 * 删除语言
 */
export function deleteLanguage(id: number) {
  return request.delete(`/languages/${id}`)
}

/**
 * 切换语言状态
 */
export function updateLanguageStatus(id: number) {
  return request.put(`/languages/${id}/status`)
}

/**
 * 设置默认语言
 */
export function setDefaultLanguage(id: number) {
  return request.put(`/languages/${id}/default`)
}

/**
 * 更新语言排序
 */
export function updateLanguageSort(ids: number[]) {
  return request.post('/languages/sort', { ids })
}

/**
 * 获取启用的语言选项（用于下拉选择）
 */
export function getLanguageOptions() {
  return request.get('/languages/options')
}

/**
 * 获取翻译 API 配置状态
 */
export function getTranslateConfig() {
  return request.get('/languages/translate-config')
}

/**
 * 翻译类型
 * - ui: 仅翻译前端界面文案（约 1000+ 条，较快）
 * - goods: 仅翻译商品相关内容（商品、分类、品牌、属性等）
 * - content: 仅翻译所有数据库内容
 * - single: 仅翻译指定的单个表
 * - all: 翻译全部（UI + 所有内容）
 */
export type TranslateType = 'ui' | 'content' | 'goods' | 'single' | 'all'

/**
 * 翻译表信息
 */
export interface TranslationTable {
  table: string
  fields: string[]
  label: string
}

/**
 * 获取可翻译的内容表列表
 */
export function getTranslationTables() {
  return request.get('/languages/translation-tables')
}

/**
 * 触发语言翻译（分批执行）
 * @param id 语言ID
 * @param sourceLocale 源语言代码
 * @param type 翻译类型
 * @param force 是否强制重新翻译（覆盖已有翻译）
 * @param restart 是否重新开始（清除之前的进度）
 * @param table 单表翻译时指定的表名
 */
export function triggerTranslate(id: number, sourceLocale: string = 'en-us', type: TranslateType = 'ui', force: boolean = false, restart: boolean = false, table?: string) {
  return request.post(`/languages/${id}/translate`, { source_locale: sourceLocale, type, force, restart, table }, {
    timeout: 180000 // 3分钟超时（单次请求处理30条记录）
  })
}

/**
 * 获取翻译进度
 */
export function getTranslateProgress(id: number) {
  return request.get(`/languages/${id}/translate-progress`)
}

/**
 * 获取翻译统计
 */
export function getTranslateStats(id: number) {
  return request.get(`/languages/${id}/translate-stats`)
}

/**
 * 检查翻译状态（对比默认语言的覆盖情况）
 */
export function checkTranslationStatus(id: number) {
  return request.get(`/languages/${id}/check-status`)
}
