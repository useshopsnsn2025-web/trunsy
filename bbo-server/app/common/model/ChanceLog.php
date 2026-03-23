<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 次数获取记录模型
 */
class ChanceLog extends Model
{
    protected $table = 'chance_logs';
    protected $autoWriteTimestamp = false;

    // 来源类型常量
    const SOURCE_LOGIN = 'login';       // 每日登录
    const SOURCE_ORDER = 'order';       // 购物
    const SOURCE_SHARE = 'share';       // 分享
    const SOURCE_TASK = 'task';         // 任务
    const SOURCE_INVITE = 'invite';     // 邀请
    const SOURCE_REVIEW = 'review';     // 评价
    const SOURCE_ADMIN = 'admin';       // 管理员
    const SOURCE_EXCHANGE = 'exchange'; // 积分兑换

    /**
     * 检查今日是否已领取登录奖励
     */
    public static function hasClaimedLoginReward(int $userId, string $gameCode): bool
    {
        $today = date('Y-m-d');
        return self::where('user_id', $userId)
            ->where('game_code', $gameCode)
            ->where('source', self::SOURCE_LOGIN)
            ->whereDay('created_at', $today)
            ->count() > 0;
    }

    /**
     * 检查今日分享次数
     */
    public static function getTodayShareCount(int $userId): int
    {
        $today = date('Y-m-d');
        return self::where('user_id', $userId)
            ->where('source', self::SOURCE_SHARE)
            ->whereDay('created_at', $today)
            ->count();
    }

    /**
     * 获取用户次数获取记录
     */
    public static function getUserLogs(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $query = self::where('user_id', $userId);

        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return [
            'list' => array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'game_code' => $item['game_code'],
                    'source' => $item['source'],
                    'chances' => (int)$item['chances'],
                    'created_at' => $item['created_at'],
                ];
            }, $list),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取来源标签
     */
    public static function getSourceLabel(string $source): string
    {
        $labels = [
            self::SOURCE_LOGIN => 'Daily Login',
            self::SOURCE_ORDER => 'Order',
            self::SOURCE_SHARE => 'Share',
            self::SOURCE_TASK => 'Task',
            self::SOURCE_INVITE => 'Invite',
            self::SOURCE_REVIEW => 'Review',
            self::SOURCE_ADMIN => 'Admin',
            self::SOURCE_EXCHANGE => 'Points Exchange',
        ];
        return $labels[$source] ?? $source;
    }
}
