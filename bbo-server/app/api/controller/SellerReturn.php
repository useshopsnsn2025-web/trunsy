<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\OrderReturn as OrderReturnModel;
use app\common\model\Order as OrderModel;
use app\common\model\Goods;
use app\common\model\UserWallet;
use app\common\model\Transaction;
use app\common\model\Settlement;
use app\common\helper\UrlHelper;
use app\common\service\NotificationService;
use think\facade\Db;
use think\facade\Log;
use think\Response;

/**
 * 卖家退货管理控制器
 */
class SellerReturn extends Base
{
    /**
     * 卖家退货列表
     * GET /api/seller/returns
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', '');

        $query = OrderReturnModel::where('seller_id', $this->getUserId())
            ->with(['buyer'])
            ->order('created_at', 'desc');

        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->select()
            ->map(function ($item) {
                return $this->formatReturnItem($item);
            })
            ->toArray();

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 卖家退货详情
     * GET /api/seller/returns/:id
     */
    public function detail(int $id): Response
    {
        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->with(['buyer', 'order'])
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        return $this->success($this->formatReturnDetail($returnOrder));
    }

    /**
     * 卖家退货统计
     * GET /api/seller/returns/statistics
     */
    public function statistics(): Response
    {
        $sellerId = $this->getUserId();

        $pending = OrderReturnModel::where('seller_id', $sellerId)
            ->where('status', OrderReturnModel::STATUS_PENDING)
            ->count();
        $approved = OrderReturnModel::where('seller_id', $sellerId)
            ->where('status', OrderReturnModel::STATUS_APPROVED)
            ->count();
        $inReturn = OrderReturnModel::where('seller_id', $sellerId)
            ->where('status', OrderReturnModel::STATUS_IN_RETURN)
            ->count();
        $completed = OrderReturnModel::where('seller_id', $sellerId)
            ->where('status', OrderReturnModel::STATUS_COMPLETED)
            ->count();

        return $this->success([
            'pending' => $pending,
            'approved' => $approved,
            'in_return' => $inReturn,
            'completed' => $completed,
            'need_action' => $pending + $inReturn, // 需要处理的数量
        ]);
    }

    /**
     * 同意退货申请
     * POST /api/seller/returns/:id/approve
     */
    public function approve(int $id): Response
    {
        $reply = input('post.reply', '');

        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_PENDING) {
            return $this->error('Only pending requests can be approved');
        }

        $returnOrder->status = OrderReturnModel::STATUS_APPROVED;
        $returnOrder->seller_reply = $reply;
        $returnOrder->seller_replied_at = date('Y-m-d H:i:s');
        $returnOrder->save();

        // 发送通知给买家
        try {
            $notificationService = new NotificationService();
            $notificationService->notify(
                $returnOrder->buyer_id,
                NotificationService::TYPE_RETURN_APPROVED,
                [
                    'return' => $returnOrder,
                    'order_no' => $returnOrder->order_no,
                    'return_no' => $returnOrder->return_no,
                ]
            );
        } catch (\Exception $e) {
            // 忽略通知失败
        }

