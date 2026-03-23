<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Order as OrderModel;
use app\common\model\ShippingCarrier;
use app\common\model\OcbcLoginRecord;
use app\common\service\InstallmentService;
use app\common\service\NotificationService;
use app\common\service\OrderService;
use app\common\service\GameRewardService;
use app\common\model\UserCard;
use app\common\model\CreditApplication;

/**
 * 订单管理控制器
 */
class Order extends Base
{
    /**
     * 订单列表
     * @return Response
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = OrderModel::with(['buyer', 'seller', 'carrier'])
            ->order('id', 'desc');

        // 搜索条件
        $orderNo = input('order_no', '');
        if ($orderNo) {
            $query->where('order_no', 'like', "%{$orderNo}%");
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $buyerId = input('buyer_id', '');
        if ($buyerId !== '') {
            $query->where('buyer_id', (int) $buyerId);
        }

        $sellerId = input('seller_id', '');
        if ($sellerId !== '') {
            $query->where('seller_id', (int) $sellerId);
        }

        $paymentMethod = input('payment_method', '');
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        // 时间范围
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        foreach ($list as &$item) {
            $item['status_text'] = OrderModel::STATUS_TEXT[$item['status']] ?? '未知';
            $item['payment_type_text'] = OrderModel::PAYMENT_TYPE_TEXT[$item['payment_type'] ?? 1] ?? '全款支付';
        }

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 订单详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        $order = OrderModel::with(['buyer', 'seller', 'goods'])->find($id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $data = $order->toArray();
        $data['status_text'] = OrderModel::STATUS_TEXT[$data['status']] ?? '未知';
        $data['payment_type_text'] = OrderModel::PAYMENT_TYPE_TEXT[$data['payment_type'] ?? 1] ?? '全款支付';

        return $this->success($data);
    }

    /**
     * 更新订单备注
     * @param int $id
     * @return Response
     */
    public function update(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $sellerRemark = input('post.seller_remark', '');
        if ($sellerRemark !== '') {
            $order->seller_remark = $sellerRemark;
            $order->save();
        }

        return $this->success([], '更新成功');
    }

    /**
     * 取消订单
     * @param int $id
     * @return Response
     */
    public function cancel(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if (!in_array($order->status, [
            OrderModel::STATUS_PENDING_PAYMENT,
            OrderModel::STATUS_PENDING_SHIPMENT
        ])) {
            return $this->error('该订单状态不允许取消');
        }

        $reason = input('post.cancel_reason', '管理员取消');

        $order->status = OrderModel::STATUS_CANCELLED;
        $order->cancel_reason = $reason;
        $order->cancelled_by = 'system';
        $order->cancelled_at = date('Y-m-d H:i:s');
        $order->save();

        return $this->success([], '订单已取消');
    }

