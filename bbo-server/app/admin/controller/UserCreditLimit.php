<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\UserCreditLimit as CreditLimitModel;
use app\common\model\CreditLimitLog;
use app\common\model\InstallmentOrder;

/**
 * 用户额度管理控制器
 */
class UserCreditLimit extends Base
{
    /**
     * 额度列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = CreditLimitModel::field('*')->order('id', 'desc');

        // 搜索条件
        $userId = input('user_id', '');
        $status = input('status', '');
        $creditLevel = input('credit_level', '');

        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        if ($creditLevel !== '') {
            $query->where('credit_level', (int)$creditLevel);
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
     * 额度详情
     */
    public function read(int $id): Response
    {
        $credit = CreditLimitModel::find($id);

        if (!$credit) {
            return $this->error('记录不存在', 404);
        }

        $data = $credit->toArray();

        // 加载用户信息
        try {
            $data['user'] = $credit->user ? $credit->user->toArray() : null;
        } catch (\Exception $e) {
            $data['user'] = null;
        }

        // 获取额度变动记录
        $data['logs'] = CreditLimitLog::where('user_id', $credit->user_id)
            ->order('created_at', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        // 获取分期订单统计
        $data['order_stats'] = [
            'total' => InstallmentOrder::where('user_id', $credit->user_id)->count(),
            'active' => InstallmentOrder::where('user_id', $credit->user_id)
                ->where('status', InstallmentOrder::STATUS_ACTIVE)
                ->count(),
            'completed' => InstallmentOrder::where('user_id', $credit->user_id)
                ->where('status', InstallmentOrder::STATUS_COMPLETED)
                ->count(),
            'overdue' => InstallmentOrder::where('user_id', $credit->user_id)
                ->where('status', InstallmentOrder::STATUS_OVERDUE)
                ->count(),
        ];

        return $this->success($data);
    }

    /**
     * 调整额度
     */
    public function adjust(int $id): Response
    {
        $credit = CreditLimitModel::find($id);

        if (!$credit) {
            return $this->error('记录不存在', 404);
        }

        $newLimit = (float)input('total_limit', 0);
        $remark = input('remark', '');

        if ($newLimit <= 0) {
            return $this->error('请输入有效的额度');
        }

        // 新额度不能小于已使用额度
        if ($newLimit < $credit->used_limit) {
            return $this->error('新额度不能小于已使用额度');
        }

        $beforeLimit = $credit->total_limit;
        $credit->total_limit = $newLimit;
        $credit->save();

        // 记录额度变动
        CreditLimitLog::log(
            $credit->user_id,
            CreditLimitLog::TYPE_ADJUST,
            $newLimit - $beforeLimit,
            $beforeLimit,
            $newLimit,
            $credit->used_limit,
            $credit->used_limit,
            null,
            null,
            $remark ?: '管理员调整额度',
            $this->adminId
        );

        return $this->success([], '额度已调整');
    }

    /**
     * 冻结额度
     */
    public function freeze(int $id): Response
    {
        $credit = CreditLimitModel::find($id);

        if (!$credit) {
            return $this->error('记录不存在', 404);
        }

        if ($credit->status === CreditLimitModel::STATUS_FROZEN) {
            return $this->error('额度已经是冻结状态');
        }

        $remark = input('remark', '');

        $credit->status = CreditLimitModel::STATUS_FROZEN;
        $credit->save();

        // 记录变动
        CreditLimitLog::log(
            $credit->user_id,
            CreditLimitLog::TYPE_FREEZE,
            $credit->available_limit,
            $credit->total_limit,
            $credit->total_limit,
            $credit->used_limit,
            $credit->used_limit,
            null,
            null,
            $remark ?: '管理员冻结额度',
            $this->adminId
        );

        return $this->success([], '额度已冻结');
    }

    /**
     * 解冻额度
     */
    public function unfreeze(int $id): Response
    {
        $credit = CreditLimitModel::find($id);

        if (!$credit) {
            return $this->error('记录不存在', 404);
        }

        if ($credit->status !== CreditLimitModel::STATUS_FROZEN) {
            return $this->error('当前状态不是冻结');
        }

        $remark = input('remark', '');

        $credit->status = CreditLimitModel::STATUS_NORMAL;
        $credit->save();

        // 记录变动
        CreditLimitLog::log(
            $credit->user_id,
            CreditLimitLog::TYPE_UNFREEZE,
            $credit->available_limit,
            $credit->total_limit,
            $credit->total_limit,
            $credit->used_limit,
            $credit->used_limit,
            null,
            null,
            $remark ?: '管理员解冻额度',
            $this->adminId
        );

        return $this->success([], '额度已解冻');
    }

    /**
     * 修改信用等级
     */
    public function updateLevel(int $id): Response
    {
        $credit = CreditLimitModel::find($id);

        if (!$credit) {
            return $this->error('记录不存在', 404);
        }

        $level = (int)input('credit_level', 1);
        $remark = input('remark', '');

        if ($level < 1 || $level > 5) {
            return $this->error('信用等级必须在1-5之间');
        }

        $credit->credit_level = $level;
        $credit->save();

        return $this->success([], '信用等级已更新');
    }

    /**
     * 额度变动记录
     */
    public function logs(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = CreditLimitLog::field('*')->order('created_at', 'desc');

        $userId = input('user_id', '');
        $type = input('type', '');

        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $total = (clone $query)->count();
        $list = $query->page($page, $pageSize)->select();

        return $this->paginate($list->toArray(), $total, $page, $pageSize);
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $total = CreditLimitModel::count();
        $active = CreditLimitModel::where('status', CreditLimitModel::STATUS_NORMAL)->count();
        $frozen = CreditLimitModel::where('status', CreditLimitModel::STATUS_FROZEN)->count();
        $closed = CreditLimitModel::where('status', CreditLimitModel::STATUS_CLOSED)->count();

        // 总授信额度
        $totalLimit = CreditLimitModel::where('status', CreditLimitModel::STATUS_NORMAL)->sum('total_limit');
        // 总使用额度
        $totalUsed = CreditLimitModel::where('status', CreditLimitModel::STATUS_NORMAL)->sum('used_limit');
        // 总可用额度
        $totalAvailable = $totalLimit - $totalUsed;
        // 使用率
        $usageRate = $totalLimit > 0 ? round($totalUsed / $totalLimit * 100, 1) : 0;

        // 按信用等级分布
        $levelDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $levelDistribution[$i] = CreditLimitModel::where('credit_level', $i)->count();
        }

        return $this->success([
            'total_users' => $total,
            'active_users' => $active,
            'frozen_users' => $frozen,
            'closed_users' => $closed,
            'total_limit' => (float)$totalLimit,
            'total_used' => (float)$totalUsed,
            'total_available' => (float)$totalAvailable,
            'usage_rate' => $usageRate,
            'level_distribution' => $levelDistribution,
        ]);
    }
}
