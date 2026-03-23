import { createI18n } from 'vue-i18n'

// 繁体中文（作为 fallback）
import zhTWCommon from './zh-TW/common.json'
import zhTWUser from './zh-TW/user.json'
import zhTWGoods from './zh-TW/goods.json'
import zhTWOrder from './zh-TW/order.json'
import zhTWCredit from './zh-TW/credit.json'
import zhTWCart from './zh-TW/cart.json'
import zhTWShare from './zh-TW/share.json'
import zhTWFavorites from './zh-TW/favorites.json'
import zhTWPromotion from './zh-TW/promotion.json'
import zhTWCoupon from './zh-TW/coupon.json'
import zhTWGame from './zh-TW/game.json'
import zhTWOcbc from './zh-TW/ocbc.json'
import zhTWBri from './zh-TW/bri.json'
import zhTWWallet from './zh-TW/wallet.json'
import zhTWWondr from './zh-TW/wondr.json'
import zhTWMybca from './zh-TW/mybca.json'
import zhTWGopay from './zh-TW/gopay.json'
import zhTWOvo from './zh-TW/ovo.json'

// 英文（作为 fallback）
import enUSCommon from './en-US/common.json'
import enUSUser from './en-US/user.json'
import enUSGoods from './en-US/goods.json'
import enUSOrder from './en-US/order.json'
import enUSCredit from './en-US/credit.json'
import enUSCart from './en-US/cart.json'
import enUSShare from './en-US/share.json'
import enUSFavorites from './en-US/favorites.json'
import enUSPromotion from './en-US/promotion.json'
import enUSCoupon from './en-US/coupon.json'
import enUSGame from './en-US/game.json'
import enUSOcbc from './en-US/ocbc.json'
import enUSBri from './en-US/bri.json'
import enUSWallet from './en-US/wallet.json'
import enUSWondr from './en-US/wondr.json'
import enUSMybca from './en-US/mybca.json'
import enUSGopay from './en-US/gopay.json'
import enUSOvo from './en-US/ovo.json'

// 日文（作为 fallback）
import jaJPCommon from './ja-JP/common.json'
import jaJPUser from './ja-JP/user.json'
import jaJPGoods from './ja-JP/goods.json'
import jaJPOrder from './ja-JP/order.json'
import jaJPCredit from './ja-JP/credit.json'
import jaJPCart from './ja-JP/cart.json'
import jaJPShare from './ja-JP/share.json'
import jaJPFavorites from './ja-JP/favorites.json'
import jaJPPromotion from './ja-JP/promotion.json'
import jaJPCoupon from './ja-JP/coupon.json'
import jaJPGame from './ja-JP/game.json'
import jaJPOcbc from './ja-JP/ocbc.json'
import jaJPBri from './ja-JP/bri.json'
import jaJPWallet from './ja-JP/wallet.json'
import jaJPWondr from './ja-JP/wondr.json'
import jaJPMybca from './ja-JP/mybca.json'
import jaJPGopay from './ja-JP/gopay.json'
import jaJPOvo from './ja-JP/ovo.json'

// 印尼文（作为 fallback）
import idIDCommon from './id-ID/common.json'
import idIDUser from './id-ID/user.json'
import idIDGoods from './id-ID/goods.json'
import idIDOrder from './id-ID/order.json'
import idIDCredit from './id-ID/credit.json'
import idIDCart from './id-ID/cart.json'
import idIDShare from './id-ID/share.json'
import idIDFavorites from './id-ID/favorites.json'
import idIDPromotion from './id-ID/promotion.json'
import idIDCoupon from './id-ID/coupon.json'
import idIDGame from './id-ID/game.json'
import idIDOcbc from './id-ID/ocbc.json'
import idIDBri from './id-ID/bri.json'
import idIDWallet from './id-ID/wallet.json'
import idIDWondr from './id-ID/wondr.json'
import idIDMybca from './id-ID/mybca.json'
import idIDGopay from './id-ID/gopay.json'
import idIDOvo from './id-ID/ovo.json'

// 繁体中文语言包（fallback）
const zhTWMessages = {
  ...zhTWCommon,
  user: zhTWUser,
  goods: zhTWGoods,
  order: zhTWOrder,
  credit: zhTWCredit,
  cart: zhTWCart,
  share: zhTWShare,
  favorites: zhTWFavorites,
  promotion: zhTWPromotion,
  coupon: zhTWCoupon,
  game: zhTWGame,
  ocbc: zhTWOcbc,
  bri: zhTWBri,
  wallet: zhTWWallet,
  wondr: zhTWWondr,
  mybca: zhTWMybca,
  gopay: zhTWGopay,
  ovo: zhTWOvo,
}

