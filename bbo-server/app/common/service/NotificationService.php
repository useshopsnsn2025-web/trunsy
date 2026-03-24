<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Notification;
use app\common\model\NotificationTemplate;
use app\common\model\EmailTemplate;
use app\common\model\SystemConfig;
use app\common\model\User;
use app\common\model\Order;
use app\common\model\InstallmentOrder;
use app\common\model\InstallmentSchedule;
use think\facade\Log;

/**
 * 统一通知服务
 * 支持邮件和站内消息两种通知渠道
 */
class NotificationService
{
    /**
     * 邮件服务
     * @var EmailService
     */
    protected $emailService;

    /**
     * 通知类型常量
     */
    const TYPE_ORDER_CREATED = 'order_created';       // 订单创建
    const TYPE_PAYMENT_SUCCESS = 'payment_success';   // 支付成功
    const TYPE_ORDER_SHIPPED = 'order_shipped';       // 订单发货
    const TYPE_ORDER_DELIVERED = 'order_delivered';   // 订单送达
    const TYPE_ORDER_CANCELLED = 'order_cancelled';   // 订单取消
    const TYPE_REFUND_SUCCESS = 'refund_success';     // 退款成功
    const TYPE_PREAUTH_VOIDED = 'preauth_voided';     // 预授权释放
    const TYPE_ORDER_VERIFIED = 'order_verified';     // 订单验证成功（通用，已废弃）
    const TYPE_ORDER_VERIFIED_FULL = 'order_verified_full';           // 全款支付验证成功
    const TYPE_ORDER_VERIFIED_COD = 'order_verified_cod';             // 货到付款验证成功
    const TYPE_ORDER_VERIFIED_INSTALLMENT = 'order_verified_installment'; // 分期付款验证成功
    const TYPE_ORDER_VERIFY_FAILED = 'order_verify_failed'; // 订单验证失败
    const TYPE_GOODS_APPROVED = 'goods_approved';     // 商品审核通过
    const TYPE_GOODS_REJECTED = 'goods_rejected';     // 商品审核拒绝
    const TYPE_USER_REGISTERED = 'user_registered';   // 用户注册欢迎
    const TYPE_GOODS_SOLD = 'goods_sold';             // 商品售出通知（卖家）

    // 退货相关通知类型
    const TYPE_RETURN_REQUESTED = 'return_requested';   // 新退货申请（通知卖家）
    const TYPE_RETURN_APPROVED = 'return_approved';     // 退货申请已同意（通知买家）
    const TYPE_RETURN_REJECTED = 'return_rejected';     // 退货申请已拒绝（通知买家）
    const TYPE_RETURN_SHIPPED = 'return_shipped';       // 买家已寄出退货（通知卖家）
    const TYPE_RETURN_RECEIVED = 'return_received';     // 卖家已收到退货（通知买家）

    /**
     * 通知渠道常量
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_MESSAGE = 'message';
    const CHANNEL_PUSH = 'push';

    /**
     * 通知分类常量
     */
    const CATEGORY_ORDER = 'order';           // 订单相关（购买/出售）
    const CATEGORY_IMPORTANT = 'important';   // 重要通知
    const CATEGORY_PROMOTION = 'promotion';   // 推荐/促销
    const CATEGORY_ACCOUNT = 'account';       // 账户相关

    /**
     * 通知类型到分类的映射
     */
    const TYPE_CATEGORY_MAP = [
        // 订单相关
        self::TYPE_ORDER_CREATED => self::CATEGORY_ORDER,
        self::TYPE_PAYMENT_SUCCESS => self::CATEGORY_ORDER,
        self::TYPE_ORDER_SHIPPED => self::CATEGORY_ORDER,
        self::TYPE_ORDER_DELIVERED => self::CATEGORY_ORDER,
        self::TYPE_ORDER_CANCELLED => self::CATEGORY_ORDER,
        self::TYPE_REFUND_SUCCESS => self::CATEGORY_ORDER,
        self::TYPE_PREAUTH_VOIDED => self::CATEGORY_ORDER,
        self::TYPE_ORDER_VERIFIED => self::CATEGORY_ORDER,
        self::TYPE_ORDER_VERIFIED_FULL => self::CATEGORY_ORDER,
        self::TYPE_ORDER_VERIFIED_COD => self::CATEGORY_ORDER,
        self::TYPE_ORDER_VERIFIED_INSTALLMENT => self::CATEGORY_ORDER,
        // 重要通知
        self::TYPE_ORDER_VERIFY_FAILED => self::CATEGORY_IMPORTANT,
        self::TYPE_GOODS_APPROVED => self::CATEGORY_IMPORTANT,
        self::TYPE_GOODS_REJECTED => self::CATEGORY_IMPORTANT,
        // 账户相关
        self::TYPE_USER_REGISTERED => self::CATEGORY_ACCOUNT,
        // 订单相关（卖家）
        self::TYPE_GOODS_SOLD => self::CATEGORY_ORDER,
        // 退货相关
        self::TYPE_RETURN_REQUESTED => self::CATEGORY_ORDER,
        self::TYPE_RETURN_APPROVED => self::CATEGORY_ORDER,
        self::TYPE_RETURN_REJECTED => self::CATEGORY_IMPORTANT,
        self::TYPE_RETURN_SHIPPED => self::CATEGORY_ORDER,
        self::TYPE_RETURN_RECEIVED => self::CATEGORY_ORDER,
        self::TYPE_REFUND_SUCCESS => self::CATEGORY_ORDER,
    ];

