<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 计划任务模型
 */
class ScheduledTask extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'scheduled_tasks';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
        'last_result' => 'integer',
        'run_count' => 'integer',
        'fail_count' => 'integer',
        'timeout' => 'integer',
        'is_singleton' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 执行结果常量
     */
    const RESULT_FAIL = 0;
    const RESULT_SUCCESS = 1;

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = $data['status'] ?? 0;
        return $status == self::STATUS_ENABLED ? '启用' : '禁用';
    }

    /**
     * 获取上次执行结果文本
     * @return string
     */
    public function getLastResultTextAttr($value, $data): string
    {
        $result = $data['last_result'] ?? null;
        if ($result === null) {
            return '未执行';
        }
        return $result == self::RESULT_SUCCESS ? '成功' : '失败';
    }

    /**
     * 关联执行日志
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany(ScheduledTaskLog::class, 'task_id', 'id');
    }

    /**
     * 获取启用的任务
     * @return \think\Collection
     */
    public static function getEnabledTasks()
    {
        return self::where('status', self::STATUS_ENABLED)
            ->order('sort', 'desc')
            ->select();
    }

    /**
     * 获取需要执行的任务
     * @return \think\Collection
     */
    public static function getDueTasks()
    {
        return self::where('status', self::STATUS_ENABLED)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->whereOr('next_run_at', '<=', date('Y-m-d H:i:s'));
            })
            ->order('sort', 'desc')
            ->select();
    }
}