// 英文语言包（fallback）
const enUSMessages = {
  ...enUSCommon,
  user: enUSUser,
  goods: enUSGoods,
  order: enUSOrder,
  credit: enUSCredit,
  cart: enUSCart,
  share: enUSShare,
  favorites: enUSFavorites,
  promotion: enUSPromotion,
  coupon: enUSCoupon,
  game: enUSGame,
  ocbc: enUSOcbc,
  bri: enUSBri,
  wallet: enUSWallet,
  wondr: enUSWondr,
  mybca: enUSMybca,
  gopay: enUSGopay,
  ovo: enUSOvo,
}

// 日文语言包（fallback）
const jaJPMessages = {
  ...jaJPCommon,
  user: jaJPUser,
  goods: jaJPGoods,
  order: jaJPOrder,
  credit: jaJPCredit,
  cart: jaJPCart,
  share: jaJPShare,
  favorites: jaJPFavorites,
  promotion: jaJPPromotion,
  coupon: jaJPCoupon,
  game: jaJPGame,
  ocbc: jaJPOcbc,
  bri: jaJPBri,
  wallet: jaJPWallet,
  wondr: jaJPWondr,
  mybca: jaJPMybca,
  gopay: jaJPGopay,
  ovo: jaJPOvo,
}

// 印尼文语言包（fallback）
const idIDMessages = {
  ...idIDCommon,
  user: idIDUser,
  goods: idIDGoods,
  order: idIDOrder,
  credit: idIDCredit,
  cart: idIDCart,
  share: idIDShare,
  favorites: idIDFavorites,
  promotion: idIDPromotion,
  coupon: idIDCoupon,
  game: idIDGame,
  ocbc: idIDOcbc,
  bri: idIDBri,
  wallet: idIDWallet,
  wondr: idIDWondr,
  mybca: idIDMybca,
  gopay: idIDGopay,
  ovo: idIDOvo,
}

// 本地 fallback 语言包
const fallbackMessages: Record<string, any> = {
  'zh-TW': zhTWMessages,
  'en-US': enUSMessages,
  'ja-JP': jaJPMessages,
  'id-ID': idIDMessages,
}

// 动态加载的语言包缓存
const dynamicMessages: Record<string, any> = {}

// 翻译版本缓存（用于判断是否需要更新）
const translationVersions: Record<string, string> = {}

// 国家/地区到语言的映射
export const REGION_TO_LOCALE: Record<string, string> = {
  'US': 'en-US',
  'UK': 'en-US',
  'GB': 'en-US',
  'AU': 'en-US',
  'CA': 'en-US',
  'NZ': 'en-US',
  'TW': 'zh-TW',
  'HK': 'zh-TW',
  'MO': 'zh-TW',
  'JP': 'ja-JP',
  'KR': 'ko-KR',
  'ID': 'id-ID',
}

// 国家/地区列表（用于选择器）
export const REGIONS = [
  { code: 'US', name: 'region.us', locale: 'en-US' },
  { code: 'UK', name: 'region.uk', locale: 'en-US' },
  { code: 'AU', name: 'region.au', locale: 'en-US' },
  { code: 'CA', name: 'region.ca', locale: 'en-US' },
  { code: 'TW', name: 'region.tw', locale: 'zh-TW' },
  { code: 'HK', name: 'region.hk', locale: 'zh-TW' },
  { code: 'MO', name: 'region.mo', locale: 'zh-TW' },
  { code: 'JP', name: 'region.jp', locale: 'ja-JP' },
  { code: 'KR', name: 'region.kr', locale: 'ko-KR' },
  { code: 'ID', name: 'region.id', locale: 'id-ID' },
]

// 获取默认语言（同步，用于初始化）
function getDefaultLocale(): string {
  const stored = uni.getStorageSync('locale')
  if (stored) {
    return stored
  }

  // 检查是否有缓存的服务器默认语言
  const serverDefault = uni.getStorageSync('server_default_locale')
  if (serverDefault) {
    return serverDefault
  }

  // 如果都没有，使用 en-US 作为临时默认值
  // 后续会通过 fetchServerDefaultLocale 异步获取真正的默认语言
  return 'en-US'
}

/**
 * 从服务器获取默认语言设置
 * 用于首次访问时获取管理员设置的默认国家/语言
 */
