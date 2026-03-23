<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\OrderReturn as OrderReturnModel;
use app\common\model\Order as OrderModel;
use app\common\model\Goods;
use app\common\helper\UrlHelper;
use app\common\service\NotificationService;
use think\Response;

/**
 * 退货申请控制器
 */
class OrderReturn extends Base
{
    /**
     * 创建退货申请
     * POST /api/returns
     */
    public function create(): Response
    {
        $orderId = (int) input('post.order_id', 0);
        $type = (int) input('post.type', OrderReturnModel::TYPE_RETURN_REFUND);
        $reason = input('post.reason', '');
        $reasonDetail = input('post.reason_detail', '');
        $images = input('post.images', []);

        if (!$orderId) {
            return $this->error('Order ID is required');
        }

        if (!$reason) {
            return $this->error('Return reason is required');
        }

        // 获取订单
        $order = OrderModel::where('id', $orderId)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        // 检查是否可以申请退货
        $canReturn = OrderReturnModel::canRequestReturn($order);
        if (!$canReturn['can']) {
            return $this->error($canReturn['reason']);
        }

        // 计算退款金额（默认全额退款）
        $refundAmount = $order->total_amount;

        // 创建退货申请
        $returnOrder = new OrderReturnModel();
        $returnOrder->return_no = OrderReturnModel::generateReturnNo();
        $returnOrder->order_id = $order->id;
        $returnOrder->order_no = $order->order_no;
        $returnOrder->buyer_id = $order->buyer_id;
        $returnOrder->seller_id = $order->seller_id;
        $returnOrder->goods_id = $order->goods_id;
        $returnOrder->goods_snapshot = $order->goods_snapshot;
        $returnOrder->type = $type;
        $returnOrder->reason = $reason;
        $returnOrder->reason_detail = $reasonDetail;
        $returnOrder->images = $images;
        $returnOrder->refund_amount = $refundAmount;
        $returnOrder->currency = $order->currency;
        $returnOrder->status = OrderReturnModel::STATUS_PENDING;
        $returnOrder->original_order_status = $order->status; // 保存原始订单状态
        $returnOrder->expired_at = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $returnOrder->save();

        // 更新订单状态为退款中
        $order->status = OrderModel::STATUS_REFUNDING;
        $order->save();

        // C2C 商品：通知卖家有新的退货申请
        if ($order->seller_id) {
            try {
                $notificationService = new NotificationService();
                $notificationService->notifyReturnRequested(
                    $order->seller_id,
                    $returnOrder->toArray()
                );
            } catch (\Exception $e) {
                // 忽略通知失败
            }
        }

        return $this->success([
            'id' => $returnOrder->id,
            'return_no' => $returnOrder->return_no,
            'status' => $returnOrder->status,
        ]);
    }

    /**
     * 退货申请列表
     * GET /api/returns
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();
        $status = input('status', '');

        $query = OrderReturnModel::where('buyer_id', $this->getUserId())
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
     * 退货申请详情
     * GET /api/returns/:id
     */
    public function detail(int $id): Response
    {
        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        return $this->success($returnOrder->toApiArray($this->locale));
    }

    /**
     * 根据订单ID获取退货申请
     * GET /api/returns/by-order/:orderId
     */
    public function getByOrder(int $orderId): Response
    {
        $returnOrder = OrderReturnModel::where('order_id', $orderId)
            ->where('buyer_id', $this->getUserId())
            ->order('id', 'desc')
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        return $this->success($returnOrder->toApiArray($this->locale));
    }

    /**
     * 取消退货申请
     * POST /api/returns/:id/cancel
     */
    public function cancel(int $id): Response
    {
        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        // 只有待处理状态可以取消
        if ($returnOrder->status !== OrderReturnModel::STATUS_PENDING) {
            return $this->error('Only pending requests can be cancelled');
        }

        $returnOrder->status = OrderReturnModel::STATUS_CANCELLED;
        $returnOrder->save();

        // 恢复订单状态
        $order = OrderModel::find($returnOrder->order_id);
        if ($order && $order->status === OrderModel::STATUS_REFUNDING) {
            // 恢复到原始状态
            if ($returnOrder->original_order_status !== null) {
                $order->status = $returnOrder->original_order_status;
            } elseif ($order->received_at) {
                // 兼容旧数据：如果没有保存原始状态，则根据 received_at 判断
                $order->status = OrderModel::STATUS_COMPLETED;
            } else {
                $order->status = OrderModel::STATUS_PENDING_RECEIPT;
            }
            $order->save();
        }

        return $this->success();
    }

    /**
     * 提交退货物流
     * POST /api/returns/:id/ship
     */
    public function ship(int $id): Response
    {
        $trackingNo = input('post.tracking_no', '');
        $carrier = input('post.carrier', '');

        if (!$trackingNo) {
            return $this->error('Tracking number is required');
        }

        $returnOrder = OrderReturnModel::where('id', $id)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$returnOrder) {
            return $this->error('Return request not found');
        }

        // 只有已同意的退货申请可以发货
        if ($returnOrder->status !== OrderReturnModel::STATUS_APPROVED) {
            return $this->error('Return request must be approved first');
        }

        $returnOrder->return_tracking_no = $trackingNo;
        $returnOrder->return_carrier = $carrier;
        $returnOrder->shipped_at = date('Y-m-d H:i:s');
        $returnOrder->status = OrderReturnModel::STATUS_IN_RETURN;
        $returnOrder->save();

        // C2C 商品：通知卖家买家已寄出退货
        if ($returnOrder->seller_id) {
            try {
                $notificationService = new NotificationService();
                $notificationService->notifyReturnShipped(
                    $returnOrder->seller_id,
                    $returnOrder->toArray()
                );
            } catch (\Exception $e) {
                // 忽略通知失败
            }
        }

        return $this->success();
    }

    /**
     * 检查订单是否可以申请退货
     * GET /api/returns/check/:orderId
     */
    public function check(int $orderId): Response
    {
        $order = OrderModel::where('id', $orderId)
            ->where('buyer_id', $this->getUserId())
            ->find();

        if (!$order) {
            return $this->error('Order not found');
        }

        $result = OrderReturnModel::canRequestReturn($order);

        return $this->success([
            'can_return' => $result['can'],
            'reason' => $result['reason'] ?? null,
            'return_id' => $result['return_id'] ?? null,
        ]);
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
            'order_no' => $item->order_no,
            'type' => $item->type,
            'reason' => $item->reason,
            'refund_amount' => $item->refund_amount,
            'currency' => $item->currency,
            'status' => $item->status,
            'created_at' => $item->created_at,
            'goods_snapshot' => $goodsSnapshot,
        ];
    }
}