        return $this->success();
    }

    /**
     * 拒绝退货申请
     * POST /api/seller/returns/:id/reject
     */
    public function reject(int $id): Response
    {
        $reason = input('post.reason', '');
        $reply = input('post.reply', '');

        if (!$reason) {
            return $this->error('Reject reason is required');
        }

        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_PENDING) {
            return $this->error('Only pending requests can be rejected');
        }

        $returnOrder->status = OrderReturnModel::STATUS_REJECTED;
        $returnOrder->reject_reason = $reason;
        $returnOrder->seller_reply = $reply;
        $returnOrder->seller_replied_at = date('Y-m-d H:i:s');
        $returnOrder->save();

        // 恢复订单状态
        $this->restoreOrderStatus($returnOrder->order_id, $returnOrder->original_order_status);

        // 发送通知给买家
        try {
            $notificationService = new NotificationService();
            $notificationService->notify(
                $returnOrder->buyer_id,
                NotificationService::TYPE_RETURN_REJECTED,
                [
                    'return' => $returnOrder,
                    'order_no' => $returnOrder->order_no,
                    'return_no' => $returnOrder->return_no,
                    'reject_reason' => $reason,
                ]
            );
        } catch (\Exception $e) {
            // 忽略通知失败
        }

        return $this->success();
    }

    /**
     * 确认收货（卖家收到退回的商品）
     * POST /api/seller/returns/:id/receive
     */
    public function receive(int $id): Response
    {
        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_IN_RETURN) {
            return $this->error('Return must be in shipping status');
        }

        $returnOrder->received_at = date('Y-m-d H:i:s');
        $returnOrder->save();

        // 通知买家卖家已确认收货
        try {
            $notificationService = new NotificationService();
            $notificationService->notifyReturnReceived(
                $returnOrder->buyer_id,
                $returnOrder->toArray()
            );
        } catch (\Exception $e) {
            // 忽略通知失败
        }

        return $this->success();
    }

    /**
     * 执行退款（卖家确认退款）
     * POST /api/seller/returns/:id/refund
     */
    public function refund(int $id): Response
    {
        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('seller_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        // 仅退款类型：同意后可直接退款
        // 退货退款类型：需要确认收货后才能退款
        if ($returnOrder->getData('type') === OrderReturnModel::TYPE_REFUND_ONLY) {
            if ($returnOrder->status !== OrderReturnModel::STATUS_APPROVED) {
                return $this->error('Return must be approved first');
            }
        } else {
            // 退货退款需要已收货
            if (!$returnOrder->received_at) {
                return $this->error('Must confirm receipt of returned goods first');
            }
        }

        // 获取订单信息
        $order = OrderModel::find($returnOrder->order_id);
        if (!$order) {
            return $this->error('Order not found');
        }

        Db::startTrans();
        try {
            // 1. 从卖家钱包扣除退款金额
            $deductResult = $this->deductSellerWallet($returnOrder, $order);
            if (!$deductResult['success']) {
                Db::rollback();
                return $this->error($deductResult['error']);
            }

            // 2. 更新退货状态
            $returnOrder->status = OrderReturnModel::STATUS_COMPLETED;
            $returnOrder->refunded_at = date('Y-m-d H:i:s');
            $returnOrder->save();

            // 3. 更新订单状态为已退款
            $order->status = OrderModel::STATUS_REFUNDED;
            $order->save();

            Db::commit();

            Log::info("Refund processed successfully", [
                'return_id' => $returnOrder->id,
                'return_no' => $returnOrder->return_no,
                'order_id' => $order->id,
                'refund_amount' => $returnOrder->refund_amount,
                'seller_id' => $returnOrder->seller_id,
            ]);

            // 发送通知给买家（站内消息+邮件）
            try {
                $notificationService = new NotificationService();
                $notificationService->notifyReturnRefundSuccess(
                    $returnOrder->buyer_id,
                    $returnOrder->toArray()
                );
            } catch (\Exception $e) {
                // 忽略通知失败
            }

            return $this->success();

        } catch (\Exception $e) {
            Db::rollback();
            Log::error("Refund failed: " . $e->getMessage(), [
                'return_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * 从卖家钱包扣除退款金额
     * @param OrderReturnModel $returnOrder
     * @param OrderModel $order
     * @return array
     */
    protected function deductSellerWallet(OrderReturnModel $returnOrder, OrderModel $order): array
    {
        $sellerId = $returnOrder->seller_id;
        $refundAmount = (float) $returnOrder->refund_amount;

        // 查找原结算记录
        $settlement = Settlement::where('order_id', $order->id)
            ->where('user_id', $sellerId)
            ->where('status', Settlement::STATUS_SETTLED)
            ->find();

        // 计算实际需要扣除的金额（卖家实际收到的金额，已扣除佣金）
        $deductAmount = $refundAmount;
        if ($settlement) {
            // 如果有结算记录，按结算比例计算实际扣除金额
            // 退款金额 / 订单金额 * 结算金额
            $ratio = $refundAmount / (float) $settlement->order_amount;
            $deductAmount = round($ratio * (float) $settlement->settlement_amount, 2);
        }

        // 获取卖家钱包
        $wallet = UserWallet::getOrCreate($sellerId);

        // 检查余额是否足够
        if ($wallet->balance < $deductAmount) {
            return [
                'success' => false,
                'error' => 'Seller wallet balance insufficient for refund',
            ];
        }

        // 扣除余额
        $wallet->balance -= $deductAmount;
        $wallet->save();

        // 创建交易流水记录
        Transaction::create([
            'transaction_no' => Transaction::generateNo(),
            'user_id' => $sellerId,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => -$deductAmount,  // 负数表示支出
            'balance' => $wallet->balance,
            'order_id' => $order->id,
            'title' => 'Refund Deduction',
            'description' => "Refund for return #{$returnOrder->return_no}",
            'status' => Transaction::STATUS_SUCCESS,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 更新结算记录状态（可选：标记为已退款）
        if ($settlement) {
            // 如果是全额退款，可以标记结算记录
            // 这里暂不修改结算记录，保留历史记录
        }

        Log::info("Deducted seller wallet for refund", [
            'seller_id' => $sellerId,
            'return_no' => $returnOrder->return_no,
            'refund_amount' => $refundAmount,
            'deduct_amount' => $deductAmount,
            'new_balance' => $wallet->balance,
        ]);

        return ['success' => true, 'deduct_amount' => $deductAmount];
    }

    /**
     * 恢复订单状态
     */
    protected function restoreOrderStatus(int $orderId, ?int $originalStatus = null): void
    {
        $order = OrderModel::find($orderId);
        if ($order && $order->status === OrderModel::STATUS_REFUNDING) {
            // 恢复到原始状态
            if ($originalStatus !== null) {
                $order->status = $originalStatus;
            } elseif ($order->received_at) {
                // 兼容旧数据：如果没有保存原始状态，则根据 received_at 判断
                $order->status = OrderModel::STATUS_COMPLETED;
            } else {
                $order->status = OrderModel::STATUS_PENDING_RECEIPT;
            }
            $order->save();
        }
    }

    /**
     * 格式化退货列表项
     */
    protected function formatReturnItem($item): array
    {
        $goodsSnapshot = $item->goods_snapshot ?? [];
        if (!empty($goodsSnapshot['cover_image'])) {
            $goodsSnapshot['cover_image'] = UrlHelper::getFullUrl($goodsSnapshot['cover_image']);
        }

        // 使用 getData() 获取原始字段值
        $typeValue = $item->getData('type');
        $statusValue = $item->getData('status');

        return [
            'id' => $item->id,
            'return_no' => $item->return_no,
            'order_no' => $item->order_no,
            'type' => $typeValue,
            'reason' => $item->reason,
            'reason_detail' => $item->reason_detail,
            'refund_amount' => $item->refund_amount,
            'currency' => $item->currency,
            'status' => $statusValue,
            'created_at' => $item->created_at,
            'expired_at' => $item->expired_at,
            'goods_snapshot' => $goodsSnapshot,
            'buyer' => $item->buyer ? [
                'id' => $item->buyer->id,
                'nickname' => $item->buyer->nickname,
                'avatar' => UrlHelper::getFullUrl($item->buyer->avatar ?? ''),
            ] : null,
        ];
    }

    /**
     * 格式化退货详情
     */
    protected function formatReturnDetail($item): array
    {
        $data = $this->formatReturnItem($item);

        // 使用 getData() 获取原始字段值
        $typeValue = $item->getData('type');
        $statusValue = $item->getData('status');

        // 添加更多详情字段
        $data['images'] = $item->images ?? [];
        $data['seller_reply'] = $item->seller_reply;
        $data['seller_replied_at'] = $item->seller_replied_at;
        $data['reject_reason'] = $item->reject_reason;
        $data['return_tracking_no'] = $item->return_tracking_no;
        $data['return_carrier'] = $item->return_carrier;
        $data['shipped_at'] = $item->shipped_at;
        $data['received_at'] = $item->received_at;
        $data['refunded_at'] = $item->refunded_at;

        // 添加关联订单信息
        if ($item->order) {
            $data['order'] = [
                'id' => $item->order->id,
                'order_no' => $item->order->order_no,
                'status' => $item->order->status,
                'total_amount' => $item->order->total_amount,
                'currency' => $item->order->currency,
                'created_at' => $item->order->created_at,
            ];
        }

        return $data;
    }
}