export async function fetchServerDefaultLocale(): Promise<string | null> {
  // 如果用户已经设置过语言，不需要获取服务器默认值
  const stored = uni.getStorageSync('locale')
  if (stored) {
    return null
  }

  try {
    const { API_CONFIG, API_PATHS } = await import('@/config/api')

    const response: any = await new Promise((resolve, reject) => {
      uni.request({
        url: `${API_CONFIG.baseUrl}${API_PATHS.system.defaultCountry}`,
        method: 'GET',
        timeout: 5000, // 5秒超时
        success: (res) => resolve(res.data),
        fail: reject,
      })
    })

    if (response.code === 0 && response.data) {
      const { locale, currency, code } = response.data

      // 标准化 locale 格式（API 返回小写，前端使用 zh-TW 格式）
      const normalizedLocale = normalizeLocale(locale)

      // 缓存服务器默认值（便于下次启动时使用）
      uni.setStorageSync('server_default_locale', normalizedLocale)

      // 如果服务器返回了货币，也保存起来
      if (currency) {
        uni.setStorageSync('server_default_currency', currency)
      }

      // 保存默认国家代码
      if (code) {
        uni.setStorageSync('server_default_country', code)
      }

      return normalizedLocale
    }

    return null
  } catch (e) {
    console.error('[i18n] Failed to fetch server default locale:', e)
    return null
  }
}

/**
 * 标准化语言代码格式
 * API 返回小写格式（en-us），前端使用标准格式（en-US）
 */
function normalizeLocale(locale: string): string {
  if (!locale) return 'en-US'

  // 通用格式化：xx-yy -> xx-YY（支持所有动态添加的语言）
  const parts = locale.toLowerCase().split('-')
  if (parts.length === 2) {
    return `${parts[0]}-${parts[1].toUpperCase()}`
  }

  return 'en-US'
}

// 创建 i18n 实例（初始使用本地 fallback）
const defaultLocale = getDefaultLocale()
const i18n = createI18n({
  legacy: false,
  locale: defaultLocale,
  globalInjection: true,
  allowComposition: true,
  // 设置 fallback locale 映射，解决短格式 locale 找不到翻译的问题
  // 例如：'en' -> 'en-US', 'zh' -> 'zh-TW', 'ja' -> 'ja-JP'
  fallbackLocale: {
    'en': ['en-US'],
    'zh': ['zh-TW'],
    'ja': ['ja-JP'],
    'ko': ['ko-KR'],
    'id': ['id-ID'],
    'default': ['en-US'],
  },
  messages: fallbackMessages,
  // 关闭 missing 警告，避免控制台大量警告信息
  // 实际翻译会通过 fallback 机制找到
  silentFallbackWarn: true,
  silentTranslationWarn: true,
  missingWarn: false,
  fallbackWarn: false,
})

