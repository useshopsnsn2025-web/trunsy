<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use think\facade\Db;
use app\common\model\Withdrawal as WithdrawalModel;
use app\common\model\UserWallet;
use app\common\model\Transaction;

/**
 * 提现管理控制器
 */
class Withdrawal extends Base
{
    /**
     * 提现申请列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = WithdrawalModel::order('id', 'desc');

        $userId = input('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $method = input('method', '');
        if ($method !== '') {
            $query->where('method', (int)$method);
        }

        $withdrawalNo = input('withdrawal_no', '');
        if ($withdrawalNo) {
            $query->where('withdrawal_no', 'like', "%{$withdrawalNo}%");
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
     * 提现详情
     */
    public function read(int $id): Response
    {
        $withdrawal = WithdrawalModel::with(['user'])->find($id);
        if (!$withdrawal) {
            return $this->error('提现记录不存在', 404);
        }
        return $this->success($withdrawal->toArray());
    }

    /**
     * 审核通过
     */
    public function approve(int $id): Response
    {
        $withdrawal = WithdrawalModel::find($id);
        if (!$withdrawal) {
            return $this->error('提现记录不存在', 404);
        }

        if ($withdrawal->status != WithdrawalModel::STATUS_PENDING) {
            return $this->error('该申请已处理');
        }

        $withdrawal->status = WithdrawalModel::STATUS_PROCESSING;
        $withdrawal->admin_id = $this->getAdminId();
        $withdrawal->audited_at = date('Y-m-d H:i:s');
        $withdrawal->save();

        return $this->success([], '审核通过，处理中');
    }

    /**
     * 审核拒绝
     */
    public function reject(int $id): Response
    {
        $withdrawal = WithdrawalModel::find($id);
        if (!$withdrawal) {
            return $this->error('提现记录不存在', 404);
        }

        if ($withdrawal->status != WithdrawalModel::STATUS_PENDING) {
            return $this->error('该申请已处理');
        }

        $reason = input('post.reason', '');
        if (empty($reason)) {
            return $this->error('请填写拒绝原因');
        }

        Db::startTrans();
        try {
            // 更新提现状态
            $withdrawal->status = WithdrawalModel::STATUS_REJECTED;
            $withdrawal->reject_reason = $reason;
            $withdrawal->admin_id = $this->getAdminId();
            $withdrawal->audited_at = date('Y-m-d H:i:s');
            $withdrawal->save();

            // 退还冻结金额到用户钱包
            $wallet = UserWallet::where('user_id', $withdrawal->user_id)->find();
            if ($wallet) {
                $wallet->balance += $withdrawal->amount;
                $wallet->frozen -= $withdrawal->amount;
                $wallet->save();

                // 创建交易记录 - 解冻
                Transaction::create([
                    'transaction_no' => Transaction::generateNo(),
                    'user_id' => $withdrawal->user_id,
                    'type' => Transaction::TYPE_UNFREEZE,
                    'amount' => $withdrawal->amount,
                    'balance' => $wallet->balance,
                    'title' => 'Withdrawal Rejected',
                    'description' => 'Withdrawal application #' . $withdrawal->withdrawal_no . ' rejected: ' . $reason,
                    'status' => Transaction::STATUS_SUCCESS,
                ]);
            }

            Db::commit();
            return $this->success([], '已拒绝');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 标记完成
     */
    public function complete(int $id): Response
    {
        $withdrawal = WithdrawalModel::find($id);
        if (!$withdrawal) {
            return $this->error('提现记录不存在', 404);
        }

        if ($withdrawal->status != WithdrawalModel::STATUS_PROCESSING) {
            return $this->error('只能完成处理中的申请');
        }

        Db::startTrans();
        try {
            // 更新提现状态
            $withdrawal->status = WithdrawalModel::STATUS_COMPLETED;
            $withdrawal->completed_at = date('Y-m-d H:i:s');
            $withdrawal->save();

            // 扣减冻结金额，更新累计提现
            $wallet = UserWallet::where('user_id', $withdrawal->user_id)->find();
            if ($wallet) {
                $wallet->frozen -= $withdrawal->amount;
                $wallet->total_withdraw += $withdrawal->actual_amount;
                $wallet->save();

                // 创建交易记录 - 提现完成
                Transaction::create([
                    'transaction_no' => Transaction::generateNo(),
                    'user_id' => $withdrawal->user_id,
                    'type' => Transaction::TYPE_EXPENSE,
                    'amount' => $withdrawal->actual_amount,
                    'balance' => $wallet->balance,
                    'title' => 'Withdrawal Completed',
                    'description' => 'Withdrawal application #' . $withdrawal->withdrawal_no . ' completed',
                    'status' => Transaction::STATUS_SUCCESS,
                ]);
            }

            Db::commit();
            return $this->success([], '已完成');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $pending = WithdrawalModel::where('status', WithdrawalModel::STATUS_PENDING)->count();
        $processing = WithdrawalModel::where('status', WithdrawalModel::STATUS_PROCESSING)->count();

        $todayAmount = WithdrawalModel::where('status', WithdrawalModel::STATUS_COMPLETED)
            ->whereDay('completed_at', date('Y-m-d'))
            ->sum('actual_amount');

        $totalAmount = WithdrawalModel::where('status', WithdrawalModel::STATUS_COMPLETED)
            ->sum('actual_amount');

        return $this->success([
            'pending' => $pending,
            'processing' => $processing,
            'today_amount' => (float)$todayAmount,
            'total_amount' => (float)$totalAmount,
        ]);
    }

    /**
     * 获取当前管理员ID（临时方法）
     */
    private function getAdminId(): int
    {
        // TODO: 从认证中获取
        return 1;
    }
}
