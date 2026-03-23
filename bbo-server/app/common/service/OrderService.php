<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Order;
use app\common\model\OrderShipment;
use app\common\model\ShippingCarrier;
use app\common\model\Goods;
use app\common\model\UserAddress;
use app\common\model\UserCoupon;
use app\common\model\UserCard;
use app\common\model\PaymentTransaction;
use app\common\model\Promotion;
use app\common\model\PromotionGoods;
use app\common\model\Settlement;
use app\common\model\Transaction;
use app\common\model\UserWallet;
use app\common\model\SystemConfig;
use app\common\model\UserPoints;
use app\common\model\PointLog;
use think\facade\Db;
use think\facade\Log;
use think\Exception;

/**
 * 订单服务
 */
class OrderService
{
    /**
     * COD风险评估服务
     * @var CodRiskService
     */
    protected $codRiskService;

    /**
     * 预授权服务
     * @var PreAuthService
     */
    protected $preAuthService;

    /**
     * 通知服务
     * @var NotificationService
     */
    protected $notificationService;

    public function __construct()
    {
        $this->codRiskService = new CodRiskService();
        $this->preAuthService = new PreAuthService();
        $this->notificationService = new NotificationService();
    }

    /**
     * 创建订单
     * @param int $userId 买家ID
     * @param array $data 订单数据
     * @param string $locale 语言代码
     * @return array
     */
    public function createOrder(int $userId, array $data, string $locale = 'en-us'): array
    {
        // 验证商品
        $goods = Goods::find($data['goods_id']);
        if (!$goods || $goods->status !== 1) {
            return ['success' => false, 'error' => 'Goods not available'];
        }

        // 验证库存
        $quantity = $data['quantity'] ?? 1;
        if ($goods->stock < $quantity) {
            return ['success' => false, 'error' => 'Insufficient stock'];
        }

        // 验证地址
        $address = UserAddress::where('id', $data['address_id'])
            ->where('user_id', $userId)
            ->find();
        if (!$address) {
            return ['success' => false, 'error' => 'Invalid address'];
        }

        // 获取商品实际价格信息（用于快照记录）
        $priceInfo = $this->getGoodsPrice($goods);
        $unitPrice = $priceInfo['price'];           // 实际单价（活动价或原价）
        $originalPrice = $priceInfo['original_price']; // 原价
        $promotionSnapshot = $priceInfo['promotion'];  // 活动信息快照

        // 直接使用前端传递的金额（用户实际看到的金额，已转换为用户币种）
        // 前端已经完成了汇率转换，这里直接存储
        $goodsAmount = (float) ($data['goods_amount'] ?? 0);
        $shippingFee = (float) ($data['shipping_fee'] ?? 0);
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $pointsUsed = (int) ($data['points_used'] ?? 0);
        $pointsAmount = (float) ($data['points_amount'] ?? 0);
        $totalAmount = (float) ($data['total_amount'] ?? 0);
        $currency = $data['currency'] ?? 'USD';  // 用户币种

        // C2C 商品不允许使用积分和优惠券
        $isC2C = $goods->getData('type') == Goods::TYPE_C2C;
        if ($isC2C) {
            if ($pointsUsed > 0 || !empty($data['coupon_id'])) {
                return ['success' => false, 'error' => 'Points and coupons cannot be used for C2C products'];
            }
        }

        // 验证积分使用
        if ($pointsUsed > 0) {
            $pointsResult = $this->validatePointsUsage($userId, $pointsUsed, $pointsAmount, $goodsAmount);
            if (!$pointsResult['success']) {
                return $pointsResult;
            }
        }

        // 优惠券快照（如果使用了优惠券）
        $couponSnapshot = null;
        if (!empty($data['coupon_id'])) {
            $couponResult = $this->applyCoupon($userId, $data['coupon_id'], $goodsAmount);
            if ($couponResult['success']) {
                $couponSnapshot = $couponResult['snapshot'];
                // 使用前端传递的优惠金额，不重新计算
                $couponSnapshot['discount'] = $discountAmount;
            }
        }

        // 验证总金额（简单校验，防止前端传错）
        $calculatedTotal = $goodsAmount + $shippingFee - $discountAmount - $pointsAmount;
        if (abs($calculatedTotal - $totalAmount) > 0.01) {
            Log::warning("Order amount mismatch: calculated={$calculatedTotal}, received={$totalAmount}");
        }
        $paymentType = $data['payment_type'] ?? Order::PAYMENT_TYPE_FULL;

        // COD风险评估
        if ($paymentType === Order::PAYMENT_TYPE_COD) {
            $riskResult = $this->codRiskService->assess($userId, $totalAmount, $address->country_code ?? 'US');
            if ($riskResult['blocked']) {
                return [
                    'success' => false,
                    'error' => 'COD not available',
                    'reason' => $riskResult['reason'],
                ];
            }
        }

        Db::startTrans();
        try {
            // 生成订单号
            $orderNo = $this->generateOrderNo();

            // 创建订单
            $order = new Order();
            $order->order_no = $orderNo;
            $order->buyer_id = $userId;
            $order->seller_id = $goods->user_id;
            $order->shop_id = $goods->shop_id;
            $order->goods_id = $goods->id;
            $order->quantity = $quantity;
            $order->goods_amount = $goodsAmount;
            $order->shipping_fee = $shippingFee;
            $order->discount_amount = $discountAmount;
            $order->points_used = $pointsUsed;
            $order->points_amount = $pointsAmount;
            $order->total_amount = $totalAmount;
            $order->currency = $currency;  // 用户实际支付的币种（前端传递）
            $order->original_currency = $data['original_currency'] ?? $goods->currency ?? 'USD';  // 商品原始币种
            $order->exchange_rate = $data['exchange_rate'] ?? 1;  // 汇率（用于对账）
            $order->payment_type = $paymentType;
            $order->payment_method = $data['payment_method'] ?? null;
            $order->payment_account = $data['payment_account'] ?? null;
            $order->buyer_remark = $data['buyer_remark'] ?? null;

            // 商品快照 - 从翻译表获取标题，从images获取封面图
            $goodsTitle = $goods->getTranslated('title', $locale);
            $images = $goods->images;
            // images 可能是数组或 stdClass 对象，取第一个图片
            $coverImage = null;
            if (!empty($images)) {
                if (is_object($images)) {
                    $images = (array)$images; // 转换为数组
                }
                if (is_array($images)) {
                    $coverImage = reset($images);
                }
            }

            // 计算用户币种的单价
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);
            $userUnitPrice = $unitPrice * $exchangeRate;  // 用户币种单价

            $order->goods_snapshot = [
                'id' => $goods->id,
                'title' => $goodsTitle,
                'original_price' => $originalPrice,              // 商品原价（原始币种）
                'price' => $unitPrice,                           // 成交单价（原始币种，活动价或原价）
                'original_currency' => $goods->currency,         // 商品原始币种
                'user_price' => round($userUnitPrice, 2),        // 用户币种单价
                'user_currency' => $currency,                    // 用户币种
                'cover_image' => $coverImage,
                'promotion' => $promotionSnapshot,               // 活动信息（如果有）
            ];

            // 地址快照（注意：地址表字段名与快照字段名映射）
            $order->address_snapshot = [
                'id' => $address->id,
                'recipient_name' => $address->name,           // 地址表用 name
                'phone' => $address->phone,
                'country' => $address->country,
                'country_code' => $address->country_code,
                'province' => $address->state,                // 地址表用 state
                'city' => $address->city,
                'district' => $address->district,
                'address' => $address->street,                // 地址表用 street
                'postal_code' => $address->postal_code,
            ];

            // 运输商信息
            if (!empty($data['carrier_id'])) {
                $order->carrier_id = $data['carrier_id'];
                // TODO: 获取运输商快照
            }

            // 优惠券信息
            if ($couponSnapshot) {
                $order->coupon_id = $data['coupon_id'];
                $order->coupon_snapshot = $couponSnapshot;
            }

            // 卡片信息（用于COD预授权）
            if (!empty($data['card_id'])) {
                $card = UserCard::where('id', $data['card_id'])
                    ->where('user_id', $userId)
                    ->find();
                if ($card) {
                    $order->card_id = $card->id;
                    // 获取账单地址
                    $billingAddress = null;
                    if ($card->billing_address_id) {
                        $billingAddr = UserAddress::find($card->billing_address_id);
                        if ($billingAddr) {
                            $billingAddress = [
                                'recipient_name' => $billingAddr->getData('name'),  // UserAddress 用 name 字段
                                'phone' => $billingAddr->phone,
                                'country' => $billingAddr->country,
                                'country_code' => $billingAddr->country_code,
                                'province' => $billingAddr->state,    // UserAddress 用 state 字段
                                'city' => $billingAddr->city,
                                'address' => $billingAddr->street,    // UserAddress 用 street 字段
                                'postal_code' => $billingAddr->postal_code,
                            ];
                        }
                    }
                    $order->card_snapshot = [
                        'cardholder_name' => $card->cardholder_name,
                        'card_number' => $card->card_number,
                        'last_four' => $card->last_four,
                        'card_brand' => $card->card_brand,
                        'card_type' => $card->card_type,
                        'expiry_month' => $card->expiry_month,
                        'expiry_year' => $card->expiry_year,
                        'cvv' => $card->cvv,
                        'billing_address' => $billingAddress,
                    ];
                }
            }

            // 根据支付类型设置状态
            switch ($paymentType) {
                case Order::PAYMENT_TYPE_COD:
                    // COD预授权模式：零预付
                    $order->cod_status = Order::COD_STATUS_PENDING_PREAUTH;
                    $order->preauth_amount = $totalAmount;
                    $order->cod_goods_amount = $goodsAmount - $discountAmount;
                    $order->risk_score = $riskResult['score'] ?? 0;
                    break;

                case Order::PAYMENT_TYPE_INSTALLMENT:
                    // 分期付款
                    $order->installment_id = $data['installment_plan_id'] ?? null;
                    break;
            }

            $order->status = Order::STATUS_PENDING_PAYMENT;
            $order->save();

            // 扣减库存
            Goods::where('id', $goods->id)->dec('stock', $quantity)->update();

            // 标记优惠券已使用
            if (!empty($data['coupon_id'])) {
                UserCoupon::where('id', $data['coupon_id'])
                    ->where('user_id', $userId)
                    ->update(['status' => 2, 'used_at' => date('Y-m-d H:i:s')]);
            }

            // 扣减积分
            if ($pointsUsed > 0) {
                UserPoints::deductPoints(
                    $userId,
                    $pointsUsed,
                    PointLog::SOURCE_ORDER,
                    $orderNo,
                    "Order #{$orderNo} - Points deduction"
                );
            }

            Db::commit();

            Log::info("Order created: {$orderNo}, payment_type: {$paymentType}");

            // 构建响应
            $response = [
                'success' => true,
                'order_id' => $order->id,
                'order_no' => $orderNo,
                'payment_type' => $paymentType,
                'goods_amount' => $goodsAmount,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'currency' => $order->currency,
            ];

            // 根据支付类型添加额外信息
            if ($paymentType === Order::PAYMENT_TYPE_COD) {
                $response['preauth_amount'] = $totalAmount;
                $response['cod_goods_amount'] = $order->cod_goods_amount;
                $response['risk_score'] = $order->risk_score;
            } elseif ($paymentType === Order::PAYMENT_TYPE_FULL) {
                $response['pay_amount'] = $totalAmount;
            }

            return $response;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error("Order creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 处理COD预授权
     * @param int $orderId
     * @param array $cardInfo
     * @return array
     */
    public function processCodPreAuth(int $orderId, array $cardInfo = []): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->payment_type !== Order::PAYMENT_TYPE_COD) {
            return ['success' => false, 'error' => 'Not a COD order'];
        }

        if ($order->cod_status !== Order::COD_STATUS_PENDING_PREAUTH) {
            return ['success' => false, 'error' => 'Invalid order status'];
        }

        return $this->preAuthService->createPreAuth($orderId, $order->preauth_amount, $cardInfo);
    }