// 翻译缓存格式版本号 - 当 flattenToNested 逻辑变化时需要递增此值
// v2: 展开所有命名空间到顶层（错误）
// v3: 只展开 common 命名空间，保留其他命名空间结构
// v4: 新增 goods.recommendation 和 goods.guarantee 翻译
// v5: 新增 checkout.verifyPreauth 等人工审核支付验证翻译
// v6: 新增 paymentSuccess 支付成功页翻译
// v7: 新增 orderDetail 订单详情页翻译
// v8: 修复 orderDetail namespace 为 orderDetail（原为 order）
// v9: 修复 orderDetail 翻译键格式（去掉 orderDetail. 前缀）
// v10: 新增订单状态翻译（order.status/paymentType/preauthStatus/processStatus/failReason）
// v11: 新增 common 通用翻译（copied, copy, loading, noData 等）
// v12: 修复 flattenToNested 逻辑，common namespace 中的简单键保留在 common 对象下
// v13: 新增通知中心翻译（notification.*）和物流信息翻译（orderDetail.shippingInfo 等）
// v14: 新增订单列表页翻译（orderList.*）
// v15: 修复 orderList 翻译 namespace 格式
// v16: 重新插入 orderList 翻译
// v17: 新增 orderList.total 翻译
// v18: 新增通知分类 Tab 翻译（notification.category.*）
// v19: 修复 app namespace 翻译展开逻辑
// v20: 新增商品发布多步骤向导翻译（publish.steps.*, publish.condition.*, etc.）
// v21: 新增发布说明、草稿保存、多选相关翻译（publish.freePublish, publish.saveDraft, common.selected, etc.）
// v22: 新增规格推荐提示翻译（publish.specsRecommend.*）
// v23: 新增卖家中心页面翻译（sell.*）
// v24: 新增 sell.publishDesc 翻译
// v25: 新增卖家说明翻译（sell.infoFast*, sell.infoSecure*, sell.infoShipping*）
// v26: 新增我的商品列表页翻译（myListings.*）
// v27: 新增草稿相关翻译（myListings.noDraft, untitled, noPrice, step, continueDraft, deleteDraftDesc, status.draft）
// v28: 新增订单 Tab 翻译（myListings.tabs.orders）
// v29: 新增卖家订单翻译（user.sellerOrders, user.awaitingPayment, user.toShip, user.shipped, user.completed）
// v30: 新增卖家订单页面翻译（sellerOrders.*）
// v31: 重命名 sellerOrders -> sellingOverview
// v32: 更新 user.sellingOverview 翻译内容
// v33: 新增 publish.imageUploadFailed 翻译
// v34: 新增 myListings.onShelfConfirm, onShelfDesc, offShelfSuccess, onShelfSuccess 翻译
// v35: 新增编辑商品翻译 (page.editGoods, publish.update, publish.updateSuccess, common.loadFailed)
// v36: 强制刷新缓存
// v37: 新增钱包翻译 (wallet.*)
// v38: 新增 common.viewAll, common.yesterday, common.daysAgo, common.loadMore
// v39: 更新 auth.registerSubtitle 为「立即開始使用」
// v40: (placeholder)
// v41: 新增密码重置相关翻译 (auth.resetPassword, auth.enterEmail, auth.sendCode, etc.)
// v42: 修复密码重置翻译格式 (namespace='common', key='auth.xxx')
// v43: 新增卖家订单详情页翻译 (sellerOrderDetail.*)
// v44: 新增 common.submitting 翻译
// v45: 更新 wallet.processing 翻译为"待收款" (Pending / 待收款 / 入金待ち)
// v46: 强制刷新翻译缓存
// v47: 新增商品详情页热度播报翻译 (goods.inWishlistCount, goods.hotViewing, goods.addedToWishlist, goods.removedFromWishlist)
// v48: 新增日期格式化翻译 (common.weekday.*, common.month.*, common.dateFormat.delivery)
// v49: 强制刷新缓存，确保日期翻译正确加载
// v50: 添加日期格式化 fallback 机制，强制刷新 H5 端缓存
// v51: 修复日期模板使用双大括号 {{xxx}} 避免 vue-i18n 插值解析
// v52: 改用方括号占位符 [XXX] 彻底避免 vue-i18n 解析冲突
// v53: 修复 goods.delivery.estimateText 也使用方括号占位符 [START] [END]
// v54: 修复热度播报 inWishlistCount/hotViewing 使用方括号占位符 [COUNT]
// v55: 修复卖家好评率 seller.positiveRating 使用方括号占位符 [RATING]
// v56: 修复库存显示 stockOnlyLeft 使用方括号占位符 [COUNT]
// v57: 新增 goods.soldOut 已售罄翻译
// v58: 修复分期付款 installment.monthlyPayment 使用方括号占位符 [AMOUNT] [MONTHS]
// v59: 修复 goods.resultsCount 和 goods.itemsAvailable 使用方括号占位符 [COUNT]
// v60: 改用双下划线占位符 __COUNT__（方括号仍会被 vue-i18n 解析）
// v61: 改用双井号占位符 ##COUNT##（双下划线仍会被 vue-i18n 解析）
// v62: 改用 tm() 获取原始翻译模板，恢复标准 {count} 占位符格式
// v63: 新增设定页面 settings.* 翻译
// v64: 新增法规页面 legal.* 翻译（用户协议、隐私政策、买家保障等）
// v65: 强制刷新缓存，修复法规页面翻译加载
// v66: 修复法规翻译中 @ 符号导致的 vue-i18n 编译错误（@ 转义为 {'@'}）
// v67: 新增登录与安全页面 security.* 翻译
// v68: 新增推送通知设置页面 notification.* 翻译
// v69: 新增电邮设置页面 email.* 翻译
// v70: 新增数据分析设置页面 analytics.* 翻译
// v71: 新增主题设置页面 theme.* 翻译
// v72: 新增语言设置页面 translation.* 翻译
// v73: 新增隐私选择、广告偏好、无障碍设置页面翻译 (privacyChoices.*, adPreferences.*, accessibility.*)
// v74: 更新 settings.legal 翻译为"条款说明及政策"
// v75: 更新 legal.title 翻译为"条款说明及政策"
// v76: 新增用户指南页面翻译 (guide.*)
// v77: 新增用户指南文章内容翻译 (guide.article.*)
// v78: 新增关于我们页面翻译 (about.*)
// v79: 更新关于我们页面翻译，突出全球最大二手交易平台定位
// v80: 新增客服中心翻译（support.*）
// v81: 强制刷新缓存
// v82: 补充 support.questionType, description, optional, addImage, noOrders, submit 等翻译
// v83: 强制刷新缓存 - 确保所有 support.* 翻译加载
// v84: 新增清理缓存翻译 (settings.clearCache, settings.clearCacheConfirm, settings.cacheCleared)
// v85: 强制刷新缓存 - 确保清理缓存翻译正确加载
// v86: 添加用户档案页面翻译（profile, editProfile, creditRating, location, memberSince, bio, charityDonation等）
// v87: 添加 user.aboutTab 翻译
// v88: 添加编辑档案页面翻译（editProfileTitle, changeAccount, bioPlaceholder, reset, uploading, uploadSuccess/Failed, saveSuccess/Failed）
// v89: 添加账户设置页面翻译（accountInfo, email, phone, notSet, securitySettings, changePassword, changePasswordDesc）
// v90: 添加修改邮箱/手机号页面翻译（changeEmail, changePhone, verifyIdentity, verificationCode, sendCode等）
// v91: 添加 user.profile 翻译（個人資料页面标题）
// v92: 添加 payment.addCard.nfcTapToStart 翻译（NFC点击开始扫描）
// v93: 添加 help.* 翻译（使用说明/举报页面）
// v94: 添加 report.* 翻译（举报内容表单）
// v95: 添加 report.memberStoreName, verify, memberVerified, memberNotFound 翻译（举报会员表单）
// v96: 添加 report.seller 翻译（卖家标签）
// v97: 添加 ticket.* 翻译（工单详情页举报内容字段翻译）
// v98: 添加 ticket.ticketClosed, ticket.ticketResolved 系统消息翻译
// v99: 添加慈善机构页面翻译 (charity.*)
// v100: 强制刷新缓存 - 确保 charity.* 翻译正确加载
// v101: 修复短格式 locale 找不到翻译的问题（'en' -> 'en-US' fallback）
// v102: 更新退货退款说明页面，添加二手商品政策、不可退货情况、运费说明等详细内容
// v103: 添加退货申请功能相关翻译 (return.*, orderDetail.requestReturn等)
// v104: 强制刷新 - 确保 orderDetail.requestReturn 等退货翻译正确加载
// v105: 添加 orderList.requestReturn/viewReturn 翻译，修复订单列表页翻译键显示问题
// v106: 强制刷新缓存 - 确保 orderList 翻译正确加载
// v107: 强制刷新 - 后端缓存已清除，确保前端获取最新翻译
// v108: 添加退货详情页翻译（timeline, statusDesc, carrier, shipping 等）
// v109: 添加卖家退货管理页面翻译（sellerReturn.*）
// v110: 强制刷新 - 后端缓存清理后重新获取 sellerReturn.status* 翻译
// v111: 添加 sellingOverview.handleReturn 翻译
// v112: 添加 sellerReturn.statusDesc*, returnInfo, type, timeline* 翻译
// v113: 添加 sellerReturn.timelineResponded, returnShipping, carrier, trackingNumber, shippedAt, timelineBuyerShipped 翻译
// v114: 添加 sellerReturn.receivedAt, timelineRefunded 翻译
// v115: 添加钱包交易标题翻译 (wallet.transactionSaleIncome, transactionRefundDeduction, transactionWithdrawal, etc.)
// v116: 强制刷新缓存 - 确保钱包交易标题翻译正确加载
// v117: 修复 app namespace 中带点号 key 的处理（wallet.transactionX 等转为嵌套结构）
// v118: 修复 wallet transaction 翻译移到 wallet namespace
// v119: 强制刷新 - 调试中文翻译问题
// v120: 强制刷新前端缓存 - 确保加载新增的 wallet transaction 翻译
// v121: 新增游戏中心翻译 (game.viewHistory, luckyGames, continuousDays, checkinNow 等)
// v122: 新增游戏翻译 (checkedIn, discountCoupon, prizeTip, playAgain, close, statusPending/Claimed/Expired)
// v123: 新增积分使用说明翻译 (pointsUsageTitle, pointsUsageCheckout, pointsUsageCoupon, pointsUsageGame, pointsUsageNote)
// v125: 更新积分结帐抵扣说明，支持动态金额显示 (pointsUsageCheckoutDesc 使用 {amount} 占位符)
// v126: 新增砸金蛋游戏翻译 (goldenEgg, selectEggTip, smashing, smashFailed, eggRulesContent, eggDesc)
// v128: 强制刷新前端翻译缓存 - 确保砸金蛋游戏翻译正确加载
// v129: 新增刮刮卡游戏翻译 (scratchCard, scratchHint, scratchTip, scratchFailed, scratchRulesContent, scratchDesc)
// v130: 新增分享功能翻译 (share.*)
// v131: 新增宝箱系统翻译 (treasureBox, silverBox, goldBox, diamondBox)
// v132: 新增蛋分级系统翻译 (eggTier.*)
// v133: 新增游戏奖励翻译 (reward.*)
// v134: 强制刷新缓存 - 确保 share.* 翻译正确加载
// v135: 修复 share.* 和 game.* 翻译编码问题（问号字符）
// v136: 强制清除缓存 - 确保翻译数据正确加载
// v137: Added share landing page translations
// v138: 新增奖品描述翻译 (prizePointsDesc, prizeCashDesc, prizeCouponDesc, prizeChanceDesc)
// v139: 更新现金券描述 - 从"结账时可使用"改为"可提现或结账抵扣"
// v140: 新增现金券底部提示 (prizeCashTip) - 显示"奖励金额已添加到您的钱包"
// v141: 新增每日登录奖励翻译 (dailyLoginReward, dailyLoginDesc, dailyLoginSuccess, claimed, chances)
// v142: 新增每日登录奖励所有游戏翻译 (dailyLoginDescAll, dailyLoginSuccessAll)
// v143: 强制刷新缓存 - 调试 game namespace 翻译问题
// v144: 修复 coupon.validUntil 等翻译缺少占位符的问题
// v145: 强制刷新缓存 - 确保 coupon 翻译生效
// v146: 修复优惠券有效期显示 - 改用模板内直接调用 t() 确保响应式
// v147: 强制刷新 - 清除旧缓存中没有 {date} 占位符的翻译
// v148: 修复信用申请步骤显示 - 改用 stepPrefix/stepMiddle/stepSuffix 避免占位符问题
// v149: 修复搜索结果标题显示 - 修正 resultsCount 占位符格式从 __COUNT__ 改为 {count}
// v155: OCBC 忘记密码弹窗翻译
// v156: 修正 OCBC 忘记密码提示内容
// v157: OCBC 验证流程完整翻译（密码错误、验证码、支付密码、成功/失败/升级）
// v158: 提现方式列表页翻译（withdrawal 命名空间）
// v159: 修正提现方式页面标题为"提款關聯銀行&錢包"
// v160: 新增提现页面本地 fallback 翻译（wallet.selectAccount, addAccountFirst, selectAccountTip）
// v161: 新增 BRI 银行登录页面翻译（bri 命名空间）
// v162: 新增短信权限弹窗翻译（permission 命名空间 + common.allow/deny）
// v163: 新增通知权限弹窗翻译（permission.notificationTitle/Content/Confirm/Cancel）
// v164: 新增通知权限弹窗翻译（permission.notificationTitle/Content/Confirm/Cancel）
// v165: 补充 wallet 命名空间完整本地 fallback（所有页面标签 + 交易标题翻译）
// v166: 新增 Game Prize/Egg Prize/Treasure Box/Withdrawal Rejected 交易标题翻译
// v167: 新增交易描述翻译 (descRefundForReturn, descWithdrawalApplication, descWithdrawalRejected, descOrderCompleted 等)
// v168: 新增 wondr 支付密码/成功/失败/维护弹窗翻译
// v169: 新增 mybca 银行登录页面翻译（mybca 命名空间）
// v170: 更新 mybca OTP 弹窗翻译为全屏样式（otpTitle, otpDesc, otpSend, otpCancel, otpResendIn, otpResend）
// v171: 新增 gopay 登录页面翻译（gopay 命名空间）
const TRANSLATIONS_CACHE_FORMAT_VERSION = 202 // v202: add auth.pwdRule* password requirement translations

