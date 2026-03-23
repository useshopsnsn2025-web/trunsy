<?php
declare(strict_types=1);

namespace app\admin\controller;

use think\Response;
use app\common\model\Transaction as TransactionModel;

/**
 * 交易流水管理控制器
 */
class Transaction extends Base
{
    /**
     * 交易流水列表
     */
    public function index(): Response
    {
        [$page, $pageSize] = $this->getPageParams();

        $query = TransactionModel::order('id', 'desc');

        $userId = input('user_id', '');
        if ($userId !== '') {
            $query->where('user_id', (int)$userId);
        }

        $type = input('type', '');
        if ($type !== '') {
            $query->where('type', (int)$type);
        }

        $status = input('status', '');
        if ($status !== '') {
            $query->where('status', (int)$status);
        }

        $transactionNo = input('transaction_no', '');
        if ($transactionNo) {
            $query->where('transaction_no', 'like', "%{$transactionNo}%");
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
     * 交易详情
     */
    public function read(int $id): Response
    {
        $transaction = TransactionModel::with(['user'])->find($id);
        if (!$transaction) {
            return $this->error('交易记录不存在', 404);
        }
        return $this->success($transaction->toArray());
    }

    /**
     * 统计数据
     */
    public function statistics(): Response
    {
        $today = date('Y-m-d');

        // 今日收入
        $todayIncome = TransactionModel::where('type', TransactionModel::TYPE_INCOME)
            ->where('status', 1)
            ->whereDay('created_at', $today)
            ->sum('amount');

        // 今日支出
        $todayExpense = TransactionModel::where('type', TransactionModel::TYPE_EXPENSE)
            ->where('status', 1)
            ->whereDay('created_at', $today)
            ->sum('amount');

        // 本月收入
        $monthIncome = TransactionModel::where('type', TransactionModel::TYPE_INCOME)
            ->where('status', 1)
            ->whereMonth('created_at')
            ->sum('amount');

        // 总收入
        $totalIncome = TransactionModel::where('type', TransactionModel::TYPE_INCOME)
            ->where('status', 1)
            ->sum('amount');

        return $this->success([
            'today_income' => (float)$todayIncome,
            'today_expense' => (float)$todayExpense,
            'month_income' => (float)$monthIncome,
            'total_income' => (float)$totalIncome,
        ]);
    }
}
