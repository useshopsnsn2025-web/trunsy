<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 邮件发送记录模型
 */
class EmailLog extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'email_logs';

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
        'user_id' => 'integer',
        'status' => 'integer',
        'data' => 'json',
    ];

    /**
     * 状态常量
     */
    const STATUS_PENDING = 0;   // 待发送
    const STATUS_SENT = 1;      // 已发送
    const STATUS_FAILED = 2;    // 发送失败

    /**
     * 状态文本
     */
    const STATUS_TEXT = [
        0 => '待发送',
        1 => '已发送',
        2 => '发送失败',
    ];

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->field('id,uuid,nickname,avatar,email');
    }

    /**
     * 标记为已发送
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 标记为发送失败
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        return $this->save();
    }

    /**
     * 转换为API数组
     * @return array
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'to_email' => $this->to_email,
            'to_name' => $this->to_name,
            'subject' => $this->subject,
            'template' => $this->template,
            'status' => $this->status,
            'status_text' => self::STATUS_TEXT[$this->status] ?? 'Unknown',
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
