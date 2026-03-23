<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 操作日志模型
 */
class OperationLog extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'operation_logs';

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
     * 更新时间字段（不需要更新时间）
     * @var bool
     */
    protected $updateTime = false;

    /**
     * 类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'params' => 'json',
        'result' => 'json',
    ];

    /**
     * 记录操作日志
     * @param array $data
     * @return bool
     */
    public static function record(array $data): bool
    {
        $log = new self();
        $log->admin_id = $data['admin_id'] ?? null;
        $log->admin_name = $data['admin_name'] ?? null;
        $log->module = $data['module'] ?? '';
        $log->action = $data['action'] ?? '';
        $log->method = $data['method'] ?? '';
        $log->url = $data['url'] ?? '';
        $log->params = $data['params'] ?? null;
        $log->result = $data['result'] ?? null;
        $log->ip = $data['ip'] ?? null;
        $log->user_agent = $data['user_agent'] ?? null;
        return $log->save();
    }

    /**
     * 关联管理员
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}