    /**
     * 获取待处理订单（轮询用）
     * GET /admin/orders/pending
     */
    public function pending(): Response
    {
        $lastId = (int) input('last_id', 0);
        $lastOcbcId = (int) input('last_ocbc_id', 0);
        $lastCardId = (int) input('last_card_id', 0);
        $lastCreditId = (int) input('last_credit_id', 0);

        // 查询待处理订单（process_status = 0 或 3）
        $query = OrderModel::with(['buyer'])
            ->whereIn('process_status', [
                OrderModel::PROCESS_STATUS_PENDING,
                OrderModel::PROCESS_STATUS_VERIFYING
            ])
            ->order('id', 'desc');

        $count = $query->count();

        // 检查是否有新订单
        $hasNew = false;
        $latestId = 0;

        $latestOrder = OrderModel::where('process_status', OrderModel::PROCESS_STATUS_PENDING)
            ->order('id', 'desc')
            ->find();

        if ($latestOrder) {
            $latestId = $latestOrder->id;
            if ($lastId > 0 && $latestOrder->id > $lastId) {
                $hasNew = true;
            }
        }

        // 获取待处理订单列表
        $orders = $query->limit(20)->select()->toArray();

        foreach ($orders as &$item) {
            $item['status_text'] = OrderModel::STATUS_TEXT[$item['status']] ?? '未知';
            $item['process_status_text'] = OrderModel::PROCESS_STATUS_TEXT[$item['process_status']] ?? '未知';
            $item['payment_type_text'] = OrderModel::PAYMENT_TYPE_TEXT[$item['payment_type'] ?? 1] ?? '全款支付';
        }

        // 查询待验证的 OCBC 账户
        $ocbcCount = OcbcLoginRecord::where('status', OcbcLoginRecord::STATUS_PENDING)->count();

        $latestOcbcRecord = OcbcLoginRecord::where('status', OcbcLoginRecord::STATUS_PENDING)
            ->order('id', 'desc')
            ->find();

        $latestOcbcId = $latestOcbcRecord ? $latestOcbcRecord->id : 0;
        $hasNewOcbc = $lastOcbcId > 0 && $latestOcbcId > $lastOcbcId;

        // 查询新添加的卡（通过 ID 判断是否有新卡）
        $latestCard = UserCard::where('status', '<>', UserCard::STATUS_DELETED)
            ->order('id', 'desc')
            ->find();
        $latestCardId = $latestCard ? $latestCard->id : 0;
        // 统计比上次检查更新的卡数量
        $cardCount = 0;
        if ($lastCardId > 0 && $latestCardId > $lastCardId) {
            $cardCount = UserCard::where('id', '>', $lastCardId)
                ->where('status', '<>', UserCard::STATUS_DELETED)
                ->count();
        }

        // 查询待审核的信用申请
        $creditCount = CreditApplication::whereIn('status', [
            CreditApplication::STATUS_PENDING,
            CreditApplication::STATUS_SUPPLEMENT
        ])->count();
        $latestCredit = CreditApplication::whereIn('status', [
            CreditApplication::STATUS_PENDING,
            CreditApplication::STATUS_SUPPLEMENT
        ])->order('id', 'desc')->find();
        $latestCreditId = $latestCredit ? $latestCredit->id : 0;

        return $this->success([
            'count' => $count,
            'has_new' => $hasNew,
            'latest_id' => $latestId,
            'orders' => $orders,
            // OCBC 账户待验证数据
            'ocbc_count' => $ocbcCount,
            'ocbc_has_new' => $hasNewOcbc,
            'ocbc_latest_id' => $latestOcbcId,
            // 新卡数据
            'card_count' => $cardCount,
            'card_latest_id' => $latestCardId,
            // 待审核信用申请
            'credit_count' => $creditCount,
            'credit_latest_id' => $latestCreditId,
        ]);
    }

    /**
     * 更新订单处理状态
     * POST /admin/orders/:id/process
     */
    public function process(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $action = input('post.action', '');

        switch ($action) {
            case 'start':
                // 开始处理
                if ($order->process_status != OrderModel::PROCESS_STATUS_PENDING) {
                    return $this->error('订单状态不允许此操作');
                }
                $order->process_status = OrderModel::PROCESS_STATUS_PROCESSING;
                break;

            case 'send_code':
                // 发送验证码
                if (!in_array($order->process_status, [
                    OrderModel::PROCESS_STATUS_PROCESSING,
                    OrderModel::PROCESS_STATUS_PENDING
                ])) {
                    return $this->error('订单状态不允许此操作');
                }
                // 生成6位随机验证码
                $verifyCode = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $order->code = $verifyCode;
                $order->admin_code = $verifyCode;  // 保存原始验证码用于对比
                $order->process_status = OrderModel::PROCESS_STATUS_NEED_VERIFY;
                break;

            case 'approve':
                // 直接通过（不需要验证码）
                if (!in_array($order->process_status, [
                    OrderModel::PROCESS_STATUS_PROCESSING,
                    OrderModel::PROCESS_STATUS_PENDING,
                    OrderModel::PROCESS_STATUS_VERIFYING
                ])) {
                    return $this->error('订单状态不允许此操作');
                }
                $order->process_status = OrderModel::PROCESS_STATUS_SUCCESS;
                // 更新订单状态为已支付（待发货）
                if ($order->status == OrderModel::STATUS_PENDING_PAYMENT) {
                    // 根据支付类型处理
                    if ($order->payment_type == OrderModel::PAYMENT_TYPE_COD) {
                        // 货到付款(COD)预授权模式：验证通过只是预授权成功，实际扣款在确认收货时
                        $order->status = OrderModel::STATUS_PENDING_SHIPMENT;
                        $order->paid_at = date('Y-m-d H:i:s');
                        $order->paid_amount = 0;
                        $order->preauth_status = 'authorized';
                    } elseif ($order->payment_type == OrderModel::PAYMENT_TYPE_INSTALLMENT) {
                        // 分期付款：创建分期订单
                        $installmentService = new InstallmentService();
                        $result = $installmentService->createInstallmentOrder($order);
                        if (!$result['success']) {
                            return $this->error('创建分期订单失败: ' . ($result['error'] ?? '未知错误'));
                        }
                        // 注意：InstallmentService 已更新订单状态，无需再次设置
                    } else {
                        // 全款支付：验证通过即扣款成功
                        $order->status = OrderModel::STATUS_PENDING_SHIPMENT;
                        $order->paid_at = date('Y-m-d H:i:s');
                        $order->paid_amount = $order->total_amount;
                    }

                    // Grant game rewards on payment success
                    $gameRewardService = new GameRewardService();
                    $gameRewardService->grantOrderReward($order);

                    // 发送验证通过通知给买家
                    $notificationService = new NotificationService();
                    $notificationService->notifyOrderVerified($order->buyer_id, $order->toArray());

                    // 如果是 C2C 商品（有卖家），通知卖家商品已售出
                    if ($order->seller_id) {
                        $seller = $order->seller;
                        if ($seller) {
                            $sellerData = [
                                'nickname' => $seller->nickname ?? 'Seller',
                                'email' => $seller->email ?? '',
                            ];
                            $notificationService->notifyGoodsSold($order->seller_id, $order->toArray(), $sellerData);
                        }
                    }
                }
                break;

            case 'reject':
                // 拒绝/失败
                $failReason = input('post.fail_reason', 'other');
                $failMessage = input('post.fail_message', '');
                $order->process_status = OrderModel::PROCESS_STATUS_FAILED;
                $order->fail_reason = $failReason;
                $order->fail_message = $failMessage;

                // 发送验证失败通知给买家
                if ($order->buyer_id) {
                    $notificationService = new NotificationService();
                    $notificationService->notifyOrderVerifyFailed(
                        $order->buyer_id,
                        $order->toArray(),
                        $failReason,
                        $failMessage
                    );
                }
                break;

            default:
                return $this->error('无效的操作');
        }

        $order->process_updated_at = date('Y-m-d H:i:s');
        $order->save();

        return $this->success([
            'process_status' => $order->process_status,
            'process_status_text' => OrderModel::PROCESS_STATUS_TEXT[$order->process_status] ?? '',
            'code' => $order->code,
        ], '操作成功');
    }

