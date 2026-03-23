<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\model\OrderReturn as OrderReturnModel;
use app\common\model\Order as OrderModel;
use app\common\model\User;
use app\common\helper\UrlHelper;
use app\common\service\NotificationService;
use think\Response;

/**
 * 管理端退货管理控制器
 */
class OrderReturn extends Base
{
    /**
     * 退货列表
     * GET /admin/returns
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $returnNo = input('return_no', '');
        $orderNo = input('order_no', '');
        $status = input('status', '');
        $type = input('type', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');

        $query = OrderReturnModel::with(['buyer', 'seller'])
            ->order('created_at', 'desc');

        // 筛选条件
        if ($returnNo) {
            $query->where('return_no', 'like', "%{$returnNo}%");
        }
        if ($orderNo) {
            $query->where('order_no', 'like', "%{$orderNo}%");
        }
        if ($status !== '' && is_numeric($status)) {
            $query->where('status', (int) $status);
        }
        if ($type !== '' && is_numeric($type)) {
            $query->where('type', (int) $type);
        }
        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
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
     * 退货详情
     * GET /admin/returns/:id
     */
    public function detail(int $id): Response
    {
        $returnOrder = OrderReturnModel::with(['buyer', 'seller', 'order'])
            ->find($id);

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        return $this->success($this->formatReturnDetail($returnOrder));
    }

    /**
     * 退货统计
     * GET /admin/returns/statistics
     */
    public function statistics(): Response
    {
        $today = date('Y-m-d');

        $total = OrderReturnModel::count();
        $pending = OrderReturnModel::where('status', OrderReturnModel::STATUS_PENDING)->count();
        $approved = OrderReturnModel::where('status', OrderReturnModel::STATUS_APPROVED)->count();
        $inReturn = OrderReturnModel::where('status', OrderReturnModel::STATUS_IN_RETURN)->count();
        $completed = OrderReturnModel::where('status', OrderReturnModel::STATUS_COMPLETED)->count();
        $rejected = OrderReturnModel::where('status', OrderReturnModel::STATUS_REJECTED)->count();
        $todayCount = OrderReturnModel::whereDay('created_at', $today)->count();

        return $this->success([
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'in_return' => $inReturn,
            'completed' => $completed,
            'rejected' => $rejected,
            'today' => $todayCount,
        ]);
    }

    /**
     * 同意退货申请
     * POST /admin/returns/:id/approve
     */
    public function approve(int $id): Response
    {
        $remark = input('post.remark', '');

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_PENDING) {
            return $this->error('Only pending requests can be approved');
        }

        $returnOrder->status = OrderReturnModel::STATUS_APPROVED;
        $returnOrder->admin_remark = $remark;
        $returnOrder->save();

        // 发送通知给买家
        try {
            $notificationService = new NotificationService();
            $notificationService->notifyReturnApproved(
                $returnOrder->buyer_id,
                $returnOrder->toArray()
            );
        } catch (\Exception $e) {
            // 忽略通知失败
        }

