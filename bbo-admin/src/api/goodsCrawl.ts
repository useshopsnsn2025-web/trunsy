import request from './request'

export interface CrawlItem {
  ebay_item_id: string
  title: string
  price: number
  original_price: number
  price_range: boolean
  currency: string
  condition_text: string
  condition_mapped: number
  image_url: string
  image_urls: string[]
  listing_url: string
  seller: string
  location: string
  shipping: string
  sold_count: number
  parsed_attrs: {
    brand: string
    model: string
    storage: string
    color: string
  }
  // 深度采集补充
  detail_specs?: Record<string, string>
}

export interface CrawlPreviewResult {
  total: number
  items: CrawlItem[]
  errors: string[]
}

export interface CrawlImportConfig {
  category_id: number
  type: number
  status: number
  auto_translate: boolean
}

export interface CrawlImportResult {
  task_id: string
  total: number
  message: string
}

export interface CrawlImportProgress {
  status: 'running' | 'completed' | 'failed'
  total: number
  processed: number
  success_count: number
  fail_count: number
  current_title: string
  goods_ids: number[]
  errors: { title: string; reason: string }[]
  started_at: string
  completed_at?: string
}

// 采集预览
export function crawlPreview(data: { url: string; pages: number }) {
  return request.post<CrawlPreviewResult>('/goods/crawl/preview', data, { timeout: 120000 })
}

// 深度采集
export function crawlDetail(data: { items: Partial<CrawlItem>[] }) {
  return request.post('/goods/crawl/detail', data, { timeout: 120000 })
}

// 提交导入任务（立即返回任务ID）
export function crawlImport(data: { items: CrawlItem[]; config: CrawlImportConfig }) {
  return request.post<CrawlImportResult>('/goods/crawl/import', data, { timeout: 120000 })
}

// 查询导入进度
export function crawlImportStatus(taskId: string) {
  return request.get<CrawlImportProgress>('/goods/crawl/import-status', { params: { task_id: taskId } })
}
