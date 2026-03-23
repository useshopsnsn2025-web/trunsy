<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Game as GameModel;
use app\common\model\GamePrize;
use app\common\model\UserGameChance;
use app\common\model\UserGameLog;
use app\common\model\UserPoints;
use app\common\model\PointLog;
use app\common\model\ChanceLog;
use app\common\model\DailyPrizeStats;
use app\common\model\Coupon;
use app\common\model\UserCoupon;
use app\common\model\UserWallet;
use app\common\model\Transaction;
use app\common\service\GameRewardService;

/**
 * 游戏控制器
 */
class Game extends Base
{
    /**
     * 获取游戏列表
     */
    public function index()
    {
        $games = GameModel::getActiveGames();

        $result = [];
        foreach ($games as $game) {
            $gameModel = GameModel::find($game['id']);
            if ($gameModel && $gameModel->isActive()) {
                $result[] = $gameModel->toApiArray($this->locale);
            }
        }

        return $this->success($result);
    }

    /**
     * 获取游戏详情
     */
    public function detail(string $code)
    {
        $game = GameModel::getByCode($code);
        if (!$game || !$game->isActive()) {
            return $this->error('Game not found or not active', 404);
        }

        $result = $game->toApiArray($this->locale);

        // 获取奖品列表
        $prizes = GamePrize::where('game_id', $game->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $result['prizes'] = [];
        foreach ($prizes as $prize) {
            $result['prizes'][] = $prize->toApiArray($this->locale);
        }

        // 获取用户游戏次数（如果已登录）
        if ($this->userId) {
            $result['user_chances'] = UserGameChance::getChances($this->userId, $code);
        }

        return $this->success($result);
    }

    /**
     * 获取游戏奖品列表
     */
    public function prizes(string $code)
    {
        $game = GameModel::getByCode($code);
        if (!$game || !$game->isActive()) {
            return $this->error('Game not found or not active', 404);
        }

        // 获取奖品列表
        $prizes = GamePrize::where('game_id', $game->id)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $result = [];
        foreach ($prizes as $prize) {
            $result[] = $prize->toApiArray($this->locale);
        }

        return $this->success($result);
    }

    /**
     * 获取用户所有游戏次数
     */
    public function chances()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $chances = UserGameChance::getAllChances($this->userId);

        return $this->success($chances);
    }

    /**
     * 转盘抽奖
     */
    public function spin()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $game = GameModel::getByCode(GameModel::CODE_WHEEL);
        if (!$game || !$game->isActive()) {
            return $this->error('Game not available', 400);
        }

        // 检查次数
        $chances = UserGameChance::getChances($this->userId, GameModel::CODE_WHEEL);
        if ($chances <= 0) {
            return $this->error('No chances left', 400);
        }

        // 使用次数
        if (!UserGameChance::useChance($this->userId, GameModel::CODE_WHEEL)) {
            return $this->error('Failed to use chance', 500);
        }

        // 执行抽奖
        $prize = $this->lottery($game->id);

        // 记录游戏日志
        $log = UserGameLog::createLog(
            $this->userId,
            $game->id,
            GameModel::CODE_WHEEL,
            $prize,
            $this->request->ip(),
            $this->request->header('X-Device-Id', '')
        );

        // 发放奖品
        if ($prize) {
            $this->issuePrize($prize, $this->userId, $log->id);
        }