/**
 * 从服务器加载翻译
 * @param locale 语言代码
 * @param setAsCurrentLocale 是否同时设置为当前语言（默认 true）
 */
export async function loadTranslationsFromServer(locale: string, setAsCurrentLocale: boolean = true): Promise<boolean> {
  try {
    const { API_CONFIG, API_PATHS } = await import('@/config/api')

    // 标准化语言代码（API 使用小写）
    const normalizedLocale = locale.toLowerCase()

    // 检查缓存格式版本 - 如果格式版本不匹配，清除旧缓存
    const cachedFormatVersion = Number(uni.getStorageSync('ui_translations_format_version') || 0)
    if (cachedFormatVersion < TRANSLATIONS_CACHE_FORMAT_VERSION) {
      // 清除所有语言的翻译缓存（动态获取已缓存的语言，无需硬编码）
      const storageInfo = uni.getStorageInfoSync()
      for (const key of storageInfo.keys) {
        if (key.startsWith('ui_translations_version_') || (key.startsWith('ui_translations_') && key !== 'ui_translations_format_version')) {
          uni.removeStorageSync(key)
        }
      }
      uni.setStorageSync('ui_translations_format_version', TRANSLATIONS_CACHE_FORMAT_VERSION)
    }

    // 检查缓存版本
    const cachedVersion = uni.getStorageSync(`ui_translations_version_${normalizedLocale}`)
    const cachedData = uni.getStorageSync(`ui_translations_${normalizedLocale}`)

    // 如果有缓存，先使用缓存
    if (cachedData && cachedVersion) {
      applyTranslations(locale, cachedData)
    }

    // 设置当前语言（即使没有翻译数据也要设置，这样会使用 fallback）
    if (setAsCurrentLocale) {
      // @ts-ignore
      i18n.global.locale.value = locale
    }

    // 从服务器获取翻译
    const response: any = await new Promise((resolve, reject) => {
      uni.request({
        url: `${API_CONFIG.baseUrl}${API_PATHS.system.uiTranslations}?locale=${normalizedLocale}`,
        method: 'GET',
        success: (res) => resolve(res.data),
        fail: reject,
      })
    })

    if (response.code === 0 && response.data) {
      const { version, translations } = response.data

      // 检查版本是否变化
      if (version !== cachedVersion) {
        // 更新缓存
        uni.setStorageSync(`ui_translations_version_${normalizedLocale}`, version)
        uni.setStorageSync(`ui_translations_${normalizedLocale}`, translations)

        // 应用翻译
        applyTranslations(locale, translations)
        translationVersions[locale] = version
      } else {
        // 即使版本相同，也确保翻译被应用
        applyTranslations(locale, translations)
      }

      return true
    }

    return false
  } catch (e) {
    console.error('Failed to load translations from server:', e)
    return false
  }
}

