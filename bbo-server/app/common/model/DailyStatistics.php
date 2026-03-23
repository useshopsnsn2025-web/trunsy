<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 每日统计模型
 */
class DailyStatistics extends Model
{
    protected $name = 'daily_statistics';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'new_users' => 'integer',
        'active_users' => 'integer',
        'new_goods' => 'integer',
        'new_orders' => 'integer',
        'completed_orders' => 'integer',
        'order_amount' => 'float',
        'commission' => 'float',
        'pv' => 'integer',
        'uv' => 'integer',
    ];

    /**
     * 获取或创建今日统计
     */
    public static function getOrCreateToday(): self
    {
        $today = date('Y-m-d');
        $stat = self::where('date', $today)->find();
        if (!$stat) {
            $stat = new self();
            $stat->date = $today;
            $stat->save();
        }
        return $stat;
    }

    /**
     * 增加计数
     */
    public static function increment(string $field, int $value = 1): void
    {
        $stat = self::getOrCreateToday();
        $stat->$field = $stat->$field + $value;
        $stat->save();
    }
}
