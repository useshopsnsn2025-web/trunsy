<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\InstallmentOrder as OrderModel;
use app\common\model\InstallmentSchedule;
use app\common\model\InstallmentPayment;

/**
 * 分期订单管理控制器
 */
class InstallmentOrder extends Base
{
    /**
     * 订单列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = OrderModel::field('*')->order('id', 'desc');

        // 搜索条件
        $keyword = input('keyword', '');
        $status = input('status', '');
        $userId = input('user_id', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');
        $overdue = input('overdue', '');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('installment_no', 'like', "%{$keyword}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // 筛选逾期订单
        if ($overdue === '1') {
            $query->where('overdue_days', '>', 0);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        $result = [];
        foreach ($list as $item) {
            $data = $item->toArray();
            // 加载用户信息
            try {
                $data['user'] = $item->user ? [
                    'id' => $item->user->id,
                    'nickname' => $item->user->nickname,
                    'email' => $item->user->email,
                ] : null;
            } catch (\Exception $e) {
                $data['user'] = null;
            }
            $result[] = $data;
        }

        return $this->paginate($result, $total, $page, $pageSize);
    }

    /**
     * 订单详情
     */
    public function read(int $id): Response
    {
        $order = OrderModel::find($id);

        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $data = $order->toArray();

        // 加载用户信息
        try {
            $data['user'] = $order->user ? $order->user->toArray() : null;
        } catch (\Exception $e) {
            $data['user'] = null;
        }

        // 获取还款计划
        $data['schedules'] = InstallmentSchedule::where('installment_id', $id)
            ->order('period', 'asc')
            ->select()
            ->toArray();

        // 获取支付记录
        $data['payments'] = InstallmentPayment::where('installment_id', $id)
            ->order('created_at', 'desc')
            ->select()
            ->toArray();

        return $this->success($data);
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $total = OrderModel::count();
        $active = OrderModel::where('status', OrderModel::STATUS_ACTIVE)->count();
        $completed = OrderModel::where('status', OrderModel::STATUS_COMPLETED)->count();
        $overdue = OrderModel::where('status', OrderModel::STATUS_OVERDUE)->count();
        $cancelled = OrderModel::where('status', OrderModel::STATUS_CANCELLED)->count();

        // 总分期金额
        $totalAmount = OrderModel::where('status', '<>', OrderModel::STATUS_CANCELLED)
            ->sum('financed_amount');

        // 总已还金额（基于已还期数 * 每期金额估算）
        $orders = OrderModel::where('status', '<>', OrderModel::STATUS_CANCELLED)
            ->field('paid_periods, period_amount')
            ->select();
        $totalRepaid = 0;
        foreach ($orders as $order) {
            $totalRepaid += $order->paid_periods * $order->period_amount;
        }

        // 本月新增订单
        $monthStart = date('Y-m-01');
        $monthNew = OrderModel::where('created_at', '>=', $monthStart . ' 00:00:00')->count();

        return $this->success([
            'total_orders' => $total,
            'active_orders' => $active,
            'completed_orders' => $completed,
            'overdue_orders' => $overdue,
            'cancelled_orders' => $cancelled,
            'total_amount' => (float)$totalAmount,
            'total_repaid' => (float)$totalRepaid,
            'month_new' => $monthNew,
        ]);
    }

    /**
     * 还款计划列表
     */
    public function schedules(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = InstallmentSchedule::field('*')->order('due_date', 'asc');

        $installmentId = input('installment_id', '');
        $status = input('status', '');
        $startDate = input('start_date', '');
        $endDate = input('end_date', '');

        if ($installmentId !== '') {
            $query->where('installment_id', (int)$installmentId);
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($startDate) {
            $query->where('due_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('due_date', '<=', $endDate);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        return $this->paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 支付记录列表
     */
    public function payments(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = InstallmentPayment::field('*')->order('created_at', 'desc');

        $installmentId = input('installment_id', '');
        $status = input('status', '');

        if ($installmentId !== '') {
            $query->where('installment_id', (int)$installmentId);
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        return $this->paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 获取状态列表
     */
    public function statusList(): Response
    {
        return $this->success([
            ['value' => OrderModel::STATUS_ACTIVE, 'label' => '还款中'],
            ['value' => OrderModel::STATUS_COMPLETED, 'label' => '已结清'],
            ['value' => OrderModel::STATUS_OVERDUE, 'label' => '已逾期'],
            ['value' => OrderModel::STATUS_CANCELLED, 'label' => '已取消'],
        ]);
    }
}
