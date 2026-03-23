<?php
declare(strict_types=1);

namespace app\api\controller;

use app\common\model\UserCheckin;

/**
 * 签到控制器
 */
class Checkin extends Base
{
    /**
     * 获取签到状态
     */
    public function status()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $status = UserCheckin::getStatus($this->userId);

        return $this->success($status);
    }

    /**
     * 执行签到
     */
    public function checkin()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $result = UserCheckin::checkin($this->userId);

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'Check-in failed', 400);
        }

        return $this->success([
            'continuous_days' => $result['continuous_days'],
            'reward_points' => $result['points_earned'],
            'extra_reward' => $result['extra_reward'],
        ]);
    }

    /**
     * 获取签到日历
     */
    public function calendar()
    {
        if (!$this->userId) {
            return $this->error('Please login first', 401);
        }

        $month = $this->request->get('month');
        $calendar = UserCheckin::getCalendar($this->userId, $month);

        return $this->success($calendar);
    }
}