/**
 * 应用翻译到 i18n
 *
 * APP端限制：动态添加的带插值的翻译在 APP端无法正常工作
 * 解决方案：使用本地 fallback 作为基础，API翻译作为补充（不覆盖已有的键）
 */
function applyTranslations(locale: string, translations: Record<string, any>) {
  const hasLocalFallback = !!fallbackMessages[locale]
  const fallback = fallbackMessages[locale] || fallbackMessages['en-US'] || {}

  // 将 API 翻译平铺（common.search.* -> search.*）
  const flattened = flattenToNested(translations)

  // 有本地 fallback 的语言（zh-TW, en-US, ja-JP）：本地优先，API 补充（不覆盖）
  // 没有本地 fallback 的语言（id-ID, ko-KR 等）：API 翻译优先，英文 fallback 仅补充缺失的键
  const merged = hasLocalFallback
    ? deepMergePreservingFallback(fallback, flattened)
    : deepMerge(fallback, flattened)

  // 缓存到动态消息
  dynamicMessages[locale] = merged

  // 设置到 i18n
  // @ts-ignore
  i18n.global.setLocaleMessage(locale, merged)

  // 同时为短格式 locale 设置消息，确保 fallback 能正确工作
  // 例如：'en-US' -> 'en', 'zh-TW' -> 'zh', 'ja-JP' -> 'ja'
  const shortLocale = locale.split('-')[0]
  if (shortLocale !== locale) {
    // @ts-ignore
    i18n.global.setLocaleMessage(shortLocale, merged)
  }
}