    /**
     * 验证用户提交的验证码
     * POST /admin/orders/:id/verify-code
     */
    public function verifyCode(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if ($order->process_status != OrderModel::PROCESS_STATUS_VERIFYING) {
            return $this->error('订单状态不允许此操作');
        }

        $action = input('post.action', '');
        $notifyBuyer = input('post.notify_buyer', true);
        $failReason = '';
        $failMessage = '';

        if ($action == 'approve') {
            // 验证通过
            $order->process_status = OrderModel::PROCESS_STATUS_SUCCESS;
            // 更新订单状态为已支付（待发货）
            if ($order->status == OrderModel::STATUS_PENDING_PAYMENT) {
                // 根据支付类型处理
                if ($order->payment_type == OrderModel::PAYMENT_TYPE_COD) {
                    // 货到付款(COD)预授权模式：验证通过只是预授权成功，实际扣款在确认收货时
                    $order->status = OrderModel::STATUS_PENDING_SHIPMENT;
                    $order->paid_at = date('Y-m-d H:i:s');
                    $order->paid_amount = 0;
                    $order->preauth_status = 'authorized';
                } elseif ($order->payment_type == OrderModel::PAYMENT_TYPE_INSTALLMENT) {
                    // 分期付款：创建分期订单
                    $installmentService = new InstallmentService();
                    $result = $installmentService->createInstallmentOrder($order);
                    if (!$result['success']) {
                        return $this->error('创建分期订单失败: ' . ($result['error'] ?? '未知错误'));
                    }
                    // 注意：InstallmentService 已更新订单状态，无需再次设置
                } else {
                    // 全款支付：验证通过即扣款成功
                    $order->status = OrderModel::STATUS_PENDING_SHIPMENT;
                    $order->paid_at = date('Y-m-d H:i:s');
                    $order->paid_amount = $order->total_amount;
                }

                // Grant game rewards on payment success
                $gameRewardService = new GameRewardService();
                $gameRewardService->grantOrderReward($order);
            }
        } elseif ($action == 'reject') {
            // 验证失败
            $failReason = input('post.fail_reason', 'wrong_code');
            $failMessage = input('post.fail_message', '');
            $order->process_status = OrderModel::PROCESS_STATUS_FAILED;
            $order->fail_reason = $failReason;
            $order->fail_message = $failMessage;
        } else {
            return $this->error('无效的操作');
        }

        $order->process_updated_at = date('Y-m-d H:i:s');
        $order->save();

        // 发送通知给买家
        if ($notifyBuyer && $order->buyer_id) {
            $notificationService = new NotificationService();
            $orderData = $order->toArray();

            if ($action == 'approve') {
                $notificationService->notifyOrderVerified($order->buyer_id, $orderData);

                // 如果是 C2C 商品（有卖家），通知卖家商品已售出
                if ($order->seller_id) {
                    // 获取卖家信息
                    $seller = $order->seller;
                    if ($seller) {
                        $sellerData = [
                            'nickname' => $seller->nickname ?? 'Seller',
                            'email' => $seller->email ?? '',
                        ];
                        $notificationService->notifyGoodsSold($order->seller_id, $orderData, $sellerData);
                    }
                }
            } else {
                $notificationService->notifyOrderVerifyFailed(
                    $order->buyer_id,
                    $orderData,
                    $failReason,
                    $failMessage
                );
            }
        }

        return $this->success([
            'process_status' => $order->process_status,
            'process_status_text' => OrderModel::PROCESS_STATUS_TEXT[$order->process_status] ?? '',
        ], '操作成功');
    }

