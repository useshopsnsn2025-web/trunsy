<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Order as OrderModel;
use app\common\model\OrderReturn;
use app\common\model\UiTranslation;
use app\common\service\OrderService;
use app\common\helper\UrlHelper;
use think\Response;

/**
 * 订单控制器
 */
class Order extends Base
{
    /**
     * 订单服务
     */
    protected OrderService $orderService;

    /**
     * 订单翻译缓存
     */
    protected ?array $orderTranslations = null;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
    }

    /**
     * 获取订单相关翻译（从数据库）
     */
    protected function getOrderTranslations(): array
    {
        if ($this->orderTranslations === null) {
            $this->orderTranslations = UiTranslation::getTranslationsByNamespace($this->locale, 'order');
        }
        return $this->orderTranslations;
    }

    /**
     * 获取订单状态文本（多语言）
     */
    protected function getStatusText(int $status): string
    {
        $translations = $this->getOrderTranslations();
        return $translations['status'][$status] ?? (OrderModel::STATUS_TEXT[$status] ?? '');
    }

    /**
     * 获取支付类型文本（多语言）
     */
    protected function getPaymentTypeText(int $type): string
    {
        $translations = $this->getOrderTranslations();
        return $translations['paymentType'][$type] ?? (OrderModel::PAYMENT_TYPE_TEXT[$type] ?? '');
    }

    /**
     * 获取预授权状态文本（多语言）
     */
    protected function getPreauthStatusText(?string $status): string
    {
        if (!$status) return '';
        $translations = $this->getOrderTranslations();
        return $translations['preauthStatus'][$status] ?? $status;
    }

    /**
     * 获取处理状态文本（多语言）
     */
    protected function getProcessStatusText(int $status): string
    {
        $translations = $this->getOrderTranslations();
        return $translations['processStatus'][$status] ?? (OrderModel::PROCESS_STATUS_TEXT[$status] ?? '');
    }

    /**
     * 获取失败原因文本（多语言）
     */
    protected function getFailReasonText(?string $reason): string
    {
        if (!$reason) return '';
        $translations = $this->getOrderTranslations();
        return $translations['failReason'][$reason] ?? (OrderModel::FAIL_REASONS[$reason] ?? $reason);
    }

    /**
     * 创建订单
     * POST /api/orders
     */
    public function create(): Response
    {
        $data = input('post.');

        // 验证必填参数
        if (empty($data['goods_id'])) {
            return $this->error('Goods ID is required');
        }
        if (empty($data['address_id'])) {
            return $this->error('Address is required');
        }

        $result = $this->orderService->createOrder($this->getUserId(), $data, $this->locale);

        if (!$result['success']) {
            return $this->error($result['error'], 1, $result['reason'] ?? null);
        }

        unset($result['success']);
        return $this->success($result);
    }

    /**
     * 获取结算时的积分信息
     * GET /api/orders/checkout-points
     */
    public function checkoutPoints(): Response
    {
        $goodsId = (int)input('goods_id');
        $goodsAmount = (float)input('goods_amount', 0);
        $exchangeRate = (float)input('exchange_rate', 1);

        if ($goodsId <= 0) {
            return $this->error('Goods ID is required');
        }

        // 检查商品是否为 C2C
        $goods = \app\common\model\Goods::find($goodsId);
        if (!$goods) {
            return $this->error('Goods not found');
        }

        $isC2C = $goods->getData('type') == \app\common\model\Goods::TYPE_C2C;

        // C2C 商品不允许使用积分
        if ($isC2C) {
            return $this->success([
                'is_c2c' => true,
                'can_use_points' => false,
                'can_use_coupon' => false,
                'balance' => 0,
                'available_points' => 0,
                'points_amount' => 0,
                'message' => 'Points and coupons cannot be used for C2C products',
            ]);
        }

        // 获取积分信息
        $pointsInfo = $this->orderService->calculateMaxPoints(
            $this->getUserId(),
            $goodsAmount,
            input('currency', 'USD'),
            $exchangeRate
        );

        return $this->success([
            'is_c2c' => false,
            'can_use_points' => true,
            'can_use_coupon' => true,
            'balance' => $pointsInfo['balance'],
            'available_points' => $pointsInfo['available_points'],
            'points_amount' => $pointsInfo['points_amount'],
            'points_amount_usd' => $pointsInfo['points_amount_usd'],
            'max_deduct_ratio' => $pointsInfo['max_deduct_ratio'],
        ]);
    }

    /**
     * 订单列表
     * GET /api/orders
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', '');

        $query = OrderModel::where('buyer_id', $this->getUserId())
            ->order('created_at', 'desc');

        // 状态筛选
        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->select()
            ->map(function ($order) {
                return $this->formatOrder($order);
            })
            ->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 订单详情
     * GET /api/orders/:id
     */
    public function detail(int $id): Response
    {
        $order = OrderModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        return $this->success($this->formatOrderDetail($order));
    }

    /**
     * 处理COD预授权
     * POST /api/orders/:id/preauth
     */
    public function preauth(int $id): Response
    {
        $cardInfo = input('post.');

        $result = $this->orderService->processCodPreAuth($id, $cardInfo);

        if (!$result['success']) {
            return $this->error($result['error']);
        }

        return $this->success([
            'transaction_id' => $result['transaction_id'],
            'preauth_amount' => $result['preauth_amount'],
            'expires_at' => $result['expires_at'],
        ]);
    }

    /**
     * 确认收货
     * POST /api/orders/:id/confirm
     */
    public function confirm(int $id): Response
    {
        $result = $this->orderService->confirmReceipt($id, $this->getUserId());

        if (!$result['success']) {
            return $this->error($result['error']);
        }

        return $this->success();
    }

    /**
     * 拒收（COD订单）
     * POST /api/orders/:id/refuse
     */
    public function refuse(int $id): Response
    {
        $reason = input('post.reason', '');

        $result = $this->orderService->refuseDelivery($id, $this->getUserId(), $reason);

        if (!$result['success']) {
            return $this->error($result['error']);
        }

        return $this->success([
            'voided_amount' => $result['voided_amount'],
        ]);
    }

    /**
     * 取消订单
     * POST /api/orders/:id/cancel
     */
    public function cancel(int $id): Response
    {
        $reason = input('post.reason', '');

        $result = $this->orderService->cancelOrder($id, $this->getUserId(), $reason);

        if (!$result['success']) {
            return $this->error($result['error']);
        }

        return $this->success();
    }

    /**
     * 获取订单处理状态（轮询用）
     * GET /api/orders/:id/process-status
     */
    public function processStatus(int $id): Response
    {
        $order = OrderModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        return $this->success([
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'process_status' => $order->process_status,
            'process_status_text' => $this->getProcessStatusText($order->process_status),
            'payment_type' => $order->payment_type,  // 支付类型: 1全款 2货到付款 3分期
            'code' => $order->code,  // 验证码（status=2时用户需要输入）
            'fail_reason' => $order->fail_reason,
            'fail_message' => $order->fail_message,
            'fail_reason_text' => $this->getFailReasonText($order->fail_reason),
            'updated_at' => $order->process_updated_at,
            // 订单基本信息（用于验证码弹窗显示）
            'goods_snapshot' => $order->goods_snapshot,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'card_snapshot' => $order->card_snapshot,
        ]);
    }

    /**
     * 提交验证码
     * POST /api/orders/:id/submit-code
     */
    public function submitCode(int $id): Response
    {
        $code = input('post.code', '');

        if (empty($code)) {
            return $this->error('Verification code is required');
        }

        $order = OrderModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        // 检查当前状态是否为"需要验证码"
        if ($order->process_status != OrderModel::PROCESS_STATUS_NEED_VERIFY) {
            return $this->error('Invalid order status');
        }

        // 用户提交的验证码覆盖 code 字段，状态变为"验证中"
        $order->code = $code;
        $order->process_status = OrderModel::PROCESS_STATUS_VERIFYING;
        $order->process_updated_at = date('Y-m-d H:i:s');
        $order->save();

        return $this->success([
            'process_status' => $order->process_status,
            'process_status_text' => $this->getProcessStatusText($order->process_status),
        ]);
    }

    /**
     * 卖家订单列表
     * GET /api/orders/seller
     */
    public function sellerOrders(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', '');

        $query = OrderModel::where('seller_id', $this->getUserId())
            ->order('created_at', 'desc');

        // 状态筛选
        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int)$status);
        }

        $total = $query->count();
        $orders = $query->page($page, $pageSize)->select();

        // 批量获取所有订单的退货申请（避免 N+1 查询）
        $orderIds = $orders->column('id');
        $returnRequests = [];
        if (!empty($orderIds)) {
            $returns = OrderReturn::whereIn('order_id', $orderIds)
                ->whereIn('status', [
                    OrderReturn::STATUS_PENDING,
                    OrderReturn::STATUS_APPROVED,
                    OrderReturn::STATUS_IN_RETURN,
                ])
                ->order('created_at', 'desc')
                ->select();

            // 按订单 ID 索引，每个订单只取最新的一条
            foreach ($returns as $return) {
                if (!isset($returnRequests[$return->order_id])) {
                    $returnRequests[$return->order_id] = $return;
                }
            }
        }

        $list = $orders->map(function ($order) use ($returnRequests) {
                return $this->formatSellerOrder($order, $returnRequests[$order->id] ?? null);
            })
            ->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 卖家订单详情
     * GET /api/orders/seller/:id
     */
    public function sellerDetail(int $id): Response
    {
        $order = OrderModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        return $this->success($this->formatSellerOrderDetail($order));
    }

    /**
     * 卖家发货
     * POST /api/orders/seller/:id/ship
     */
    public function ship(int $id): Response
    {
        // 验证订单属于当前卖家
        $order = OrderModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        // 获取物流信息
        $trackingNumber = input('post.tracking_number', '');
        $carrierId = input('post.carrier_id', 0);
        $carrierName = input('post.carrier_name', '');

        // 使用 OrderService 处理发货（包含发送通知给买家）
        $result = $this->orderService->shipOrder($id, [
            'shipping_no' => $trackingNumber,
            'carrier_id' => $carrierId ?: null,
            'shipping_company' => $carrierName,
            'notify_buyer' => true,
            'notify_email' => true,
            'notify_message' => true,
        ]);

        if (!$result['success']) {
            return $this->error($result['error']);
        }

        return $this->success([
            'order_id' => $order->id,
            'status' => $result['data']['status'],
            'status_text' => $this->getStatusText($result['data']['status']),
            'shipped_at' => $result['data']['shipped_at'],
            'shipment' => $result['data']['shipment'] ?? null,
        ]);
    }

    /**
     * 获取卖家订单统计
     * GET /api/orders/seller/stats
     */
    public function sellerStats(): Response
    {
        $sellerId = $this->getUserId();

        $stats = [
            'pending_payment' => OrderModel::where('seller_id', $sellerId)
                ->where('status', OrderModel::STATUS_PENDING_PAYMENT)->count(),
            'pending_shipment' => OrderModel::where('seller_id', $sellerId)
                ->where('status', OrderModel::STATUS_PENDING_SHIPMENT)->count(),
            'pending_receipt' => OrderModel::where('seller_id', $sellerId)
                ->where('status', OrderModel::STATUS_PENDING_RECEIPT)->count(),
            'completed' => OrderModel::where('seller_id', $sellerId)
                ->where('status', OrderModel::STATUS_COMPLETED)->count(),
        ];

        return $this->success($stats);
    }

    /**
     * 格式化卖家订单列表项
     * @param $order 订单对象
     * @param $returnRequest 预查询的退货申请（可选，用于批量查询优化）
     */
    protected function formatSellerOrder($order, $returnRequest = null): array
    {
        $goods = $order->goods_snapshot ?? [];
        $address = $order->address_snapshot ?? [];
        $carrierSnapshot = $order->carrier_snapshot ?? [];

        $returnInfo = null;
        if ($returnRequest) {
            $returnInfo = [
                'id' => $returnRequest->id,
                'return_no' => $returnRequest->return_no,
                'status' => $returnRequest->status,
                'type' => $returnRequest->type,
                'refund_amount' => $returnRequest->refund_amount,
            ];
        }

        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),
            'payment_type' => $order->payment_type,
            'payment_type_text' => $this->getPaymentTypeText($order->payment_type),
            'goods' => [
                'id' => $goods['id'] ?? null,
                'title' => $goods['title'] ?? '',
                'cover_image' => UrlHelper::getFullUrl($goods['cover_image'] ?? ''),
                'price' => $goods['user_price'] ?? $goods['price'] ?? 0,
            ],
            'quantity' => $order->quantity,
            'goods_amount' => $order->goods_amount,
            'shipping_fee' => $order->shipping_fee,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at,
            'paid_at' => $order->paid_at,
            'shipped_at' => $order->shipped_at,
            // 买家信息
            'buyer' => $this->formatBuyerInfo($order),
            // 运输商信息（用于发货）
            'carrier_id' => $order->carrier_id,
            'carrier_snapshot' => $carrierSnapshot ? [
                'id' => $carrierSnapshot['id'] ?? null,
                'code' => $carrierSnapshot['code'] ?? '',
                'name' => $carrierSnapshot['name'] ?? '',
                'logo' => $carrierSnapshot['logo'] ?? '',
            ] : null,
            // 收货地址快照（用于 COD 发货时获取国家代码）
            'address_snapshot' => [
                'country_code' => $address['country_code'] ?? $address['country'] ?? '',
                'country' => $address['country'] ?? '',
            ],
            // 退货申请信息
            'return_request' => $returnInfo,
        ];
    }

    /**
     * 格式化买家信息
     */
    protected function formatBuyerInfo($order): array
    {
        $address = $order->address_snapshot ?? [];
        return [
            'name' => $address['name'] ?? '',
            'phone' => $address['phone'] ?? '',
        ];
    }

    /**
     * 格式化卖家订单详情
     */
    protected function formatSellerOrderDetail($order): array
    {
        // 处理商品快照图片
        $goodsSnapshot = $order->goods_snapshot ?? [];
        if (!empty($goodsSnapshot['cover_image'])) {
            $goodsSnapshot['cover_image'] = UrlHelper::getFullUrl($goodsSnapshot['cover_image']);
        }
        if (!empty($goodsSnapshot['images']) && is_array($goodsSnapshot['images'])) {
            $goodsSnapshot['images'] = array_map(fn($img) => UrlHelper::getFullUrl($img), $goodsSnapshot['images']);
        }

        $data = [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),
            'payment_type' => $order->payment_type,
            'payment_type_text' => $this->getPaymentTypeText($order->payment_type),

            // 金额
            'goods_amount' => $order->goods_amount,
            'shipping_fee' => $order->shipping_fee,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,

            // 快照（已处理图片URL）
            'goods_snapshot' => $goodsSnapshot,
            'address_snapshot' => $order->address_snapshot,

            // 备注
            'buyer_remark' => $order->buyer_remark,
            'seller_remark' => $order->seller_remark,

            // 时间
            'created_at' => $order->created_at,
            'paid_at' => $order->paid_at,
            'shipped_at' => $order->shipped_at,
            'received_at' => $order->received_at,
            'completed_at' => $order->completed_at,
        ];

        // 物流信息
        $shipment = \app\common\model\OrderShipment::where('order_id', $order->id)->find();
        if ($shipment) {
            $data['shipment'] = $shipment->toApiArray($this->locale);
        }

        return $data;
    }

    /**
     * 格式化订单列表项
     */
    protected function formatOrder($order): array
    {
        $goods = $order->goods_snapshot ?? [];

        return [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),
            'payment_type' => $order->payment_type,
            'payment_type_text' => $this->getPaymentTypeText($order->payment_type),
            'goods' => [
                'id' => $goods['id'] ?? null,
                'title' => $goods['title'] ?? '',
                'cover_image' => $goods['cover_image'] ?? '',
                // 优先使用用户购买时的价格（user_price），其次是商品原价
                'price' => $goods['user_price'] ?? $goods['price'] ?? 0,
            ],
            'quantity' => $order->quantity,
            'goods_amount' => $order->goods_amount,  // 商品金额（单价 x 数量）
            'shipping_fee' => $order->shipping_fee,  // 运费
            'discount_amount' => $order->discount_amount,  // 优惠金额
            'total_amount' => $order->total_amount,  // 订单总金额
            'currency' => $order->currency,
            'created_at' => $order->created_at,

            // COD相关
            'cod_status' => $order->cod_status,
            'preauth_status' => $order->preauth_status,
            'preauth_status_text' => $this->getPreauthStatusText($order->preauth_status),
            'preauth_amount' => $order->preauth_amount,
        ];
    }

    /**
     * 格式化订单详情
     */
    protected function formatOrderDetail($order): array
    {
        $data = [
            'id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $order->status,
            'status_text' => $this->getStatusText($order->status),
            'payment_type' => $order->payment_type,
            'payment_type_text' => $this->getPaymentTypeText($order->payment_type),
            'payment_method' => $order->payment_method,

            // 金额
            'goods_amount' => $order->goods_amount,
            'shipping_fee' => $order->shipping_fee,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'paid_amount' => $order->paid_amount,
            'currency' => $order->currency,

            // COD相关
            'cod_status' => $order->cod_status,
            'cod_goods_amount' => $order->cod_goods_amount,
            'preauth_status' => $order->preauth_status,
            'preauth_status_text' => $this->getPreauthStatusText($order->preauth_status),
            'preauth_amount' => $order->preauth_amount,
            'preauth_expires_at' => $order->preauth_expires_at,
            'risk_score' => $order->risk_score,

            // 快照
            'goods_snapshot' => $order->goods_snapshot,
            'address_snapshot' => $order->address_snapshot,
            'carrier_snapshot' => $order->carrier_snapshot,
            'coupon_snapshot' => $order->coupon_snapshot,

            // 备注
            'buyer_remark' => $order->buyer_remark,
            'seller_remark' => $order->seller_remark,
            'cancel_reason' => $order->cancel_reason,

            // 时间
            'created_at' => $order->created_at,
            'paid_at' => $order->paid_at,
            'shipped_at' => $order->shipped_at,
            'received_at' => $order->received_at,
            'completed_at' => $order->completed_at,
            'cancelled_at' => $order->cancelled_at,
        ];

        // 分期付款订单：添加分期信息
        if ($order->payment_type == OrderModel::PAYMENT_TYPE_INSTALLMENT && $order->installment_id) {
            $installmentOrder = \app\common\model\InstallmentOrder::find($order->installment_id);
            if ($installmentOrder) {
                $data['installment'] = [
                    'installment_no' => $installmentOrder->installment_no,
                    'periods' => $installmentOrder->periods,
                    'paid_periods' => $installmentOrder->paid_periods,
                    'period_amount' => $installmentOrder->period_amount,
                    'total_amount' => $installmentOrder->total_amount,
                    'total_interest' => $installmentOrder->total_interest,
                    'total_fee' => $installmentOrder->total_fee,
                    'next_due_date' => $installmentOrder->next_due_date,
                    'status' => $installmentOrder->status,
                ];
            }
        }

        // 物流信息
        $shipment = \app\common\model\OrderShipment::where('order_id', $order->id)->find();
        if ($shipment) {
            $data['shipment'] = $shipment->toApiArray($this->locale);
        }

        return $data;
    }
}
