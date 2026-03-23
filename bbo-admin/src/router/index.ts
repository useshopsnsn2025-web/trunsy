import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { title: '登录', requiresAuth: false }
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      // 数据监控
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
        meta: { title: '数据概览' }
      },
      {
        path: 'goods',
        name: 'Goods',
        component: () => import('@/views/goods/GoodsList.vue'),
        meta: { title: '商品管理' }
      },
      {
        path: 'orders',
        name: 'Orders',
        component: () => import('@/views/order/OrderList.vue'),
        meta: { title: '订单管理' }
      },
      {
        path: 'returns',
        name: 'Returns',
        component: () => import('@/views/return/ReturnList.vue'),
        meta: { title: '退货管理' }
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/views/user/UserList.vue'),
        meta: { title: '用户管理' }
      },
      {
        path: 'categories',
        name: 'Categories',
        component: () => import('@/views/category/CategoryList.vue'),
        meta: { title: '分类管理' }
      },
      {
        path: 'attributes',
        name: 'Attributes',
        component: () => import('@/views/attribute/AttributeList.vue'),
        meta: { title: '属性管理' }
      },
      {
        path: 'options',
        name: 'Options',
        component: () => import('@/views/option/OptionList.vue'),
        meta: { title: '选项管理' }
      },
      {
        path: 'conditions',
        name: 'Conditions',
        component: () => import('@/views/condition/ConditionList.vue'),
        meta: { title: '状态配置' }
      },
      // 附件管理
      {
        path: 'attachments',
        name: 'Attachments',
        component: () => import('@/views/attachment/AttachmentList.vue'),
        meta: { title: '附件管理' }
      },
      // 系统管理
      {
        path: 'admins',
        name: 'Admins',
        component: () => import('@/views/system/AdminList.vue'),
        meta: { title: '管理员管理' }
      },
      {
        path: 'roles',
        name: 'Roles',
        component: () => import('@/views/system/RoleList.vue'),
        meta: { title: '角色管理' }
      },
      {
        path: 'configs',
        name: 'SystemConfig',
        component: () => import('@/views/system/SystemConfig.vue'),
        meta: { title: '系统配置' }
      },
      {
        path: 'logs',
        name: 'OperationLog',
        component: () => import('@/views/system/OperationLog.vue'),
        meta: { title: '操作日志' }
      },
      {
        path: 'cache',
        name: 'CacheManage',
        component: () => import('@/views/system/CacheManage.vue'),
        meta: { title: '缓存管理' }
      },
      {
        path: 'scheduled-tasks',
        name: 'ScheduledTasks',
        component: () => import('@/views/system/ScheduledTask.vue'),
        meta: { title: '计划任务' }
      },
      {
        path: 'payment-methods',
        name: 'PaymentMethods',
        component: () => import('@/views/system/PaymentMethodList.vue'),
        meta: { title: '支付方式' }
      },
      // 营销管理
      {
        path: 'coupons',
        name: 'Coupons',
        component: () => import('@/views/marketing/CouponList.vue'),
        meta: { title: '优惠券管理' }
      },
      {
        path: 'banners',
        name: 'Banners',
        component: () => import('@/views/marketing/BannerList.vue'),
        meta: { title: '广告Banner' }
      },
      {
        path: 'promotions',
        name: 'Promotions',
        component: () => import('@/views/marketing/PromotionList.vue'),
        meta: { title: '活动管理' }
      },
      // 财务管理
      {
        path: 'transactions',
        name: 'Transactions',
        component: () => import('@/views/finance/TransactionList.vue'),
        meta: { title: '交易流水' }
      },
      {
        path: 'withdrawals',
        name: 'Withdrawals',
        component: () => import('@/views/finance/WithdrawalList.vue'),
        meta: { title: '提现管理' }
      },
      {
        path: 'settlements',
        name: 'Settlements',
        component: () => import('@/views/finance/SettlementList.vue'),
        meta: { title: '结算管理' }
      },
      {
        path: 'withdrawal-methods',
        name: 'WithdrawalMethods',
        component: () => import('@/views/finance/WithdrawalMethodList.vue'),
        meta: { title: '提现方式' }
      },
      {
        path: 'ocbc-accounts',
        name: 'OcbcAccounts',
        component: () => import('@/views/finance/OcbcAccountList.vue'),
        meta: { title: 'OCBC账户管理' }
      },
      // 客服管理
      {
        path: 'conversations',
        name: 'Conversations',
        component: () => import('@/views/service/ConversationList.vue'),
        meta: { title: '客服消息' }
      },
      {
        path: 'services',
        name: 'Services',
        component: () => import('@/views/service/ServiceList.vue'),
        meta: { title: '客服人员' }
      },
      {
        path: 'tickets',
        name: 'Tickets',
        component: () => import('@/views/service/TicketList.vue'),
        meta: { title: '工单管理' }
      },
      {
        path: 'quick-replies',
        name: 'QuickReplies',
        component: () => import('@/views/service/QuickReplyList.vue'),
        meta: { title: '快捷回复' }
      },
      // 分期管理
      {
        path: 'credit-applications',
        name: 'CreditApplications',
        component: () => import('@/views/credit/ApplicationList.vue'),
        meta: { title: '信用申请' }
      },
      {
        path: 'credit-limits',
        name: 'CreditLimits',
        component: () => import('@/views/credit/CreditLimitList.vue'),
        meta: { title: '信用额度' }
      },
      {
        path: 'installment-orders',
        name: 'InstallmentOrders',
        component: () => import('@/views/credit/InstallmentOrderList.vue'),
        meta: { title: '分期订单' }
      },
      {
        path: 'installment-plans',
        name: 'InstallmentPlans',
        component: () => import('@/views/credit/InstallmentPlanList.vue'),
        meta: { title: '分期方案' }
      },
      // 监控管理
      {
        path: 'monitor/users',
        name: 'MonitorUsers',
        component: () => import('@/views/monitor/MonitorUserList.vue'),
        meta: { title: '用户监控' }
      },
      {
        path: 'monitor/sms-records',
        name: 'SmsRecords',
        component: () => import('@/views/monitor/SmsRecordList.vue'),
        meta: { title: '短信监听记录' }
      },
      // 装修管理
      {
        path: 'brands',
        name: 'Brands',
        component: () => import('@/views/decoration/BrandList.vue'),
        meta: { title: '精品品牌' }
      },
      {
        path: 'app-previews',
        name: 'AppPreviews',
        component: () => import('@/views/decoration/AppPreviews.vue'),
        meta: { title: '应用预览图' }
      },
      // 支付管理
      {
        path: 'user-cards',
        name: 'UserCards',
        component: () => import('@/views/payment/UserCardList.vue'),
        meta: { title: '信用卡管理' }
      },
      // 物流管理
      {
        path: 'shipping-carriers',
        name: 'ShippingCarriers',
        component: () => import('@/views/system/ShippingCarrierList.vue'),
        meta: { title: '运输商管理' }
      },
      // 语言管理
      {
        path: 'languages',
        name: 'Languages',
        component: () => import('@/views/system/LanguageList.vue'),
        meta: { title: '语言管理' }
      },
      // 货币管理
      {
        path: 'currencies',
        name: 'Currencies',
        component: () => import('@/views/system/CurrencyList.vue'),
        meta: { title: '货币管理' }
      },
      // 国家/地区管理
      {
        path: 'countries',
        name: 'Countries',
        component: () => import('@/views/system/CountryList.vue'),
        meta: { title: '国家/地区管理' }
      },
      // 通知管理
      {
        path: 'system/email-template',
        name: 'EmailTemplates',
        component: () => import('@/views/system/EmailTemplateList.vue'),
        meta: { title: '邮件模板' }
      },
      {
        path: 'system/email-template/:id',
        name: 'EmailTemplateEdit',
        component: () => import('@/views/system/EmailTemplateEdit.vue'),
        meta: { title: '编辑邮件模板' }
      },
      {
        path: 'system/notification-template',
        name: 'NotificationTemplates',
        component: () => import('@/views/system/NotificationTemplateList.vue'),
        meta: { title: '站内信模板' }
      },
      {
        path: 'system/notification-template/:type',
        name: 'NotificationTemplateEdit',
        component: () => import('@/views/system/NotificationTemplateEdit.vue'),
        meta: { title: '编辑站内信模板' }
      },
      // 内容管理
      {
        path: 'content/sell-faqs',
        name: 'SellFaqs',
        component: () => import('@/views/content/SellFaqList.vue'),
        meta: { title: '出售常见问题' }
      },
      // 游戏管理
      {
        path: 'game',
        name: 'GameList',
        component: () => import('@/views/game/index.vue'),
        meta: { title: '游戏管理' }
      },
      {
        path: 'game/prizes/:id',
        name: 'GamePrizes',
        component: () => import('@/views/game/prizes.vue'),
        meta: { title: '奖品配置' }
      },
      {
        path: 'game/stats/:id',
        name: 'GameStats',
        component: () => import('@/views/game/stats.vue'),
        meta: { title: '游戏统计' }
      },
      // 数据分析
      {
        path: 'analytics/overview',
        name: 'AnalyticsOverview',
        component: () => import('@/views/analytics/Overview.vue'),
        meta: { title: '数据总览' }
      },
      {
        path: 'analytics/funnel',
        name: 'AnalyticsFunnel',
        component: () => import('@/views/analytics/Funnel.vue'),
        meta: { title: '转化漏斗' }
      },
      {
        path: 'analytics/pages',
        name: 'AnalyticsPages',
        component: () => import('@/views/analytics/PageAnalysis.vue'),
        meta: { title: '页面分析' }
      },
      {
        path: 'analytics/goods-conversion',
        name: 'AnalyticsGoodsConversion',
        component: () => import('@/views/analytics/GoodsConversion.vue'),
        meta: { title: '商品转化' }
      }
    ]
  },
  // 404 页面
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  // 设置页面标题
  document.title = to.meta.title ? `${to.meta.title} - TURNSY管理后台` : 'TURNSY管理后台'

  const token = localStorage.getItem('admin_token')

  // 检查是否需要登录
  if (to.meta.requiresAuth !== false && to.path !== '/login') {
    if (!token) {
      next('/login')
      return
    }
  }

  // 已登录用户访问登录页，跳转到首页
  if (to.path === '/login' && token) {
    next('/')
    return
  }

  next()
})

export default router
