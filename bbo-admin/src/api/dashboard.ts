import request from './request'

export interface Overview {
  users: {
    total: number
    today: number
    active: number
  }
  goods: {
    total: number
    today: number
    online: number
  }
  orders: {
    total: number
    today: number
    today_amount: number
    month_amount: number
  }
}

export interface TrendData {
  date: string
  users: number
  goods: number
  orders: number
  amount: number
}

// 获取概览数据
export function getDashboardOverview() {
  return request.get('/dashboard/overview')
}

// 获取趋势数据
export function getDashboardTrend(days: number = 7) {
  return request.get('/dashboard/trend', { params: { days } })
}

// 获取订单状态分布
export function getDashboardOrderStatus() {
  return request.get('/dashboard/order-status')
}

// 获取热门分类
export function getDashboardHotCategories() {
  return request.get('/dashboard/hot-categories')
}

// 获取最新订单
export function getDashboardRecentOrders() {
  return request.get('/dashboard/recent-orders')
}

// 获取待处理事项
export function getDashboardPending() {
  return request.get('/dashboard/pending')
}
