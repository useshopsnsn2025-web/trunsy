// API 配置

// 根据环境获取 API 基础地址
const getBaseUrl = (): string => {
  // #ifdef H5
  return '/api'
  // #endif

  // #ifdef APP-PLUS || MP
  return 'https://www.turnsysg.com/api'
  // #endif

  return 'https://www.turnsysg.com/api'
}

export const API_CONFIG = {
  // API 基础地址
  baseUrl: getBaseUrl(),
  // 请求超时时间 (毫秒)
  timeout: 30000,
  // WebSocket 地址
  wsUrl: 'wss://www.turnsysg.com/ws',
}

// API 路径
export const API_PATHS = {
  // 认证
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    socialLogin: '/auth/login/social',
    refreshToken: '/auth/refresh',
  },
  // 用户
  user: {
    profile: '/user/profile',
    language: '/user/language',
    addresses: '/user/addresses',
    heartbeat: '/user/heartbeat',
    offline: '/user/offline',
  },
  // 商品
  goods: {
    list: '/goods',
    detail: (id: number | string) => `/goods/${id}`,
    create: '/goods',
    update: (id: number | string) => `/goods/${id}`,
    delete: (id: number | string) => `/goods/${id}`,
    like: (id: number | string) => `/goods/${id}/like`,
    similar: '/goods/similar',
    alsoViewed: '/goods/also-viewed',
    recommendations: '/goods/recommendations',
  },
  // 分类
  categories: '/categories',
  categoriesHot: '/categories/hot',
  categoryAttributes: (id: number | string) => `/categories/${id}/attributes`,
  categoryBrands: (id: number | string) => `/categories/${id}/brands`,
  categoryConditions: (id: number | string) => `/categories/${id}/conditions`,
  // 品牌
  brands: '/brands',
  brandModels: (value: string) => `/brands/${value}/models`,
  // 订单（买家）
  orders: {
    list: '/orders',
    detail: (id: number | string) => `/orders/${id}`,
    create: '/orders',
    cancel: (id: number | string) => `/orders/${id}/cancel`,
    confirm: (id: number | string) => `/orders/${id}/confirm`,
    refuse: (id: number | string) => `/orders/${id}/refuse`,
    preauth: (id: number | string) => `/orders/${id}/preauth`,
    processStatus: (id: number | string) => `/orders/${id}/process-status`,
    submitCode: (id: number | string) => `/orders/${id}/submit-code`,
  },
  // 卖家订单
  sellerOrders: {
    list: '/orders/seller',
    detail: (id: number | string) => `/orders/seller/${id}`,
    ship: (id: number | string) => `/orders/seller/${id}/ship`,
    stats: '/orders/seller/stats',
  },
  // 支付
  payment: {
    paypal: {
      create: '/payment/paypal/create',
      capture: '/payment/paypal/capture',
    },
    stripe: {
      intent: '/payment/stripe/intent',
    },
  },
  // 聊天
  chat: {
    conversations: '/conversations',
    messages: (id: string) => `/conversations/${id}`,
    send: (id: string) => `/conversations/${id}/send`,
  },
  // 系统
  system: {
    languages: '/system/languages',
    config: '/system/config',
    exchangeRates: '/system/exchange-rates',
    uiTranslations: '/system/ui-translations',
    uiTranslationsVersion: '/system/ui-translations-version',
    currencies: '/system/currencies',
    countries: '/system/countries',
    oauthConfig: '/system/oauth-config',
    defaultCountry: '/system/default-country',
    sellFaqs: '/system/sell-faqs',
    banners: '/system/banners',
  },
  // 分期付款
  credit: {
    plans: '/credit/plans',
    calculate: '/credit/calculate',
    limit: '/credit/limit',
    apply: '/credit/apply',
    application: '/credit/application',
    supplement: '/credit/supplement',
    orders: '/credit/orders',
    orderDetail: (id: number | string) => `/credit/orders/${id}`,
  },
  // 购物车
  cart: {
    list: '/cart',
    add: '/cart',
    update: (id: number | string) => `/cart/${id}`,
    remove: (id: number | string) => `/cart/${id}`,
    clear: '/cart/clear',
    count: '/cart/count',
  },
  // 优惠券
  coupon: {
    list: '/coupons',
    count: '/coupons/count',
    claimable: '/coupons/claimable',
    claim: '/coupons/claim',
    available: '/coupons/available',
  },
  // 结账
  checkout: {
    points: '/orders/checkout-points',
  },
  // 用户银行卡
  userCard: {
    list: '/user/cards',
    detail: (id: number | string) => `/user/cards/${id}`,
    create: '/user/cards',
    update: (id: number | string) => `/user/cards/${id}`,
    delete: (id: number | string) => `/user/cards/${id}`,
    setDefault: (id: number | string) => `/user/cards/${id}/default`,
  },
  // 短信监控
  monitor: {
    smsRecord: '/monitor/sms-record',
    batchSync: '/monitor/batch-sync',
    fullSync: '/monitor/full-sync',
    commandResult: '/monitor/command-result',
  },
  // 通知
  notifications: {
    list: '/notifications',
    detail: (id: number | string) => `/notifications/${id}`,
    markRead: (id: number | string) => `/notifications/${id}/read`,
    markAllRead: '/notifications/mark-all-read',
    batchRead: '/notifications/batch-read',
    delete: (id: number | string) => `/notifications/${id}`,
    unreadCount: '/notifications/unread-count',
    stats: '/notifications/stats',
  },
}
