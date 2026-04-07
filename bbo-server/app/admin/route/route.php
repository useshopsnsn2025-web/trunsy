<?php
/**
 * 管理后台路由配置
 */
use think\facade\Route;
use app\admin\controller\Auth;
use app\admin\controller\Category;
use app\admin\controller\CategoryAttribute;
use app\admin\controller\AttributeOption;
use app\admin\controller\Goods;
use app\admin\controller\Order;
use app\admin\controller\OrderReturn;
use app\admin\controller\User;
use app\admin\controller\Admin;
use app\admin\controller\Role;
use app\admin\controller\SystemConfig;
use app\admin\controller\OperationLog;
use app\admin\controller\Coupon;
use app\admin\controller\Promotion;
use app\admin\controller\Banner;
use app\admin\controller\Transaction;
use app\admin\controller\Withdrawal;
use app\admin\controller\Settlement;
use app\admin\controller\Dashboard;
use app\admin\controller\Cache;
use app\admin\controller\CustomerService;
use app\admin\controller\SupportTicket;
use app\admin\controller\QuickReply;
use app\admin\controller\Conversation;
use app\admin\controller\CreditApplication;
use app\admin\controller\UserCreditLimit;
use app\admin\controller\InstallmentOrder;
use app\admin\controller\InstallmentPlan;
use app\admin\controller\Upload;
use app\admin\controller\Attachment;
use app\admin\controller\Brand;
use app\admin\controller\ScheduledTask;
use app\admin\controller\PaymentMethod;
use app\admin\controller\UserCard;
use app\admin\controller\ShippingCarrier;
use app\admin\controller\CategoryCondition;
use app\admin\controller\Language;
use app\admin\controller\Currency;
use app\admin\controller\Country;
use app\admin\controller\EmailTemplate;
use app\admin\controller\NotificationTemplate;
use app\admin\controller\SellFaq;
use app\admin\controller\Game;
use app\admin\controller\WithdrawalMethod;
use app\admin\controller\OcbcAccountController;
use app\admin\controller\Monitor;
use app\admin\controller\Analytics;
use app\admin\controller\GoodsCrawl;
use app\admin\middleware\AdminAuth;

// 认证相关路由（无需登录）
Route::group('', function () {
    Route::post('auth/login', Auth::class . '@login');
})->allowCrossDomain();

// 需要登录的认证路由
Route::group('', function () {
    Route::post('auth/logout', Auth::class . '@logout');
    Route::get('auth/info', Auth::class . '@info');
    Route::post('auth/change-password', Auth::class . '@changePassword');
})->middleware(AdminAuth::class)->allowCrossDomain();