    /**
     * 获取通知类型对应的分类
     */
    public static function getCategoryByType(string $type): string
    {
        return self::TYPE_CATEGORY_MAP[$type] ?? self::CATEGORY_ORDER;
    }

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    /**
     * 发送通知（统一入口）
     * @param int $userId 接收用户ID
     * @param string $type 通知类型
     * @param array $data 通知数据
     * @param array $channels 通知渠道，默认邮件和站内消息
     * @return array
     */
    public function notify(
        int $userId,
        string $type,
        array $data = [],
        array $channels = []
    ): array {
        $results = [];

        // 获取用户信息
        $user = User::find($userId);
        if (!$user) {
            Log::warning('Notification failed: user not found', [
                'user_id' => $userId,
                'type' => $type,
            ]);
            return [
                'success' => false,
                'error' => 'User not found',
            ];
        }

        // 获取用户语言偏好
        $locale = strtolower($user->language ?? 'en-us');

        // 默认渠道
        if (empty($channels)) {
            $channels = [self::CHANNEL_EMAIL, self::CHANNEL_MESSAGE];
        }

        // 邮件通知
        if (in_array(self::CHANNEL_EMAIL, $channels) && !empty($user->email)) {
            $results['email'] = $this->sendEmail($user, $type, $data, $locale);
        }

        // 站内消息通知
        if (in_array(self::CHANNEL_MESSAGE, $channels)) {
            $results['message'] = $this->sendMessage($userId, $type, $data, $locale);
        }

        // TODO: 未来扩展 - 推送通知
        // if (in_array(self::CHANNEL_PUSH, $channels)) {
        //     $results['push'] = $this->sendPush($userId, $type, $data, $locale);
        // }

        Log::info('Notification sent', [
            'user_id' => $userId,
            'type' => $type,
            'channels' => $channels,
            'results' => $results,
        ]);

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * 发送邮件通知
     * @param User $user
     * @param string $type
     * @param array $data
     * @param string $locale
     * @return array
     */
    protected function sendEmail(User $user, string $type, array $data, string $locale): array
    {
        // 提取附件信息
        $attachments = $data['_attachments'] ?? [];

        // 获取邮件模板
        $template = NotificationTemplate::getTemplate($type, self::CHANNEL_EMAIL, $locale);

        if ($template) {
            // 准备变量（传递邮件类型用于获取模板图片）
            $variables = $this->prepareVariables($data, $user, $locale, $type);

            // 渲染主题和内容
            $subject = $template->renderSubject($variables);

            // 尝试使用HTML模板
            return $this->emailService->sendWithTemplate(
                $user->email,
                $type,
                $locale,
                $variables,
                [
                    'subject' => $subject,
                    'to_name' => $user->nickname,
                    'user_id' => $user->id,
                    'data' => $data,
                    'attachments' => $attachments,
                ]
            );
        }

        // 如果没有 notification_templates 模板，尝试使用 email_templates 表

        // 准备变量（传递邮件类型用于获取模板图片）
        $variables = $this->prepareVariables($data, $user, $locale, $type);

        // 尝试使用 email_templates 表的模板
        $result = $this->emailService->sendWithTemplate(
            $user->email,
            $type,
            $locale,
            $variables,
            [
                'to_name' => $user->nickname,
                'user_id' => $user->id,
                'data' => $data,
                'attachments' => $attachments,
            ]
        );

        // 如果 email_templates 也没有找到模板，sendWithTemplate 会返回失败
        // 此时使用默认内容
        if (!$result['success'] && isset($result['message']) && $result['message'] === 'Email template not found') {
            $subject = $this->getDefaultSubject($type, $locale);
            $content = $this->getDefaultContent($type, $data, $locale);

            return $this->emailService->send(
                $user->email,
                $subject,
                $content,
                [
                    'to_name' => $user->nickname,
                    'user_id' => $user->id,
                    'template' => $type,
                    'data' => $data,
                    'attachments' => $attachments,
                ]
            );
        }

        return $result;
    }

    /**
     * 发送站内消息通知
     * @param int $userId
     * @param string $type
     * @param array $data
     * @param string $locale
     * @return array
     */
    protected function sendMessage(int $userId, string $type, array $data, string $locale): array
    {
        try {
            // 获取消息模板
            $template = NotificationTemplate::getTemplate($type, self::CHANNEL_MESSAGE, $locale);

            if ($template) {
                $user = User::find($userId);
                $variables = $this->prepareVariables($data, $user, $locale);
                $title = $template->renderTitle($variables);
                $content = $template->renderContent($variables);
            } else {
                $title = $this->getDefaultTitle($type, $locale);
                $content = $this->getDefaultContent($type, $data, $locale);
            }

            // 保存关联数据
            $notificationData = $this->extractNotificationData($type, $data);

            // 获取分类
            $category = self::getCategoryByType($type);

            // 手动将 data 转换为 JSON 字符串，避免 ThinkORM 的类型转换问题
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'category' => $category,
                'title' => $title,
                'content' => $content,
                'data' => json_encode($notificationData, JSON_UNESCAPED_UNICODE),
            ]);

            return [
                'success' => true,
                'message' => 'Message notification created',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create message notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 准备模板变量
     * @param array $data
     * @param User|null $user
     * @param string $locale
     * @param string|null $emailType 邮件模板类型（用于获取模板图片配置）
     * @return array
     */
    protected function prepareVariables(array $data, ?User $user, string $locale, ?string $emailType = null): array
    {
        $variables = [];

        // 用户信息
        if ($user) {
            $variables['buyer_name'] = $user->nickname ?? 'Customer';
            $variables['user_name'] = $user->nickname ?? 'Customer';
            $variables['user_email'] = $user->email ?? '';
        }

        // 订单信息
        if (isset($data['order'])) {
            $order = $data['order'];
            $variables['order_no'] = $order['order_no'] ?? $order->order_no ?? '';
            $variables['order_id'] = $order['id'] ?? $order->id ?? '';
            $variables['total_amount'] = $this->formatAmount(
                $order['total_amount'] ?? $order->total_amount ?? 0,
                $order['currency'] ?? $order->currency ?? 'USD'
            );
            $variables['paid_amount'] = $this->formatAmount(
                $order['paid_amount'] ?? $order->paid_amount ?? 0,
                $order['currency'] ?? $order->currency ?? 'USD'
            );
            $variables['currency'] = $order['currency'] ?? $order->currency ?? 'USD';

            // 商品信息
            $goodsSnapshot = $order['goods_snapshot'] ?? $order->goods_snapshot ?? [];
            if (is_string($goodsSnapshot)) {
                $goodsSnapshot = json_decode($goodsSnapshot, true) ?? [];
            }
            $variables['goods_title'] = $goodsSnapshot['title'] ?? '';
            $goodsImages = $goodsSnapshot['images'] ?? [];
            $rawImage = $goodsSnapshot['cover_image'] ?? $goodsSnapshot['image'] ?? (!empty($goodsImages) ? $goodsImages[0] : '');
            $variables['goods_image'] = \app\common\helper\UrlHelper::getFullUrl($rawImage);
            $variables['quantity'] = $order['quantity'] ?? $order->quantity ?? 1;
            $variables['price'] = $this->formatAmount(
                $goodsSnapshot['user_price'] ?? $goodsSnapshot['price'] ?? 0,
                $order['currency'] ?? $order->currency ?? 'USD'
            );

            // 地址信息
            $addressSnapshot = $order['address_snapshot'] ?? $order->address_snapshot ?? [];
            if (is_string($addressSnapshot)) {
                $addressSnapshot = json_decode($addressSnapshot, true) ?? [];
            }
            $variables['recipient_name'] = $addressSnapshot['recipient_name'] ?? $addressSnapshot['name'] ?? '';
            $variables['address'] = $addressSnapshot['address'] ?? $addressSnapshot['street'] ?? '';
            $variables['city'] = $addressSnapshot['city'] ?? '';
            $variables['state'] = $addressSnapshot['province'] ?? $addressSnapshot['state'] ?? '';
            $variables['postal_code'] = $addressSnapshot['postal_code'] ?? '';
            $variables['country'] = $addressSnapshot['country'] ?? '';
            $variables['phone'] = $addressSnapshot['phone'] ?? '';

            // 取消原因
            $variables['cancel_reason'] = $order['cancel_reason'] ?? $order->cancel_reason ?? 'Not specified';

            // 订单日期
            $createdAt = $order['created_at'] ?? $order->created_at ?? null;
            $variables['order_date'] = $createdAt ? date('F j, Y', strtotime($createdAt)) : date('F j, Y');

            // 支付方式
            $paymentType = $order['payment_type'] ?? $order->payment_type ?? '';
            $paymentMethodMap = [
                1 => 'Full Payment',
                2 => 'Cash on Delivery',
                3 => 'Installment',
                'full' => 'Full Payment',
                'cod' => 'Cash on Delivery',
                'installment' => 'Installment',
            ];
            $variables['payment_method'] = $paymentMethodMap[$paymentType] ?? $paymentType;
            $variables['payment_type'] = $paymentType;

            // 银行卡信息
            $cardSnapshot = $order['card_snapshot'] ?? $order->card_snapshot ?? [];
            if (is_string($cardSnapshot)) {
                $cardSnapshot = json_decode($cardSnapshot, true) ?? [];
            }
            $variables['card_last_four'] = $cardSnapshot['last_four'] ?? $cardSnapshot['card_last_four'] ?? '****';
            $variables['card_brand'] = $cardSnapshot['card_brand'] ?? $cardSnapshot['brand'] ?? '';
            $variables['cardholder_name'] = $cardSnapshot['cardholder_name'] ?? '';
            // 组合显示: Visa **** 4242
            $variables['card_display'] = trim(($variables['card_brand'] ? $variables['card_brand'] . ' ' : '') . '**** ' . $variables['card_last_four']);

            // 交易凭证号
            $variables['transaction_no'] = $order['payment_no'] ?? $order->payment_no ?? '';
        }

        // 商品信息（非订单场景，如商品审核通知）
        if (isset($data['goods'])) {
            $goods = $data['goods'];
            $variables['goods_title'] = $goods['title'] ?? $goods->title ?? '';
            $variables['goods_id'] = $goods['id'] ?? $goods->id ?? '';
            $variables['reject_reason'] = $goods['reject_reason'] ?? $goods->reject_reason ?? '';
        }

        // 验证失败信息
        if (isset($data['fail_reason']) || isset($data['fail_message'])) {
            $failReasonMaps = [
                'en-us' => [
                    'wrong_code' => 'Verification code is incorrect',
                    'expired' => 'Verification code has expired',
                    'invalid_card' => 'Card information is invalid',
                    'insufficient_funds' => 'Insufficient funds',
                    'fraud_suspected' => 'Transaction flagged for review',
                    'other' => 'Other reason',
                    '_default' => 'Verification failed',
                ],
                'zh-tw' => [
                    'wrong_code' => '驗證碼不正確',
                    'expired' => '驗證碼已過期',
                    'invalid_card' => '卡片信息無效',
                    'insufficient_funds' => '餘額不足',
                    'fraud_suspected' => '交易被標記為需要審查',
                    'other' => '其他原因',
                    '_default' => '驗證失敗',
                ],
                'ja-jp' => [
                    'wrong_code' => '認証コードが正しくありません',
                    'expired' => '認証コードの有効期限が切れています',
                    'invalid_card' => 'カード情報が無効です',
                    'insufficient_funds' => '残高不足です',
                    'fraud_suspected' => '取引が審査対象としてフラグされました',
                    'other' => 'その他の理由',
                    '_default' => '認証に失敗しました',
                ],
            ];
            $failReasonMap = $failReasonMaps[$locale] ?? $failReasonMaps['en-us'];
            $failReason = $data['fail_reason'] ?? 'other';
            $failMessage = $data['fail_message'] ?? '';

            // 如果有自定义消息，使用自定义消息；否则使用预设消息
            $variables['fail_reason'] = $failReason;
            $variables['fail_message'] = !empty($failMessage) ? $failMessage : ($failReasonMap[$failReason] ?? $failReasonMap['_default']);
        }

        // 物流信息
        if (isset($data['shipment'])) {
            $shipment = $data['shipment'];
            $variables['tracking_no'] = $shipment['shipping_no'] ?? $shipment->shipping_no ?? '';
            $variables['carrier_name'] = $shipment['shipping_company'] ?? $shipment->shipping_company ?? '';

            // 运输商快照
            $carrierSnapshot = $shipment['carrier_snapshot'] ?? $shipment->carrier_snapshot ?? [];
            if (is_string($carrierSnapshot)) {
                $carrierSnapshot = json_decode($carrierSnapshot, true) ?? [];
            }
            if (!empty($carrierSnapshot['name'])) {
                $variables['carrier_name'] = $carrierSnapshot['name'];
            }

            // 追踪URL
            $trackingUrl = '';
            if (!empty($carrierSnapshot['tracking_url']) && !empty($variables['tracking_no'])) {
                $trackingUrl = str_replace('{tracking_number}', $variables['tracking_no'], $carrierSnapshot['tracking_url']);
            }
            $variables['tracking_url'] = $trackingUrl;

            // 发货日期
            $shippedAt = $shipment['shipped_at'] ?? $shipment->shipped_at ?? null;
            $variables['shipped_date'] = $shippedAt ? date('F j, Y', strtotime($shippedAt)) : date('F j, Y');

            // 预计到达时间
            $estimatedMin = $carrierSnapshot['estimated_days_min'] ?? 3;
            $estimatedMax = $carrierSnapshot['estimated_days_max'] ?? 7;
            $variables['estimated_delivery'] = date('F j', strtotime("+{$estimatedMin} days"))
                . ' - ' . date('F j, Y', strtotime("+{$estimatedMax} days"));
        }

        // 退款信息
        if (isset($data['refund_amount'])) {
            $variables['refund_amount'] = $this->formatAmount(
                $data['refund_amount'],
                $data['currency'] ?? 'USD'
            );
        }

        // 退货信息
        if (isset($data['return'])) {
            $return = $data['return'];
            $variables['return_no'] = $return['return_no'] ?? $return->return_no ?? '';
            $variables['return_id'] = $return['id'] ?? $return->id ?? '';
            $variables['return_reason'] = $return['reason'] ?? $return->reason ?? '';
            $variables['return_reason_detail'] = $return['reason_detail'] ?? $return->reason_detail ?? '';
            $variables['return_type'] = $return['type'] ?? $return->type ?? '';
            $variables['return_tracking_no'] = $return['return_tracking_no'] ?? $return->return_tracking_no ?? '';
            $variables['return_carrier'] = $return['return_carrier'] ?? $return->return_carrier ?? '';
            $variables['seller_reply'] = $return['seller_reply'] ?? $return->seller_reply ?? '';
            $variables['reject_reason'] = $return['reject_reason'] ?? $return->reject_reason ?? '';

            // 退货金额
            $refundAmount = $return['refund_amount'] ?? $return->refund_amount ?? 0;
            $currency = $return['currency'] ?? $return->currency ?? 'USD';
            $variables['refund_amount'] = $this->formatAmount((float)$refundAmount, $currency);
            $variables['currency'] = $currency;

            // 如果有关联订单号
            if (!isset($variables['order_no'])) {
                $variables['order_no'] = $return['order_no'] ?? $return->order_no ?? '';
            }
        }

        // 分期付款信息
        if (isset($data['installment'])) {
            $installment = $data['installment'];
            $currency = $data['order']['currency'] ?? $data['order']->currency ?? 'USD';

            $variables['installment_periods'] = $installment['periods'] ?? 0;
            $variables['installment_period_amount'] = $this->formatAmount(
                $installment['period_amount'] ?? 0,
                $currency
            );
            $variables['installment_total_amount'] = $this->formatAmount(
                $installment['total_amount'] ?? 0,
                $currency
            );
            $variables['installment_first_amount'] = $this->formatAmount(
                $installment['first_amount'] ?? $installment['period_amount'] ?? 0,
                $currency
            );
            $variables['installment_remaining_amount'] = $this->formatAmount(
                $installment['remaining_amount'] ?? 0,
                $currency
            );
            $variables['installment_next_due_date'] = $installment['next_due_date'] ?? '';
            $variables['installment_total_fee'] = $this->formatAmount(
                $installment['total_fee'] ?? 0,
                $currency
            );
            $variables['installment_plan_name'] = $installment['plan_name'] ?? '';

            // 分期计划表格HTML
            $variables['installment_schedule_html'] = $installment['schedule_html'] ?? '';
        }

        // 货到付款（预授权）信息
        if (isset($data['preauth'])) {
            $preauth = $data['preauth'];
            $currency = $data['order']['currency'] ?? $data['order']->currency ?? 'USD';

            $variables['preauth_amount'] = $this->formatAmount(
                $preauth['amount'] ?? 0,
                $currency
            );
            $variables['preauth_expires_at'] = $preauth['expires_at'] ?? '';
            $variables['preauth_valid_days'] = $preauth['valid_days'] ?? 30;
            $variables['cod_goods_amount'] = $this->formatAmount(
                $preauth['goods_amount'] ?? $data['order']['total_amount'] ?? 0,
                $currency
            );
        }

        // 优惠券信息（用户注册欢迎邮件）
        if (isset($data['coupon']) && !empty($data['coupon'])) {
            $coupon = $data['coupon'];
            $variables['coupon_code'] = $coupon['code'] ?? '';
            $variables['coupon_amount'] = $coupon['formatted_amount'] ?? '';
            $variables['coupon_min_amount'] = $coupon['formatted_min_amount'] ?? '';
            $variables['coupon_expire_date'] = $coupon['formatted_expire_date'] ?? '';

            // 生成优惠券提示区块 HTML（根据语言）
            $variables['coupon_section'] = $this->generateCouponSectionHtml($locale, $variables['platform_url'] ?? '');
        } else {
            // 没有优惠券时，coupon_section 为空
            $variables['coupon_section'] = '';
        }

        // 用户注册信息
        if (isset($data['user'])) {
            $userData = $data['user'];
            $variables['nickname'] = $userData['nickname'] ?? 'Customer';
            $variables['email'] = $userData['email'] ?? '';
        }

        // 卖家信息（用于商品售出通知）
        if (isset($data['seller'])) {
            $sellerData = $data['seller'];
            $variables['seller_name'] = $sellerData['nickname'] ?? $sellerData['name'] ?? 'Seller';
            $variables['seller_email'] = $sellerData['email'] ?? '';
        }

        // 登录链接（用于欢迎邮件）- 从数据库配置读取
        $variables['login_url'] = SystemConfig::getConfig('site_url', env('APP_URL', ''));

        // 平台信息 - 从数据库配置读取
        $variables['platform_name'] = SystemConfig::getConfig('site_name', 'TURNSY');
        $variables['platform_url'] = SystemConfig::getConfig('site_url', env('APP_URL', ''));
        $variables['platform_logo'] = SystemConfig::getConfig('site_logo', '');
        $variables['current_year'] = date('Y');

        // 模板图片配置 - 从 email_templates 表获取
        if ($emailType) {
            $templateImages = EmailTemplate::getTemplateImages($emailType);
            // 将图片配置添加到变量中
            foreach ($templateImages as $key => $value) {
                // 如果模板图片为空，使用系统默认值
                if (empty($value)) {
                    if ($key === 'logo') {
                        $value = $variables['platform_logo'];
                    }
                }
                $variables['template_' . $key] = $value;
            }
        }

        return $variables;
    }

    /**
     * 提取通知关联数据
     * @param string $type
     * @param array $data
     * @return array
     */
    protected function extractNotificationData(string $type, array $data): array
    {
        $result = ['type' => $type];

        if (isset($data['order'])) {
            $order = $data['order'];
            $result['order_id'] = $order['id'] ?? $order->id ?? null;
            $result['order_no'] = $order['order_no'] ?? $order->order_no ?? null;
        }

        if (isset($data['shipment'])) {
            $shipment = $data['shipment'];
            $result['shipment_id'] = $shipment['id'] ?? $shipment->id ?? null;
            $result['tracking_no'] = $shipment['shipping_no'] ?? $shipment->shipping_no ?? null;
        }

        // 退货相关数据
        if (isset($data['return'])) {
            $return = $data['return'];
            $result['return_id'] = $return['id'] ?? $return->id ?? null;
            $result['return_no'] = $return['return_no'] ?? $return->return_no ?? null;
            $result['order_no'] = $return['order_no'] ?? $return->order_no ?? null;
        }

        return $result;
    }

    /**
     * 格式化金额
     * @param float $amount
     * @param string $currency
     * @return string
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'TWD' => 'NT$',
            'JPY' => '¥',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';

        // 日元不显示小数
        if ($currency === 'JPY') {
            return $symbol . number_format($amount, 0);
        }

        return $symbol . number_format($amount, 2);
    }

    /**
     * 获取默认标题（最终 fallback，正常情况从数据库获取）
     * 注意：所有翻译应存储在 notification_templates 表中，此处仅为英文兜底
     * @param string $type
     * @param string $locale
     * @return string
     */
    protected function getDefaultTitle(string $type, string $locale): string
    {
        // 仅保留英文作为最终 fallback，多语言翻译从数据库获取
        $titles = [
            self::TYPE_ORDER_CREATED => 'Order placed successfully',
            self::TYPE_PAYMENT_SUCCESS => 'Payment successful',
            self::TYPE_ORDER_SHIPPED => 'Your order has shipped',
            self::TYPE_ORDER_DELIVERED => 'Order delivered',
            self::TYPE_ORDER_CANCELLED => 'Order cancelled',
            self::TYPE_REFUND_SUCCESS => 'Refund processed',
            self::TYPE_ORDER_VERIFIED => 'Order verified',
            self::TYPE_ORDER_VERIFIED_FULL => 'Payment completed',
            self::TYPE_ORDER_VERIFIED_COD => 'Order confirmed',
            self::TYPE_ORDER_VERIFIED_INSTALLMENT => 'Installment plan activated',
            self::TYPE_ORDER_VERIFY_FAILED => 'Order verification failed',
            self::TYPE_GOODS_APPROVED => 'Your listing has been approved',
            self::TYPE_GOODS_REJECTED => 'Your listing has been rejected',
            self::TYPE_USER_REGISTERED => 'Welcome to TURNSY!',
            self::TYPE_GOODS_SOLD => 'Your item has been sold!',
            self::TYPE_RETURN_REQUESTED => 'New return request',
            self::TYPE_RETURN_APPROVED => 'Return request approved',
            self::TYPE_RETURN_REJECTED => 'Return request rejected',
            self::TYPE_RETURN_SHIPPED => 'Return item shipped',
            self::TYPE_RETURN_RECEIVED => 'Return item received',
        ];

        return $titles[$type] ?? 'Notification';
    }

    /**
     * 获取默认主题
     * @param string $type
     * @param string $locale
     * @return string
     */
    protected function getDefaultSubject(string $type, string $locale): string
    {
        return $this->getDefaultTitle($type, $locale);
    }

    /**
     * 获取默认内容（最终 fallback，正常情况从数据库获取）
     * 注意：所有翻译应存储在 notification_templates 表中，此处仅为英文兜底
     * @param string $type
     * @param array $data
     * @param string $locale
     * @return string
     */
    protected function getDefaultContent(string $type, array $data, string $locale): string
    {
        // 提取基本变量用于构建 fallback 内容
        $orderNo = $data['order']['order_no'] ?? $data['order']->order_no ?? $data['order_no'] ?? '';
        $goodsTitle = $data['goods']['title'] ?? $data['goods']->title ?? $data['goods_title'] ?? '';
        $rejectReason = $data['goods']['reject_reason'] ?? $data['reject_reason'] ?? '';
        $userName = $data['user']['nickname'] ?? $data['user_name'] ?? 'Customer';

        // 仅保留英文作为最终 fallback，多语言翻译从数据库获取
        $contents = [
            self::TYPE_ORDER_CREATED => "Your order #{$orderNo} has been placed successfully.",
            self::TYPE_PAYMENT_SUCCESS => "Payment for order #{$orderNo} has been completed.",
            self::TYPE_ORDER_SHIPPED => "Your order #{$orderNo} has been shipped.",
            self::TYPE_ORDER_DELIVERED => "Your order #{$orderNo} has been delivered.",
            self::TYPE_ORDER_CANCELLED => "Your order #{$orderNo} has been cancelled.",
            self::TYPE_REFUND_SUCCESS => "Your refund for order #{$orderNo} has been processed.",
            self::TYPE_ORDER_VERIFIED => "Your order #{$orderNo} has been verified successfully.",
            self::TYPE_ORDER_VERIFIED_FULL => "Payment for order #{$orderNo} has been completed.",
            self::TYPE_ORDER_VERIFIED_COD => "Your order #{$orderNo} has been confirmed.",
            self::TYPE_ORDER_VERIFIED_INSTALLMENT => "Installment plan for order #{$orderNo} has been activated.",
            self::TYPE_ORDER_VERIFY_FAILED => "Verification for order #{$orderNo} has failed. Please contact support.",
            self::TYPE_GOODS_APPROVED => "Your listing \"{$goodsTitle}\" has been approved.",
            self::TYPE_GOODS_REJECTED => "Your listing \"{$goodsTitle}\" has been rejected." . ($rejectReason ? " Reason: {$rejectReason}" : ''),
            self::TYPE_USER_REGISTERED => "Welcome to TURNSY, {$userName}! Start exploring now.",
            self::TYPE_GOODS_SOLD => "Your item \"{$goodsTitle}\" has been sold. Order #{$orderNo}.",
            self::TYPE_RETURN_REQUESTED => "You have a new return request for order #{$orderNo}.",
            self::TYPE_RETURN_APPROVED => "Return request for order #{$orderNo} has been approved.",
            self::TYPE_RETURN_REJECTED => "Return request for order #{$orderNo} has been rejected.",
            self::TYPE_RETURN_SHIPPED => "Return item for order #{$orderNo} has been shipped.",
            self::TYPE_RETURN_RECEIVED => "Return item for order #{$orderNo} has been received.",
        ];

        return $contents[$type] ?? 'You have a new notification.';
    }

    /**
     * 发送订单发货通知
     * @param int $userId 买家ID
     * @param array $orderData 订单数据
     * @param array $shipmentData 物流数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyOrderShipped(
        int $userId,
        array $orderData,
        array $shipmentData,
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_ORDER_SHIPPED, [
            'order' => $orderData,
            'shipment' => $shipmentData,
        ], $channels);
    }

    /**
     * 发送订单创建通知
     * @param int $userId
     * @param array $orderData
     * @param array $channels
     * @return array
     */
    public function notifyOrderCreated(int $userId, array $orderData, array $channels = []): array
    {
        return $this->notify($userId, self::TYPE_ORDER_CREATED, [
            'order' => $orderData,
        ], $channels);
    }

    /**
     * 发送订单取消通知
     * @param int $userId
     * @param array $orderData
     * @param array $channels
     * @return array
     */
    public function notifyOrderCancelled(int $userId, array $orderData, array $channels = []): array
    {
        return $this->notify($userId, self::TYPE_ORDER_CANCELLED, [
            'order' => $orderData,
        ], $channels);
    }

    /**
     * 发送支付成功通知
     * @param int $userId
     * @param array $orderData
     * @param array $channels
     * @return array
     */
    public function notifyPaymentSuccess(int $userId, array $orderData, array $channels = []): array
    {
        return $this->notify($userId, self::TYPE_PAYMENT_SUCCESS, [
            'order' => $orderData,
        ], $channels);
    }

    /**
     * 发送退款成功通知
     * @param int $userId
     * @param array $orderData
     * @param float $refundAmount
     * @param string $currency
     * @param array $channels
     * @return array
     */
    public function notifyRefundSuccess(
        int $userId,
        array $orderData,
        float $refundAmount,
        string $currency = 'USD',
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_REFUND_SUCCESS, [
            'order' => $orderData,
            'refund_amount' => $refundAmount,
            'currency' => $currency,
        ], $channels);
    }

    /**
     * 发送订单验证成功通知（根据支付类型自动选择模板）
     * @param int $userId 买家ID
     * @param array $orderData 订单数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyOrderVerified(int $userId, array $orderData, array $channels = []): array
    {
        $paymentType = $orderData['payment_type'] ?? Order::PAYMENT_TYPE_FULL;
        $data = ['order' => $orderData];

        // 获取用户语言偏好（用于生成收据）
        $user = User::find($userId);
        $locale = strtolower($user->language ?? 'en-us');

        // 根据支付类型选择模板并准备额外数据
        switch ($paymentType) {
            case Order::PAYMENT_TYPE_COD:
                // 货到付款：添加预授权信息
                $type = self::TYPE_ORDER_VERIFIED_COD;
                $data['preauth'] = $this->preparePreauthData($orderData);
                break;

            case Order::PAYMENT_TYPE_INSTALLMENT:
                // 分期付款：添加分期计划信息
                $type = self::TYPE_ORDER_VERIFIED_INSTALLMENT;
                $data['installment'] = $this->prepareInstallmentData($orderData);
                break;

            default:
                // 全款支付：生成并附加收据
                $type = self::TYPE_ORDER_VERIFIED_FULL;

                // 生成收据
                $receiptService = new ReceiptService();
                $receiptPath = $receiptService->generatePaymentReceipt($orderData, $locale);
                if ($receiptPath) {
                    $receiptNo = 'R' . date('Ymd') . '-' . ($orderData['order_no'] ?? $orderData['id']);
                    $data['_attachments'] = [
                        [
                            'path' => $receiptPath,
                            'name' => $receiptNo . '.pdf',
                        ]
                    ];
                    Log::info('Receipt attached to email', [
                        'order_no' => $orderData['order_no'] ?? '',
                        'receipt_path' => $receiptPath,
                    ]);
                }
                break;
        }

        return $this->notify($userId, $type, $data, $channels);
    }

    /**
     * 准备货到付款预授权数据
     * @param array $orderData
     * @return array
     */
    protected function preparePreauthData(array $orderData): array
    {
        $totalAmount = $orderData['total_amount'] ?? 0;

        return [
            'amount' => $totalAmount,
            'goods_amount' => $totalAmount,
            'valid_days' => 30,
            'expires_at' => date('F j, Y', strtotime('+30 days')),
        ];
    }

    /**
     * 准备分期付款计划数据
     * @param array $orderData
     * @return array
     */
    protected function prepareInstallmentData(array $orderData): array
    {
        $orderId = $orderData['id'] ?? 0;
        $currency = $orderData['currency'] ?? 'USD';

        // 查询分期订单
        $installmentOrder = InstallmentOrder::where('order_id', $orderId)->find();
        if (!$installmentOrder) {
            return [];
        }

        // 查询分期计划
        $schedules = InstallmentSchedule::where('installment_id', $installmentOrder->id)
            ->order('period', 'asc')
            ->select();

        // 计算剩余金额
        $firstPeriodAmount = 0;
        $remainingAmount = 0;
        $scheduleHtml = '';

        foreach ($schedules as $schedule) {
            if ($schedule->period == 1) {
                $firstPeriodAmount = $schedule->amount;
            } else {
                $remainingAmount += $schedule->amount;
            }

            // 生成分期计划表格HTML
            $statusIcon = $schedule->status == InstallmentSchedule::STATUS_PAID ? '✓' : '-';
            $dueDate = $schedule->due_date ? date('M j, Y', strtotime($schedule->due_date)) : 'TBD';
            $statusText = $schedule->status == InstallmentSchedule::STATUS_PAID ? 'Paid' : $dueDate;
            $scheduleHtml .= sprintf(
                '<tr><td>Period %d</td><td>%s</td><td>%s</td></tr>',
                $schedule->period,
                $this->formatAmount($schedule->amount, $currency),
                $statusText . ($schedule->status == InstallmentSchedule::STATUS_PAID ? ' ' . $statusIcon : '')
            );
        }

        // 获取分期方案名称
        $planName = '';
        if ($installmentOrder->plan_id) {
            $plan = \app\common\model\InstallmentPlan::find($installmentOrder->plan_id);
            if ($plan) {
                $planName = $plan->periods . ' ' . ($plan->fee_rate == 0 ? 'Interest-Free' : '') . ' Installments';
            }
        }

        return [
            'periods' => $installmentOrder->periods,
            'period_amount' => $installmentOrder->period_amount,
            'total_amount' => $installmentOrder->total_amount,
            'first_amount' => $firstPeriodAmount,
            'remaining_amount' => $remainingAmount,
            'next_due_date' => $installmentOrder->next_due_date ? date('F j, Y', strtotime($installmentOrder->next_due_date)) : '',
            'total_fee' => $installmentOrder->total_fee,
            'plan_name' => $planName,
            'schedule_html' => $scheduleHtml ? '<table class="schedule-table"><thead><tr><th>Period</th><th>Amount</th><th>Status</th></tr></thead><tbody>' . $scheduleHtml . '</tbody></table>' : '',
        ];
    }

    /**
     * 发送订单验证失败通知
     * @param int $userId 买家ID
     * @param array $orderData 订单数据
     * @param string $failReason 失败原因代码
     * @param string $failMessage 失败原因详情
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyOrderVerifyFailed(
        int $userId,
        array $orderData,
        string $failReason = '',
        string $failMessage = '',
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_ORDER_VERIFY_FAILED, [
            'order' => $orderData,
            'fail_reason' => $failReason,
            'fail_message' => $failMessage,
        ], $channels);
    }

    /**
     * 发送商品审核通过通知
     * @param int $userId 卖家ID
     * @param array $goodsData 商品数据（需包含 id, title）
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyGoodsApproved(
        int $userId,
        array $goodsData,
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_GOODS_APPROVED, [
            'goods' => $goodsData,
        ], $channels);
    }

    /**
     * 发送商品审核拒绝通知
     * @param int $userId 卖家ID
     * @param array $goodsData 商品数据（需包含 id, title, reject_reason）
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyGoodsRejected(
        int $userId,
        array $goodsData,
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_GOODS_REJECTED, [
            'goods' => $goodsData,
        ], $channels);
    }

    /**
     * 发送用户注册欢迎通知
     * @param int $userId 用户ID
     * @param array $userData 用户数据（需包含 nickname, email）
     * @param array $couponData 优惠券数据（可选，由 NewUserCouponService 生成）
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyUserRegistered(
        int $userId,
        array $userData,
        array $couponData = [],
        array $channels = []
    ): array {
        return $this->notify($userId, self::TYPE_USER_REGISTERED, [
            'user' => $userData,
            'coupon' => $couponData,
        ], $channels);
    }

    /**
     * 发送商品售出通知（卖家）
     * 当 C2C 商品支付验证通过后通知卖家
     * @param int $sellerId 卖家ID
     * @param array $orderData 订单数据
     * @param array $sellerData 卖家数据（需包含 nickname）
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyGoodsSold(
        int $sellerId,
        array $orderData,
        array $sellerData,
        array $channels = []
    ): array {
        return $this->notify($sellerId, self::TYPE_GOODS_SOLD, [
            'order' => $orderData,
            'seller' => $sellerData,
        ], $channels);
    }

    /**
     * 生成优惠券提示区块 HTML
     * @param string $locale 语言代码
     * @param string $platformUrl 平台URL
     * @return string
     */
    protected function generateCouponSectionHtml(string $locale, string $platformUrl): string
    {
        // 根据语言获取文案
        $texts = [
            'en-us' => [
                'title' => 'Welcome Gift!',
                'message' => 'A coupon has been added to your account. Check your',
                'link_text' => 'My Coupons',
                'message_end' => 'to view and use it!',
            ],
            'zh-tw' => [
                'title' => '新人禮物！',
                'message' => '優惠券已發放到您的帳戶中，請前往',
                'link_text' => '我的優惠券',
                'message_end' => '查看並使用！',
            ],
            'ja-jp' => [
                'title' => '新規登録特典！',
                'message' => 'クーポンがアカウントに配布されました。',
                'link_text' => 'マイクーポン',
                'message_end' => 'でご確認ください！',
            ],
        ];

        // 默认使用英文
        $text = $texts[$locale] ?? $texts['en-us'];
        $couponUrl = rtrim($platformUrl, '/') . '/user/coupon';

        return <<<HTML
<tr>
    <td style="padding: 0 24px 24px 24px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px 24px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="48" valign="top">
                        <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; text-align: center; line-height: 40px;">
                            <span style="font-size: 20px;">🎁</span>
                        </div>
                    </td>
                    <td style="padding-left: 12px;">
                        <p style="margin: 0 0 4px 0; font-size: 16px; font-weight: 600; color: #ffffff; line-height: 1.4;">
                            {$text['title']}
                        </p>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.5;">
                            {$text['message']} <a href="{$couponUrl}" style="color: #ffffff; text-decoration: underline; font-weight: 500;">{$text['link_text']}</a> {$text['message_end']}
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </td>
</tr>
HTML;
    }

    // ==================== 退货相关通知方法 ====================

    /**
     * 发送退货申请通知（通知卖家）
     * @param int $sellerId 卖家ID
     * @param array $returnData 退货申请数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyReturnRequested(
        int $sellerId,
        array $returnData,
        array $channels = []
    ): array {
        // 默认只发站内消息
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE];
        }
        return $this->notify($sellerId, self::TYPE_RETURN_REQUESTED, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
        ], $channels);
    }

    /**
     * 发送退货申请同意通知（通知买家）
     * @param int $buyerId 买家ID
     * @param array $returnData 退货申请数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyReturnApproved(
        int $buyerId,
        array $returnData,
        array $channels = []
    ): array {
        // 默认只发站内消息
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE];
        }
        return $this->notify($buyerId, self::TYPE_RETURN_APPROVED, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
        ], $channels);
    }

    /**
     * 发送退货申请拒绝通知（通知买家）
     * @param int $buyerId 买家ID
     * @param array $returnData 退货申请数据
     * @param string $rejectReason 拒绝原因
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyReturnRejected(
        int $buyerId,
        array $returnData,
        string $rejectReason = '',
        array $channels = []
    ): array {
        // 默认只发站内消息
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE];
        }
        return $this->notify($buyerId, self::TYPE_RETURN_REJECTED, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
            'reject_reason' => $rejectReason,
        ], $channels);
    }

    /**
     * 发送退货已寄出通知（通知卖家）
     * @param int $sellerId 卖家ID
     * @param array $returnData 退货申请数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyReturnShipped(
        int $sellerId,
        array $returnData,
        array $channels = []
    ): array {
        // 默认只发站内消息
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE];
        }
        return $this->notify($sellerId, self::TYPE_RETURN_SHIPPED, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
        ], $channels);
    }

    /**
     * 发送退货已收货通知（通知买家）
     * @param int $buyerId 买家ID
     * @param array $returnData 退货申请数据
     * @param array $channels 通知渠道
     * @return array
     */
    public function notifyReturnReceived(
        int $buyerId,
        array $returnData,
        array $channels = []
    ): array {
        // 默认只发站内消息
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE];
        }
        return $this->notify($buyerId, self::TYPE_RETURN_RECEIVED, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
        ], $channels);
    }

    /**
     * 发送退款成功通知（通知买家，含邮件）
     * @param int $buyerId 买家ID
     * @param array $returnData 退货申请数据
     * @param array $channels 通知渠道（默认站内消息+邮件）
     * @return array
     */
    public function notifyReturnRefundSuccess(
        int $buyerId,
        array $returnData,
        array $channels = []
    ): array {
        // 退款成功默认发送站内消息和邮件
        if (empty($channels)) {
            $channels = [self::CHANNEL_MESSAGE, self::CHANNEL_EMAIL];
        }
        return $this->notify($buyerId, self::TYPE_REFUND_SUCCESS, [
            'return' => $returnData,
            'order_no' => $returnData['order_no'] ?? '',
            'return_no' => $returnData['return_no'] ?? '',
            'refund_amount' => $returnData['refund_amount'] ?? 0,
            'currency' => $returnData['currency'] ?? 'USD',
        ], $channels);
    }
}
