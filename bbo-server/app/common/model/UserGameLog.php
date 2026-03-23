<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户游戏记录模型
 */
class UserGameLog extends Model
{
    protected $table = 'user_game_logs';
    protected $autoWriteTimestamp = false;

    // 状态常量
    const STATUS_PENDING = 'pending';   // 待领取
    const STATUS_CLAIMED = 'claimed';   // 已领取
    const STATUS_EXPIRED = 'expired';   // 已过期

    /**
     * 创建游戏记录
     */
    public static function createLog(int $userId, int $gameId, string $gameCode, ?GamePrize $prize, string $ip = '', string $deviceId = ''): self
    {
        $data = [
            'user_id' => $userId,
            'game_id' => $gameId,
            'game_code' => $gameCode,
            'ip' => $ip,
            'device_id' => $deviceId,
            'created_at' => date('Y-m-d H:i:s'),
            'expired_at' => date('Y-m-d H:i:s', strtotime('+30 days')), // 30天有效期
        ];

        if ($prize) {
            $data['prize_id'] = $prize->id;
            $data['prize_type'] = $prize->getData('type');
            $data['prize_value'] = $prize->value;
            $data['prize_name'] = $prize->getTranslated('name', 'en-us');
        }

        return self::create($data);
    }

    /**
     * 获取用户游戏记录
     */
    public static function getUserLogs(int $userId, int $page = 1, int $pageSize = 20, ?string $gameCode = null, ?string $locale = null): array
    {
        $query = self::where('user_id', $userId);

        if ($gameCode) {
            $query->where('game_code', $gameCode);
        }

        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $pageSize)
            ->select();

        // 获取所有涉及的奖品ID
        $prizeIds = [];
        foreach ($list as $item) {
            if ($item->prize_id) {
                $prizeIds[] = $item->prize_id;
            }
        }

        // 批量获取奖品翻译名称
        $prizeNames = [];
        if (!empty($prizeIds) && $locale) {
            $prizes = GamePrize::whereIn('id', array_unique($prizeIds))->select();
            foreach ($prizes as $prize) {
                $prizeNames[$prize->id] = $prize->getTranslated('name', $locale);
            }
        }

        return [
            'list' => array_map(function ($item) use ($prizeNames, $locale) {
                // 优先使用翻译后的奖品名称，否则使用快照名称
                $prizeName = $item['prize_name'];
                if ($locale && isset($prizeNames[$item['prize_id']])) {
                    $prizeName = $prizeNames[$item['prize_id']];
                }

                return [
                    'id' => $item['id'],
                    'game_code' => $item['game_code'],
                    'prize_type' => $item['prize_type'],
                    'prize_value' => (float)$item['prize_value'],
                    'prize_name' => $prizeName,
                    'status' => $item['status'],
                    'created_at' => $item['created_at'],
                    'expired_at' => $item['expired_at'],
                    'claimed_at' => $item['claimed_at'],
                ];
            }, $list->toArray()),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取最近中奖记录（用于播报）
     */
    public static function getRecentWinners(int $limit = 50, float $minValue = 10): array
    {
        return self::alias('l')
            ->join('users u', 'l.user_id = u.id')
            ->where('l.prize_value', '>=', $minValue)
            ->where('l.created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->field('l.id, l.prize_id, l.prize_type, l.prize_name, l.prize_value, l.created_at, u.nickname')
            ->order('l.created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 脱敏用户名
     */
    public static function maskUsername(string $name): string
    {
        if (empty($name)) {
            return '***';
        }

        $length = mb_strlen($name);
        if ($length <= 2) {
            return mb_substr($name, 0, 1) . '**';
        }

        return mb_substr($name, 0, 1) . '***' . mb_substr($name, -1);
    }

    /**
     * 领取奖品
     */
    public function claim(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // 检查是否过期
        if ($this->expired_at && strtotime($this->expired_at) < time()) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
            return false;
        }

        $this->status = self::STATUS_CLAIMED;
        $this->claimed_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}
