<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 站内通知模型
 */
class Notification extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'notifications';

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
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'is_read' => 'integer',
        'data' => 'json',
    ];

    /**
     * 通知类型常量
     */
    const TYPE_ORDER_CREATED = 'order_created';       // 订单创建
    const TYPE_PAYMENT_SUCCESS = 'payment_success';   // 支付成功
    const TYPE_ORDER_SHIPPED = 'order_shipped';       // 订单发货
    const TYPE_ORDER_DELIVERED = 'order_delivered';   // 订单送达
    const TYPE_ORDER_CANCELLED = 'order_cancelled';   // 订单取消
    const TYPE_REFUND_SUCCESS = 'refund_success';     // 退款成功
    const TYPE_PREAUTH_VOIDED = 'preauth_voided';     // 预授权释放

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
     * 标记为已读
     * @return bool
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->is_read = 1;
        $this->read_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 批量标记已读
     * @param int $userId
     * @param array $ids 通知ID数组，为空则标记全部
     * @return int
     */
    public static function markAsReadBatch(int $userId, array $ids = []): int
    {
        $query = self::where('user_id', $userId)
            ->where('is_read', 0);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取用户未读通知数量
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * 转换为API数组
     * @param string|null $locale 语言代码，用于翻译标题
     * @return array
     */
    public function toApiArray(?string $locale = null): array
    {
        // 使用 getData 确保获取的是数据库字段值，避免与属性名冲突
        $notificationType = $this->getData('type');

        $title = $this->title;
        $content = $this->content;

        if ($locale && $notificationType) {
            $locale = strtolower($locale);

            // 从 notification_templates 获取翻译（会自动触发翻译生成）
            $template = NotificationTemplate::getTemplate($notificationType, 'message', $locale);
            if ($template) {
                // 用模板的标题（未渲染的，不含变量值）
                $translatedTitle = $template->title;
                if ($translatedTitle) {
                    // 模板标题可能含 {order_no} 等占位符，去掉占位符直接用
                    // 因为通知已保存了渲染后的内容，这里只取纯标题部分
                    $title = preg_replace('/\s*[-–—]\s*\{[^}]+\}/', '', $translatedTitle);
                    $title = preg_replace('/\{[^}]+\}/', '', $title);
                    $title = trim($title);
                    if (empty($title)) {
                        $title = $translatedTitle; // 如果清理后为空，保留原始
                    }
                }
            }

            // 对已存储的 content 做动态翻译（仅当 content 不是目标语言时）
            if ($content && $locale !== 'en-us') {
                $content = self::translateContent($content, $locale);
            }
        }

        return [
            'id' => $this->id,
            'type' => $notificationType,
            'category' => $this->getData('category') ?? 'order',
            'title' => $title,
            'content' => $content,
            'data' => $this->data,
            'is_read' => (bool)$this->is_read,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 翻译通知内容（带缓存）
     */
    protected static function translateContent(string $content, string $locale): string
    {
        // 缓存键：内容hash + 语言
        $cacheKey = 'notification_content:' . md5($content) . ':' . $locale;
        $cached = cache($cacheKey);
        if ($cached !== null && $cached !== false) {
            return $cached;
        }

        try {
            $translator = new \app\common\service\TranslateService();
            // 使用 auto 让翻译服务自动检测源语言（内容可能是英文、中文或其他语言）
            $translated = $translator->translate($content, 'auto', $locale);
            if (!empty($translated) && $translated !== $content) {
                // 缓存7天
                cache($cacheKey, $translated, 7 * 86400);
                return $translated;
            }
        } catch (\Exception $e) {
            // 翻译失败，返回原文
        }

        return $content;
    }

}
