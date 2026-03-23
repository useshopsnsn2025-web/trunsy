<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\facade\Db;
use app\common\model\UserWallet;
use app\common\model\Transaction;
use app\common\model\Withdrawal;
use app\common\model\UserCard;
use app\common\model\SystemConfig;
use app\common\model\Order;
use app\common\model\WithdrawalMethod;
use app\common\model\User;
use app\common\helper\UrlHelper;

/**
 * 钱包控制器
 */
class Wallet extends Base
{
    /**
     * 获取钱包信息
     * @return Response
     */
    public function index(): Response
    {
        // 获取或创建用户钱包
        $wallet = UserWallet::where('user_id', $this->userId)->find();

        if (!$wallet) {
            // 自动创建钱包
            $wallet = UserWallet::create([
                'user_id' => $this->userId,
                'balance' => 0,
                'frozen' => 0,
                'total_income' => 0,
                'total_withdraw' => 0,
            ]);
        }

        // 计算待收款金额（预授权已通过但未完成的 COD 订单，扣除佣金后的金额）
        $commissionRate = (float) SystemConfig::getConfig('platform_commission_rate', 5) / 100;

        $pendingOrders = Db::name('orders')
            ->where('seller_id', $this->userId)
            ->where('payment_type', Order::PAYMENT_TYPE_COD)
            ->where('preauth_status', Order::PREAUTH_STATUS_AUTHORIZED)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_CLOSED])
            ->column('preauth_amount');

        $processing = 0;
        foreach ($pendingOrders as $amount) {
            $processing += $amount * (1 - $commissionRate);
        }
        $processing = round($processing, 2);

        return $this->success([
            'balance' => (float) $wallet->balance,
            'frozen' => (float) $wallet->frozen,
            'processing' => (float) $processing,
            'totalIncome' => (float) $wallet->total_income,
            'totalWithdraw' => (float) $wallet->total_withdraw,
            'currency' => 'USD',
        ]);
    }

    /**
     * 获取交易记录列表
     * @return Response
     */
    public function transactions(): Response
    {
        $page = (int) input('page', 1);
        $pageSize = (int) input('pageSize', 20);
        $type = input('type');
        $startDate = input('startDate');
        $endDate = input('endDate');

        $query = Transaction::where('user_id', $this->userId);

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();

        $items = [];
        foreach ($list as $item) {
            $items[] = [
                'id' => $item->id,
                'transactionNo' => $item->transaction_no,
                'type' => $item->type,
                'amount' => (float) $item->amount,
                'balance' => (float) $item->balance,
                'title' => $this->translateTransactionTitle($item->title),
                'description' => $this->translateTransactionDescription($item->description),
                'orderId' => $item->order_id,
                'status' => $item->status,
                'createdAt' => $item->created_at,
            ];
        }

        return $this->success([
            'list' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize),
        ]);
    }

    /**
     * 翻译交易标题
     * @param string $title
     * @return string
     */
    private function translateTransactionTitle(string $title): string
    {
        // 标题翻译映射（直接映射到翻译值）
        $titleMap = [
            'Sale Income' => [
                'en-us' => 'Sale Income',
                'zh-tw' => '銷售收入',
                'ja-jp' => '販売収入',
            ],
            'Withdrawal frozen' => [
                'en-us' => 'Withdrawal Frozen',
                'zh-tw' => '提現凍結',
                'ja-jp' => '出金凍結',
            ],
            'Withdrawal Frozen' => [
                'en-us' => 'Withdrawal Frozen',
                'zh-tw' => '提現凍結',
                'ja-jp' => '出金凍結',
            ],
            'Withdrawal cancelled' => [
                'en-us' => 'Withdrawal Cancelled',
                'zh-tw' => '提現取消',
                'ja-jp' => '出金キャンセル',
            ],
            'Withdrawal Cancelled' => [
                'en-us' => 'Withdrawal Cancelled',
                'zh-tw' => '提現取消',
                'ja-jp' => '出金キャンセル',
            ],
            'Withdrawal completed' => [
                'en-us' => 'Withdrawal Completed',
                'zh-tw' => '提現完成',
                'ja-jp' => '出金完了',
            ],
            'Withdrawal Completed' => [
                'en-us' => 'Withdrawal Completed',
                'zh-tw' => '提現完成',
                'ja-jp' => '出金完了',
            ],
            'Refund' => [
                'en-us' => 'Refund',
                'zh-tw' => '退款',
                'ja-jp' => '返金',
            ],
        ];

        if (isset($titleMap[$title])) {
            $locale = $this->locale;
            // 优先使用请求的语言，降级到 en-us
            return $titleMap[$title][$locale] ?? $titleMap[$title]['en-us'] ?? $title;
        }

        return $title;
    }

    /**
     * 翻译交易描述
     * @param string|null $description
     * @return string|null
     */
    private function translateTransactionDescription(?string $description): ?string
    {
        if (empty($description)) {
            return $description;
        }

        $locale = $this->locale;

        // 订单完成描述模板
        $orderCompletedTemplates = [
            'en-us' => 'Order #{orderNo} completed',
            'zh-tw' => '訂單 #{orderNo} 已完成',
            'ja-jp' => '注文 #{orderNo} 完了',
        ];

        // 提现申请描述模板
        $withdrawalAppTemplates = [
            'en-us' => 'Withdrawal application #{withdrawalNo}',
            'zh-tw' => '提現申請 #{withdrawalNo}',
            'ja-jp' => '出金申請 #{withdrawalNo}',
        ];

        // 订单完成描述: "Order #xxx completed"
        if (preg_match('/^Order #(.+) completed/', $description, $matches)) {
            $orderNo = $matches[1];
            $template = $orderCompletedTemplates[$locale] ?? $orderCompletedTemplates['en-us'];
            return str_replace('{orderNo}', $orderNo, $template);
        }

        // 提现申请被拒描述: "Withdrawal application #xxx rejected: reason"
        if (preg_match('/^Withdrawal application #(\S+)\s+rejected:\s*(.*)$/', $description, $matches)) {
            $withdrawalNo = $matches[1];
            $reason = $matches[2];
            $rejectedTemplates = [
                'en-us' => 'Withdrawal #{withdrawalNo} rejected: {reason}',
                'zh-tw' => '提現 #{withdrawalNo} 被拒：{reason}',
                'ja-jp' => '出金 #{withdrawalNo} 却下：{reason}',
            ];
            $template = $rejectedTemplates[$locale] ?? $rejectedTemplates['en-us'];
            return str_replace(['{withdrawalNo}', '{reason}'], [$withdrawalNo, $reason], $template);
        }

        // 提现申请描述: "Withdrawal application #xxx"
        if (preg_match('/^Withdrawal application #(\S+)$/', $description, $matches)) {
            $withdrawalNo = $matches[1];
            $template = $withdrawalAppTemplates[$locale] ?? $withdrawalAppTemplates['en-us'];
            return str_replace('{withdrawalNo}', $withdrawalNo, $template);
        }

        // 退款退货描述: "Refund for return #RTxxx"
        if (preg_match('/^Refund for return #(.+)$/', $description, $matches)) {
            $returnNo = $matches[1];
            $refundTemplates = [
                'en-us' => 'Refund for return #{returnNo}',
                'zh-tw' => '退貨退款 #{returnNo}',
                'ja-jp' => '返品返金 #{returnNo}',
            ];
            $template = $refundTemplates[$locale] ?? $refundTemplates['en-us'];
            return str_replace('{returnNo}', $returnNo, $template);
        }

        // 游戏奖品描述: "$10 cash coupon (Game Log #277)" 等
        // 翻译关键词，保留金额（前端负责货币转换）
        $keywordMap = [
            'cash coupon' => ['en-us' => 'cash coupon', 'zh-tw' => '現金券', 'ja-jp' => 'キャッシュクーポン'],
            'Cash Voucher' => ['en-us' => 'Cash Voucher', 'zh-tw' => '現金券', 'ja-jp' => 'キャッシュバウチャー'],
            'Points' => ['en-us' => 'Points', 'zh-tw' => '積分', 'ja-jp' => 'ポイント'],
            'Game Log' => ['en-us' => 'Game Log', 'zh-tw' => '遊戲記錄', 'ja-jp' => 'ゲームログ'],
        ];

        $translated = $description;
        foreach ($keywordMap as $keyword => $translations) {
            $replacement = $translations[$locale] ?? $translations['en-us'];
            $translated = str_ireplace($keyword, $replacement, $translated);
        }

        return $translated;
    }

    /**
     * 获取提现记录列表
     * @return Response
     */
    public function withdrawals(): Response
    {
        $page = (int) input('page', 1);
        $pageSize = (int) input('pageSize', 20);
        $status = input('status');

        $query = Withdrawal::where('user_id', $this->userId);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();

        $items = [];
        foreach ($list as $item) {
            $items[] = [
                'id' => $item->id,
                'withdrawalNo' => $item->withdrawal_no,
                'amount' => (float) $item->amount,
                'fee' => (float) $item->fee,
                'actualAmount' => (float) $item->actual_amount,
                'method' => $item->method,
                'accountName' => $item->account_name,
                'accountNo' => $this->maskAccountNo($item->account_no),
                'bankName' => $item->bank_name,
                'status' => $item->status,
                'rejectReason' => $item->reject_reason,
                'createdAt' => $item->created_at,
                'completedAt' => $item->completed_at,
            ];
        }

        return $this->success([
            'list' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize),
        ]);
    }

    /**
     * 申请提现
     * @return Response
     */
    public function withdraw(): Response
    {
        $amount = (float) input('post.amount');
        $cardId = (int) input('post.cardId');
        $displayCurrency = input('post.displayCurrency', 'USD');
        $displayAmount = input('post.displayAmount') ? (float) input('post.displayAmount') : null;

        // 验证金额
        if ($amount <= 0) {
            return $this->error('Invalid amount');
        }

        // 获取提现配置
        $minAmount = (float) SystemConfig::getConfig('withdraw_min_amount', 10);
        $maxAmount = (float) SystemConfig::getConfig('withdraw_max_amount', 10000);
        $feeRate = (float) SystemConfig::getConfig('withdraw_fee_rate', 0);
        $fixedFee = (float) SystemConfig::getConfig('withdraw_fixed_fee', 0);
        $dailyLimit = (float) SystemConfig::getConfig('withdraw_daily_limit', 50000);

        if ($amount < $minAmount) {
            return $this->error('Minimum withdrawal amount is ' . $minAmount);
        }

        if ($amount > $maxAmount) {
            return $this->error('Maximum withdrawal amount is ' . $maxAmount);
        }

        // 检查今日提现限额
        $todayWithdraw = Withdrawal::where('user_id', $this->userId)
            ->whereDay('created_at')
            ->whereIn('status', [0, 1, 2]) // 排除已拒绝的
            ->sum('amount') ?: 0;

        if ($todayWithdraw + $amount > $dailyLimit) {
            return $this->error('Daily withdrawal limit exceeded');
        }

        // 验证银行卡
        $card = UserCard::where('id', $cardId)
            ->where('user_id', $this->userId)
            ->where('status', 1) // 正常状态
            ->find();

        if (!$card) {
            return $this->error('Bank card not found');
        }

        // 获取钱包
        $wallet = UserWallet::where('user_id', $this->userId)->find();
        if (!$wallet || $wallet->balance < $amount) {
            return $this->error('Insufficient balance');
        }

        // 计算手续费
        $fee = $amount * $feeRate + $fixedFee;
        $actualAmount = $amount - $fee;

        if ($actualAmount <= 0) {
            return $this->error('Amount too small after fee');
        }

        Db::startTrans();
        try {
            // 冻结金额
            $wallet->balance -= $amount;
            $wallet->frozen += $amount;
            $wallet->save();

            // 创建提现申请
            $withdrawal = Withdrawal::create([
                'withdrawal_no' => Withdrawal::generateNo(),
                'user_id' => $this->userId,
                'amount' => $amount,
                'fee' => $fee,
                'actual_amount' => $actualAmount,
                'display_currency' => $displayCurrency,
                'display_amount' => $displayAmount ?? $amount,
                'method' => Withdrawal::METHOD_BANK,
                'account_name' => $card->cardholder_name,
                'account_no' => $card->card_number ?? ($card->last_four ? '****' . $card->last_four : ''),
                'bank_name' => $card->card_brand ?? $card->card_type,
                'status' => Withdrawal::STATUS_PENDING,
            ]);

            // 创建交易记录 - 冻结
            // 存储英文原文，在查询时翻译
            Transaction::create([
                'transaction_no' => Transaction::generateNo(),
                'user_id' => $this->userId,
                'type' => Transaction::TYPE_FREEZE,
                'amount' => $amount,
                'balance' => $wallet->balance,
                'title' => 'Withdrawal Frozen',
                'description' => 'Withdrawal application #' . $withdrawal->withdrawal_no,
                'status' => Transaction::STATUS_SUCCESS,
            ]);

            Db::commit();

            return $this->success([
                'id' => $withdrawal->id,
                'withdrawalNo' => $withdrawal->withdrawal_no,
                'amount' => (float) $withdrawal->amount,
                'fee' => (float) $withdrawal->fee,
                'actualAmount' => (float) $withdrawal->actual_amount,
                'status' => $withdrawal->status,
            ], 'Withdrawal application submitted');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Withdrawal failed: ' . $e->getMessage());
        }
    }

    /**
     * 取消提现申请
     * @param int $id
     * @return Response
     */
    public function cancelWithdraw(int $id): Response
    {
        $withdrawal = Withdrawal::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$withdrawal) {
            return $this->error('Withdrawal not found', 404);
        }

        // 只有待审核的可以取消
        if ($withdrawal->status !== Withdrawal::STATUS_PENDING) {
            return $this->error('Cannot cancel this withdrawal');
        }

        Db::startTrans();
        try {
            // 退还冻结金额
            $wallet = UserWallet::where('user_id', $this->userId)->find();
            $wallet->balance += $withdrawal->amount;
            $wallet->frozen -= $withdrawal->amount;
            $wallet->save();

            // 更新提现状态
            $withdrawal->status = Withdrawal::STATUS_REJECTED;
            $withdrawal->reject_reason = 'Cancelled by user';
            $withdrawal->save();

            // 创建交易记录 - 解冻
            // 存储英文原文，在查询时翻译
            Transaction::create([
                'transaction_no' => Transaction::generateNo(),
                'user_id' => $this->userId,
                'type' => Transaction::TYPE_UNFREEZE,
                'amount' => $withdrawal->amount,
                'balance' => $wallet->balance,
                'title' => 'Withdrawal Cancelled',
                'description' => 'Withdrawal application #' . $withdrawal->withdrawal_no,
                'status' => Transaction::STATUS_SUCCESS,
            ]);

            Db::commit();

            return $this->success(['success' => true], 'Withdrawal cancelled');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('Cancel failed: ' . $e->getMessage());
        }
    }

    /**
     * 获取提现配置
     * @return Response
     */
    public function withdrawConfig(): Response
    {
        $minAmount = (float) SystemConfig::getConfig('withdraw_min_amount', 10);
        $maxAmount = (float) SystemConfig::getConfig('withdraw_max_amount', 10000);
        $feeRate = (float) SystemConfig::getConfig('withdraw_fee_rate', 0);
        $fixedFee = (float) SystemConfig::getConfig('withdraw_fixed_fee', 0);
        $dailyLimit = (float) SystemConfig::getConfig('withdraw_daily_limit', 50000);

        // 计算今日已提现金额
        $todayWithdraw = Withdrawal::where('user_id', $this->userId)
            ->whereDay('created_at')
            ->whereIn('status', [0, 1, 2])
            ->sum('amount') ?: 0;

        return $this->success([
            'minAmount' => $minAmount,
            'maxAmount' => $maxAmount,
            'feeRate' => $feeRate,
            'fixedFee' => $fixedFee,
            'dailyLimit' => $dailyLimit,
            'remainingLimit' => max(0, $dailyLimit - $todayWithdraw),
        ]);
    }

    /**
     * 获取用户可用的提现方式列表
     * @return Response
     */
    public function withdrawalMethods(): Response
    {
        // 获取用户信息
        $user = User::find($this->userId);
        if (!$user) {
            return $this->error('User not found');
        }

        $countryId = null;

        // 优先使用用户设置的国家，其次使用前端传的 region 参数
        $countryCode = $user->country ?: input('get.region', '');

        if ($countryCode) {
            $country = \app\common\model\Country::where('code', $countryCode)->find();
            if ($country) {
                $countryId = $country->id;
            }
        }

        // 如果没有国家ID，返回所有启用的提现方式
        if (!$countryId) {
            $methods = WithdrawalMethod::where('status', WithdrawalMethod::STATUS_ENABLED)
                ->order('sort', 'asc')
                ->select()
                ->toArray();

            // 获取翻译
            if (!empty($methods)) {
                $methodIds = array_column($methods, 'id');
                $translations = \app\common\model\WithdrawalMethodTranslation::whereIn('method_id', $methodIds)
                    ->where('locale', $this->locale)
                    ->column('name', 'method_id');

                foreach ($methods as &$method) {
                    $method['name'] = $translations[$method['id']] ?? $method['name'];
                    $method['logo'] = UrlHelper::getFullUrl($method['logo'] ?? '');
                    $method['routePath'] = $method['route_path'] ?? '';
                }
            }

            return $this->success($methods);
        }

        // 获取该国家支持的提现方式
        $methods = WithdrawalMethod::getMethodsByCountry($countryId, $this->locale);

        // 转换图片路径为完整 URL
        foreach ($methods as &$method) {
            $method['logo'] = UrlHelper::getFullUrl($method['logo'] ?? '');
        }

        return $this->success($methods);
    }

    /**
     * 遮掩账号
     * @param string $accountNo
     * @return string
     */
    private function maskAccountNo(string $accountNo): string
    {
        $len = strlen($accountNo);
        if ($len <= 4) {
            return $accountNo;
        }
        return str_repeat('*', $len - 4) . substr($accountNo, -4);
    }
}