        return $this->success();
    }

    /**
     * 拒绝退货申请
     * POST /admin/returns/:id/reject
     */
    public function reject(int $id): Response
    {
        $reason = input('post.reason', '');
        $remark = input('post.remark', '');

        if (!$reason) {
            return $this->error('Reject reason is required');
        }

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_PENDING) {
            return $this->error('Only pending requests can be rejected');
        }

        $returnOrder->status = OrderReturnModel::STATUS_REJECTED;
        $returnOrder->reject_reason = $reason;
        $returnOrder->admin_remark = $remark;
        $returnOrder->save();

        // 恢复订单状态（使用保存的原始状态）
        $this->restoreOrderStatus($returnOrder->order_id, $returnOrder->original_order_status);

        // 发送通知给买家
        try {
            $notificationService = new NotificationService();
            $notificationService->notifyReturnRejected(
                $returnOrder->buyer_id,
                $returnOrder->toArray(),
                $reason
            );
        } catch (\Exception $e) {
            // 忽略通知失败
        }

        return $this->success();
    }

    /**
     * 确认收货（卖家已收到退货商品）
     * POST /admin/returns/:id/receive
     */
    public function receive(int $id): Response
    {
        $remark = input('post.remark', '');

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        if ($returnOrder->status !== OrderReturnModel::STATUS_IN_RETURN) {
            return $this->error('Return must be in shipping status');
        }

        $returnOrder->received_at = date('Y-m-d H:i:s');
        if ($remark) {
            $returnOrder->admin_remark = $remark;
        }
        $returnOrder->save();

        // 发送通知给买家
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
     * 执行退款
     * POST /admin/returns/:id/refund
     */
    public function refund(int $id): Response
    {
        $remark = input('post.remark', '');

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        // 仅退款类型：同意后可直接退款
        // 退货退款类型：需要确认收货后才能退款
        if ($returnOrder->type === OrderReturnModel::TYPE_REFUND_ONLY) {
            if ($returnOrder->status !== OrderReturnModel::STATUS_APPROVED) {
                return $this->error('Return must be approved first');
            }
        } else {
            // 退货退款需要已收货
            if (!$returnOrder->received_at) {
                return $this->error('Must confirm receipt of returned goods first');
            }
        }

        // TODO: 实际执行退款逻辑（调用支付接口）
        // 这里仅更新状态

        $returnOrder->status = OrderReturnModel::STATUS_COMPLETED;
        $returnOrder->refunded_at = date('Y-m-d H:i:s');
        if ($remark) {
            $returnOrder->admin_remark = $remark;
        }
        $returnOrder->save();

        // 更新订单状态为已退款
        $order = OrderModel::find($returnOrder->order_id);
        if ($order) {
            $order->status = OrderModel::STATUS_REFUNDED;
            $order->save();
        }

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
    }

    /**
     * 关闭退货申请
     * POST /admin/returns/:id/close
     */
    public function close(int $id): Response
    {
        $reason = input('post.reason', '');
        $remark = input('post.remark', '');

        if (!$reason) {
            return $this->error('Close reason is required');
        }

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        // 只有待处理、已同意、退货中的申请可以关闭
        $allowedStatuses = [
            OrderReturnModel::STATUS_PENDING,
            OrderReturnModel::STATUS_APPROVED,
            OrderReturnModel::STATUS_IN_RETURN,
        ];
        if (!in_array($returnOrder->status, $allowedStatuses)) {
            return $this->error('This return request cannot be closed');
        }

        $returnOrder->status = OrderReturnModel::STATUS_CANCELLED;
        $returnOrder->reject_reason = $reason;
        $returnOrder->admin_remark = $remark;
        $returnOrder->save();

        // 恢复订单状态（使用保存的原始状态）
        $this->restoreOrderStatus($returnOrder->order_id, $returnOrder->original_order_status);

        return $this->success();
    }

    /**
     * 更新备注
     * PUT /admin/returns/:id/remark
     */
    public function remark(int $id): Response
    {
        $remark = input('post.remark', '');

        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        $returnOrder->admin_remark = $remark;
        $returnOrder->save();

        return $this->success();
    }

    /**
     * 删除退货记录
     * DELETE /admin/returns/:id
     */
    public function delete(int $id): Response
    {
        $returnOrder = OrderReturnModel::find($id);
        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        $returnOrder->delete();

        return $this->success([], 'Deleted successfully');
    }

    /**
     * 批量删除退货记录
     * POST /admin/returns/batch/delete
     */
    public function batchDelete(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('Please select records to delete');
        }

        $count = OrderReturnModel::whereIn('id', $ids)->delete();

        return $this->success(['count' => $count], "成功删除 {$count} 条记录");
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

        return [
            'id' => $item->id,
            'return_no' => $item->return_no,
            'order_id' => $item->order_id,
            'order_no' => $item->order_no,
            'buyer_id' => $item->buyer_id,
            'seller_id' => $item->seller_id,
            'type' => $item->type,
            'type_text' => $this->getTypeText($item->type),
            'reason' => $item->reason,
            'reason_text' => $this->getReasonText($item->reason),
            'reason_detail' => $item->reason_detail,
            'images' => $item->images ?? [],
            'refund_amount' => $item->refund_amount,
            'currency' => $item->currency,
            'status' => $item->status,
            'status_text' => $this->getStatusText($item->status),
            'reject_reason' => $item->reject_reason,
            'admin_remark' => $item->admin_remark,
            'return_tracking_no' => $item->return_tracking_no,
            'return_carrier' => $item->return_carrier,
            'shipped_at' => $item->shipped_at,
            'received_at' => $item->received_at,
            'refunded_at' => $item->refunded_at,
            'expired_at' => $item->expired_at,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'goods_snapshot' => $goodsSnapshot,
            'buyer' => $item->buyer ? [
                'id' => $item->buyer->id,
                'nickname' => $item->buyer->nickname,
                'avatar' => UrlHelper::getFullUrl($item->buyer->avatar ?? ''),
            ] : null,
            'seller' => $item->seller ? [
                'id' => $item->seller->id,
                'nickname' => $item->seller->nickname,
                'avatar' => UrlHelper::getFullUrl($item->seller->avatar ?? ''),
            ] : null,
        ];
    }

    /**
     * 格式化退货详情
     */
    protected function formatReturnDetail($item): array
    {
        $data = $this->formatReturnItem($item);

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

    /**
     * 获取类型文本
     */
    protected function getTypeText(int $type): string
    {
        $types = [
            OrderReturnModel::TYPE_REFUND_ONLY => '仅退款',
            OrderReturnModel::TYPE_RETURN_REFUND => '退货退款',
        ];
        return $types[$type] ?? '未知';
    }

    /**
     * 获取原因文本
     */
    protected function getReasonText(string $reason): string
    {
        $reasons = OrderReturnModel::REASONS;
        return $reasons[$reason] ?? $reason;
    }

    /**
     * 获取状态文本
     */
    protected function getStatusText(int $status): string
    {
        $statuses = [
            OrderReturnModel::STATUS_PENDING => '待处理',
            OrderReturnModel::STATUS_APPROVED => '已同意',
            OrderReturnModel::STATUS_REJECTED => '已拒绝',
            OrderReturnModel::STATUS_CANCELLED => '已取消',
            OrderReturnModel::STATUS_IN_RETURN => '退货中',
            OrderReturnModel::STATUS_COMPLETED => '已完成',
        ];
        return $statuses[$status] ?? '未知';
    }
}
