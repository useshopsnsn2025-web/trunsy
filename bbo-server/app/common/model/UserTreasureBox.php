<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 用户宝箱模型
 */
class UserTreasureBox extends Model
{
    protected $table = 'user_treasure_boxes';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 状态
    const STATUS_PENDING = 'pending';
    const STATUS_OPENED = 'opened';
    const STATUS_EXPIRED = 'expired';

    // 来源
    const SOURCE_CHECKIN = 'checkin';
    const SOURCE_ORDER = 'order';
    const SOURCE_ACTIVITY = 'activity';

    /**
     * 给用户发放宝箱
     */
    public static function grantBox(int $userId, string $boxCode, string $source, string $sourceId = ''): ?self
    {
        $box = TreasureBox::getByCode($boxCode);
        if (!$box) {
            return null;
        }

        return self::create([
            'user_id' => $userId,
            'box_code' => $boxCode,
            'source' => $source,
            'source_id' => $sourceId,
            'status' => self::STATUS_PENDING,
            'expired_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ]);
    }

    /**
     * 获取用户待开启的宝箱列表
     */
    public static function getPendingBoxes(int $userId): array
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->whereOr('expired_at', '>', date('Y-m-d H:i:s'));
            })
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取用户宝箱数量统计
     */
    public static function getBoxCounts(int $userId): array
    {
        $counts = self::where('user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->whereOr('expired_at', '>', date('Y-m-d H:i:s'));
            })
            ->group('box_code')
            ->column('COUNT(*)', 'box_code');

        return [
            'silver_box' => $counts['silver_box'] ?? 0,
            'gold_box' => $counts['gold_box'] ?? 0,
            'diamond_box' => $counts['diamond_box'] ?? 0,
        ];
    }

    /**
     * 开启宝箱
     */
    public function open(int $userId): ?array
    {
        // 验证归属和状态
        if ($this->user_id !== $userId) {
            return null;
        }

        if ($this->status !== self::STATUS_PENDING) {
            return null;
        }

        // 检查是否过期
        if ($this->expired_at && strtotime($this->expired_at) < time()) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
            return null;
        }

        // 获取宝箱配置
        $box = TreasureBox::getByCode($this->box_code);
        if (!$box) {
            return null;
        }

        Db::startTrans();
        try {
            // 执行抽奖
            $prize = $box->lottery();
            if (!$prize) {
                Db::rollback();
                return null;
            }

            // 发放奖品
            $this->issuePrize($userId, $prize);

            // 更新宝箱状态
            $this->status = self::STATUS_OPENED;
            $this->prize_id = $prize->id;
            $this->prize_type = $prize->getData('type');
            $this->prize_value = $prize->value;
            $this->opened_at = date('Y-m-d H:i:s');
            $this->save();

            Db::commit();

            return [
                'prize_id' => $prize->id,
                'prize_type' => $prize->getData('type'),
                'prize_value' => (float)$prize->value,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            \think\facade\Log::error('Open treasure box failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 发放奖品
     */
    protected function issuePrize(int $userId, TreasureBoxPrize $prize): void
    {
        $prizeType = $prize->getData('type');

        switch ($prizeType) {
            case TreasureBoxPrize::TYPE_POINTS:
                UserPoints::addPoints(
                    $userId,
                    (int)$prize->value,
                    PointLog::SOURCE_GAME,
                    (string)$this->id,
                    "Treasure box reward"
                );
                break;

            case TreasureBoxPrize::TYPE_CASH:
                // 现金直接发放到用户钱包
                $wallet = UserWallet::getOrCreate($userId);
                $wallet->addBalance((float)$prize->value);
                Transaction::create([
                    'transaction_no' => Transaction::generateNo(),
                    'user_id' => $userId,
                    'type' => Transaction::TYPE_INCOME,
                    'amount' => (float)$prize->value,
                    'balance' => $wallet->balance,
                    'title' => 'Treasure Box',
                    'description' => "Treasure box cash reward",
                    'status' => Transaction::STATUS_SUCCESS,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                break;

            case TreasureBoxPrize::TYPE_COUPON:
                if ($prize->coupon_id) {
                    // 发放指定优惠券
                    $coupon = Coupon::find($prize->coupon_id);
                    if ($coupon) {
                        UserCoupon::create([
                            'user_id' => $userId,
                            'coupon_id' => $prize->coupon_id,
                            'status' => UserCoupon::STATUS_UNUSED,
                            'received_at' => date('Y-m-d H:i:s'),
                            'expired_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                        ]);
                    }
                }
                break;

            case TreasureBoxPrize::TYPE_CHANCE:
                $gameCode = $prize->game_code ?? Game::CODE_WHEEL;
                UserGameChance::addChances(
                    $userId,
                    $gameCode,
                    (int)$prize->value,
                    ChanceLog::SOURCE_TASK,
                    (string)$this->id
                );
                break;
        }
    }

    /**
     * 获取用户宝箱历史记录
     */
    public static function getHistory(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }
}