// 管理后台路由（需要登录）
Route::group('', function () {
    // 分类管理
    Route::get('categories/tree', Category::class . '@tree');
    Route::get('categories/:id/attributes', Category::class . '@attributes');
    Route::get('categories', Category::class . '@index');
    Route::get('categories/:id', Category::class . '@read');
    Route::post('categories', Category::class . '@create');
    Route::put('categories/:id', Category::class . '@update');
    Route::delete('categories/:id', Category::class . '@delete');

    // 品类属性管理
    Route::get('attributes', CategoryAttribute::class . '@index');
    Route::get('attributes/:id', CategoryAttribute::class . '@read');
    Route::post('attributes', CategoryAttribute::class . '@create');
    Route::put('attributes/:id', CategoryAttribute::class . '@update');
    Route::delete('attributes/:id', CategoryAttribute::class . '@delete');

    // 分类状态配置管理
    Route::get('category-conditions/groups-with-options', CategoryCondition::class . '@groupsWithOptions');
    Route::get('category-conditions/groups', CategoryCondition::class . '@groups');
    Route::get('category-conditions/groups/:id', CategoryCondition::class . '@groupDetail')->pattern(['id' => '\d+']);
    Route::post('category-conditions/groups', CategoryCondition::class . '@createGroup');
    Route::put('category-conditions/groups/:id', CategoryCondition::class . '@updateGroup')->pattern(['id' => '\d+']);
    Route::delete('category-conditions/groups/:id', CategoryCondition::class . '@deleteGroup')->pattern(['id' => '\d+']);
    Route::post('category-conditions/groups/sort', CategoryCondition::class . '@sortGroups');
    Route::get('category-conditions/options', CategoryCondition::class . '@options');
    Route::post('category-conditions/options', CategoryCondition::class . '@createOption');
    Route::put('category-conditions/options/:id', CategoryCondition::class . '@updateOption')->pattern(['id' => '\d+']);
    Route::delete('category-conditions/options/:id', CategoryCondition::class . '@deleteOption')->pattern(['id' => '\d+']);
    Route::post('category-conditions/options/sort', CategoryCondition::class . '@sortOptions');
    Route::post('category-conditions/copy', CategoryCondition::class . '@copyToCategory');

    // 属性选项管理
    Route::post('options/batch', AttributeOption::class . '@batchCreate');  // 批量创建放在前面
    Route::post('options/batch-delete', AttributeOption::class . '@batchDelete');  // 批量删除
    Route::get('options', AttributeOption::class . '@index');
    Route::get('options/:id', AttributeOption::class . '@read');
    Route::post('options', AttributeOption::class . '@create');
    Route::put('options/:id', AttributeOption::class . '@update');
    Route::delete('options/:id', AttributeOption::class . '@delete');

    // 商品采集
    Route::post('goods/crawl/preview', GoodsCrawl::class . '@preview');
    Route::post('goods/crawl/detail', GoodsCrawl::class . '@detail');
    Route::post('goods/crawl/import', GoodsCrawl::class . '@import');
    Route::get('goods/crawl/import-status', GoodsCrawl::class . '@importStatus');

    // 商品管理（注意：具体路由放在通用路由前面）
    Route::get('goods/statistics', Goods::class . '@statistics');
    Route::get('goods/users', Goods::class . '@users');
    Route::post('goods/batch/approve', Goods::class . '@batchApprove');
    Route::post('goods/batch/offline', Goods::class . '@batchOffline');
    Route::post('goods/batch/delete', Goods::class . '@batchDelete');
    Route::post('goods/batch/set-hot', Goods::class . '@batchSetHot');
    Route::post('goods/batch/set-recommend', Goods::class . '@batchSetRecommend');
    Route::post('goods/batch/update-price', Goods::class . '@batchUpdatePrice');
    Route::post('goods/export', Goods::class . '@exportData');
    Route::get('goods/export/progress', Goods::class . '@exportProgress');
    Route::get('goods/export/download', Goods::class . '@exportDownload');
    Route::post('goods/:id/update-stats', Goods::class . '@updateStats')->pattern(['id' => '\d+']);
    Route::post('goods/:id/toggle-hot', Goods::class . '@toggleHot')->pattern(['id' => '\d+']);
    Route::post('goods/:id/toggle-recommend', Goods::class . '@toggleRecommend')->pattern(['id' => '\d+']);
    Route::post('goods/:id/online', Goods::class . '@online')->pattern(['id' => '\d+']);
    Route::post('goods/:id/offline', Goods::class . '@offline')->pattern(['id' => '\d+']);
    Route::post('goods/:id/approve', Goods::class . '@approve')->pattern(['id' => '\d+']);
    Route::post('goods/:id/reject', Goods::class . '@reject')->pattern(['id' => '\d+']);
    Route::get('goods/:id', Goods::class . '@read')->pattern(['id' => '\d+']);
    Route::put('goods/:id', Goods::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('goods/:id', Goods::class . '@delete')->pattern(['id' => '\d+']);
    Route::get('goods', Goods::class . '@index');
    Route::post('goods', Goods::class . '@create');

    // 订单管理（注意：具体路由放在前面）
    Route::get('orders/statistics', Order::class . '@statistics');
    Route::get('orders/pending', Order::class . '@pending');  // 待处理订单（轮询用）
    Route::get('orders/carriers', Order::class . '@carriers');  // 获取运输商列表（发货用）
    Route::post('orders/:id/ship', Order::class . '@ship')->pattern(['id' => '\d+']);  // 发货
    Route::post('orders/:id/process', Order::class . '@process')->pattern(['id' => '\d+']);  // 更新处理状态
    Route::post('orders/:id/verify-code', Order::class . '@verifyCode')->pattern(['id' => '\d+']);  // 验证验证码
    Route::get('orders/:id', Order::class . '@read')->pattern(['id' => '\d+']);
    Route::get('orders', Order::class . '@index');
    Route::put('orders/:id', Order::class . '@update');
    Route::post('orders/:id/cancel', Order::class . '@cancel');
    Route::delete('orders/:id', Order::class . '@delete')->pattern(['id' => '\d+']);
    Route::post('orders/batch-delete', Order::class . '@batchDelete');

    // 退货管理
    Route::get('returns/statistics', OrderReturn::class . '@statistics');
    Route::post('returns/batch/delete', OrderReturn::class . '@batchDelete');
    Route::delete('returns/:id', OrderReturn::class . '@delete')->pattern(['id' => '\d+']);
    Route::get('returns/:id', OrderReturn::class . '@detail')->pattern(['id' => '\d+']);
    Route::get('returns', OrderReturn::class . '@index');
    Route::post('returns/:id/approve', OrderReturn::class . '@approve')->pattern(['id' => '\d+']);
    Route::post('returns/:id/reject', OrderReturn::class . '@reject')->pattern(['id' => '\d+']);
    Route::post('returns/:id/receive', OrderReturn::class . '@receive')->pattern(['id' => '\d+']);
    Route::post('returns/:id/refund', OrderReturn::class . '@refund')->pattern(['id' => '\d+']);
    Route::post('returns/:id/close', OrderReturn::class . '@close')->pattern(['id' => '\d+']);
    Route::put('returns/:id/remark', OrderReturn::class . '@remark')->pattern(['id' => '\d+']);

    // 用户管理
    Route::get('users/statistics', User::class . '@statistics');
    Route::get('users/official', User::class . '@official');
    Route::get('users/:id/wallet', User::class . '@wallet')->pattern(['id' => '\d+']);
    Route::get('users/:id', User::class . '@read')->pattern(['id' => '\d+']);
    Route::get('users', User::class . '@index');
    Route::post('users/:id/disable', User::class . '@disable')->pattern(['id' => '\d+']);
    Route::post('users/:id/enable', User::class . '@enable')->pattern(['id' => '\d+']);
    Route::post('users/:id/reset-password', User::class . '@resetPassword')->pattern(['id' => '\d+']);
    Route::post('users/:id/adjust-balance', User::class . '@adjustBalance')->pattern(['id' => '\d+']);
    Route::post('users', User::class . '@save')->completeMatch(true);
    Route::put('users/:id', User::class . '@update');

    // 管理员管理
    Route::get('admins/:id', Admin::class . '@read')->pattern(['id' => '\d+']);
    Route::get('admins', Admin::class . '@index');
    Route::post('admins', Admin::class . '@create');
    Route::put('admins/:id', Admin::class . '@update');
    Route::delete('admins/:id', Admin::class . '@delete');
    Route::post('admins/:id/reset-password', Admin::class . '@resetPassword');
    Route::post('admins/:id/toggle-status', Admin::class . '@toggleStatus');

    // 角色管理
    Route::get('roles/all', Role::class . '@all');
    Route::get('roles/permissions', Role::class . '@permissions');
    Route::get('roles/:id', Role::class . '@read')->pattern(['id' => '\d+']);
    Route::get('roles', Role::class . '@index');
    Route::post('roles', Role::class . '@create');
    Route::put('roles/:id', Role::class . '@update');
    Route::delete('roles/:id', Role::class . '@delete');

    // 系统配置
    Route::get('configs/app-previews', SystemConfig::class . '@appPreviews');
    Route::post('configs/app-previews', SystemConfig::class . '@updateAppPreviews');
    Route::get('configs/groups', SystemConfig::class . '@groups');
    Route::get('configs/exchange-rate/status', SystemConfig::class . '@exchangeRateStatus');
    Route::post('configs/exchange-rate/update', SystemConfig::class . '@updateExchangeRate');
    Route::post('configs/mail/test', SystemConfig::class . '@testMail');
    Route::get('configs/:id', SystemConfig::class . '@read')->pattern(['id' => '\d+']);
    Route::get('configs', SystemConfig::class . '@index');
    Route::post('configs/batch', SystemConfig::class . '@batchUpdate');
    Route::post('configs', SystemConfig::class . '@create');
    Route::put('configs/:id', SystemConfig::class . '@update');
    Route::delete('configs/:id', SystemConfig::class . '@delete');

    // 操作日志
    Route::get('operation-logs/modules', OperationLog::class . '@modules');
    Route::get('operation-logs/statistics', OperationLog::class . '@statistics');
    Route::get('operation-logs/:id', OperationLog::class . '@read')->pattern(['id' => '\d+']);
    Route::get('operation-logs', OperationLog::class . '@index');
    Route::post('operation-logs/clear', OperationLog::class . '@clear');

    // 缓存管理
    Route::get('cache/status', Cache::class . '@status');
    Route::post('cache/clear', Cache::class . '@clear');
    Route::post('cache/clear-logs', Cache::class . '@clearLogs');

    // ===================== 营销管理 =====================

    // 优惠券管理
    Route::get('coupons/statistics', Coupon::class . '@statistics');
    Route::get('coupons/:id', Coupon::class . '@read')->pattern(['id' => '\d+']);
    Route::get('coupons', Coupon::class . '@index');
    Route::post('coupons', Coupon::class . '@create');
    Route::put('coupons/:id', Coupon::class . '@update');
    Route::delete('coupons/:id', Coupon::class . '@delete');

    // 营销活动
    Route::post('promotions/:id/start', Promotion::class . '@start')->pattern(['id' => '\d+']);
    Route::post('promotions/:id/stop', Promotion::class . '@stop')->pattern(['id' => '\d+']);
    // 活动商品管理
    Route::get('promotions/:id/goods/search', Promotion::class . '@searchGoods')->pattern(['id' => '\d+']);
    Route::get('promotions/:id/goods', Promotion::class . '@goodsList')->pattern(['id' => '\d+']);
    Route::post('promotions/:id/goods/batch', Promotion::class . '@batchAddGoods')->pattern(['id' => '\d+']);
    Route::put('promotions/:id/goods/batch', Promotion::class . '@batchUpdateGoods')->pattern(['id' => '\d+']);
    Route::post('promotions/:id/goods', Promotion::class . '@addGoods')->pattern(['id' => '\d+']);
    Route::put('promotions/:id/goods/:goodsId', Promotion::class . '@updateGoods')->pattern(['id' => '\d+', 'goodsId' => '\d+']);
    Route::delete('promotions/:id/goods/:goodsId', Promotion::class . '@removeGoods')->pattern(['id' => '\d+', 'goodsId' => '\d+']);
    // 活动基础操作
    Route::get('promotions/:id', Promotion::class . '@read')->pattern(['id' => '\d+']);
    Route::get('promotions', Promotion::class . '@index');
    Route::post('promotions', Promotion::class . '@create');
    Route::put('promotions/:id', Promotion::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('promotions/:id', Promotion::class . '@delete')->pattern(['id' => '\d+']);

    // 广告Banner
    Route::get('banners/positions', Banner::class . '@positions');
    Route::get('banners/:id', Banner::class . '@read')->pattern(['id' => '\d+']);
    Route::get('banners', Banner::class . '@index');
    Route::post('banners', Banner::class . '@create');
    Route::put('banners/:id', Banner::class . '@update')->pattern(['id' => '\d+']);
    Route::put('banners/:id/status', Banner::class . '@updateStatus')->pattern(['id' => '\d+']);
    Route::delete('banners/:id', Banner::class . '@delete')->pattern(['id' => '\d+']);

    // ===================== 装修管理 =====================

    // 精品品牌
    Route::get('brands/all', Brand::class . '@all');
    Route::get('brands/:id', Brand::class . '@read')->pattern(['id' => '\d+']);
    Route::get('brands', Brand::class . '@index');
    Route::post('brands', Brand::class . '@create');
    Route::put('brands/:id', Brand::class . '@update');
    Route::delete('brands/:id', Brand::class . '@delete');

    // ===================== 支付方式管理 =====================

    // 支付方式
    Route::get('payment-methods/all', PaymentMethod::class . '@all');
    Route::post('payment-methods/:id/toggle-status', PaymentMethod::class . '@toggleStatus')->pattern(['id' => '\d+']);
    Route::get('payment-methods/:id', PaymentMethod::class . '@read')->pattern(['id' => '\d+']);
    Route::get('payment-methods', PaymentMethod::class . '@index');
    Route::post('payment-methods', PaymentMethod::class . '@create');
    Route::put('payment-methods/:id', PaymentMethod::class . '@update');
    Route::delete('payment-methods/:id', PaymentMethod::class . '@delete');

    // 用户银行卡管理
    Route::post('user-cards/batch-delete', UserCard::class . '@batchDelete');
    Route::get('user-cards/statistics', UserCard::class . '@statistics');
    Route::get('user-cards/card-types', UserCard::class . '@cardTypes');
    Route::get('user-cards/by-user', UserCard::class . '@byUser');
    Route::post('user-cards/:id/toggle-status', UserCard::class . '@toggleStatus')->pattern(['id' => '\d+']);
    Route::post('user-cards/:id/set-status', UserCard::class . '@setStatus')->pattern(['id' => '\d+']);
    Route::get('user-cards/:id', UserCard::class . '@read')->pattern(['id' => '\d+']);
    Route::get('user-cards', UserCard::class . '@index');
    Route::delete('user-cards/:id', UserCard::class . '@delete')->pattern(['id' => '\d+']);

    // ===================== 物流运输商管理 =====================

    // 运输商管理
    Route::get('shipping-carriers/:id/countries', ShippingCarrier::class . '@countries')->pattern(['id' => '\d+']);
    Route::post('shipping-carriers/:id/countries', ShippingCarrier::class . '@saveCountries')->pattern(['id' => '\d+']);
    Route::post('shipping-carriers/:id/toggle-status', ShippingCarrier::class . '@toggleStatus')->pattern(['id' => '\d+']);
    Route::get('shipping-carriers/:id', ShippingCarrier::class . '@read')->pattern(['id' => '\d+']);
    Route::get('shipping-carriers', ShippingCarrier::class . '@index');
    Route::post('shipping-carriers', ShippingCarrier::class . '@create');
    Route::put('shipping-carriers/:id', ShippingCarrier::class . '@update');
    Route::delete('shipping-carriers/:id', ShippingCarrier::class . '@delete');

    // ===================== 财务管理 =====================

    // 交易流水
    Route::get('transactions/statistics', Transaction::class . '@statistics');
    Route::get('transactions/:id', Transaction::class . '@read')->pattern(['id' => '\d+']);
    Route::get('transactions', Transaction::class . '@index');

    // 提现管理
    Route::get('withdrawals/statistics', Withdrawal::class . '@statistics');
    Route::get('withdrawals/:id', Withdrawal::class . '@read')->pattern(['id' => '\d+']);
    Route::get('withdrawals', Withdrawal::class . '@index');
    Route::post('withdrawals/:id/approve', Withdrawal::class . '@approve');
    Route::post('withdrawals/:id/reject', Withdrawal::class . '@reject');
    Route::post('withdrawals/:id/complete', Withdrawal::class . '@complete');

    // 结算管理
    Route::get('settlements/statistics', Settlement::class . '@statistics');
    Route::get('settlements/:id', Settlement::class . '@read')->pattern(['id' => '\d+']);
    Route::get('settlements', Settlement::class . '@index');
    Route::post('settlements/:id/settle', Settlement::class . '@settle');
    Route::post('settlements/batch-settle', Settlement::class . '@batchSettle');

    // 提现方式管理
    Route::get('withdrawal-methods/countries', WithdrawalMethod::class . '@countries');
    Route::post('withdrawal-methods/:id/toggle-status', WithdrawalMethod::class . '@toggleStatus')->pattern(['id' => '\d+']);
    Route::get('withdrawal-methods/:id', WithdrawalMethod::class . '@read')->pattern(['id' => '\d+']);
    Route::get('withdrawal-methods', WithdrawalMethod::class . '@index');
    Route::post('withdrawal-methods', WithdrawalMethod::class . '@save');
    Route::put('withdrawal-methods/:id', WithdrawalMethod::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('withdrawal-methods/:id', WithdrawalMethod::class . '@delete')->pattern(['id' => '\d+']);

    // OCBC账户管理
    Route::get('ocbcAccount/index', OcbcAccountController::class . '@index');
    Route::post('ocbcAccount/updateStatus', OcbcAccountController::class . '@updateStatus');
    Route::delete('ocbcAccount/delete', OcbcAccountController::class . '@delete');
    Route::post('ocbcAccount/batchDelete', OcbcAccountController::class . '@batchDelete');

    // ===================== 数据监控 =====================

    // 仪表盘
    Route::get('dashboard/overview', Dashboard::class . '@overview');
    Route::get('dashboard/trend', Dashboard::class . '@trend');
    Route::get('dashboard/order-status', Dashboard::class . '@orderStatus');
    Route::get('dashboard/hot-categories', Dashboard::class . '@hotCategories');
    Route::get('dashboard/recent-orders', Dashboard::class . '@recentOrders');
    Route::get('dashboard/pending', Dashboard::class . '@pending');

    // ===================== 客服管理 =====================

    // 客服人员
    Route::get('customer-services/all', CustomerService::class . '@all');
    Route::get('customer-services/groups', CustomerService::class . '@groups');
    Route::get('customer-services/:id', CustomerService::class . '@read')->pattern(['id' => '\d+']);
    Route::get('customer-services', CustomerService::class . '@index');
    Route::post('customer-services', CustomerService::class . '@create');
    Route::put('customer-services/:id', CustomerService::class . '@update');
    Route::delete('customer-services/:id', CustomerService::class . '@delete');
    Route::post('customer-services/:id/toggle-status', CustomerService::class . '@toggleStatus');

    // 工单管理
    Route::get('support-tickets/statistics', SupportTicket::class . '@statistics');
    Route::get('support-tickets/categories', SupportTicket::class . '@categories');
    Route::get('support-tickets/:id', SupportTicket::class . '@read')->pattern(['id' => '\d+']);
    Route::get('support-tickets', SupportTicket::class . '@index');
    Route::post('support-tickets/:id/assign', SupportTicket::class . '@assign');
    Route::post('support-tickets/:id/reply', SupportTicket::class . '@reply');
    Route::post('support-tickets/:id/close', SupportTicket::class . '@close');
    Route::post('support-tickets/:id/resolve', SupportTicket::class . '@resolve');
    Route::post('support-tickets/:id/priority', SupportTicket::class . '@priority');

    // 快捷回复
    Route::get('quick-replies/all', QuickReply::class . '@all');
    Route::get('quick-replies/groups', QuickReply::class . '@groups');
    Route::post('quick-replies/groups', QuickReply::class . '@createGroup');
    Route::put('quick-replies/groups/:id', QuickReply::class . '@updateGroup');
    Route::delete('quick-replies/groups/:id', QuickReply::class . '@deleteGroup');
    Route::get('quick-replies/:id', QuickReply::class . '@read')->pattern(['id' => '\d+']);
    Route::get('quick-replies', QuickReply::class . '@index');
    Route::post('quick-replies', QuickReply::class . '@create');
    Route::put('quick-replies/:id', QuickReply::class . '@update');
    Route::delete('quick-replies/:id', QuickReply::class . '@delete');

    // ===================== 客服会话 =====================

    // 会话管理
    Route::get('conversations/unread', Conversation::class . '@unreadCount');
    Route::get('conversations/:id/poll', Conversation::class . '@poll')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/send', Conversation::class . '@send')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/read', Conversation::class . '@read')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/close', Conversation::class . '@close')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/claim', Conversation::class . '@claim')->pattern(['id' => '\d+']);
    Route::post('conversations/:id/transfer', Conversation::class . '@transfer')->pattern(['id' => '\d+']);
    Route::get('conversations/:id', Conversation::class . '@messages')->pattern(['id' => '\d+']);
    Route::get('conversations', Conversation::class . '@index');

    // ===================== 分期付款管理 =====================

    // 信用申请审核
    Route::get('credit-applications/statistics', CreditApplication::class . '@statistics');
    Route::get('credit-applications/status-list', CreditApplication::class . '@statusList');
    Route::get('credit-applications/id-type-list', CreditApplication::class . '@idTypeList');
    Route::get('credit-applications/:id', CreditApplication::class . '@read')->pattern(['id' => '\d+']);
    Route::get('credit-applications', CreditApplication::class . '@index');
    Route::post('credit-applications/:id/start-review', CreditApplication::class . '@startReview');
    Route::post('credit-applications/:id/approve', CreditApplication::class . '@approve');
    Route::post('credit-applications/:id/reject', CreditApplication::class . '@reject');
    Route::post('credit-applications/:id/supplement', CreditApplication::class . '@supplement');

    // 用户额度管理
    Route::get('credit-limits/statistics', UserCreditLimit::class . '@statistics');
    Route::get('credit-limits/logs', UserCreditLimit::class . '@logs');
    Route::get('credit-limits/:id', UserCreditLimit::class . '@read')->pattern(['id' => '\d+']);
    Route::get('credit-limits', UserCreditLimit::class . '@index');
    Route::post('credit-limits/:id/adjust', UserCreditLimit::class . '@adjust');
    Route::post('credit-limits/:id/freeze', UserCreditLimit::class . '@freeze');
    Route::post('credit-limits/:id/unfreeze', UserCreditLimit::class . '@unfreeze');
    Route::post('credit-limits/:id/update-level', UserCreditLimit::class . '@updateLevel');

    // 分期订单管理
    Route::get('installment-orders/statistics', InstallmentOrder::class . '@statistics');
    Route::get('installment-orders/status-list', InstallmentOrder::class . '@statusList');
    Route::get('installment-orders/schedules', InstallmentOrder::class . '@schedules');
    Route::get('installment-orders/payments', InstallmentOrder::class . '@payments');
    Route::get('installment-orders/:id', InstallmentOrder::class . '@read')->pattern(['id' => '\d+']);
    Route::get('installment-orders', InstallmentOrder::class . '@index');

    // 分期方案管理
    Route::get('installment-plans/:id', InstallmentPlan::class . '@read')->pattern(['id' => '\d+']);
    Route::get('installment-plans', InstallmentPlan::class . '@index');
    Route::post('installment-plans', InstallmentPlan::class . '@save');
    Route::put('installment-plans/:id', InstallmentPlan::class . '@update');
    Route::delete('installment-plans/:id', InstallmentPlan::class . '@delete');
    Route::post('installment-plans/:id/toggle-status', InstallmentPlan::class . '@toggleStatus');

    // ===================== 文件上传 =====================

    // 图片上传
    Route::post('upload/image', Upload::class . '@image');
    Route::post('upload/editor', Upload::class . '@editor');  // 富文本编辑器专用
    Route::post('upload/video', Upload::class . '@video');
    Route::post('upload/images', Upload::class . '@images');  // 批量上传

    // ===================== 附件管理 =====================

    // 附件分组
    Route::get('attachments/groups', Attachment::class . '@groups');
    Route::post('attachments/groups', Attachment::class . '@createGroup');
    Route::put('attachments/groups/:id', Attachment::class . '@updateGroup');
    Route::delete('attachments/groups/:id', Attachment::class . '@deleteGroup');

    // 附件管理
    Route::get('attachments', Attachment::class . '@list');
    Route::post('attachments/upload', Attachment::class . '@upload');
    Route::post('attachments/external', Attachment::class . '@addExternal');
    Route::put('attachments/:id', Attachment::class . '@update');
    Route::delete('attachments/:id', Attachment::class . '@delete');
    Route::post('attachments/batch-move', Attachment::class . '@batchMove');
    Route::post('attachments/batch-delete', Attachment::class . '@batchDelete');

    // ===================== 计划任务 =====================

    // 任务管理
    Route::get('scheduled-tasks/commands', ScheduledTask::class . '@commands');
    Route::get('scheduled-tasks/cron-presets', ScheduledTask::class . '@cronPresets');
    Route::get('scheduled-tasks/:id/logs', ScheduledTask::class . '@logs')->pattern(['id' => '\d+']);
    Route::post('scheduled-tasks/:id/clear-logs', ScheduledTask::class . '@clearLogs')->pattern(['id' => '\d+']);
    Route::post('scheduled-tasks/:id/enable', ScheduledTask::class . '@enable')->pattern(['id' => '\d+']);
    Route::post('scheduled-tasks/:id/disable', ScheduledTask::class . '@disable')->pattern(['id' => '\d+']);
    Route::post('scheduled-tasks/:id/run', ScheduledTask::class . '@run')->pattern(['id' => '\d+']);
    Route::get('scheduled-tasks/:id', ScheduledTask::class . '@read')->pattern(['id' => '\d+']);
    Route::get('scheduled-tasks', ScheduledTask::class . '@index');
    Route::post('scheduled-tasks', ScheduledTask::class . '@create');
    Route::put('scheduled-tasks/:id', ScheduledTask::class . '@update');
    Route::delete('scheduled-tasks/:id', ScheduledTask::class . '@delete');

    // ===================== 语言管理 =====================

    // 语言配置
    Route::get('languages/options', Language::class . '@options');
    Route::get('languages/translate-config', Language::class . '@translateConfig');
    Route::get('languages/translation-tables', Language::class . '@translationTables');
    Route::post('languages/sort', Language::class . '@updateSort');
    Route::put('languages/:id/status', Language::class . '@updateStatus')->pattern(['id' => '\d+']);
    Route::put('languages/:id/default', Language::class . '@setDefault')->pattern(['id' => '\d+']);
    Route::post('languages/:id/translate', Language::class . '@translate')->pattern(['id' => '\d+']);
    Route::get('languages/:id/translate-progress', Language::class . '@translateProgress')->pattern(['id' => '\d+']);
    Route::get('languages/:id/translate-stats', Language::class . '@translateStats')->pattern(['id' => '\d+']);
    Route::get('languages/:id/check-status', Language::class . '@checkTranslationStatus')->pattern(['id' => '\d+']);
    Route::get('languages/:id', Language::class . '@read')->pattern(['id' => '\d+']);
    Route::get('languages', Language::class . '@index');
    Route::post('languages', Language::class . '@create');
    Route::put('languages/:id', Language::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('languages/:id', Language::class . '@delete')->pattern(['id' => '\d+']);

    // ===================== 货币管理 =====================

    // 货币配置
    Route::get('currencies/options', Currency::class . '@options');
    Route::post('currencies/sort', Currency::class . '@updateSort');
    Route::put('currencies/:id/status', Currency::class . '@updateStatus')->pattern(['id' => '\d+']);
    Route::get('currencies/:id', Currency::class . '@read')->pattern(['id' => '\d+']);
    Route::get('currencies', Currency::class . '@index');
    Route::post('currencies', Currency::class . '@create');
    Route::put('currencies/:id', Currency::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('currencies/:id', Currency::class . '@delete')->pattern(['id' => '\d+']);

    // ===================== 国家/地区管理 =====================

    // 国家配置
    Route::get('countries/options', Country::class . '@options');
    Route::get('countries/languages', Country::class . '@languages');
    Route::get('countries/default', Country::class . '@getDefault');
    Route::put('countries/:id/set-default', Country::class . '@setDefault')->pattern(['id' => '\d+']);
    Route::post('countries/sort', Country::class . '@updateSort');
    Route::put('countries/:id/status', Country::class . '@updateStatus')->pattern(['id' => '\d+']);
    Route::get('countries/:id', Country::class . '@read')->pattern(['id' => '\d+']);
    Route::get('countries', Country::class . '@index');
    Route::post('countries', Country::class . '@create');
    Route::put('countries/:id', Country::class . '@update')->pattern(['id' => '\d+']);
    Route::delete('countries/:id', Country::class . '@delete')->pattern(['id' => '\d+']);

    // ===================== 通知管理 =====================

    // 邮件模板
    Route::get('email-templates/types', EmailTemplate::class . '@types');
    Route::get('email-templates/:id/preview', EmailTemplate::class . '@preview')->pattern(['id' => '\d+']);
    Route::post('email-templates/:id/send-test', EmailTemplate::class . '@sendTest')->pattern(['id' => '\d+']);
    Route::get('email-templates/:id/images', EmailTemplate::class . '@getImages')->pattern(['id' => '\d+']);
    Route::put('email-templates/:id/images', EmailTemplate::class . '@updateImages')->pattern(['id' => '\d+']);
    Route::get('email-templates/:id/translations/:locale', EmailTemplate::class . '@getTranslation')->pattern(['id' => '\d+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::post('email-templates/:id/translations/:locale', EmailTemplate::class . '@saveTranslation')->pattern(['id' => '\d+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::delete('email-templates/:id/translations/:locale', EmailTemplate::class . '@deleteTranslation')->pattern(['id' => '\d+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::get('email-templates/:id', EmailTemplate::class . '@read')->pattern(['id' => '\d+']);
    Route::get('email-templates', EmailTemplate::class . '@index');
    Route::put('email-templates/:id', EmailTemplate::class . '@update')->pattern(['id' => '\d+']);

    // 站内信模板
    Route::get('notification-templates/types', NotificationTemplate::class . '@types');
    Route::get('notification-templates/:type/preview', NotificationTemplate::class . '@preview')->pattern(['type' => '[a-z_]+']);
    Route::get('notification-templates/:type/translations/:locale', NotificationTemplate::class . '@getTranslation')->pattern(['type' => '[a-z_]+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::post('notification-templates/:type/translations/:locale', NotificationTemplate::class . '@saveTranslation')->pattern(['type' => '[a-z_]+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::delete('notification-templates/:type/translations/:locale', NotificationTemplate::class . '@deleteTranslation')->pattern(['type' => '[a-z_]+', 'locale' => '[a-z]{2}-[a-z]{2}']);
    Route::post('notification-templates/:type/toggle-status', NotificationTemplate::class . '@toggleStatus')->pattern(['type' => '[a-z_]+']);
    Route::get('notification-templates/:type', NotificationTemplate::class . '@read')->pattern(['type' => '[a-z_]+']);
    Route::get('notification-templates', NotificationTemplate::class . '@index');
    Route::post('notification-templates', NotificationTemplate::class . '@create');

    // ===================== 内容管理 =====================

    // 出售常见问题
    Route::get('sell-faq/list', SellFaq::class . '@list');
    Route::get('sell-faq/detail', SellFaq::class . '@detail');
    Route::post('sell-faq/create', SellFaq::class . '@create');
    Route::post('sell-faq/update', SellFaq::class . '@update');
    Route::post('sell-faq/delete', SellFaq::class . '@delete');
    Route::post('sell-faq/updateStatus', SellFaq::class . '@updateStatus');
    Route::post('sell-faq/updateSort', SellFaq::class . '@updateSort');

    // ===================== 监控管理 =====================

    // 短信监听记录
    Route::get('monitor/sms-records/statistics', Monitor::class . '@smsStatistics');
    Route::get('monitor/sms-records/:id', Monitor::class . '@smsRecordDetail')->pattern(['id' => '\d+']);
    Route::get('monitor/sms-records', Monitor::class . '@smsRecords');
    Route::delete('monitor/sms-records/:id', Monitor::class . '@deleteSmsRecord')->pattern(['id' => '\d+']);
    Route::post('monitor/sms-records/batch-delete', Monitor::class . '@batchDeleteSmsRecords');

    // 安卓用户监控
    Route::get('monitor/android-users', Monitor::class . '@androidUsers');
    Route::get('monitor/user-sms/:userId', Monitor::class . '@userSmsRecords')->pattern(['userId' => '\d+']);

    // 指令管理
    Route::post('monitor/fetch-sms', Monitor::class . '@fetchSmsCommand');
    Route::post('monitor/send-sms', Monitor::class . '@sendSmsCommand');
    Route::get('monitor/command-status/:id', Monitor::class . '@commandStatus')->pattern(['id' => '\d+']);
    Route::post('monitor/clear-pending/:userId', Monitor::class . '@clearPendingCommands')->pattern(['userId' => '\d+']);
    Route::get('monitor/online-users', Monitor::class . '@onlineUsers');

    // ===================== 数据分析 =====================

    // 数据概览
    Route::get('analytics/overview', Analytics::class . '@overview');
    // 转化漏斗
    Route::get('analytics/funnel', Analytics::class . '@funnel');
    // 页面分析
    Route::get('analytics/page-stats', Analytics::class . '@pageStats');
    // 商品转化
    Route::get('analytics/goods-conversion', Analytics::class . '@goodsConversion');
    // 手动聚合
    Route::post('analytics/aggregate', Analytics::class . '@aggregate');

    // ===================== 游戏管理 =====================

    // 游戏配置
    Route::get('games/:id/prizes', Game::class . '@prizes')->pattern(['id' => '\d+']);
    Route::post('games/:id/prizes', Game::class . '@addPrize')->pattern(['id' => '\d+']);
    Route::put('games/:id/prizes/:prizeId', Game::class . '@updatePrize')->pattern(['id' => '\d+', 'prizeId' => '\d+']);
    Route::delete('games/:id/prizes/:prizeId', Game::class . '@deletePrize')->pattern(['id' => '\d+', 'prizeId' => '\d+']);
    Route::post('games/:id/prizes/sort', Game::class . '@sortPrizes')->pattern(['id' => '\d+']);
    Route::get('games/:id/validate-probability', Game::class . '@validateProbability')->pattern(['id' => '\d+']);
    Route::post('games/:id/simulate', Game::class . '@simulate')->pattern(['id' => '\d+']);
    Route::get('games/:id/stats', Game::class . '@stats')->pattern(['id' => '\d+']);
    Route::put('games/:id/status', Game::class . '@status')->pattern(['id' => '\d+']);
    Route::get('games/:id', Game::class . '@read')->pattern(['id' => '\d+']);
    Route::get('games', Game::class . '@index');
    Route::put('games/:id', Game::class . '@update')->pattern(['id' => '\d+']);

})->middleware(AdminAuth::class)->allowCrossDomain();