        return $this->success([
            'prize' => $prize ? $prize->toApiArray($this->locale) : null,
            'remaining_chances' => UserGameChance::getChances($this->userId, GameModel::CODE_WHEEL),
            'log_id' => $log->id,
        ]);
    }

    /**
     * 砸金蛋
     */
    public function smashEgg()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $game = GameModel::getByCode(GameModel::CODE_EGG);
        if (!$game || !$game->isActive()) {
            return $this->error('Game not available', 400);
        }

        // 检查次数
        $chances = UserGameChance::getChances($this->userId, GameModel::CODE_EGG);
        if ($chances <= 0) {
            return $this->error('No chances left', 400);
        }

        // 使用次数
        if (!UserGameChance::useChance($this->userId, GameModel::CODE_EGG)) {
            return $this->error('Failed to use chance', 500);
        }

        // 执行抽奖
        $prize = $this->lottery($game->id);

        // 记录游戏日志
        $log = UserGameLog::createLog(
            $this->userId,
            $game->id,
            GameModel::CODE_EGG,
            $prize,
            $this->request->ip(),
            $this->request->header('X-Device-Id', '')
        );

        // 发放奖品
        if ($prize) {
            $this->issuePrize($prize, $this->userId, $log->id);
        }

        return $this->success([
            'prize' => $prize ? $prize->toApiArray($this->locale) : null,
            'remaining_chances' => UserGameChance::getChances($this->userId, GameModel::CODE_EGG),
            'log_id' => $log->id,
        ]);
    }

    /**
     * 刮刮卡
     */
    public function scratch()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $game = GameModel::getByCode(GameModel::CODE_SCRATCH);
        if (!$game || !$game->isActive()) {
            return $this->error('Game not available', 400);
        }

        // 检查次数
        $chances = UserGameChance::getChances($this->userId, GameModel::CODE_SCRATCH);
        if ($chances <= 0) {
            return $this->error('No chances left', 400);
        }

        // 使用次数
        if (!UserGameChance::useChance($this->userId, GameModel::CODE_SCRATCH)) {
            return $this->error('Failed to use chance', 500);
        }

        // 执行抽奖
        $prize = $this->lottery($game->id);

        // 记录游戏日志
        $log = UserGameLog::createLog(
            $this->userId,
            $game->id,
            GameModel::CODE_SCRATCH,
            $prize,
            $this->request->ip(),
            $this->request->header('X-Device-Id', '')
        );

        // 发放奖品
        if ($prize) {
            $this->issuePrize($prize, $this->userId, $log->id);
        }

        return $this->success([
            'prize' => $prize ? $prize->toApiArray($this->locale) : null,
            'remaining_chances' => UserGameChance::getChances($this->userId, GameModel::CODE_SCRATCH),
            'log_id' => $log->id,
        ]);
    }

    /**
     * 获取用户游戏记录
     */
    public function logs()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);
        $gameCode = $this->request->get('game_code');

        $result = UserGameLog::getUserLogs($this->userId, $page, $pageSize, $gameCode, $this->locale);

        return $this->success($result);
    }

    /**
     * 获取中奖播报
     */
    public function winners()
    {
        $limit = (int)$this->request->get('limit', 20);
        $minValue = (float)$this->request->get('min_value', 10);

        $winners = UserGameLog::getRecentWinners($limit, $minValue);

        // 获取所有涉及的奖品ID，用于批量获取翻译和类型
        $prizeIds = array_filter(array_unique(array_column($winners, 'prize_id')));
        $prizeData = []; // 存储奖品名称和类型
        if (!empty($prizeIds)) {
            $prizes = GamePrize::whereIn('id', $prizeIds)->select();
            foreach ($prizes as $prize) {
                $prizeData[$prize->id] = [
                    'name' => $prize->getTranslated('name', $this->locale),
                    'type' => $prize->getData('type'),
                ];
            }
        }

        $result = [];
        foreach ($winners as $winner) {
            $prizeId = $winner['prize_id'];

            // 优先使用翻译后的奖品名称，否则使用快照名称
            $prizeName = $winner['prize_name'];
            if (isset($prizeData[$prizeId]['name'])) {
                $prizeName = $prizeData[$prizeId]['name'];
            }

            // 优先从奖品表获取类型，否则使用日志中的类型
            $prizeType = $winner['prize_type'];
            if (isset($prizeData[$prizeId]['type'])) {
                $prizeType = $prizeData[$prizeId]['type'];
            }

            $result[] = [
                'user_nickname' => UserGameLog::maskUsername($winner['nickname'] ?? 'User'),
                'prize_name' => $prizeName,
                'prize_type' => $prizeType,
                'prize_value' => (float)$winner['prize_value'],
                'time' => $winner['created_at'],
            ];
        }

        return $this->success($result);
    }

    /**
     * 每日登录奖励状态
     * 返回所有游戏的领取状态
     */
    public function dailyLoginStatus()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        // 检查今日是否已领取（只要领取过任意一个游戏就算已领取）
        $claimed = ChanceLog::hasClaimedLoginReward($this->userId, GameModel::CODE_WHEEL);

        // 每日登录奖励：转盘1次、砸金蛋1次、刮刮卡1次
        $rewards = [
            ['game_code' => GameModel::CODE_WHEEL, 'chances' => 1, 'name' => 'wheel'],
            ['game_code' => GameModel::CODE_EGG, 'chances' => 1, 'name' => 'egg'],
            ['game_code' => GameModel::CODE_SCRATCH, 'chances' => 1, 'name' => 'scratch'],
        ];

        return $this->success([
            'claimed' => $claimed,
            'rewards' => $rewards,
            'total_chances' => 3, // 总共 3 次机会
        ]);
    }

    /**
     * 每日登录领取次数
     * 一次性发放所有游戏各 1 次
     */
    public function claimLoginReward()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        // 检查今日是否已领取（以转盘为准）
        if (ChanceLog::hasClaimedLoginReward($this->userId, GameModel::CODE_WHEEL)) {
            return $this->error('Already claimed today', 400);
        }

        $ip = $this->request->ip();
        $addedChances = [];

        // 发放所有游戏各 1 次
        $games = [
            GameModel::CODE_WHEEL,
            GameModel::CODE_EGG,
            GameModel::CODE_SCRATCH,
        ];

        foreach ($games as $gameCode) {
            $added = UserGameChance::addChances(
                $this->userId,
                $gameCode,
                1,
                ChanceLog::SOURCE_LOGIN,
                '',
                $ip
            );

            if ($added) {
                $addedChances[$gameCode] = UserGameChance::getChances($this->userId, $gameCode);
            }
        }

        if (empty($addedChances)) {
            return $this->error('Failed to claim reward', 500);
        }

        return $this->success([
            'chances_added' => count($addedChances),
            'chances' => $addedChances,
        ]);
    }

    /**
     * Get order reward preview (what rewards were granted for an order)
     */
    public function orderReward()
    {
        $orderNo = $this->request->get('order_no', '');
        if (!$orderNo) {
            return $this->error('Order number required', 400);
        }

        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        // Check wheel chances granted for this order
        $wheelLog = ChanceLog::where('user_id', $this->userId)
            ->where('game_code', GameModel::CODE_WHEEL)
            ->where('source', ChanceLog::SOURCE_ORDER)
            ->where('source_id', $orderNo)
            ->find();

        // Check egg chance granted for this order
        $eggLog = ChanceLog::where('user_id', $this->userId)
            ->where('game_code', GameModel::CODE_EGG)
            ->where('source', ChanceLog::SOURCE_ORDER)
            ->where('source_id', $orderNo)
            ->find();

        // Get egg tier if egg was granted
        $eggTier = null;
        if ($eggLog) {
            // Try to get the egg tier from order amount
            $order = \app\common\model\Order::where('order_no', $orderNo)
                ->where('buyer_id', $this->userId)
                ->find();
            if ($order) {
                $eggTier = \app\common\model\EggTier::getEggByOrderAmount((float)$order->total_amount);
            }
        }

        return $this->success([
            'wheel_chances' => $wheelLog ? (int)$wheelLog->chances : 0,
            'egg_granted' => $eggLog ? true : false,
            'egg_code' => $eggTier ? $eggTier->code : null,
            'egg_name' => $eggTier ? $eggTier->getTranslated('name', $this->locale) : null,
        ]);
    }

    /**
     * Get reward preview for an order amount (before payment)
     */
    public function rewardPreview()
    {
        $amount = (float)$this->request->get('amount', 0);

        $gameRewardService = new GameRewardService();
        $preview = $gameRewardService->getOrderRewardPreview($amount);

        return $this->success($preview);
    }

    /**
     * 执行抽奖逻辑
     */
    protected function lottery(int $gameId): ?GamePrize
    {
        // 获取所有启用的奖品
        $prizes = GamePrize::where('game_id', $gameId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        if ($prizes->isEmpty()) {
            return null;
        }

        // 构建概率区间
        $ranges = [];
        $cumulative = 0;

        foreach ($prizes as $prize) {
            // 检查奖品是否可用
            if (!$prize->isAvailable()) {
                continue;
            }

            // 检查用户每日限制
            if (!$prize->checkUserDailyLimit($this->userId)) {
                continue;
            }

            $cumulative += $prize->probability;
            $ranges[] = [
                'prize' => $prize,
                'threshold' => $cumulative,
            ];
        }

        if (empty($ranges)) {
            return null;
        }

        // 生成随机数
        $random = mt_rand(1, 10000) / 10000;

        // 确定中奖奖品
        foreach ($ranges as $range) {
            if ($random <= $range['threshold']) {
                $winPrize = $range['prize'];

                // 扣减库存
                $winPrize->deductStock();

                // 更新每日统计
                DailyPrizeStats::incrementIssued(
                    $gameId,
                    $winPrize->id,
                    $this->calculatePrizeValue($winPrize)
                );

                return $winPrize;
            }
        }

        // 如果没有命中任何奖品（概率配置问题），返回第一个可用奖品
        return $ranges[0]['prize'] ?? null;
    }

    /**
     * 发放奖品（抽中后立即发放并标记为已领取）
     */
    protected function issuePrize(GamePrize $prize, int $userId, int $logId): void
    {
        $prizeType = $prize->getData('type');

        switch ($prizeType) {
            case GamePrize::TYPE_POINTS:
                // 发放积分（description 始终存英文，由 PointLog::translateDescription 负责翻译）
                UserPoints::addPoints(
                    $userId,
                    (int)$prize->value,
                    PointLog::SOURCE_GAME,
                    (string)$logId,
                    (int)$prize->value . ' Points'
                );
                break;

            case GamePrize::TYPE_CASH:
                // 现金直接发放到用户钱包
                $this->issueCash($userId, (float)$prize->value, $logId, $prize->getTranslated('name', 'en-us'));
                break;

            case GamePrize::TYPE_COUPON:
                // 发放优惠券到用户账户
                if ($prize->coupon_id) {
                    $this->issueCoupon($userId, $prize->coupon_id, $logId);
                }
                break;

            case GamePrize::TYPE_CHANCE:
                // 发放额外游戏次数
                UserGameChance::addChances(
                    $userId,
                    GameModel::CODE_WHEEL,
                    (int)$prize->value,
                    ChanceLog::SOURCE_TASK,
                    (string)$logId,
                    ''
                );
                break;
        }

        // 立即标记为已领取
        $log = UserGameLog::find($logId);
        if ($log) {
            $log->status = UserGameLog::STATUS_CLAIMED;
            $log->claimed_at = date('Y-m-d H:i:s');
            $log->save();
        }
    }

    /**
     * 领取奖品
     */
    public function claimPrize(int $id)
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        // 获取游戏记录
        $log = UserGameLog::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$log) {
            return $this->error('Prize record not found', 404);
        }

        if ($log->status === UserGameLog::STATUS_CLAIMED) {
            return $this->error('Prize already claimed', 400);
        }

        if ($log->status === UserGameLog::STATUS_EXPIRED) {
            return $this->error('Prize has expired', 400);
        }

        // 检查是否过期
        if ($log->expired_at && strtotime($log->expired_at) < time()) {
            $log->status = UserGameLog::STATUS_EXPIRED;
            $log->save();
            return $this->error('Prize has expired', 400);
        }

        // 获取奖品信息
        $prize = GamePrize::find($log->prize_id);
        if (!$prize) {
            return $this->error('Prize not found', 404);
        }

        // 根据奖品类型发放
        switch ($prize->getData('type')) {
            case GamePrize::TYPE_POINTS:
                // 积分在抽奖时已发放，直接标记为已领取
                break;

            case GamePrize::TYPE_CASH:
                // 现金券：添加到用户钱包余额
                UserPoints::addPoints(
                    $this->userId,
                    (int)($prize->value * 1000), // 转换为积分（$1 = 1000积分）
                    PointLog::SOURCE_GAME,
                    (string)$log->id,
                    (int)($prize->value * 1000) . ' Points'
                );
                break;

            case GamePrize::TYPE_COUPON:
                // 优惠券：发放优惠券到用户账户
                // TODO: 实现优惠券发放逻辑
                break;

            case GamePrize::TYPE_CHANCE:
                // 额外游戏次数在抽奖时已发放，直接标记为已领取
                break;
        }

        // 标记为已领取
        $log->status = UserGameLog::STATUS_CLAIMED;
        $log->claimed_at = date('Y-m-d H:i:s');
        $log->save();

        return $this->success([
            'message' => 'Prize claimed successfully',
            'prize_name' => $prize->getTranslated('name', $this->locale),
            'prize_type' => $prize->getData('type'),
            'prize_value' => (float)$prize->value,
        ]);
    }

    /**
     * 计算奖品价值（用于统计）
     */
    protected function calculatePrizeValue(GamePrize $prize): float
    {
        switch ($prize->getData('type')) {
            case GamePrize::TYPE_CASH:
                return (float)$prize->value;

            case GamePrize::TYPE_POINTS:
                // 1000积分 = $1
                return (float)$prize->value / 1000;

            case GamePrize::TYPE_COUPON:
                // 优惠券估值为面值的一定比例
                return (float)$prize->value * 0.3;

            default:
                return 0;
        }
    }

    /**
     * 发放优惠券
     */
    protected function issueCoupon(int $userId, int $couponId, int $logId): bool
    {
        // 获取优惠券模板
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return false;
        }

        // 计算过期时间（30天有效期）
        $expiredAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        // 创建用户优惠券记录
        UserCoupon::create([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'status' => UserCoupon::STATUS_UNUSED,
            'received_at' => date('Y-m-d H:i:s'),
            'expired_at' => $expiredAt,
        ]);

        // 更新优惠券已领取数量
        $coupon->received_count = $coupon->received_count + 1;
        $coupon->save();

        return true;
    }

    /**
     * 发放现金到用户钱包
     */
    protected function issueCash(int $userId, float $amount, int $logId, string $description): bool
    {
        // 获取或创建用户钱包
        $wallet = UserWallet::getOrCreate($userId);

        // 增加钱包余额
        $wallet->addBalance($amount);

        // 记录交易流水
        Transaction::create([
            'transaction_no' => Transaction::generateNo(),
            'user_id' => $userId,
            'type' => Transaction::TYPE_INCOME,
            'amount' => $amount,
            'balance' => $wallet->balance,
            'title' => 'Game Prize',
            'description' => $description . ' (Game Log #' . $logId . ')',
            'status' => Transaction::STATUS_SUCCESS,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