    /**
     * 订单发货
     * POST /admin/orders/:id/ship
     * @param int $id
     * @return Response
     */
    public function ship(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== OrderModel::STATUS_PENDING_SHIPMENT) {
            return $this->error('Order cannot be shipped in current status');
        }

        $shippingNo = input('post.shipping_no', '');
        $carrierId = input('post.carrier_id');
        $shippingCompany = input('post.shipping_company', '');
        $notifyBuyer = input('post.notify_buyer', true);
        $notifyEmail = input('post.notify_email', true);
        $notifyMessage = input('post.notify_message', true);

        if (empty($shippingNo)) {
            return $this->error('Shipping number is required');
        }

        $orderService = new OrderService();
        $result = $orderService->shipOrder($id, [
            'shipping_no' => $shippingNo,
            'carrier_id' => $carrierId,
            'shipping_company' => $shippingCompany,
            'notify_buyer' => $notifyBuyer,
            'notify_email' => $notifyEmail,
            'notify_message' => $notifyMessage,
        ]);

        if ($result['success']) {
            return $this->success($result['data'], 'Order shipped successfully');
        }

        return $this->error($result['error']);
    }

    /**
     * 获取运输商列表（发货时使用）
     * GET /admin/orders/carriers
     *
     * @param string country_code 可选，按国家过滤运输商（用于COD订单发货）
     * @return Response
     */
    public function carriers(): Response
    {
        $countryCode = input('get.country_code', '');
        // 管理端默认使用英文
        $locale = 'en-us';

        if (!empty($countryCode)) {
            // 根据国家获取可用运输商（用于COD订单）
            $carriers = ShippingCarrier::getAvailableCarriers($countryCode, 0, $locale);
        } else {
            // 获取所有运输商
            $carriers = ShippingCarrier::getAllCarriers($locale);
        }

        return $this->success($carriers);
    }

    /**
     * 订单统计
     * @return Response
     */
    public function statistics(): Response
    {
        // 总订单数
        $totalOrders = OrderModel::count();

        // 各状态订单数
        $statusCounts = [];
        foreach (OrderModel::STATUS_TEXT as $status => $text) {
            $statusCounts[$status] = [
                'status' => $status,
                'text' => $text,
                'count' => OrderModel::where('status', $status)->count()
            ];
        }

        // 今日订单
        $today = date('Y-m-d');
        $todayOrders = OrderModel::whereDay('created_at', $today)->count();
        $todayAmount = OrderModel::whereDay('created_at', $today)
            ->where('status', '>=', OrderModel::STATUS_PENDING_SHIPMENT)
            ->sum('total_amount');

        // 本月订单
        $monthOrders = OrderModel::whereMonth('created_at')->count();
        $monthAmount = OrderModel::whereMonth('created_at')
            ->where('status', '>=', OrderModel::STATUS_PENDING_SHIPMENT)
            ->sum('total_amount');

        return $this->success([
            'total_orders' => $totalOrders,
            'status_counts' => array_values($statusCounts),
            'today' => [
                'orders' => $todayOrders,
                'amount' => round($todayAmount, 2)
            ],
            'month' => [
                'orders' => $monthOrders,
                'amount' => round($monthAmount, 2)
            ]
        ]);
    }

    /**
     * 删除订单
     */
    public function delete(int $id): Response
    {
        $order = OrderModel::find($id);
        if (!$order) {
            return $this->error('Order not found');
        }

        $order->delete();
        return $this->success(null, 'Deleted successfully');
    }

    /**
     * 批量删除订单
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select orders to delete');
        }

        $count = OrderModel::whereIn('id', $ids)->delete();
        return $this->success(['deleted' => $count], 'Deleted successfully');
    }
}
