<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Settlement as SettlementModel;

/**
 * 结算管理控制器
 */
class Settlement extends Base
{
    /**
     * 结算列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = SettlementModel::order('id', 'desc');

        $userId = input('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $settlementNo = input('settlement_no', '');
        if ($settlementNo) {
            $query->where('settlement_no', 'like', "%{$settlementNo}%");
        }

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

        return $this->paginate($list, $total, $page, $pageSize);
    }

    /**
     * 结算详情
     */
    public function read(int $id): Response
    {
        $settlement = SettlementModel::with(['user', 'orderInfo'])->find($id);
        if (!$settlement) {
            return $this->error('结算记录不存在', 404);
        }
        return $this->success($settlement->toArray());
    }

    /**
     * 执行结算
     */
    public function settle(int $id): Response
    {
        $settlement = SettlementModel::find($id);
        if (!$settlement) {
            return $this->error('结算记录不存在', 404);
        }

        if ($settlement->status == SettlementModel::STATUS_SETTLED) {
            return $this->error('该订单已结算');
        }

        $settlement->status = SettlementModel::STATUS_SETTLED;
        $settlement->settled_at = date('Y-m-d H:i:s');
        $settlement->save();

        // TODO: 将结算金额添加到卖家钱包

        return $this->success([], '结算成功');
    }

    /**
     * 批量结算
     */
    public function batchSettle(): Response
    {
        $ids = input('post.ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要结算的记录');
        }

        $count = 0;
        foreach ($ids as $id) {
            $settlement = SettlementModel::find($id);
            if ($settlement && $settlement->status == SettlementModel::STATUS_PENDING) {
                $settlement->status = SettlementModel::STATUS_SETTLED;
                $settlement->settled_at = date('Y-m-d H:i:s');
                $settlement->save();
                $count++;
            }
        }

        return $this->success(['count' => $count], "已结算 {$count} 条记录");
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $pending = SettlementModel::where('status', SettlementModel::STATUS_PENDING)->count();
        $pendingAmount = SettlementModel::where('status', SettlementModel::STATUS_PENDING)
            ->sum('settlement_amount');

        $settledAmount = SettlementModel::where('status', SettlementModel::STATUS_SETTLED)
            ->sum('settlement_amount');

        $totalCommission = SettlementModel::sum('commission_amount');

        return $this->success([
            'pending' => $pending,
            'pending_amount' => (float)$pendingAmount,
            'settled_amount' => (float)$settledAmount,
            'total_commission' => (float)$totalCommission,
        ]);
    }
}
