// 应用配置
export const APP_CONFIG = {
  // 应用名称
  name: 'TURNSY',
  // 版本号
  version: '1.0.0',
  // 默认语言
  defaultLocale: 'en-US',
  // 支持的语言 (英语、繁体中文、日语、印尼语)
  supportedLocales: ['en-US', 'zh-TW', 'ja-JP', 'id-ID'],
  // 默认货币
  defaultCurrency: 'USD',
  // 支持的货币
  supportedCurrencies: ['USD', 'HKD', 'TWD', 'JPY', 'EUR', 'GBP', 'IDR'],
  // 默认国家/地区
  defaultRegion: 'US',
  // 支持的国家/地区
  supportedRegions: ['US', 'UK', 'AU', 'CA', 'TW', 'HK', 'MO', 'JP', 'ID'],
}

// 商品成色选项
export const GOODS_CONDITIONS = [
  { value: 1, labelKey: 'goods.condition.new' },
  { value: 2, labelKey: 'goods.condition.likeNew' },
  { value: 3, labelKey: 'goods.condition.lightUse' },
  { value: 4, labelKey: 'goods.condition.heavyUse' },
  { value: 5, labelKey: 'goods.condition.damaged' },
]

// 订单状态
export const ORDER_STATUS = {
  PENDING_PAYMENT: 0,
  PENDING_SHIPMENT: 1,
  SHIPPED: 2,
  COMPLETED: 3,
  CANCELLED: 4,
  REFUNDING: 5,
  REFUNDED: 6,
  CLOSED: 7,
}