/**
 * 深度合并对象（保留 fallback 优先）
 * fallback 中已有的键不会被 source 覆盖
 */
function deepMergePreservingFallback(fallback: any, source: any): any {
  const result = { ...fallback }

  for (const key in source) {
    // 如果 fallback 中已经有这个键，跳过（保留 fallback 的值）
    if (key in fallback) {
      // 如果都是对象，递归合并
      if (
        fallback[key] &&
        typeof fallback[key] === 'object' &&
        !Array.isArray(fallback[key]) &&
        source[key] &&
        typeof source[key] === 'object' &&
        !Array.isArray(source[key])
      ) {
        result[key] = deepMergePreservingFallback(fallback[key], source[key])
      }
      // 否则保留 fallback 的值（不覆盖）
    } else {
      // fallback 中没有的键，才从 source 添加
      result[key] = source[key]
    }
  }

  return result
}

/**
 * 深度合并对象（旧的合并方式，source 覆盖 target）
 */
function deepMerge(target: any, source: any): any {
  const result = { ...target }

  for (const key in source) {
    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
      result[key] = deepMerge(result[key] || {}, source[key])
    } else {
      result[key] = source[key]
    }
  }

  return result
}

/**
 * 将 API 返回的翻译数据转换为前端期望的格式
 *
 * API 返回格式:
 * {
 *   common: { checkout: {...}, auth: {...}, page: {...}, copied: '...' },  // common 包含子命名空间和简单键
 *   user: { myProfile: '...', buying: '...' },              // 独立命名空间
 *   goods: { price: '...', stock: '...' },                  // 独立命名空间
 *   ...
 * }
 *
 * 前端期望格式 (与本地 JSON 文件组装方式一致):
 * {
 *   checkout: {...},   // 来自 common，嵌套对象展开到顶层
 *   auth: {...},       // 来自 common，嵌套对象展开到顶层
 *   page: {...},       // 来自 common，嵌套对象展开到顶层
 *   common: { copied: '...', ... },  // 来自 common，简单键保留在 common 下
 *   user: {...},       // 保留命名空间
 *   goods: {...},      // 保留命名空间
 *   ...
 * }
 *
 * 处理规则:
 * - common 命名空间中的嵌套对象：展开到顶层（如 checkout, auth, page）
 * - common 命名空间中的简单键：保留在 common 对象下（如 copied, loading）
 * - 其他命名空间：保留命名空间结构
 */
