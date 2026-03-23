import request from './request'

// Overview data
export function getAnalyticsOverview(params: { start_date: string; end_date: string }) {
  return request.get('/analytics/overview', { params })
}

// Funnel data
export function getAnalyticsFunnel(params: {
  start_date: string
  end_date: string
  funnel_type?: string
}) {
  return request.get('/analytics/funnel', { params })
}

// Page stats
export function getAnalyticsPageStats(params: {
  start_date: string
  end_date: string
  page?: string
}) {
  return request.get('/analytics/page-stats', { params })
}

// Goods conversion
export function getAnalyticsGoodsConversion(params: {
  start_date: string
  end_date: string
  limit?: number
}) {
  return request.get('/analytics/goods-conversion', { params })
}

// Manual aggregate
export function triggerAggregate(date: string) {
  return request.post('/analytics/aggregate', { date })
}
