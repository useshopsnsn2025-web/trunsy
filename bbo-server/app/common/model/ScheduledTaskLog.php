<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 计划任务执行日志模型
 */
class ScheduledTaskLog extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'scheduled_task_logs';

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
     * 更新时间字段（不需要）
     * @var bool
     */
    protected $updateTime = false;

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'task_id' => 'integer',
        'duration' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 执行状态常量
     */
    const STATUS_RUNNING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statusMap = [
            self::STATUS_RUNNING => '执行中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
        ];
        return $statusMap[$data['status'] ?? 0] ?? '未知';
    }

    /**
     * 关联任务
     * @return \think\model\relation\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(ScheduledTask::class, 'task_id', 'id');
    }

    /**
     * 创建执行日志
     * @param int $taskId
     * @return static
     */
    public static function start(int $taskId): self
    {
        $log = new self();
        $log->task_id = $taskId;
        $log->started_at = date('Y-m-d H:i:s');
        $log->status = self::STATUS_RUNNING;
        $log->save();
        return $log;
    }

    /**
     * 完成执行
     * @param bool $success
     * @param string|null $output
     * @param string|null $error
     * @return bool
     */
    public function finish(bool $success, ?string $output = null, ?string $error = null): bool
    {
        $this->ended_at = date('Y-m-d H:i:s');
        $this->duration = strtotime($this->ended_at) - strtotime($this->started_at);
        $this->status = $success ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        $this->output = $output;
        $this->error = $error;
        return $this->save();
    }
}