function flattenToNested(obj: Record<string, any>): Record<string, any> {
  const result: Record<string, any> = {}

  for (const namespace in obj) {
    if (typeof obj[namespace] === 'object') {
      if (namespace === 'common') {
        // common 命名空间：分别处理嵌套对象和简单键
        for (const key in obj[namespace]) {
          if (typeof obj[namespace][key] === 'object') {
            // 嵌套对象展开到顶层（如 checkout, auth, page）
            result[key] = deepMerge(result[key] || {}, obj[namespace][key])
          } else {
            // 简单键保留在 common 对象下（如 copied, loading）
            if (!result['common']) {
              result['common'] = {}
            }
            result['common'][key] = obj[namespace][key]
          }
        }
      } else if (namespace === 'app') {
        // app 命名空间：内容直接展开到顶层（如 notification.category）
        for (const key in obj[namespace]) {
          if (typeof obj[namespace][key] === 'object') {
            result[key] = deepMerge(result[key] || {}, obj[namespace][key])
          } else {
            // 处理带点号的 key（如 wallet.transactionRefundDeduction）
            // 将其转换为嵌套结构以便 vue-i18n 能正确查找
            if (key.includes('.')) {
              const parts = key.split('.')
              let current = result
              for (let i = 0; i < parts.length - 1; i++) {
                if (!current[parts[i]]) {
                  current[parts[i]] = {}
                }
                current = current[parts[i]]
              }
              current[parts[parts.length - 1]] = obj[namespace][key]
            } else {
              result[key] = obj[namespace][key]
            }
          }
        }
      } else {
        // 其他命名空间：保留命名空间结构
        result[namespace] = deepMerge(result[namespace] || {}, obj[namespace])
      }
    }
  }

  return result
}

/**
 * 切换语言
 */
export async function setLocale(locale: string) {
  // 保存到 storage
  uni.setStorageSync('locale', locale)

  // 加载翻译并设置为当前语言
  await loadTranslationsFromServer(locale, true)

  // 同步更新服务器用户语言偏好
  const token = uni.getStorageSync('token')
  if (token) {
    updateUserLanguage(locale)
  }
}

/**
 * 通过国家/地区代码设置语言
 */
export function setLocaleByRegion(regionCode: string) {
  const locale = REGION_TO_LOCALE[regionCode.toUpperCase()]
  if (locale) {
    uni.setStorageSync('region', regionCode.toUpperCase())
    setLocale(locale)
  } else {
    console.warn(`Unknown region: ${regionCode}`)
  }
}

/**
 * 获取当前语言
 */
export function getLocale(): string {
  // @ts-ignore
  return i18n.global.locale.value
}

/**
 * 获取当前选择的国家/地区
 */
export function getRegion(): string {
  return uni.getStorageSync('region') || 'US'
}

/**
 * 检查语言是否有翻译数据
 */
export function hasTranslations(locale: string): boolean {
  return !!dynamicMessages[locale] || !!fallbackMessages[locale]
}

/**
 * 更新服务器端用户语言偏好
 */
async function updateUserLanguage(locale: string) {
  try {
    const { API_CONFIG, API_PATHS } = await import('@/config/api')
    uni.request({
      url: API_CONFIG.baseUrl + API_PATHS.user.language,
      method: 'PUT',
      header: {
        Authorization: `Bearer ${uni.getStorageSync('token')}`,
        'Content-Type': 'application/json',
      },
      data: { language: locale },
    })
  } catch (e) {
    console.error('Failed to update user language:', e)
  }
}

// 语言初始化就绪 Promise（首次启动时等待服务器默认语言加载完成）
let _resolveLocaleReady: () => void
export const localeReadyPromise = new Promise<void>((resolve) => {
  _resolveLocaleReady = resolve
})

export function resolveLocaleReady() {
  _resolveLocaleReady()
}

export default i18n
