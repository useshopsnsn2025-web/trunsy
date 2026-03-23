<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分享奖励配置模型
 */
class ShareRewardConfig extends Model
{
    protected $table = 'share_reward_configs';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 奖励类型
    const TYPE_REGISTER = 'register';       // 注册奖励
    const TYPE_FIRST_ORDER = 'first_order'; // 首单奖励
    const TYPE_SHARE_CLICK = 'share_click'; // 分享点击奖励

    // 奖励目标
    const TARGET_INVITER = 'inviter';   // 邀请人
    const TARGET_INVITEE = 'invitee';   // 被邀请人

    // 奖励方式
    const REWARD_POINTS = 'points';     // 积分
    const REWARD_CHANCES = 'chances';   // 游戏次数
    const REWARD_COUPON = 'coupon';     // 优惠券

    /**
     * 获取单个奖励配置
     */
    public static function getConfig(string $type, string $target): ?self
    {
        return self::where('type', $type)
            ->where('target', $target)
            ->where('status', 1)
            ->find();
    }

    /**
     * 获取多个奖励配置（同一类型可能有多个奖励）
     */
    public static function getConfigs(string $type, string $target): array
    {
        return self::where('type', $type)
            ->where('target', $target)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 发放奖励
     */
    public static function issueReward(array $config, int $userId, string $source, string $sourceId = ''): bool
    {
        $rewardType = $config['reward_type'];
        $rewardValue = (int)$config['reward_value'];

        switch ($rewardType) {
            case self::REWARD_POINTS:
                return UserPoints::addPoints(
                    $userId,
                    $rewardValue,
                    $source,
                    $sourceId,
                    "Invite reward: {$config['type']} - {$config['target']}"
                );

            case self::REWARD_CHANCES:
                $gameCode = $config['game_code'] ?? Game::CODE_WHEEL;
                return UserGameChance::addChances(
                    $userId,
                    $gameCode,
                    $rewardValue,
                    $source,
                    $sourceId
                );

            case self::REWARD_COUPON:
                // TODO: 实现优惠券发放
                if (!empty($config['coupon_id'])) {
                    // 发放指定优惠券
                    return true;
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * 检查每日限制
     */
    public static function checkDailyLimit(int $configId, int $userId): bool
    {
        $config = self::find($configId);
        if (!$config || $config->daily_limit < 0) {
            return true; // 无限制
        }

        // 检查今日已发放次数
        $today = date('Y-m-d');
        $count = \think\facade\Db::name('point_logs')
            ->whereDay('created_at', $today)
            ->where('user_id', $userId)
            ->where('source', 'like', 'invite%')
            ->count();

        return $count < $config->daily_limit;
    }

    /**
     * 获取所有奖励配置（管理后台）
     */
    public static function getAllConfigs(): array
    {
        return self::order('type', 'asc')
            ->order('target', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 转换为 API 数组
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'target' => $this->target,
            'reward_type' => $this->reward_type,
            'reward_value' => (float)$this->reward_value,
            'game_code' => $this->game_code,
            'daily_limit' => (int)$this->daily_limit,
            'total_limit' => (int)$this->total_limit,
            'status' => (int)$this->status,
        ];
    }
}
