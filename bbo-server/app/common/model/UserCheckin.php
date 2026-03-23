<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 用户签到模型
 */
class UserCheckin extends Model
{
    protected $table = 'user_checkins';
    protected $autoWriteTimestamp = false;

    /**
     * 签到奖励配置
     */
    public static function getRewardConfig(): array
    {
        return [
            1 => ['points' => 5, 'extra' => null],
            2 => ['points' => 10, 'extra' => null],
            3 => ['points' => 15, 'extra' => null],
            4 => ['points' => 20, 'extra' => '5% coupon'],
            5 => ['points' => 25, 'extra' => null],
            6 => ['points' => 30, 'extra' => null],
            7 => ['points' => 50, 'extra' => 'silver_box'],
            14 => ['points' => 50, 'extra' => 'gold_box'],
            30 => ['points' => 100, 'extra' => 'diamond_box'],
        ];
    }

    /**
     * 检查今日是否已签到
     */
    public static function hasCheckedInToday(int $userId): bool
    {
        $today = date('Y-m-d');
        return self::where('user_id', $userId)
            ->where('checkin_date', $today)
            ->count() > 0;
    }

    /**
     * 获取用户连续签到天数
     */
    public static function getContinuousDays(int $userId): int
    {
        // 获取昨天的签到记录
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $record = self::where('user_id', $userId)
            ->where('checkin_date', $yesterday)
            ->find();

        if ($record) {
            return (int)$record->continuous_days;
        }

        // 检查今天是否已签到
        $today = date('Y-m-d');
        $todayRecord = self::where('user_id', $userId)
            ->where('checkin_date', $today)
            ->find();

        if ($todayRecord) {
            return (int)$todayRecord->continuous_days;
        }

        return 0;
    }

    /**
     * 执行签到
     */
    public static function checkin(int $userId): array
    {
        $today = date('Y-m-d');

        // 检查是否已签到
        if (self::hasCheckedInToday($userId)) {
            return ['success' => false, 'message' => 'Already checked in today'];
        }

        // 获取连续签到天数
        $continuousDays = self::getContinuousDays($userId) + 1;

        // 获取奖励配置
        $config = self::getRewardConfig();
        $reward = $config[$continuousDays] ?? $config[min(array_filter(array_keys($config), function($k) use ($continuousDays) {
            return $k <= $continuousDays;
        }))];

        // 创建签到记录
        $record = self::create([
            'user_id' => $userId,
            'checkin_date' => $today,
            'continuous_days' => $continuousDays,
            'reward_type' => 'points',
            'reward_value' => $reward['points'],
            'extra_reward' => $reward['extra'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 发放积分奖励
        UserPoints::addPoints(
            $userId,
            $reward['points'],
            PointLog::SOURCE_CHECKIN,
            (string)$record->id,
            "Day {$continuousDays} check-in reward"
        );

        // 处理额外奖励
        $extraRewardResult = null;
        if ($reward['extra']) {
            $extraRewardResult = self::processExtraReward($userId, $reward['extra'], $continuousDays);
        }

        return [
            'success' => true,
            'continuous_days' => $continuousDays,
            'points_earned' => $reward['points'],
            'extra_reward' => $extraRewardResult,
        ];
    }

    /**
     * 处理额外奖励
     */
    protected static function processExtraReward(int $userId, string $extraReward, int $day): ?array
    {
        // 处理宝箱奖励
        if (in_array($extraReward, ['silver_box', 'gold_box', 'diamond_box'])) {
            // 发放宝箱给用户
            $userBox = UserTreasureBox::grantBox(
                $userId,
                $extraReward,
                UserTreasureBox::SOURCE_CHECKIN,
                "day_{$day}"
            );

            if ($userBox) {
                return [
                    'type' => 'box',
                    'box_type' => $extraReward,
                    'box_id' => $userBox->id,
                ];
            }
            return null;
        }

        // 处理优惠券奖励
        if (strpos($extraReward, 'coupon') !== false) {
            // 解析优惠券折扣值（如 "5% coupon" -> 5）
            preg_match('/(\d+)%/', $extraReward, $matches);
            $discount = $matches[1] ?? 5;

            // TODO: 根据实际优惠券系统发放
            return [
                'type' => 'coupon',
                'coupon_name' => $extraReward,
                'discount' => (int)$discount,
            ];
        }

        return null;
    }

    /**
     * 获取签到日历
     */
    public static function getCalendar(int $userId, string $month = null): array
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $records = self::where('user_id', $userId)
            ->where('checkin_date', '>=', $startDate)
            ->where('checkin_date', '<=', $endDate)
            ->select()
            ->toArray();

        $checkinDates = [];
        foreach ($records as $record) {
            $checkinDates[$record['checkin_date']] = [
                'points' => (int)$record['reward_value'],
                'continuous_days' => (int)$record['continuous_days'],
                'extra_reward' => $record['extra_reward'],
            ];
        }

        return [
            'month' => $month,
            'checkin_dates' => $checkinDates,
            'continuous_days' => self::getContinuousDays($userId),
            'checked_in_today' => self::hasCheckedInToday($userId),
        ];
    }

    /**
     * 获取签到状态
     */
    public static function getStatus(int $userId): array
    {
        $continuousDays = self::getContinuousDays($userId);
        $checkedInToday = self::hasCheckedInToday($userId);
        $config = self::getRewardConfig();

        // 获取今日奖励（如果还没签到）
        $todayRewardDay = $checkedInToday ? $continuousDays : $continuousDays + 1;
        $todayReward = $config[$todayRewardDay] ?? $config[min(array_filter(array_keys($config), function($k) use ($todayRewardDay) {
            return $k <= $todayRewardDay;
        }))];

        // 生成未来 7 天的签到日历
        $calendar = [];
        $today = date('Y-m-d');

        // 获取用户历史签到记录（过去 7 天）
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $records = self::where('user_id', $userId)
            ->where('checkin_date', '>=', $startDate)
            ->where('checkin_date', '<=', $today)
            ->column('checkin_date');

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $dayNumber = $continuousDays + $i + ($checkedInToday ? 1 : 1);

            // 找到对应天数的奖励配置
            $dayReward = $config[$dayNumber] ?? null;
            if (!$dayReward) {
                // 找不到精确匹配时，使用小于等于该天数的最大配置
                $matchingDays = array_filter(array_keys($config), function($k) use ($dayNumber) {
                    return $k <= $dayNumber;
                });
                $dayReward = $matchingDays ? $config[max($matchingDays)] : $config[1];
            }

            $calendar[] = [
                'date' => $date,
                'day' => $dayNumber,
                'reward_points' => $dayReward['points'],
                'extra_reward' => $dayReward['extra'],
                'checked' => $i === 0 ? $checkedInToday : false,
            ];
        }

        return [
            'continuous_days' => $continuousDays,
            'checked_today' => $checkedInToday,
            'today_reward' => [
                'points' => $todayReward['points'],
                'extra_reward' => $todayReward['extra'],
            ],
            'calendar' => $calendar,
            'reward_config' => $config,
        ];
    }
}