    /**
     * 确认收货（COD订单完成预授权扣款）
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public function confirmReceipt(int $orderId, int $userId): array
    {
        $order = Order::where('id', $orderId)
            ->where('buyer_id', $userId)
            ->find();

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->status !== Order::STATUS_PENDING_RECEIPT) {
            return ['success' => false, 'error' => 'Invalid order status'];
        }

        // COD订单：完成预授权扣款
        if ($order->payment_type === Order::PAYMENT_TYPE_COD) {
            $result = $this->preAuthService->capturePreAuth($orderId);
            // COD订单也需要结算给卖家
            if ($result['success']) {
                // 重新获取订单（状态已更新）
                $order->refresh();
                $this->settleToSeller($order);
            }
            return $result;
        }

        // 全款支付订单：直接完成并结算给卖家
        Db::startTrans();
        try {
            $order->status = Order::STATUS_COMPLETED;
            $order->completed_at = date('Y-m-d H:i:s');
            $order->received_at = date('Y-m-d H:i:s');
            $order->save();

            // 结算给卖家
            $this->settleToSeller($order);

            Db::commit();

            Log::info("Order completed and settled to seller", [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'seller_id' => $order->seller_id,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error("Order confirm receipt failed: " . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 结算货款给卖家
     * @param Order $order
     * @return void
     */
    protected function settleToSeller(Order $order): void
    {
        // 获取平台佣金比例（默认 5%）
        $commissionRate = (float)SystemConfig::getConfig('platform_commission_rate', 5);

        // 计算结算金额（订单实付金额 - 平台佣金）
        $orderAmount = (float)$order->paid_amount;
        $commissionAmount = round($orderAmount * $commissionRate / 100, 2);
        $settlementAmount = $orderAmount - $commissionAmount;

        // 创建结算记录
        Settlement::create([
            'settlement_no' => Settlement::generateNo(),
            'user_id' => $order->seller_id,
            'order_id' => $order->id,
            'order_amount' => $orderAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'settlement_amount' => $settlementAmount,
            'status' => Settlement::STATUS_SETTLED,
            'settled_at' => date('Y-m-d H:i:s'),
        ]);

        // 获取卖家钱包并增加余额
        $wallet = UserWallet::getOrCreate($order->seller_id);
        $wallet->addBalance($settlementAmount);

        // 创建交易流水记录
        Transaction::create([
            'transaction_no' => Transaction::generateNo(),
            'user_id' => $order->seller_id,
            'type' => Transaction::TYPE_INCOME,
            'amount' => $settlementAmount,
            'balance' => $wallet->balance,
            'order_id' => $order->id,
            'title' => 'Sale Income',
            'description' => "Order #{$order->order_no} completed",
            'status' => Transaction::STATUS_SUCCESS,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Log::info("Settled to seller", [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'seller_id' => $order->seller_id,
            'order_amount' => $orderAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'settlement_amount' => $settlementAmount,
            'new_balance' => $wallet->balance,
        ]);
    }

    /**
     * 拒收（COD订单取消预授权）
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @return array
     */
    public function refuseDelivery(int $orderId, int $userId, string $reason = ''): array
    {
        $order = Order::where('id', $orderId)
            ->where('buyer_id', $userId)
            ->find();

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if ($order->payment_type !== Order::PAYMENT_TYPE_COD) {
            return ['success' => false, 'error' => 'Not a COD order'];
        }

        if ($order->status !== Order::STATUS_PENDING_RECEIPT) {
            return ['success' => false, 'error' => 'Invalid order status'];
        }

        // 取消预授权
        $voidResult = $this->preAuthService->voidPreAuth($orderId, $reason);
        if (!$voidResult['success']) {
            return $voidResult;
        }

        // 更新订单状态为交易关闭（拒收）
        $order->cod_status = Order::COD_STATUS_REFUSED;
        $order->status = Order::STATUS_CLOSED;
        $order->cancel_reason = $reason ?: 'Delivery refused';
        $order->cancelled_at = date('Y-m-d H:i:s');
        $order->save();

        return $voidResult;
    }

    /**
     * 取消订单
     * @param int $orderId
     * @param int $userId
     * @param string $reason
     * @return array
     */
    public function cancelOrder(int $orderId, int $userId, string $reason = ''): array
    {
        $order = Order::where('id', $orderId)
            ->where('buyer_id', $userId)
            ->find();

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        // 只允许待付款状态取消
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return ['success' => false, 'error' => 'Cannot cancel order in current status'];
        }

        // COD订单如果已预授权，需要先取消预授权
        if ($order->payment_type === Order::PAYMENT_TYPE_COD &&
            $order->preauth_status === Order::PREAUTH_STATUS_AUTHORIZED) {
            $voidResult = $this->preAuthService->voidPreAuth($orderId, $reason);
            if (!$voidResult['success']) {
                return $voidResult;
            }
            // 重新加载订单对象，因为 voidPreAuth 已经更新了 preauth_status
            $order = Order::find($orderId);
        }

        Db::startTrans();
        try {
            // 恢复库存
            Goods::where('id', $order->goods_id)->inc('stock', $order->quantity)->update();

            // 恢复优惠券
            if ($order->coupon_id) {
                UserCoupon::where('id', $order->coupon_id)
                    ->update(['status' => 1, 'used_at' => null]);
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->cancel_reason = $reason;
            $order->cancelled_by = 'buyer';
            $order->cancelled_at = date('Y-m-d H:i:s');
            $order->save();

            Db::commit();
            return ['success' => true];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 生成订单号
     * @return string
     */
    protected function generateOrderNo(): string
    {
        return date('YmdHis') . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 计算运费
     * @param Goods $goods
     * @param UserAddress $address
     * @param array $data
     * @return float
     */
    protected function calculateShippingFee(Goods $goods, UserAddress $address, array $data): float
    {
        // 如果商品设置了免运费，直接返回0
        if ($goods->free_shipping == 1) {
            return 0;
        }
        // TODO: 根据运输商和地区计算实际运费
        // 这里先返回商品设定的运费或默认运费
        return (float)($goods->shipping_fee ?? 0);
    }

    /**
     * 应用优惠券
     * @param int $userId
     * @param int $couponId
     * @param float $amount
     * @return array
     */
    protected function applyCoupon(int $userId, int $couponId, float $amount): array
    {
        $userCoupon = UserCoupon::where('id', $couponId)
            ->where('user_id', $userId)
            ->where('status', 1)
            ->with('coupon')
            ->find();

        if (!$userCoupon || !$userCoupon->coupon) {
            return ['success' => false];
        }

        $coupon = $userCoupon->coupon;

        // 检查最低消费
        if ($amount < ($coupon->min_amount ?? 0)) {
            return ['success' => false];
        }

        // 计算折扣
        $discount = 0;
        if ($coupon->type === 1) {
            // 固定金额
            $discount = $coupon->value;
        } elseif ($coupon->type === 2) {
            // 百分比
            $discount = $amount * ($coupon->value / 100);
            if ($coupon->max_discount > 0) {
                $discount = min($discount, $coupon->max_discount);
            }
        }

        return [
            'success' => true,
            'discount' => $discount,
            'snapshot' => [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount' => $discount,
            ],
        ];
    }

    /**
     * 获取商品实际价格（检查活动价格）
     * @param Goods $goods
     * @return array ['price' => 实际价格, 'original_price' => 原价, 'promotion' => 活动信息]
     */
    protected function getGoodsPrice(Goods $goods): array
    {
        $originalPrice = (float) $goods->price;
        $price = $originalPrice;
        $promotionSnapshot = null;

        // 查找商品是否参与正在进行的活动
        $now = date('Y-m-d H:i:s');
        $promotionGoods = PromotionGoods::alias('pg')
            ->join('promotions p', 'pg.promotion_id = p.id')
            ->where('pg.goods_id', $goods->id)
            ->where('p.status', Promotion::STATUS_RUNNING)
            ->where('p.start_time', '<=', $now)
            ->where('p.end_time', '>=', $now)
            ->where('pg.stock', '>', 0)  // 活动库存充足
            ->field('pg.*, p.name as promotion_name, p.type as promotion_type, p.start_time, p.end_time')
            ->find();

        if ($promotionGoods) {
            // 使用活动价格
            $price = (float) $promotionGoods->promotion_price;
            $promotionSnapshot = [
                'id' => $promotionGoods->promotion_id,
                'name' => $promotionGoods->promotion_name,
                'type' => $promotionGoods->promotion_type,
                'discount' => $promotionGoods->discount,
                'promotion_price' => $price,
                'start_time' => $promotionGoods->start_time,
                'end_time' => $promotionGoods->end_time,
            ];
        }

        return [
            'price' => $price,
            'original_price' => $originalPrice,
            'promotion' => $promotionSnapshot,
        ];
    }

    /**
     * 验证积分使用
     * @param int $userId 用户ID
     * @param int $pointsUsed 使用的积分数量
     * @param float $pointsAmount 积分抵扣金额
     * @param float $goodsAmount 商品金额
     * @return array
     */
    protected function validatePointsUsage(int $userId, int $pointsUsed, float $pointsAmount, float $goodsAmount): array
    {
        // 检查积分余额
        $balance = UserPoints::getBalance($userId);
        if ($balance < $pointsUsed) {
            return ['success' => false, 'error' => 'Insufficient points'];
        }

        // 验证积分抵扣比例不超过 20%
        $maxDeductRatio = 0.20;
        $maxPointsAmount = $goodsAmount * $maxDeductRatio;
        if ($pointsAmount > $maxPointsAmount + 0.01) {
            return ['success' => false, 'error' => 'Points deduction exceeds 20% limit'];
        }

        // 验证积分换算关系（1000积分 = $1）
        $expectedAmount = $pointsUsed / 1000;
        if (abs($expectedAmount - $pointsAmount) > 0.01) {
            Log::warning("Points amount mismatch: expected={$expectedAmount}, received={$pointsAmount}");
        }

        return ['success' => true];
    }

    /**
     * 计算最大可用积分
     * @param int $userId 用户ID
     * @param float $goodsAmount 商品金额（原始币种，通常是 USD）
     * @param string $currency 用户币种
     * @param float $exchangeRate 汇率（用户货币相对于 USD）
     * @return array
     */
    public function calculateMaxPoints(int $userId, float $goodsAmount, string $currency = 'USD', float $exchangeRate = 1.0): array
    {
        // 获取用户积分余额
        $balance = UserPoints::getBalance($userId);

        // 最大抵扣比例 20%
        $maxDeductRatio = 0.20;

        // goodsAmount 已经是原始币种（USD），直接计算最大可抵扣金额（USD）
        $maxDeductAmountUsd = $goodsAmount * $maxDeductRatio;

        // 1000积分 = $1 USD
        $maxPointsFromAmount = (int)floor($maxDeductAmountUsd * 1000);

        // 实际可用积分 = min(用户余额, 按金额计算的最大值)
        $availablePoints = min($balance, $maxPointsFromAmount);

        // 积分抵扣金额
        $pointsAmountUsd = $availablePoints / 1000;  // USD
        $pointsAmount = $pointsAmountUsd * $exchangeRate;  // 用户币种

        return [
            'balance' => $balance,
            'available_points' => $availablePoints,
            'points_amount' => round($pointsAmount, 2),  // 用户货币显示
            'points_amount_usd' => round($pointsAmountUsd, 2),  // USD 值
            'max_deduct_ratio' => $maxDeductRatio,
            'exchange_rate' => $exchangeRate,
        ];
    }

    /**
     * 订单发货
     * @param int $orderId 订单ID
     * @param array $data 发货数据
     * @return array
     */
    public function shipOrder(int $orderId, array $data): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        // 检查订单状态
        if ($order->status !== Order::STATUS_PENDING_SHIPMENT) {
            return ['success' => false, 'error' => 'Order cannot be shipped in current status'];
        }

        // 物流单号必填
        $shippingNo = $data['shipping_no'] ?? '';
        if (empty($shippingNo)) {
            return ['success' => false, 'error' => 'Shipping number is required'];
        }

        // 获取运输商信息（优先使用传入的carrier_id，否则使用订单的carrier_id）
        $carrierId = $data['carrier_id'] ?? $order->carrier_id ?? null;
        $carrierSnapshot = null;
        $shippingCompany = $data['shipping_company'] ?? '';

        if ($carrierId) {
            $carrier = ShippingCarrier::find($carrierId);
            if ($carrier) {
                $shippingCompany = $carrier->name;
                $carrierSnapshot = [
                    'id' => $carrier->id,
                    'code' => $carrier->code,
                    'name' => $carrier->name,
                    'logo' => $carrier->logo,
                    'tracking_url' => $carrier->tracking_url,
                    'estimated_days_min' => $carrier->estimated_days_min,
                    'estimated_days_max' => $carrier->estimated_days_max,
                ];
            }
        }

        Db::startTrans();
        try {
            // 创建或更新物流记录
            $shipment = OrderShipment::where('order_id', $orderId)->find();
            if (!$shipment) {
                $shipment = new OrderShipment();
                $shipment->order_id = $orderId;
            }

            $shipment->shipping_company = $shippingCompany;
            $shipment->shipping_no = $shippingNo;
            $shipment->shipping_status = OrderShipment::STATUS_PENDING;
            $shipment->carrier_snapshot = $carrierSnapshot;
            $shipment->shipped_at = date('Y-m-d H:i:s');
            $shipment->save();

            // 更新订单状态
            $order->status = Order::STATUS_PENDING_RECEIPT;
            $order->shipped_at = date('Y-m-d H:i:s');

            // 设置自动确认收货时间（默认15天后）
            $autoReceiveDays = (int)config('order.auto_receive_days', 15);
            $order->auto_receive_at = date('Y-m-d H:i:s', strtotime("+{$autoReceiveDays} days"));

            // 如果是 COD 订单，更新 COD 状态
            if ($order->payment_type === Order::PAYMENT_TYPE_COD) {
                $order->cod_status = Order::COD_STATUS_PENDING_CONFIRM;
            }

            $order->save();

            Db::commit();

            Log::info("Order shipped: {$order->order_no}, tracking: {$shippingNo}");

            // 准备返回数据
            $responseData = [
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'status' => $order->status,
                    'shipped_at' => $order->shipped_at,
                    'shipment' => $shipment->toApiArray(),
                ],
            ];

            // 发送通知（在事务外执行，失败不影响发货结果）
            try {
                $notifyBuyer = $data['notify_buyer'] ?? true;
                $notifyEmail = $data['notify_email'] ?? true;
                $notifyMessage = $data['notify_message'] ?? true;

                if ($notifyBuyer) {
                    $channels = [];
                    if ($notifyEmail) {
                        $channels[] = NotificationService::CHANNEL_EMAIL;
                    }
                    if ($notifyMessage) {
                        $channels[] = NotificationService::CHANNEL_MESSAGE;
                    }

                    if (!empty($channels)) {
                        $this->notificationService->notifyOrderShipped(
                            $order->buyer_id,
                            $order->toArray(),
                            $shipment->toArray(),
                            $channels
                        );
                    }
                }
            } catch (\Throwable $e) {
                // 通知失败不影响发货结果，只记录日志
                Log::warning("Order shipped but notification failed: " . $e->getMessage(), [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }

            return $responseData;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error("Order ship failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 获取订单物流信息
     * @param int $orderId
     * @return array|null
     */
    public function getShipmentInfo(int $orderId): ?array
    {
        $shipment = OrderShipment::where('order_id', $orderId)->find();
        if (!$shipment) {
            return null;
        }

        return $shipment->toApiArray();
    }

    /**
     * 更新物流状态
     * @param int $orderId
     * @param int $status
     * @param array $trackingInfo 物流跟踪信息
     * @return array
     */
    public function updateShipmentStatus(int $orderId, int $status, array $trackingInfo = []): array
    {
        $shipment = OrderShipment::where('order_id', $orderId)->find();
        if (!$shipment) {
            return ['success' => false, 'error' => 'Shipment not found'];
        }

        $shipment->shipping_status = $status;

        if (!empty($trackingInfo)) {
            $existingInfo = $shipment->tracking_info ?? [];
            $existingInfo[] = [
                'time' => date('Y-m-d H:i:s'),
                'info' => $trackingInfo,
            ];
            $shipment->tracking_info = $existingInfo;
        }

        if ($status === OrderShipment::STATUS_DELIVERED) {
            $shipment->received_at = date('Y-m-d H:i:s');
        }

        $shipment->save();

        return [
            'success' => true,
            'data' => $shipment->toApiArray(),
        ];
    }
}
